<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\ChampionshipRepository;
use App\Repositories\UserRepository;

final class ChampionshipAccessService
{
    public function __construct(private readonly ChampionshipRepository $championships, private readonly AuthorizationService $authorization)
    {
    }

    public function isAdministrator(array $user): bool
    {
        return in_array('administrator', $this->authorization->roleKeys($user), true);
    }

    public function canView(array $user, int $championshipId): bool
    {
        if ($this->authorization->cannot($user, 'championships.view')) {
            return false;
        }
        return $this->championships->findForUser($championshipId, (int) $user['id'], $this->isAdministrator($user)) !== null;
    }

    public function find(array $user, int $championshipId, string $permission, bool $mutation = false): ?array
    {
        if ($this->authorization->cannot($user, $permission)) {
            return null;
        }
        $championship = $this->championships->findForUser($championshipId, (int) $user['id'], $this->isAdministrator($user));
        if (!$championship) {
            return null;
        }
        if ($mutation && $championship['status'] === 'archived') {
            return null;
        }
        return $championship;
    }

    public function canManageAssignments(array $user, int $championshipId): bool
    {
        return $this->isAdministrator($user) && $this->find($user, $championshipId, 'championships.manage_assignments', true) !== null;
    }
}
