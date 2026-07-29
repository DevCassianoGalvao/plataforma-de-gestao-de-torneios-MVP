<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;

try {
    $router = require dirname(__DIR__) . '/routes/web.php';
    $router->dispatch(Request::capture())->send();
} catch (\Throwable $exception) {
    Logger::exception($exception);
    $message = App\Core\Config::bool('APP_DEBUG') && App\Core\Config::get('APP_ENV', 'development') !== 'production' ? $exception->getMessage() : 'Ocorreu um erro interno.';
    Response::html(View::page('Erro', View::render('errors/500', ['message' => $message])), 500)->send();
}
