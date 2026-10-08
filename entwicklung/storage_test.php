<?php
declare(strict_types=1);
require '/app/lib/install_storage.php';
function verify(bool $v,string $label): void { if(!$v)throw new RuntimeException($label);echo "PASS $label\n"; }
$base=sys_get_temp_dir().'/mastunden-test-'.bin2hex(random_bytes(8));mkdir($base,0700);mkdir($base.'/public',0700);mkdir($base.'/public/sub',0700);
$a=create_private_storage($base.'/public');$b=create_private_storage($base.'/public');
verify(is_dir($a)&&is_writable($a),'storage created automatically');
verify(dirname($a)===$base&&!str_starts_with($a,$base.'/public/'),'storage outside entire web root');
verify((fileperms($a)&0777)===0700,'private directory permissions');
verify($a!==$b,'separate installations receive separate folders');
symlink($base.'/public',$base.'/alias');$c=create_private_storage($base.'/alias');verify(dirname($c)===$base,'canonical root handles symlink');unlink($base.'/alias');
chmod($base,0555);$failed=false;try{create_private_storage($base.'/public');}catch(RuntimeException $e){$failed=true;}verify($failed,'non-writable parent fails without public fallback');chmod($base,0700);
$failed=false;try{create_private_storage($base.'/missing');}catch(RuntimeException $e){$failed=true;}verify($failed,'unknown web root rejected');
foreach([$a,$b,$c,$base.'/public/sub',$base.'/public',$base] as $path)rmdir($path);
