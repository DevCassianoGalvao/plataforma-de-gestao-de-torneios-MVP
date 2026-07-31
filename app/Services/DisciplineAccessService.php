<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\ChampionshipRepository;
use App\Repositories\TeamRepository;

final class DisciplineAccessService
{
    public function __construct(private readonly ChampionshipRepository $championships, private readonly TeamRepository $teams, private readonly AuthorizationService $authorization)
    {
    }

    public function scope(array $user): string
    {
        $roles = $this->authorization->roleKeys($user);
        if (in_array('administrator', $roles, true)) return 'administrator';
        if (in_array('team_manager', $roles, true)) return 'team';
        if (in_array('match_operator', $roles, true)) return 'operator';
        return 'none';
    }

    public function canViewChampionship(array $user, int $championshipId): bool
    {
        if ($this->authorization->cannot($user, 'discipline.view')) return false;
        $scope = $this->scope($user);
        if ($scope === 'administrator') return $this->championships->findForUser($championshipId, (int) $user['id'], true) !== null;
        if ($scope !== 'team') return false;
        foreach ($this->teams->listForUser((int) $user['id'], 'team', ['championship_id' => $championshipId]) as $team) return true;
        return false;
    }

    public function canManage(array $user, int $championshipId): bool
    {
        return $this->authorization->can($user, 'discipline.manage') && $this->canViewChampionship($user, $championshipId) && $this->scope($user) !== 'team';
    }

    public function canProcess(array $user, int $championshipId): bool
    {
        return $this->authorization->can($user, 'discipline.process') && $this->canViewChampionship($user, $championshipId);
    }

    public function allowedTeamIds(array $user, int $championshipId): array
    {
        $scope = $this->scope($user);
        if ($scope === 'team') return array_map(static fn (array $team): int => (int) $team['id'], $this->teams->listForUser((int) $user['id'], 'team', ['championship_id' => $championshipId]));
        return [];
    }
}
