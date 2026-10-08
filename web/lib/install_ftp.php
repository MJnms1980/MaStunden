<?php
declare(strict_types=1);

/** Temporary installation connection. Credentials are never persisted. */
final class InstallerFtp
{
    private string $base;
    private string $remoteApp;
    private string $remoteParent;
    private string $localParent;
    private string $storageName = '';
    public function __construct(private string $app, string $documentRoot, private array $options)
    {
        if (!extension_loaded('curl')) throw new RuntimeException('Für die FTP-Einrichtung muss der Hoster die PHP-Erweiterung cURL aktivieren.');
        $host = $options['host'];
        if (!preg_match('/^[a-zA-Z0-9.-]+$/D', $host) || $options['port'] < 1 || $options['port'] > 65535 || !in_array($options['protocol'], ['ftp','ftps'], true)) throw new RuntimeException('FTP-Server, Port oder Verbindungstyp ungültig.');
        $this->base = 'ftp://' . $host . ':' . $options['port'];
        $path = rtrim($options['path'], '/');
        if ($path === '' || $path[0] !== '/' || preg_match('/[\\\\\r\n\x00]/', $path) || preg_match('~/(?:\.|\.\.)(?:/|$)~', $path) || str_contains($path, '//')) throw new RuntimeException('Bitte einen absoluten FTP-Ordner ohne Punktsegmente angeben, zum Beispiel /public_html/mastunden.');
        $root = realpath($documentRoot); $localApp = realpath($app);
        if (!$root || !$localApp || ($localApp !== $root && !str_starts_with($localApp, $root.'/'))) throw new RuntimeException('Das öffentliche Webverzeichnis konnte nicht zugeordnet werden.');
        $suffix = substr($localApp, strlen($root));
        if ($suffix !== '' && !str_ends_with($path, $suffix)) throw new RuntimeException('Der FTP-Ordner passt nicht zum Unterordner dieser Installation.');
        $remoteRoot = $suffix === '' ? $path : substr($path, 0, -strlen($suffix));
        if ($remoteRoot === '' || $remoteRoot === '/') throw new RuntimeException('Dieser FTP-Zugang endet im öffentlichen Webverzeichnis. Für privaten Speicher wird Zugriff auf dessen übergeordneten Ordner benötigt.');
        $this->remoteApp = $path;
        $this->remoteParent = rtrim(dirname($remoteRoot), '/');
        $this->localParent = dirname($root);
        $name = '/install-probe-'.bin2hex(random_bytes(12)).'.php';
        $body = '<?php exit; // '.bin2hex(random_bytes(24));
        try {
            $this->upload($path.$name, $body);
            if (@file_get_contents($app.$name) !== $body) throw new RuntimeException('Der FTP-Ordner gehört nicht zu dieser Installation oder PHP kann die Dateien nicht lesen.');
        } finally { $this->quiet('DELE '.$path.$name); }
    }
    private function request(string $path, ?string $body = null, array $commands = []): void
    {
        $url = $this->base.'/%2F'.implode('/', array_map('rawurlencode', explode('/', ltrim($path, '/'))));
        $ch = curl_init($url); $stream = null;
        $settings = [CURLOPT_USERNAME=>$this->options['username'], CURLOPT_PASSWORD=>$this->options['password'], CURLOPT_RETURNTRANSFER=>true, CURLOPT_CONNECTTIMEOUT=>10, CURLOPT_TIMEOUT=>30, CURLOPT_PROTOCOLS=>CURLPROTO_FTP, CURLOPT_PROXY=>'', CURLOPT_FTP_SKIP_PASV_IP=>true, CURLOPT_SSL_VERIFYPEER=>true, CURLOPT_SSL_VERIFYHOST=>2, CURLOPT_USE_SSL=>$this->options['protocol']==='ftps'?CURLUSESSL_ALL:CURLUSESSL_NONE];
        if ($body !== null) { $stream=fopen('php://temp','w+'); fwrite($stream,$body); rewind($stream); $settings[CURLOPT_UPLOAD]=true; $settings[CURLOPT_INFILE]=$stream; $settings[CURLOPT_INFILESIZE]=strlen($body); }
        else $settings[CURLOPT_NOBODY]=true;
        if ($commands) $settings[CURLOPT_QUOTE]=$commands;
        curl_setopt_array($ch,$settings); $ok=curl_exec($ch); $code=curl_errno($ch); curl_close($ch); if($stream)fclose($stream);
        if($ok===false) throw new RuntimeException('FTP-Einrichtung fehlgeschlagen (Fehler '.$code.'). Zugangsdaten, FTP-Ordner, Rechte und bei FTPS das Serverzertifikat prüfen.');
    }
    private function upload(string $path,string $body): void { $this->request($path,$body); }
    private function command(string $command): void { $this->request('/',null,[$command]); }
    private function quiet(string $command): void { try{$this->command($command);}catch(Throwable $ignored){} }
    public function createStorage(): string
    {
        $this->storageName='mastunden-private-'.bin2hex(random_bytes(12));
        $remote=$this->remoteParent.'/'.$this->storageName;
        $this->command('MKD '.$remote);
        try{$this->command('SITE CHMOD 0770 '.$remote);}catch(Throwable $e){$this->cleanup();throw $e;}
        $local=$this->localParent.'/'.$this->storageName; clearstatcache(); $real=realpath($local);
        $probe=$local.'/write-test-'.bin2hex(random_bytes(8));
        if(!$real || $real!==$local || ((fileperms($real)&0007)!==0) || @file_put_contents($probe,'test')!==4 || @file_get_contents($probe)!=='test' || !@unlink($probe)) { @unlink($probe); $this->cleanup(); throw new RuntimeException('Der FTP-Ordner wurde angelegt, ist für PHP aber nicht nutzbar. Der Hoster muss gemeinsame Schreibrechte für PHP und FTP sowie Zugriff außerhalb des Webverzeichnisses erlauben.'); }
        return $real;
    }
    public function writeConfig(string $body): void
    {
        $name='/config-install-'.bin2hex(random_bytes(12)).'.php';
        try {
            $this->upload($this->remoteApp.$name,$body);
            $this->command('SITE CHMOD 0640 '.$this->remoteApp.$name);
            clearstatcache();
            if(@file_get_contents($this->app.$name)!==$body || ((fileperms($this->app.$name)&0007)!==0)) throw new RuntimeException('PHP kann die per FTP geschriebene Konfiguration nicht lesen. Bitte gemeinsame Leserechte beim Hoster einrichten lassen.');
            if(is_file($this->app.'/config.php')) throw new RuntimeException('Installation bereits abgeschlossen.');
            $this->request('/',null,['RNFR '.$this->remoteApp.$name,'RNTO '.$this->remoteApp.'/config.php']);
        } finally { $this->quiet('DELE '.$this->remoteApp.$name); }
    }
    public function cleanup(): void { if($this->storageName!=='')$this->quiet('RMD '.$this->remoteParent.'/'.$this->storageName); }
}
