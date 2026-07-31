<?php
declare(strict_types=1);

namespace Tests\Http;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Core\Security;
use App\Core\Session;
use App\Database\TransferSeed;
use function Tests\assert_same;
use function Tests\assert_true;

final class TransferHttpTest
{
    public static function run(): void
    {
        $pdo = Database::connection();
        TransferSeed::run($pdo);
        $slug = 'copa-brasil-de-talentos-2026';
        $championshipId = (int) $pdo->query("SELECT id FROM championships WHERE slug = '{$slug}'")->fetchColumn();
        $athleteRow = $pdo->query("SELECT a.id FROM athletes a INNER JOIN teams t ON t.id = a.team_id WHERE t.championship_id = {$championshipId} AND a.deleted_at IS NULL ORDER BY a.id LIMIT 1")->fetch();
        $teamRows = $pdo->query("SELECT id FROM teams WHERE championship_id = {$championshipId} AND deleted_at IS NULL ORDER BY id LIMIT 2")->fetchAll();
        /** @var Router $router */
        $router = require dirname(__DIR__, 2) . '/routes/web.php';

        Session::destroy();
        self::login($router, 'admin@torneios.local');
        assert_same(200, $router->dispatch(Request::fake('GET', '/torneio-online/admin/vai-e-vem'))->status, 'Administrador nao abriu Vai e Vem');
        $date = date('Y-m-d', strtotime('+1 day'));
        $body = ['_csrf' => Security::csrfToken(), 'championship_id' => $championshipId, 'athlete_id' => (int) $athleteRow['id'], 'previous_team_id' => (int) $teamRows[0]['id'], 'new_team_id' => (int) $teamRows[1]['id'], 'type' => 'contratacao', 'movement_date' => $date, 'public_observation' => 'Anuncio seguro', 'internal_notes' => 'Segredo editorial', 'status' => 'pending'];
        assert_same(302, $router->dispatch(Request::fake('POST', '/torneio-online/admin/vai-e-vem', $body))->status, 'Criacao de movimentacao falhou');
        $id = (int) $pdo->query("SELECT id FROM transfer_movements WHERE type = 'contratacao' AND movement_date = '{$date}'")->fetchColumn();
        assert_true($id > 0, 'Movimento HTTP nao persistido');
        assert_same(403, $router->dispatch(Request::fake('POST', '/torneio-online/admin/vai-e-vem/' . $id . '/aprovar', ['_csrf' => 'invalid']))->status, 'CSRF de transferencia aceito');
        assert_same(302, $router->dispatch(Request::fake('POST', '/torneio-online/admin/vai-e-vem/' . $id . '/aprovar', ['_csrf' => Security::csrfToken()]))->status, 'Aprovacao HTTP falhou');
        assert_same(302, $router->dispatch(Request::fake('POST', '/torneio-online/admin/vai-e-vem/' . $id . '/publicar', ['_csrf' => Security::csrfToken()]))->status, 'Publicacao HTTP falhou');
        $public = $router->dispatch(new Request('GET', '/torneio-online/campeonatos/' . $slug . '/vai-e-vem', ['q' => 'Anuncio']));
        assert_same(200, $public->status, 'Portal de Vai e Vem nao abriu');
        assert_true(str_contains($public->body, 'Anuncio seguro') && !str_contains($public->body, 'Segredo editorial'), 'Conteudo privado vazou no portal');
        $photoPath = (string) $pdo->query('SELECT photo_path FROM athletes WHERE id = ' . (int) $athleteRow['id'])->fetchColumn();
        $photoResponse = $router->dispatch(Request::fake('GET', '/torneio-online/campeonatos/' . $slug . '/vai-e-vem/' . $id . '/foto'));
        assert_same($photoPath === '' ? 404 : 200, $photoResponse->status, 'Rota de foto publica respondeu incorretamente');
        self::logout($router);

        $trainerTeamId = (int) $pdo->query("SELECT id FROM teams WHERE slug = 'estrela-norte-fc'")->fetchColumn();
        $managerTeamId = (int) $pdo->query("SELECT id FROM teams WHERE slug = 'serra-azul-futebol'")->fetchColumn();
        $trainerAthleteId = (int) $pdo->query("SELECT id FROM athletes WHERE team_id = {$trainerTeamId} AND deleted_at IS NULL ORDER BY id LIMIT 1")->fetchColumn();
        $managerAthleteId = (int) $pdo->query("SELECT id FROM athletes WHERE team_id = {$managerTeamId} AND deleted_at IS NULL ORDER BY id LIMIT 1")->fetchColumn();
        $requestDate = date('Y-m-d', strtotime('+5 days'));

        self::login($router, 'treinador@torneios.local');
        assert_same(200, $router->dispatch(Request::fake('GET', '/torneio-online/admin/vai-e-vem'))->status, 'Treinador nao abriu Vai e Vem');
        $foreignAttempt = $router->dispatch(Request::fake('POST', '/torneio-online/admin/vai-e-vem', ['_csrf' => Security::csrfToken(), 'championship_id' => $championshipId, 'athlete_id' => $managerAthleteId, 'previous_team_id' => $managerTeamId, 'new_team_id' => $trainerTeamId, 'type' => 'transferencia', 'movement_date' => $requestDate, 'status' => 'pending']));
        assert_same(403, $foreignAttempt->status, 'Treinador criou solicitacao a partir de equipe alheia');
        $ownRequest = $router->dispatch(Request::fake('POST', '/torneio-online/admin/vai-e-vem', ['_csrf' => Security::csrfToken(), 'championship_id' => $championshipId, 'athlete_id' => $trainerAthleteId, 'previous_team_id' => $trainerTeamId, 'new_team_id' => $managerTeamId, 'type' => 'transferencia', 'movement_date' => $requestDate, 'status' => 'pending']));
        assert_same(302, $ownRequest->status, 'Treinador nao criou solicitacao da propria equipe');
        $requestId = (int) $pdo->query("SELECT id FROM transfer_movements WHERE athlete_id = {$trainerAthleteId} AND movement_date = '{$requestDate}' AND type = 'transferencia' LIMIT 1")->fetchColumn();
        assert_true($requestId > 0, 'Solicitacao do treinador nao foi persistida');
        assert_same(200, $router->dispatch(Request::fake('GET', '/torneio-online/admin/vai-e-vem/' . $requestId))->status, 'Treinador nao visualizou a propria solicitacao');
        assert_same(403, $router->dispatch(Request::fake('POST', '/torneio-online/admin/vai-e-vem/' . $requestId . '/aprovar', ['_csrf' => Security::csrfToken()]))->status, 'Treinador aprovou movimentacao');
        assert_same(403, $router->dispatch(Request::fake('POST', '/torneio-online/admin/vai-e-vem/' . $requestId . '/publicar', ['_csrf' => Security::csrfToken()]))->status, 'Treinador publicou movimentacao');
        assert_same(403, $router->dispatch(Request::fake('POST', '/torneio-online/admin/vai-e-vem/' . $requestId . '/aplicar-vinculo', ['_csrf' => Security::csrfToken()]))->status, 'Treinador aplicou vinculo oficial');
        assert_same(403, $router->dispatch(Request::fake('GET', '/torneio-online/admin/vai-e-vem/' . $id))->status, 'Treinador visualizou movimentacao de outra equipe');
        self::logout($router);

        self::login($router, 'gestor@torneios.local');
        assert_same(403, $router->dispatch(Request::fake('POST', '/torneio-online/admin/vai-e-vem/' . $requestId . '/cancelar', ['_csrf' => Security::csrfToken()]))->status, 'Gestor cancelou solicitacao de outro treinador');
        self::logout($router);

        self::login($router, 'treinador@torneios.local');
        assert_same(302, $router->dispatch(Request::fake('POST', '/torneio-online/admin/vai-e-vem/' . $requestId . '/cancelar', ['_csrf' => Security::csrfToken()]))->status, 'Treinador nao cancelou a propria solicitacao');
        assert_same('cancelled', (string) $pdo->query("SELECT status FROM transfer_movements WHERE id = {$requestId}")->fetchColumn(), 'Cancelamento nao persistiu');
        self::logout($router);

        self::login($router, 'operador@torneios.local');
        assert_same(403, $router->dispatch(Request::fake('GET', '/torneio-online/admin/vai-e-vem'))->status, 'Operador acessou transferencias');
        self::logout($router);
    }

    private static function login(Router $router, string $email): void { $response = $router->dispatch(Request::fake('POST', '/torneio-online/login', ['_csrf' => Security::csrfToken(), 'email' => $email, 'password' => getenv('SEED_DEMO_PASSWORD') ?: 'TestDemo123'])); assert_same(302, $response->status, 'Login de transferencia falhou'); assert_true(Auth::authenticated(), 'Sessao de transferencia nao criada'); }
    private static function logout(Router $router): void { $router->dispatch(Request::fake('POST', '/torneio-online/logout', ['_csrf' => Security::csrfToken()])); Session::destroy(); }
}
