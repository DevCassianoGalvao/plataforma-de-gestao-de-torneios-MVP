<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Database\NewsSeed;
use App\Repositories\NewsRepository;
use App\Repositories\UserRepository;
use App\Services\AuditService;
use App\Services\AuthorizationService;
use App\Services\NewsAccessService;
use App\Services\NewsImageService;
use App\Services\NewsService;
use App\Services\StorageService;
use function Tests\assert_same;
use function Tests\assert_true;

final class NewsIntegrationTest
{
    public static function run(): void
    {
        $pdo = Database::connection(); NewsSeed::run($pdo); NewsSeed::run($pdo);
        $championshipId = (int) $pdo->query("SELECT id FROM championships WHERE slug = 'copa-brasil-de-talentos-2026'")->fetchColumn(); $count = (int) $pdo->query("SELECT COUNT(*) FROM news_articles WHERE championship_id = {$championshipId} AND deleted_at IS NULL")->fetchColumn(); assert_same(3, $count, 'Seed de noticias nao foi idempotente');
        $users = new UserRepository($pdo); $admin = $users->findByEmail('admin@torneios.local'); $communication = $users->findByEmail('comunicacao@torneios.local'); assert_true((bool) $admin && (bool) $communication, 'Usuarios editoriais ausentes');
        $repository = new NewsRepository($pdo); $access = new NewsAccessService($repository, new AuthorizationService($users)); assert_true($access->canManageChampionship($communication, $championshipId), 'Comunicacao sem escopo editorial atribuido');
        $storage = new StorageService(); $service = new NewsService($repository, new NewsImageService($storage), $storage, new AuditService($pdo));
        $result = $service->save($admin, ['championship_id' => $championshipId, 'title' => 'Noticia de integracao', 'slug' => 'noticia-integracao', 'summary' => 'Resumo', 'content' => '<script>alert(1)</script>', 'status' => 'published', 'featured' => 0], null); assert_true($result['ok'], 'CRUD de noticia falhou'); $newsId = (int) $result['id'];
        $duplicate = $service->save($admin, ['championship_id' => $championshipId, 'title' => 'Outra', 'slug' => 'noticia-integracao', 'summary' => '', 'content' => 'Conteudo', 'status' => 'draft'], null); assert_true(!$duplicate['ok'], 'Slug duplicado foi aceito');
        $article = $repository->find($newsId); assert_true($article && $article['status'] === 'published', 'Noticia publicada nao foi lida'); assert_same(2, count($repository->listPublished($championshipId)), 'Busca publica nao encontrou noticias publicadas');
        $temporary = tempnam(sys_get_temp_dir(), 'news-inline-'); file_put_contents($temporary, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
        $inline = ['error' => UPLOAD_ERR_OK, 'size' => filesize($temporary), 'tmp_name' => $temporary, 'name' => 'inline.png'];
        $withImage = $service->save($admin, ['championship_id' => $championshipId, 'title' => 'Noticia com imagem', 'slug' => 'noticia-com-imagem', 'summary' => 'Resumo', 'content' => "Primeiro paragrafo\n[[imagem]]\nUltimo paragrafo", 'status' => 'draft'], null, null, $inline); assert_true($withImage['ok'], 'Imagem no conteudo nao foi salva');
        $imageArticle = $repository->find((int) $withImage['id']); assert_true($imageArticle !== null && preg_match('/\[\[imagem:news\/content\/[a-f0-9]{32}\.webp\]\]/', (string) $imageArticle['content']) === 1, 'Marcador da imagem no conteudo nao foi persistido');
        if ($imageArticle && preg_match('/\[\[imagem:(news\/content\/[^\]]+)\]\]/', (string) $imageArticle['content'], $match) === 1) $storage->delete($match[1]); @unlink($temporary);
        $service->delete($newsId, (int) $admin['id']); assert_true($repository->find($newsId) === null, 'Exclusao logica nao ocultou noticia');
    }
}
