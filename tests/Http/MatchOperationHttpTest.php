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

final class MatchOperationHttpTest
{
    public static function run(): void
    {
        $pdo = Database::connection();
        /** @var Router $router */
        $router = require dirname(__DIR__, 2) . '/routes/web.php';
        $matchId = (int) $pdo->query('SELECT id FROM matches ORDER BY id LIMIT 1')->fetchColumn();
        $users = new UserRepository($pdo);

        Session::destroy();
        self::login($router, 'admin@torneios.local');
        $adminMatches = $router->dispatch(Request::fake('GET', '/torneio-online/minhas-partidas'));
        assert_same(200, $adminMatches->status, 'Administrador nao abriu suas partidas');
        assert_true(str_contains($adminMatches->body, 'Partidas para operar') && str_contains($adminMatches->body, 'Vis&atilde;o administrativa'), 'Administrador nao recebeu a visao de todas as partidas');
        assert_same(200, $router->dispatch(Request::fake('GET', '/torneio-online/admin/notificacoes'))->status, 'Administrador nao abriu a central de notificacoes');
        $central = $router->dispatch(Request::fake('GET', '/torneio-online/admin/partidas/' . $matchId . '/operacao'));
        assert_same(200, $central->status, 'Central operacional nao abriu');
        assert_true(str_contains($central->body, 'Central operacional da partida') && str_contains($central->body, 'Checklist de finalizacao'), 'Central nao renderizou operacao e checklist');
        assert_same(403, $router->dispatch(Request::fake('POST', '/torneio-online/admin/partidas/' . $matchId . '/operacao/evento', ['event_type' => 'occurrence', 'period' => 'other', '_csrf' => 'invalid']))->status, 'CSRF da operacao foi aceito');
        self::logout($router);

        self::login($router, 'operador@torneios.local');
        $operatorMatches = $router->dispatch(Request::fake('GET', '/torneio-online/minhas-partidas'));
        assert_same(200, $operatorMatches->status, 'Operador nao abriu suas partidas');
        assert_true(str_contains($operatorMatches->body, 'Minhas partidas') && str_contains($operatorMatches->body, 'partidas atribu'), 'Operador nao recebeu a visao atribuida');
        assert_same(200, $router->dispatch(Request::fake('GET', '/torneio-online/admin/partidas/' . $matchId . '/operacao'))->status, 'Operador nao visualizou a central atribuida');
        self::logout($router);

        self::login($router, 'organizador@torneios.local');
        assert_same(200, $router->dispatch(Request::fake('GET', '/torneio-online/admin/partidas/' . $matchId . '/operacao'))->status, 'Organizador nao visualizou a central autorizada');
        self::logout($router);

        $trainerId = (int) $pdo->query("SELECT id FROM users WHERE email = 'treinador@torneios.local'")->fetchColumn();
        $foreignMatchId = (int) $pdo->query("SELECT m.id FROM matches m WHERE m.id <> {$matchId} AND NOT EXISTS (SELECT 1 FROM team_user_assignments tua WHERE tua.user_id = {$trainerId} AND tua.status = 'active' AND tua.team_id IN (m.home_team_id, m.away_team_id)) ORDER BY m.id LIMIT 1")->fetchColumn();
        self::login($router, 'treinador@torneios.local');
        assert_same(403, $router->dispatch(Request::fake('GET', '/torneio-online/admin/partidas/' . $foreignMatchId . '/operacao'))->status, 'IDOR da central foi aceito');
        self::logout($router);

        self::login($router, 'comunicacao@torneios.local');
        assert_same(403, $router->dispatch(Request::fake('GET', '/torneio-online/admin/partidas/' . $matchId . '/operacao'))->status, 'Comunicacao recebeu central operacional');
        self::logout($router);
    }

    private static function login(Router $router, string $email): void
    {
        $response = $router->dispatch(Request::fake('POST', '/torneio-online/login', ['_csrf' => Security::csrfToken(), 'email' => $email, 'password' => getenv('SEED_DEMO_PASSWORD') ?: 'TestDemo123']));
        assert_same(302, $response->status, 'Login da central falhou para ' . $email);
        assert_true(Auth::authenticated(), 'Sessao da central nao foi criada');
    }

    private static function logout(Router $router): void
    {
        assert_same(302, $router->dispatch(Request::fake('POST', '/torneio-online/logout', ['_csrf' => Security::csrfToken()]))->status, 'Logout da central falhou');
    }
}
