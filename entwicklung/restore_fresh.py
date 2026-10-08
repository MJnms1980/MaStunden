import pathlib
exec(pathlib.Path('work/test_app.py').read_text().split('A=Client()')[0])
BASE='http://127.0.0.1:8089/legacy/'
A=Client();A.get('install.php');A.post({'host':'mastunden-db','port':3306,'database':'mastunden','username':'mastunden','db_password':'LocalTestApp-2026','prefix':'mg_','company':'Neues Unternehmen','name':'Neuer Admin','email':'fresh@example.test','password':'Fresh-Admin-2026','repeat':'Fresh-Admin-2026'},'install.php');A.ok('fresh subdirectory installation')
assert 'MaStunden ist eingerichtet' in A.body
A.get('index.php?page=backup');A.post({'action':'restore_upload','archive_password':'WRONG-PASSWORD','backup_password':'Safety-Backup-2026','confirm':'WIEDERHERSTELLEN'},'index.php?page=backup',{'archive':('backup.zip','application/zip',pathlib.Path('work/v100-compat.zip').read_bytes())});A.denied('wrong backup password rejected')
A.get('index.php?page=backup');A.post({'action':'restore_upload','archive_password':'Backup-Encryption-2026','backup_password':'Safety-Backup-2026','confirm':'WIEDERHERSTELLEN'},'index.php?page=backup',{'archive':('backup.zip','application/zip',pathlib.Path('work/v100-compat.zip').read_bytes())});A.ok('upload backup from different installation')
for i in range(200):
 a='backup_step' if 'value="backup_step"' in A.body else ('restore_step' if 'value="restore_step"' in A.body else None)
 if not a:break
 A.post({'action':a},'index.php?page=backup');A.ok('fresh restore chunk '+str(i))
assert 'value="restore_commit"' in A.body
A.post({'action':'restore_commit','confirm':'DATEN ERSETZEN'},'index.php?page=backup');A.ok('restore into empty company completed')
A.post({'action':'login','email':'admin@example.test','password':'Admin-Test-Password-2026'});A.ok('restored administrator login');assert 'Beispiel GmbH' in A.body
A.get('index.php?download=1');assert A.raw.startswith(b'%PDF');print('PASS documents restored on separate storage')
A.get('index.php?export=csv&user=2&month=2026-09');assert '480;480' in A.body;print('PASS foreign-prefix restored report')
A.get('index.php?page=team&edit=1');A.post({'action':'user_save','id':1,'name':'Beispiel GmbH','email':'admin@example.test','role':'employee','employment_start':'2026-10-01','employment_end':'','vacation_days':30,'active':'on','reason':'test'},'index.php?page=team');A.denied('last administrator protected')
print('ALL FRESH RESTORE TESTS PASSED')
