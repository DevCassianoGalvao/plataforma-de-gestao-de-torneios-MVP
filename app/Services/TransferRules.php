<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\TransferRepository;

final class TransferRules
{
    public static function validType(string $type): bool { return in_array($type, TransferRepository::TYPES, true); }
    public static function validStatus(string $status): bool { return in_array($status, TransferRepository::STATUSES, true); }
    public static function canTransition(string $from, string $to): bool { $transitions = ['draft' => ['pending', 'cancelled'], 'pending' => ['draft', 'approved', 'cancelled'], 'approved' => ['published', 'cancelled'], 'published' => ['cancelled'], 'cancelled' => []]; return in_array($to, $transitions[$from] ?? [], true); }
}
