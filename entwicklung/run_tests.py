"""Isolated synthetic-data regression suite. Requires Docker and Python 3."""
import argparse,os,pathlib,shutil,socket,subprocess,tempfile,time,uuid
parser=argparse.ArgumentParser();parser.add_argument('--php',default='8.4',choices=['8.3','8.4']);parser.add_argument('--use-existing-image',action='store_true',help='Use an already built local test image');args=parser.parse_args()
root=pathlib.Path(__file__).resolve().parents[1];dev=root/'entwicklung';tag='mastunden-test:'+args.php
name='mastunden-tests-'+uuid.uuid4().hex[:10];net=name+'-net';db=name+'-db';app=name+'-app'
def call(command,**kw):return subprocess.run(command,check=True,**kw)
with tempfile.TemporaryDirectory(prefix='mastunden-tests-') as temporary:
 tmp=pathlib.Path(temporary);web=tmp/'web';shutil.copytree(root/'web',web,ignore=shutil.ignore_patterns('config.php','install-lock.php'))
 (tmp/'work').mkdir();port_socket=socket.socket();port_socket.bind(('127.0.0.1',0));port=port_socket.getsockname()[1];port_socket.close()
 try:
  if not args.use_existing_image:call(['docker','build','--build-arg','PHP_VERSION='+args.php,'-t',tag,str(dev)])
  call(['docker','network','create',net])
  call(['docker','run','-d','--name',db,'--network',net,'--network-alias','mastunden-db','-e','MYSQL_ROOT_PASSWORD=Synthetic-Root-Only-2026','-e','MYSQL_DATABASE=mastunden','-e','MYSQL_USER=mastunden','-e','MYSQL_PASSWORD=LocalTestApp-2026','mysql:8.4'])
  for _ in range(90):
   probe=subprocess.run(['docker','exec','-e','MYSQL_PWD=LocalTestApp-2026',db,'mysql','--protocol=TCP','-hmastunden-db','-umastunden','mastunden','-e','SELECT 1'],capture_output=True)
   if probe.returncode==0:break
   time.sleep(1)
  else:raise RuntimeError('Isolated database did not become ready')
  call(['docker','run','-d','--name',app,'--network',net,'-p',f'127.0.0.1:{port}:8080','-e','MASTUNDEN_LOCAL_TEST=1','-v',str(web)+':/app',tag])
  call(['docker','exec',app,'sh','-c','find /app -name "*.php" -exec php -l {} \\;'])
  env=os.environ|{'MASTUNDEN_TEST_URL':f'http://127.0.0.1:{port}/','MASTUNDEN_TEST_PREFIX':'tests_','MASTUNDEN_TEST_CONTAINER':app,'MASTUNDEN_TEST_DB_CONTAINER':db}
  for script in ['integration.py','security_tests.py','lock_tests.py','sql_tests.py','update_tests.py']:
   call(['python3',str(dev/script)],cwd=tmp,env=env)
  print('ALL ISOLATED TESTS PASSED (PHP '+args.php+')',flush=True)
 finally:
  for container in [app,db]:subprocess.run(['docker','rm','-f',container],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
  subprocess.run(['docker','network','rm',net],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
