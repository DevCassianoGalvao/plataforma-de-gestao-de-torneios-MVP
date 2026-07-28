<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\ChampionshipRepository;
use App\Repositories\ScheduleRepository;
use App\Repositories\TeamRepository;

final class ScheduleAccessService
{
    public function __construct(private readonly ScheduleRepository $schedules, private readonly ChampionshipRepository $championships, private readonly TeamRepository $teams, private readonly AuthorizationService $authorization)
    {
    }

    public function scope(array $user): string
    {
        $roles = $this->authorization->roleKeys($user);
        if (in_array('administrator', $roles, true)) return 'administrator';
        if (in_array('organizer', $roles, true)) return 'organizer';
        if (in_array('team_manager', $roles, true)) return 'team';
        return 'none';
    }

    public function championship(array $user, int $championshipId): ?array
    {
        $scope = $this->scope($user);
        if ($scope === 'administrator') return $this->championships->findForUser($championshipId, 0, true);
        if ($scope === 'organizer') return $this->championships->findForUser($championshipId, (int) $user['id'], false);
        return null;
    }

    public function canManage(array $user, int $championshipId): bool
    {
        $scope = $this->scope($user);
        return in_array($scope, ['administrator', 'organizer'], true) && $this->championship($user, $championshipId) !== null && $this->authorization->can($user, 'schedule.update');
    }

    public function canGenerate(array $user, int $championshipId): bool
    {
        return $this->canManage($user, $championshipId) && $this->authorization->can($user, 'schedule.generate');
    }

    public function listMatches(array $user, array $filters = []): array
    {
        return $this->schedules->listMatches((int) $user['id'], $this->scope($user), $filters);
    }

    public function findMatch(array $user, int $matchId): ?array
    {
        return $this->schedules->matchForUser($matchId, (int) $user['id'], $this->scope($user));
    }

    public function teamsForUser(array $user, int $championshipId): array
    {
        $scope = $this->scope($user);
        if ($scope === 'administrator') return $this->teams->listForUser(0, 'administrator', ['championship_id' => $championshipId, 'status' => 'active']);
        if ($scope === 'organizer') return $this->teams->listForUser((int) $user['id'], 'organizer', ['championship_id' => $championshipId, 'status' => 'active']);
        return $this->teams->listForUser((int) $user['id'], 'team', ['championship_id' => $championshipId, 'status' => 'active']);
    }
}
