<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Database\MatchOperationSeed;
use App\Repositories\LineupRepository;
use App\Repositories\MatchOperationRepository;
use App\Repositories\ScheduleRepository;
use App\Repositories\TacticalFormationRepository;
use App\Repositories\TeamRepository;
use App\Repositories\UserRepository;
use App\Services\AuditService;
use App\Services\AuthorizationService;
use App\Services\LineupService;
use App\Services\MatchOperationAccessService;
use App\Services\MatchOperationService;
use function Tests\assert_same;
use function Tests\assert_true;

final class MatchOperationIntegrationTest
{
    public static function run(): void
    {
        $pdo = Database::connection();
        MatchOperationSeed::run($pdo);
        MatchOperationSeed::run($pdo);
        assert_same(1, (int) $pdo->query('SELECT COUNT(*) FROM match_operator_assignments')->fetchColumn(), 'Seed de operadores duplicou atribuicoes');
        assert_same(4, (int) $pdo->query('SELECT COUNT(*) FROM match_officials')->fetchColumn(), 'Seed de arbitragem duplicou funcoes');

        $users = new UserRepository($pdo);
        $admin = $users->findByEmail('admin@torneios.local');
        $operator = $users->findByEmail('operador@torneios.local');
        $matchId = (int) $pdo->query('SELECT id FROM matches ORDER BY id LIMIT 1')->fetchColumn();
        $schedules = new ScheduleRepository($pdo);
        $match = $schedules->matchById($matchId);
        assert_true($match !== null, 'Fixture de partida ausente');
        $lineups = new LineupRepository($pdo);
        $authorization = new AuthorizationService($users);
        $lineupService = new LineupService($lineups, new TacticalFormationRepository($pdo), new TeamRepository($pdo), $authorization, new AuditService($pdo));
        $formationId = (int) $pdo->query("SELECT id FROM tactical_formations WHERE slug = '4-4-2' LIMIT 1")->fetchColumn();

        foreach ([(int) $match['home_team_id'], (int) $match['away_team_id']] as $teamId) {
            $draft = $lineupService->ensureDraft($admin, $match, $teamId);
            $suggestion = $lineupService->suggest($match, $teamId, $formationId);
            $suggestion['staff_ids'] = array_map(static fn (array $member): int => (int) $member['id'], $lineups->staff($teamId));
            $result = $lineupService->save($admin, $match, $draft, array_merge(['formation_id' => $formationId], $suggestion), true);
            assert_true($result['ok'], 'Escalacao de operacao nao foi confirmada');
        }

        $operations = new MatchOperationRepository($pdo);
        $service = new MatchOperationService($operations, $lineups, new AuditService($pdo));
        $access = new MatchOperationAccessService($operations, $schedules, $authorization);
        $homeLineup = $lineups->find($matchId, (int) $match['home_team_id']);
        $awayLineup = $lineups->find($matchId, (int) $match['away_team_id']);
        $homePlayers = $homeLineup['players'];
        $awayPlayers = $awayLineup['players'];
        $homeStarters = array_values(array_filter($homePlayers, static fn (array $player): bool => $player['role'] === 'starter'));
        $homeReserves = array_values(array_filter($homePlayers, static fn (array $player): bool => $player['role'] === 'reserve'));
        $awayStarters = array_values(array_filter($awayPlayers, static fn (array $player): bool => $player['role'] === 'starter'));
        $homeStarter = (int) $homeStarters[0]['athlete_id'];
        $homeSecondStarter = (int) $homeStarters[1]['athlete_id'];
        $homeReserve = (int) $homeReserves[0]['athlete_id'];
        $awayStarter = (int) $awayStarters[0]['athlete_id'];
        $operation = $service->ensure($match, (int) $operator['id']);
        assert_same('open', $operation['status'], 'Operacao nao iniciou aberta');

        assert_true($service->addEvent($operator, $match, ['event_type' => 'goal', 'period' => 'regular', 'team_id' => $match['home_team_id'], 'athlete_id' => $homeStarter])['ok'], 'Gol valido falhou');
        assert_true($service->addEvent($operator, $match, ['event_type' => 'own_goal', 'period' => 'regular', 'team_id' => $match['home_team_id'], 'athlete_id' => $awayStarter])['ok'], 'Gol contra valido falhou');
        assert_true($service->addEvent($operator, $match, ['event_type' => 'assist', 'period' => 'regular', 'team_id' => $match['home_team_id'], 'athlete_id' => $homeSecondStarter, 'related_athlete_id' => $homeStarter])['ok'], 'Assistencia valida falhou');
        foreach (['yellow', 'second_yellow', 'red'] as $type) assert_true($service->addEvent($operator, $match, ['event_type' => $type, 'period' => 'regular', 'team_id' => $match['home_team_id'], 'athlete_id' => $homeStarter])['ok'], 'Registro disciplinar falhou: ' . $type);
        assert_true($service->addEvent($operator, $match, ['event_type' => 'occurrence', 'period' => 'other', 'notes' => 'Ocorrencia administrativa'])['ok'], 'Ocorrencia falhou');
        assert_true($service->addEvent($operator, $match, ['event_type' => 'penalty_scored', 'period' => 'penalties', 'team_id' => $match['home_team_id'], 'athlete_id' => $homeStarter])['ok'], 'Penalti convertido falhou');
        assert_true($service->addEvent($operator, $match, ['event_type' => 'penalty_missed', 'period' => 'penalties', 'team_id' => $match['away_team_id'], 'athlete_id' => $awayStarter])['ok'], 'Penalti perdido falhou');
        assert_true(!$service->addEvent($operator, $match, ['event_type' => 'penalty_scored', 'period' => 'regular', 'team_id' => $match['home_team_id'], 'athlete_id' => $homeStarter])['ok'], 'Penalti entrou no tempo normal');
        assert_true(!$service->addEvent($operator, $match, ['event_type' => 'goal', 'period' => 'regular', 'team_id' => 999999, 'athlete_id' => $homeStarter])['ok'], 'Evento de equipe fora da partida aceito');
        $score = $operations->score($operation);
        assert_same(2, $score['home_score'], 'Gol contra nao foi atribuido ao time que marcou');
        assert_same(0, $score['away_score'], 'Placar visitante incorreto');
        assert_same(1, $score['home_penalties'], 'Penalti nao foi separado do placar normal');
        assert_same(0, $score['away_penalties'], 'Penalti perdido entrou no placar');

        $firstSubstitution = $service->addSubstitution($operator, $match, ['team_id' => $match['home_team_id'], 'athlete_out_id' => $homeStarter, 'athlete_in_id' => $homeReserve, 'period' => 'regular', 'window_number' => 1]);
        assert_true($firstSubstitution['ok'], 'Substituicao valida falhou: ' . implode(' | ', $firstSubstitution['errors'] ?? []));
        assert_true(!$service->addSubstitution($operator, $match, ['team_id' => $match['home_team_id'], 'athlete_out_id' => $homeStarter, 'athlete_in_id' => $homeReserve, 'period' => 'regular', 'window_number' => 0])['ok'], 'Janela invalida aceita');
        for ($index = 0; $index < 4; $index++) assert_true($service->addSubstitution($operator, $match, ['team_id' => $match['home_team_id'], 'athlete_out_id' => $homeStarter, 'athlete_in_id' => $homeReserve, 'period' => 'regular', 'window_number' => 1])['ok'], 'Substituicao dentro do limite falhou');
        assert_true(!$service->addSubstitution($operator, $match, ['team_id' => $match['home_team_id'], 'athlete_out_id' => $homeStarter, 'athlete_in_id' => $homeReserve, 'period' => 'regular', 'window_number' => 1])['ok'], 'Limite de substituicoes nao foi aplicado');

        assert_true($service->saveOfficials($operator, $match, ['referee' => 'Arbitro Teste', 'assistant_1' => 'Assistente A', 'assistant_2' => 'Assistente B', 'scorekeeper' => 'Mesario'])['ok'], 'Arbitragem nao foi salva');
        assert_true($service->saveTimes($operator, $match, ['first_half_started_at' => '2026-07-28T10:00', 'first_half_ended_at' => '2026-07-28T10:45', 'second_half_started_at' => '2026-07-28T11:00', 'second_half_ended_at' => '2026-07-28T11:50'])['ok'], 'Horarios nao foram salvos');
        assert_true($service->saveAdministrativeResult($operator, $match, ['home_score' => 3, 'away_score' => 1, 'reason' => 'Decisao disciplinar'])['ok'], 'Resultado administrativo valido falhou');
        assert_same(3, $operations->score($operations->find($matchId))['home_score'], 'Resultado administrativo nao substituiu o placar');
        assert_true(!$service->finish($operator, $match, false)['ok'], 'Finalizacao sem confirmacao aceita');
        assert_true($service->finish($operator, $match, true)['ok'], 'Finalizacao com checklist pronto falhou');
        $finished = $operations->find($matchId);
        assert_same('awaiting_homologation', $finished['status'], 'Operacao nao aguardou homologacao');
        assert_same('finished', $finished['match_status'], 'Partida nao foi finalizada');
        assert_true(!$service->addEvent($operator, $match, ['event_type' => 'occurrence', 'period' => 'other'])['ok'], 'Evento aceito apos finalizacao');
        assert_true(!$access->canOperate($operator, $match), 'Operador manteve edicao apos finalizacao');
        assert_true(!$access->canHomologate($operator, $match), 'Operador recebeu permissao de homologar');
        assert_true($service->saveAdministrativeResult($operator, $match, ['home_score' => 4, 'away_score' => 2, 'reason' => 'Retificacao tardia'])['ok'] === false, 'Resultado administrativo alterou partida finalizada');
        assert_true(!$service->homologate($admin, $match, false)['ok'], 'Homologacao sem confirmacao aceita');
        assert_true($service->homologate($admin, $match, true)['ok'], 'Homologacao valida falhou');
        $homologated = $operations->find($matchId);
        assert_same('homologated', $homologated['status'], 'Operacao nao foi homologada');
        assert_same('homologated', $homologated['match_status'], 'Partida nao foi homologada');
        assert_same(2, (int) $pdo->query('SELECT COUNT(*) FROM match_operation_history')->fetchColumn(), 'Historico de transicao inconsistente');
        $foreignMatchId = (int) $pdo->query('SELECT m.id FROM matches m WHERE m.id <> ' . $matchId . ' AND NOT EXISTS (SELECT 1 FROM match_operator_assignments moa WHERE moa.match_id = m.id) ORDER BY m.id LIMIT 1')->fetchColumn();
        assert_true($foreignMatchId > 0 && $access->matchForUser($operator, $foreignMatchId) === null, 'IDOR do operador foi aceito');
    }
}
