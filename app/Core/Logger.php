<?php
declare(strict_types=1);

namespace App\Core;

final class Logger
{
    public static function exception(\Throwable $exception): void
    {
        $directory = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
        $line = sprintf("[%s] %s in %s:%d\n", date(DATE_ATOM), $exception->getMessage(), $exception->getFile(), $exception->getLine());
        error_log($line, 3, $directory . '/app.log');
    }
}
