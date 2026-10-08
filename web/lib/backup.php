<?php
declare(strict_types=1);
function job(): ?array { $p=storage_path('maintenance.json');return is_file($p)?json_decode(file_get_contents($p),true):null; }
function save_job(array $j): void { $tmp=storage_path('maintenance.new');file_put_contents($tmp,json_encode($j,JSON_UNESCAPED_UNICODE),LOCK_EX);rename($tmp,storage_path('maintenance.json')); }
function archive_open(string $file,string $password,bool $create=false): ZipArchive { $z=new ZipArchive();$ok=$z->open($file,$create?ZipArchive::CREATE:0);if($ok!==true)fail('Sicherungsarchiv konnte nicht geöffnet werden.');$z->setPassword($password);return $z; }
function zip_string(ZipArchive $z,string $name,string $data): void { if(!$z->addFromString($name,$data)||!$z->setEncryptionName($name,ZipArchive::EM_AES_256))fail('Verschlüsselung des Backups fehlgeschlagen.'); }
function start_backup(string $password,?string $restore=null): void {
 if(job())fail('Ein Sicherungsvorgang läuft bereits.');password_rule($password);if(!ZipArchive::isEncryptionMethodSupported(ZipArchive::EM_AES_256))fail('Der Server unterstützt keine AES-verschlüsselten ZIP-Backups.');
 $files=array_column(rows('documents'),'storage_name');foreach(['company_logo','developer_logo'] as $k)if(setting($k)!=='')$files[]=setting($k);$files=array_values(array_unique($files));$id=bin2hex(random_bytes(12));
 $_SESSION['backup_password']=$password;$j=['id'=>$id,'mode'=>'backup','phase'=>'tables','table'=>0,'offset'=>0,'files'=>$files,'file_index'=>0,'manifest'=>['app'=>'MaStunden','version'=>APP_VERSION,'schema'=>1,'created'=>now(),'entries'=>[]],'archive'=>'backup-'.$id.'.zip','owner'=>(int)user()['id'],'restore'=>$restore];save_job($j);
}
function backup_step(): void {
 $j=job();if(!$j)fail('Kein laufender Vorgang.');if($j['mode']!=='backup')fail('Ungültige Sicherungsphase.');$password=$_SESSION['backup_password']??'';if($password==='')fail('Bitte Sicherung abbrechen und nach erneuter Anmeldung neu starten.');$z=archive_open(storage_path($j['archive']),$password,true);$names=array_keys(schema());
 if($j['phase']==='tables'){
  $name=$names[$j['table']];$data=q('SELECT * FROM '.table($name).' ORDER BY '.($name==='settings'?'k':'id').' LIMIT 200 OFFSET '.(int)$j['offset'])->fetchAll();$entry='tables/'.$name.'-'.$j['offset'].'.json';$json=json_encode($data,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);zip_string($z,$entry,$json);$j['manifest']['entries'][$entry]=['sha256'=>hash('sha256',$json),'size'=>strlen($json),'table'=>$name];if(count($data)<200){$j['table']++;$j['offset']=0;}else $j['offset']+=200;if($j['table']>=count($names))$j['phase']='files';
 }elseif($j['phase']==='files'){
  if(isset($j['files'][$j['file_index']])){$file=$j['files'][$j['file_index']];$p=storage_path($file);if(!is_file($p))fail('Eine gespeicherte Datei fehlt. Sicherung abgebrochen: '.$file);$entry='files/'.$file;if(!$z->addFile($p,$entry)||!$z->setEncryptionName($entry,ZipArchive::EM_AES_256))fail('Dateisicherung fehlgeschlagen.');$j['manifest']['entries'][$entry]=['sha256'=>hash_file('sha256',$p),'size'=>filesize($p)];$j['file_index']++;}else $j['phase']='finish';
 }else{
  $json=json_encode($j['manifest'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);zip_string($z,'manifest.json',$json);zip_string($z,'manifest.hmac',hash_hmac('sha256',$json,$password));$z->close();$_SESSION['latest_backup']=$j['archive'];$_SESSION['backup_created']=time();
  if($j['restore']){$r=json_decode(file_get_contents(storage_path('restore-state.json')),true);$r['safety']=$j['archive'];$r['phase']='verify';save_job($r);}else{unlink(storage_path('maintenance.json'));flash('Verschlüsseltes Backup erstellt. Jetzt herunterladen und Passwort getrennt aufbewahren.');}return;
 }
 if(!$z->close())fail('Backup konnte nicht gespeichert werden.');save_job($j);
}
function restore_upload(): void {
 if(job())fail('Ein Vorgang läuft bereits.');if(post('confirm')!=='WIEDERHERSTELLEN')fail('Zur Bestätigung WIEDERHERSTELLEN eingeben.');$pass=post('archive_password');password_rule(post('backup_password'));$id=bin2hex(random_bytes(8));$name='restore-'.$id.'.zip';if(isset($_POST['use_ftp'])){$source=storage_path('incoming-backup.zip');if(!is_file($source)||filesize($source)>2*1024*1024*1024)fail('incoming-backup.zip fehlt im privaten Ordner oder ist größer als 2 GB.');if(!copy($source,storage_path($name)))fail('FTP-Archiv konnte nicht vorbereitet werden.');}else{$f=$_FILES['archive']??null;if(!$f||$f['error']!==UPLOAD_ERR_OK)fail('Archivupload fehlgeschlagen. Hosting-Uploadlimit beachten.');if($f['size']>512*1048576)fail('Browser-Wiederherstellung unterstützt Archive bis 512 MB; größere Archive per FTP in den privaten Ordner übertragen.');if(!move_uploaded_file($f['tmp_name'],storage_path($name)))fail('Archiv konnte nicht gespeichert werden.');}$z=archive_open(storage_path($name),$pass);$raw=$z->getFromName('manifest.json');$mac=$z->getFromName('manifest.hmac');if($raw===false||$mac===false||!hash_equals(hash_hmac('sha256',$raw,$pass),$mac)){ $z->close();unlink(storage_path($name));fail('Falsches Passwort oder beschädigtes Archiv.');}$m=json_decode($raw,true,512,JSON_THROW_ON_ERROR);if(($m['app']??'')!=='MaStunden'||($m['schema']??0)!==1||!in_array(($m['version']??''),['1.0.0','1.0.1','1.0.2','1.0.3','1.0.4','1.0.5','1.0.6',APP_VERSION],true))fail('Backup passt nicht zur installierten Version.');$size=0;$tables=[];foreach($m['entries'] as $n=>$meta){if(!preg_match('~^(tables/[a-z]+-[0-9]+\.json|files/[a-f0-9]{48}\.(pdf|jpg|png))$~D',$n))fail('Unzulässiger Pfad im Backup.');$stat=$z->statName($n);if(!$stat||(int)$stat['size']!==(int)$meta['size'])fail('Archiv ist unvollständig.');$size+=(int)$meta['size'];if((int)$meta['size']>64*1048576)fail('Ein Sicherungsbestandteil überschreitet 64 MB.');if(str_starts_with($n,'tables/')){if(!isset($meta['table'])||!array_key_exists($meta['table'],schema()))fail('Unbekannte Tabelle im Backup.');$tables[$meta['table']]=true;}}
 if(count($tables)!==count(schema())||$size>2*1024*1024*1024)fail('Backup unvollständig oder entpackt größer als 2 GB.');$z->close();
 $_SESSION['restore_password']=$pass;$r=['id'=>$id,'mode'=>'restore','phase'=>'verify','archive'=>$name,'manifest'=>$m,'entries'=>array_keys($m['entries']),'index'=>0,'owner'=>(int)user()['id'],'prefix'=>cfg()['prefix'].'r'.$id.'_','safety'=>null];file_put_contents(storage_path('restore-state.json'),json_encode($r));start_backup(post('backup_password'),$name);
}
function staging_table(array $j,string $name): string { if(!array_key_exists($name,schema()))fail('Unbekannte Tabelle.');return '`'.$j['prefix'].$name.'`'; }
function restore_step(): void {
 $j=job();if(!$j||$j['mode']!=='restore')fail('Keine Wiederherstellung vorbereitet.');$z=archive_open(storage_path($j['archive']),$_SESSION['restore_password']??'');
 if($j['phase']==='verify'){
  if(isset($j['entries'][$j['index']])){$n=$j['entries'][$j['index']];$data=$z->getFromName($n);if($data===false||!hash_equals($j['manifest']['entries'][$n]['sha256'],hash('sha256',$data)))fail('Integritätsprüfung fehlgeschlagen. Bestehende Daten bleiben erhalten.');$j['index']++;}
  else{$j['phase']='create';$j['index']=0;}
 }elseif($j['phase']==='create'){
  foreach(schema() as $name=>$definition)q('CREATE TABLE IF NOT EXISTS '.staging_table($j,$name).' ('.$definition.') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');$j['phase']='import';$j['index']=0;
 }elseif($j['phase']==='import'){
  if(isset($j['entries'][$j['index']])){$n=$j['entries'][$j['index']];$data=$z->getFromName($n);if($data===false||!hash_equals($j['manifest']['entries'][$n]['sha256'],hash('sha256',$data)))fail('Archiv beschädigt.');
   if(str_starts_with($n,'tables/')){$name=$j['manifest']['entries'][$n]['table'];$valid=array_column(q('SHOW COLUMNS FROM '.table($name))->fetchAll(),'Field');foreach(json_decode($data,true,512,JSON_THROW_ON_ERROR) as $row){if(array_diff(array_keys($row),$valid)||count($row)!==count($valid))fail('Ungültige Tabellenspalten.');$keys=array_keys($row);q('REPLACE INTO '.staging_table($j,$name).' (`'.implode('`,`',$keys).'`) VALUES ('.implode(',',array_fill(0,count($keys),'?')).')',array_values($row));}}
   else{$p=storage_path(basename($n));if(is_file($p)&&!hash_equals(hash_file('sha256',$p),hash('sha256',$data)))fail('Dateikonflikt. Wiederherstellung in einer frischen Installation durchführen.');if(!is_file($p)&&file_put_contents($p,$data,LOCK_EX)===false)fail('Datei konnte nicht wiederhergestellt werden.');}$j['index']++;
  }else{$j['phase']='ready';}
 }elseif($j['phase']==='ready'){
  $z->close();fail('Bitte Sicherheitsbackup herunterladen und Wiederherstellung ausdrücklich abschließen.');
 }
 $z->close();save_job($j);
}
function restore_commit(): void {
 $j=job();if(!$j||$j['mode']!=='restore'||$j['phase']!=='ready')fail('Wiederherstellung noch nicht geprüft.');if(post('confirm')!=='DATEN ERSETZEN')fail('Zur Bestätigung DATEN ERSETZEN eingeben.');
 $active=q('SELECT COUNT(*) FROM '.staging_table($j,'users')." WHERE active=1 AND role='admin'")->fetchColumn();if(!$active)fail('Backup enthält keinen aktiven Administrator.');
 // Write intent before the atomic table switch: the journal allows safe cleanup after interruption.
 $j['phase']='switching';save_job($j);$renames=[];foreach(schema() as $name=>$_){$renames[]=table($name).' TO `'.$j['prefix'].'old_'.$name.'`';$renames[]=staging_table($j,$name).' TO '.table($name);}q('RENAME TABLE '.implode(', ',$renames));$j['phase']='switched';save_job($j);finish_restore($j);
}
function finish_restore(array $j): void { set_setting('session_epoch',bin2hex(random_bytes(16))); foreach(schema() as $name=>$_)q('DROP TABLE IF EXISTS `'.$j['prefix'].'old_'.$name.'`');@unlink(storage_path($j['archive']));@unlink(storage_path('restore-state.json'));@unlink(storage_path('maintenance.json'));$_SESSION=[];session_regenerate_id(true);flash('Wiederherstellung abgeschlossen. Mit einem Konto aus dem Backup anmelden.'); }
function cancel_job(): void {
 $j=job();if(!$j)return;if(in_array($j['phase'],['switching','switched'],true)){ $old=q('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?',[$j['prefix'].'old_users'])->fetchColumn();if($old){finish_restore($j);return;}}
 if($j['mode']==='restore')foreach(schema() as $name=>$_)q('DROP TABLE IF EXISTS '.staging_table($j,$name));@unlink(storage_path($j['archive']));@unlink(storage_path('restore-state.json'));@unlink(storage_path('maintenance.json'));flash('Vorgang abgebrochen. Bestehende Datenbank unverändert.');
}
function backup_action(string $action): void { require_admin();switch($action){case 'backup_start':start_backup(post('backup_password'));break;case 'backup_step':backup_step();break;case 'restore_upload':restore_upload();break;case 'restore_step':restore_step();break;case 'restore_commit':restore_commit();break;case 'backup_cancel':cancel_job();break;default:fail('Unbekannter Sicherungsvorgang.');} }
