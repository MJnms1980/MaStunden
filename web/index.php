<?php
declare(strict_types=1);
// Stable entry point: no application modules are loaded before the update lock.
ini_set('display_errors','0');
if(!is_file(__DIR__.'/config.php')){header('Location: install.php');exit;}
try{
 $config=require __DIR__.'/config.php';
 if(isset($config['storage_guard_sha256'])&&(!is_file($config['storage'].'/.htaccess')||!hash_equals($config['storage_guard_sha256'],hash_file('sha256',$config['storage'].'/.htaccess'))))throw new RuntimeException('Speicherschutz fehlt.');
 require __DIR__.'/update-bootstrap.php';
 mu_boot(__DIR__,$config['storage']);
 require __DIR__.'/app.php';
}catch(Throwable $e){http_response_code(503);header('Retry-After: 15');echo 'MaStunden ist vorübergehend nicht verfügbar. Bitte erneut laden. Bleibt der Hinweis bestehen, Update-Rücknahme und Speicherrechte prüfen.';}
