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

    public function canRequest(array $user): bool
    {
        return $this->authorization->can($user, 'transfers.request');
    }

    public function ownTeamIds(array $user): array
    {
        return array_map(static fn (array $t): int => (int) $t['id'], $this->transfers->ownTeams((int) $user['id']));
    }

    public function canRequestForTeam(array $user, int $teamId): bool
    {
        return $this->canRequest($user) && $this->transfers->isOwnTeam($teamId, (int) $user['id']);
    }

    public function canAccessRecord(array $user, array $record): bool
    {
        if ($this->canManageChampionship($user, (int) $record['championship_id'])) return true;
        if (!$this->canRequest($user)) return false;
        return (int) $record['author_id'] === (int) $user['id']
            && $record['previous_team_id'] !== null
            && $this->transfers->isOwnTeam((int) $record['previous_team_id'], (int) $user['id']);
    }

    public function canCancelRecord(array $user, array $record): bool
    {
        if ($this->canManageChampionship($user, (int) $record['championship_id'])) return true;
        if (!$this->canRequest($user)) return false;
        return (int) $record['author_id'] === (int) $user['id']
            && in_array($record['status'], ['draft', 'pending'], true)
            && $record['previous_team_id'] !== null
            && $this->transfers->isOwnTeam((int) $record['previous_team_id'], (int) $user['id']);
    }

    public function canSubmit(array $user, int $championshipId, ?int $previousTeamId, ?array $existingRecord): bool
    {
        if ($this->canManageChampionship($user, $championshipId)) return true;
        if (!$this->canRequest($user)) return false;
        if ($existingRecord !== null && (int) $existingRecord['author_id'] !== (int) $user['id']) return false;
        if ($existingRecord !== null && !in_array($existingRecord['status'], ['draft', 'pending'], true)) return false;
        return $previousTeamId !== null && $this->transfers->isOwnTeam($previousTeamId, (int) $user['id']);
    }
}
