<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $name, array $data = []): string
    {
        $file = dirname(__DIR__) . '/Views/' . trim($name, '/') . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('View nao encontrada: ' . $name);
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }

    public static function page(string $title, string $content): string
    {
        return self::render('layouts/base', compact('title', 'content'));
    }

    public static function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
