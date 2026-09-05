<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;

final class AuthorizationService
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    public function can(array $user, string $permission): bool
    {
        return in_array($permission, $this->users->permissions((int) $user['id']), true);
    }

    public function cannot(array $user, string $permission): bool
    {
        return !$this->can($user, $permission);
    }

    public function roleKeys(array $user): array
    {
        return array_map(static fn (array $role): string => (string) $role['key'], $this->users->roles((int) $user['id']));
    }

    public function primaryRole(array $user): ?string
    {
        $priority = ['administrator', 'organizer', 'team_manager', 'match_operator', 'accountability'];
        $roles = $this->roleKeys($user);
        foreach ($priority as $key) {
            if (in_array($key, $roles, true)) {
                return $key;
            }
        }
        return $roles[0] ?? null;
    }
}
