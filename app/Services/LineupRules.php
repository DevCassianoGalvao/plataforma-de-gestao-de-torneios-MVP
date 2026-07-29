<?php
declare(strict_types=1);

namespace App\Services;

final class LineupRules
{
    public const STATUSES = ['draft', 'confirmed'];

    public static function canEdit(string $status): bool
    {
        return $status === 'draft';
    }

    public static function validRole(string $role): bool
    {
        return in_array($role, ['starter', 'reserve'], true);
    }
}
