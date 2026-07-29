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

final class DisciplineHttpTest
{
    public static function run(): void
    {
        /** @var Router $router */
        $router = require dirname(__DIR__, 2) . '/routes/web.php';
        $championshipId = (int) Database::connection()->query('SELECT id FROM championships ORDER BY id LIMIT 1')->fetchColumn();
        self::login($router, 'admin@torneios.local');
        $response = $router->dispatch(new Request('GET', '/copa-online/admin/disciplina', ['championship_id' => (string) $championshipId]));
        assert_same(200, $response->status, 'Central disciplinar nao abriu');
        assert_true(str_contains($response->body, 'Disciplina e suspensoes'), 'Central disciplinar nao renderizou');
        assert_same(403, $router->dispatch(Request::fake('POST', '/copa-online/admin/disciplina/suspensao', ['championship_id' => $championshipId, '_csrf' => 'invalid']))->status, 'CSRF disciplinar foi aceito');
        self::logout($router);
        self::login($router, 'comunicacao@torneios.local');
        assert_same(403, $router->dispatch(new Request('GET', '/copa-online/admin/disciplina', ['championship_id' => (string) $championshipId]))->status, 'Comunicacao recebeu disciplina');
        self::logout($router);
    }

    private static function login(Router $router, string $email): void
    {
        $response = $router->dispatch(Request::fake('POST', '/copa-online/login', ['_csrf' => Security::csrfToken(), 'email' => $email, 'password' => getenv('SEED_DEMO_PASSWORD') ?: 'TestDemo123']));
        assert_same(302, $response->status, 'Login disciplinar falhou');
        assert_true(Auth::authenticated(), 'Sessao disciplinar nao foi criada');
    }

    private static function logout(Router $router): void
    {
        assert_same(302, $router->dispatch(Request::fake('POST', '/copa-online/logout', ['_csrf' => Security::csrfToken()]))->status, 'Logout disciplinar falhou');
        Session::destroy();
    }
}
