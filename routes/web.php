<?php
declare(strict_types=1);

use App\Core\Router;
use App\Http\Controllers\AuthPlaceholderController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HomeController;

$router = new Router();
$home = new HomeController();
$health = new HealthController();
$auth = new AuthPlaceholderController();

$router->get('/', [$home, 'index']);
$router->get('/health', [$health, 'show']);
$router->get('/login', [$auth, 'show']);
$router->post('/login', [$auth, 'submit']);

return $router;
