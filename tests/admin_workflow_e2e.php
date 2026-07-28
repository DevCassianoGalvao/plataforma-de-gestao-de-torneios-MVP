<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';

use App\Services\AssistedAdministrationService;
use App\Services\TournamentOperationService;
use App\Support\Database;

$view=(string)file_get_contents(dirname(__DIR__).'/app/Views/admin/tournament-operations.php');
$controller=(string)file_get_contents(dirname(__DIR__).'/app/Controllers/TournamentOperationController.php');
foreach(['players_json','name="match_id" type="number"','name="team_id" type="number"','name="person_id" type="number"'] as $forbidden) if(str_contains($view,$forbidden)) throw new RuntimeException('Technical input exposed: '.$forbidden);
foreach(['name="team_id"','name="person_id"','name="stage_id"','name="group_id"','name="registration_id"','name="match_id"'] as $guided) if(!str_contains($view,$guided)) throw new RuntimeException('Guided workflow field missing: '.$guided);
foreach(['requireTournamentPermission','matchAccess','relation($tid,\'registration\'','relation($tid,\'group\'','Security::verifyCsrf'] as $guard) if(!str_contains($controller,$guard)) throw new RuntimeException('Controller protection missing: '.$guard);
$db=Database::connection();$suffix=bin2hex(random_bytes(4));$now=date('Y-m-d H:i:s');
$db->prepare('INSERT INTO organizations(status,name,slug,created_at,updated_at) VALUES("active",?,?,?,?)')->execute(['Admin workflow '.$suffix,'admin-workflow-'.$suffix,$now,$now]);$organization=(int)$db->lastInsertId();
$db->prepare('INSERT INTO projects(organization_id,status,name,slug,created_at,updated_at) VALUES(?,"active",?,?,?,?)')->execute([$organization,'Projeto '.$suffix,'project-'.$suffix,$now,$now]);$project=(int)$db->lastInsertId();
$db->prepare('INSERT INTO tournaments(project_id,status,name,slug,created_at,updated_at) VALUES(?,"draft",?,?,?,?)')->execute([$project,'Campeonato '.$suffix,'tournament-'.$suffix,$now,$now]);$tournament=(int)$db->lastInsertId();
$service=new AssistedAdministrationService($db);$teamA=$service->createTeam($tournament,['name'=>'Equipe A '.$suffix,'slug'=>'team-a-'.$suffix]);$teamB=$service->createTeam($tournament,['name'=>'Equipe B '.$suffix,'slug'=>'team-b-'.$suffix]);$athlete=$service->createAthlete($tournament,['team_id'=>$teamA,'full_name'=>'Atleta '.$suffix,'public_name'=>'Atleta']);$staff=$service->createStaff($tournament,['team_id'=>$teamA,'full_name'=>'Treinador '.$suffix,'role'=>'Treinador']);
if(!(int)$db->query('SELECT COUNT(*) FROM team_tournament_entries WHERE tournament_id='.(int)$tournament.' AND team_id='.(int)$teamA)->fetchColumn()) throw new RuntimeException('Team was not linked to tournament.');
if(!(int)$db->query('SELECT COUNT(*) FROM team_memberships WHERE person_id='.(int)$athlete.' AND team_id='.(int)$teamA.' AND role="athlete"')->fetchColumn()) throw new RuntimeException('Athlete linkage missing.');
if(!(int)$db->query('SELECT COUNT(*) FROM team_memberships WHERE person_id='.(int)$staff.' AND team_id='.(int)$teamA.' AND role="Treinador"')->fetchColumn()) throw new RuntimeException('Staff linkage missing.');
$operation=new TournamentOperationService($db);try{$operation->registerAthlete($tournament,$teamB,$athlete);throw new RuntimeException('Cross-team registration was accepted.');}catch(RuntimeException $e){if($e->getMessage()!=='Atleta não pertence à equipe selecionada.')throw $e;}
echo "ADMIN_WORKFLOW_E2E_OK\n";
