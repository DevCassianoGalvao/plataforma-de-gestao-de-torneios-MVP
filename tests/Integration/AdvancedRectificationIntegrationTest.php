<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Repositories\LineupRepository;
use App\Repositories\MatchOperationRepository;
use App\Repositories\UserRepository;
use App\Services\AuditService;
use App\Services\MatchOperationService;
use function Tests\assert_same;
use function Tests\assert_true;

final class AdvancedRectificationIntegrationTest
{
    public static function run(): void
    {
        $pdo = Database::connection();
        $admin = (new UserRepository($pdo))->findByEmail('admin@torneios.local');
        $fixture = $pdo->query("SELECT mo.id AS operation_id, m.id, m.championship_id, m.home_team_id, m.away_team_id FROM match_operations mo INNER JOIN matches m ON m.id = mo.match_id INNER JOIN match_operation_events e ON e.match_id = m.id WHERE e.valid = 1 ORDER BY mo.id LIMIT 1")->fetch();
        assert_true((bool) $admin && (bool) $fixture, 'Fixture para retificação avançada ausente');

        $matchId = (int) $fixture['id'];
        $pdo->prepare("UPDATE match_operations SET status = 'homologated', review_status = 'approved', updated_at = NOW() WHERE id = ?")->execute([(int) $fixture['operation_id']]);
        $pdo->prepare("UPDATE matches SET status = 'homologated', updated_at = NOW() WHERE id = ?")->execute([$matchId]);
        $match = ['id' => $matchId, 'championship_id' => (int) $fixture['championship_id'], 'home_team_id' => (int) $fixture['home_team_id'], 'away_team_id' => (int) $fixture['away_team_id']];
        $operations = new MatchOperationRepository($pdo);
        $service = new MatchOperationService($operations, new LineupRepository($pdo), new AuditService($pdo));
        $request = $service->requestRectification($admin, $match, 'Corrigir minuto lançado na súmula.', 'evento');
        assert_true($request['ok'], 'Retificação avançada não foi solicitada');
        $rectification = $operations->rectifications($matchId)[0] ?? null;
        assert_true((bool) $rectification && (int) $rectification['critical'] === 1 && $rectification['requested_field'] === 'evento', 'Retificação não registrou campo crítico');
        assert_true($service->decideRectification($admin, $match, (int) $rectification['id'], true, 'Correção autorizada.')['ok'], 'Retificação avançada não foi aprovada');
        $event = $operations->events($matchId)[0] ?? null;
        assert_true((bool) $event, 'Evento de teste ausente');
        $edited = $service->editRectificationEvent($admin, $match, (int) $rectification['id'], (int) $event['id'], ['field' => 'minute', 'value' => '12', 'reason' => 'Minuto confirmado pela arbitragem.']);
        assert_true($edited['ok'], 'Edição de evento em retificação falhou');
        assert_same(12, (int) $operations->eventForMatch($matchId, (int) $event['id'])['minute'], 'Evento não foi atualizado');
        assert_true(count($operations->rectificationChanges((int) $rectification['id'])) >= 1, 'Histórico de campo retificado não foi registrado');
        assert_true($service->completeRectification($admin, $match, (int) $rectification['id'])['ok'], 'Retificação não foi enviada para nova aprovação');
        assert_same('awaiting_reapproval', (string) $operations->activeRectification($matchId)['status'], 'Retificação não aguardou segunda aprovação');
        $pdo->prepare('INSERT INTO championship_rectification_settings (championship_id, require_second_approval, updated_by, created_at, updated_at) VALUES (?, 1, ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE require_second_approval = 1, updated_by = VALUES(updated_by), updated_at = NOW()')->execute([(int) $fixture['championship_id'], (int) $admin['id']]);
        $blocked = $service->homologate($admin, $match, true);
        assert_true(!$blocked['ok'], 'A mesma pessoa aprovou a própria retificação crítica');
    }
}
