<?php
declare(strict_types=1);
function database_connect(array $c): PDO {
 $local=in_array(strtolower($c['host']),['localhost','127.0.0.1','::1'],true);
 $mode=$c['db_tls']??($local?'local':'verify');
 $options=[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>10];
 if($mode==='verify'){
  $ca=$c['db_ssl_ca']??'';
  if($ca==='')foreach(['/etc/ssl/certs/ca-certificates.crt','/etc/pki/tls/certs/ca-bundle.crt','/etc/ssl/cert.pem'] as $candidate)if(@is_readable($candidate)){$ca=$candidate;break;}
  if($ca===''||!is_readable($ca))fail('Für die SQL-TLS-Verbindung fehlt eine lesbare CA-Datei. db_ssl_ca in config.php eintragen.');
  $options[PDO::MYSQL_ATTR_SSL_CA]=$ca;$options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT]=true;
 }elseif($mode==='private-network'){
  // Explicit compatibility setting for an existing isolated hosting network only.
  $ip=filter_var($c['host'],FILTER_VALIDATE_IP,FILTER_FLAG_IPV4);$n=$ip?ip2long($ip):0;
  if(!$ip||!(($n&0xff000000)===0x0a000000||($n&0xfff00000)===0xac100000||($n&0xffff0000)===0xc0a80000))fail('Die Ausnahme ist nur für feste private IPv4-Adressen zulässig.');
 }elseif(!($mode==='local'&&$local)&&!(getenv('MASTUNDEN_LOCAL_TEST')==='1'&&$mode==='test'))fail('Externe SQL-Verbindungen erfordern geprüftes TLS.');
 $pdo=new PDO('mysql:host='.$c['host'].';port='.$c['port'].';dbname='.$c['database'].';charset=utf8mb4',$c['username'],$c['password'],$options);
 $cipher=$pdo->query("SHOW SESSION STATUS LIKE 'Ssl_cipher'")->fetch();
 if($mode==='verify'&&empty($cipher['Value']))fail('Der SQL-Server hat keine TLS-Verbindung aufgebaut.');
 $GLOBALS['db_transport']=$mode==='verify'?'TLS, Zertifikat geprüft':($local?'Lokale Verbindung':($mode==='private-network'?'WARNUNG: internes Hostingnetz ohne TLS; TLS beim Hoster erforderlich':'Isolierter Testbetrieb'));
 return $pdo;
}
