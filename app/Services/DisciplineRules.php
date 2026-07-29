<?php
declare(strict_types=1);

namespace App\Services;

final class DisciplineRules
{
    public const PERSON_TYPES = ['athlete', 'staff'];
    public const CARD_TYPES = ['yellow', 'second_yellow', 'red'];
    public const SUSPENSION_STATUSES = ['active', 'fulfilled', 'revoked', 'cancelled'];

    public static function personType(string $value): bool
    {
        return in_array($value, self::PERSON_TYPES, true);
    }

    public static function cardType(string $value): bool
    {
        return in_array($value, self::CARD_TYPES, true);
    }

    public static function matches(mixed $value): int
    {
        $number = filter_var($value, FILTER_VALIDATE_INT);
        return $number !== false && $number > 0 && $number <= 20 ? (int) $number : 0;
    }
}
