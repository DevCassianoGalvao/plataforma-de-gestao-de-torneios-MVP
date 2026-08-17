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
        $teamId = (int) Database::connection()->query('SELECT id FROM teams WHERE deleted_at IS NULL ORDER BY id LIMIT 1')->fetchColumn();
        assert_true($teamId > 0, 'Fixture de equipe nao foi criado');
        $now = date('Y-m-d H:i:s');
        $assignment = Database::connection()->prepare("INSERT INTO team_user_assignments (team_id, user_id, assignment_type, status, starts_at, created_by, created_at, updated_at) VALUES (?, ?, 'manager', 'active', ?, ?, ?, ?)");
        $assignment->execute([$teamId, $userId, date('Y-m-d'), (int) $admin['id'], $now, $now]);

        $update = $router->dispatch(Request::fake('POST', '/torneio-online/admin/usuarios/' . $userId, [
            '_csrf' => Security::csrfToken(),
            'name' => 'Usuario atualizado',
            'email' => $email,
            'password' => 'SenhaNova123',
            'password_confirmation' => 'SenhaNova123',
        ]));
        assert_same(302, $update->status, 'Administrador nao conseguiu atualizar usuario');
        self::logout($router);
        self::loginWithPassword($router, $email, 'SenhaNova123');
        self::logout($router);
        self::login($router, 'admin@torneios.local');

        $delete = $router->dispatch(Request::fake('POST', '/torneio-online/admin/usuarios/' . $userId . '/excluir', ['_csrf' => Security::csrfToken()]));
        assert_same(302, $delete->status, 'Administrador nao conseguiu excluir usuario');
        $deletedAt = Database::connection()->prepare('SELECT deleted_at FROM users WHERE id = ?');
        $deletedAt->execute([$userId]);
        assert_true((string) $deletedAt->fetchColumn() !== '', 'Exclusao do usuario nao foi registrada');
        $storedEmail = Database::connection()->prepare('SELECT email FROM users WHERE id = ?');
        $storedEmail->execute([$userId]);
        assert_true(str_ends_with((string) $storedEmail->fetchColumn(), '@invalid.local'), 'E-mail excluido continuou preso no banco');
        $assignmentCheck = Database::connection()->prepare("SELECT COUNT(*) FROM team_user_assignments WHERE user_id = ? AND status = 'ended' AND ends_at IS NOT NULL");
        $assignmentCheck->execute([$userId]);
        assert_same(1, (int) $assignmentCheck->fetchColumn(), 'Vinculo da equipe excluida nao foi encerrado com o historico preservado');
        assert_true($repository->create('Mesmo e-mail', $email, password_hash('Teste1234', PASSWORD_DEFAULT)) > 0, 'E-mail de usuario excluido nao pode ser reutilizado');

        self::logout($router);
        self::login($router, 'prestacao@torneios.local');
        $forbidden = $router->dispatch(Request::fake('POST', '/torneio-online/admin/usuarios/' . $userId . '/excluir', ['_csrf' => Security::csrfToken()]));
        assert_same(403, $forbidden->status, 'Prestacao de contas conseguiu excluir usuario');
        self::logout($router);
    }

    private static function login(Router $router, string $email): void
    {
        self::loginWithPassword($router, $email, getenv('SEED_DEMO_PASSWORD') ?: 'TestDemo123');
    }

    private static function loginWithPassword(Router $router, string $email, string $password): void
    {
        $response = $router->dispatch(Request::fake('POST', '/torneio-online/login', ['_csrf' => Security::csrfToken(), 'email' => $email, 'password' => $password]));
        assert_same(302, $response->status, 'Login HTTP de usuarios falhou');
        assert_true(Auth::authenticated(), 'Sessao HTTP de usuarios nao foi criada');
    }

    private static function logout(Router $router): void
    {
        $router->dispatch(Request::fake('POST', '/torneio-online/logout', ['_csrf' => Security::csrfToken()]));
        Session::destroy();
    }
}
