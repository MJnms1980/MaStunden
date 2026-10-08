import os,pathlib,re
os.environ['MASTUNDEN_TEST_URL']='http://127.0.0.1:8090/main/'
exec(pathlib.Path('work/test_app.py').read_text().split('A=Client()')[0])
c=Client();c.get('install.php')
for field in ['company','name']:
 assert re.search('name="'+field+'" value=""',c.body),field
print('PASS company and name initially empty')
data={'host':'mastunden-db','port':3306,'database':'mastunden','username':'mastunden','db_password':'LocalTestApp-2026','prefix':'mh_','company':'Example Company','name':'Test Admin','email':'admin@example.test','password':'Admin-Test-Password-2026','repeat':'Admin-Test-Password-2026','ftp_enabled':'1','ftp_host':'localhost','ftp_port':2121,'ftp_user':'installer','ftp_password':'Synthetic-FTP-2026','ftp_path':'/public/main','ftp_protocol':'ftps'}
for changes,expected in [({'company':''},'Unternehmensname fehlt'),({'name':''},'Ihr Name fehlt'),({'ftp_path':'/public/../main'},'absoluten FTP-Ordner'),({'ftp_password':'wrong'},'FTP-Einrichtung fehlgeschlagen'),({'ftp_host':'127.0.0.1'},'FTP-Einrichtung fehlgeschlagen'),({'ftp_path':'/wrong/main'},'FTP-Einrichtung fehlgeschlagen')]:
 c.post(data|changes,'install.php');assert expected in c.body,c.body
 assert 'Synthetic-FTP-2026' not in c.body
 print('PASS rejection:',expected,changes.keys())
c.post(data,'install.php');c.ok('FTPS installation with read-only program folder');assert 'MaStunden ist eingerichtet' in c.body
c.get('install.php');assert c.status==403;print('PASS installed setup locked')
