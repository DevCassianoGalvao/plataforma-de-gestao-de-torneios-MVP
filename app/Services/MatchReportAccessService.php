<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\ChampionshipRepository;
use App\Repositories\MatchReportRepository;

final class MatchReportAccessService
{
    public function __construct(private readonly MatchReportRepository $reports, private readonly MatchOperationAccessService $matches, private readonly ChampionshipRepository $championships, private readonly AuthorizationService $authorization)
    {
    }

    public function matchForUser(array $user, int $matchId): ?array
    {
        $match = $this->reports->match($matchId);
        return $match && $this->matches->matchForUser($user, $matchId) && $this->matches->canView($user, $match) ? $match : null;
    }

    public function canView(array $user, array $match): bool
    {
        return $this->authorization->can($user, 'match_reports.view') && $this->matches->canView($user, $match);
    }

    public function canDownload(array $user, array $match): bool
    {
        return $this->authorization->can($user, 'match_reports.download') && $this->matches->canView($user, $match);
    }

    public function canGenerate(array $user, array $match): bool
    {
        $roles = $this->authorization->roleKeys($user);
        if (!$this->authorization->can($user, 'match_reports.generate')) return false;
        if (in_array('administrator', $roles, true)) return true;
        return in_array('organizer', $roles, true) && $this->matchForUser($user, (int) $match['id']) !== null;
    }

    public function canPackage(array $user, int $championshipId): bool
    {
        if (!$this->authorization->can($user, 'match_reports.package')) return false;
        return $this->authorizedChampionship($user, $championshipId);
    }

    public function authorizedChampionship(array $user, int $championshipId): bool
    {
        $roles = $this->authorization->roleKeys($user);
        if (in_array('administrator', $roles, true)) return $this->championships->findForUser($championshipId, 0, true) !== null;
        return in_array('organizer', $roles, true) && $this->championships->findForUser($championshipId, (int) $user['id'], false) !== null;
    }
}
