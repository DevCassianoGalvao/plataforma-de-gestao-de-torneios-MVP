<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\ChampionshipRepository;
use App\Repositories\StandingsRepository;
use App\Repositories\TeamRepository;

final class StandingsAccessService
{
    public function __construct(private readonly ChampionshipRepository $championships, private readonly StandingsRepository $standings, private readonly TeamRepository $teams, private readonly AuthorizationService $authorization)
    {
    }

    public function scope(array $user): string
    {
        $roles = $this->authorization->roleKeys($user);
        if (in_array('administrator', $roles, true)) return 'administrator';
        if (in_array('team_manager', $roles, true)) return 'team';
        return 'none';
    }

    public function canViewPhase(array $user, int $phaseId): bool
    {
        if ($this->authorization->cannot($user, 'standings.view')) return false;
        $phase = $this->standings->phase($phaseId);
        if (!$phase) return false;
        $scope = $this->scope($user);
        if ($scope === 'administrator') return true;
        if ($scope !== 'team') return false;
        foreach ($this->teams->listForUser((int) $user['id'], 'team', ['championship_id' => $phase['championship_id']]) as $team) return true;
        return false;
    }

    public function canManage(array $user, int $phaseId, string $permission): bool
    {
        return $this->authorization->can($user, $permission) && $this->scope($user) === 'administrator' && $this->canViewPhase($user, $phaseId);
    }
}
