<?php
declare(strict_types=1);

namespace App\Database;

use PDO;

final class NewsSeed
{
    public static function run(PDO $pdo): void
    {
        if (getenv('APP_ENV') === 'production') throw new \RuntimeException('Seed de demonstracao bloqueado em producao.');
        $championshipId = (int) $pdo->query("SELECT id FROM championships WHERE slug = 'copa-brasil-de-talentos-2026' AND deleted_at IS NULL LIMIT 1")->fetchColumn();
        $adminId = (int) $pdo->query("SELECT id FROM users WHERE email = 'admin@torneios.local' LIMIT 1")->fetchColumn();
        $communicationId = (int) $pdo->query("SELECT id FROM users WHERE email = 'comunicacao@torneios.local' LIMIT 1")->fetchColumn();
        if (!$championshipId || !$adminId || !$communicationId) throw new \RuntimeException('Usuarios ou campeonato necessarios ao seed de noticias nao encontrados.');
        $now = date('Y-m-d H:i:s');
        $pdo->prepare("UPDATE championships SET visibility = 'public', updated_at = ? WHERE id = ?")->execute([$now, $championshipId]);
        $pdo->prepare('INSERT IGNORE INTO championship_user_assignments (championship_id, user_id, assignment_type, created_at, created_by) VALUES (?, ?, ?, ?, ?)')->execute([$championshipId, $communicationId, 'communication', $now, $adminId]);
        $articles = [
            ['Abertura da Copa Brasil de Talentos', 'abertura-da-copa-brasil-de-talentos', 'A temporada comeca com oito equipes e muita expectativa.', "A Copa Brasil de Talentos esta pronta para a bola rolar.\n\nA organizacao divulgou os primeiros detalhes da temporada e reforcou o compromisso com uma competicao segura.", 'published', 1, date('Y-m-d H:i:s', strtotime('-2 days'))],
            ['Agenda editorial da proxima rodada', 'agenda-editorial-da-proxima-rodada', 'Confira o que sera publicado nos proximos dias.', 'Texto de demonstracao para uma noticia agendada.', 'scheduled', 0, date('Y-m-d H:i:s', strtotime('+2 days'))],
            ['Bastidores da organizacao', 'bastidores-da-organizacao', 'Rascunho editorial para revisao interna.', 'Este texto ainda nao esta disponivel no portal publico.', 'draft', 0, null],
        ];
        $statement = $pdo->prepare('INSERT INTO news_articles (championship_id, author_id, title, slug, summary, content, status, featured, published_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE title = VALUES(title), summary = VALUES(summary), content = VALUES(content), status = VALUES(status), featured = VALUES(featured), published_at = VALUES(published_at), updated_at = VALUES(updated_at), deleted_at = NULL');
        foreach ($articles as [$title, $slug, $summary, $content, $status, $featured, $publishedAt]) $statement->execute([$championshipId, $adminId, $title, $slug, $summary, $content, $status, $featured, $publishedAt, $now, $now]);
    }
}
