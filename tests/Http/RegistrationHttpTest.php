<?php
declare(strict_types=1);

namespace Tests\Http;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Core\Security;
use App\Core\Session;
use App\Database\RegistrationSeed;
use function Tests\assert_same;
use function Tests\assert_true;

final class RegistrationHttpTest
{
    public static function run(): void
    {
        $pdo = Database::connection();
        RegistrationSeed::run($pdo);
        /** @var Router $router */
        $router = require dirname(__DIR__, 2) . '/routes/web.php';
        $championshipId = (int) $pdo->query("SELECT id FROM championships WHERE slug = 'copa-brasil-de-talentos-2026'")->fetchColumn();
        $trainerId = (int) $pdo->query("SELECT id FROM users WHERE email = 'treinador@torneios.local'")->fetchColumn();
        $target = $pdo->query("SELECT a.id, a.team_id, a.sporting_name FROM athletes a INNER JOIN team_user_assignments tua ON tua.team_id = a.team_id AND tua.user_id = {$trainerId} AND tua.status = 'active' WHERE a.deleted_at IS NULL AND NOT EXISTS (SELECT 1 FROM athlete_registrations ar WHERE ar.championship_id = {$championshipId} AND ar.athlete_id = a.id) ORDER BY a.id LIMIT 1")->fetch();
        assert_true($target !== false, 'Atleta HTTP sem inscricao nao encontrado');
        $documentId = (int) $pdo->query("SELECT d.id FROM athlete_documents d INNER JOIN athlete_document_types dt ON dt.id = d.document_type_id WHERE d.athlete_id = {$target['id']} AND dt.`key` = 'guardian_authorization' LIMIT 1")->fetchColumn();
        $pdo->prepare("UPDATE athlete_documents SET status = 'approved', rejection_reason = NULL WHERE id = ?")->execute([$documentId]);
        Session::destroy();

        self::login($router, 'treinador@torneios.local');
        assert_same(200, $router->dispatch(Request::fake('GET', '/copa-online/admin/inscricoes'))->status, 'Treinador nao abriu central de inscricoes');
        assert_same(200, $router->dispatch(Request::fake('GET', '/copa-online/admin/inscricoes/elenco'))->status, 'Treinador nao abriu elenco oficial');
        assert_same(200, $router->dispatch(Request::fake('GET', '/copa-online/admin/inscricoes/nova'))->status, 'Formulario de inscricao nao abriu');
        $created = $router->dispatch(Request::fake('POST', '/copa-online/admin/inscricoes', ['_csrf' => Security::csrfToken(), 'championship_id' => $championshipId, 'team_id' => $target['team_id'], 'athlete_id' => $target['id'], 'requested_number' => '44', 'observations' => 'Inscricao HTTP']));
        assert_same(302, $created->status, 'Treinador nao criou inscricao');
        $registrationId = (int) $pdo->query("SELECT id FROM athlete_registrations WHERE championship_id = {$championshipId} AND athlete_id = {$target['id']} LIMIT 1")->fetchColumn();
        assert_true($registrationId > 0, 'Inscricao HTTP nao persistida');
        assert_same(200, $router->dispatch(Request::fake('GET', '/copa-online/admin/inscricoes/' . $registrationId))->status, 'Detalhe da inscricao nao abriu');
        assert_same(302, $router->dispatch(Request::fake('POST', '/copa-online/admin/inscricoes/' . $registrationId . '/enviar', ['_csrf' => Security::csrfToken()]))->status, 'Envio HTTP falhou');
        self::logout($router);

        self::login($router, 'organizador@torneios.local');
        assert_same(302, $router->dispatch(Request::fake('POST', '/copa-online/admin/inscricoes/' . $registrationId . '/iniciar-analise', ['_csrf' => Security::csrfToken()]))->status, 'Organizador nao iniciou analise');
        assert_same(302, $router->dispatch(Request::fake('POST', '/copa-online/admin/inscricoes/' . $registrationId . '/aprovar', ['_csrf' => Security::csrfToken()]))->status, 'Organizador nao aprovou inscricao');
        $roster = $router->dispatch(Request::fake('GET', '/copa-online/admin/inscricoes/elenco'));
        assert_same(200, $roster->status, 'Elenco HTTP nao abriu apos aprovacao');
        assert_true((int) $pdo->query("SELECT COUNT(*) FROM athlete_registrations WHERE id = {$registrationId} AND status = 'approved'")->fetchColumn() === 1, 'Aprovacao HTTP nao persistiu');
        assert_true(str_contains($roster->body, (string) $target['sporting_name']), 'Elenco HTTP nao exibiu atleta aprovado');
        assert_same(403, $router->dispatch(Request::fake('POST', '/copa-online/admin/inscricoes/' . $registrationId . '/rejeitar', ['_csrf' => 'invalid', 'rejection_reason' => 'x']))->status, 'CSRF de inscricao foi aceito');
        self::logout($router);

        $foreignId = (int) $pdo->query("SELECT id FROM athlete_registrations WHERE team_id NOT IN (SELECT team_id FROM team_user_assignments WHERE user_id = {$trainerId} AND status = 'active') LIMIT 1")->fetchColumn();
        self::login($router, 'treinador@torneios.local');
        assert_same(403, $router->dispatch(Request::fake('GET', '/copa-online/admin/inscricoes/' . $foreignId))->status, 'Treinador acessou inscricao de outra equipe');
        self::logout($router);
        self::login($router, 'operador@torneios.local');
        assert_same(403, $router->dispatch(Request::fake('GET', '/copa-online/admin/inscricoes'))->status, 'Operador acessou inscricoes');
        self::logout($router);
        self::login($router, 'comunicacao@torneios.local');
        assert_same(403, $router->dispatch(Request::fake('GET', '/copa-online/admin/inscricoes'))->status, 'Comunicacao acessou inscricoes');
        self::logout($router);
    }

    private static function login(Router $router, string $email): void
    {
        $response = $router->dispatch(Request::fake('POST', '/copa-online/login', ['_csrf' => Security::csrfToken(), 'email' => $email, 'password' => getenv('SEED_DEMO_PASSWORD') ?: 'TestDemo123']));
        assert_same(302, $response->status, 'Login de inscricao falhou para ' . $email);
        assert_true(Auth::authenticated(), 'Sessao de inscricao nao criada');
    }

    private static function logout(Router $router): void
    {
        assert_same(302, $router->dispatch(Request::fake('POST', '/copa-online/logout', ['_csrf' => Security::csrfToken()]))->status, 'Logout de inscricao falhou');
    }
}
