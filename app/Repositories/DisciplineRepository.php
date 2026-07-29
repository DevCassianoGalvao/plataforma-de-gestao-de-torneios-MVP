<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class DisciplineRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function match(int $matchId): ?array
    {
        $statement = $this->pdo->prepare('SELECT m.*, p.name AS phase_name, p.slug AS phase_slug, c.name AS championship_name FROM matches m INNER JOIN competition_phases p ON p.id = m.phase_id INNER JOIN championships c ON c.id = m.championship_id WHERE m.id = ? LIMIT 1');
        $statement->execute([$matchId]);
        return $statement->fetch() ?: null;
    }

    public function event(int $eventId): ?array
    {
        $statement = $this->pdo->prepare('SELECT e.*, m.championship_id, m.phase_id, m.home_team_id, m.away_team_id FROM match_operation_events e INNER JOIN matches m ON m.id = e.match_id WHERE e.id = ? LIMIT 1');
        $statement->execute([$eventId]);
        return $statement->fetch() ?: null;
    }

    public function personBelongsToTeam(string $personType, int $personId, int $teamId, int $championshipId): bool
    {
        $table = $personType === 'staff' ? 'team_staff' : 'athletes';
        $deleted = 'p.deleted_at IS NULL';
        $statement = $this->pdo->prepare("SELECT p.id FROM {$table} p INNER JOIN teams t ON t.id = p.team_id WHERE p.id = ? AND p.team_id = ? AND t.championship_id = ? AND {$deleted} LIMIT 1");
        $statement->execute([$personId, $teamId, $championshipId]);
        return (bool) $statement->fetchColumn();
    }

    public function settings(int $championshipId): array
    {
        $statement = $this->pdo->prepare("SELECT ds.* FROM regulation_discipline_settings ds INNER JOIN regulations r ON r.id = ds.regulation_id WHERE r.championship_id = ? AND r.status = 'published' ORDER BY r.version_number DESC LIMIT 1");
        $statement->execute([$championshipId]);
        return $statement->fetch() ?: [
            'yellow_cards_for_suspension' => 3,
            'yellow_suspension_matches' => 1,
            'red_card_automatic_suspension' => 1,
            'red_card_suspension_matches' => 1,
            'reset_cards_enabled' => 0,
            'reset_cards_stage' => null,
        ];
    }

    public function matchCardEvents(int $matchId): array
    {
        $statement = $this->pdo->prepare("SELECT e.*, m.championship_id, m.phase_id, m.match_date, m.match_time FROM match_operation_events e INNER JOIN matches m ON m.id = e.match_id WHERE e.match_id = ? AND e.event_type IN ('yellow', 'second_yellow', 'red') ORDER BY e.id");
        $statement->execute([$matchId]);
        return $statement->fetchAll();
    }

    public function ledgerBySource(string $sourceKey): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM discipline_ledger WHERE source_key = ? LIMIT 1');
        $statement->execute([$sourceKey]);
        return $statement->fetch() ?: null;
    }

    public function createLedger(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $statement = $this->pdo->prepare('INSERT INTO discipline_ledger (championship_id, match_id, phase_id, team_id, person_type, athlete_id, team_staff_id, card_type, source, source_event_id, source_key, status, occurred_at, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id), status = IF(status = \'cancelled\', status, VALUES(status)), updated_at = VALUES(updated_at)');
        $statement->execute([$data['championship_id'], $data['match_id'], $data['phase_id'], $data['team_id'], $data['person_type'], $data['athlete_id'], $data['team_staff_id'], $data['card_type'], $data['source'], $data['source_event_id'], $data['source_key'], $data['status'] ?? 'considered', $data['occurred_at'], $data['created_by'], $now, $now]);
        return (int) ($this->pdo->lastInsertId() ?: $this->ledgerBySource((string) $data['source_key'])['id']);
    }

    public function cancelLedgerForEvent(int $eventId, int $userId, string $reason): ?int
    {
        $ledger = $this->ledgerBySource('event:' . $eventId);
        if (!$ledger) return null;
        $now = date('Y-m-d H:i:s');
        $this->pdo->prepare("UPDATE discipline_ledger SET status = 'cancelled', cancelled_by = ?, cancelled_at = ?, cancellation_reason = ?, updated_at = ? WHERE id = ? AND status = 'considered'")->execute([$userId, $now, $reason, $now, $ledger['id']]);
        return (int) $ledger['id'];
    }

    public function ledgerById(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM discipline_ledger WHERE id = ? LIMIT 1');
        $statement->execute([$id]);
        return $statement->fetch() ?: null;
    }

    public function cancelEvent(int $eventId, int $userId, string $reason): bool
    {
        $statement = $this->pdo->prepare('UPDATE match_operation_events SET valid = 0, cancelled_by = ?, cancelled_at = ?, cancellation_reason = ?, updated_at = ? WHERE id = ? AND valid = 1');
        $now = date('Y-m-d H:i:s');
        $statement->execute([$userId, $now, $reason, $now, $eventId]);
        return $statement->rowCount() > 0;
    }

    public function activeYellowCount(int $championshipId, string $personType, ?int $athleteId, ?int $staffId): int
    {
        $statement = $this->pdo->prepare("SELECT COUNT(*) FROM discipline_ledger WHERE championship_id = ? AND person_type = ? AND athlete_id <=> ? AND team_staff_id <=> ? AND card_type = 'yellow' AND status = 'considered'");
        $statement->execute([$championshipId, $personType, $athleteId, $staffId]);
        return (int) $statement->fetchColumn();
    }

    public function suspensionBySource(string $sourceKey): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM discipline_suspensions WHERE source_key = ? LIMIT 1');
        $statement->execute([$sourceKey]);
        return $statement->fetch() ?: null;
    }

    public function revokeAutomaticBySource(string $sourceKey, int $userId, string $reason): ?array
    {
        $row = $this->suspensionBySource($sourceKey);
        if (!$row || !in_array($row['status'], ['active', 'pending'], true) || (int) $row['fulfilled_matches'] > 0) return null;
        $now = date('Y-m-d H:i:s');
        $this->pdo->prepare("UPDATE discipline_suspensions SET status = 'revoked', revoked_by = ?, revoked_at = ?, revocation_reason = ?, updated_at = ? WHERE id = ?")->execute([$userId, $now, $reason, $now, $row['id']]);
        $row['status'] = 'revoked';
        return $row;
    }

    public function createSuspension(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $statement = $this->pdo->prepare('INSERT INTO discipline_suspensions (championship_id, team_id, person_type, athlete_id, team_staff_id, origin, suspension_type, total_matches, fulfilled_matches, status, generating_match_id, source_key, notes, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, \'active\', ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)');
        $statement->execute([$data['championship_id'], $data['team_id'], $data['person_type'], $data['athlete_id'], $data['team_staff_id'], $data['origin'], $data['suspension_type'], $data['total_matches'], $data['generating_match_id'], $data['source_key'], $data['notes'] ?? null, $data['created_by'], $now, $now]);
        return (int) ($this->pdo->lastInsertId() ?: $this->suspensionBySource((string) $data['source_key'])['id']);
    }

    public function activeSuspension(int $championshipId, string $personType, int $personId, ?int $beforeMatchId = null): ?array
    {
        $personColumn = $personType === 'staff' ? 'team_staff_id' : 'athlete_id';
        $sql = "SELECT s.*, GREATEST(s.total_matches - s.fulfilled_matches, 0) AS remaining_matches FROM discipline_suspensions s WHERE s.championship_id = ? AND s.person_type = ? AND s.{$personColumn} = ? AND s.status = 'active' AND s.fulfilled_matches < s.total_matches";
        $params = [$championshipId, $personType, $personId];
        if ($beforeMatchId !== null) { $sql .= ' AND (s.generating_match_id IS NULL OR s.generating_match_id <> ?)'; $params[] = $beforeMatchId; }
        $sql .= ' ORDER BY s.created_at, s.id LIMIT 1';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetch() ?: null;
    }

    public function activeForMatch(int $championshipId, int $matchId, int $teamId): array
    {
        $statement = $this->pdo->prepare("SELECT s.*, GREATEST(s.total_matches - s.fulfilled_matches, 0) AS remaining_matches, COALESCE(a.sporting_name, a.full_name, ts.display_name, ts.full_name) AS person_name FROM discipline_suspensions s LEFT JOIN athletes a ON a.id = s.athlete_id LEFT JOIN team_staff ts ON ts.id = s.team_staff_id WHERE s.championship_id = ? AND s.team_id = ? AND s.status = 'active' AND s.fulfilled_matches < s.total_matches AND (s.generating_match_id IS NULL OR s.generating_match_id <> ?) ORDER BY s.created_at, s.id");
        $statement->execute([$championshipId, $teamId, $matchId]);
        return $statement->fetchAll();
    }

    public function fulfillmentExists(int $suspensionId, int $matchId): bool
    {
        $statement = $this->pdo->prepare('SELECT id FROM discipline_suspension_fulfillments WHERE suspension_id = ? AND match_id = ? AND status = \'counted\' LIMIT 1');
        $statement->execute([$suspensionId, $matchId]);
        return (bool) $statement->fetchColumn();
    }

    public function fulfill(int $suspensionId, array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $statement = $this->pdo->prepare('INSERT INTO discipline_suspension_fulfillments (suspension_id, championship_id, match_id, team_id, person_type, athlete_id, team_staff_id, status, fulfilled_at, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, \'counted\', ?, ?)');
        $statement->execute([$suspensionId, $data['championship_id'], $data['match_id'], $data['team_id'], $data['person_type'], $data['athlete_id'], $data['team_staff_id'], $now, $data['created_by']]);
        $id = (int) $this->pdo->lastInsertId();
        $this->pdo->prepare("UPDATE discipline_suspensions SET fulfilled_matches = fulfilled_matches + 1, status = CASE WHEN fulfilled_matches + 1 >= total_matches THEN 'fulfilled' ELSE 'active' END, updated_at = ? WHERE id = ? AND status = 'active'")->execute([$now, $suspensionId]);
        return $id;
    }

    public function processingRun(int $matchId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM discipline_processing_runs WHERE match_id = ? LIMIT 1');
        $statement->execute([$matchId]);
        return $statement->fetch() ?: null;
    }

    public function markProcessed(int $championshipId, int $matchId, int $userId): void
    {
        $now = date('Y-m-d H:i:s');
        $this->pdo->prepare('INSERT INTO discipline_processing_runs (championship_id, match_id, status, processed_by, processed_at, updated_at) VALUES (?, ?, \'processed\', ?, ?, ?) ON DUPLICATE KEY UPDATE status = \'processed\', processed_by = VALUES(processed_by), processed_at = VALUES(processed_at), updated_at = VALUES(updated_at)')->execute([$championshipId, $matchId, $userId, $now, $now]);
    }

    public function resetDone(string $key): bool
    {
        $statement = $this->pdo->prepare('SELECT id FROM discipline_card_resets WHERE reset_key = ? LIMIT 1');
        $statement->execute([$key]);
        return (bool) $statement->fetchColumn();
    }

    public function resetCards(array $match, int $userId, string $reason): void
    {
        $key = 'phase:' . (int) $match['phase_id'];
        if ($this->resetDone($key)) return;
        $now = date('Y-m-d H:i:s');
        $this->pdo->prepare("UPDATE discipline_ledger SET status = 'cleared', updated_at = ? WHERE championship_id = ? AND phase_id <> ? AND status = 'considered'")->execute([$now, $match['championship_id'], $match['phase_id']]);
        $this->pdo->prepare('INSERT INTO discipline_card_resets (championship_id, phase_id, reset_key, reason, executed_by, executed_at) VALUES (?, ?, ?, ?, ?, ?)')->execute([$match['championship_id'], $match['phase_id'], $key, $reason, $userId, $now]);
    }

    public function summary(int $championshipId, array $filters = []): array
    {
        $conditions = ['l.championship_id = ?', "l.status = 'considered'"];
        $params = [$championshipId];
        if (($filters['team_id'] ?? '') !== '') { $conditions[] = 'l.team_id = ?'; $params[] = (int) $filters['team_id']; }
        if (($filters['person_type'] ?? '') !== '') { $conditions[] = 'l.person_type = ?'; $params[] = $filters['person_type']; }
        $sql = "SELECT l.person_type, l.athlete_id, l.team_staff_id, l.team_id, COALESCE(a.sporting_name, a.full_name, ts.display_name, ts.full_name) AS person_name, t.name AS team_name, SUM(l.card_type = 'yellow') AS yellow_cards, SUM(l.card_type = 'red') AS red_cards, SUM(l.card_type = 'second_yellow') AS second_yellow_cards FROM discipline_ledger l INNER JOIN teams t ON t.id = l.team_id LEFT JOIN athletes a ON a.id = l.athlete_id LEFT JOIN team_staff ts ON ts.id = l.team_staff_id WHERE " . implode(' AND ', $conditions) . ' GROUP BY l.person_type, l.athlete_id, l.team_staff_id, l.team_id, person_name, t.name ORDER BY yellow_cards DESC, person_name';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function suspensions(int $championshipId, array $filters = []): array
    {
        $sql = "SELECT s.*, COALESCE(a.sporting_name, a.full_name, ts.display_name, ts.full_name) AS person_name, t.name AS team_name FROM discipline_suspensions s INNER JOIN teams t ON t.id = s.team_id LEFT JOIN athletes a ON a.id = s.athlete_id LEFT JOIN team_staff ts ON ts.id = s.team_staff_id WHERE s.championship_id = ?";
        $params = [$championshipId];
        if (($filters['status'] ?? '') !== '') { $sql .= ' AND s.status = ?'; $params[] = $filters['status']; }
        if (($filters['team_id'] ?? '') !== '') { $sql .= ' AND s.team_id = ?'; $params[] = (int) $filters['team_id']; }
        $sql .= ' ORDER BY s.status, s.created_at DESC, s.id DESC';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function ledger(int $championshipId): array
    {
        $statement = $this->pdo->prepare("SELECT l.*, COALESCE(a.sporting_name, a.full_name, ts.display_name, ts.full_name) AS person_name, t.name AS team_name, m.match_date FROM discipline_ledger l INNER JOIN teams t ON t.id = l.team_id LEFT JOIN athletes a ON a.id = l.athlete_id LEFT JOIN team_staff ts ON ts.id = l.team_staff_id LEFT JOIN matches m ON m.id = l.match_id WHERE l.championship_id = ? ORDER BY l.occurred_at DESC, l.id DESC");
        $statement->execute([$championshipId]);
        return $statement->fetchAll();
    }

    public function history(int $championshipId): array
    {
        $statement = $this->pdo->prepare('SELECT h.*, u.name AS user_name FROM discipline_history h INNER JOIN users u ON u.id = h.changed_by WHERE h.championship_id = ? ORDER BY h.created_at DESC, h.id DESC');
        $statement->execute([$championshipId]);
        return $statement->fetchAll();
    }

    public function historyInsert(array $data): void
    {
        $this->pdo->prepare('INSERT INTO discipline_history (championship_id, ledger_id, suspension_id, fulfillment_id, action, details, changed_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')->execute([$data['championship_id'], $data['ledger_id'] ?? null, $data['suspension_id'] ?? null, $data['fulfillment_id'] ?? null, $data['action'], $data['details'] ?? null, $data['changed_by'], date('Y-m-d H:i:s')]);
    }

    public function manualSuspension(array $data): int
    {
        $id = $this->createSuspension($data);
        $this->historyInsert(['championship_id' => $data['championship_id'], 'suspension_id' => $id, 'action' => 'manual_suspension_created', 'details' => $data['notes'] ?? null, 'changed_by' => $data['created_by']]);
        return $id;
    }

    public function revokeSuspension(int $id, int $userId, string $reason): bool
    {
        $statement = $this->pdo->prepare("UPDATE discipline_suspensions SET status = 'revoked', revoked_by = ?, revoked_at = ?, revocation_reason = ?, updated_at = ? WHERE id = ? AND status IN ('active', 'pending')");
        $now = date('Y-m-d H:i:s');
        $statement->execute([$userId, $now, $reason, $now, $id]);
        if ($statement->rowCount() < 1) return false;
        $row = $this->suspension($id);
        $this->historyInsert(['championship_id' => $row['championship_id'], 'suspension_id' => $id, 'action' => 'suspension_revoked', 'details' => $reason, 'changed_by' => $userId]);
        return true;
    }

    public function suspension(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM discipline_suspensions WHERE id = ? LIMIT 1');
        $statement->execute([$id]);
        return $statement->fetch() ?: null;
    }
}
