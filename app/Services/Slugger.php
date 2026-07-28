<?php
declare(strict_types=1);

namespace App\Services;

final class Slugger
{
    public static function make(string $value): string
    {
        $value = trim($value);
        $converted = function_exists('iconv') ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) : $value;
        $value = strtolower((string) $converted);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        return trim($value, '-');
    }
}
