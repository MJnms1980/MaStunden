<?php
declare(strict_types=1);
function update_state(): ?array { $p=storage_path('update-state.json');return is_file($p)?json_decode(file_get_contents($p),true,32,JSON_THROW_ON_ERROR):null; }
function update_save(array $s): void {mu_write(storage_path('update-state.json'),json_encode($s,JSON_THROW_ON_ERROR));}
function update_admin(): void {
 require_admin();$bucket='maintenance:'.user()['id'];$since=gmdate('Y-m-d H:i:s',time()-900);
 if(count(rows('attempts','bucket=? AND attempted_at>?',[$bucket,$since]))>=5)fail('Zu viele Passwortversuche. Bitte 15 Minuten warten.');
 if(!password_verify((string)($_POST['admin_password']??''),user()['password'])){insert('attempts',['bucket'=>$bucket,'attempted_at'=>now()]);fail('Administratorpasswort prüfen.');}
 q('DELETE FROM '.table('attempts').' WHERE bucket=?',[$bucket]);
}
function update_clear_dir(string $dir): void {if(is_dir($dir)){foreach(scandir($dir) as $n)if($n!=='.'&&$n!=='..'){if(!is_file($dir.'/'.$n)||is_link($dir.'/'.$n)||!unlink($dir.'/'.$n))fail('Update-Dateireste konnten nicht entfernt werden.');}if(!rmdir($dir))fail('Updateordner konnte nicht entfernt werden.');}}
function update_manifest(ZipArchive $z): array {
 if(!function_exists('sodium_crypto_sign_verify_detached'))fail('Der Server benötigt die PHP-Erweiterung Sodium für geprüfte Updates.');
 if($z->numFiles>160)fail('Zu viele Dateien im Update.');
 $seen=[];$total=0;for($i=0;$i<$z->numFiles;$i++){$s=$z->statIndex($i);if(!$s||isset($seen[$s['name']])||$s['size']>8*1048576)fail('Ungültiger oder zu großer Archiveintrag.');$seen[$s['name']]=true;$total+=$s['size'];}if($total>32*1048576)fail('Update entpackt größer als 32 MB.');
 $raw=$z->getFromName('manifest.json');$sig=$z->getFromName('manifest.sig');$pk=base64_decode(trim(file_get_contents(dirname(__DIR__).'/update-public.key')),true);
 if(!is_string($raw)||strlen($raw)>131072||!is_string($sig)||strlen($sig)!==SODIUM_CRYPTO_SIGN_BYTES||!is_string($pk)||strlen($pk)!==SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES||!sodium_crypto_sign_verify_detached($sig,$raw,$pk))fail('Das Update besitzt keine gültige IT-Janz-Signatur.');
 $m=json_decode($raw,true,32,JSON_THROW_ON_ERROR);
 if(($m['app']??'')!=='MaStunden'||($m['protocol']??0)!==1||($m['schema']??0)!==1||!preg_match('/^\d+\.\d+\.\d+$/D',$m['version']??'')||!version_compare($m['version'],APP_VERSION,'>')||!version_compare(APP_VERSION,$m['minimum_from']??'999.0.0','>=')||PHP_VERSION_ID<($m['php_min']??PHP_INT_MAX))fail('Dieses Update passt nicht zur installierten Version oder PHP-Version.');
 foreach(['index.php','update-bootstrap.php','update-public.key'] as $n)if(!isset($m['bootstrap'][$n])||!hash_equals($m['bootstrap'][$n],hash_file('sha256',dirname(__DIR__).'/'.$n)))fail('Dieses Update benötigt zunächst eine manuelle Aktualisierung des Update-Assistenten.');
 if(!is_array($m['files']??null)||count($m['files'])+2!==$z->numFiles)fail('Unvollständiges Update.');
 foreach(['app.php','lib/core.php','lib/schema.php','lib/updater.php'] as $n)if(!isset($m['files'][$n]))fail('Erforderliche Programmdatei fehlt.');
 foreach($m['files'] as $n=>$hash){if(!mu_path($n)||!preg_match('/^[a-f0-9]{64}$/D',$hash)||!isset($seen['web/'.$n]))fail('Unzulässiger Updatepfad.');$data=$z->getFromName('web/'.$n);if($data===false||!hash_equals($hash,hash('sha256',$data)))fail('Die Prüfsumme einer Programmdatei stimmt nicht.');}
 // Complete replacement only: no obsolete executable files are silently retained.
 foreach(array_merge(glob(dirname(__DIR__).'/lib/*.php')?:[],glob(dirname(__DIR__).'/assets/*')?:[],[dirname(__DIR__).'/app.php',dirname(__DIR__).'/install.php']) as $p){$n=substr($p,strlen(dirname(__DIR__))+1);if(is_file($p)&&mu_path($n)&&!isset($m['files'][$n]))fail('Das Update enthält nicht alle vorhandenen Programmdateien. Manuelle Aktualisierung erforderlich.');}
 return $m;
}
function update_prepare(): void {
 update_admin();no_maintenance();if(update_state())fail('Es ist bereits ein Update vorbereitet.');
 $f=$_FILES['package']??null;if(!$f||$f['error']!==UPLOAD_ERR_OK||$f['size']>16*1048576)fail('Update-ZIP fehlt oder überschreitet das Uploadlimit (höchstens 16 MB).');
 $z=new ZipArchive();if($z->open($f['tmp_name'])!==true)fail('Update-ZIP kann nicht geöffnet werden.');
 try{$m=update_manifest($z);$root=dirname(__DIR__);foreach($m['files'] as $n=>$hash){$p=$root.'/'.$n;if(is_link($p)||is_link(dirname($p))||!is_writable(dirname($p)))fail('Programmordner ist nicht schreibbar. Bitte das Update anhand der FTP-Anleitung installieren.');}
  $free=disk_free_space(cfg()['storage']);if($free===false||$free<100*1048576)fail('Mindestens 100 MB freier Speicher werden für die Updatevorbereitung benötigt.');
  password_rule(post('backup_password'));$dir=storage_path('update-stage');update_clear_dir($dir);if(!mkdir($dir,0700))fail('Updateordner konnte nicht angelegt werden.');
  foreach($m['files'] as $n=>$hash)mu_write($dir.'/'.str_replace('/','_',$n),$z->getFromName('web/'.$n));
  start_backup(post('backup_password'));
  update_save(['phase'=>'backup','owner'=>(int)user()['id'],'manifest'=>$m,'backup'=>job()['archive'],'created'=>now()]);
 }finally{$z->close();}
 audit('prepare','update',null,null,['version'=>$m['version']]);flash('Update geprüft. Jetzt wird das verschlüsselte Sicherheitsbackup erstellt.');
}
function update_backup_verified(array $s): void {
 $p=storage_path($s['backup']);$pass=$_SESSION['backup_password']??'';
 if(!is_file($p)||$pass===''||($_SESSION['latest_backup']??'')!==$s['backup'])fail('Sicherheitsbackup fehlt oder Sitzung abgelaufen. Update abbrechen und neu vorbereiten.');
 $z=archive_open($p,$pass);try{$raw=$z->getFromName('manifest.json');$mac=$z->getFromName('manifest.hmac');if(!is_string($raw)||!is_string($mac)||!hash_equals(hash_hmac('sha256',$raw,$pass),$mac))fail('Sicherheitsbackup konnte nicht bestätigt werden.');$m=json_decode($raw,true,32,JSON_THROW_ON_ERROR);foreach($m['entries'] as $n=>$meta){$stream=$z->getStream($n);if(!$stream)fail('Sicherheitsbackup unvollständig.');$ctx=hash_init('sha256');hash_update_stream($ctx,$stream);fclose($stream);if(!hash_equals($meta['sha256'],hash_final($ctx)))fail('Sicherheitsbackup beschädigt.');}}finally{$z->close();}
}
function update_apply(): void {
 update_admin();$s=update_state();if(!$s||$s['owner']!==(int)user()['id']||job())fail('Updatevorbereitung ist nicht abgeschlossen.');
 update_backup_verified($s);if(post('confirm')!=='UPDATE INSTALLIEREN')fail('Bitte UPDATE INSTALLIEREN zur Bestätigung eingeben.');
 $root=dirname(__DIR__);$storage=cfg()['storage'];
 // Drop the application lock before upgrading the outer lock to avoid lock-order inversion.
 app_unlock();flock($GLOBALS['update_mutex'],LOCK_UN);mu_lock(LOCK_EX);app_lock();
 $current=update_state();if(!$current||$current!==$s||job())fail('Updatezustand wurde geändert. Bitte neu laden.');
 $rollback=storage_path('update-rollback');update_clear_dir($rollback);if(!mkdir($rollback,0700))fail('Rücknahmeordner konnte nicht angelegt werden.');$j=['files'=>[]];
 foreach($s['manifest']['files'] as $n=>$hash){if(!mu_path($n))fail('Updatepfad ungültig.');$data=file_get_contents(storage_path('update-stage').'/'.str_replace('/','_',$n));if(!hash_equals($hash,hash('sha256',$data)))fail('Vorbereitete Datei wurde verändert.');$p=$root.'/'.$n;if(is_file($p)){$old=file_get_contents($p);mu_write($rollback.'/'.str_replace('/','_',$n),$old);$j['files'][$n]=hash('sha256',$old);}else $j['files'][$n]=null;}
 mu_write(storage_path('update-journal.json'),json_encode($j,JSON_THROW_ON_ERROR));
 try{
  foreach($s['manifest']['files'] as $n=>$hash)mu_replace($root,$n,file_get_contents(storage_path('update-stage').'/'.str_replace('/','_',$n)));
  foreach($s['manifest']['files'] as $n=>$hash)if(!hash_equals($hash,hash_file('sha256',$root.'/'.$n)))fail('Dateiprüfung nach Update fehlgeschlagen.');
  audit('install','update',null,['version'=>APP_VERSION],['version'=>$s['manifest']['version']]);
  mu_write(storage_path('update-result.json'),json_encode(['status'=>'installed','version'=>$s['manifest']['version'],'time'=>now(),'backup'=>$s['backup']]));
  // Removing the durable journal is the commit point. The next request rolls back until then.
  if(!unlink(storage_path('update-state.json')))fail('Updatezustand konnte nicht abgeschlossen werden.');
  if(!unlink(storage_path('update-journal.json')))fail('Updateabschluss konnte nicht gespeichert werden.');
 }catch(Throwable $e){mu_recover($root,$storage);throw $e;}
 @unlink(storage_path('update-state.json'));flash('Update installiert. Das Sicherheitsbackup bleibt gespeichert.');
}
function update_action(string $a): void {
 require_admin();if($a==='update_prepare'){update_prepare();go('index.php?page=backup');}
 if($a==='update_apply'){update_apply();go('index.php?page=backup');}
 if($a==='update_cancel'){update_admin();$s=update_state();if(job())fail('Bitte zuerst die laufende Sicherung unter Backup abbrechen.');if($s){audit('cancel','update',null,null,['version'=>$s['manifest']['version']]);if(!unlink(storage_path('update-state.json')))fail('Update konnte nicht abgebrochen werden.');}update_clear_dir(storage_path('update-stage'));flash('Update abgebrochen. Programm und gespeicherte Backups bleiben erhalten.');return;}
 fail('Unbekannter Updatevorgang.');
}
function view_update(): void {
 require_admin();echo '<section class="card"><h2>Update-Assistent</h2><p>Installieren Sie ein von IT-Janz signiertes Updatepaket. Zugangsdaten, Mitarbeiterdaten und Unterlagen bleiben erhalten. Während der Vorbereitung sind Änderungen gesperrt.</p>';
 $s=update_state();if($s){echo '<p>Vorbereitetes Update: <strong>'.h($s['manifest']['version']).'</strong></p>';if(job())echo '<p><a class="button" href="index.php?page=backup">Sicherheitsbackup fortsetzen</a></p>';else{echo '<p><a class="button secondary" href="index.php?backup_download='.h($s['backup']).'">Sicherheitsbackup herunterladen</a></p><p>Backup-Passwort getrennt aufbewahren. Bei einer Unterbrechung des Dateiaustauschs wird beim nächsten Aufruf die bisherige Programmversion wiederhergestellt.</p>';form_start('update_apply');field('Administratorpasswort','admin_password','','password','required autocomplete="current-password"');field('UPDATE INSTALLIEREN eingeben','confirm','','text','required');submit('Update installieren');}form_start('update_cancel');field('Administratorpasswort','admin_password','','password','required');submit('Vorbereitung abbrechen','secondary');}
 else{echo '<p><a href="https://github.com/MJnms1980/MaStunden/releases" target="_blank" rel="noopener noreferrer">Offizielle Downloads öffnen</a></p><p>Verwenden Sie das separate Updatepaket, nicht das Installationspaket. Updates mit Datenbankumbau oder neuer Assistentenversion werden ausdrücklich abgewiesen und müssen nach ihrer Anleitung installiert werden.</p>';form_start('update_prepare',[],true);field('Signiertes Updatepaket (ZIP)','package','','file','required accept=".zip"');field('Neues Passwort für das Sicherheitsbackup','backup_password','','password','required minlength="12" maxlength="72" autocomplete="new-password"');field('Administratorpasswort','admin_password','','password','required autocomplete="current-password"');submit('Paket prüfen und Sicherheitsbackup starten');}
 echo '</section>';
}
