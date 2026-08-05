<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Repositories\RetentionRepository;
use App\Services\AuditService;
use App\Services\RetentionService;
use App\Repositories\UserRepository;
use function Tests\assert_same;
use function Tests\assert_true;

final class RetentionIntegrationTest
{
    public static function run(): void
    {
        $pdo = Database::connection();
        $admin = (new UserRepository($pdo))->findByEmail('admin@torneios.local');
        $championshipId = (int) $pdo->query("SELECT id FROM championships WHERE slug = 'copa-brasil-de-talentos-2026' LIMIT 1")->fetchColumn();
        assert_true((bool) $admin && $championshipId > 0, 'Fixture de retenção ausente');
        $slug = 'retencao-integracao-' . bin2hex(random_bytes(4));
        $now = date('Y-m-d H:i:s');
        $insert = $pdo->prepare('INSERT INTO news_articles (championship_id, author_id, title, slug, summary, content, status, featured, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?)');
        $insert->execute([$championshipId, (int) $admin['id'], 'Notícia de retenção', $slug, 'Teste de arquivamento', 'Conteúdo de teste', 'draft', $now, $now]);
        $articleId = (int) $pdo->lastInsertId();
        $repository = new RetentionRepository($pdo);
        $service = new RetentionService($pdo, $repository, new AuditService($pdo));
        try {
            assert_true($service->archive('noticias', $articleId, (int) $admin['id'], 'Teste de retenção.')['ok'], 'Arquivamento não foi concluído');
            assert_same('archived', (string) $pdo->query('SELECT status FROM news_articles WHERE id = ' . $articleId)->fetchColumn(), 'Registro não foi arquivado');
            assert_true($service->restore('noticias', $articleId, (int) $admin['id'], 'Teste de restauração.')['ok'], 'Restauração não foi concluída');
            assert_same('draft', (string) $pdo->query('SELECT status FROM news_articles WHERE id = ' . $articleId)->fetchColumn(), 'Status anterior não foi restaurado');
            $blocked = false;
            try { $service->archive('partidas', $articleId, (int) $admin['id'], 'Não permitido.'); } catch (\InvalidArgumentException) { $blocked = true; }
            assert_true($blocked, 'Entidade oficial não protegida contra retenção genérica');
            assert_true((int) $pdo->query("SELECT COUNT(*) FROM retention_actions WHERE entity_type = 'noticias' AND entity_id = {$articleId}")->fetchColumn() === 2, 'Ações de retenção não foram auditadas');
        } finally {
            $pdo->prepare('DELETE FROM retention_actions WHERE entity_type = ? AND entity_id = ?')->execute(['noticias', $articleId]);
            $pdo->prepare('DELETE FROM news_articles WHERE id = ?')->execute([$articleId]);
        }
    }
}
