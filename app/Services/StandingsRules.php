<?php
declare(strict_types=1);

namespace App\Services;

final class StandingsRules
{
    public const STAGES = ['quarterfinals', 'semifinals', 'final'];

    public static function stage(string $value): bool
    {
        return in_array($value, self::STAGES, true);
    }
}
