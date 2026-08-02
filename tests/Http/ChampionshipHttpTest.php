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
use App\Repositories\UserRepository;
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
        assert_same(4, count((new UserRepository($pdo))->rolesCatalog()), 'Catalogo de perfis nao foi consolidado');
        $accountabilityPage = $router->dispatch(Request::fake('GET', '/torneio-online/admin/campeonatos/copa-brasil-de-talentos-2026/prestacao'));
        assert_same(200, $accountabilityPage->status, 'Administrador nao abriu vinculos de prestacao de contas');
        $accountabilityReport = $router->dispatch(Request::fake('GET', '/torneio-online/prestacao/campeonatos/1'));
        assert_same(200, $accountabilityReport->status, 'Administrador nao abriu o relatorio de prestacao');
        assert_true(str_contains($accountabilityPage->body, 'Usuários vinculados') && str_contains($accountabilityPage->body, 'Prestação de contas'), 'Tela de prestacao de contas nao foi renderizada');
        $accountabilityUserId = (int) $pdo->query("SELECT id FROM users WHERE email = 'prestacao@torneios.local' LIMIT 1")->fetchColumn();
        $unassign = $router->dispatch(Request::fake('POST', '/torneio-online/admin/campeonatos/copa-brasil-de-talentos-2026/prestacao/' . $accountabilityUserId . '/encerrar', ['_csrf' => Security::csrfToken()]));
        assert_same(302, $unassign->status, 'Administrador nao encerrou vinculo de prestacao');
        $assign = $router->dispatch(Request::fake('POST', '/torneio-online/admin/campeonatos/copa-brasil-de-talentos-2026/prestacao', ['_csrf' => Security::csrfToken(), 'user_id' => $accountabilityUserId]));
        assert_same(302, $assign->status, 'Administrador nao criou vinculo de prestacao');
        assert_same(1, (int) $pdo->query("SELECT COUNT(*) FROM championship_user_assignments WHERE assignment_type = 'accountability' AND user_id = {$accountabilityUserId}")->fetchColumn(), 'Vinculo de prestacao nao foi persistido');
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
        self::login($router, 'admin@torneios.local');
        $assigned = $router->dispatch(Request::fake('GET', '/torneio-online/admin/campeonatos/copa-brasil-de-talentos-2026'));
        assert_same(200, $assigned->status, 'Administrador nao abriu campeonato');
        assert_same(200, $router->dispatch(Request::fake('GET', '/torneio-online/admin/campeonatos/copa-brasil-de-talentos-2026/regulamento'))->status, 'Administrador nao abriu resumo do regulamento');
        assert_same(200, $router->dispatch(Request::fake('GET', '/torneio-online/admin/campeonatos/copa-brasil-de-talentos-2026/regulamento/editar'))->status, 'Administrador nao abriu editor do regulamento');
        $preset = $router->dispatch(Request::fake('POST', '/torneio-online/admin/campeonatos/copa-brasil-de-talentos-2026/regulamento/preset', ['_csrf' => Security::csrfToken()]));
        assert_same(302, $preset->status, 'Administrador nao aplicou preset');
        $save = $router->dispatch(Request::fake('POST', '/torneio-online/admin/campeonatos/copa-brasil-de-talentos-2026/regulamento', ['_csrf' => Security::csrfToken(), 'name' => 'Regulamento HTTP', 'effective_from' => '2026-03-01', 'group_count' => '2', 'teams_per_group' => '5', 'qualified_per_group' => '4', 'group_rounds' => 'single', 'knockout_starts_at' => 'quarterfinals', 'final_format' => 'single_match', 'points_win' => '3', 'points_draw' => '1', 'points_loss' => '0', 'wo_winner_goals' => '3', 'wo_loser_goals' => '0', 'yellow_cards_for_suspension' => '2', 'yellow_suspension_matches' => '1', 'red_card_automatic_suspension' => 'on', 'red_card_suspension_matches' => '1', 'reset_cards_stage' => '', 'regular_time_minutes' => '40', 'halftime_minutes' => '10', 'substitutions_allowed' => '5', 'substitution_windows' => '3', 'extra_time_minutes' => '10', 'tiebreakers' => ['wins' => ['enabled' => 'on', 'priority' => '1'], 'goal_difference' => ['enabled' => 'on', 'priority' => '2']]]));
        assert_same(302, $save->status, 'Administrador nao salvou rascunho');
        assert_same(200, $router->dispatch(Request::fake('GET', '/torneio-online/admin/campeonatos/copa-brasil-de-talentos-2026/regulamento/revisar'))->status, 'Revisao do regulamento nao abriu');
        $publish = $router->dispatch(Request::fake('POST', '/torneio-online/admin/campeonatos/copa-brasil-de-talentos-2026/regulamento/publicar', ['_csrf' => Security::csrfToken()]));
        assert_same(302, $publish->status, 'Administrador nao publicou regulamento');
        assert_same(200, $router->dispatch(Request::fake('GET', '/torneio-online/admin/campeonatos/copa-brasil-de-talentos-2026/regulamento/versoes'))->status, 'Historico de regulamentos nao abriu');
        $identityPage = $router->dispatch(Request::fake('GET', '/torneio-online/admin/campeonatos/copa-brasil-de-talentos-2026/identidade'));
        assert_same(200, $identityPage->status, 'Administrador nao abriu identidade do campeonato');
        assert_true(str_contains($identityPage->body, 'data-color-field') && str_contains($identityPage->body, 'Paleta do campeonato'), 'Seletor visual de cores nao foi renderizado');
        assert_true(str_contains($identityPage->body, 'data-file-state') && str_contains($identityPage->body, '1920 × 720 px'), 'Estado de arquivos e orientação do banner nao foram renderizados');
        $tmp = tempnam(sys_get_temp_dir(), 'mvp-http-logo-');
        file_put_contents($tmp, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
        $identity = $router->dispatch(Request::fake('POST', '/torneio-online/admin/campeonatos/copa-brasil-de-talentos-2026/identidade', ['_csrf' => Security::csrfToken(), 'default_theme' => 'light', 'primary_color' => '#123C32', 'secondary_color' => '#245C4A', 'accent_color' => '#D9A441'], ['logo_path' => ['error' => UPLOAD_ERR_OK, 'size' => filesize($tmp), 'tmp_name' => $tmp, 'name' => 'logo.png']]));
        assert_same(302, $identity->status, 'Upload HTTP valido foi rejeitado');
        $storedPath = (string) $pdo->query("SELECT logo_path FROM championships WHERE slug = 'copa-brasil-de-talentos-2026'")->fetchColumn();
        assert_true($storedPath !== '', 'Logo HTTP nao foi persistido');
        $identityWithAsset = $router->dispatch(Request::fake('GET', '/torneio-online/admin/campeonatos/copa-brasil-de-talentos-2026/identidade'));
        assert_true(str_contains($identityWithAsset->body, 'Arquivo atual será mantido') && str_contains($identityWithAsset->body, 'Arquivo atual em uso'), 'Arquivo existente nao apareceu de forma compreensivel');
        assert_same(200, $router->dispatch(Request::fake('GET', '/torneio-online/admin/campeonatos/copa-brasil-de-talentos-2026/assets/logo_path'))->status, 'Asset privado nao foi servido');
        @unlink(dirname(__DIR__, 2) . '/storage/private/' . $storedPath);
        @unlink($tmp);
        $invalidFile = tempnam(sys_get_temp_dir(), 'mvp-http-invalid-');
        file_put_contents($invalidFile, 'arquivo invalido');
        $invalidUpload = $router->dispatch(Request::fake('POST', '/torneio-online/admin/campeonatos/copa-brasil-de-talentos-2026/identidade', ['_csrf' => Security::csrfToken(), 'default_theme' => 'light', 'primary_color' => '#123C32', 'secondary_color' => '#245C4A', 'accent_color' => '#D9A441'], ['logo_path' => ['error' => UPLOAD_ERR_OK, 'size' => filesize($invalidFile), 'tmp_name' => $invalidFile, 'name' => 'logo.png']]));
        assert_same(422, $invalidUpload->status, 'Upload HTTP invalido foi aceito');
        @unlink($invalidFile);
        $secondCreated = $router->dispatch(Request::fake('POST', '/torneio-online/admin/campeonatos', ['_csrf' => Security::csrfToken(), 'name' => 'Campeonato HTTP Dois', 'short_name' => 'HTTP Dois', 'slug' => 'campeonato-http-dois', 'description' => 'Teste de criacao adicional.', 'season_id' => $seasonId, 'category_id' => $categoryId, 'starts_at' => '2026-09-01', 'ends_at' => '2026-10-01', 'registration_starts_at' => '2026-08-01', 'registration_ends_at' => '2026-08-20', 'visibility' => 'private']));
        assert_same(302, $secondCreated->status, 'Administrador nao criou segundo campeonato');
        assert_true((int) $pdo->query("SELECT id FROM championships WHERE slug = 'campeonato-http-dois' LIMIT 1")->fetchColumn() > 0, 'Segundo campeonato HTTP nao foi persistido');
        $logout = $router->dispatch(Request::fake('POST', '/torneio-online/logout', ['_csrf' => Security::csrfToken()]));
        assert_same(302, $logout->status, 'Logout do administrador falhou');
        self::login($router, 'treinador@torneios.local');
        $trainer = $router->dispatch(Request::fake('GET', '/torneio-online/admin/campeonatos'));
        assert_same(403, $trainer->status, 'Treinador acessou modulo de campeonatos');
        $logout = $router->dispatch(Request::fake('POST', '/torneio-online/logout', ['_csrf' => Security::csrfToken()]));
        assert_same(302, $logout->status, 'Logout do treinador falhou');
        self::login($router, 'prestacao@torneios.local');
        $accountability = $router->dispatch(Request::fake('GET', '/torneio-online/prestacao'));
        assert_same(200, $accountability->status, 'Usuario de prestacao nao abriu o painel');
        assert_true(str_contains($accountability->body, 'Copa Brasil de Talentos 2026'), 'Campeonato autorizado nao apareceu na prestacao');
        $logout = $router->dispatch(Request::fake('POST', '/torneio-online/logout', ['_csrf' => Security::csrfToken()]));
        assert_same(302, $logout->status, 'Logout da prestacao falhou');
        self::login($router, 'operador@torneios.local');
        assert_same(403, $router->dispatch(Request::fake('GET', '/torneio-online/admin/campeonatos/copa-brasil-de-talentos-2026/regulamento'))->status, 'Operador acessou regulamento');
        assert_same(403, $router->dispatch(Request::fake('GET', '/torneio-online/admin/campeonatos/copa-brasil-de-talentos-2026/prestacao'))->status, 'Operador acessou vinculos de prestacao');
        $logout = $router->dispatch(Request::fake('POST', '/torneio-online/logout', ['_csrf' => Security::csrfToken()]));
        assert_same(302, $logout->status, 'Logout do operador falhou');
    }

    private static function login(Router $router, string $email): void
    {
        $csrf = Security::csrfToken();
        $response = $router->dispatch(Request::fake('POST', '/torneio-online/login', ['_csrf' => $csrf, 'email' => $email, 'password' => getenv('SEED_DEMO_PASSWORD') ?: 'TestDemo123']));
        assert_same(302, $response->status, 'Login de teste falhou para ' . $email);
        assert_true(Auth::authenticated(), 'Sessao nao criada para ' . $email);
    }
}
