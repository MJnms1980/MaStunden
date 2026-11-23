<?php
declare(strict_types=1);
function audit_subject_matches(?array $data,int $id): bool {
 if(!$data)return false;
 if((int)($data['user_id']??0)===$id||(int)($data['user']['id']??0)===$id)return true;
 if(isset($data['snapshot'])&&is_string($data['snapshot']))return audit_subject_matches(json_decode($data['snapshot'],true),$id);
 return false;
}
function anonymize_mentions(string $text,array $person): string {
 // Replace complete tokens only. Never use a name substring as a deletion key.
 foreach([$person['email'],$person['name']] as $value){
  if($value==='')continue;
  $text=preg_replace('/(?<![\p{L}\p{N}_])'.preg_quote($value,'/').'(?![\p{L}\p{N}_])/u','[gelöschte Person]',$text)??$text;
 }
 return $text;
}
function anonymize_audit_value(mixed $value,array $person): mixed {
 if(is_array($value)){foreach($value as $k=>$v)$value[$k]=anonymize_audit_value($v,$person);return $value;}
 if(is_string($value)){
  $decoded=json_decode($value,true);if(is_array($decoded))return json_encode(anonymize_audit_value($decoded,$person),JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
  return anonymize_mentions($value,$person);
 }
 return $value;
}
function delete_employee(int $id): void {
 require_admin();$person=one('users','id=?',[$id]);if(!$person)fail('Mitarbeiter nicht gefunden.');
 if($id===(int)user()['id'])fail('Das eigene angemeldete Konto kann nicht gelöscht werden.');
 if($person['role']==='admin'&&$person['active']&&count(rows('users',"role='admin' AND active=1"))<=1)fail('Der letzte aktive Administrator darf nicht gelöscht werden.');
 if(post('confirm')!=='MITARBEITER LÖSCHEN'||post('retention_checked')!=='1')fail('Bitte Löschung bestätigen und Aufbewahrungspflichten prüfen.');
 if(!password_verify(post('admin_password'),user()['password']))fail('Administratorpasswort stimmt nicht.');
 $reason=require_reason();$ids=[];$counts=[];
 foreach(['entries','absences','models','closures','documents','adjustments','notifications'] as $table){$items=rows($table,'user_id=?',[$id]);$ids[$table]=array_map('intval',array_column($items,'id'));$counts[$table]=count($items);if($table==='documents')foreach($items as $item)file_intent('delete',$item['storage_name']);}
 foreach(rows('audit') as $event){
  $before=json_decode($event['before_json']??'null',true);$after=json_decode($event['after_json']??'null',true);
  $related=($event['entity']==='users'&&(int)$event['entity_id']===$id)||in_array((int)$event['entity_id'],$ids[$event['entity']]??[],true)||audit_subject_matches($before,$id)||audit_subject_matches($after,$id);
  if($related){q('DELETE FROM '.table('audit').' WHERE id=?',[$event['id']]);continue;}
  $clean=['actor_id'=>(int)$event['actor_id']===$id?null:$event['actor_id'],'reason'=>anonymize_mentions($event['reason'],$person)];
  foreach(['before_json'=>$before,'after_json'=>$after] as $field=>$data)$clean[$field]=$data===null?null:json_encode(anonymize_audit_value($data,$person),JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
  update('audit',(int)$event['id'],$clean);
 }
 foreach(rows('notifications','user_id<>?',[$id]) as $notice){$text=anonymize_mentions($notice['message'],$person);if($text!==$notice['message'])update('notifications',(int)$notice['id'],['message'=>$text]);}
 foreach(array_keys($ids) as $table)q('DELETE FROM '.table($table).' WHERE user_id=?',[$id]);
 q('DELETE FROM '.table('attempts').' WHERE bucket IN (?,?)',[hash_hmac('sha256',strtolower($person['email']),cfg()['key']),'upload:'.$id]);
 q('DELETE FROM '.table('users').' WHERE id=?',[$id]);
 audit('employee_erased','users',null,null,['deleted_records'=>$counts],$reason);
 flash('Mitarbeiter und zugehörige aktive Daten gelöscht. Sicherungsarchive bleiben erhalten und können gelöschte Daten wiederherstellen. Lokale und externe Sicherungen sowie Exporte nach Ihrem Aufbewahrungskonzept separat behandeln.');
}
