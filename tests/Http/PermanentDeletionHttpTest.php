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

final class PermanentDeletionHttpTest
{
    public static function run(): void
    {
        /** @var Router $router */
        $router = require dirname(__DIR__, 2) . '/routes/web.php';
        self::login($router, 'admin@torneios.local');
        $page = $router->dispatch(Request::fake('GET', '/torneio-online/admin/retencao'));
        assert_same(200, $page->status, 'Administrador nao abriu a ferramenta de limpeza');
        assert_true(str_contains($page->body, 'Excluir dados definitivamente'), 'Ferramenta de exclusao definitiva nao apareceu');
        $teamId = (int) \App\Core\Database::connection()->query('SELECT id FROM teams ORDER BY id LIMIT 1')->fetchColumn();
        $blocked = $router->dispatch(Request::fake('POST', '/torneio-online/admin/retencao/excluir-definitivamente', [
            '_csrf' => Security::csrfToken(),
            'entity' => 'equipes',
            'ids' => [$teamId],
            'reason' => 'Teste de confirmacao',
            'confirmation' => 'EXCLUIR',
        ]));
        assert_same(422, $blocked->status, 'Confirmacao incompleta foi aceita pela rota');
        self::logout($router);
        self::login($router, 'prestacao@torneios.local');
        assert_same(403, $router->dispatch(Request::fake('GET', '/torneio-online/admin/retencao'))->status, 'Prestacao acessou ferramenta destrutiva');
        self::logout($router);
    }

    private static function login(Router $router, string $email): void
    {
        $response = $router->dispatch(Request::fake('POST', '/torneio-online/login', ['_csrf' => Security::csrfToken(), 'email' => $email, 'password' => getenv('SEED_DEMO_PASSWORD') ?: 'TestDemo123']));
        assert_same(302, $response->status, 'Login HTTP da limpeza falhou');
        assert_true(Auth::authenticated(), 'Sessao HTTP da limpeza nao foi criada');
    }

    private static function logout(Router $router): void
    {
        $router->dispatch(Request::fake('POST', '/torneio-online/logout', ['_csrf' => Security::csrfToken()]));
        Session::destroy();
    }
}
