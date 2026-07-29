<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\ChampionshipRepository;
use App\Repositories\RegulationRepository;

final class ChampionshipStatusService
{
    private const TRANSITIONS = [
        'draft' => ['registration', 'archived'],
        'registration' => ['configured', 'archived'],
        'configured' => ['in_progress', 'archived'],
        'in_progress' => ['finished'],
        'finished' => ['archived'],
        'archived' => [],
    ];

    public function __construct(private readonly ChampionshipRepository $championships, private readonly RegulationRepository $regulations)
    {
    }

    public function transition(array $championship, string $next): array
    {
        $current = (string) $championship['status'];
        if (!isset(self::TRANSITIONS[$current]) || !in_array($next, self::TRANSITIONS[$current], true)) {
            return ['ok' => false, 'message' => 'Transicao de status invalida.'];
        }
        if ($next === 'configured' && !$this->regulations->published((int) $championship['id'])) {
            return ['ok' => false, 'message' => 'Publique um regulamento antes de configurar o campeonato.'];
        }
        if ($next === 'in_progress' && !$this->regulations->published((int) $championship['id'])) {
            return ['ok' => false, 'message' => 'O campeonato precisa de um regulamento publicado.'];
        }
        $this->championships->updateStatus((int) $championship['id'], $next);
        return ['ok' => true];
    }
}
