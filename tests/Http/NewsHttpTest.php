<?php
declare(strict_types=1);

namespace Tests\Http;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Core\Security;
use App\Core\Session;
use App\Database\NewsSeed;
use function Tests\assert_same;
use function Tests\assert_true;

final class NewsHttpTest
{
    public static function run(): void
    {
        $pdo = Database::connection(); NewsSeed::run($pdo); /** @var Router $router */ $router = require dirname(__DIR__, 2) . '/routes/web.php'; $championshipSlug = 'copa-brasil-de-talentos-2026'; $championshipId = (int) $pdo->query("SELECT id FROM championships WHERE slug = '{$championshipSlug}'")->fetchColumn();
        Session::destroy(); self::login($router, 'comunicacao@torneios.local'); assert_same(200, $router->dispatch(Request::fake('GET', '/copa-online/admin/noticias'))->status, 'Comunicacao nao abriu noticias');
        $temporary = tempnam(sys_get_temp_dir(), 'news-http-'); file_put_contents($temporary, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
        $body = ['_csrf' => Security::csrfToken(), 'championship_id' => $championshipId, 'title' => '<script>Noticia segura</script>', 'slug' => 'noticia-http-segura', 'summary' => 'Resumo HTTP', 'content' => '<script>alert(1)</script>', 'status' => 'draft', 'featured' => '1']; $created = $router->dispatch(Request::fake('POST', '/copa-online/admin/noticias', $body, ['cover_image' => ['error' => UPLOAD_ERR_OK, 'size' => filesize($temporary), 'tmp_name' => $temporary, 'name' => 'capa.png']])); assert_same(302, $created->status, 'Comunicacao nao criou noticia');
        $newsId = (int) $pdo->query("SELECT id FROM news_articles WHERE slug = 'noticia-http-segura'")->fetchColumn(); $path = (string) $pdo->query("SELECT cover_image_path FROM news_articles WHERE id = {$newsId}")->fetchColumn(); assert_true($newsId > 0 && str_ends_with($path, '.jpg'), 'Capa nao foi otimizada para JPEG'); @unlink($temporary);
        assert_same(404, $router->dispatch(Request::fake('GET', '/copa-online/campeonatos/' . $championshipSlug . '/noticias/noticia-http-segura'))->status, 'Rascunho apareceu no portal');
        assert_same(403, $router->dispatch(Request::fake('POST', '/copa-online/admin/noticias/' . $newsId . '/publicar', ['_csrf' => 'invalid']))->status, 'CSRF de noticia foi aceito');
        $publish = $router->dispatch(Request::fake('POST', '/copa-online/admin/noticias/' . $newsId . '/publicar', ['_csrf' => Security::csrfToken()])); assert_same(302, $publish->status, 'Publicacao de noticia falhou');
        $public = $router->dispatch(Request::fake('GET', '/copa-online/campeonatos/' . $championshipSlug . '/noticias/noticia-http-segura')); assert_same(200, $public->status, 'Detalhe publico de noticia nao abriu'); assert_true(str_contains($public->body, '&lt;script&gt;') && !str_contains($public->body, '<script>alert'), 'Conteudo de noticia permitiu XSS'); assert_same(200, $router->dispatch(Request::fake('GET', '/copa-online/campeonatos/' . $championshipSlug . '/noticias/noticia-http-segura/capa'))->status, 'Capa publica nao abriu');
        assert_same(200, $router->dispatch(new Request('GET', '/copa-online/campeonatos/' . $championshipSlug . '/noticias', ['q' => 'HTTP']))->status, 'Busca publica nao abriu'); assert_same(200, $router->dispatch(Request::fake('GET', '/copa-online/admin/noticias/' . $newsId . '/preview'))->status, 'Comunicacao autorizada nao abriu a previa');
        self::logout($router); self::login($router, 'treinador@torneios.local'); assert_same(403, $router->dispatch(Request::fake('GET', '/copa-online/admin/noticias'))->status, 'Treinador acessou noticias'); self::logout($router); self::login($router, 'organizador-sem-acesso@torneios.local'); assert_same(403, $router->dispatch(Request::fake('GET', '/copa-online/admin/noticias/' . $newsId))->status, 'IDOR editorial foi aceito'); self::logout($router);
        self::login($router, 'comunicacao@torneios.local'); $archived = $router->dispatch(Request::fake('POST', '/copa-online/admin/noticias/' . $newsId . '/excluir', ['_csrf' => Security::csrfToken()])); assert_same(302, $archived->status, 'Exclusao logica HTTP falhou'); assert_same(404, $router->dispatch(Request::fake('GET', '/copa-online/campeonatos/' . $championshipSlug . '/noticias/noticia-http-segura'))->status, 'Noticia arquivada continuou publica'); (new \App\Services\StorageService())->delete($path); self::logout($router);
    }

    private static function login(Router $router, string $email): void { $response = $router->dispatch(Request::fake('POST', '/copa-online/login', ['_csrf' => Security::csrfToken(), 'email' => $email, 'password' => getenv('SEED_DEMO_PASSWORD') ?: 'TestDemo123'])); assert_same(302, $response->status, 'Login de noticias falhou'); assert_true(Auth::authenticated(), 'Sessao editorial nao foi criada'); }
    private static function logout(Router $router): void { $router->dispatch(Request::fake('POST', '/copa-online/logout', ['_csrf' => Security::csrfToken()])); Session::destroy(); }
}
