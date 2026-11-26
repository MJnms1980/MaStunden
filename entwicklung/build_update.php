<?php
/** Release maintainer only. Never upload this tool or the private key to web hosting. */
declare(strict_types=1);
if(PHP_SAPI!=='cli'||$argc!==4){fwrite(STDERR,"Usage: php build_update.php WEB_DIRECTORY PRIVATE_SIGNING_KEY OUTPUT_ZIP\n");exit(1);}
$root=realpath($argv[1]);if(!$root)throw new RuntimeException('Web directory missing');
require $root.'/lib/schema.php';require $root.'/update-bootstrap.php';
$secret=file_get_contents($argv[2]);if(strlen($secret)!==SODIUM_CRYPTO_SIGN_SECRETKEYBYTES||!hash_equals(base64_encode(sodium_crypto_sign_publickey_from_secretkey($secret)),trim(file_get_contents($root.'/update-public.key'))))throw new RuntimeException('Signing key does not match distribution');
$z=new ZipArchive();if($z->open($argv[3],ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true)throw new RuntimeException('Cannot create package');
$m=['app'=>'MaStunden','version'=>APP_VERSION,'minimum_from'=>'1.0.9','schema'=>1,'protocol'=>1,'php_min'=>80300,'files'=>[],'bootstrap'=>[]];
foreach(['index.php','update-bootstrap.php','update-public.key'] as $n)$m['bootstrap'][$n]=hash_file('sha256',$root.'/'.$n);
$paths=array_merge(glob($root.'/lib/*.php'),glob($root.'/assets/*'),[$root.'/app.php',$root.'/install.php']);sort($paths);
foreach($paths as $p){$n=substr($p,strlen($root)+1);if(!mu_path($n)||is_link($p))throw new RuntimeException('Unexpected file');$data=file_get_contents($p);$m['files'][$n]=hash('sha256',$data);$z->addFromString('web/'.$n,$data);}
$raw=json_encode($m,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);$z->addFromString('manifest.json',$raw);$z->addFromString('manifest.sig',sodium_crypto_sign_detached($raw,$secret));sodium_memzero($secret);if(!$z->close())throw new RuntimeException('Cannot finish package');echo 'Signed update '.APP_VERSION." created\n";
