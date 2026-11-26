"""Real TLS certificate validation against the disposable MySQL test container."""
import os,pathlib,subprocess,json
exec(pathlib.Path(__file__).with_name('integration.py').read_text().split('A=Client()')[0])
D=os.environ['MASTUNDEN_TEST_DB_CONTAINER'];C=os.environ['MASTUNDEN_TEST_CONTAINER']
def cmd(args):
 p=subprocess.run(args,capture_output=True);assert p.returncode==0,p.stderr.decode();return p.stdout
def php(code):return cmd(['docker','exec',C,'php','-r',code]).decode()
cmd(['docker','exec',D,'openssl','req','-x509','-newkey','rsa:2048','-nodes','-keyout','/tmp/sql-test.key','-out','/tmp/sql-test.pem','-days','2','-subj','/CN=mastunden-db','-addext','subjectAltName=DNS:mastunden-db'])
cmd(['docker','exec','--user','root',D,'chmod','644','/tmp/sql-test.key','/tmp/sql-test.pem'])
cmd(['docker','exec','-e','MYSQL_PWD=Synthetic-Root-Only-2026',D,'mysql','-uroot','-e',"SET GLOBAL ssl_ca='/tmp/sql-test.pem'; SET GLOBAL ssl_cert='/tmp/sql-test.pem'; SET GLOBAL ssl_key='/tmp/sql-test.key'; ALTER INSTANCE RELOAD TLS;"])
ca=cmd(['docker','exec',D,'cat','/tmp/sql-test.pem'])
A=Client();A.get();A.post({'action':'login','email':'admin@example.test','password':'Admin-Test-Password-2026'});A.ok('SQL synthetic session')
original=php("echo hash_file('sha256','/app/config.php');")
data={'action':'sql_test','sql_host':'mastunden-db','sql_port':'3306','sql_user':'mastunden','sql_password':'','admin_password':'Admin-Test-Password-2026'}
A.get('index.php?page=sql');A.post(data.copy(),'index.php?page=sql',{'sql_ca':('ca.pem','application/x-pem-file',ca)});A.ok('SQL verified TLS dry run');assert 'erfolgreich geprüft' in A.body
assert php("echo hash_file('sha256','/app/config.php');")==original
ip=php("echo gethostbyname('mastunden-db');")
A.post(data|{'sql_host':ip,'save_sql':'1'},'index.php?page=sql',{'sql_ca':('ca.pem','application/x-pem-file',ca)});A.denied('SQL hostname mismatch rejected')
assert php("echo hash_file('sha256','/app/config.php');")==original
A.get('index.php?page=sql');A.post(data|{'save_sql':'1'},'index.php?page=sql',{'sql_ca':('ca.pem','application/x-pem-file',ca)});A.ok('SQL verified connection saved and reopened')
assert 'TLS, Zertifikat geprüft' in A.body
assert php("$c=require '/app/config.php';echo $c['db_tls'];")=='verify'
assert php("$c=require '/app/config.php';echo hash_file('sha256',$c['storage'].'/sql-config-previous.php');")==original
print('PASS TLS success, hostname verification, dry-run preservation and protected configuration rollback copy')
