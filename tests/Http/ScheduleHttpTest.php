<?php
declare(strict_types=1);

namespace Tests\Http;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Core\Security;
use App\Core\Session;
use App\Database\ScheduleSeed;
use function Tests\assert_same;
use function Tests\assert_true;

final class ScheduleHttpTest
{
    public static function run(): void
    {
        $pdo = Database::connection();
        ScheduleSeed::run($pdo);
        /** @var Router $router */
        $router = require dirname(__DIR__, 2) . '/routes/web.php';
        $phaseId = (int) $pdo->query("SELECT id FROM competition_phases WHERE slug = 'fase-grupos'")->fetchColumn();
        $matchId = (int) $pdo->query('SELECT id FROM matches ORDER BY id LIMIT 1')->fetchColumn();
        Session::destroy();
        self::login($router, 'admin@torneios.local');
        assert_same(200, $router->dispatch(Request::fake('GET', '/copa-online/admin/tabela'))->status, 'Tabela HTTP nao abriu');
        assert_same(200, $router->dispatch(Request::fake('GET', '/copa-online/admin/fases'))->status, 'Fases HTTP nao abriram');
        assert_same(200, $router->dispatch(new Request('GET', '/copa-online/admin/grupos', ['phase_id' => $phaseId]))->status, 'Grupos HTTP nao abriram');
        assert_same(200, $router->dispatch(Request::fake('GET', '/copa-online/admin/locais'))->status, 'Locais HTTP nao abriram');
        assert_same(200, $router->dispatch(new Request('GET', '/copa-online/admin/tabela/assistente', ['phase_id' => $phaseId]))->status, 'Assistente HTTP nao abriu');
        assert_same(200, $router->dispatch(Request::fake('GET', '/copa-online/admin/partidas/' . $matchId))->status, 'Detalhe da partida nao abriu');
        assert_same(403, $router->dispatch(Request::fake('POST', '/copa-online/admin/partidas/' . $matchId . '/cancelar', ['_csrf' => 'invalid', 'reason' => 'teste']))->status, 'CSRF da agenda foi aceito');
        self::logout($router);

        self::login($router, 'treinador@torneios.local');
        $teamSchedule = $router->dispatch(Request::fake('GET', '/copa-online/admin/tabela'));
        assert_same(200, $teamSchedule->status, 'Treinador nao abriu tabela da propria equipe');
        $foreignId = (int) $pdo->query("SELECT m.id FROM matches m WHERE NOT EXISTS (SELECT 1 FROM team_user_assignments tua WHERE tua.user_id = (SELECT id FROM users WHERE email = 'treinador@torneios.local') AND tua.status = 'active' AND tua.team_id IN (m.home_team_id, m.away_team_id)) LIMIT 1")->fetchColumn();
        assert_same(403, $router->dispatch(Request::fake('GET', '/copa-online/admin/partidas/' . $foreignId))->status, 'Treinador acessou partida de equipe alheia');
        self::logout($router);
        self::login($router, 'operador@torneios.local');
        assert_same(403, $router->dispatch(Request::fake('GET', '/copa-online/admin/tabela'))->status, 'Operador acessou tabela');
        self::logout($router);
        self::login($router, 'comunicacao@torneios.local');
        assert_same(403, $router->dispatch(Request::fake('GET', '/copa-online/admin/tabela'))->status, 'Comunicacao acessou tabela');
        self::logout($router);
    }

    private static function login(Router $router, string $email): void
    {
        $response = $router->dispatch(Request::fake('POST', '/copa-online/login', ['_csrf' => Security::csrfToken(), 'email' => $email, 'password' => getenv('SEED_DEMO_PASSWORD') ?: 'TestDemo123']));
        assert_same(302, $response->status, 'Login de tabela falhou para ' . $email);
        assert_true(Auth::authenticated(), 'Sessao de tabela nao criada');
    }

    private static function logout(Router $router): void
    {
        assert_same(302, $router->dispatch(Request::fake('POST', '/copa-online/logout', ['_csrf' => Security::csrfToken()]))->status, 'Logout de tabela falhou');
    }
}
