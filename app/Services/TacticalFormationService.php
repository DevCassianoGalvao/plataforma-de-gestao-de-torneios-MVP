<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\TacticalFormationRepository;
use App\Repositories\TeamRepository;

final class TacticalFormationService
{
    public function __construct(private readonly TacticalFormationRepository $formations, private readonly TeamRepository $teams)
    {
    }

    public function list(): array
    {
        return $this->formations->listActive();
    }

    public function find(int $id): ?array
    {
        $formation = $this->formations->findWithSlots($id);
        if (!$formation || !$this->validate($formation)['ok']) return null;
        return $formation;
    }

    public function validate(array $formation): array
    {
        $slots = $formation['slots'] ?? [];
        $errors = [];
        if ((int) ($formation['player_count'] ?? 0) !== 11) $errors[] = 'A formacao deve prever 11 jogadores.';
        if (count($slots) !== 11) $errors[] = 'A formacao deve possuir exatamente 11 slots.';
        $goalkeepers = 0;
        foreach ($slots as $slot) {
            if (($slot['position_code'] ?? '') === 'goalkeeper') $goalkeepers++;
            foreach (['horizontal_position', 'vertical_position'] as $coordinate) {
                $value = (float) ($slot[$coordinate] ?? -1);
                if ($value < 0 || $value > 100) $errors[] = 'Coordenada de slot fora do intervalo de 0 a 100.';
            }
        }
        if ($goalkeepers !== 1) $errors[] = 'A formacao deve possuir exatamente um goleiro.';
        return ['ok' => $errors === [], 'errors' => array_values(array_unique($errors))];
    }

    public function setDefault(int $teamId, int $formationId, int $userId): array
    {
        $formation = $this->find($formationId);
        if (!$formation) return ['ok' => false, 'errors' => ['Formacao invalida ou incompleta.']];
        $this->teams->updateDefaultFormation($teamId, $formationId, $userId);
        return ['ok' => true, 'formation' => $formation];
    }
}
