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
use App\Repositories\UserRepository;
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
        assert_true(str_contains($admin->body, 'Gerar nova senha') && !str_contains($admin->body, 'Gerar redefinicao'), 'Acao administrativa de senha nao foi atualizada');
        $accountability = (new UserRepository(Database::connection()))->findByEmail('prestacao@torneios.local');
        assert_true($accountability !== null, 'Usuario de prestacao de contas nao encontrado');
        $reset = $router->dispatch(Request::fake('POST', '/torneio-online/admin/usuarios/' . $accountability['id'] . '/reset-password', ['_csrf' => Security::csrfToken()]));
        assert_same(302, $reset->status, 'Administrador nao conseguiu gerar nova senha');
        $adminAfterReset = $router->dispatch(Request::fake('GET', '/torneio-online/admin/usuarios'));
        assert_true(preg_match('/Nova senha temporaria para .*: ([A-Za-z0-9]{12})\./', $adminAfterReset->body, $temporaryMatch) === 1, 'Senha temporaria nao foi exibida uma unica vez');
        $adminAfterFlash = $router->dispatch(Request::fake('GET', '/torneio-online/admin/usuarios'));
        assert_true(!str_contains($adminAfterFlash->body, 'Nova senha temporaria para'), 'Senha temporaria permaneceu visivel apos o primeiro acesso');
        $accountabilityAfterReset = (new UserRepository(Database::connection()))->findById((int) $accountability['id']);
        assert_true(password_verify($temporaryMatch[1], (string) $accountabilityAfterReset['password_hash']), 'Senha temporaria nao foi armazenada corretamente');
        (new UserRepository(Database::connection()))->updatePassword((int) $accountability['id'], password_hash(getenv('SEED_DEMO_PASSWORD') ?: 'TestDemo123', PASSWORD_DEFAULT));
        $logout = $router->dispatch(Request::fake('POST', '/torneio-online/logout', ['_csrf' => Security::csrfToken()]));
        assert_same(302, $logout->status, 'Logout nao redirecionou');
        $csrf = Security::csrfToken();
        $trainer = $router->dispatch(Request::fake('POST', '/torneio-online/login', ['_csrf' => $csrf, 'email' => 'treinador@torneios.local', 'password' => getenv('SEED_DEMO_PASSWORD') ?: 'TestDemo123']));
        assert_same(Config::url('/minha-equipe'), $trainer->headers['Location'] ?? null, 'Redirecionamento de treinador incorreto');
        $denied = $router->dispatch(Request::fake('GET', '/torneio-online/admin/usuarios'));
        assert_same(403, $denied->status, 'Treinador acessou usuarios');
        $trainerTeams = $router->dispatch(Request::fake('GET', '/torneio-online/admin/equipes'));
        assert_same(200, $trainerTeams->status, 'Treinador nao acessou sua area esportiva');
        assert_true(str_contains($trainerTeams->body, 'Cadastro esportivo') && str_contains($trainerTeams->body, 'Equipes'), 'Menu esportivo do treinador nao foi exibido');
        assert_true(!str_contains($trainerTeams->body, '>Conteúdo<') && !str_contains($trainerTeams->body, '>Acesso<') && !str_contains($trainerTeams->body, 'Notícias'), 'Menu administrativo apareceu para treinador');
        $protected = $router->dispatch(Request::fake('GET', '/torneio-online/admin'));
        assert_same(302, $protected->status, 'Treinador nao foi redirecionado do painel global');
        assert_same(Config::url('/minha-equipe'), $protected->headers['Location'] ?? null, 'Destino do treinador no painel global incorreto');
    }
}
