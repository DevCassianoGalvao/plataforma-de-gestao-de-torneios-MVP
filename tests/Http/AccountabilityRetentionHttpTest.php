<?php
declare(strict_types=1);

namespace Tests\Http;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Core\Security;
use App\Core\Session;
use function Tests\assert_same;
use function Tests\assert_true;

final class AccountabilityRetentionHttpTest
{
    public static function run(): void
    {
        $pdo = Database::connection();
        $championshipId = (int) $pdo->query("SELECT id FROM championships WHERE slug = 'copa-brasil-de-talentos-2026' LIMIT 1")->fetchColumn();
        /** @var Router $router */
        $router = require dirname(__DIR__, 2) . '/routes/web.php';
        self::login($router, 'admin@torneios.local');
        $retention = $router->dispatch(Request::fake('GET', '/torneio-online/admin/retencao'));
        assert_same(200, $retention->status, 'Administrador não abriu retenção');
        assert_true(str_contains($retention->body, 'Retenção e arquivamento'), 'Tela de retenção sem título');
        $accountability = $router->dispatch(Request::fake('GET', '/torneio-online/prestacao/campeonatos/' . $championshipId));
        assert_same(200, $accountability->status, 'Administrador não abriu prestação detalhada');
        $matchId = (int) $pdo->query("SELECT id FROM matches WHERE championship_id = {$championshipId} AND status = 'homologated' ORDER BY id LIMIT 1")->fetchColumn();
        $matchDetail = $router->dispatch(Request::fake('GET', '/torneio-online/prestacao/campeonatos/' . $championshipId . '/partidas/' . $matchId));
        assert_same(200, $matchDetail->status, 'Administrador nao abriu detalhe da partida na prestacao');
        $pdf = $router->dispatch(Request::fake('GET', '/torneio-online/prestacao/campeonatos/' . $championshipId . '/exportar/pdf'));
        assert_same(200, $pdf->status, 'Exportação PDF de prestação não abriu');
        assert_same('application/pdf', $pdf->headers['Content-Type'] ?? '', 'MIME do PDF de prestação incorreto');
        self::logout($router);
        self::login($router, 'prestacao@torneios.local');
        assert_same(403, $router->dispatch(Request::fake('GET', '/torneio-online/admin/retencao'))->status, 'Prestação de contas acessou retenção administrativa');
        $accountabilityMatchDetail = $router->dispatch(Request::fake('GET', '/torneio-online/prestacao/campeonatos/' . $championshipId . '/partidas/' . $matchId));
        assert_same(200, $accountabilityMatchDetail->status, 'Usuario de prestacao nao abriu detalhe da partida');
        self::logout($router);
    }

    private static function login(Router $router, string $email): void
    {
        $response = $router->dispatch(Request::fake('POST', '/torneio-online/login', ['_csrf' => Security::csrfToken(), 'email' => $email, 'password' => getenv('SEED_DEMO_PASSWORD') ?: 'TestDemo123']));
        assert_same(302, $response->status, 'Login de prestação/retenção falhou');
        assert_true(Auth::authenticated(), 'Sessão HTTP não foi criada');
    }

    private static function logout(Router $router): void
    {
        $router->dispatch(Request::fake('POST', '/torneio-online/logout', ['_csrf' => Security::csrfToken()]));
        Session::destroy();
    }
}
