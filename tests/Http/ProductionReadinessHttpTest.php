<?php
declare(strict_types=1);

namespace Tests\Http;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Router;
use App\Core\Security;
use App\Core\Session;
use function Tests\assert_same;
use function Tests\assert_true;

final class ProductionReadinessHttpTest
{
    public static function run(): void
    {
        /** @var Router $router */
        $router = require dirname(__DIR__, 2) . '/routes/web.php';
        Session::destroy();
        $protected = $router->dispatch(Request::fake('GET', '/torneio-online/admin'));
        assert_same(302, $protected->status, 'Rota protegida nao exigiu autenticacao');
        assert_true(!str_contains((string) ($protected->headers['Location'] ?? ''), '://'), 'Redirect de autenticacao exposto como URL externa');
        $login = $router->dispatch(Request::fake('GET', '/torneio-online/login'));
        preg_match('/name="_csrf" value="([^"]+)"/', $login->body, $match);
        $csrf = html_entity_decode($match[1] ?? '', ENT_QUOTES, 'UTF-8');
        $auth = $router->dispatch(Request::fake('POST', '/torneio-online/login', ['_csrf' => $csrf, 'email' => 'admin@torneios.local', 'password' => getenv('SEED_DEMO_PASSWORD') ?: 'TestDemo123', 'next' => '//evil.example']));
        assert_same(302, $auth->status, 'Login valido falhou durante smoke de seguranca');
        assert_true(!str_contains((string) ($auth->headers['Location'] ?? ''), 'evil.example'), 'Open redirect aceito no login');
        assert_true(Auth::authenticated(), 'Sessao nao foi criada');
        $portal = $router->dispatch(Request::fake('GET', '/torneio-online/campeonatos/copa-brasil-de-talentos-2026'));
        assert_same(200, $portal->status, 'Portal nao abriu durante smoke de seguranca');
        foreach (['Content-Security-Policy', 'X-Content-Type-Options', 'X-Frame-Options', 'Referrer-Policy', 'Permissions-Policy'] as $header) assert_true(isset($portal->headers[$header]), 'Header ausente no portal: ' . $header);
        assert_true(!str_contains($portal->body, 'private_notes'), 'Campo privado vazou no portal');
        $notifications = $router->dispatch(Request::fake('GET', '/torneio-online/admin/notificacoes'));
        assert_same(200, $notifications->status, 'Central de notificacoes nao abriu para administrador');
        $logout = $router->dispatch(Request::fake('POST', '/torneio-online/logout', ['_csrf' => Security::csrfToken()]));
        assert_same(302, $logout->status, 'Logout de smoke falhou');
        Session::destroy();
    }
}
