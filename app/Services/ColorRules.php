<?php
declare(strict_types=1);

namespace App\Services;

final class ColorRules
{
    public static function valid(string $color): bool
    {
        return preg_match('/^#[0-9A-Fa-f]{6}$/', $color) === 1;
    }
}
