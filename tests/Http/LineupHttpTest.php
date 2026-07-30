<?php
declare(strict_types=1);

namespace Tests\Http;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Core\Security;
use App\Core\Session;
use App\Database\LineupSeed;
use App\Repositories\LineupRepository;
use App\Repositories\TacticalFormationRepository;
use App\Repositories\TeamRepository;
use App\Repositories\UserRepository;
use App\Services\AuditService;
use App\Services\AuthorizationService;
use App\Services\LineupService;
use function Tests\assert_same;
use function Tests\assert_true;

final class LineupHttpTest
{
    public static function run(): void
    {
        $pdo = Database::connection();
        LineupSeed::run($pdo);
        /** @var Router $router */
        $router = require dirname(__DIR__, 2) . '/routes/web.php';
        $teamId = (int) $pdo->query("SELECT id FROM teams WHERE slug = 'estrela-norte-fc'")->fetchColumn();
        $matchId = (int) $pdo->query('SELECT id FROM matches WHERE home_team_id = ' . $teamId . ' OR away_team_id = ' . $teamId . ' ORDER BY id LIMIT 1')->fetchColumn();
        $formationId = (int) $pdo->query("SELECT id FROM tactical_formations WHERE slug = '4-4-2'")->fetchColumn();
        $users = new UserRepository($pdo);
        $lineups = new LineupRepository($pdo);
        $admin = $users->findByEmail('admin@torneios.local');
        $match = (new \App\Repositories\ScheduleRepository($pdo))->matchById($matchId);
        $service = new LineupService($lineups, new TacticalFormationRepository($pdo), new TeamRepository($pdo), new AuthorizationService($users), new AuditService($pdo));
        $draft = $service->ensureDraft($admin, $match, $teamId);
        $suggestion = $service->suggest($match, $teamId, $formationId);
        $suggestion['staff_ids'] = array_map(static fn (array $member): int => (int) $member['id'], $lineups->staff($teamId));
        assert_true($service->save($admin, $match, $draft, array_merge(['formation_id' => $formationId], $suggestion), true)['ok'], 'Fixture HTTP de escalacao nao confirmou');
        $reopened = $service->reopen($admin, $match, $lineups->find($matchId, $teamId), 'Teste do editor tatico');
        assert_true($reopened['ok'], 'Fixture HTTP de escalacao nao reabriu');

        Session::destroy();
        self::login($router, 'admin@torneios.local');
        $summary = $router->dispatch(Request::fake('GET', '/torneio-online/admin/partidas/' . $matchId . '/escalacoes'));
        assert_same(200, $summary->status, 'Central HTTP de escalacoes nao abriu');
        $edit = $router->dispatch(Request::fake('GET', '/torneio-online/admin/partidas/' . $matchId . '/escalacao/' . $teamId));
        assert_same(200, $edit->status, 'Campo HTTP de escalacao nao abriu');
        assert_true(str_contains($edit->body, 'tactical-field--editor') && str_contains($edit->body, 'football-field.svg') && str_contains($edit->body, 'lineup-formation-catalog'), 'Campo tatico interativo nao foi renderizado');
        assert_same(200, $router->dispatch(Request::fake('POST', '/torneio-online/admin/partidas/' . $matchId . '/escalacao/' . $teamId . '/automatico', ['_csrf' => Security::csrfToken(), 'formation_id' => $formationId]))->status, 'Distribuicao HTTP falhou');
        assert_same(403, $router->dispatch(Request::fake('POST', '/torneio-online/admin/partidas/' . $matchId . '/escalacao/' . $teamId, ['_csrf' => 'invalid']))->status, 'CSRF de escalacao foi aceito');
        self::logout($router);

        self::login($router, 'organizador@torneios.local');
        assert_same(200, $router->dispatch(Request::fake('GET', '/torneio-online/admin/partidas/' . $matchId . '/escalacoes'))->status, 'Organizador nao visualizou escalacoes');
        assert_same(403, $router->dispatch(Request::fake('GET', '/torneio-online/admin/partidas/' . $matchId . '/escalacao/' . $teamId))->status, 'Organizador recebeu edicao de escalacao');
        self::logout($router);

        self::login($router, 'operador@torneios.local');
        assert_same(200, $router->dispatch(Request::fake('GET', '/torneio-online/admin/partidas/' . $matchId . '/escalacoes'))->status, 'Operador nao visualizou escalacoes confirmadas');
        assert_same(403, $router->dispatch(Request::fake('GET', '/torneio-online/admin/partidas/' . $matchId . '/escalacao/' . $teamId))->status, 'Operador recebeu edicao de escalacao');
        self::logout($router);

        self::login($router, 'treinador@torneios.local');
        assert_same(200, $router->dispatch(Request::fake('GET', '/torneio-online/admin/partidas/' . $matchId . '/escalacao/' . $teamId))->status, 'Treinador nao abriu propria escalacao');
        $trainerId = (int) $pdo->query("SELECT id FROM users WHERE email = 'treinador@torneios.local'")->fetchColumn();
        $foreignMatch = (int) $pdo->query("SELECT m.id FROM matches m WHERE NOT EXISTS (SELECT 1 FROM team_user_assignments tua WHERE tua.user_id = {$trainerId} AND tua.status = 'active' AND tua.team_id IN (m.home_team_id, m.away_team_id)) LIMIT 1")->fetchColumn();
        assert_same(403, $router->dispatch(Request::fake('GET', '/torneio-online/admin/partidas/' . $foreignMatch . '/escalacoes'))->status, 'IDOR de escalacao foi aceito');
        self::logout($router);

        self::login($router, 'comunicacao@torneios.local');
        assert_same(403, $router->dispatch(Request::fake('GET', '/torneio-online/admin/partidas/' . $matchId . '/escalacoes'))->status, 'Comunicacao recebeu escalacoes');
        self::logout($router);
    }

    private static function login(Router $router, string $email): void
    {
        $response = $router->dispatch(Request::fake('POST', '/torneio-online/login', ['_csrf' => Security::csrfToken(), 'email' => $email, 'password' => getenv('SEED_DEMO_PASSWORD') ?: 'TestDemo123']));
        assert_same(302, $response->status, 'Login de escalacao falhou para ' . $email);
        assert_true(Auth::authenticated(), 'Sessao de escalacao nao criada');
    }

    private static function logout(Router $router): void
    {
        assert_same(302, $router->dispatch(Request::fake('POST', '/torneio-online/logout', ['_csrf' => Security::csrfToken()]))->status, 'Logout de escalacao falhou');
    }
}
