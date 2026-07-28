<?php
declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }

    $relative = substr($class, 4);
    $file = dirname(__DIR__) . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

use App\Core\Config;
use App\Core\Env;
use App\Core\Session;

Env::load(dirname(__DIR__) . '/.env');
date_default_timezone_set(Config::get('APP_TIMEZONE', 'UTC'));
Session::start();
