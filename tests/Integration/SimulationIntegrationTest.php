<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Repositories\SimulationRepository;
use App\Repositories\UserRepository;
use App\Services\AuditService;
use App\Services\SimulationService;
use App\Services\StandingsCalculator;
use function Tests\assert_same;
use function Tests\assert_true;

final class SimulationIntegrationTest
{
    public static function run(): void
    {
        $pdo=Database::connection();$repo=new SimulationRepository($pdo);$service=new SimulationService($repo,new StandingsCalculator(),new AuditService($pdo));$admin=(new UserRepository($pdo))->findByEmail('admin@torneios.local');$phase=$pdo->query("SELECT * FROM competition_phases WHERE phase_type='groups' ORDER BY id LIMIT 1")->fetch();$group=$repo->groups((int)$phase['id'])[0];$officialMatches=(int)$pdo->query('SELECT COUNT(*) FROM matches')->fetchColumn();$officialStandings=(int)$pdo->query('SELECT COUNT(*) FROM competition_standings')->fetchColumn();$officialReports=(int)$pdo->query('SELECT COUNT(*) FROM match_reports')->fetchColumn();
        $created=$service->create(['name'=>'Cenário isolado de teste','championship_id'=>$phase['championship_id'],'phase_id'=>$phase['id'],'group_id'=>$group['id'],'description'=>'Teste'],(int)$admin['id']);assert_true($created['ok'],'Cenario nao criado');$id=(int)$created['id'];$reference=$repo->officialMatches((int)$phase['id'],(int)$group['id'])[0];assert_true($service->addReference($id,(int)$reference['id'],(int)$admin['id'])['ok'],'Referencia nao adicionada');$simulated=$repo->matches($id)[0];assert_true($service->score($id,(int)$simulated['id'],['home_score'=>2,'away_score'=>1],(int)$admin['id'])['ok'],'Placar nao salvo');assert_true($service->addEvent($id,(int)$simulated['id'],['team_id'=>$simulated['home_team_id'],'event_type'=>'goal'],(int)$admin['id'])['ok'],'Evento nao salvo');assert_true($service->simulateRound($id,(int)$reference['round_id'],(int)$admin['id'])['ok'],'Rodada nao simulada');$projection=$service->projection($id);assert_true(isset($projection[(int)$group['id']]['simulated']), 'Classificacao simulada ausente');assert_same($officialMatches,(int)$pdo->query('SELECT COUNT(*) FROM matches')->fetchColumn(),'Simulacao alterou partidas oficiais');assert_same($officialStandings,(int)$pdo->query('SELECT COUNT(*) FROM competition_standings')->fetchColumn(),'Simulacao alterou classificacao oficial');assert_same($officialReports,(int)$pdo->query('SELECT COUNT(*) FROM match_reports')->fetchColumn(),'Simulacao gerou sumula oficial');
        $copy=$repo->duplicate($id,(int)$admin['id']);assert_true(count($repo->matches($copy))>=1,'Duplicacao nao copiou partidas');$copyMatch=$repo->matches($copy)[0];assert_true($service->score($copy,(int)$copyMatch['id'],['home_score'=>0,'away_score'=>3],(int)$admin['id'])['ok'],'Variacao do cenario duplicado falhou');assert_true($service->compare($id,$copy)!==[],'Comparacao de cenarios ausente');$repo->archive($id);assert_same('archived',$repo->scenario($id)['status'],'Arquivamento falhou');$repo->delete($copy);assert_true($repo->scenario($copy)===null,'Exclusao logica falhou');
    }
}
