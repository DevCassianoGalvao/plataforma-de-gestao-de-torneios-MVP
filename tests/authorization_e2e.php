<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';

use App\Services\ScopeService;
use App\Support\Database;
use App\Support\ScopedRepository;

$db=Database::connection(); $db->beginTransaction();
try {
    $tag='auth-e2e-'.bin2hex(random_bytes(4));
    $insert=function(string $table,array $data)use($db):int{$data['created_at']=date('Y-m-d H:i:s');$data['updated_at']=date('Y-m-d H:i:s');$s=$db->prepare('INSERT INTO `'.$table.'` (`'.implode('`,`',array_keys($data)).'`) VALUES ('.implode(',',array_fill(0,count($data),'?')).')');$s->execute(array_values($data));return(int)$db->lastInsertId();};
    $role=function(string $key)use($db):int{$s=$db->prepare('SELECT id FROM roles WHERE role_key=?');$s->execute([$key]);return(int)$s->fetchColumn();};
    $user=function(string $name)use($insert,$tag):int{return $insert('users',['name'=>$name,'email'=>$tag.'-'.$name.'@example.test','password_hash'=>password_hash('Test@2026',PASSWORD_DEFAULT),'status'=>'active']);};
    $assign=function(int $uid,string $roleKey,array $scope=[])use($insert,$role):void{$insert('user_role_assignments',['user_id'=>$uid,'role_id'=>$role($roleKey),'organization_id'=>$scope['organization_id']??null,'project_id'=>$scope['project_id']??null,'tournament_id'=>$scope['tournament_id']??null,'team_id'=>$scope['team_id']??null,'status'=>'active']);};
    $org=$insert('organizations',['name'=>$tag,'slug'=>$tag]);$p1=$insert('projects',['organization_id'=>$org,'name'=>$tag.' p1','slug'=>$tag.'-p1']);$p2=$insert('projects',['organization_id'=>$org,'name'=>$tag.' p2','slug'=>$tag.'-p2']);
    $t1=$insert('tournaments',['project_id'=>$p1,'name'=>$tag.' t1','slug'=>$tag.'-t1','season'=>'2026','status'=>'active']);$t2=$insert('tournaments',['project_id'=>$p2,'name'=>$tag.' t2','slug'=>$tag.'-t2','season'=>'2026','status'=>'active']);
    $a=$insert('teams',['project_id'=>$p1,'name'=>$tag.' A','short_name'=>'A','status'=>'active']);$b=$insert('teams',['project_id'=>$p1,'name'=>$tag.' B','short_name'=>'B','status'=>'active']);$outsider=$insert('teams',['project_id'=>$p2,'name'=>$tag.' X','short_name'=>'X','status'=>'active']);$insert('team_tournament_entries',['tournament_id'=>$t1,'team_id'=>$a,'status'=>'approved']);$insert('team_tournament_entries',['tournament_id'=>$t1,'team_id'=>$b,'status'=>'approved']);$insert('team_tournament_entries',['tournament_id'=>$t2,'team_id'=>$outsider,'status'=>'approved']);
    $personA=$insert('people',['full_name'=>$tag.' athlete A','person_type'=>'athlete','status'=>'active']);$personB=$insert('people',['full_name'=>$tag.' athlete B','person_type'=>'athlete','status'=>'active']);$insert('team_memberships',['team_id'=>$a,'person_id'=>$personA,'role'=>'athlete','status'=>'active']);$insert('team_memberships',['team_id'=>$b,'person_id'=>$personB,'role'=>'athlete','status'=>'active']);
    $match=$insert('matches',['tournament_id'=>$t1,'home_team_id'=>$a,'away_team_id'=>$b,'status'=>'scheduled']);$other=$insert('matches',['tournament_id'=>$t2,'home_team_id'=>$outsider,'away_team_id'=>$outsider,'status'=>'scheduled']);
    $doc=$insert('documents',['tournament_id'=>$t1,'team_id'=>$a,'title'=>$tag.' private','file_path'=>'private/none.pdf','visibility'=>'private','status'=>'active']);
    $super=$user('super');$projectAdmin=$user('project');$organizer=$user('organizer');$manager=$user('manager');$operator=$user('operator');$communication=$user('comm');$auditor=$user('auditor');
    $assign($super,'superadmin');$assign($projectAdmin,'project_admin',['project_id'=>$p1]);$assign($organizer,'tournament_organizer',['tournament_id'=>$t1]);$assign($manager,'team_manager',['tournament_id'=>$t1,'team_id'=>$a]);$assign($operator,'match_operator',['tournament_id'=>$t1]);$assign($communication,'communication',['tournament_id'=>$t1]);$assign($auditor,'auditor',['tournament_id'=>$t1]);
    $insert('match_operator_assignments',['user_id'=>$operator,'match_id'=>$match,'status'=>'active']);
    $scope=new ScopeService($db);$yes=function(bool $v,string $m):void{if(!$v)throw new RuntimeException('FAIL '.$m);};$no=function(bool $v,string $m):void{if($v)throw new RuntimeException('FAIL '.$m);};
    $yes($scope->allows($super,'manage_permissions',$scope->context('organizations',$org)),'super global');
    $yes($scope->allows($projectAdmin,'view',$scope->context('projects',$p1)),'project own');$no($scope->allows($projectAdmin,'view',$scope->context('tournaments',$t2)),'project IDOR');
    $yes($scope->allows($organizer,'manage_bracket',$scope->context('tournaments',$t1)),'organizer tournament');$no($scope->allows($organizer,'homologate_match',$scope->context('matches',$other)),'organizer foreign match');
    $yes($scope->allows($manager,'update',$scope->context('people',$personA)),'manager own athlete');$no($scope->allows($manager,'view',$scope->context('people',$personB)),'manager foreign athlete');$yes($scope->allows($manager,'download_private_file',$scope->context('documents',$doc)),'manager own document');$no($scope->allows($manager,'homologate_match',$scope->context('matches',$match)),'manager homologation');
    $yes($scope->allows($operator,'operate_match',$scope->context('matches',$match)),'assigned operator');$no($scope->allows($operator,'operate_match',$scope->context('matches',$other)),'operator foreign match');$no($scope->allows($operator,'manage_regulation',$scope->context('tournaments',$t1)),'operator regulation');
    $yes($scope->allows($communication,'publish',$scope->context('tournaments',$t1)),'communication publish');$no($scope->allows($communication,'homologate_match',$scope->context('matches',$match)),'communication result');$yes($scope->allows($auditor,'export',$scope->context('tournaments',$t1)),'auditor export');$no($scope->allows($auditor,'update',$scope->context('matches',$match)),'auditor mutation');
    $repo=new ScopedRepository($db,['id'=>$manager]);$yes($repo->find('people',$personA)!==null,'scoped repo own');$yes($repo->find('people',$personB)===null,'scoped repo hides foreign ID');
    $db->rollBack(); echo "AUTHORIZATION_E2E_OK\n";
} catch (Throwable $e) { if($db->inTransaction())$db->rollBack();fwrite(STDERR,$e->getMessage()."\n");exit(1); }
