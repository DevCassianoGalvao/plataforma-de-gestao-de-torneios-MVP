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
        self::login($router, 'comunicacao@torneios.local');
        assert_same(200, $router->dispatch(Request::fake('GET', '/copa-online/admin/vai-e-vem'))->status, 'Comunicacao nao abriu Vai e Vem');
        $date = date('Y-m-d', strtotime('+1 day'));
        $body = ['_csrf' => Security::csrfToken(), 'championship_id' => $championshipId, 'athlete_id' => (int) $athleteRow['id'], 'previous_team_id' => (int) $teamRows[0]['id'], 'new_team_id' => (int) $teamRows[1]['id'], 'type' => 'contratacao', 'movement_date' => $date, 'public_observation' => 'Anuncio seguro', 'internal_notes' => 'Segredo editorial', 'status' => 'pending'];
        assert_same(302, $router->dispatch(Request::fake('POST', '/copa-online/admin/vai-e-vem', $body))->status, 'Criacao de movimentacao falhou');
        $id = (int) $pdo->query("SELECT id FROM transfer_movements WHERE type = 'contratacao' AND movement_date = '{$date}'")->fetchColumn();
        assert_true($id > 0, 'Movimento HTTP nao persistido');
        assert_same(403, $router->dispatch(Request::fake('POST', '/copa-online/admin/vai-e-vem/' . $id . '/aprovar', ['_csrf' => 'invalid']))->status, 'CSRF de transferencia aceito');
        assert_same(302, $router->dispatch(Request::fake('POST', '/copa-online/admin/vai-e-vem/' . $id . '/aprovar', ['_csrf' => Security::csrfToken()]))->status, 'Aprovacao HTTP falhou');
        assert_same(302, $router->dispatch(Request::fake('POST', '/copa-online/admin/vai-e-vem/' . $id . '/publicar', ['_csrf' => Security::csrfToken()]))->status, 'Publicacao HTTP falhou');
        $public = $router->dispatch(new Request('GET', '/copa-online/campeonatos/' . $slug . '/vai-e-vem', ['q' => 'Anuncio']));
        assert_same(200, $public->status, 'Portal de Vai e Vem nao abriu');
        assert_true(str_contains($public->body, 'Anuncio seguro') && !str_contains($public->body, 'Segredo editorial'), 'Conteudo privado vazou no portal');
        $photoPath = (string) $pdo->query('SELECT photo_path FROM athletes WHERE id = ' . (int) $athleteRow['id'])->fetchColumn();
        $photoResponse = $router->dispatch(Request::fake('GET', '/copa-online/campeonatos/' . $slug . '/vai-e-vem/' . $id . '/foto'));
        assert_same($photoPath === '' ? 404 : 200, $photoResponse->status, 'Rota de foto publica respondeu incorretamente');

        self::logout($router);
        self::login($router, 'treinador@torneios.local');
        assert_same(403, $router->dispatch(Request::fake('GET', '/copa-online/admin/vai-e-vem'))->status, 'Treinador acessou transferencias');
        self::logout($router);
        self::login($router, 'organizador-sem-acesso@torneios.local');
        assert_same(403, $router->dispatch(Request::fake('GET', '/copa-online/admin/vai-e-vem/' . $id))->status, 'IDOR de transferencia aceito');
        self::logout($router);
    }

    private static function login(Router $router, string $email): void { $response = $router->dispatch(Request::fake('POST', '/copa-online/login', ['_csrf' => Security::csrfToken(), 'email' => $email, 'password' => getenv('SEED_DEMO_PASSWORD') ?: 'TestDemo123'])); assert_same(302, $response->status, 'Login de transferencia falhou'); assert_true(Auth::authenticated(), 'Sessao de transferencia nao criada'); }
    private static function logout(Router $router): void { $router->dispatch(Request::fake('POST', '/copa-online/logout', ['_csrf' => Security::csrfToken()])); Session::destroy(); }
}
