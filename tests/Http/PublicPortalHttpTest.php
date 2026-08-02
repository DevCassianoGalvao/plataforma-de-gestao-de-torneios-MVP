<?php
declare(strict_types=1);

namespace Tests\Http;

use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Database\NewsSeed;
use App\Database\PortalEngagementSeed;
use App\Database\ScheduleSeed;
use App\Database\TransferSeed;
use function Tests\assert_same;
use function Tests\assert_true;

final class PublicPortalHttpTest
{
    public static function run(): void
    {
        $pdo = Database::connection();
        ScheduleSeed::run($pdo);
        NewsSeed::run($pdo);
        TransferSeed::run($pdo);
        PortalEngagementSeed::run($pdo);
        /** @var Router $router */
        $router = require dirname(__DIR__, 2) . '/routes/web.php';
        $slug = 'copa-brasil-de-talentos-2026';
        $base = '/torneio-online/campeonatos/' . $slug;
        $paths = ['', '/proximos-jogos', '/resultados', '/classificacao', '/mata-mata', '/equipes', '/atletas', '/artilharia', '/assistencias', '/cartoes', '/regulamento', '/campeao', '/noticias', '/vai-e-vem', '/arbitragem', '/contato'];
        foreach ($paths as $path) {
            $response = $router->dispatch(Request::fake('GET', $base . $path));
            assert_same(200, $response->status, 'Rota publica falhou: ' . $path);
            assert_true(!str_contains(strtolower($response->body), 'private_notes'), 'Campo privado vazou na rota: ' . $path);
            assert_true(!str_contains(strtolower($response->body), 'internal_notes'), 'Campo interno vazou na rota: ' . $path);
        }
        $groups = $router->dispatch(Request::fake('GET', $base . '/grupos'));
        assert_same(302, $groups->status, 'A rota legada de grupos deve redirecionar para a classificação.');

        $teamSlug = (string) $pdo->query("SELECT slug FROM teams WHERE championship_id = (SELECT id FROM championships WHERE slug = '{$slug}') AND deleted_at IS NULL ORDER BY id LIMIT 1")->fetchColumn();
        $athleteId = (int) $pdo->query("SELECT a.id FROM athletes a INNER JOIN teams t ON t.id = a.team_id WHERE t.championship_id = (SELECT id FROM championships WHERE slug = '{$slug}') AND a.deleted_at IS NULL ORDER BY a.id LIMIT 1")->fetchColumn();
        $matchId = (int) $pdo->query("SELECT id FROM matches WHERE championship_id = (SELECT id FROM championships WHERE slug = '{$slug}') AND status <> 'cancelled' ORDER BY id LIMIT 1")->fetchColumn();
        foreach (['/equipes/' . $teamSlug, '/atletas/' . $athleteId, '/partidas/' . $matchId] as $path) {
            assert_same(200, $router->dispatch(Request::fake('GET', $base . $path))->status, 'Detalhe publico falhou: ' . $path);
        }
        $publicMatch = $router->dispatch(Request::fake('GET', $base . '/partidas/' . $matchId));
        assert_true(str_contains($publicMatch->body, 'Escala&ccedil;&otilde;es da partida') && str_contains($publicMatch->body, 'football-field.svg'), 'Campo tático não foi carregado no registro público da partida.');

        assert_true(str_contains($publicMatch->body, 'assets/branding/favicon.png') && str_contains($publicMatch->body, 'torneio-online-web-app.png') && str_contains($publicMatch->body, 'Torneio Online Web App'), 'Marca da plataforma nao foi aplicada ao portal publico.');

        $home = $router->dispatch(Request::fake('GET', $base));
        assert_true(str_contains($home->body, 'canonical') && str_contains($home->body, 'og:title'), 'SEO basico ausente na home publica');
        assert_true(str_contains($home->body, 'style="--portal-primary:') && str_contains($home->body, '--portal-secondary:') && str_contains($home->body, '--portal-accent:'), 'Cores da identidade nao foram aplicadas diretamente no portal.');
        assert_true(str_contains($home->body, 'https://www.cassianogalvao.com.br/torneio-online/campeonatos/' . $slug), 'Canonical nao usa o dominio final');
        assert_true(str_contains($home->body, 'id="portal-navigation"') && str_contains($home->body, 'data-portal-nav-dismiss'), 'Navegacao publica movel nao possui dialogo descartavel.');
        $sitemap = $router->dispatch(Request::fake('GET', '/sitemap.xml'));
        assert_same(200, $sitemap->status, 'Sitemap publico falhou');
        assert_true(str_contains($sitemap->body, 'https://www.cassianogalvao.com.br/torneio-online/campeonatos/' . $slug), 'Sitemap nao usa o dominio final');
        $robots = $router->dispatch(Request::fake('GET', '/robots.txt'));
        assert_same(200, $robots->status, 'Robots publico falhou');
        assert_true(str_contains($robots->body, 'https://www.cassianogalvao.com.br/torneio-online/sitemap.xml'), 'Robots nao referencia sitemap absoluto');
        assert_same(404, $router->dispatch(Request::fake('GET', '/torneio-online/campeonatos/nao-existe'))->status, '404 publico nao foi retornado');
    }
}
