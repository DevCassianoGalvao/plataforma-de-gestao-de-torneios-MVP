<?php
declare(strict_types=1);

namespace Tests\Http;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Core\Security;
use App\Core\Session;
use App\Database\AuthSeed;
use App\Database\ChampionshipSeed;
use App\Database\TacticalFormationSeed;
use App\Database\TeamSeed;
use App\Repositories\ChampionshipRepository;
use App\Repositories\TeamRepository;
use function Tests\assert_same;
use function Tests\assert_true;

final class TeamHttpTest
{
    public static function run(): void
    {
        $pdo = Database::connection();
        $password = getenv('SEED_DEMO_PASSWORD') ?: 'TestDemo123';
        AuthSeed::run($pdo, $password);
        ChampionshipSeed::run($pdo);
        TacticalFormationSeed::run($pdo);
        TeamSeed::run($pdo);
        /** @var Router $router */
        $router = require dirname(__DIR__, 2) . '/routes/web.php';
        $championshipId = (int) $pdo->query("SELECT id FROM championships WHERE slug = 'copa-brasil-de-talentos-2026' LIMIT 1")->fetchColumn();
        $seasonId = (int) $pdo->query("SELECT season_id FROM championships WHERE id = {$championshipId}")->fetchColumn();
        $categoryId = (int) $pdo->query("SELECT category_id FROM championships WHERE id = {$championshipId}")->fetchColumn();
        $formationId = (int) $pdo->query("SELECT id FROM tactical_formations WHERE slug = '4-4-2' LIMIT 1")->fetchColumn();
        Session::destroy();
        self::login($router, 'admin@torneios.local');
        assert_same(200, $router->dispatch(Request::fake('GET', '/copa-online/admin/equipes'))->status, 'Administrador nao abriu equipes');
        $invalid = $router->dispatch(Request::fake('POST', '/copa-online/admin/equipes', ['_csrf' => Security::csrfToken(), 'name' => 'Incompleta']));
        assert_same(422, $invalid->status, 'Formulario invalido de equipe foi aceito');
        $created = $router->dispatch(Request::fake('POST', '/copa-online/admin/equipes', ['_csrf' => Security::csrfToken(), 'championship_id' => $championshipId, 'name' => 'Equipe HTTP Admin', 'short_name' => 'HTTP Admin', 'slug' => 'equipe-http-admin', 'abbreviation' => 'EHA', 'description' => 'Equipe criada no contrato HTTP.', 'city' => 'Sao Paulo', 'state' => 'SP', 'primary_color' => '#123C32', 'secondary_color' => '#D9A441', 'default_tactical_formation_id' => $formationId]));
        assert_same(302, $created->status, 'Administrador nao criou equipe');
        $teamId = (int) $pdo->query("SELECT id FROM teams WHERE slug = 'equipe-http-admin' LIMIT 1")->fetchColumn();
        assert_true($teamId > 0, 'Equipe HTTP nao foi persistida');
        $duplicate = $router->dispatch(Request::fake('POST', '/copa-online/admin/equipes', ['_csrf' => Security::csrfToken(), 'championship_id' => $championshipId, 'name' => 'Outra Equipe', 'short_name' => 'Outra', 'slug' => 'equipe-http-admin', 'abbreviation' => 'OUT', 'primary_color' => '#123C32', 'secondary_color' => '#D9A441']));
        assert_same(422, $duplicate->status, 'Slug duplicado de equipe foi aceito');

        $tmp = tempnam(sys_get_temp_dir(), 'mvp-team-http-');
        file_put_contents($tmp, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
        $identity = $router->dispatch(Request::fake('POST', '/copa-online/admin/equipes/equipe-http-admin/identidade', ['_csrf' => Security::csrfToken(), 'primary_color' => '#245C4A', 'secondary_color' => '#D9A441'], ['shield' => ['error' => UPLOAD_ERR_OK, 'size' => filesize($tmp), 'tmp_name' => $tmp, 'name' => 'escudo.png']]));
        assert_same(302, $identity->status, 'Upload de escudo valido foi rejeitado');
        assert_same(200, $router->dispatch(Request::fake('GET', '/copa-online/admin/equipes/equipe-http-admin/assets/shield_path'))->status, 'Escudo privado nao foi servido');
        $stored = (string) $pdo->query("SELECT shield_path FROM teams WHERE id = {$teamId}")->fetchColumn();
        if ($stored) @unlink(dirname(__DIR__, 2) . '/storage/private/' . $stored);
        @unlink($tmp);
        $invalidFile = tempnam(sys_get_temp_dir(), 'mvp-team-http-invalid-');
        file_put_contents($invalidFile, 'arquivo invalido');
        $invalidUpload = $router->dispatch(Request::fake('POST', '/copa-online/admin/equipes/equipe-http-admin/identidade', ['_csrf' => Security::csrfToken(), 'primary_color' => '#245C4A', 'secondary_color' => '#D9A441'], ['shield' => ['error' => UPLOAD_ERR_OK, 'size' => filesize($invalidFile), 'tmp_name' => $invalidFile, 'name' => 'escudo.png']]));
        assert_same(422, $invalidUpload->status, 'Upload invalido de escudo foi aceito');
        @unlink($invalidFile);

        $managerId = (int) $pdo->query("SELECT id FROM users WHERE email = 'gestor@torneios.local' LIMIT 1")->fetchColumn();
        $assigned = $router->dispatch(Request::fake('POST', '/copa-online/admin/equipes/equipe-http-admin/responsaveis', ['_csrf' => Security::csrfToken(), 'user_id' => $managerId, 'assignment_type' => 'manager', 'starts_at' => '2026-01-01']));
        assert_same(302, $assigned->status, 'Administrador nao atribuiu gestor');
        $assignmentId = (int) $pdo->query("SELECT id FROM team_user_assignments WHERE team_id = {$teamId} AND user_id = {$managerId} AND assignment_type = 'manager' LIMIT 1")->fetchColumn();
        $ended = $router->dispatch(Request::fake('POST', '/copa-online/admin/equipes/equipe-http-admin/responsaveis/' . $assignmentId . '/encerrar', ['_csrf' => Security::csrfToken(), 'ends_at' => '2026-02-01']));
        assert_same(302, $ended->status, 'Administrador nao encerrou vinculo');

        $roleId = (int) $pdo->query("SELECT id FROM staff_roles WHERE `key` = 'physical_trainer' LIMIT 1")->fetchColumn();
        $staffCreate = $router->dispatch(Request::fake('POST', '/copa-online/admin/equipes/equipe-http-admin/comissao', ['_csrf' => Security::csrfToken(), 'staff_role_id' => $roleId, 'full_name' => 'Preparador HTTP', 'display_name' => 'Prep HTTP', 'email' => 'prep@example.test', 'status' => 'active', 'starts_at' => '2026-01-01']));
        assert_same(302, $staffCreate->status, 'Administrador nao cadastrou comissao');
        $staffId = (int) $pdo->query("SELECT id FROM team_staff WHERE team_id = {$teamId} AND full_name = 'Preparador HTTP' LIMIT 1")->fetchColumn();
        assert_same(200, $router->dispatch(Request::fake('GET', '/copa-online/admin/equipes/equipe-http-admin/comissao/' . $staffId . '/editar'))->status, 'Edicao da comissao nao abriu');
        $staffUpdate = $router->dispatch(Request::fake('POST', '/copa-online/admin/equipes/equipe-http-admin/comissao/' . $staffId, ['_csrf' => Security::csrfToken(), 'staff_role_id' => $roleId, 'full_name' => 'Preparador HTTP Atualizado', 'display_name' => 'Prep Atualizado', 'email' => 'prep@example.test', 'status' => 'active', 'starts_at' => '2026-01-01']));
        assert_same(302, $staffUpdate->status, 'Edicao da comissao falhou');
        assert_same(302, $router->dispatch(Request::fake('POST', '/copa-online/admin/equipes/equipe-http-admin/comissao/' . $staffId . '/status', ['_csrf' => Security::csrfToken(), 'status' => 'inactive']))->status, 'Inativacao da comissao falhou');
        assert_same(302, $router->dispatch(Request::fake('POST', '/copa-online/admin/equipes/equipe-http-admin/formacao', ['_csrf' => Security::csrfToken(), 'formation_id' => $formationId]))->status, 'Formacao padrao nao foi salva');
        assert_same(302, $router->dispatch(Request::fake('POST', '/copa-online/admin/equipes/equipe-http-admin/status', ['_csrf' => Security::csrfToken(), 'status' => 'active']))->status, 'Ativacao de equipe falhou');
        assert_same(302, $router->dispatch(Request::fake('POST', '/copa-online/admin/equipes/equipe-http-admin/status', ['_csrf' => Security::csrfToken(), 'status' => 'inactive']))->status, 'Inativacao de equipe falhou');
        assert_same(302, $router->dispatch(Request::fake('POST', '/copa-online/admin/equipes/equipe-http-admin/restaurar', ['_csrf' => Security::csrfToken()]))->status, 'Restauracao de equipe falhou');

        $foreignChampionships = new ChampionshipRepository($pdo);
        $foreignId = $foreignChampionships->create(['name' => 'Campeonato HTTP Externo', 'short_name' => 'HTTP Externo', 'slug' => 'campeonato-http-externo', 'description' => '', 'season_id' => $seasonId, 'category_id' => $categoryId, 'starts_at' => '2026-09-01', 'ends_at' => '2026-10-01', 'registration_starts_at' => '2026-08-01', 'registration_ends_at' => '2026-08-20', 'status' => 'draft', 'visibility' => 'private', 'default_theme' => 'light', 'primary_color' => '#123C32', 'secondary_color' => '#245C4A', 'accent_color' => '#D9A441'], (int) $pdo->query("SELECT id FROM users WHERE email = 'admin@torneios.local'")->fetchColumn());
        $teamRepository = new TeamRepository($pdo);
        $teamRepository->create(['championship_id' => $foreignId, 'name' => 'Equipe Externa', 'short_name' => 'Externa', 'slug' => 'equipe-externa', 'abbreviation' => 'EXT', 'description' => '', 'city' => '', 'state' => '', 'primary_color' => '#123C32', 'secondary_color' => '#D9A441', 'status' => 'draft', 'default_tactical_formation_id' => $formationId], (int) $pdo->query("SELECT id FROM users WHERE email = 'admin@torneios.local'")->fetchColumn());
        $logout = $router->dispatch(Request::fake('POST', '/copa-online/logout', ['_csrf' => Security::csrfToken()]));
        assert_same(302, $logout->status, 'Logout do administrador falhou');
        self::login($router, 'organizador@torneios.local');
        $organizerTeam = $router->dispatch(Request::fake('POST', '/copa-online/admin/equipes', ['_csrf' => Security::csrfToken(), 'championship_id' => $championshipId, 'name' => 'Equipe HTTP Organizador', 'short_name' => 'HTTP Org', 'slug' => 'equipe-http-organizador', 'abbreviation' => 'EHO', 'primary_color' => '#123C32', 'secondary_color' => '#D9A441', 'default_tactical_formation_id' => $formationId]));
        assert_same(302, $organizerTeam->status, 'Organizador nao criou equipe autorizada');
        assert_same(200, $router->dispatch(Request::fake('GET', '/copa-online/admin/equipes/equipe-http-organizador'))->status, 'Organizador nao abriu equipe autorizada');
        assert_same(403, $router->dispatch(Request::fake('GET', '/copa-online/admin/equipes/equipe-externa'))->status, 'Organizador acessou equipe de campeonato alheio');
        assert_same(419, $router->dispatch(Request::fake('POST', '/copa-online/admin/equipes/equipe-http-organizador', ['_csrf' => 'invalid', 'name' => 'Alterada', 'short_name' => 'Alterada', 'slug' => 'alterada', 'abbreviation' => 'ALT', 'primary_color' => '#123C32', 'secondary_color' => '#D9A441']))->status, 'CSRF invalido de equipe foi aceito');
        assert_same(302, $router->dispatch(Request::fake('POST', '/copa-online/logout', ['_csrf' => Security::csrfToken()]))->status, 'Logout do organizador falhou');
        self::login($router, 'treinador@torneios.local');
        assert_same(200, $router->dispatch(Request::fake('GET', '/copa-online/admin/equipes/estrela-norte-fc'))->status, 'Treinador nao abriu propria equipe');
        assert_same(403, $router->dispatch(Request::fake('GET', '/copa-online/admin/equipes/serra-azul-futebol'))->status, 'Treinador abriu equipe alheia');
        assert_same(302, $router->dispatch(Request::fake('POST', '/copa-online/logout', ['_csrf' => Security::csrfToken()]))->status, 'Logout do treinador falhou');
        self::login($router, 'operador@torneios.local');
        assert_same(403, $router->dispatch(Request::fake('GET', '/copa-online/admin/equipes'))->status, 'Operador acessou equipes');
        assert_same(302, $router->dispatch(Request::fake('POST', '/copa-online/logout', ['_csrf' => Security::csrfToken()]))->status, 'Logout do operador falhou');
        self::login($router, 'comunicacao@torneios.local');
        assert_same(403, $router->dispatch(Request::fake('GET', '/copa-online/admin/equipes'))->status, 'Comunicacao acessou equipes');
        assert_true((int) $pdo->query("SELECT COUNT(*) FROM audit_logs WHERE action LIKE 'teams.%' OR action LIKE 'team_staff.%'")->fetchColumn() >= 6, 'Auditoria de equipes ausente');
    }

    private static function login(Router $router, string $email): void
    {
        $csrf = Security::csrfToken();
        $response = $router->dispatch(Request::fake('POST', '/copa-online/login', ['_csrf' => $csrf, 'email' => $email, 'password' => getenv('SEED_DEMO_PASSWORD') ?: 'TestDemo123']));
        assert_same(302, $response->status, 'Login de teste falhou para ' . $email);
        assert_true(Auth::authenticated(), 'Sessao nao criada para ' . $email);
    }
}
