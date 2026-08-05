<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Repositories\BackupRepository;
use App\Repositories\UserRepository;
use App\Services\AuditService;
use App\Services\BackupService;
use function Tests\assert_true;

final class BackupIntegrationTest
{
    public static function run(): void
    {
        $pdo = Database::connection();
        $admin = (new UserRepository($pdo))->findByEmail('admin@torneios.local');
        assert_true((bool) $admin, 'Administrador de teste ausente para backup');

        $root = dirname(__DIR__, 2);
        $directory = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'backups';
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new \RuntimeException('Diretorio de teste de backup ausente');
        }

        $repository = new BackupRepository($pdo);
        $service = new BackupService($repository, new AuditService($pdo));
        $now = date('Y-m-d H:i:s');
        $key = 'backup-delete-test-' . bin2hex(random_bytes(4));
        $filename = $key . '.zip';
        $absolutePath = $directory . DIRECTORY_SEPARATOR . $filename;
        file_put_contents($absolutePath, 'backup fixture');

        $insert = $pdo->prepare('INSERT INTO application_backups (backup_key, type, status, local_status, validation_status, remote_status, local_path, size_bytes, created_by, started_at, completed_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $insert->execute([$key, 'manual', 'completed', 'completed', 'valid', 'not_configured', 'storage/backups/' . $filename, 14, (int) $admin['id'], $now, $now, $now, $now]);
        $backupId = (int) $pdo->lastInsertId();

        try {
            $service->delete($backupId, (int) $admin['id']);
            assert_true(!is_file($absolutePath), 'Arquivo físico do backup não foi removido');
            assert_true($repository->find($backupId) === null, 'Backup excluído ainda apareceu na listagem ativa');
            assert_true((bool) $pdo->query('SELECT deleted_at FROM application_backups WHERE id = ' . $backupId)->fetchColumn(), 'Exclusão lógica não foi registrada');
            assert_true((bool) $pdo->query("SELECT id FROM audit_logs WHERE action = 'backup.deleted' AND resource_id = '" . $backupId . "' ORDER BY id DESC LIMIT 1")->fetchColumn(), 'Exclusão de backup não foi auditada');
        } finally {
            @unlink($absolutePath);
            $pdo->prepare('DELETE FROM audit_logs WHERE action = ? AND resource_id = ?')->execute(['backup.deleted', (string) $backupId]);
            $pdo->prepare('DELETE FROM application_backups WHERE id = ?')->execute([$backupId]);
        }
    }
}
