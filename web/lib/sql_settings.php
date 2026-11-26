<?php
declare(strict_types=1);
function sql_settings_action(): void {
 update_admin();no_maintenance();$c=cfg();$host=post('sql_host');$port=filter_var(post('sql_port'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>65535]]);
 if(!preg_match('/^[a-zA-Z0-9.:-]{1,253}$/D',$host)||!$port)fail('SQL-Servername und Port prüfen.');
 $c['host']=$host;$c['port']=$port;$c['username']=bounded(post('sql_user'),128);if($c['username']==='')fail('SQL-Benutzer fehlt.');
 if((string)($_POST['sql_password']??'')!=='')$c['password']=(string)$_POST['sql_password'];
 $c['db_tls']=in_array(strtolower($host),['localhost','127.0.0.1','::1'],true)?'local':'verify';
 $ca=null;$f=$_FILES['sql_ca']??null;
 if($f&&$f['error']!==UPLOAD_ERR_NO_FILE){if($f['error']!==UPLOAD_ERR_OK||$f['size']>262144)fail('CA-Datei ungültig oder größer als 256 KB.');$pem=file_get_contents($f['tmp_name']);if(!str_contains($pem,'-----BEGIN CERTIFICATE-----')||str_contains($pem,'PRIVATE KEY')||!openssl_x509_read($pem))fail('Bitte ausschließlich das CA-Zertifikat des Anbieters im PEM-Format verwenden.');$ca=storage_path('sql-ca-'.bin2hex(random_bytes(16)).'.pem');mu_write($ca,$pem);$c['db_ssl_ca']=$ca;}
 $transport=$GLOBALS['db_transport']??'';
 try{
  try{$candidate=database_connect($c);$oldIdentity=db()->query('SELECT @@hostname, @@port, DATABASE()')->fetch(PDO::FETCH_NUM);$newIdentity=$candidate->query('SELECT @@hostname, @@port, DATABASE()')->fetch(PDO::FETCH_NUM);if($oldIdentity!==$newIdentity)fail('Die neue Verbindung führt nicht zur bisherigen Datenbankinstanz.');$s=$candidate->prepare('SELECT v FROM '.table('settings').' WHERE k=?');$s->execute(['session_epoch']);if(!hash_equals(setting('session_epoch'),(string)$s->fetchColumn()))fail('Die neue Verbindung gehört nicht zu dieser Installation.');}
  catch(PDOException $e){fail('SQL-Verbindung nicht bestätigt. Servername, Port, Zugangsdaten und CA-Zertifikat beim Anbieter prüfen. Die bisherige Verbindung bleibt erhalten.');}
  if(!isset($_POST['save_sql'])){flash('Verbindung erfolgreich geprüft: '.($GLOBALS['db_transport']??'').' · dieselbe Datenbankinstanz. Es wurde nichts umgestellt.');return;}
  $root=dirname(__DIR__);if(!is_writable($root)||is_link($root.'/config.php'))fail('Konfiguration kann nicht sicher ersetzt werden. Die bisherigen Einstellungen bleiben erhalten.');
  $old=file_get_contents($root.'/config.php');$backup=storage_path('sql-config-previous.php');mu_write($backup,$old);chmod($backup,0600);
  $tmp=$root.'/config-new.php';$f=fopen($tmp,'wb');if(!$f)fail('Neue Konfiguration kann nicht erstellt werden.');
  try{if(!chmod($tmp,0600))fail('Konfiguration kann nicht geschützt werden.');$data="<?php\nreturn ".var_export($c,true).";\n";if(fwrite($f,$data)!==strlen($data)||!fflush($f)||!fsync($f))fail('Konfiguration konnte nicht gesichert werden.');}finally{fclose($f);}
  if(!rename($tmp,$root.'/config.php'))fail('Konfiguration konnte nicht aktiviert werden.');if(function_exists('opcache_invalidate'))opcache_invalidate($root.'/config.php',true);
  $ca=null;audit('change','sql_connection',null,null,['transport'=>$c['db_tls']],'Verbindung und Datenbankidentität vor Umschaltung geprüft.');flash('Geprüfte SQL-Verbindung gespeichert. Sie wird beim nächsten Seitenaufruf verwendet.');
 }finally{if($ca!==null)@unlink($ca);$GLOBALS['db_transport']=$transport;}
}
function view_sql(): void {
 require_admin();$c=cfg();echo '<section class="card"><h2>SQL-Verbindung</h2><p>Aktuell: <strong>'.h($GLOBALS['db_transport']??'').'</strong></p><p>Externe Verbindungen verwenden TLS mit Zertifikatsprüfung. Der Servername muss zum Zertifikat passen. Ein Wechsel der Datenbank selbst ist hier nicht vorgesehen.</p>';form_start('sql_test',[],true);echo '<div class="form-grid">';field('SQL-Servername','sql_host',$c['host'],'text','required');field('Port','sql_port',$c['port'],'number','min="1" max="65535" required');field('SQL-Benutzer','sql_user',$c['username'],'text','required autocomplete="off"');field('Neues SQL-Passwort (leer = unverändert)','sql_password','','password','autocomplete="new-password"');field('CA-Zertifikat des Anbieters (optional, PEM)','sql_ca','','file','accept=".pem,.crt"');field('Administratorpasswort','admin_password','','password','required autocomplete="current-password"');echo '</div><p class="muted">Ohne neue CA-Datei wird die vorhandene CA bzw. der System-Zertifikatsspeicher verwendet. Zugangsdaten werden weder angezeigt noch ins Änderungsprotokoll geschrieben. Die vorherige Konfiguration liegt für eine Rücknahme geschützt als sql-config-previous.php im privaten Speicher.</p><div class="button-row"><button>Nur Verbindung prüfen</button><button name="save_sql" value="1">Prüfen und speichern</button></div></form></section>';
}
