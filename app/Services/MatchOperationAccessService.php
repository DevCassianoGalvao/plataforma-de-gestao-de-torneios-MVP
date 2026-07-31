<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\MatchOperationRepository;
use App\Repositories\ScheduleRepository;

final class MatchOperationAccessService
{
    public function __construct(private readonly MatchOperationRepository $operations, private readonly ScheduleRepository $schedules, private readonly AuthorizationService $authorization)
    {
    }

    public function scope(array $user): string
    {
        $roles = $this->authorization->roleKeys($user);
        if (in_array('administrator', $roles, true)) return 'administrator';
        if (in_array('match_operator', $roles, true)) return 'operator';
        if (in_array('team_manager', $roles, true)) return 'team';
        return 'none';
    }

    public function matchForUser(array $user, int $matchId): ?array
    {
        $scope = $this->scope($user);
        if ($scope === 'administrator') return $this->schedules->matchById($matchId);
        if ($scope === 'operator') return $this->operations->operatorAssigned($matchId, (int) $user['id']) ? $this->schedules->matchById($matchId) : null;
        return $this->schedules->matchForUser($matchId, (int) $user['id'], $scope);
    }

    public function canOperate(array $user, array $match): bool
    {
        $scope = $this->scope($user);
        if (!$this->authorization->can($user, 'match_operation.operate')) return false;
        $operation = $this->operations->find((int) $match['id']);
        if ($operation && $operation['status'] !== 'open') return false;
        return $scope === 'administrator' || ($scope === 'operator' && $this->operations->operatorAssigned((int) $match['id'], (int) $user['id']));
    }

    public function canHomologate(array $user, array $match): bool
    {
        $scope = $this->scope($user);
        return $this->authorization->can($user, 'match_operation.homologate') && $scope === 'administrator' && $this->matchForUser($user, (int) $match['id']) !== null;
    }

    public function canView(array $user, array $match): bool
    {
        if (!$this->authorization->can($user, 'match_operation.view')) return false;
        return $this->matchForUser($user, (int) $match['id']) !== null;
    }
}
