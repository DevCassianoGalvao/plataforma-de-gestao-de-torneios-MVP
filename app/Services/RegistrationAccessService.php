<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\ChampionshipRepository;
use App\Repositories\RegistrationRepository;
use App\Repositories\TeamRepository;

final class RegistrationAccessService
{
    public function __construct(private readonly RegistrationRepository $registrations, private readonly TeamRepository $teams, private readonly ChampionshipRepository $championships, private readonly AuthorizationService $authorization)
    {
    }

    public function scope(array $user): string
    {
        $roles = $this->authorization->roleKeys($user);
        if (in_array('administrator', $roles, true)) return 'administrator';
        if (in_array('organizer', $roles, true)) return 'championship';
        return 'team';
    }

    public function list(array $user, array $filters = []): array
    {
        return $this->registrations->listForUser((int) $user['id'], $this->scope($user), $filters);
    }

    public function find(array $user, int $id): ?array
    {
        return $this->registrations->findForUser($id, (int) $user['id'], $this->scope($user));
    }

    public function team(array $user, int $teamId): ?array
    {
        $scope = $this->scope($user);
        if ($scope === 'administrator') return $this->teams->findForUser($teamId, 0, 'administrator');
        if ($scope === 'championship') return $this->teams->findForUser($teamId, (int) $user['id'], 'championship', true);
        if ($scope !== 'team') return null;
        return $this->teams->findForUser($teamId, (int) $user['id'], 'team', true);
    }

    public function championship(array $user, int $championshipId): ?array
    {
        $scope = $this->scope($user);
        if ($scope === 'administrator') return $this->championships->findForUser($championshipId, 0, true);
        if ($scope === 'championship') return $this->championships->findForUser($championshipId, (int) $user['id'], false);
        return null;
    }

    public function authorizedChampionships(array $user): array
    {
        $scope = $this->scope($user);
        if ($scope === 'administrator') return $this->championships->listForUser(0, true);
        if ($scope === 'championship') return $this->championships->listForUser((int) $user['id'], false);
        return [];
    }

    public function canReview(array $user): bool
    {
        return $this->scope($user) !== 'team';
    }
}
