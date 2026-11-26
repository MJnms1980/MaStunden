"""Synthetic update signing, authorization, backup and interrupted-swap regression tests."""
import os,pathlib,subprocess,json,base64,re
exec(pathlib.Path(__file__).with_name('integration.py').read_text().split('A=Client()')[0])
C=os.environ['MASTUNDEN_TEST_CONTAINER']
def php(code):
 p=subprocess.run(['docker','exec',C,'php','-r',code],capture_output=True,text=True);assert p.returncode==0,p.stderr;return p.stdout
pre="$config=require '/app/config.php';require '/app/update-bootstrap.php';require '/app/lib/core.php';require '/app/lib/files.php';require '/app/lib/backup.php';require '/app/lib/updater.php';"
originalkey=php("echo file_get_contents('/app/update-public.key');")
php("$k=sodium_crypto_sign_keypair();file_put_contents('/tmp/update-test.key',sodium_crypto_sign_secretkey($k));file_put_contents('/app/update-public.key',base64_encode(sodium_crypto_sign_publickey($k))); ")
def package(kind='valid'):
 code=r'''
 $files=[];$z=new ZipArchive();$z->open('/tmp/test-update.zip',ZipArchive::CREATE|ZipArchive::OVERWRITE);
 foreach(array_merge(glob('/app/lib/*.php'),glob('/app/assets/*'),['/app/app.php','/app/install.php']) as $p){$n=substr($p,5);$d=file_get_contents($p);if($n==='lib/schema.php')$d=str_replace("'1.0.9'","'1.0.10'",$d);$files[$n]=hash('sha256',$d);$z->addFromString('web/'.$n,$d);}
 $m=['app'=>'MaStunden','protocol'=>1,'schema'=>1,'version'=>'1.0.10','minimum_from'=>'1.0.9','php_min'=>80300,'files'=>$files,'bootstrap'=>[]];
 foreach(['index.php','update-bootstrap.php','update-public.key'] as $n)$m['bootstrap'][$n]=hash_file('sha256','/app/'.$n);
 if(KIND==='traversal'){$m['files']['../config.php']=hash('sha256','evil');$z->addFromString('web/../config.php','evil');}
 if(KIND==='config'){$m['files']['config.php']=hash('sha256','evil');$z->addFromString('web/config.php','evil');}
 if(KIND==='downgrade')$m['version']='1.0.8';
 if(KIND==='schema')$m['schema']=2;
 $raw=json_encode($m);$sig=sodium_crypto_sign_detached($raw,file_get_contents('/tmp/update-test.key'));
 if(KIND==='tampered')$raw.=' ';
 $z->addFromString('manifest.json',$raw);$z->addFromString('manifest.sig',$sig);$z->close();echo base64_encode(file_get_contents('/tmp/test-update.zip'));
 '''.replace('KIND',json.dumps(kind))
 return base64.b64decode(php(code))
A=Client();A.get();A.post({'action':'login','email':'admin@example.test','password':'Admin-Test-Password-2026'});A.ok('update synthetic admin session')
E=Client();E.get();E.post({'action':'login','email':'employee2@example.test','password':'Employee-Personal-2026'});E.ok('update synthetic employee session')
config_hash=php("echo hash_file('sha256','/app/config.php');")
try:
 for page in ['update','sql']:
  E.get('index.php?page='+page);E.denied('employee denied '+page)
 A.get('index.php?page=update');A.post({'action':'update_prepare','csrf':'invalid','admin_password':'Admin-Test-Password-2026'},'index.php?page=update');A.denied('update CSRF rejected')
 for kind in ['tampered','traversal','config','downgrade','schema']:
  A.get('index.php?page=update');A.post({'action':'update_prepare','admin_password':'Admin-Test-Password-2026','backup_password':'Update-Backup-Test-2026'},'index.php?page=update',{'package':('update.zip','application/zip',package(kind))});A.denied('update rejects '+kind)
  assert php(pre+"echo is_file(storage_path('update-state.json'))?'bad':'clean';")=='clean'
 # A failed TLS check must not alter the working configuration.
 A.get('index.php?page=sql');A.post({'action':'sql_test','sql_host':'mastunden-db','sql_port':'3306','sql_user':'mastunden','sql_password':'WRONG','admin_password':'Admin-Test-Password-2026','save_sql':'1'},'index.php?page=sql');A.denied('failed SQL verification preserves configuration')
 assert php("echo hash_file('sha256','/app/config.php');")==config_hash
 A.get('index.php?page=update');A.post({'action':'update_prepare','admin_password':'Admin-Test-Password-2026','backup_password':'Update-Backup-Test-2026'},'index.php?page=update',{'package':('update.zip','application/zip',package())});A.ok('signed update starts encrypted safety backup')
 E.get();E.post({'action':'clock_start'});E.denied('employee writes frozen during update preparation')
 for _ in range(300):
  if not php(pre+"echo job()?'running':'';"):break
  A.get('index.php?page=backup');A.post({'action':'backup_step'},'index.php?page=backup');A.ok('update backup step')
 else:raise AssertionError('backup never completed')
 A.get('index.php?page=update');A.post({'action':'update_apply','admin_password':'wrong','confirm':'UPDATE INSTALLIEREN'},'index.php?page=update');A.denied('update requires administrator password again')
 A.get('index.php?page=update');A.post({'action':'update_apply','admin_password':'Admin-Test-Password-2026','confirm':'UPDATE INSTALLIEREN'},'index.php?page=update');A.ok('signed update installed')
 assert 'MaStunden 1.0.10' in A.body
 assert php("echo hash_file('sha256','/app/config.php');")==config_hash
 assert php(pre+"echo (int)is_file(storage_path(json_decode(file_get_contents(storage_path('update-result.json')),true)['backup']));")=='1'
 assert php(pre+"echo (int)is_file(storage_path('update-journal.json'));")=='0'
 print('PASS update preserves configuration and encrypted backup')
 # Kill after a durable journal and one replaced file. The real next HTTP request recovers.
 code=pre+r'''mu_boot('/app',cfg()['storage']);flock($GLOBALS['update_mutex'],LOCK_UN);mu_lock(LOCK_EX);$n='lib/schema.php';$old=file_get_contents('/app/'.$n);mu_write(storage_path('update-rollback').'/lib_schema.php',$old);mu_write(storage_path('update-journal.json'),json_encode(['files'=>[$n=>hash('sha256',$old)]]));mu_replace('/app',$n,'<?php this is deliberately broken syntax');posix_kill(getmypid(),9);'''
 p=subprocess.run(['docker','exec',C,'php','-r',code],capture_output=True);assert p.returncode==137,p.stderr
 A.get('index.php?page=backup');A.ok('interrupted update recovered before PHP modules load');assert 'zurückgesetzt' in A.body and 'MaStunden 1.0.10' in A.body
 print('PASS crash recovery restored executable program')
finally:
 php("file_put_contents('/app/update-public.key',"+json.dumps(originalkey)+");")
