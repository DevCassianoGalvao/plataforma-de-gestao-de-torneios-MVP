<?php
declare(strict_types=1);

namespace App\Core;

final class Logger
{
    public static function message(string $message): void
    {
        $directory = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
        error_log(sprintf("[%s] %s\n", date(DATE_ATOM), $message), 3, $directory . '/app.log');
    }

    public static function exception(\Throwable $exception): string
    {
        $reference = strtoupper(bin2hex(random_bytes(5)));
        $lines = [sprintf('[%s] [%s] %s: %s in %s:%d', date(DATE_ATOM), $reference, $exception::class, $exception->getMessage(), $exception->getFile(), $exception->getLine())];
        for ($current = $exception->getPrevious(); $current !== null; $current = $current->getPrevious()) {
            $lines[] = sprintf('[%s] [%s] Caused by %s: %s in %s:%d', date(DATE_ATOM), $reference, $current::class, $current->getMessage(), $current->getFile(), $current->getLine());
        }
        $lines[] = sprintf('[%s] [%s] Request: %s %s', date(DATE_ATOM), $reference, $_SERVER['REQUEST_METHOD'] ?? 'CLI', $_SERVER['REQUEST_URI'] ?? '');
        $lines[] = $exception->getTraceAsString();
        self::message(implode("\n", $lines));
        return $reference;
    }
}
