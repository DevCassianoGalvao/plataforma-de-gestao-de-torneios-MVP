<?php
declare(strict_types=1);

use App\Core\Database;
use App\Core\Router;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PlaceholderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Repositories\UserRepository;
use App\Services\AuditService;
use App\Services\AuthService;
use App\Services\AuthorizationService;
use App\Services\MailService;
use App\Services\PasswordResetService;

$router = new Router();
$pdo = Database::connection();
$users = new UserRepository($pdo);
$audit = new AuditService($pdo);
$authorization = new AuthorizationService($users);
$mail = new MailService();
$passwordReset = new PasswordResetService($pdo, $users, $audit, $mail);
$authService = new AuthService($pdo, $users, $audit);

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

$placeholder = new PlaceholderController($users, $authorization, $audit);
$router->get('/meus-campeonatos', static fn ($request, $params = []) => $placeholder->show($request, ['Meus campeonatos']));
$router->get('/minha-equipe', static fn ($request, $params = []) => $placeholder->show($request, ['Minha equipe']));
$router->get('/minhas-partidas', static fn ($request, $params = []) => $placeholder->show($request, ['Minhas partidas']));
$router->get('/conteudo', static fn ($request, $params = []) => $placeholder->show($request, ['Conteudo']));

return $router;
