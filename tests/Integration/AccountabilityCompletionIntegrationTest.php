<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Repositories\AccountabilityRepository;
use App\Repositories\UserRepository;
use App\Services\AccountabilityExportService;
use App\Services\AuditService;
use App\Services\StorageService;
use function Tests\assert_same;
use function Tests\assert_true;

final class AccountabilityCompletionIntegrationTest
{
    public static function run(): void
    {
        $pdo = Database::connection();
        $championshipId = (int) $pdo->query("SELECT id FROM championships WHERE slug = 'copa-brasil-de-talentos-2026' LIMIT 1")->fetchColumn();
        $admin = (new UserRepository($pdo))->findByEmail('admin@torneios.local');
        assert_true($championshipId > 0 && (bool) $admin, 'Fixture de prestação de contas ausente');

        $repository = new AccountabilityRepository($pdo);
        $repository->saveSettings($championshipId, (int) $admin['id'], ['require_current_report' => 1, 'require_signed_report' => 0, 'require_approved_evidence' => 0]);
        assert_same(1, (int) $repository->settings($championshipId)['require_current_report'], 'Configuração documental não foi salva');

        $exports = new AccountabilityExportService($repository, new StorageService(), new AuditService($pdo));
        foreach (['csv' => 'text/csv', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'pdf' => 'application/pdf', 'zip' => 'application/zip'] as $format => $mime) {
            $file = $exports->generate($championshipId, $format, [], (int) $admin['id']);
            assert_same($mime, strtok($file['mime'], ';'), 'MIME incorreto no exportador de prestação: ' . $format);
            assert_true($file['body'] !== '' && strlen($file['hash']) === 64, 'Arquivo de prestação vazio ou sem hash: ' . $format);
            if ($format === 'pdf') assert_true(str_starts_with($file['body'], '%PDF-1.4'), 'PDF de prestação não é um PDF real');
            if (in_array($format, ['xlsx', 'zip'], true)) assert_true(str_starts_with($file['body'], 'PK'), 'Pacote binário de prestação inválido: ' . $format);
        }
        assert_true((int) $pdo->query('SELECT COUNT(*) FROM accountability_export_logs')->fetchColumn() >= 4, 'Exportações não foram registradas');
        foreach ($repository->matches($championshipId) as $match) assert_same('homologated', (string) $pdo->query('SELECT status FROM matches WHERE id = ' . (int) $match['id'])->fetchColumn(), 'Prestação incluiu partida não aprovada');
    }
}
