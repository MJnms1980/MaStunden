import os,pathlib,subprocess,json,re
os.environ.setdefault('MASTUNDEN_TEST_URL','http://127.0.0.1:8098/')
exec(pathlib.Path(__file__).with_name('integration.py').read_text().split('A=Client()')[0])
PREFIX="$GLOBALS['config']=require '/app/config.php';require '/app/lib/core.php';require '/app/lib/files.php';"
def php(code,crash=False):
 p=subprocess.run(['docker','exec',os.environ.get('MASTUNDEN_TEST_CONTAINER','mastunden-security108'),'php','-r',PREFIX+code],capture_output=True,text=True)
 if crash: assert p.returncode==137,(p.returncode,p.stderr)
 else: assert p.returncode==0,p.stderr
 return p.stdout
A=Client();A.get();A.post({'action':'login','email':'admin@example.test','password':'Admin-Test-Password-2026'});A.ok('security admin login')
E=Client();E.get();E.post({'action':'login','email':'employee2@example.test','password':'Employee-Personal-2026'});E.ok('security employee login')
files=lambda:json.loads(php("echo json_encode(array_map('basename',glob(cfg()['storage'].'/*')));"))
before=set(files());E.get('index.php?page=documents');E.post({'action':'document_upload','title':'x'*191,'category':'General','note':''},'index.php?page=documents',{'file':('test.pdf','application/pdf',b'%PDF-1.4\nsynthetic\n%%EOF')});E.denied('oversized metadata rejected');assert before==set(files());print('PASS failed metadata upload leaves no file')
# A crashed transaction must remove a newly created file but retain an existing deleted-file target.
for committed in [False,True]:
 name=('c' if committed else 'b')*48+'.pdf';new=('e' if committed else 'd')*48+'.pdf'
 code="app_lock();file_put_contents(storage_path('"+name+"'),'synthetic');db()->beginTransaction();file_intent('delete','"+name+"');file_intent('create','"+new+"');file_put_contents(storage_path('"+new+"'),'synthetic');"+("db()->commit();" if committed else "")+"posix_kill(getmypid(),9);"
 php(code,True);php('app_lock();recover_file_journals();')
 current=files();assert (name in current)==(not committed);assert (new in current)==committed
 php("@unlink(storage_path('"+name+"'));@unlink(storage_path('"+new+"'));")
 print('PASS crash recovery '+('after commit' if committed else 'before commit'))
# Response history must contain both values.
for response in ['Erste Antwort','Zweite Antwort']:
 A.get('index.php?page=documents');A.post({'action':'document_review','id':1,'status':'processing','response':response},'index.php?page=documents');A.ok('document response saved')
audit=json.loads(php("echo json_encode(rows('audit',\"entity='documents' AND action='review'\",[],'ORDER BY id DESC LIMIT 1')[0]);"));assert 'Erste Antwort' in audit['before_json'] and 'Zweite Antwort' in audit['after_json'];print('PASS document response history')
# Deleting Anna must preserve Annabelle events and an administrator's actions on another employee.
uid=int(php("echo insert('users',['email'=>'erase108@example.test','name'=>'Anna','password'=>password_hash('Test-Only-Password108',PASSWORD_DEFAULT),'role'=>'employee','employment_start'=>'2026-01-01','created_at'=>now()]);"))
php("insert('audit',['actor_id'=>"+str(uid)+",'action'=>'save','entity'=>'entries','entity_id'=>999999,'before_json'=>null,'after_json'=>json_encode(['user_id'=>3]),'reason'=>'Annabelle bleibt','created_at'=>now()]);file_put_contents(storage_path('backup-'.str_repeat('f',24).'.zip'),'synthetic');")
A.get('index.php?page=team');A.post({'action':'user_delete','id':uid,'confirm':'MITARBEITER LÖSCHEN','retention_checked':'1','admin_password':'Admin-Test-Password-2026','reason':'Synthetic security test'},'index.php?page=team');A.ok('employee deletion')
event=json.loads(php("echo json_encode(one('audit',\"reason='Annabelle bleibt'\"));"));assert event and event['actor_id'] is None;assert 'backup-'+('f'*24)+'.zip' in files();print('PASS unrelated audit and backups retained; actor anonymized')
A.get('index.php?page=backup');A.post({'action':'backup_delete','archive':'backup-'+('f'*24)+'.zip','confirm':'BACKUP LÖSCHEN','admin_password':'Admin-Test-Password-2026','reason':'Synthetic archive removal'},'index.php?page=backup');A.ok('explicit backup deletion');assert 'backup-'+('f'*24)+'.zip' not in files()
# Quota deliberately smaller than existing synthetic document.
php("set_setting('storage_quota_mb','0');")
E.get('index.php?page=documents');E.post({'action':'document_upload','title':'Quota','category':'Test','note':''},'index.php?page=documents',{'file':('quota.pdf','application/pdf',b'%PDF-1.4\n%%EOF')});E.denied('quota enforced');php("set_setting('storage_quota_mb','500');")
print('ALL SECURITY REGRESSIONS PASSED')
