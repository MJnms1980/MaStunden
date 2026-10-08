import os,pathlib,re,secrets
os.environ['MASTUNDEN_TEST_URL']='http://127.0.0.1:8094/'
exec(pathlib.Path('work/test_app.py').read_text().split('A=Client()')[0])
a=Client();a.get();a.post({'action':'login','email':'admin@example.test','password':'Admin-Test-Password-2026'});a.ok('admin login')
email='erase-'+secrets.token_hex(5)+'@example.test'
a.get('index.php?page=team');a.post({'action':'user_save','name':'Deletion Test','email':email,'role':'employee','employment_start':'2026-01-01','password':'Deletion-Initial-2026','active':'on'},'index.php?page=team');a.ok('new fixture')
uid=re.search(re.escape(email)+r'.*?edit=(\d+)',a.body,re.S)[1]
e=Client();e.get();e.post({'action':'login','email':email,'password':'Deletion-Initial-2026'});e.post({'action':'password','current':'Deletion-Initial-2026','new':'Deletion-Personal-2026','repeat':'Deletion-Personal-2026'},'index.php?page=account');e.ok('employee fixture login')
e.get('index.php?page=absences');e.post({'action':'absence_save','start_date':'2026-10-15','end_date':'2026-10-15','type':'vacation','fraction':'1'},'index.php?page=absences&month=2026-10');e.ok('request')
aid=re.search(r'name="action" value="absence_cancel".*?name="id" value="(\d+)"',e.body,re.S)[1]
a.get('index.php?page=approvals');a.post({'action':'absence_review','id':aid,'status':'rejected','reason':'Ablehnung Test 106'},'index.php?page=approvals');a.ok('reject')
e.get('index.php?page=absences&month=2026-10');assert 'Zurückziehen / stornieren' in e.body
e.post({'action':'absence_cancel','id':aid,'reason':('Rücknahme Test 106 Konto '+uid)},'index.php?page=absences&month=2026-10');e.ok('employee withdraws rejected request');assert 'Zurückgezogen / storniert' in e.body
a.get('index.php?page=audit');assert ('Rücknahme Test 106 Konto '+uid) in a.body and '<strong>Begründung:</strong>' in a.body;print('PASS visible audit reason')
a.get('index.php?page=approvals');a.post({'action':'absence_review','id':aid,'status':'approved'},'index.php?page=approvals');a.denied('cannot approve withdrawn request')
e.get('index.php?page=documents');e.post({'action':'document_upload','title':'Erase fixture','category':'Test','absence_id':'0'},'index.php?page=documents',{'file':('test.pdf','application/pdf',b'%PDF-1.4\nSynthetic\n%%EOF')});e.ok('fixture upload')
doc=re.search(r'download=(\d+)',e.body)[1]
base={'action':'user_delete','id':uid,'confirm':'MITARBEITER LÖSCHEN','retention_checked':'1','admin_password':'Admin-Test-Password-2026','reason':'Löschtest 106 abgeschlossen'}
e.get();e.post(dict(base),'index.php?page=team');e.denied('employee cannot delete accounts')
a.get('index.php?page=team');a.post(base|{'id':'1'},'index.php?page=team');a.denied('self deletion prevented')
a.get('index.php?page=team');a.post(base|{'confirm':''},'index.php?page=team');a.denied('confirmation required')
a.get('index.php?page=team');a.post(base|{'admin_password':'wrong'},'index.php?page=team');a.denied('admin password required')
a.get('index.php?page=team');a.post(dict(base),'index.php?page=team');a.ok('employee erased');assert email not in a.body
a.get('index.php?download='+doc);a.denied('document no longer available')
e.get();assert 'Anmelden' in e.body;print('PASS existing employee session invalidated')
a.get('index.php?page=audit');assert 'Löschtest 106 abgeschlossen' in a.body and ('Rücknahme Test 106 Konto '+uid) not in a.body;print('PASS related audit removed and neutral deletion receipt retained')
pathlib.Path('work/test106/uid.txt').write_text(uid)
