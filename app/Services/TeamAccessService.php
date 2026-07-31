<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\ChampionshipRepository;
use App\Repositories\TeamRepository;

final class TeamAccessService
{
    public function __construct(private readonly TeamRepository $teams, private readonly ChampionshipRepository $championships, private readonly AuthorizationService $authorization)
    {
    }

    public function scope(array $user): string
    {
        $roles = $this->authorization->roleKeys($user);
        if (in_array('administrator', $roles, true)) return 'administrator';
        return 'team';
    }

    public function find(array $user, int $teamId, string $permission, bool $mutation = false): ?array
    {
        if ($this->authorization->cannot($user, $permission)) return null;
        $scope = $this->scope($user);
        $team = $this->teams->findForUser($teamId, (int) $user['id'], $scope, $mutation);
        if ($mutation && $team && $team['championship_status'] === 'archived') return null;
        return $team;
    }

    public function findForEitherMutation(array $user, int $teamId, string $globalPermission, string $ownPermission): ?array
    {
        $scope = $this->scope($user);
        if ($scope === 'team') return $this->find($user, $teamId, $ownPermission, true);
        return $this->find($user, $teamId, $globalPermission, true);
    }

    public function authorizedChampionships(array $user): array
    {
        $scope = $this->scope($user);
        if ($scope === 'administrator') return $this->championships->listForUser((int) $user['id'], true);
        return [];
    }

    public function canManageAssignments(array $user, int $teamId): bool
    {
        return $this->scope($user) !== 'team' && $this->find($user, $teamId, 'teams.manage_assignments', true) !== null;
    }
}
