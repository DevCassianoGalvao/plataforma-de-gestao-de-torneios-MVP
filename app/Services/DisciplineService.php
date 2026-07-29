<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\DisciplineRepository;

final class DisciplineService
{
    public function __construct(private readonly DisciplineRepository $discipline, private readonly AuditService $audit)
    {
    }

    public function processHomologatedMatch(array $match, int $userId): array
    {
        $matchId = (int) $match['id'];
        $context = $this->discipline->match($matchId) ?: $match;
        if (($context['status'] ?? $context['match_status'] ?? '') !== 'homologated') return $this->fail('A partida precisa estar homologada para processar disciplina.');
        $settings = $this->discipline->settings((int) $context['championship_id']);
        if ((int) ($settings['reset_cards_enabled'] ?? 0) === 1 && $this->resetMatchesPhase($context, (string) ($settings['reset_cards_stage'] ?? ''))) $this->discipline->resetCards($context, $userId, 'Limpeza configurada na transicao de fase.');
        $fulfilled = $this->fulfillForTeam($context, (int) $context['home_team_id'], $userId) + $this->fulfillForTeam($context, (int) $context['away_team_id'], $userId);
        $created = 0;
        foreach ($this->discipline->matchCardEvents($matchId) as $event) {
            $personType = (string) ($event['person_type'] ?: 'athlete');
            $personId = $personType === 'staff' ? (int) $event['team_staff_id'] : (int) $event['athlete_id'];
            if (!$personId || !(int) $event['team_id']) continue;
            $sourceKey = 'event:' . (int) $event['id'];
            $status = (int) $event['valid'] === 1 ? 'considered' : 'cancelled';
            $ledgerId = $this->discipline->createLedger(['championship_id' => $context['championship_id'], 'match_id' => $matchId, 'phase_id' => $context['phase_id'], 'team_id' => $event['team_id'], 'person_type' => $personType, 'athlete_id' => $personType === 'athlete' ? $event['athlete_id'] : null, 'team_staff_id' => $personType === 'staff' ? $event['team_staff_id'] : null, 'card_type' => $event['event_type'], 'source' => 'match_event', 'source_event_id' => $event['id'], 'source_key' => $sourceKey, 'status' => $status, 'occurred_at' => $this->occurredAt($context), 'created_by' => $userId]);
            $created++;
            if ($status !== 'considered') continue;
            if (in_array($event['event_type'], ['red', 'second_yellow'], true) && (int) ($settings['red_card_automatic_suspension'] ?? 1) === 1) {
                $source = 'card:' . $sourceKey;
                $suspensionId = $this->discipline->createSuspension(['championship_id' => $context['championship_id'], 'team_id' => $event['team_id'], 'person_type' => $personType, 'athlete_id' => $personType === 'athlete' ? $event['athlete_id'] : null, 'team_staff_id' => $personType === 'staff' ? $event['team_staff_id'] : null, 'origin' => $event['event_type'], 'suspension_type' => 'automatic_card', 'total_matches' => max(1, (int) ($settings['red_card_suspension_matches'] ?? 1)), 'generating_match_id' => $matchId, 'source_key' => $source, 'notes' => 'Suspensao automatica gerada na homologacao.', 'created_by' => $userId]);
                $this->discipline->historyInsert(['championship_id' => $context['championship_id'], 'ledger_id' => $ledgerId, 'suspension_id' => $suspensionId, 'action' => 'automatic_suspension_created', 'details' => $event['event_type'], 'changed_by' => $userId]);
            }
            if ($event['event_type'] === 'yellow') {
                $count = $this->discipline->activeYellowCount((int) $context['championship_id'], $personType, $personType === 'athlete' ? (int) $event['athlete_id'] : null, $personType === 'staff' ? (int) $event['team_staff_id'] : null);
                $threshold = max(1, (int) ($settings['yellow_cards_for_suspension'] ?? 3));
                if ($count >= $threshold && $count % $threshold === 0) {
                    $source = 'yellow:' . $personType . ':' . $personId . ':threshold:' . intdiv($count, $threshold) . ':phase:' . (int) $context['phase_id'];
                    $suspensionId = $this->discipline->createSuspension(['championship_id' => $context['championship_id'], 'team_id' => $event['team_id'], 'person_type' => $personType, 'athlete_id' => $personType === 'athlete' ? $event['athlete_id'] : null, 'team_staff_id' => $personType === 'staff' ? $event['team_staff_id'] : null, 'origin' => 'yellow_accumulation', 'suspension_type' => 'automatic_card', 'total_matches' => max(1, (int) ($settings['yellow_suspension_matches'] ?? 1)), 'generating_match_id' => $matchId, 'source_key' => $source, 'notes' => 'Suspensao automatica por acumulacao de cartoes.', 'created_by' => $userId]);
                    $this->discipline->historyInsert(['championship_id' => $context['championship_id'], 'ledger_id' => $ledgerId, 'suspension_id' => $suspensionId, 'action' => 'yellow_accumulation_reached', 'details' => 'Cartoes acumulados: ' . $count, 'changed_by' => $userId]);
                }
            }
        }
        $this->discipline->markProcessed((int) $context['championship_id'], $matchId, $userId);
        $this->audit->record('discipline.match_processed', $userId, 'match', $matchId, ['ledger_entries' => $created, 'fulfillments' => $fulfilled], null);
        return ['ok' => true, 'errors' => [], 'ledger_entries' => $created, 'fulfillments' => $fulfilled];
    }

    public function activeSuspension(int $championshipId, string $personType, int $personId, int $matchId): ?array
    {
        return $this->discipline->activeSuspension($championshipId, $personType, $personId, $matchId);
    }

    public function activeForMatch(int $championshipId, int $matchId, int $teamId): array
    {
        return $this->discipline->activeForMatch($championshipId, $matchId, $teamId);
    }

    public function createManual(array $user, array $data): array
    {
        $personType = trim((string) ($data['person_type'] ?? 'athlete'));
        $matches = DisciplineRules::matches($data['total_matches'] ?? 0);
        $teamId = (int) ($data['team_id'] ?? 0);
        $personId = (int) ($data['person_id'] ?? 0);
        if (!DisciplineRules::personType($personType) || !$teamId || !$personId || !$matches) return $this->fail('Pessoa, equipe e quantidade de partidas sao obrigatorios.');
        if (!$this->discipline->personBelongsToTeam($personType, $personId, $teamId, (int) $data['championship_id'])) return $this->fail('Pessoa fora da equipe ou campeonato informado.');
        $source = 'manual:' . bin2hex(random_bytes(12));
        $id = $this->discipline->manualSuspension(['championship_id' => (int) $data['championship_id'], 'team_id' => $teamId, 'person_type' => $personType, 'athlete_id' => $personType === 'athlete' ? $personId : null, 'team_staff_id' => $personType === 'staff' ? $personId : null, 'origin' => 'manual', 'suspension_type' => 'manual', 'total_matches' => $matches, 'generating_match_id' => null, 'source_key' => $source, 'notes' => trim((string) ($data['notes'] ?? '')) ?: null, 'created_by' => (int) $user['id']]);
        $this->audit->record('discipline.manual_suspension_created', (int) $user['id'], 'discipline_suspension', $id, ['championship_id' => $data['championship_id']], null);
        return ['ok' => true, 'errors' => [], 'id' => $id];
    }

    public function cancelCard(array $user, int $eventId, string $reason): array
    {
        if (trim($reason) === '') return $this->fail('Informe o motivo da anulacao.');
        $event = $this->discipline->event($eventId);
        if (!$event) return $this->fail('Evento disciplinar inexistente.');
        if (!$this->discipline->cancelEvent($eventId, (int) $user['id'], $reason)) return $this->fail('Cartao inexistente ou ja anulado.');
        $ledgerId = $this->discipline->cancelLedgerForEvent($eventId, (int) $user['id'], $reason);
        if (!$ledgerId) return $this->fail('Cartao ainda nao processado ou ja anulado.');
        $ledger = $this->discipline->ledgerById($ledgerId);
        $this->discipline->historyInsert(['championship_id' => $ledger['championship_id'], 'ledger_id' => $ledgerId, 'action' => 'card_cancelled', 'details' => $reason, 'changed_by' => (int) $user['id']]);
        $automatic = $this->discipline->revokeAutomaticBySource('card:event:' . $eventId, (int) $user['id'], 'Cartao de origem anulado: ' . $reason);
        if ($automatic) $this->discipline->historyInsert(['championship_id' => $automatic['championship_id'], 'suspension_id' => $automatic['id'], 'action' => 'suspension_revoked_by_card_cancellation', 'details' => $reason, 'changed_by' => (int) $user['id']]);
        return ['ok' => true, 'errors' => []];
    }

    public function list(int $championshipId, array $filters = []): array
    {
        $teamIds = array_map('intval', (array) ($filters['allowed_team_ids'] ?? []));
        if ($teamIds !== []) $filters['team_id'] = $teamIds[0];
        $allowed = array_map('intval', (array) ($filters['allowed_team_ids'] ?? []));
        $keepTeam = static fn (array $row): bool => $allowed === [] || in_array((int) ($row['team_id'] ?? 0), $allowed, true);
        return ['summary' => $this->discipline->summary($championshipId, $filters), 'suspensions' => array_values(array_filter($this->discipline->suspensions($championshipId, $filters), $keepTeam)), 'ledger' => array_values(array_filter($this->discipline->ledger($championshipId), $keepTeam)), 'history' => $this->discipline->history($championshipId)];
    }

    private function fulfillForTeam(array $match, int $teamId, int $userId): int
    {
        $count = 0;
        foreach ($this->discipline->activeForMatch((int) $match['championship_id'], (int) $match['id'], $teamId) as $suspension) {
            if ($this->discipline->fulfillmentExists((int) $suspension['id'], (int) $match['id'])) continue;
            $id = $this->discipline->fulfill((int) $suspension['id'], ['championship_id' => $match['championship_id'], 'match_id' => $match['id'], 'team_id' => $teamId, 'person_type' => $suspension['person_type'], 'athlete_id' => $suspension['athlete_id'], 'team_staff_id' => $suspension['team_staff_id'], 'created_by' => $userId]);
            $this->discipline->historyInsert(['championship_id' => $match['championship_id'], 'suspension_id' => $suspension['id'], 'fulfillment_id' => $id, 'action' => 'suspension_fulfilled', 'details' => 'Partida elegivel homologada.', 'changed_by' => $userId]);
            $count++;
        }
        return $count;
    }

    private function resetMatchesPhase(array $match, string $configuredStage): bool
    {
        if ($configuredStage === '') return false;
        return in_array(strtolower($configuredStage), [strtolower((string) ($match['phase_slug'] ?? '')), strtolower((string) ($match['phase_name'] ?? '')), (string) $match['phase_id']], true);
    }

    private function occurredAt(array $match): string
    {
        return trim((string) ($match['match_date'] ?? '')) . ' ' . trim((string) ($match['match_time'] ?? '00:00:00')) ?: date('Y-m-d H:i:s');
    }

    private function fail(string $message): array
    {
        return ['ok' => false, 'errors' => [$message]];
    }
}
