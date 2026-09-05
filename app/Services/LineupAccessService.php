<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\LineupRepository;
use App\Repositories\ScheduleRepository;
use App\Repositories\TeamRepository;

final class LineupAccessService
{
    public function __construct(private readonly LineupRepository $lineups, private readonly ScheduleRepository $schedules, private readonly TeamRepository $teams, private readonly AuthorizationService $authorization)
    {
    }

    public function scope(array $user): string
    {
        $roles = $this->authorization->roleKeys($user);
        if (in_array('administrator', $roles, true)) return 'administrator';
        if (in_array('organizer', $roles, true)) return 'championship';
        if (in_array('team_manager', $roles, true)) return 'team';
        if (in_array('match_operator', $roles, true)) return 'operator';
        return 'none';
    }

    public function matchForUser(array $user, int $matchId): ?array
    {
        $scope = $this->scope($user);
        if ($scope === 'operator') return $this->schedules->matchById($matchId);
        return $this->schedules->matchForUser((int) $matchId, (int) $user['id'], $scope);
    }

    public function canManageTeam(array $user, array $match, int $teamId): bool
    {
        if (!in_array($teamId, [(int) $match['home_team_id'], (int) $match['away_team_id']], true)) return false;
        if (in_array($this->scope($user), ['administrator', 'championship'], true)) return $this->authorization->can($user, 'lineups.update') && $this->matchForUser($user, (int) $match['id']) !== null;
        if ($this->scope($user) !== 'team' || $this->authorization->cannot($user, 'lineups.manage_own')) return false;
        return $this->teams->findForUser($teamId, (int) $user['id'], 'team', true) !== null;
    }

    public function canConfirm(array $user, array $match, int $teamId): bool
    {
        if (!$this->canManageTeam($user, $match, $teamId)) return false;
        return $this->authorization->can($user, 'lineups.confirm');
    }

    public function canReopen(array $user, array $match, int $teamId): bool
    {
        return in_array($this->scope($user), ['administrator', 'championship'], true) && $this->authorization->can($user, 'lineups.reopen') && in_array($teamId, [(int) $match['home_team_id'], (int) $match['away_team_id']], true) && $this->matchForUser($user, (int) $match['id']) !== null;
    }

    public function canView(array $user, array $match, ?array $lineup = null): bool
    {
        $scope = $this->scope($user);
        if ($scope === 'none') return false;
        if ($scope === 'operator') return $lineup !== null && $lineup['status'] === 'confirmed';
        if ($scope === 'administrator') return $this->authorization->can($user, 'lineups.view');
        if ($scope === 'championship') return $this->authorization->can($user, 'lineups.view') && $this->matchForUser($user, (int) $match['id']) !== null;
        return $this->authorization->can($user, 'lineups.view') && $this->schedules->matchForUser((int) $match['id'], (int) $user['id'], 'team') !== null;
    }

    public function find(array $user, array $match, int $teamId): ?array
    {
        $lineup = $this->lineups->find((int) $match['id'], $teamId);
        return $this->canView($user, $match, $lineup) || $this->canManageTeam($user, $match, $teamId) ? $lineup : null;
    }
}
