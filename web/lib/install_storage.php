<?php
declare(strict_types=1);

/** Create a new private directory beside the web root, never inside it. */
function create_private_storage(string $documentRoot): string
{
    $root = realpath($documentRoot);
    if ($root === false || !is_dir($root) || dirname($root) === $root) {
        throw new RuntimeException('Der Hoster stellt keinen geeigneten privaten Speicherbereich bereit. Bitte den Hosting-Support kontaktieren.');
    }
    $parent = realpath(dirname($root));
    if ($parent === false || !is_writable($parent)) {
        throw new RuntimeException('MaStunden kann den privaten Speicher nicht automatisch anlegen. Der Hoster muss PHP Schreibzugriff außerhalb des öffentlichen Webverzeichnisses erlauben.');
    }
    for ($attempt = 0; $attempt < 3; $attempt++) {
        $path = $parent . '/mastunden-private-' . bin2hex(random_bytes(12));
        if (!@mkdir($path, 0700)) {
            if (file_exists($path) || is_link($path)) continue;
            throw new RuntimeException('Der private Speicher konnte nicht erstellt werden. Bitte den Hoster die PHP-Schreibrechte und open_basedir prüfen lassen.');
        }
        $real = realpath($path);
        if ($real === false || $real === $root || str_starts_with($real, $root . '/') || !is_writable($real)) {
            @rmdir($path);
            throw new RuntimeException('Der automatisch angelegte Speicher konnte nicht als privat und beschreibbar geprüft werden.');
        }
        return $real;
    }
    throw new RuntimeException('Der private Speicher konnte nicht erstellt werden. Bitte erneut versuchen.');
}

/** Shared-hosting fallback: accept only a positively tested webserver deny rule. */
function create_verified_web_storage(string $appDirectory): string
{
    if (!extension_loaded('curl') || empty($_SERVER['HTTPS']) || $_SERVER['HTTPS']==='off') {
        throw new RuntimeException('Der geschützte Hosting-Speicher benötigt HTTPS und die PHP-Erweiterung cURL.');
    }
    $host=(string)($_SERVER['HTTP_HOST']??'');
    if (!preg_match('/^[a-zA-Z0-9.-]+(?::[0-9]{1,5})?$/D',$host)) throw new RuntimeException('Ungültige Domain für die Speicherprüfung.');
    $name='mastunden-storage-'.bin2hex(random_bytes(12));
    $path=$appDirectory.'/'.$name;
    if(!mkdir($path,0755)) throw new RuntimeException('Der geschützte Speicher konnte nicht angelegt werden.');
    $base='https://'.$host.rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME'])),'/').'/'.$name.'/';
    $files=[];
    $fetch=static function(string $url): array {
        $ch=curl_init($url);$body='';
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>20,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_PROXY=>'',CURLOPT_HTTPHEADER=>['Cache-Control: no-cache'],CURLOPT_WRITEFUNCTION=>static function($handle,string $chunk)use(&$body):int{if(strlen($body)+strlen($chunk)>65536)return 0;$body.=$chunk;return strlen($chunk);}]);
        $ok=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);curl_close($ch);
        if($ok===false)throw new RuntimeException('Die HTTPS-Prüfung des Speicherschutzes ist fehlgeschlagen.');
        return [$status,$body];
    };
    try {
        $token=bin2hex(random_bytes(24));
        foreach(['txt','pdf','jpg','png','zip','json','log','lock','php'] as $ext){
            $file='probe-'.bin2hex(random_bytes(8)).'.'.$ext;
            $content=$ext==='php'?'<?php echo '.var_export($token,true).';':$token;
            if(file_put_contents($path.'/'.$file,$content)!==strlen($content))throw new RuntimeException('Speicherprobe konnte nicht geschrieben werden.');
            chmod($path.'/'.$file,0644);$files[]=$file;
        }
        // Prove that this URL really reaches our directory before checking denial.
        [$status,$body]=$fetch($base.$files[0]);
        if($status!==200 || !hash_equals($token,$body))throw new RuntimeException('Die Zuordnung des geschützten Speichers zur Domain konnte nicht bestätigt werden.');
        $rule="Require all denied\n";
        if(file_put_contents($path.'/.htaccess',$rule)!==strlen($rule))throw new RuntimeException('Speicherschutz konnte nicht geschrieben werden.');
        chmod($path.'/.htaccess',0644);
        foreach($files as $file){[$status,$body]=$fetch($base.$file);if($status!==403 || str_contains($body,$token))throw new RuntimeException('Der Webserver sperrt den Speicher nicht zuverlässig. Installation abgebrochen.');}
        foreach($files as $file)unlink($path.'/'.$file);
        // Additional filesystem protection; PHP remains the owner.
        if(!chmod($path,0700))throw new RuntimeException('Private Speicherrechte konnten nicht gesetzt werden.');
        $GLOBALS['config']['storage_guard_sha256']=hash('sha256',$rule);
        return $path;
    }catch(Throwable $e){foreach($files as $file)@unlink($path.'/'.$file);@unlink($path.'/.htaccess');@rmdir($path);throw $e;}
}
