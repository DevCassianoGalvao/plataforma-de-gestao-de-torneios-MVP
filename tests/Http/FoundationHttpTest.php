<?php
declare(strict_types=1);

namespace Tests\Http;

use App\Core\Request;
use App\Core\Router;
use function Tests\assert_same;
use function Tests\assert_true;

final class FoundationHttpTest
{
    public static function run(): void
    {
        /** @var Router $router */
        $router = require dirname(__DIR__, 2) . '/routes/web.php';
        $health = $router->dispatch(Request::fake('GET', '/torneio-online/health'));
        assert_same(200, $health->status, 'Health HTTP falhou');
        assert_true(str_contains($health->body, '"status":"ok"'), 'Health nao retornou JSON esperado');
        $notFound = $router->dispatch(Request::fake('GET', '/torneio-online/missing'));
        assert_same(404, $notFound->status, '404 HTTP falhou');
        $login = $router->dispatch(Request::fake('GET', '/torneio-online/login'));
        assert_same(200, $login->status, 'Placeholder de login falhou');
        assert_true(str_contains($login->body, '<h1>Entrar</h1>'), 'Tela de login nao identificada');
        $forgot = $router->dispatch(Request::fake('GET', '/torneio-online/senha/esqueci'));
        assert_same(404, $forgot->status, 'Recuperacao por e-mail ainda esta disponivel');
    }
}
