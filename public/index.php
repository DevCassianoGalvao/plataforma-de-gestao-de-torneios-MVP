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
    $reference = Logger::exception($exception);
    $debug = App\Core\Config::bool('APP_DEBUG') && App\Core\Config::get('APP_ENV', 'development') !== 'production';
    $databaseUpdate = App\Core\DeploymentIssue::requiresDatabaseUpdate($exception);
    $message = $debug
        ? $exception->getMessage()
        : ($databaseUpdate ? 'O sistema recebeu uma atualizacao que ainda precisa ser aplicada ao banco de dados.' : 'Nao foi possivel concluir esta operacao agora. Tente novamente em alguns instantes.');
    Response::html(View::page('Erro', View::render('errors/500', ['message' => $message, 'reference' => $reference, 'databaseUpdate' => $databaseUpdate])), $databaseUpdate ? 503 : 500)->send();
}
