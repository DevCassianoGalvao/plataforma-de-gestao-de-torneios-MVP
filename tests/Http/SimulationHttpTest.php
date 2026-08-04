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

final class SimulationHttpTest
{
    public static function run(): void
    {
        /** @var Router $router */ $router=require dirname(__DIR__,2).'/routes/web.php';
        self::login($router,'admin@torneios.local');
        assert_same(200,$router->dispatch(new Request('GET','/torneio-online/admin/simulacoes'))->status,'Painel de simulacoes nao abriu');
        assert_same(403,$router->dispatch(Request::fake('POST','/torneio-online/admin/simulacoes',['_csrf'=>'invalido']))->status,'CSRF de simulacao foi aceito');
        self::logout($router);self::login($router,'prestacao@torneios.local');
        assert_same(403,$router->dispatch(new Request('GET','/torneio-online/admin/simulacoes'))->status,'Prestacao de contas acessou simulacoes');
        self::logout($router);
        $public=$router->dispatch(new Request('GET','/torneio-online/campeonatos/copa-brasil-de-talentos-2026/classificacao'));
        assert_true(!str_contains($public->body,'Cenário isolado de teste'),'Simulacao vazou para portal publico');
    }
    private static function login(Router $router,string $email):void {$response=$router->dispatch(Request::fake('POST','/torneio-online/login',['_csrf'=>Security::csrfToken(),'email'=>$email,'password'=>getenv('SEED_DEMO_PASSWORD')?:'TestDemo123']));assert_same(302,$response->status,'Login de simulacao falhou');assert_true(Auth::authenticated(),'Sessao de simulacao nao criada');}
    private static function logout(Router $router):void {$router->dispatch(Request::fake('POST','/torneio-online/logout',['_csrf'=>Security::csrfToken()]));Session::destroy();}
}
