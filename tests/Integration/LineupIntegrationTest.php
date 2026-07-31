<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Database\LineupSeed;
use App\Repositories\ChampionshipRepository;
use App\Repositories\LineupRepository;
use App\Repositories\ScheduleRepository;
use App\Repositories\TacticalFormationRepository;
use App\Repositories\TeamRepository;
use App\Repositories\UserRepository;
use App\Services\AuditService;
use App\Services\AuthorizationService;
use App\Services\LineupAccessService;
use App\Services\LineupService;
use function Tests\assert_same;
use function Tests\assert_true;

final class LineupIntegrationTest
{
    public static function run(): void
    {
        $pdo = Database::connection();
        LineupSeed::run($pdo);
        LineupSeed::run($pdo);
        assert_same(120, (int) $pdo->query("SELECT COUNT(*) FROM athlete_registrations WHERE observations LIKE 'Registro aprovado ficticio para Etapa 8.'")->fetchColumn(), 'Seed de elenco elegivel duplicou registros');

        $users = new UserRepository($pdo);
        $admin = $users->findByEmail('admin@torneios.local');
        $trainer = $users->findByEmail('treinador@torneios.local');
        $operator = $users->findByEmail('operador@torneios.local');
        $teamId = (int) $pdo->query("SELECT id FROM teams WHERE slug = 'estrela-norte-fc'")->fetchColumn();
        $foreignTeamId = (int) $pdo->query("SELECT id FROM teams WHERE slug = 'serra-azul-futebol'")->fetchColumn();
        $match = (new ScheduleRepository($pdo))->matchById((int) $pdo->query('SELECT id FROM matches WHERE home_team_id = ' . $teamId . ' OR away_team_id = ' . $teamId . ' ORDER BY id LIMIT 1')->fetchColumn());
        $formationId = (int) $pdo->query("SELECT id FROM tactical_formations WHERE slug = '4-4-2'")->fetchColumn();
        $lineups = new LineupRepository($pdo);
        $access = new LineupAccessService($lineups, new ScheduleRepository($pdo), new TeamRepository($pdo), new AuthorizationService($users));
        $service = new LineupService($lineups, new TacticalFormationRepository($pdo), new TeamRepository($pdo), new AuthorizationService($users), new AuditService($pdo));

        $draft = $service->ensureDraft($admin, $match, $teamId);
        $suggestion = $service->suggest($match, $teamId, $formationId);
        assert_true($suggestion['ok'] && count($suggestion['starters']) === 11 && count($suggestion['reserves']) >= 1, 'Distribuicao automatica nao preencheu titulares e reservas');
        $firstSlots = array_keys($suggestion['starters']);
        $first = $suggestion['starters'][$firstSlots[1]];
        $second = $suggestion['starters'][$firstSlots[9]];
        $suggestion['starters'][$firstSlots[1]] = $second;
        $suggestion['starters'][$firstSlots[9]] = $first;
        $suggestion['staff_ids'] = array_map(static fn (array $member): int => (int) $member['id'], $lineups->staff($teamId));
        $saved = $service->save($admin, $match, $draft, array_merge(['formation_id' => $formationId], $suggestion), false);
        assert_true($saved['ok'], 'Rascunho de escalacao nao foi salvo');
        $draft = $lineups->find((int) $match['id'], $teamId);
        $confirmed = $service->save($admin, $match, $draft, array_merge(['formation_id' => $formationId], $suggestion), true);
        assert_true($confirmed['ok'], 'Escalacao valida nao foi confirmada');
        $lineup = $lineups->find((int) $match['id'], $teamId);
        assert_same('confirmed', $lineup['status'], 'Status confirmado ausente');
        assert_same(11, count(array_filter($lineup['players'], static fn (array $player): bool => $player['role'] === 'starter')), 'Escalacao nao possui onze titulares');
        assert_true(count(array_filter($lineup['players'], static fn (array $player): bool => $player['role'] === 'reserve')) >= 1, 'Reservas ausentes');
        assert_true(count(array_filter($lineup['players'], static fn (array $player): bool => (int) $player['is_out_of_position'] === 1)) >= 1, 'Ajuste fora de posicao nao foi marcado');
        assert_true(count($lineups->history((int) $lineup['id'])) >= 3, 'Historico minimo nao foi registrado');
        assert_true(!$service->save($admin, $match, $lineup, array_merge(['formation_id' => $formationId], $suggestion), false)['ok'], 'Escalacao confirmada aceitou edicao comum');

        $nextMatch = (new ScheduleRepository($pdo))->matchById((int) $pdo->query('SELECT id FROM matches WHERE (home_team_id = ' . $teamId . ' OR away_team_id = ' . $teamId . ') AND id <> ' . (int) $match['id'] . ' ORDER BY id DESC LIMIT 1')->fetchColumn());
        $copied = $service->reuseLatestConfirmed($admin, $nextMatch, $teamId);
        assert_true($copied['ok'], 'Última escalação confirmada não foi copiada para o próximo jogo');
        $copiedLineup = $lineups->find((int) $nextMatch['id'], $teamId);
        assert_same('draft', $copiedLineup['status'], 'Escalação reutilizada precisa permanecer em rascunho');
        assert_same(11, count(array_filter($copiedLineup['players'], static fn (array $player): bool => $player['role'] === 'starter')), 'Titulares não foram copiados para o próximo jogo');

        $draftAgain = $service->reopen($admin, $match, $lineup, 'Ajuste autorizado');
        assert_true($draftAgain['ok'], 'Reabertura autorizada falhou');
        $reopened = $lineups->find((int) $match['id'], $teamId);
        assert_same('draft', $reopened['status'], 'Reabertura nao voltou para rascunho');
        $foreignAthlete = (int) $pdo->query('SELECT athlete_id FROM athlete_registrations WHERE team_id = ' . $foreignTeamId . " AND status = 'approved' LIMIT 1")->fetchColumn();
        $invalid = $suggestion;
        $invalid['starters'][$firstSlots[0]] = $foreignAthlete;
        assert_true(!$service->save($admin, $match, $reopened, array_merge(['formation_id' => $formationId], $invalid), false)['ok'], 'Atleta de outra equipe foi aceito');
        $duplicate = $suggestion;
        $duplicate['starters'][$firstSlots[2]] = $duplicate['starters'][$firstSlots[1]];
        assert_true(!$service->save($admin, $match, $reopened, array_merge(['formation_id' => $formationId], $duplicate), false)['ok'], 'Atleta duplicado foi aceito');
        assert_true($access->canManageTeam($trainer, $match, $teamId), 'Treinador nao gerencia propria equipe');
        assert_true(!$access->canManageTeam($operator, $match, $teamId), 'Operador recebeu edicao de escalacao');
        assert_true($access->canView($operator, $match, $lineup), 'Operador nao visualiza escalacao confirmada');
        assert_true($access->matchForUser($trainer, (int) $pdo->query('SELECT id FROM matches WHERE home_team_id = ' . $foreignTeamId . ' AND away_team_id <> ' . $teamId . ' LIMIT 1')->fetchColumn()) === null, 'IDOR de partida de outra equipe aceito');
    }
}
