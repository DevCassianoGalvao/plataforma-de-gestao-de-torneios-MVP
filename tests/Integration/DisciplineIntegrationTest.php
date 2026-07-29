<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Database\DisciplineSeed;
use App\Repositories\DisciplineRepository;
use App\Repositories\LineupRepository;
use App\Repositories\TacticalFormationRepository;
use App\Repositories\TeamRepository;
use App\Repositories\UserRepository;
use App\Services\AuditService;
use App\Services\DisciplineService;
use App\Services\AuthorizationService;
use App\Services\LineupService;
use function Tests\assert_same;
use function Tests\assert_true;

final class DisciplineIntegrationTest
{
    public static function run(): void
    {
        $pdo = Database::connection();
        DisciplineSeed::run($pdo);
        DisciplineSeed::run($pdo);
        assert_same(2, (int) $pdo->query('SELECT COUNT(*) FROM discipline_ledger WHERE source IN (\'seed\')')->fetchColumn(), 'Seed disciplinar duplicou ledger');
        assert_same(3, (int) $pdo->query('SELECT COUNT(*) FROM discipline_suspensions WHERE source_key LIKE \'seed:%\'')->fetchColumn(), 'Seed disciplinar duplicou suspensoes');

        $users = new UserRepository($pdo);
        $admin = $users->findByEmail('admin@torneios.local');
        $match = $pdo->query('SELECT * FROM matches ORDER BY id LIMIT 1')->fetch();
        $discipline = new DisciplineRepository($pdo);
        $service = new DisciplineService($discipline, new AuditService($pdo));
        $before = (int) $pdo->query('SELECT COUNT(*) FROM discipline_ledger')->fetchColumn();
        if ($match['status'] === 'homologated') {
            assert_true($service->processHomologatedMatch($match, (int) $admin['id'])['ok'], 'Processamento disciplinar falhou');
            assert_true($service->processHomologatedMatch($match, (int) $admin['id'])['ok'], 'Processamento disciplinar nao foi idempotente');
            assert_true((int) $pdo->query('SELECT COUNT(*) FROM discipline_ledger')->fetchColumn() >= $before, 'Ledger disciplinar desapareceu');
            assert_true((int) $pdo->query('SELECT COUNT(*) FROM discipline_processing_runs')->fetchColumn() >= 1, 'Execucao disciplinar nao foi registrada');
            $redEvent = (int) $pdo->query("SELECT id FROM match_operation_events WHERE match_id = {$match['id']} AND event_type = 'red' ORDER BY id DESC LIMIT 1")->fetchColumn();
            assert_true($service->cancelCard($admin, $redEvent, 'Revisao disciplinar de teste')['ok'], 'Anulacao de cartao falhou');
        }
        $teamId = (int) $pdo->query('SELECT id FROM teams ORDER BY id LIMIT 1')->fetchColumn();
        $athleteId = (int) $pdo->query('SELECT id FROM athletes WHERE team_id = ' . $teamId . ' ORDER BY id LIMIT 1')->fetchColumn();
        $manual = $service->createManual($admin, ['championship_id' => $match['championship_id'], 'team_id' => $teamId, 'person_type' => 'athlete', 'person_id' => $athleteId, 'total_matches' => 2, 'notes' => 'Teste manual']);
        assert_true($manual['ok'], 'Suspensao manual nao foi criada');
        assert_true($discipline->revokeSuspension((int) $manual['id'], (int) $admin['id'], 'Teste de revogacao'), 'Revogacao nao preservou transicao');
        assert_same('revoked', $discipline->suspension((int) $manual['id'])['status'], 'Suspensao nao foi revogada');
        assert_true((int) $pdo->query("SELECT COUNT(*) FROM discipline_history WHERE action IN ('manual_suspension_created', 'suspension_revoked')")->fetchColumn() >= 2, 'Historico disciplinar incompleto');

        $future = $pdo->query("SELECT * FROM matches WHERE status IN ('scheduled', 'confirmed', 'postponed') ORDER BY id LIMIT 1")->fetch();
        if ($future) {
            $lineups = new LineupRepository($pdo);
            $formations = new TacticalFormationRepository($pdo);
            $lineupService = new LineupService($lineups, $formations, new TeamRepository($pdo), new AuthorizationService($users), new AuditService($pdo), $service);
            $teamId = (int) $future['home_team_id'];
            $formationId = (int) $pdo->query("SELECT id FROM tactical_formations WHERE slug = '4-4-2' LIMIT 1")->fetchColumn();
            $draft = $lineupService->ensureDraft($admin, $future, $teamId);
            $suggestion = $lineupService->suggest($future, $teamId, $formationId);
            $starterSlots = $suggestion['starters'];
            $starter = (int) reset($starterSlots);
            $blockedSuspension = $service->createManual($admin, ['championship_id' => $future['championship_id'], 'team_id' => $teamId, 'person_type' => 'athlete', 'person_id' => $starter, 'total_matches' => 1, 'notes' => 'Bloqueio de escalação']);
            assert_true($blockedSuspension['ok'], 'Suspensao para bloqueio nao foi criada');
            $suggestion['staff_ids'] = array_map(static fn (array $member): int => (int) $member['id'], $lineups->staff($teamId));
            $blocked = $lineupService->save($admin, $future, $draft, array_merge(['formation_id' => $formationId], $suggestion), true);
            assert_true(!$blocked['ok'], 'Atleta suspenso foi confirmado na escalação');
        }
    }
}
