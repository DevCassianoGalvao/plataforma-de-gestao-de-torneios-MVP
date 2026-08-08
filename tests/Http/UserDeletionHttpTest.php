<?php
declare(strict_types=1);

namespace Tests\Http;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Core\Security;
use App\Core\Session;
use App\Repositories\UserRepository;
use function Tests\assert_same;
use function Tests\assert_true;

final class UserDeletionHttpTest
{
    public static function run(): void
    {
        /** @var Router $router */
        $router = require dirname(__DIR__, 2) . '/routes/web.php';
        self::login($router, 'admin@torneios.local');

        $admin = Auth::user();
        assert_true((bool) $admin, 'Administrador nao foi carregado');
        $selfDelete = $router->dispatch(Request::fake('POST', '/torneio-online/admin/usuarios/' . $admin['id'] . '/excluir', ['_csrf' => Security::csrfToken()]));
        assert_same(422, $selfDelete->status, 'A propria conta foi aceita para exclusao');

        $email = 'exclusao-' . bin2hex(random_bytes(4)) . '@torneios.local';
        $repository = new UserRepository(Database::connection());
        $userId = $repository->create('Usuario para excluir', $email, password_hash('Teste1234', PASSWORD_DEFAULT));
        $delete = $router->dispatch(Request::fake('POST', '/torneio-online/admin/usuarios/' . $userId . '/excluir', ['_csrf' => Security::csrfToken()]));
        assert_same(302, $delete->status, 'Administrador nao conseguiu excluir usuario');
        $deletedAt = Database::connection()->prepare('SELECT deleted_at FROM users WHERE id = ?');
        $deletedAt->execute([$userId]);
        assert_true((string) $deletedAt->fetchColumn() !== '', 'Exclusao do usuario nao foi registrada');

        self::logout($router);
        self::login($router, 'prestacao@torneios.local');
        $forbidden = $router->dispatch(Request::fake('POST', '/torneio-online/admin/usuarios/' . $userId . '/excluir', ['_csrf' => Security::csrfToken()]));
        assert_same(403, $forbidden->status, 'Prestacao de contas conseguiu excluir usuario');
        self::logout($router);
    }

    private static function login(Router $router, string $email): void
    {
        $response = $router->dispatch(Request::fake('POST', '/torneio-online/login', ['_csrf' => Security::csrfToken(), 'email' => $email, 'password' => getenv('SEED_DEMO_PASSWORD') ?: 'TestDemo123']));
        assert_same(302, $response->status, 'Login HTTP de usuarios falhou');
        assert_true(Auth::authenticated(), 'Sessao HTTP de usuarios nao foi criada');
    }

    private static function logout(Router $router): void
    {
        $router->dispatch(Request::fake('POST', '/torneio-online/logout', ['_csrf' => Security::csrfToken()]));
        Session::destroy();
    }
}
