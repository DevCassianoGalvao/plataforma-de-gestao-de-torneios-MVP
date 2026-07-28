<?php
declare(strict_types=1);

use App\Core\Database;
use App\Core\Router;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ChampionshipController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PlaceholderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegulationController;
use App\Http\Controllers\UserController;
use App\Repositories\CategoryRepository;
use App\Repositories\ChampionshipRepository;
use App\Repositories\RegulationRepository;
use App\Repositories\SeasonRepository;
use App\Repositories\UserRepository;
use App\Services\AuditService;
use App\Services\AuthService;
use App\Services\AuthorizationService;
use App\Services\ChampionshipAccessService;
use App\Services\ChampionshipStatusService;
use App\Services\MailService;
use App\Services\PasswordResetService;
use App\Services\RegulationService;
use App\Services\StorageService;

$router = new Router();
$pdo = Database::connection();
$users = new UserRepository($pdo);
$audit = new AuditService($pdo);
$authorization = new AuthorizationService($users);
$mail = new MailService();
$passwordReset = new PasswordResetService($pdo, $users, $audit, $mail);
$authService = new AuthService($pdo, $users, $audit);
$seasons = new SeasonRepository($pdo);
$categories = new CategoryRepository($pdo);
$championships = new ChampionshipRepository($pdo);
$regulationRepository = new RegulationRepository($pdo);
$access = new ChampionshipAccessService($championships, $authorization);
$regulationService = new RegulationService($pdo, $regulationRepository, $audit);
$statusService = new ChampionshipStatusService($championships, $regulationRepository);
$storage = new StorageService();

$router->get('/', [new HomeController(), 'index']);
$router->get('/health', [new HealthController(), 'show']);

$auth = new AuthController($users, $authorization, $audit, $authService, $passwordReset);
$router->get('/login', [$auth, 'login']);
$router->post('/login', [$auth, 'authenticate']);
$router->post('/logout', [$auth, 'logout']);
$router->get('/senha/esqueci', [$auth, 'forgot']);
$router->post('/senha/esqueci', [$auth, 'requestReset']);
$router->get('/senha/redefinir', [$auth, 'resetForm']);
$router->post('/senha/redefinir', [$auth, 'reset']);

$admin = new AdminController($users, $authorization, $audit);
$router->get('/admin', [$admin, 'dashboard']);

$userController = new UserController($users, $authorization, $audit, $passwordReset);
$router->get('/admin/usuarios', [$userController, 'index']);
$router->get('/admin/usuarios/novo', [$userController, 'createForm']);
$router->post('/admin/usuarios', [$userController, 'create']);
$router->get('/admin/usuarios/{id}/editar', [$userController, 'editForm']);
$router->post('/admin/usuarios/{id}', [$userController, 'update']);
$router->post('/admin/usuarios/{id}/status', [$userController, 'status']);
$router->post('/admin/usuarios/{id}/perfis', [$userController, 'roles']);
$router->post('/admin/usuarios/{id}/reset-password', [$userController, 'resetPassword']);

$profile = new ProfileController($users, $authorization, $audit);
$router->get('/admin/perfil', [$profile, 'show']);
$router->post('/admin/perfil', [$profile, 'update']);
$router->post('/admin/perfil/senha', [$profile, 'changePassword']);

$auditController = new AuditController($users, $authorization, $audit);
$router->get('/admin/auditoria', [$auditController, 'index']);

$catalog = new CatalogController($users, $authorization, $audit, $seasons, $categories);
$router->get('/admin/temporadas', [$catalog, 'seasons']);
$router->get('/admin/temporadas/nova', [$catalog, 'seasonForm']);
$router->post('/admin/temporadas', [$catalog, 'saveSeason']);
$router->get('/admin/temporadas/{id}/editar', [$catalog, 'seasonForm']);
$router->post('/admin/temporadas/{id}', [$catalog, 'saveSeason']);
$router->get('/admin/categorias', [$catalog, 'categories']);
$router->get('/admin/categorias/nova', [$catalog, 'categoryForm']);
$router->post('/admin/categorias', [$catalog, 'saveCategory']);
$router->get('/admin/categorias/{id}/editar', [$catalog, 'categoryForm']);
$router->post('/admin/categorias/{id}', [$catalog, 'saveCategory']);

$championship = new ChampionshipController($users, $authorization, $audit, $championships, $seasons, $categories, $access, $statusService, $regulationService, $storage, $users);
$router->get('/admin/campeonatos', [$championship, 'index']);
$router->get('/admin/campeonatos/novo', [$championship, 'createForm']);
$router->post('/admin/campeonatos', [$championship, 'create']);
$router->get('/admin/campeonatos/{slug}', [$championship, 'show']);
$router->get('/admin/campeonatos/{slug}/editar', [$championship, 'editForm']);
$router->post('/admin/campeonatos/{slug}', [$championship, 'update']);
$router->get('/admin/campeonatos/{slug}/identidade', [$championship, 'identityForm']);
$router->post('/admin/campeonatos/{slug}/identidade', [$championship, 'identity']);
$router->get('/admin/campeonatos/{slug}/assets/{field}', [$championship, 'asset']);
$router->post('/admin/campeonatos/{slug}/status', [$championship, 'status']);
$router->post('/admin/campeonatos/{slug}/arquivar', [$championship, 'archive']);
$router->get('/admin/campeonatos/{slug}/organizadores', [$championship, 'assignments']);
$router->post('/admin/campeonatos/{slug}/organizadores', [$championship, 'assign']);
$router->post('/admin/campeonatos/{slug}/organizadores/{userId}/remover', [$championship, 'unassign']);

$regulation = new RegulationController($users, $authorization, $audit, $championships, $regulationRepository, $access, $regulationService, $storage);
$router->get('/admin/campeonatos/{slug}/regulamento', [$regulation, 'show']);
$router->get('/admin/campeonatos/{slug}/regulamento/editar', [$regulation, 'edit']);
$router->post('/admin/campeonatos/{slug}/regulamento', [$regulation, 'save']);
$router->post('/admin/campeonatos/{slug}/regulamento/preset', [$regulation, 'preset']);
$router->post('/admin/campeonatos/{slug}/regulamento/documento', [$regulation, 'document']);
$router->get('/admin/campeonatos/{slug}/regulamento/documentos/{documentId}', [$regulation, 'documentAsset']);
$router->get('/admin/campeonatos/{slug}/regulamento/revisar', [$regulation, 'review']);
$router->post('/admin/campeonatos/{slug}/regulamento/publicar', [$regulation, 'publish']);
$router->get('/admin/campeonatos/{slug}/regulamento/versoes', [$regulation, 'versions']);
$router->get('/admin/campeonatos/{slug}/regulamento/versoes/{version}', [$regulation, 'version']);

$placeholder = new PlaceholderController($users, $authorization, $audit);
$router->get('/meus-campeonatos', static fn ($request, $params = []) => $placeholder->show($request, ['Meus campeonatos']));
$router->get('/minha-equipe', static fn ($request, $params = []) => $placeholder->show($request, ['Minha equipe']));
$router->get('/minhas-partidas', static fn ($request, $params = []) => $placeholder->show($request, ['Minhas partidas']));
$router->get('/conteudo', static fn ($request, $params = []) => $placeholder->show($request, ['Conteudo']));

return $router;
