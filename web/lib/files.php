<?php
declare(strict_types=1);
/** Filesystem intent is durable before any file is created. The commit marker
 * lives in the same InnoDB transaction as the application records. */
function file_intent(string $kind,string $name): void {
 if(!db()->inTransaction()||!in_array($kind,['create','delete'],true))fail('Dateioperation ohne Transaktion.');
 storage_path($name);
 $j=$GLOBALS['file_journal']??['id'=>bin2hex(random_bytes(16)),'create'=>[],'delete'=>[]];
 $j[$kind][]=$name;$j[$kind]=array_values(array_unique($j[$kind]));
 $path=storage_path('files-'.$j['id'].'.json');$tmp=$path.'.new';
 $s=json_encode($j,JSON_THROW_ON_ERROR);$f=fopen($tmp,'wb');if(!$f)fail('Dateijournal konnte nicht erstellt werden.');
 try{if(fwrite($f,$s)!==strlen($s)||!fflush($f)||!fsync($f))fail('Dateijournal konnte nicht gesichert werden.');}finally{fclose($f);}
 if(!rename($tmp,$path))fail('Dateijournal konnte nicht aktiviert werden.');
 $GLOBALS['file_journal']=$j;set_setting('file_commit_'.$j['id'],'1');
}
function recover_file_journals(): void {
 foreach(glob(cfg()['storage'].'/files-*.json')?:[] as $path){
  $j=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);
  if(!is_array($j)||!preg_match('/^[a-f0-9]{32}$/D',$j['id']??'')||basename($path)!=='files-'.$j['id'].'.json')fail('Dateijournal ungültig; Administrator kontaktieren.');
  $key='file_commit_'.$j['id'];$committed=(bool)one('settings','k=?',[$key]);
  foreach($j[$committed?'delete':'create'] as $name){$p=storage_path($name);if(is_file($p)&&!unlink($p))fail('Dateibereinigung nicht abgeschlossen. Speicherrechte prüfen.');}
  if(!unlink($path))fail('Dateijournal konnte nicht abgeschlossen werden.');
  q('DELETE FROM '.table('settings').' WHERE k=?',[$key]);
 }
 unset($GLOBALS['file_journal'],$GLOBALS['settings_cache']);
}
function app_lock(bool $exclusive=true): void {
 $mutex=$GLOBALS['app_mutex']??=fopen(storage_path('app.lock'),'c');if(!$mutex)fail('Anwendungssperre konnte nicht geöffnet werden.');
 $deadline=microtime(true)+5;
 do{if(flock($mutex,($exclusive?LOCK_EX:LOCK_SH)|LOCK_NB))return;usleep(25000);}while(microtime(true)<$deadline);
 fail('Die Anwendung ist kurzzeitig ausgelastet. Bitte erneut versuchen.');
}
function app_unlock(): void {if(isset($GLOBALS['app_mutex']))flock($GLOBALS['app_mutex'],LOCK_UN);}
function stream_private_file(string $path): never {
 $stream=fopen($path,'rb');if(!$stream)fail('Datei kann nicht gelesen werden.');
 $stat=fstat($stream);header('Content-Length: '.$stat['size']);session_write_close();app_unlock();fpassthru($stream);fclose($stream);exit;
}
function verify_storage_guard(bool $force=false): void {
 if(!isset(cfg()['storage_guard_sha256']))return;
 $stamp=storage_path('storage-verified.json');$state=is_file($stamp)?json_decode(file_get_contents($stamp),true):null;
 if(!$force&&is_array($state)&&time()-(int)($state['time']??0)<3600&&hash_equals(cfg()['storage_guard_sha256'],(string)($state['hash']??'')))return;
 $base=cfg()['storage_public_url']??'';
 if(!preg_match('~^https://[^/?#]+/[^?#]*$~D',$base)||!extension_loaded('curl'))fail('Speicherschutzprüfung benötigt die feste HTTPS-Speicheradresse und cURL. Administrator kontaktieren.');
 $name='check-'.bin2hex(random_bytes(16)).'.txt';$path=storage_path($name);$token=bin2hex(random_bytes(32));
 if(file_put_contents($path,$token,LOCK_EX)!==strlen($token))fail('Speicherprüfung konnte nicht vorbereitet werden.');
 try{
  $ch=curl_init(rtrim($base,'/').'/'.$name);$body='';curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>10,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_PROXY=>'',CURLOPT_WRITEFUNCTION=>static function($ch,$chunk)use(&$body){if(strlen($body)+strlen($chunk)>65536)return 0;$body.=$chunk;return strlen($chunk);}]);
  $ok=curl_exec($ch);$status=curl_getinfo($ch,CURLINFO_RESPONSE_CODE);curl_close($ch);
  if($ok===false||$status!==403||str_contains($body,$token))fail('Der Webserver bestätigt den privaten Speicherschutz nicht. Zugriff vorsorglich gesperrt; Administrator kontaktieren.');
  if(file_put_contents($stamp,json_encode(['time'=>time(),'hash'=>cfg()['storage_guard_sha256']]),LOCK_EX)===false)fail('Speicherprüfung konnte nicht protokolliert werden.');
 }finally{@unlink($path);}
}
function orphan_files(): array {
 $used=array_fill_keys(array_column(rows('documents'),'storage_name'),true);
 foreach(['company_logo','developer_logo'] as $key){$name=setting($key);if($name!=='')$used[$name]=true;}
 $files=[];foreach(glob(cfg()['storage'].'/*')?:[] as $path){$name=basename($path);if(preg_match('/^[a-f0-9]{48}\.(pdf|jpg|png)$/D',$name)&&is_file($path)&&filemtime($path)<time()-86400&&!isset($used[$name]))$files[]=$name;}
 return $files;
}

function request_lock(): void {
 $write=($_SERVER['REQUEST_METHOD']??'GET')==='POST';app_lock($write);
 $check=false;if(isset(cfg()['storage_guard_sha256'])){$path=storage_path('storage-verified.json');$state=is_file($path)?json_decode(file_get_contents($path),true):null;$check=!is_array($state)||time()-(int)($state['time']??0)>=3600||!hash_equals(cfg()['storage_guard_sha256'],(string)($state['hash']??''));}
 if($write||$check||(glob(cfg()['storage'].'/files-*.json')?:[])){
  if(!$write){app_unlock();app_lock();}
  verify_storage_guard();recover_file_journals();if(!$write)app_lock(false);
 }
}
