<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
use App\Services\ScopeService;
use App\Support\Database;

$seed=dirname(__DIR__).'/database/seed.php';$php=PHP_BINARY;
passthru(escapeshellarg($php).' '.escapeshellarg($seed).' --demo',$code);if($code!==0)throw new RuntimeException('Demo seed failed.');
$db=Database::connection();$count=fn(string $sql,array $params=[]):int=>((function()use($db,$sql,$params){$s=$db->prepare($sql);$s->execute($params);return$s->fetchColumn();})());
if($count('SELECT COUNT(*) FROM users WHERE email LIKE "%@example.com"')<20)throw new RuntimeException('Demo users missing.');
if($count('SELECT COUNT(*) FROM people WHERE email LIKE "athlete-%@example.com"')<288)throw new RuntimeException('Demo athletes missing.');
$cup=(int)$db->query("SELECT id FROM tournaments WHERE slug='copa-brasil-de-talentos-2026'")->fetchColumn();if(!$cup||$count('SELECT COUNT(*) FROM tournament_rule_versions WHERE tournament_id=?',[$cup])<1)throw new RuntimeException('Demo rules missing.');
if($count('SELECT COUNT(*) FROM groups_competition g JOIN stages s ON s.id=g.stage_id WHERE s.tournament_id=? AND g.group_code IN ("A","B")',[$cup])!==2)throw new RuntimeException('Demo groups missing.');
if($count('SELECT COUNT(*) FROM matches WHERE tournament_id=? AND home_team_id=away_team_id',[$cup])!==0)throw new RuntimeException('Invalid demo match.');
if($count('SELECT COUNT(*) FROM match_events e JOIN matches m ON m.id=e.match_id WHERE m.tournament_id=? AND e.event_type IN ("goal","own_goal") AND e.is_cancelled=0',[$cup])<1)throw new RuntimeException('Demo events missing.');
if($count('SELECT COUNT(*) FROM standings_snapshots WHERE tournament_id=?',[$cup])<10)throw new RuntimeException('Demo standings missing.');
if($count('SELECT COUNT(*) FROM stages WHERE tournament_id=? AND stage_key="quarterfinal"',[$cup])!==1)throw new RuntimeException('Demo knockout missing.');
if(!is_file(dirname(__DIR__).'/public/uploads-public/demo/team.svg')||!is_file(dirname(__DIR__).'/storage/private/demo/documento-ficticio.pdf'))throw new RuntimeException('Demo files missing.');
$teamUser=(int)$db->query("SELECT id FROM users WHERE email='treinador01@example.com'")->fetchColumn();$match=(int)$db->query("SELECT m.id FROM matches m JOIN user_role_assignments a ON a.team_id IN (m.home_team_id,m.away_team_id) WHERE a.user_id=$teamUser AND m.tournament_id=$cup LIMIT 1")->fetchColumn();$scope=new ScopeService($db);if(!$match||!$scope->allows($teamUser,'view',$scope->context('matches',$match)))throw new RuntimeException('Demo team scope missing.');
$spec=[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']];$proc=proc_open([$php,$seed,'--demo'],$spec,$pipes,null,['APP_ENV'=>'production']);if(!is_resource($proc))throw new RuntimeException('Production seed check did not start.');fclose($pipes[0]);stream_get_contents($pipes[1]);stream_get_contents($pipes[2]);$production=proc_close($proc);if($production===0)throw new RuntimeException('Production seed was not blocked.');
echo "DEMO_SEED_OK\n";
