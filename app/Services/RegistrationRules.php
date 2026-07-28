<?php
declare(strict_types=1);

namespace App\Services;

final class RegistrationRules
{
    public const STATUSES = ['draft', 'submitted', 'under_review', 'pending_correction', 'approved', 'rejected', 'suspended', 'cancelled'];
    public const ACTIVE_STATUSES = ['submitted', 'under_review', 'pending_correction', 'approved', 'suspended'];

    public static function transition(string $from, string $to): bool
    {
        $allowed = [
            'draft' => ['submitted', 'cancelled'],
            'submitted' => ['under_review', 'pending_correction', 'cancelled'],
            'under_review' => ['pending_correction', 'approved', 'rejected'],
            'pending_correction' => ['submitted', 'cancelled'],
            'approved' => ['suspended', 'cancelled'],
            'suspended' => ['approved', 'cancelled'],
            'rejected' => [],
            'cancelled' => [],
        ];
        return in_array($to, $allowed[$from] ?? [], true);
    }

    public static function validNumber(mixed $number): bool
    {
        return $number === null || $number === '' || (filter_var($number, FILTER_VALIDATE_INT) !== false && (int) $number >= 1 && (int) $number <= 99);
    }

    public static function windowOpen(array $championship, ?\DateTimeImmutable $now = null): bool
    {
        $now ??= new \DateTimeImmutable('today');
        $start = !empty($championship['registration_starts_at']) ? new \DateTimeImmutable((string) $championship['registration_starts_at']) : null;
        $end = !empty($championship['registration_ends_at']) ? new \DateTimeImmutable((string) $championship['registration_ends_at']) : null;
        if (!$start || !$end) return false;
        return $now >= $start && $now <= $end;
    }
}
