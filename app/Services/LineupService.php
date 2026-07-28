<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\LineupRepository;
use App\Repositories\TacticalFormationRepository;
use App\Repositories\TeamRepository;

final class LineupService
{
    public function __construct(private readonly LineupRepository $lineups, private readonly TacticalFormationRepository $formations, private readonly TeamRepository $teams, private readonly AuthorizationService $authorization, private readonly AuditService $audit)
    {
    }

    public function ensureDraft(array $user, array $match, int $teamId): array
    {
        $lineup = $this->lineups->find((int) $match['id'], $teamId);
        if ($lineup) return $lineup;
        $team = $this->teams->findForUser($teamId, $this->scopeUserId($user), $this->scope($user), true);
        if (!$team) throw new \RuntimeException('Equipe fora do escopo da escalacao.');
        $formationId = (int) ($team['default_tactical_formation_id'] ?? 0);
        if (!$formationId) $formationId = (int) (($this->formations->listActive()[0]['id'] ?? 0));
        if (!$formationId || !$this->formations->findWithSlots($formationId)) throw new \RuntimeException('Formacao padrao invalida.');
        $id = $this->lineups->create((int) $match['id'], $teamId, $formationId, (int) $user['id']);
        return $this->lineups->find((int) $match['id'], $teamId) ?? throw new \RuntimeException('Escalacao nao criada.');
    }

    public function suggest(array $match, int $teamId, int $formationId): array
    {
        $formation = $this->formations->findWithSlots($formationId);
        if (!$formation) return ['ok' => false, 'errors' => ['Formacao invalida.'], 'starters' => [], 'reserves' => []];
        $athletes = $this->lineups->eligibleAthletes((int) $match['championship_id'], $teamId);
        $remaining = array_values($athletes);
        $starters = [];
        foreach ($formation['slots'] as $slot) {
            if ($remaining === []) break;
            $bestIndex = 0;
            $bestScore = -1;
            foreach ($remaining as $index => $athlete) {
                $score = $this->positionScore($athlete, $slot);
                if ($score > $bestScore) { $bestScore = $score; $bestIndex = $index; }
            }
            $athlete = $remaining[$bestIndex];
            array_splice($remaining, $bestIndex, 1);
            $starters[(string) $slot['slot_key']] = (int) $athlete['id'];
        }
        $goalkeeperSlot = null;
        foreach ($formation['slots'] as $slot) if ($slot['position_code'] === 'goalkeeper') { $goalkeeperSlot = (string) $slot['slot_key']; break; }
        return ['ok' => true, 'errors' => [], 'starters' => $starters, 'reserves' => array_map(static fn (array $athlete): int => (int) $athlete['id'], array_slice($remaining, 0, 7)), 'captain_athlete_id' => (int) ($starters[array_key_first($starters)] ?? 0), 'goalkeeper_athlete_id' => (int) ($goalkeeperSlot !== null ? ($starters[$goalkeeperSlot] ?? 0) : 0)];
    }

    public function save(array $user, array $match, array $lineup, array $data, bool $confirm = false, ?string $reason = null): array
    {
        $current = $this->lineups->find((int) $match['id'], (int) $lineup['team_id']);
        if (!$current || !LineupRules::canEdit((string) $current['status'])) return ['ok' => false, 'errors' => ['Escalacao confirmada. Reabra antes de editar.']];
        $lineup = $current;
        $formationId = (int) ($data['formation_id'] ?? 0);
        $formation = $this->formations->findWithSlots($formationId);
        if (!$formation) return ['ok' => false, 'errors' => ['Escolha uma formacao valida.']];
        $athletes = $this->lineups->eligibleAthletes((int) $match['championship_id'], (int) $lineup['team_id']);
        $staff = $this->lineups->staff((int) $lineup['team_id']);
        $built = $this->buildPlayers($formation, $athletes, $data);
        if ($built['errors'] !== []) return ['ok' => false, 'errors' => $built['errors']];
        $errors = $this->validateSelection($built, $athletes, $data, $confirm, $match);
        if ($errors !== []) return ['ok' => false, 'errors' => $errors];
        $staffIds = array_map('intval', (array) ($data['staff_ids'] ?? []));
        $knownStaff = array_map(static fn (array $member): int => (int) $member['id'], $staff);
        foreach ($staffIds as $staffId) if (!in_array($staffId, $knownStaff, true)) return ['ok' => false, 'errors' => ['Comissao presente invalida.']];
        $status = $confirm ? 'confirmed' : 'draft';
        $action = $confirm ? 'confirmed' : 'saved';
        $this->lineups->saveContent((int) $lineup['id'], $formationId, $built['captain'], $built['goalkeeper'], $built['players'], $staffIds, (int) $user['id'], $status, $action, $reason);
        $this->audit->record('lineup.' . $action, (int) $user['id'], 'match_lineup', (int) $lineup['id'], ['match_id' => $match['id'], 'team_id' => $lineup['team_id'], 'formation_id' => $formationId], null);
        return ['ok' => true, 'errors' => [], 'lineup' => $this->lineups->find((int) $match['id'], (int) $lineup['team_id'])];
    }

    public function reopen(array $user, array $match, array $lineup, string $reason): array
    {
        if (trim($reason) === '') return ['ok' => false, 'errors' => ['Informe o motivo da reabertura.']];
        if ($lineup['status'] !== 'confirmed') return ['ok' => false, 'errors' => ['Apenas escalacao confirmada pode ser reaberta.']];
        $this->lineups->reopen((int) $lineup['id'], (int) $user['id'], trim($reason));
        $this->audit->record('lineup.reopened', (int) $user['id'], 'match_lineup', (int) $lineup['id'], ['match_id' => $match['id'], 'team_id' => $lineup['team_id']], null);
        return ['ok' => true, 'errors' => []];
    }

    public function formData(array $lineup): array
    {
        $starters = [];
        $reserves = [];
        foreach ($lineup['players'] ?? [] as $player) {
            if ($player['role'] === 'starter') $starters[(string) $player['slot_key']] = (int) $player['athlete_id'];
            else $reserves[] = (int) $player['athlete_id'];
        }
        return ['formation_id' => (int) $lineup['tactical_formation_id'], 'starters' => $starters, 'reserves' => $reserves, 'captain_athlete_id' => (int) ($lineup['captain_athlete_id'] ?? 0), 'goalkeeper_athlete_id' => (int) ($lineup['goalkeeper_athlete_id'] ?? 0), 'staff_ids' => array_map(static fn (array $member): int => (int) $member['team_staff_id'], $lineup['staff'] ?? [])];
    }

    private function buildPlayers(array $formation, array $athletes, array $data): array
    {
        $byId = [];
        foreach ($athletes as $athlete) $byId[(int) $athlete['id']] = $athlete;
        $players = [];
        $errors = [];
        $seen = [];
        foreach ($formation['slots'] as $order => $slot) {
            $slotKey = (string) $slot['slot_key'];
            $athleteId = (int) (($data['starters'][$slotKey] ?? 0));
            if (!$athleteId) continue;
            if (!isset($byId[$athleteId])) { $errors[] = 'Titular selecionado nao pertence ao elenco aprovado e ativo.'; continue; }
            if (isset($seen[$athleteId])) { $errors[] = 'Atleta nao pode ocupar dois slots.'; continue; }
            $seen[$athleteId] = true;
            $score = $this->positionScore($byId[$athleteId], $slot);
            $players[] = $this->playerRow($byId[$athleteId], 'starter', $slotKey, $order + 1, $score < 70, (int) ($data['captain_athlete_id'] ?? 0), (int) ($data['goalkeeper_athlete_id'] ?? 0));
        }
        $reserveSeen = [];
        foreach (array_map('intval', (array) ($data['reserves'] ?? [])) as $order => $athleteId) {
            if (!$athleteId) continue;
            if (!isset($byId[$athleteId])) { $errors[] = 'Reserva selecionado nao pertence ao elenco aprovado e ativo.'; continue; }
            if (isset($seen[$athleteId]) || isset($reserveSeen[$athleteId])) { $errors[] = 'Atleta nao pode ser titular e reserva ao mesmo tempo.'; continue; }
            $reserveSeen[$athleteId] = true;
            $players[] = $this->playerRow($byId[$athleteId], 'reserve', null, $order + 1, false, 0, 0);
        }
        return ['players' => $players, 'errors' => array_values(array_unique($errors)), 'starterIds' => array_values(array_map(static fn (array $player): int => (int) $player['athlete_id'], array_filter($players, static fn (array $player): bool => $player['role'] === 'starter'))), 'captain' => (int) ($data['captain_athlete_id'] ?? 0), 'goalkeeper' => (int) ($data['goalkeeper_athlete_id'] ?? 0)];
    }

    private function validateSelection(array $built, array $athletes, array $data, bool $confirm, array $match): array
    {
        $errors = [];
        if (!$confirm) return [];
        if (in_array($match['status'], ['cancelled', 'finished', 'homologated'], true)) $errors[] = 'Partida encerrada nao aceita confirmacao de escalacao.';
        if (count($built['starterIds']) !== 11) $errors[] = 'Confirmacao exige exatamente 11 titulares.';
        if (!$built['captain'] || !in_array($built['captain'], $built['starterIds'], true)) $errors[] = 'Escolha um capitao titular.';
        if (!$built['goalkeeper'] || !in_array($built['goalkeeper'], $built['starterIds'], true)) $errors[] = 'Escolha um goleiro titular.';
        $athleteMap = [];
        foreach ($athletes as $athlete) $athleteMap[(int) $athlete['id']] = $athlete;
        if ($built['goalkeeper'] && isset($athleteMap[$built['goalkeeper']]) && !$this->hasPosition($athleteMap[$built['goalkeeper']], 'goalkeeper')) $errors[] = 'O goleiro precisa possuir posicao de goleiro principal ou secundaria.';
        return array_values(array_unique($errors));
    }

    private function playerRow(array $athlete, string $role, ?string $slotKey, int $order, bool $outOfPosition, int $captain, int $goalkeeper): array
    {
        return ['athlete_id' => (int) $athlete['id'], 'role' => $role, 'slot_key' => $slotKey, 'position_code' => (string) $athlete['primary_position_code'], 'shirt_number' => $athlete['preferred_number'] ?: null, 'is_captain' => (int) $athlete['id'] === $captain ? 1 : 0, 'is_goalkeeper' => (int) $athlete['id'] === $goalkeeper ? 1 : 0, 'is_out_of_position' => $outOfPosition ? 1 : 0, 'display_order' => $order];
    }

    private function positionScore(array $athlete, array $slot): int
    {
        $slotCode = (string) $slot['position_code'];
        $secondary = array_filter(explode(',', (string) ($athlete['secondary_position_codes'] ?? '')));
        $secondaryGroups = array_filter(explode(',', (string) ($athlete['secondary_position_groups'] ?? '')));
        if ((string) $athlete['primary_position_code'] === $slotCode) return 100;
        if (in_array($slotCode, $secondary, true)) return 80;
        if ((string) $athlete['primary_position_group'] === (string) $slot['position_group']) return 50;
        if (in_array((string) $slot['position_group'], $secondaryGroups, true)) return 40;
        return 0;
    }

    private function hasPosition(array $athlete, string $code): bool
    {
        return $athlete['primary_position_code'] === $code || in_array($code, array_filter(explode(',', (string) ($athlete['secondary_position_codes'] ?? ''))), true);
    }

    private function scope(array $user): string
    {
        $roles = $this->authorization->roleKeys($user);
        return in_array('administrator', $roles, true) ? 'administrator' : 'team';
    }

    private function scopeUserId(array $user): int
    {
        return $this->scope($user) === 'administrator' ? 0 : (int) $user['id'];
    }
}
