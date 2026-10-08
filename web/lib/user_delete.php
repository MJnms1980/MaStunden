<?php
declare(strict_types=1);
function delete_employee(int $id): void {
 require_admin();$person=one('users','id=?',[$id]);if(!$person)fail('Mitarbeiter nicht gefunden.');
 if($id===(int)user()['id'])fail('Das eigene angemeldete Konto kann nicht gelöscht werden.');
 if($person['role']==='admin'&&$person['active']&&count(rows('users',"role='admin' AND active=1"))<=1)fail('Der letzte aktive Administrator darf nicht gelöscht werden.');
 if(post('confirm')!=='MITARBEITER LÖSCHEN'||post('retention_checked')!=='1')fail('Bitte Löschung bestätigen und Aufbewahrungspflichten prüfen.');
 if(!password_verify(post('admin_password'),user()['password']))fail('Administratorpasswort stimmt nicht.');
 $reason=require_reason();$ids=[];$counts=[];$files=[];
 foreach(['entries','absences','models','closures','documents','adjustments','notifications'] as $table){$items=rows($table,'user_id=?',[$id]);$ids[$table]=array_map('intval',array_column($items,'id'));$counts[$table]=count($items);if($table==='documents')foreach($items as $item)$files[]=storage_path($item['storage_name']);}
 // Existing on-server archives may contain the deleted person. External copies need separate handling.
 foreach(['backup-*.zip','restore-*.zip','incoming-backup.zip'] as $pattern)foreach(glob(cfg()['storage'].'/'.$pattern)?:[] as $file)$files[]=$file;
 foreach(array_unique($files) as $file){if(!is_file($file))continue;$quarantine=storage_path('erase-'.bin2hex(random_bytes(24)).'.tmp');if(!rename($file,$quarantine))fail('Eine Datei konnte nicht für die Löschung vorbereitet werden.');$GLOBALS['erase_rollback'][$quarantine]=$file;$GLOBALS['delete_after_commit'][]=$quarantine;}
 foreach(rows('audit') as $event){$before=json_decode($event['before_json']??'null',true);$after=json_decode($event['after_json']??'null',true);$related=(int)$event['actor_id']===$id||($event['entity']==='users'&&(int)$event['entity_id']===$id)||in_array((int)$event['entity_id'],$ids[$event['entity']]??[],true);
 foreach([$before,$after] as $data)if(is_array($data)&&((int)($data['user_id']??0)===$id||(int)($data['user']['id']??0)===$id))$related=true;
 // Includes older deletion events whose target row no longer exists.
 $text=($event['before_json']??'').' '.($event['after_json']??'').' '.$event['reason'];
 foreach([$person['email'],$person['name']] as $needle)if($needle!==''&&str_contains($text,$needle))$related=true;
 if($related)q('DELETE FROM '.table('audit').' WHERE id=?',[$event['id']]);}
 foreach(rows('notifications') as $notice)if(str_contains($notice['message'],$person['name'])||str_contains($notice['message'],$person['email']))q('DELETE FROM '.table('notifications').' WHERE id=?',[$notice['id']]);
 foreach(array_keys($ids) as $table)q('DELETE FROM '.table($table).' WHERE user_id=?',[$id]);
 q('DELETE FROM '.table('attempts').' WHERE bucket=?',[hash_hmac('sha256',strtolower($person['email']),cfg()['key'])]);
 q('DELETE FROM '.table('users').' WHERE id=?',[$id]);
 audit('employee_erased','users',null,null,['deleted_records'=>$counts],$reason);
 flash('Mitarbeiter und zugehörige Daten gelöscht. Externe Backups und Exporte separat prüfen; alte Sicherungen können gelöschte Daten wiederherstellen.');
}
