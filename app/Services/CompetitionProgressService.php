<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\StandingsRepository;

/** Rebuilds derived competition data only after an organizer homologates a result. */
final class CompetitionProgressService
{
    public function __construct(private readonly StandingsRepository $standings, private readonly StandingsService $service, private readonly AuditService $audit)
    {
    }

    public function afterHomologation(array $match, int $userId): array
    {
        $phase = $this->standings->phase((int) $match['phase_id']);
        if (!$phase) return ['ok' => true, 'actions' => []];
        $actions = [];
        if ($phase['phase_type'] === 'groups') {
            $this->service->recalculate($phase, $userId);
            $actions[] = 'classificacao_recalculada';
            if ($this->standings->phaseMatchesPending((int) $phase['id']) === 0) {
                $generated = $this->service->generateKnockout($phase, $userId);
                if (!$generated['ok']) return $generated;
                $actions[] = 'mata_mata_gerado';
            }
        } elseif ($phase['phase_type'] === 'knockout') {
            $advanced = $this->service->processKnockoutMatch(array_merge($match, ['status' => 'homologated']), $userId);
            if (!$advanced['ok']) return $advanced;
            $actions[] = 'mata_mata_atualizado';
        }
        $this->audit->record('competition.derived_data_updated', $userId, 'match', (int) $match['id'], ['actions' => $actions], null);
        return ['ok' => true, 'errors' => [], 'actions' => $actions];
    }
}
