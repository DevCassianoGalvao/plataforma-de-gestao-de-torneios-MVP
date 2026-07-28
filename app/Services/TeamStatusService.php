<?php
declare(strict_types=1);

namespace App\Services;

final class TeamStatusService
{
    private const TRANSITIONS = [
        'draft' => ['active', 'archived'],
        'active' => ['inactive', 'withdrawn', 'archived'],
        'inactive' => ['active', 'withdrawn', 'archived'],
        'withdrawn' => ['archived'],
        'archived' => ['active'],
    ];

    public function transition(array $team, string $next): array
    {
        $current = (string) ($team['status'] ?? '');
        if (!isset(self::TRANSITIONS[$current]) || !in_array($next, self::TRANSITIONS[$current], true)) {
            return ['ok' => false, 'message' => 'Transicao de status da equipe invalida.'];
        }
        return ['ok' => true, 'previous' => $current, 'next' => $next];
    }
}
