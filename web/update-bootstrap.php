<?php
/** Stable updater protocol 1. Replaced only by a documented manual upgrade. */
declare(strict_types=1);
function mu_write(string $path,string $data): void {
 $tmp=$path.'.new';$f=fopen($tmp,'wb');if(!$f)throw new RuntimeException('Datei nicht schreibbar.');
 try{if(fwrite($f,$data)!==strlen($data)||!fflush($f)||!fsync($f))throw new RuntimeException('Datei nicht gesichert.');}finally{fclose($f);}
 if(!rename($tmp,$path))throw new RuntimeException('Datei nicht aktivierbar.');
}
function mu_path(string $name): bool {return (bool)preg_match('~^(app\.php|install\.php|lib/[a-z_]+\.php|assets/[a-zA-Z0-9_.-]+\.(css|js|png|jpg|svg))$~D',$name);}
function mu_lock(int $mode): void {
 $f=$GLOBALS['update_mutex'];$until=microtime(true)+10;
 do{if(flock($f,$mode|LOCK_NB))return;usleep(25000);}while(microtime(true)<$until);
 throw new RuntimeException('Aktualisierung läuft. Bitte in Kürze erneut laden.');
}
function mu_replace(string $root,string $name,string $data): void {
 if(!mu_path($name)||is_link($root.'/'.$name)||!is_dir(dirname($root.'/'.$name))||is_link(dirname($root.'/'.$name)))throw new RuntimeException('Ungültiger Updatepfad.');
 mu_write($root.'/'.$name,$data);if(function_exists('opcache_invalidate'))opcache_invalidate($root.'/'.$name,true);
}
function mu_recover(string $root,string $storage): void {
 $path=$storage.'/update-journal.json';if(!is_file($path))return;
 $j=json_decode(file_get_contents($path),true,32,JSON_THROW_ON_ERROR);
 if(!is_array($j)||!isset($j['files'])||!is_array($j['files']))throw new RuntimeException('Updatejournal ungültig.');
 foreach($j['files'] as $name=>$hash){
  if(!mu_path($name))throw new RuntimeException('Updatepfad ungültig.');
  if($hash===null){if(is_file($root.'/'.$name)&&!unlink($root.'/'.$name))throw new RuntimeException('Rücknahme fehlgeschlagen.');}
  else{$old=$storage.'/update-rollback/'.str_replace('/','_',$name);if(!is_file($old)||!hash_equals($hash,hash_file('sha256',$old)))throw new RuntimeException('Rücknahme-Datei fehlt.');mu_replace($root,$name,file_get_contents($old));}
 }
 mu_write($storage.'/update-result.json',json_encode(['status'=>'rolled_back','time'=>gmdate('c')]));
 if(!unlink($path))throw new RuntimeException('Rücknahme nicht abgeschlossen.');
 @unlink($storage.'/update-state.json');
}
function mu_boot(string $root,string $storage): void {
 $f=fopen($storage.'/update.lock','c');if(!$f)throw new RuntimeException('Updatesperre nicht verfügbar.');$GLOBALS['update_mutex']=$f;mu_lock(LOCK_SH);
 if(is_file($storage.'/update-journal.json')){flock($f,LOCK_UN);mu_lock(LOCK_EX);mu_recover($root,$storage);mu_lock(LOCK_SH);}
}
