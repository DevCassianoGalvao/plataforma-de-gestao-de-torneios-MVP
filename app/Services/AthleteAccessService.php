<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\AthleteRepository;
use App\Repositories\ChampionshipRepository;
use App\Repositories\TeamRepository;

final class AthleteAccessService
{
    public function __construct(private readonly AthleteRepository $athletes, private readonly TeamRepository $teams, private readonly ChampionshipRepository $championships, private readonly AuthorizationService $authorization)
    {
    }

    public function scope(array $user): string
    {
        $roles = $this->authorization->roleKeys($user);
        if (in_array('administrator', $roles, true)) return 'administrator';
        if (in_array('organizer', $roles, true)) return 'organizer';
        return 'team';
    }

    public function list(array $user, array $filters = []): array
    {
        return $this->athletes->listForUser((int) $user['id'], $this->scope($user), $filters);
    }

    public function find(array $user, int $athleteId, string $permission, bool $mutation = false): ?array
    {
        if ($this->authorization->cannot($user, $permission)) return null;
        return $this->athletes->findForUser($athleteId, (int) $user['id'], $this->scope($user), $mutation);
    }

    public function team(array $user, int $teamId, bool $mutation = true): ?array
    {
        $permission = $this->scope($user) === 'team' ? 'athletes.manage_own' : 'athletes.create';
        if ($this->authorization->cannot($user, $permission)) return null;
        return $this->teams->findForUser($teamId, (int) $user['id'], $this->scope($user), $mutation);
    }

    public function authorizedTeams(array $user): array
    {
        return $this->teams->listForUser((int) $user['id'], $this->scope($user), ['status' => 'active']);
    }

    public function authorizedChampionships(array $user): array
    {
        if ($this->scope($user) === 'administrator') return $this->championships->listForUser((int) $user['id'], true);
        if ($this->scope($user) === 'organizer') return $this->championships->listForUser((int) $user['id'], false);
        return [];
    }
}
