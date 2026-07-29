<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class MatchReportRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function match(int $matchId): ?array
    {
        $sql = 'SELECT m.*, c.name AS championship_name, c.short_name AS championship_short_name, s.name AS season_name, cat.name AS category_name, p.name AS phase_name, g.name AS group_name, r.round_number, ht.name AS home_team_name, ht.short_name AS home_team_short_name, at.name AS away_team_name, at.short_name AS away_team_short_name, v.name AS venue_name FROM matches m INNER JOIN championships c ON c.id = m.championship_id LEFT JOIN seasons s ON s.id = c.season_id LEFT JOIN categories cat ON cat.id = c.category_id INNER JOIN competition_phases p ON p.id = m.phase_id INNER JOIN competition_groups g ON g.id = m.group_id INNER JOIN competition_rounds r ON r.id = m.round_id INNER JOIN teams ht ON ht.id = m.home_team_id INNER JOIN teams at ON at.id = m.away_team_id LEFT JOIN venues v ON v.id = m.venue_id WHERE m.id = ? LIMIT 1';
        $statement = $this->pdo->prepare($sql);
        $statement->execute([$matchId]);
        return $statement->fetch() ?: null;
    }

    public function operation(int $matchId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM match_operations WHERE match_id = ? LIMIT 1');
        $statement->execute([$matchId]);
        return $statement->fetch() ?: null;
    }

    public function events(int $matchId): array
    {
        $sql = 'SELECT e.*, a.full_name AS athlete_full_name, a.sporting_name AS athlete_sporting_name, ra.full_name AS related_full_name, ra.sporting_name AS related_sporting_name, t.name AS team_name FROM match_operation_events e LEFT JOIN athletes a ON a.id = e.athlete_id LEFT JOIN athletes ra ON ra.id = e.related_athlete_id LEFT JOIN teams t ON t.id = e.team_id WHERE e.match_id = ? ORDER BY e.created_at, e.id';
        $statement = $this->pdo->prepare($sql);
        $statement->execute([$matchId]);
        return $statement->fetchAll();
    }

    public function substitutions(int $matchId): array
    {
        $sql = 'SELECT s.*, t.name AS team_name, ao.full_name AS athlete_out_name, ai.full_name AS athlete_in_name FROM match_substitutions s INNER JOIN teams t ON t.id = s.team_id INNER JOIN athletes ao ON ao.id = s.athlete_out_id INNER JOIN athletes ai ON ai.id = s.athlete_in_id WHERE s.match_id = ? AND s.valid = 1 ORDER BY s.created_at, s.id';
        $statement = $this->pdo->prepare($sql);
        $statement->execute([$matchId]);
        return $statement->fetchAll();
    }

    public function officials(int $matchId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM match_officials WHERE match_id = ? ORDER BY display_order, role, id');
        $statement->execute([$matchId]);
        return $statement->fetchAll();
    }

    public function decisions(int $matchId): array
    {
        $statement = $this->pdo->prepare('SELECT d.*, u.name AS decided_by_name FROM administrative_decisions d LEFT JOIN users u ON u.id = d.decided_by WHERE d.match_id = ? ORDER BY d.decided_at, d.id');
        $statement->execute([$matchId]);
        return $statement->fetchAll();
    }

    public function lineups(int $matchId): array
    {
        $statement = $this->pdo->prepare('SELECT ml.*, t.name AS team_name, t.short_name AS team_short_name, f.name AS formation_name FROM match_lineups ml INNER JOIN teams t ON t.id = ml.team_id INNER JOIN tactical_formations f ON f.id = ml.tactical_formation_id WHERE ml.match_id = ? ORDER BY t.name');
        $statement->execute([$matchId]);
        $lineups = $statement->fetchAll();
        $players = $this->pdo->prepare('SELECT p.*, a.full_name, a.sporting_name, pos.name AS position_name FROM match_lineup_players p INNER JOIN athletes a ON a.id = p.athlete_id LEFT JOIN positions pos ON pos.code = p.position_code WHERE p.lineup_id = ? ORDER BY CASE WHEN p.role = \'starter\' THEN 0 ELSE 1 END, p.display_order, a.full_name');
        $staff = $this->pdo->prepare('SELECT ls.*, ts.full_name, ts.display_name, sr.name AS role_name FROM match_lineup_staff ls INNER JOIN team_staff ts ON ts.id = ls.team_staff_id INNER JOIN staff_roles sr ON sr.id = ts.staff_role_id WHERE ls.lineup_id = ? AND ls.present = 1 ORDER BY sr.display_order, ts.full_name');
        foreach ($lineups as &$lineup) {
            $players->execute([(int) $lineup['id']]);
            $lineup['players'] = $players->fetchAll();
            $staff->execute([(int) $lineup['id']]);
            $lineup['staff'] = $staff->fetchAll();
        }
        unset($lineup);
        return $lineups;
    }

    public function payload(int $matchId): ?array
    {
        $match = $this->match($matchId);
        if (!$match) return null;
        $operation = $this->operation($matchId) ?: [];
        $events = $this->events($matchId);
        $lineups = $this->lineups($matchId);
        $stats = [];
        foreach ($events as $event) {
            if ((int) ($event['valid'] ?? 0) !== 1 || empty($event['athlete_id'])) continue;
            $athleteId = (int) $event['athlete_id'];
            $stats[$athleteId] ??= ['goals' => 0, 'assists' => 0, 'yellow' => 0, 'red' => 0];
            $type = (string) $event['event_type'];
            if (in_array($type, ['goal', 'own_goal'], true) && (string) $event['period'] !== 'penalties') $stats[$athleteId]['goals']++;
            if ($type === 'assist') $stats[$athleteId]['assists']++;
            if (in_array($type, ['yellow', 'second_yellow'], true)) $stats[$athleteId]['yellow']++;
            if (in_array($type, ['red', 'second_yellow'], true)) $stats[$athleteId]['red']++;
        }
        $homeGoals = 0; $awayGoals = 0; $homePenalties = 0; $awayPenalties = 0;
        foreach ($events as $event) {
            if ((int) ($event['valid'] ?? 0) !== 1) continue;
            $isHome = (int) ($event['team_id'] ?? 0) === (int) $match['home_team_id'];
            if ($event['event_type'] === 'goal' || $event['event_type'] === 'own_goal') {
                if ((string) $event['period'] === 'penalties') continue;
                $isHome ? $homeGoals++ : $awayGoals++;
            }
            if ($event['event_type'] === 'penalty_scored' && (string) $event['period'] === 'penalties') $isHome ? $homePenalties++ : $awayPenalties++;
        }
        $administrative = $operation['administrative_home_score'] !== null && $operation['administrative_away_score'] !== null;
        if ($administrative) { $homeGoals = (int) $operation['administrative_home_score']; $awayGoals = (int) $operation['administrative_away_score']; }
        foreach ($lineups as &$lineup) foreach ($lineup['players'] as &$player) {
            $player['report_stats'] = $stats[(int) $player['athlete_id']] ?? ['goals' => 0, 'assists' => 0, 'yellow' => 0, 'red' => 0];
        }
        unset($lineup, $player);
        $occurrences = array_values(array_filter($events, static fn (array $event): bool => $event['event_type'] === 'occurrence' && (int) $event['valid'] === 1));
        return ['match' => $match, 'operation' => $operation, 'events' => $events, 'substitutions' => $this->substitutions($matchId), 'officials' => $this->officials($matchId), 'decisions' => $this->decisions($matchId), 'lineups' => $lineups, 'occurrences' => $occurrences, 'score' => ['home' => $homeGoals, 'away' => $awayGoals, 'home_penalties' => $homePenalties, 'away_penalties' => $awayPenalties, 'administrative' => $administrative], 'generated_at' => date('Y-m-d H:i:s')];
    }

    public function reportForMatch(int $matchId): ?array
    {
        $statement = $this->pdo->prepare('SELECT r.*, v.id AS version_id, v.version_number, v.verification_code, v.content_hash, v.storage_path, v.original_name, v.mime_type, v.file_size, v.html_snapshot, v.created_by AS version_created_by, v.created_at AS version_created_at FROM match_reports r LEFT JOIN match_report_versions v ON v.id = r.current_version_id WHERE r.match_id = ? LIMIT 1');
        $statement->execute([$matchId]);
        return $statement->fetch() ?: null;
    }

    public function version(int $versionId): ?array
    {
        $statement = $this->pdo->prepare('SELECT v.*, r.match_id, r.championship_id, m.round_id, m.status AS match_status FROM match_report_versions v INNER JOIN match_reports r ON r.id = v.match_report_id INNER JOIN matches m ON m.id = r.match_id WHERE v.id = ? LIMIT 1');
        $statement->execute([$versionId]);
        return $statement->fetch() ?: null;
    }

    public function versions(int $matchId): array
    {
        $statement = $this->pdo->prepare('SELECT v.* FROM match_report_versions v INNER JOIN match_reports r ON r.id = v.match_report_id WHERE r.match_id = ? ORDER BY v.version_number DESC');
        $statement->execute([$matchId]);
        return $statement->fetchAll();
    }

    public function ensureReport(int $matchId, int $championshipId, int $userId): int
    {
        $now = date('Y-m-d H:i:s');
        $this->pdo->prepare('INSERT INTO match_reports (match_id, championship_id, status, homologated_by, homologated_at, created_at, updated_at) VALUES (?, ?, \'available\', ?, ?, ?, ?) ON DUPLICATE KEY UPDATE homologated_by = VALUES(homologated_by), homologated_at = VALUES(homologated_at), updated_at = VALUES(updated_at)')->execute([$matchId, $championshipId, $userId, $now, $now, $now]);
        $statement = $this->pdo->prepare('SELECT id FROM match_reports WHERE match_id = ? LIMIT 1');
        $statement->execute([$matchId]);
        return (int) $statement->fetchColumn();
    }

    public function insertVersion(int $reportId, array $data): int
    {
        $statement = $this->pdo->prepare('INSERT INTO match_report_versions (match_report_id, version_number, verification_code, content_hash, storage_path, original_name, mime_type, file_size, html_snapshot, created_by, created_at, supersedes_version_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $statement->execute([$reportId, $data['version_number'], $data['verification_code'], $data['content_hash'], $data['storage_path'], $data['original_name'], $data['mime_type'], $data['file_size'], $data['html_snapshot'], $data['created_by'], date('Y-m-d H:i:s'), $data['supersedes_version_id']]);
        return (int) $this->pdo->lastInsertId();
    }

    public function setCurrentVersion(int $reportId, int $versionId): void
    {
        $this->pdo->prepare('UPDATE match_reports SET current_version_id = ?, updated_at = ? WHERE id = ?')->execute([$versionId, date('Y-m-d H:i:s'), $reportId]);
    }

    public function currentByRound(int $roundId): array
    {
        return $this->currentList('m.round_id = ?', [$roundId]);
    }

    public function currentByChampionship(int $championshipId): array
    {
        return $this->currentList('m.championship_id = ?', [$championshipId]);
    }

    public function round(int $roundId): ?array
    {
        $statement = $this->pdo->prepare('SELECT r.*, p.championship_id FROM competition_rounds r INNER JOIN competition_phases p ON p.id = r.phase_id WHERE r.id = ? LIMIT 1');
        $statement->execute([$roundId]);
        return $statement->fetch() ?: null;
    }

    private function currentList(string $where, array $params): array
    {
        $sql = 'SELECT v.*, r.match_id, r.championship_id, m.round_id, m.match_date, m.match_time, c.name AS championship_name, ht.name AS home_team_name, at.name AS away_team_name FROM match_report_versions v INNER JOIN match_reports r ON r.current_version_id = v.id INNER JOIN matches m ON m.id = r.match_id INNER JOIN championships c ON c.id = m.championship_id INNER JOIN teams ht ON ht.id = m.home_team_id INNER JOIN teams at ON at.id = m.away_team_id WHERE ' . $where . ' ORDER BY m.match_date, m.match_time, m.id';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function begin(): void { $this->pdo->beginTransaction(); }
    public function commit(): void { $this->pdo->commit(); }
    public function rollBack(): void { if ($this->pdo->inTransaction()) $this->pdo->rollBack(); }
}
