<?php
declare(strict_types=1);
$config=require __DIR__.'/../config.php';require __DIR__.'/../lib/core.php';require __DIR__.'/../lib/time.php';
function check(bool $v,string $label): void {if(!$v)throw new RuntimeException($label);echo "PASS $label\n";}
function rejects(callable $f,string $label): void {try{$f();}catch(RuntimeException $e){echo "PASS $label\n";return;}throw new RuntimeException($label);}
check(parse_local('2026-03-29T01:30')==='2026-03-29 00:30:00','spring conversion');
rejects(fn()=>parse_local('2026-03-29T02:30'),'nonexistent spring hour rejected');
rejects(fn()=>parse_local('2026-10-25T02:30'),'ambiguous autumn hour rejected');
check(parse_local('2026-10-25T02:30','+02:00')==='2026-10-25 00:30:00','first autumn hour');
check(parse_local('2026-10-25T02:30','+01:00')==='2026-10-25 01:30:00','second autumn hour');
rejects(fn()=>parse_local('2026-02-30T09:00'),'invalid calendar date rejected');
$e=['start_at'=>'2026-03-29 00:00:00','end_at'=>'2026-03-29 03:00:00','breaks_json'=>'[["2026-03-29 01:00:00","2026-03-29 01:30:00"]]'];
check(work_minutes($e,strtotime('2026-03-28 UTC'),strtotime('2026-03-30 UTC'))===150,'actual elapsed time across spring DST');
$e=['start_at'=>'2026-09-30 20:00:00','end_at'=>'2026-10-01 04:00:00','breaks_json'=>'[]'];
check(work_minutes($e,strtotime('2026-09-29 UTC'),strtotime('2026-09-30 22:00 UTC'))===120,'September overnight allocation');
check(work_minutes($e,strtotime('2026-09-30 22:00 UTC'),strtotime('2026-10-02 UTC'))===360,'October overnight allocation');
check(month_report(2,'2026-09')['sum']['work']===990,'monthly work total 16:30');
check(month_report(2,'2026-09')['sum']['credit']===480,'vacation credit one day');
check(vacation(2,2026)['used']===1.0,'vacation accounting');
db()->beginTransaction();try{
 insert('absences',['user_id'=>3,'start_date'=>'2026-09-21','end_date'=>'2026-09-21','type'=>'vacation','fraction'=>0,'amount_minutes'=>120,'status'=>'approved','note'=>'','response'=>'','created_at'=>now()]);
 check(month_report(3,'2026-09',false)['sum']['credit']===120,'hourly absence credit');check(vacation(3,2026)['used']===0.25,'hourly vacation converted to scheduled day');
 insert('models',['user_id'=>3,'valid_from'=>'2026-09-15','minutes'=>'[240,240,240,240,240,0,0]']);check(scheduled(3,'2026-09-14')===480,'old work model retained');check(scheduled(3,'2026-09-15')===240,'new effective work model');
 insert('holidays',['day'=>'2026-09-22','name'=>'Testfeiertag']);check(month_report(3,'2026-09',false)['sum']['credit']===360,'holiday plus hourly credit');
 $snap=month_report(2,'2026-09');insert('models',['user_id'=>2,'valid_from'=>'2026-09-01','minutes'=>'[60,60,60,60,60,0,0]']);check(month_report(2,'2026-09')['sum']===$snap['sum'],'approved snapshot stable');
}finally{db()->rollBack();}
echo "ALL DOMAIN TESTS PASSED\n";
