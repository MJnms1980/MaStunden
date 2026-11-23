import subprocess,time,os
C=os.environ['MASTUNDEN_TEST_CONTAINER']
pre="$GLOBALS['config']=require '/app/config.php';require '/app/lib/core.php';require '/app/lib/files.php';"
def run(code):
 p=subprocess.run(['docker','exec',C,'php','-r',pre+code],capture_output=True,text=True,timeout=15);assert p.returncode==0,p.stderr;return p.stdout
reader=subprocess.Popen(['docker','exec',C,'php','-r',pre+"app_lock(false);echo 'ready'.PHP_EOL;fflush(STDOUT);sleep(3);"],stdout=subprocess.PIPE,stderr=subprocess.PIPE)
assert reader.stdout.readline().strip()==b'ready';start=time.monotonic();run("$_SERVER['REQUEST_METHOD']='GET';request_lock();echo 'ok';");elapsed=time.monotonic()-start;assert elapsed<1.5,elapsed;reader.communicate();print('PASS parallel read requests do not wait for other readers')
run("file_put_contents(storage_path('stream-test.txt'),str_repeat('x',4*1024*1024));")
stream=subprocess.Popen(['docker','exec',C,'php','-r',pre+"app_lock(false);stream_private_file(storage_path('stream-test.txt'));"],stdout=subprocess.PIPE,stderr=subprocess.PIPE)
assert stream.stdout.read(128)==b'x'*128
assert stream.poll() is None
start=time.monotonic();run("app_lock();echo 'ok';");assert time.monotonic()-start<1.5;stream.communicate(timeout=15);run("unlink(storage_path('stream-test.txt'));");print('PASS blocked download stream releases global lock')
for c in ["['host'=>'203.0.113.1','port'=>3306,'database'=>'test','username'=>'test','password'=>'test','db_tls'=>'private-network']", "['host'=>'external.example.org','port'=>3306,'database'=>'test','username'=>'test','password'=>'test','db_tls'=>'local']"]:
 result=run("try{database_connect("+c+");exit(1);}catch(RuntimeException $e){echo 'denied';}");assert result=='denied'
print('PASS private-network compatibility cannot bypass TLS for public servers')
