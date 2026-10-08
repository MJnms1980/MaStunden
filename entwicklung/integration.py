import urllib.request, urllib.parse, urllib.error, http.cookiejar, re, json, pathlib, datetime, sys
import os
BASE=os.environ.get('MASTUNDEN_TEST_URL','http://127.0.0.1:8087/')
class Client:
 def __init__(self):self.opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()));self.body='';self.status=0
 def get(self,path='index.php'):
  try:r=self.opener.open(BASE+path)
  except urllib.error.HTTPError as e:r=e
  b=r.read();self.raw=b;self.body=b.decode('utf-8',errors='replace');self.status=r.code;return self.body
 def post(self,data,path='index.php',files=None):
  if 'csrf' not in data:data['csrf']=re.search(r'name="csrf" value="([^"]+)"',self.body).group(1)
  if files:
   boundary='MaStundenTestBoundary';parts=[]
   for k,v in data.items():parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{k}"\r\n\r\n{v}\r\n'.encode())
   for field,(name,mime,content) in files.items():parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{field}"; filename="{name}"\r\nContent-Type: {mime}\r\n\r\n'.encode()+content+b'\r\n')
   parts.append(f'--{boundary}--\r\n'.encode());body=b''.join(parts);headers={'Content-Type':'multipart/form-data; boundary='+boundary}
  else:body=urllib.parse.urlencode(data,doseq=True).encode();headers={}
  try:r=self.opener.open(urllib.request.Request(BASE+path,body,headers))
  except urllib.error.HTTPError as e:r=e
  self.raw=r.read();self.body=self.raw.decode('utf-8',errors='replace');self.status=r.code;return self.body
 def ok(self,label):
  if self.status!=200 or 'class="alert error"' in self.body or 'Fatal error' in self.body:
   pathlib.Path('work/test-failure.html').write_text(self.body);raise AssertionError(label+': '+str(self.status)+' '+re.sub('<[^>]+>',' ',self.body)[-1800:])
  print('PASS',label,flush=True)
 def denied(self,label):
  assert self.status>=400 or 'class="alert error"' in self.body,label
  print('PASS',label,flush=True)
A=Client();A.get('install.php')
if 'Installation gesperrt' not in A.body:
 A.post({'host':'mastunden-db','port':3306,'database':'mastunden','username':'mastunden','db_password':'LocalTestApp-2026','prefix':os.environ.get('MASTUNDEN_TEST_PREFIX','ma_'),'company':'Beispiel GmbH','name':'Beispiel GmbH','email':'admin@example.test','password':'Admin-Test-Password-2026','repeat':'Admin-Test-Password-2026'},'install.php');A.ok('browser installation')
 assert 'MaStunden ist eingerichtet' in A.body
else:
 A.get();A.post({'action':'login','email':'admin@example.test','password':'Admin-Test-Password-2026'});A.ok('administrator login')
A.get('install.php');assert 'Installation gesperrt' in A.body;print('PASS installer locked')
for page in ['dashboard','times','absences','reports','documents','team','approvals','settings','backup','audit','account']:
 A.get('index.php?page='+page);A.ok('page '+page)
A.get('index.php?page=team')
for i,name in [(2,'Anna Weber'),(3,'Jonas Becker')]:
 A.post({'action':'user_save','name':name,'email':f'employee{i}@example.test','role':'employee','employment_start':'2026-01-01','employment_end':'','vacation_days':30,'password':'Employee-Test-Password-2026','active':'on'},'index.php?page=team');A.ok('create employee '+str(i))
for uid in [2,3]:
 A.get('index.php?page=team&edit='+str(uid));A.post({'action':'model_save','user_id':uid,'valid_from':'2026-01-01',**{f'hours[{i}]':8 if i<5 else 0 for i in range(7)},'reason':'Explicit test schedule'},'index.php?page=team&edit='+str(uid));A.ok('explicit schedule')
E=Client();E.get();E.post({'action':'login','email':'employee2@example.test','password':'Employee-Test-Password-2026'});E.ok('employee login');assert 'vorläufige' in E.body
E.post({'action':'password','current':'Employee-Test-Password-2026','new':'Employee-Personal-2026','repeat':'Employee-Personal-2026'},'index.php?page=account');E.ok('required password change')
E.get('index.php?page=times');E.post({'action':'entry_save','start':'2026-09-14T08:00','end':'2026-09-14T16:30','break_start[]':['2026-09-14T12:00'],'break_end[]':['2026-09-14T12:30'],'note':'Test work'},'index.php?page=times&month=2026-09');E.ok('work block and pause')
E.post({'action':'entry_save','start':'2026-09-14T15:00','end':'2026-09-14T17:00'},'index.php?page=times&month=2026-09');E.denied('overlap rejected')
E.get('index.php?page=times');E.post({'action':'entry_save','user_id':'3','start':'2026-09-15T08:00','end':'2026-09-15T09:00'},'index.php?page=times&month=2026-09');E.ok('forged owner bound to current employee')
E.get('index.php?export=csv&users[]=3&month=2026-09');E.denied('cross-user report denied')
E.get('index.php?page=team');E.denied('employee admin page denied')
E.get('index.php?page=times');E.post({'action':'entry_save','csrf':'bad','start':'2026-09-16T08:00','end':'2026-09-16T09:00'});E.denied('CSRF rejected')
E.get('index.php?page=absences');E.post({'action':'absence_save','start_date':'2026-09-14','end_date':'2026-09-14','type':'vacation','fraction':'1'});E.denied('absence over work rejected')
E.get('index.php?page=absences');E.post({'action':'absence_save','start_date':'2026-09-16','end_date':'2026-09-16','type':'vacation','fraction':'1'},'index.php?page=absences&month=2026-09');E.ok('vacation request')
A.get('index.php?page=approvals');A.post({'action':'absence_review','id':1,'status':'approved','reason':''},'index.php?page=approvals');A.ok('vacation approval')
E.get('index.php?page=documents');E.post({'action':'document_upload','title':'Nachweis','category':'Allgemein','absence_id':'0','note':'Test'},'index.php?page=documents',{'file':('nachweis.pdf','application/pdf',b'%PDF-1.4\nTest upload\n%%EOF')});E.ok('protected document upload')
E.get('index.php?download=1');assert E.raw.startswith(b'%PDF');print('PASS own document download')
F=Client();F.get();F.post({'action':'login','email':'employee3@example.test','password':'Employee-Test-Password-2026'});F.post({'action':'password','current':'Employee-Test-Password-2026','new':'Employee3-Personal-2026','repeat':'Employee3-Personal-2026'},'index.php?page=account');F.get('index.php?download=1');F.denied('cross-user document denied')
E.get('index.php?page=times');E.post({'action':'entry_save','start':'2026-09-17T22:00','end':'2026-09-18T06:00','break_start[]':['2026-09-18T02:00'],'break_end[]':['2026-09-18T02:30']},'index.php?page=times&month=2026-09');E.ok('night shift')
E.get('index.php?page=reports&month=2026-09');E.post({'action':'close_month','month':'2026-09','status':'submitted'},'index.php?page=reports&month=2026-09');E.ok('submit month')
E.get('index.php?page=times');E.post({'action':'entry_save','start':'2026-09-19T08:00','end':'2026-09-19T09:00'},'index.php?page=times&month=2026-09');E.denied('submitted month locked')
A.get('index.php?page=reports&user=2&month=2026-09');A.post({'action':'close_month','user_id':2,'month':'2026-09','status':'approved','reason':'Geprüft'},'index.php?page=reports&user=2&month=2026-09');A.ok('month approval')
A.get('index.php?export=pdf&user=2&month=2026-09');assert A.raw.startswith(b'%PDF-1.4');pathlib.Path('work/sample-report.pdf').write_bytes(A.raw);print('PASS PDF export')
A.get('index.php?export=csv&user=2&month=2026-09');assert '480;480' in A.body;print('PASS CSV totals')
A.get('index.php?export=summary&users[]=2&users[]=3&month=2026-09');assert A.raw.startswith(b'%PDF-1.4');print('PASS team summary')
A.get('index.php?page=backup');A.post({'action':'backup_start','backup_password':'Backup-Encryption-2026'},'index.php?page=backup');A.ok('start backup')
E.get('index.php?page=times');E.denied('maintenance blocks employees')
for i in range(100):
 if 'name="action" value="backup_step"' not in A.body:break
 A.post({'action':'backup_step'},'index.php?page=backup');A.ok('backup chunk '+str(i))
match=re.search(r'backup_download=([^"&]+)',A.body);assert match,'backup link missing';backup=match.group(1);A.get('index.php?backup_download='+backup);assert A.raw[:2]==b'PK';zipdata=A.raw;pathlib.Path('work/sample-backup.zip').write_bytes(zipdata);print('PASS encrypted backup download')
A.get('index.php?page=backup');A.post({'action':'restore_upload','archive_password':'Backup-Encryption-2026','backup_password':'Safety-Backup-2026','confirm':'WIEDERHERSTELLEN'},'index.php?page=backup',{'archive':('backup.zip','application/zip',zipdata)});A.ok('restore upload and safety backup')
for i in range(180):
 action='backup_step' if 'name="action" value="backup_step"' in A.body else ('restore_step' if 'name="action" value="restore_step"' in A.body else None)
 if not action:break
 A.post({'action':action},'index.php?page=backup');A.ok('restore phase '+str(i))
assert 'name="action" value="restore_commit"' in A.body
A.post({'action':'restore_commit','confirm':'DATEN ERSETZEN'},'index.php?page=backup');A.ok('atomic restore commit');assert 'Anmelden' in A.body
A.post({'action':'login','email':'admin@example.test','password':'Admin-Test-Password-2026'});A.ok('login after restore')
A.get('index.php?download=1');assert A.raw.startswith(b'%PDF');print('PASS restored document')
A.get('index.php?export=csv&user=2&month=2026-09');assert '480;480' in A.body;print('PASS restored report totals')
E.get();assert 'Willkommen zurück' in E.body;print('PASS previous employee session revoked by restore')
print('ALL INTEGRATION TESTS PASSED')
