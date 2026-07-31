<?php
declare(strict_types=1);

namespace Tests\Http;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Request;
use App\Core\Router;
use App\Core\Security;
use App\Core\Session;
use App\Database\AuthSeed;
use App\Core\Database;
use function Tests\assert_same;
use function Tests\assert_true;

final class AuthenticationHttpTest
{
    public static function run(): void
    {
        AuthSeed::run(Database::connection(), getenv('SEED_DEMO_PASSWORD') ?: 'TestDemo123');
        /** @var Router $router */
        $router = require dirname(__DIR__, 2) . '/routes/web.php';
        Session::destroy();
        $login = $router->dispatch(Request::fake('GET', '/torneio-online/login'));
        assert_same(200, $login->status, 'GET login falhou');
        $invalidCsrf = $router->dispatch(Request::fake('POST', '/torneio-online/login', ['email' => 'admin@torneios.local', 'password' => 'TestDemo123']));
        assert_same(419, $invalidCsrf->status, 'CSRF de login invalido aceito');
        $csrf = Security::csrfToken();
        $valid = $router->dispatch(Request::fake('POST', '/torneio-online/login', ['_csrf' => $csrf, 'email' => 'admin@torneios.local', 'password' => getenv('SEED_DEMO_PASSWORD') ?: 'TestDemo123']));
        assert_same(302, $valid->status, 'Login valido nao redirecionou');
        assert_same(Config::url('/admin'), $valid->headers['Location'] ?? null, 'Redirecionamento de administrador incorreto');
        assert_true(Auth::authenticated(), 'Sessao nao criada');
        $admin = $router->dispatch(Request::fake('GET', '/torneio-online/admin/usuarios'));
        assert_same(200, $admin->status, 'Administrador nao acessou usuarios');
        assert_true(str_contains($admin->body, 'id="app-sidebar"') && str_contains($admin->body, 'data-sidebar-dismiss') && str_contains($admin->body, 'aria-controls="app-sidebar"'), 'Navegacao administrativa movel nao possui controles acessiveis.');
        $logout = $router->dispatch(Request::fake('POST', '/torneio-online/logout', ['_csrf' => Security::csrfToken()]));
        assert_same(302, $logout->status, 'Logout nao redirecionou');
        $csrf = Security::csrfToken();
        $trainer = $router->dispatch(Request::fake('POST', '/torneio-online/login', ['_csrf' => $csrf, 'email' => 'treinador@torneios.local', 'password' => getenv('SEED_DEMO_PASSWORD') ?: 'TestDemo123']));
        assert_same(Config::url('/minha-equipe'), $trainer->headers['Location'] ?? null, 'Redirecionamento de treinador incorreto');
        $denied = $router->dispatch(Request::fake('GET', '/torneio-online/admin/usuarios'));
        assert_same(403, $denied->status, 'Treinador acessou usuarios');
        $protected = $router->dispatch(Request::fake('GET', '/torneio-online/admin'));
        assert_same(403, $protected->status, 'Treinador acessou painel global');
    }
}
