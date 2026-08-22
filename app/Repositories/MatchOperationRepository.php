<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class MatchOperationRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function find(int $matchId): ?array
    {
        $statement = $this->pdo->prepare('SELECT mo.*, m.championship_id, m.phase_id, m.group_id, m.round_id, m.match_date, m.match_time, m.status AS match_status, m.venue_id, c.name AS championship_name, p.name AS phase_name, g.name AS group_name, r.round_number, v.name AS venue_name, ht.id AS home_team_id, ht.name AS home_team_name, ht.short_name AS home_team_short_name, ht.slug AS home_team_slug, ht.shield_path AS home_team_shield_path, at.id AS away_team_id, at.name AS away_team_name, at.short_name AS away_team_short_name, at.slug AS away_team_slug, at.shield_path AS away_team_shield_path FROM match_operations mo INNER JOIN matches m ON m.id = mo.match_id INNER JOIN championships c ON c.id = m.championship_id INNER JOIN competition_phases p ON p.id = m.phase_id INNER JOIN competition_groups g ON g.id = m.group_id INNER JOIN competition_rounds r ON r.id = m.round_id INNER JOIN teams ht ON ht.id = m.home_team_id INNER JOIN teams at ON at.id = m.away_team_id LEFT JOIN venues v ON v.id = m.venue_id WHERE mo.match_id = ? LIMIT 1');
        $statement->execute([$matchId]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function ensure(int $matchId, int $userId): array
    {
        $now = date('Y-m-d H:i:s');
        $statement = $this->pdo->prepare('INSERT INTO match_operations (match_id, status, created_by, created_at, updated_at) VALUES (?, \'open\', ?, ?, ?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)');
        $statement->execute([$matchId, $userId, $now, $now]);
        $operation = $this->find($matchId);
        if (!$operation) throw new \RuntimeException('Operacao da partida nao encontrada.');
        return $operation;
    }

    public function events(int $matchId): array
    {
        $statement = $this->pdo->prepare("SELECT e.*, t.name AS team_name, a.full_name AS athlete_name, a.sporting_name AS athlete_sporting_name, ts.full_name AS staff_name, ts.display_name AS staff_display_name, ra.full_name AS related_athlete_name, ra.sporting_name AS related_athlete_sporting_name FROM match_operation_events e LEFT JOIN (SELECT ml.match_id, mlp.athlete_id, MIN(ml.team_id) AS team_id FROM match_lineups ml INNER JOIN match_lineup_players mlp ON mlp.lineup_id = ml.id WHERE ml.status = 'confirmed' GROUP BY ml.match_id, mlp.athlete_id) lt ON lt.match_id = e.match_id AND lt.athlete_id = e.athlete_id LEFT JOIN teams t ON t.id = COALESCE(e.team_id, lt.team_id) LEFT JOIN athletes a ON a.id = e.athlete_id LEFT JOIN team_staff ts ON ts.id = e.team_staff_id LEFT JOIN athletes ra ON ra.id = e.related_athlete_id WHERE e.match_id = ? ORDER BY e.created_at, e.id");
        $statement->execute([$matchId]);
        return $statement->fetchAll();
    }

    public function substitutions(int $matchId): array
    {
        $statement = $this->pdo->prepare('SELECT s.*, t.name AS team_name, ao.full_name AS athlete_out_name, ao.sporting_name AS athlete_out_sporting_name, ai.full_name AS athlete_in_name, ai.sporting_name AS athlete_in_sporting_name FROM match_substitutions s INNER JOIN teams t ON t.id = s.team_id INNER JOIN athletes ao ON ao.id = s.athlete_out_id INNER JOIN athletes ai ON ai.id = s.athlete_in_id WHERE s.match_id = ? ORDER BY s.created_at, s.id');
        $statement->execute([$matchId]);
        return $statement->fetchAll();
    }

    public function officials(int $matchId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM match_officials WHERE match_id = ? ORDER BY display_order, role, id');
        $statement->execute([$matchId]);
        return $statement->fetchAll();
    }

    public function history(int $operationId): array
    {
        $statement = $this->pdo->prepare('SELECT h.*, u.name AS user_name FROM match_operation_history h INNER JOIN users u ON u.id = h.changed_by WHERE h.operation_id = ? ORDER BY h.created_at, h.id');
        $statement->execute([$operationId]);
        return $statement->fetchAll();
    }

    public function rectifications(int $matchId): array
    {
        $statement = $this->pdo->prepare('SELECT rr.*, requester.name AS requested_by_name, decider.name AS decided_by_name FROM match_operation_rectifications rr INNER JOIN users requester ON requester.id = rr.requested_by LEFT JOIN users decider ON decider.id = rr.decided_by WHERE rr.match_id = ? ORDER BY rr.requested_at DESC, rr.id DESC');
        $statement->execute([$matchId]);
        return $statement->fetchAll();
    }

    public function eventForMatch(int $matchId, int $eventId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM match_operation_events WHERE id = ? AND match_id = ? LIMIT 1');
        $statement->execute([$eventId, $matchId]);
        return $statement->fetch() ?: null;
    }

    public function score(array $operation): array
    {
        $events = $this->pdo->prepare("SELECT e.team_id, e.athlete_id, e.event_type, e.period, e.valid, lt.team_id AS lineup_team_id FROM match_operation_events e LEFT JOIN (SELECT ml.match_id, mlp.athlete_id, MIN(ml.team_id) AS team_id FROM match_lineups ml INNER JOIN match_lineup_players mlp ON mlp.lineup_id = ml.id WHERE ml.status = 'confirmed' GROUP BY ml.match_id, mlp.athlete_id) lt ON lt.match_id = e.match_id AND lt.athlete_id = e.athlete_id WHERE e.match_id = ?");
        $events->execute([(int) $operation['match_id']]);
        $homeScore = 0;
        $awayScore = 0;
        $homePenalties = 0;
        $awayPenalties = 0;
        $homeTeamId = (int) $operation['home_team_id'];
        $awayTeamId = (int) $operation['away_team_id'];
        foreach ($events->fetchAll() as $event) {
            if ((int) $event['valid'] !== 1) continue;
            $teamId = $event['team_id'] === null ? ($event['lineup_team_id'] === null ? null : (int) $event['lineup_team_id']) : (int) $event['team_id'];
            if ($event['team_id'] === null && $event['event_type'] === 'own_goal' && $teamId !== null) $teamId = $teamId === $homeTeamId ? $awayTeamId : ($teamId === $awayTeamId ? $homeTeamId : null);
            if ($event['period'] === 'penalties' && $event['event_type'] === 'penalty_scored') {
                if ($teamId === $homeTeamId) $homePenalties++;
                if ($teamId === $awayTeamId) $awayPenalties++;
                continue;
            }
            if (!in_array($event['event_type'], ['goal', 'own_goal'], true)) continue;
            if ($teamId === $homeTeamId) $homeScore++;
            if ($teamId === $awayTeamId) $awayScore++;
        }
        $row = ['home_score' => $homeScore, 'away_score' => $awayScore];
        if ($operation['administrative_home_score'] !== null && $operation['administrative_away_score'] !== null) {
            $row['home_score'] = (int) $operation['administrative_home_score'];
            $row['away_score'] = (int) $operation['administrative_away_score'];
            $row['administrative'] = true;
        } else {
            $row['administrative'] = false;
        }
        return ['home_score' => (int) $row['home_score'], 'away_score' => (int) $row['away_score'], 'home_penalties' => $homePenalties, 'away_penalties' => $awayPenalties, 'administrative' => (bool) $row['administrative']];
    }

    public function matchSettings(int $championshipId): array
    {
        $statement = $this->pdo->prepare("SELECT rms.* FROM regulation_match_settings rms INNER JOIN regulations r ON r.id = rms.regulation_id WHERE r.championship_id = ? AND r.status = 'published' ORDER BY r.version_number DESC LIMIT 1");
        $statement->execute([$championshipId]);
        return $statement->fetch() ?: ['substitutions_allowed' => 5, 'substitution_windows' => 3, 'extra_time_enabled' => 0, 'penalty_shootout_enabled' => 1];
    }

    public function createEvent(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $statement = $this->pdo->prepare('INSERT INTO match_operation_events (match_id, team_id, person_type, athlete_id, team_staff_id, related_athlete_id, event_type, period, minute, notes, valid, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?)');
        $statement->execute([$data['match_id'], $data['team_id'], $data['person_type'] ?? 'athlete', $data['athlete_id'], $data['team_staff_id'] ?? null, $data['related_athlete_id'], $data['event_type'], $data['period'], $data['minute'], $data['notes'], $data['created_by'], $now, $now]);
        return (int) $this->pdo->lastInsertId();
    }

    public function cancelEvent(int $eventId, int $userId, string $reason): bool
    {
        $statement = $this->pdo->prepare('UPDATE match_operation_events SET valid = 0, cancelled_by = ?, cancelled_at = ?, cancellation_reason = ?, updated_at = ? WHERE id = ? AND valid = 1');
        $now = date('Y-m-d H:i:s');
        $statement->execute([$userId, $now, $reason, $now, $eventId]);
        return $statement->rowCount() > 0;
    }

    public function returnForCorrection(int $operationId, int $matchId, int $userId, string $reason): void
    {
        $now = date('Y-m-d H:i:s');
        $operation = $this->operationById($operationId);
        $this->pdo->prepare("UPDATE match_operations SET status = 'open', review_status = 'returned', review_reason = ?, reviewed_by = ?, reviewed_at = ?, updated_at = ? WHERE id = ? AND status = 'awaiting_homologation'")->execute([$reason, $userId, $now, $now, $operationId]);
        $this->pdo->prepare("UPDATE matches SET status = 'confirmed', updated_at = ? WHERE id = ? AND status = 'finished'")->execute([$now, $matchId]);
        $this->historyInsert($operationId, 'returned_for_correction', $operation['status'], 'open', $reason, $userId);
    }

    public function rejectReview(int $operationId, int $userId, string $reason): void
    {
        $now = date('Y-m-d H:i:s');
        $operation = $this->operationById($operationId);
        $this->pdo->prepare("UPDATE match_operations SET review_status = 'rejected', review_reason = ?, reviewed_by = ?, reviewed_at = ?, updated_at = ? WHERE id = ? AND status = 'awaiting_homologation'")->execute([$reason, $userId, $now, $now, $operationId]);
        $this->historyInsert($operationId, 'review_rejected', $operation['status'], $operation['status'], $reason, $userId);
    }

    public function requestRectification(int $matchId, int $operationId, int $userId, string $reason, string $field = 'operacao', bool $critical = false): int
    {
        $now = date('Y-m-d H:i:s');
        $statement = $this->pdo->prepare("INSERT INTO match_operation_rectifications (match_id, operation_id, requested_by, requested_at, reason, requested_field, critical, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)");
        $statement->execute([$matchId, $operationId, $userId, $now, $reason, $field, $critical ? 1 : 0, $now, $now]);
        return (int) $this->pdo->lastInsertId();
    }

    public function decideRectification(int $rectificationId, int $matchId, int $userId, bool $approved, string $reason): bool
    {
        $statement = $this->pdo->prepare("SELECT * FROM match_operation_rectifications WHERE id = ? AND match_id = ? AND status = 'pending' LIMIT 1");
        $statement->execute([$rectificationId, $matchId]);
        $rectification = $statement->fetch();
        if (!$rectification) return false;
        $now = date('Y-m-d H:i:s');
        $status = $approved ? 'approved' : 'rejected';
        $this->pdo->prepare('UPDATE match_operation_rectifications SET status = ?, decided_by = ?, decided_at = ?, decision_reason = ?, updated_at = ? WHERE id = ?')->execute([$status, $userId, $now, $reason ?: null, $now, $rectificationId]);
        if ($approved) {
            $operation = $this->operationById((int) $rectification['operation_id']);
            $this->pdo->prepare("UPDATE match_operation_rectifications SET correction_by = ?, correction_started_at = ?, updated_at = ? WHERE id = ?")->execute([$userId, $now, $now, $rectificationId]);
            $this->pdo->prepare("UPDATE match_operations SET status = 'open', review_status = 'returned', review_reason = ?, reviewed_by = ?, reviewed_at = ?, updated_at = ? WHERE id = ? AND status = 'homologated'")->execute([$rectification['reason'], $userId, $now, $now, $operation['id']]);
            $this->pdo->prepare("UPDATE matches SET status = 'confirmed', updated_at = ? WHERE id = ? AND status = 'homologated'")->execute([$now, $matchId]);
            $this->pdo->prepare("UPDATE match_publications SET status = 'internal', cancelled_at = ?, cancelled_by = ?, reason = ?, updated_at = ? WHERE match_id = ? AND status = 'published'")->execute([$now, $userId, 'Retificacao aprovada: ' . $rectification['reason'], $now, $matchId]);
            $this->historyInsert((int) $operation['id'], 'rectification_approved', 'homologated', 'open', $rectification['reason'], $userId);
        }
        return true;
    }

    public function activeRectification(int $matchId): ?array
    {
        $statement = $this->pdo->prepare("SELECT * FROM match_operation_rectifications WHERE match_id = ? AND status IN ('approved','awaiting_reapproval') ORDER BY id DESC LIMIT 1");
        $statement->execute([$matchId]);
        return $statement->fetch() ?: null;
    }

    public function rectificationSettings(int $championshipId): array
    {
        $statement = $this->pdo->prepare('SELECT s.* FROM championship_rectification_settings s WHERE s.championship_id = ? LIMIT 1');
        $statement->execute([$championshipId]);
        return $statement->fetch() ?: ['championship_id' => $championshipId, 'require_second_approval' => 0];
    }

    public function updateEventForRectification(int $matchId, int $eventId, array $data): ?array
    {
        $event = $this->eventForMatch($matchId, $eventId);
        if (!$event) return null;
        $allowed = ['event_type', 'period', 'minute', 'team_id', 'athlete_id', 'related_athlete_id', 'notes'];
        $sets = []; $params = [];
        foreach ($allowed as $field) { if (array_key_exists($field, $data)) { $sets[] = $field . ' = ?'; $params[] = $data[$field]; } }
        if ($sets === []) return $event;
        $sets[] = 'updated_at = ?'; $params[] = date('Y-m-d H:i:s'); $params[] = $eventId; $params[] = $matchId;
        $this->pdo->prepare('UPDATE match_operation_events SET ' . implode(', ', $sets) . ' WHERE id = ? AND match_id = ?')->execute($params);
        return $this->eventForMatch($matchId, $eventId);
    }

    public function recordRectificationChange(int $rectificationId, int $matchId, string $entityType, int $entityId, string $field, mixed $old, mixed $new, string $reason, int $userId): void
    {
        $this->pdo->prepare('INSERT INTO match_rectification_changes (rectification_id, match_id, entity_type, entity_id, field_name, old_value, new_value, reason, changed_by, changed_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute([$rectificationId, $matchId, $entityType, $entityId, $field, is_scalar($old) || $old === null ? (string) $old : json_encode($old), is_scalar($new) || $new === null ? (string) $new : json_encode($new), $reason ?: null, $userId, date('Y-m-d H:i:s')]);
    }

    public function rectificationChanges(int $rectificationId): array
    {
        $statement = $this->pdo->prepare('SELECT c.*, u.name AS changed_by_name FROM match_rectification_changes c INNER JOIN users u ON u.id = c.changed_by WHERE c.rectification_id = ? ORDER BY c.changed_at, c.id');
        $statement->execute([$rectificationId]);
        return $statement->fetchAll();
    }

    public function submitRectificationForApproval(int $operationId, int $matchId, int $userId): bool
    {
        $now = date('Y-m-d H:i:s'); $operation = $this->operationById($operationId);
        if (!$operation || $operation['status'] !== 'open') return false;
        $statement = $this->pdo->prepare("UPDATE match_operations SET status = 'awaiting_homologation', review_status = 'awaiting_review', reviewed_by = ?, reviewed_at = ?, updated_at = ? WHERE id = ? AND status = 'open'");
        $statement->execute([$userId, $now, $now, $operationId]);
        if ($statement->rowCount() === 0) return false;
        $this->historyInsert($operationId, 'rectification_submitted', $operation['status'], 'awaiting_homologation', 'Correção enviada para nova aprovação.', $userId);
        return true;
    }

    public function completeRectification(int $rectificationId, int $matchId, int $userId): bool
    {
        $now = date('Y-m-d H:i:s');
        $statement = $this->pdo->prepare("UPDATE match_operation_rectifications SET status = 'awaiting_reapproval', correction_by = COALESCE(correction_by, ?), correction_completed_at = ?, updated_at = ? WHERE id = ? AND match_id = ? AND status = 'approved'");
        $statement->execute([$userId, $now, $now, $rectificationId, $matchId]);
        return $statement->rowCount() > 0;
    }

    public function finalizeRectification(int $rectificationId, int $userId): void
    {
        $this->pdo->prepare("UPDATE match_operation_rectifications SET status = 'completed', reapproved_by = ?, reapproved_at = ?, updated_at = ? WHERE id = ? AND status = 'awaiting_reapproval'")->execute([$userId, date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $rectificationId]);
    }

    public function createSubstitution(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $statement = $this->pdo->prepare('INSERT INTO match_substitutions (match_id, team_id, athlete_out_id, athlete_in_id, period, window_number, minute, notes, valid, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?)');
        $statement->execute([$data['match_id'], $data['team_id'], $data['athlete_out_id'], $data['athlete_in_id'], $data['period'], $data['window_number'], $data['minute'], $data['notes'], $data['created_by'], $now, $now]);
        return (int) $this->pdo->lastInsertId();
    }

    public function saveOfficials(int $matchId, array $officials, int $userId): void
    {
        $now = date('Y-m-d H:i:s');
        $this->pdo->prepare('DELETE FROM match_officials WHERE match_id = ?')->execute([$matchId]);
        $statement = $this->pdo->prepare('INSERT INTO match_officials (match_id, role, display_name, display_order, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
        foreach ($officials as $role => $name) {
            if (trim((string) $name) === '') continue;
            $statement->execute([$matchId, $role, trim((string) $name), 1, $userId, $now, $now]);
        }
    }

    public function updateTimes(int $operationId, array $times): void
    {
        $allowed = ['first_half_started_at', 'first_half_ended_at', 'second_half_started_at', 'second_half_ended_at', 'extra_time_started_at', 'extra_time_ended_at'];
        $sets = [];
        $params = [];
        foreach ($allowed as $column) {
            $sets[] = $column . ' = ?';
            $params[] = $times[$column] ?? null;
        }
        $sets[] = 'updated_at = ?';
        $params[] = date('Y-m-d H:i:s');
        $params[] = $operationId;
        $this->pdo->prepare('UPDATE match_operations SET ' . implode(', ', $sets) . ' WHERE id = ? AND status = \'open\'')->execute($params);
    }

    public function setAdministrativeResult(int $operationId, int $homeScore, int $awayScore, string $reason, int $userId): void
    {
        $now = date('Y-m-d H:i:s');
        $this->pdo->prepare('UPDATE match_operations SET administrative_home_score = ?, administrative_away_score = ?, administrative_result_reason = ?, administrative_result_by = ?, administrative_result_at = ?, updated_at = ? WHERE id = ? AND status = \'open\'')->execute([$homeScore, $awayScore, $reason, $userId, $now, $now, $operationId]);
    }

    public function finish(int $operationId, int $matchId, int $userId): void
    {
        $now = date('Y-m-d H:i:s');
        $operation = $this->operationById($operationId);
        $this->pdo->prepare("UPDATE match_operations SET status = 'awaiting_homologation', review_status = 'awaiting_review', review_reason = NULL, finalized_by = ?, finalized_at = ?, updated_at = ? WHERE id = ? AND status = 'open'")->execute([$userId, $now, $now, $operationId]);
        $this->pdo->prepare("UPDATE matches SET status = 'finished', updated_at = ? WHERE id = ? AND status NOT IN ('cancelled', 'homologated')")->execute([$now, $matchId]);
        $this->historyInsert($operationId, 'finished', $operation['status'], 'awaiting_homologation', 'Operacao finalizada e enviada para homologacao.', $userId);
    }

    public function homologate(int $operationId, int $matchId, int $userId): void
    {
        $now = date('Y-m-d H:i:s');
        $operation = $this->operationById($operationId);
        $this->pdo->prepare("UPDATE match_operations SET status = 'homologated', review_status = 'approved', review_reason = NULL, reviewed_by = ?, reviewed_at = ?, homologated_by = ?, homologated_at = ?, updated_at = ? WHERE id = ? AND status = 'awaiting_homologation'")->execute([$userId, $now, $userId, $now, $now, $operationId]);
        $this->pdo->prepare("UPDATE matches SET status = 'homologated', updated_at = ? WHERE id = ? AND status = 'finished'")->execute([$now, $matchId]);
        $this->historyInsert($operationId, 'homologated', $operation['status'], 'homologated', 'Resultado homologado sem retificacao avancada.', $userId);
    }

    public function operatorAssigned(int $matchId, int $userId): bool
    {
        $statement = $this->pdo->prepare("SELECT id FROM match_operator_assignments WHERE match_id = ? AND user_id = ? AND status = 'active' LIMIT 1");
        $statement->execute([$matchId, $userId]);
        return (bool) $statement->fetchColumn();
    }

    public function assignOperator(int $matchId, int $userId, int $createdBy): void
    {
        $this->pdo->prepare("INSERT INTO match_operator_assignments (match_id, user_id, assignment_type, status, created_by, created_at) VALUES (?, ?, 'operator', 'active', ?, ?) ON DUPLICATE KEY UPDATE status = 'active', ended_at = NULL")->execute([$matchId, $userId, $createdBy, date('Y-m-d H:i:s')]);
    }

    public function assignedMatches(int $userId): array
    {
        $statement = $this->pdo->prepare("SELECT m.*, c.name AS championship_name, p.name AS phase_name, g.name AS group_name, r.round_number, ht.name AS home_team_name, at.name AS away_team_name, v.name AS venue_name, mo.status AS operation_status FROM match_operator_assignments a INNER JOIN matches m ON m.id = a.match_id INNER JOIN championships c ON c.id = m.championship_id INNER JOIN competition_phases p ON p.id = m.phase_id INNER JOIN competition_groups g ON g.id = m.group_id INNER JOIN competition_rounds r ON r.id = m.round_id INNER JOIN teams ht ON ht.id = m.home_team_id INNER JOIN teams at ON at.id = m.away_team_id LEFT JOIN venues v ON v.id = m.venue_id LEFT JOIN match_operations mo ON mo.match_id = m.id WHERE a.user_id = ? AND a.status = 'active' AND m.status NOT IN ('cancelled', 'homologated') ORDER BY m.match_date IS NULL, m.match_date, m.match_time, m.id");
        $statement->execute([$userId]);
        return $statement->fetchAll();
    }

    public function allOpenMatches(): array
    {
        $statement = $this->pdo->query("SELECT m.*, c.name AS championship_name, p.name AS phase_name, g.name AS group_name, r.round_number, ht.name AS home_team_name, at.name AS away_team_name, v.name AS venue_name, mo.status AS operation_status FROM matches m INNER JOIN championships c ON c.id = m.championship_id INNER JOIN competition_phases p ON p.id = m.phase_id INNER JOIN competition_groups g ON g.id = m.group_id INNER JOIN competition_rounds r ON r.id = m.round_id INNER JOIN teams ht ON ht.id = m.home_team_id INNER JOIN teams at ON at.id = m.away_team_id LEFT JOIN venues v ON v.id = m.venue_id LEFT JOIN match_operations mo ON mo.match_id = m.id WHERE m.status NOT IN ('cancelled', 'homologated') ORDER BY m.match_date IS NULL, m.match_date, m.match_time, m.id");
        return $statement->fetchAll();
    }

    public function assignments(int $matchId): array
    {
        $statement = $this->pdo->prepare("SELECT a.*, u.name, u.email FROM match_operator_assignments a INNER JOIN users u ON u.id = a.user_id WHERE a.match_id = ? AND a.status = 'active' ORDER BY u.name");
        $statement->execute([$matchId]);
        return $statement->fetchAll();
    }

    private function operationById(int $id): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM match_operations WHERE id = ? LIMIT 1');
        $statement->execute([$id]);
        return $statement->fetch() ?: throw new \RuntimeException('Operacao da partida nao encontrada.');
    }

    private function historyInsert(int $operationId, string $action, string $from, string $to, string $details, int $userId): void
    {
        $this->pdo->prepare('INSERT INTO match_operation_history (operation_id, action, from_status, to_status, details, changed_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)')->execute([$operationId, $action, $from, $to, $details, $userId, date('Y-m-d H:i:s')]);
    }
}
