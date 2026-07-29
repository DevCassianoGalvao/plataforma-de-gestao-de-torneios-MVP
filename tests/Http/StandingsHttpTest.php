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

final class StandingsHttpTest
{
    public static function run(): void
    {
        /** @var Router $router */
        $router = require dirname(__DIR__, 2) . '/routes/web.php';
        $phaseId = (int) Database::connection()->query("SELECT id FROM competition_phases WHERE phase_type = 'groups' ORDER BY id LIMIT 1")->fetchColumn();
        self::login($router, 'admin@torneios.local');
        $response = $router->dispatch(new Request('GET', '/torneio-online/admin/classificacao', ['phase_id' => (string) $phaseId]));
        assert_same(200, $response->status, 'Classificacao nao abriu');
        assert_true(str_contains($response->body, 'Classificacao'), 'Classificacao nao renderizou');
        assert_same(403, $router->dispatch(Request::fake('POST', '/torneio-online/admin/classificacao/recalcular', ['phase_id' => $phaseId, '_csrf' => 'invalid']))->status, 'CSRF da classificacao foi aceito');
        self::logout($router);
        self::login($router, 'comunicacao@torneios.local');
        assert_same(403, $router->dispatch(new Request('GET', '/torneio-online/admin/classificacao', ['phase_id' => (string) $phaseId]))->status, 'Comunicacao recebeu classificacao');
        self::logout($router);
    }

    private static function login(Router $router, string $email): void
    {
        $response = $router->dispatch(Request::fake('POST', '/torneio-online/login', ['_csrf' => Security::csrfToken(), 'email' => $email, 'password' => getenv('SEED_DEMO_PASSWORD') ?: 'TestDemo123']));
        assert_same(302, $response->status, 'Login da classificacao falhou');
        assert_true(Auth::authenticated(), 'Sessao da classificacao nao foi criada');
    }

    private static function logout(Router $router): void
    {
        assert_same(302, $router->dispatch(Request::fake('POST', '/torneio-online/logout', ['_csrf' => Security::csrfToken()]))->status, 'Logout da classificacao falhou');
        Session::destroy();
    }
}
