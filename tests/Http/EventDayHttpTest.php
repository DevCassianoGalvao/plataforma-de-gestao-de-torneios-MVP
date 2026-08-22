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

final class EventDayHttpTest
{
    public static function run(): void
    {
        $pdo = Database::connection();
        $championshipId = (int) $pdo->query('SELECT id FROM championships WHERE deleted_at IS NULL ORDER BY id LIMIT 1')->fetchColumn();
        /** @var Router $router */
        $router = require dirname(__DIR__, 2) . '/routes/web.php';
        self::login($router);
        $response = $router->dispatch(Request::fake('GET', '/torneio-online/admin/dias-evento?championship_id=' . $championshipId));
        assert_same(200, $response->status, 'Administrador não abriu o painel de dias de evento');
        assert_true(str_contains($response->body, 'Dias de evento'), 'Painel de dias de evento sem título');
        assert_true(str_contains($response->body, 'Evidências do dia'), 'Painel sem formulário de evidências');
        self::logout($router);
    }

    private static function login(Router $router): void
    {
        $response = $router->dispatch(Request::fake('POST', '/torneio-online/login', [
            '_csrf' => Security::csrfToken(),
            'email' => 'admin@torneios.local',
            'password' => getenv('SEED_DEMO_PASSWORD') ?: 'TestDemo123',
        ]));
        assert_same(302, $response->status, 'Login administrativo falhou no painel de eventos');
        assert_true(Auth::authenticated(), 'Sessão administrativa não foi criada');
    }

    private static function logout(Router $router): void
    {
        $router->dispatch(Request::fake('POST', '/torneio-online/logout', ['_csrf' => Security::csrfToken()]));
        Session::destroy();
    }
}
