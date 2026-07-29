<?php
declare(strict_types=1);

namespace Tests\Http;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Core\Security;
use App\Core\Session;
use App\Database\AuthSeed;
use App\Database\ChampionshipSeed;
use function Tests\assert_same;
use function Tests\assert_true;

final class ChampionshipHttpTest
{
    public static function run(): void
    {
        $pdo = Database::connection();
        AuthSeed::run($pdo, getenv('SEED_DEMO_PASSWORD') ?: 'TestDemo123');
        ChampionshipSeed::run($pdo);
        /** @var Router $router */
        $router = require dirname(__DIR__, 2) . '/routes/web.php';
        $seasonId = (int) $pdo->query("SELECT id FROM seasons WHERE name = 'Temporada 2026' LIMIT 1")->fetchColumn();
        $categoryId = (int) $pdo->query("SELECT id FROM categories WHERE slug = 'sub-15-masculino' LIMIT 1")->fetchColumn();
        Session::destroy();
        self::login($router, 'admin@torneios.local');
        $list = $router->dispatch(Request::fake('GET', '/torneio-online/admin/campeonatos'));
        assert_same(200, $list->status, 'Administrador nao abriu campeonatos');
        assert_true(str_contains($list->body, 'Copa Brasil de Talentos 2026'), 'Campeonato seed nao apareceu');
        $season = $router->dispatch(Request::fake('GET', '/torneio-online/admin/temporadas'));
        assert_same(200, $season->status, 'Temporadas nao abriram');
        $newSeason = $router->dispatch(Request::fake('POST', '/torneio-online/admin/temporadas', ['_csrf' => Security::csrfToken(), 'name' => 'Temporada HTTP', 'year' => '2027', 'starts_at' => '2027-01-01', 'ends_at' => '2027-12-31', 'status' => 'draft']));
        assert_same(302, $newSeason->status, 'Administrador nao criou temporada');
        $httpSeasonId = (int) $pdo->query("SELECT id FROM seasons WHERE name = 'Temporada HTTP' LIMIT 1")->fetchColumn();
        assert_true($httpSeasonId > 0, 'Temporada HTTP nao foi persistida');
        $newCategory = $router->dispatch(Request::fake('POST', '/torneio-online/admin/categorias', ['_csrf' => Security::csrfToken(), 'name' => 'Categoria HTTP', 'slug' => 'categoria-http', 'description' => '', 'minimum_age' => '', 'maximum_age' => '', 'gender_rule' => '', 'status' => 'active']));
        assert_same(302, $newCategory->status, 'Administrador nao criou categoria');
        $httpCategoryId = (int) $pdo->query("SELECT id FROM categories WHERE slug = 'categoria-http' LIMIT 1")->fetchColumn();
        assert_true($httpCategoryId > 0, 'Categoria HTTP nao foi persistida');
        $csrf = Security::csrfToken();
        $invalid = $router->dispatch(Request::fake('POST', '/torneio-online/admin/campeonatos', ['_csrf' => $csrf, 'name' => 'Incompleto']));
        assert_same(422, $invalid->status, 'Formulario invalido de campeonato foi aceito');
        $adminCreated = $router->dispatch(Request::fake('POST', '/torneio-online/admin/campeonatos', ['_csrf' => Security::csrfToken(), 'name' => 'Campeonato HTTP Admin', 'short_name' => 'HTTP Admin', 'slug' => 'campeonato-http-admin', 'description' => 'Teste de criacao.', 'season_id' => $httpSeasonId, 'category_id' => $httpCategoryId, 'starts_at' => '2027-03-01', 'ends_at' => '2027-04-01', 'registration_starts_at' => '2027-02-01', 'registration_ends_at' => '2027-02-20', 'visibility' => 'private']));
        assert_same(302, $adminCreated->status, 'Administrador nao criou campeonato');
        assert_true((int) $pdo->query("SELECT id FROM championships WHERE slug = 'campeonato-http-admin' LIMIT 1")->fetchColumn() > 0, 'Campeonato HTTP admin nao foi persistido');
        $logout = $router->dispatch(Request::fake('POST', '/torneio-online/logout', ['_csrf' => Security::csrfToken()]));
        assert_same(302, $logout->status, 'Logout do admin falhou');
        self::login($router, 'organizador@torneios.local');
        $assigned = $router->dispatch(Request::fake('GET', '/torneio-online/admin/campeonatos/copa-brasil-de-talentos-2026'));
        assert_same(200, $assigned->status, 'Organizador atribuido nao abriu campeonato');
        assert_same(200, $router->dispatch(Request::fake('GET', '/torneio-online/admin/campeonatos/copa-brasil-de-talentos-2026/regulamento'))->status, 'Organizador nao abriu resumo do regulamento');
        assert_same(200, $router->dispatch(Request::fake('GET', '/torneio-online/admin/campeonatos/copa-brasil-de-talentos-2026/regulamento/editar'))->status, 'Organizador nao abriu editor do regulamento');
        $preset = $router->dispatch(Request::fake('POST', '/torneio-online/admin/campeonatos/copa-brasil-de-talentos-2026/regulamento/preset', ['_csrf' => Security::csrfToken()]));
        assert_same(302, $preset->status, 'Organizador nao aplicou preset');
        $save = $router->dispatch(Request::fake('POST', '/torneio-online/admin/campeonatos/copa-brasil-de-talentos-2026/regulamento', ['_csrf' => Security::csrfToken(), 'name' => 'Regulamento HTTP', 'effective_from' => '2026-03-01', 'group_count' => '2', 'teams_per_group' => '5', 'qualified_per_group' => '4', 'group_rounds' => 'single', 'knockout_starts_at' => 'quarterfinals', 'final_format' => 'single_match', 'points_win' => '3', 'points_draw' => '1', 'points_loss' => '0', 'wo_winner_goals' => '3', 'wo_loser_goals' => '0', 'yellow_cards_for_suspension' => '2', 'yellow_suspension_matches' => '1', 'red_card_automatic_suspension' => 'on', 'red_card_suspension_matches' => '1', 'reset_cards_stage' => '', 'regular_time_minutes' => '40', 'halftime_minutes' => '10', 'substitutions_allowed' => '5', 'substitution_windows' => '3', 'extra_time_minutes' => '10', 'tiebreakers' => ['wins' => ['enabled' => 'on', 'priority' => '1'], 'goal_difference' => ['enabled' => 'on', 'priority' => '2']]]));
        assert_same(302, $save->status, 'Organizador nao salvou rascunho');
        assert_same(200, $router->dispatch(Request::fake('GET', '/torneio-online/admin/campeonatos/copa-brasil-de-talentos-2026/regulamento/revisar'))->status, 'Revisao do regulamento nao abriu');
        $publish = $router->dispatch(Request::fake('POST', '/torneio-online/admin/campeonatos/copa-brasil-de-talentos-2026/regulamento/publicar', ['_csrf' => Security::csrfToken()]));
        assert_same(302, $publish->status, 'Organizador nao publicou regulamento');
        assert_same(200, $router->dispatch(Request::fake('GET', '/torneio-online/admin/campeonatos/copa-brasil-de-talentos-2026/regulamento/versoes'))->status, 'Historico de regulamentos nao abriu');
        $tmp = tempnam(sys_get_temp_dir(), 'mvp-http-logo-');
        file_put_contents($tmp, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
        $identity = $router->dispatch(Request::fake('POST', '/torneio-online/admin/campeonatos/copa-brasil-de-talentos-2026/identidade', ['_csrf' => Security::csrfToken(), 'default_theme' => 'light', 'primary_color' => '#123C32', 'secondary_color' => '#245C4A', 'accent_color' => '#D9A441'], ['logo_path' => ['error' => UPLOAD_ERR_OK, 'size' => filesize($tmp), 'tmp_name' => $tmp, 'name' => 'logo.png']]));
        assert_same(302, $identity->status, 'Upload HTTP valido foi rejeitado');
        $storedPath = (string) $pdo->query("SELECT logo_path FROM championships WHERE slug = 'copa-brasil-de-talentos-2026'")->fetchColumn();
        assert_true($storedPath !== '', 'Logo HTTP nao foi persistido');
        assert_same(200, $router->dispatch(Request::fake('GET', '/torneio-online/admin/campeonatos/copa-brasil-de-talentos-2026/assets/logo_path'))->status, 'Asset privado nao foi servido');
        @unlink(dirname(__DIR__, 2) . '/storage/private/' . $storedPath);
        @unlink($tmp);
        $invalidFile = tempnam(sys_get_temp_dir(), 'mvp-http-invalid-');
        file_put_contents($invalidFile, 'arquivo invalido');
        $invalidUpload = $router->dispatch(Request::fake('POST', '/torneio-online/admin/campeonatos/copa-brasil-de-talentos-2026/identidade', ['_csrf' => Security::csrfToken(), 'default_theme' => 'light', 'primary_color' => '#123C32', 'secondary_color' => '#245C4A', 'accent_color' => '#D9A441'], ['logo_path' => ['error' => UPLOAD_ERR_OK, 'size' => filesize($invalidFile), 'tmp_name' => $invalidFile, 'name' => 'logo.png']]));
        assert_same(422, $invalidUpload->status, 'Upload HTTP invalido foi aceito');
        @unlink($invalidFile);
        $organizerCreated = $router->dispatch(Request::fake('POST', '/torneio-online/admin/campeonatos', ['_csrf' => Security::csrfToken(), 'name' => 'Campeonato HTTP Organizador', 'short_name' => 'HTTP Org', 'slug' => 'campeonato-http-organizador', 'description' => 'Teste de escopo.', 'season_id' => $seasonId, 'category_id' => $categoryId, 'starts_at' => '2026-09-01', 'ends_at' => '2026-10-01', 'registration_starts_at' => '2026-08-01', 'registration_ends_at' => '2026-08-20', 'visibility' => 'private']));
        assert_same(302, $organizerCreated->status, 'Organizador nao criou campeonato');
        $organizerCreatedId = (int) $pdo->query("SELECT id FROM championships WHERE slug = 'campeonato-http-organizador' LIMIT 1")->fetchColumn();
        assert_same(1, (int) $pdo->query("SELECT COUNT(*) FROM championship_user_assignments WHERE championship_id = {$organizerCreatedId} AND user_id = (SELECT id FROM users WHERE email = 'organizador@torneios.local') AND assignment_type = 'organizer'")->fetchColumn(), 'Organizador criador nao recebeu vinculo automatico');
        assert_same(403, $router->dispatch(Request::fake('GET', '/torneio-online/admin/campeonatos/campeonato-http-admin'))->status, 'Organizador acessou campeonato sem vinculo');
        $logout = $router->dispatch(Request::fake('POST', '/torneio-online/logout', ['_csrf' => Security::csrfToken()]));
        assert_same(302, $logout->status, 'Logout do organizador falhou');
        self::login($router, 'organizador-sem-acesso@torneios.local');
        $denied = $router->dispatch(Request::fake('GET', '/torneio-online/admin/campeonatos/copa-brasil-de-talentos-2026'));
        assert_same(403, $denied->status, 'Organizador sem vinculo recebeu campeonato');
        $logout = $router->dispatch(Request::fake('POST', '/torneio-online/logout', ['_csrf' => Security::csrfToken()]));
        assert_same(302, $logout->status, 'Logout do organizador externo falhou');
        self::login($router, 'treinador@torneios.local');
        $trainer = $router->dispatch(Request::fake('GET', '/torneio-online/admin/campeonatos'));
        assert_same(403, $trainer->status, 'Treinador acessou modulo de campeonatos');
        $logout = $router->dispatch(Request::fake('POST', '/torneio-online/logout', ['_csrf' => Security::csrfToken()]));
        assert_same(302, $logout->status, 'Logout do treinador falhou');
        self::login($router, 'operador@torneios.local');
        assert_same(403, $router->dispatch(Request::fake('GET', '/torneio-online/admin/campeonatos/copa-brasil-de-talentos-2026/regulamento'))->status, 'Operador acessou regulamento');
        $logout = $router->dispatch(Request::fake('POST', '/torneio-online/logout', ['_csrf' => Security::csrfToken()]));
        assert_same(302, $logout->status, 'Logout do operador falhou');
        self::login($router, 'comunicacao@torneios.local');
        assert_same(403, $router->dispatch(Request::fake('GET', '/torneio-online/admin/campeonatos/copa-brasil-de-talentos-2026/regulamento'))->status, 'Comunicacao acessou regulamento');
    }

    private static function login(Router $router, string $email): void
    {
        $csrf = Security::csrfToken();
        $response = $router->dispatch(Request::fake('POST', '/torneio-online/login', ['_csrf' => $csrf, 'email' => $email, 'password' => getenv('SEED_DEMO_PASSWORD') ?: 'TestDemo123']));
        assert_same(302, $response->status, 'Login de teste falhou para ' . $email);
        assert_true(Auth::authenticated(), 'Sessao nao criada para ' . $email);
    }
}
