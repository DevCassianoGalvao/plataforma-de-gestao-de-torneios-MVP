<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\NewsRepository;

final class NewsAccessService
{
    public function __construct(private readonly NewsRepository $news, private readonly AuthorizationService $authorization)
    {
    }

    public function isAdministrator(array $user): bool
    {
        return in_array('administrator', $this->authorization->roleKeys($user), true);
    }

    public function allowedChampionshipIds(array $user): ?array
    {
        if ($this->isAdministrator($user)) return null;
        $rows = $this->news->championshipsForUser((int) $user['id'], $this->authorization->roleKeys($user), false);
        return array_map(static fn (array $row): int => (int) $row['id'], $rows);
    }

    public function canManageChampionship(array $user, int $championshipId): bool
    {
        if ($this->authorization->cannot($user, 'content.manage')) return false;
        if ($this->isAdministrator($user)) return true;
        return in_array($championshipId, $this->allowedChampionshipIds($user) ?? [], true);
    }

    public function canPublishChampionship(array $user, int $championshipId): bool
    {
        return $this->authorization->can($user, 'content.publish') && $this->canManageChampionship($user, $championshipId);
    }
}
