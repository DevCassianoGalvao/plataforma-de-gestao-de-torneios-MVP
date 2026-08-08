<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Repositories\RetentionRepository;
use App\Repositories\UserRepository;
use App\Services\AuditService;
use App\Services\PermanentDeletionService;
use App\Services\StorageService;
use function Tests\assert_same;
use function Tests\assert_true;

final class PermanentDeletionIntegrationTest
{
    public static function run(): void
    {
        $pdo = Database::connection();
        $admin = (new UserRepository($pdo))->findByEmail('admin@torneios.local');
        $championshipId = (int) $pdo->query("SELECT id FROM championships WHERE slug = 'copa-brasil-de-talentos-2026' LIMIT 1")->fetchColumn();
        $positionId = (int) $pdo->query('SELECT id FROM positions ORDER BY id LIMIT 1')->fetchColumn();
        assert_true((bool) $admin && $championshipId > 0 && $positionId > 0, 'Fixture de exclusao definitiva ausente');

        $suffix = bin2hex(random_bytes(4));
        $teamIds = [];
        $athleteIds = [];
        $now = date('Y-m-d H:i:s');
        $teamInsert = $pdo->prepare('INSERT INTO teams (championship_id, name, short_name, slug, abbreviation, status, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $athleteInsert = $pdo->prepare('INSERT INTO athletes (team_id, full_name, sporting_name, birth_date, primary_position_id, status, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        for ($index = 1; $index <= 2; $index++) {
            $teamInsert->execute([$championshipId, 'Limpeza ' . $suffix . ' ' . $index, 'Limpeza ' . $index, 'limpeza-' . $suffix . '-' . $index, 'L' . $index, 'active', (int) $admin['id'], $now, $now]);
            $teamId = (int) $pdo->lastInsertId();
            $teamIds[] = $teamId;
            $athleteInsert->execute([$teamId, 'Atleta Limpeza ' . $suffix . ' ' . $index, 'Teste ' . $index, '2000-01-01', $positionId, 'active', (int) $admin['id'], $now, $now]);
            $athleteIds[] = (int) $pdo->lastInsertId();
        }

        $service = new PermanentDeletionService($pdo, new RetentionRepository($pdo), new AuditService($pdo), new StorageService());
        $preview = $service->preview('equipes', $teamIds);
        assert_true($preview['ok'] === true, 'Prévia de exclusão definitiva falhou');
        assert_true((int) $preview['total_rows'] >= 4, 'Prévia não incluiu equipes e atletas vinculados');

        $invalid = $service->purge('equipes', $teamIds, (int) $admin['id'], 'Teste de bloqueio', 'EXCLUIR');
        assert_true($invalid['ok'] === false, 'Confirmação incompleta permitiu exclusão');
        assert_same(2, (int) $pdo->query('SELECT COUNT(*) FROM teams WHERE id IN (' . implode(',', $teamIds) . ')')->fetchColumn(), 'Confirmação inválida removeu equipes');

        $result = $service->purge('equipes', $teamIds, (int) $admin['id'], 'Remoção de dados fictícios do teste.', PermanentDeletionService::CONFIRMATION);
        assert_true($result['ok'] === true, 'Exclusão definitiva em lote falhou');
        assert_same(0, (int) $pdo->query('SELECT COUNT(*) FROM teams WHERE id IN (' . implode(',', $teamIds) . ')')->fetchColumn(), 'Equipes não foram removidas definitivamente');
        assert_same(0, (int) $pdo->query('SELECT COUNT(*) FROM athletes WHERE id IN (' . implode(',', $athleteIds) . ')')->fetchColumn(), 'Atletas vinculados não foram removidos');
        assert_same(2, (int) $pdo->query("SELECT COUNT(*) FROM retention_actions WHERE entity_type = 'equipes' AND entity_id IN (" . implode(',', $teamIds) . ") AND action = 'purge'")->fetchColumn(), 'Exclusões definitivas não foram registradas');

        $pdo->exec("DELETE FROM retention_actions WHERE entity_type = 'equipes' AND entity_id IN (" . implode(',', $teamIds) . ')');
        $auditIds = $pdo->query("SELECT id FROM audit_logs WHERE action = 'retention.purged' AND resource_type = 'equipes' AND resource_id = '" . implode(',', $teamIds) . "'")->fetchAll(\PDO::FETCH_COLUMN);
        if ($auditIds !== []) {
            $pdo->exec('DELETE FROM admin_notifications WHERE audit_id IN (' . implode(',', array_map('intval', $auditIds)) . ')');
            $pdo->exec('DELETE FROM audit_logs WHERE id IN (' . implode(',', array_map('intval', $auditIds)) . ')');
        }
    }
}
