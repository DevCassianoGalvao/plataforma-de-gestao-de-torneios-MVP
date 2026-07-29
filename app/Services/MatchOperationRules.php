<?php
declare(strict_types=1);

namespace App\Services;

final class MatchOperationRules
{
    public const EVENT_TYPES = ['goal', 'own_goal', 'assist', 'yellow', 'second_yellow', 'red', 'occurrence', 'penalty_scored', 'penalty_missed'];
    public const PERIODS = ['regular', 'extra_time', 'penalties', 'other'];
    public const OFFICIAL_ROLES = ['referee', 'assistant_1', 'assistant_2', 'scorekeeper', 'fourth_official', 'other'];

    public static function eventType(string $value): bool
    {
        return in_array($value, self::EVENT_TYPES, true);
    }

    public static function period(string $value): bool
    {
        return in_array($value, self::PERIODS, true);
    }

    public static function minute(mixed $value): ?int
    {
        if ($value === '' || $value === null) return null;
        $minute = filter_var($value, FILTER_VALIDATE_INT);
        return $minute !== false && $minute >= 0 && $minute <= 300 ? (int) $minute : null;
    }
}
