<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\TransferRepository;

final class TransferAccessService
{
    public function __construct(private readonly TransferRepository $transfers, private readonly AuthorizationService $authorization) {}
    public function isAdministrator(array $user): bool { return in_array('administrator', $this->authorization->roleKeys($user), true); }
    public function allowedChampionshipIds(array $user): ?array { if ($this->isAdministrator($user)) return null; return array_map(static fn (array $r): int => (int) $r['id'], $this->transfers->championshipsForUser((int) $user['id'], $this->authorization->roleKeys($user), false)); }
    public function canManageChampionship(array $user, int $championshipId): bool { if ($this->authorization->cannot($user, 'transfers.manage')) return false; if ($this->isAdministrator($user)) return true; return in_array($championshipId, $this->allowedChampionshipIds($user) ?? [], true); }
}
