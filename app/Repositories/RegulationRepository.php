<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class RegulationRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function list(int $championshipId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM regulations WHERE championship_id = ? ORDER BY version_number DESC');
        $statement->execute([$championshipId]);
        return $statement->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM regulations WHERE id = ? LIMIT 1');
        $statement->execute([$id]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function findWithSettings(int $id): ?array
    {
        $regulation = $this->find($id);
        if (!$regulation) {
            return null;
        }
        foreach (['format', 'points', 'discipline', 'match'] as $type) {
            $table = 'regulation_' . $type . '_settings';
            $statement = $this->pdo->prepare('SELECT * FROM ' . $table . ' WHERE regulation_id = ? LIMIT 1');
            $statement->execute([$id]);
            $regulation[$type . '_settings'] = $statement->fetch() ?: [];
        }
        $statement = $this->pdo->prepare('SELECT * FROM regulation_roster_settings WHERE regulation_id = ? LIMIT 1');
        $statement->execute([$id]);
        $regulation['roster_settings'] = $statement->fetch() ?: [];
        $statement = $this->pdo->prepare('SELECT rrd.*, adt.`key`, adt.name FROM regulation_required_documents rrd INNER JOIN athlete_document_types adt ON adt.id = rrd.document_type_id WHERE rrd.regulation_id = ? ORDER BY rrd.display_order, adt.name');
        $statement->execute([$id]);
        $regulation['required_documents'] = $statement->fetchAll();
        $statement = $this->pdo->prepare('SELECT * FROM regulation_tiebreakers WHERE regulation_id = ? ORDER BY priority');
        $statement->execute([$id]);
        $regulation['tiebreakers'] = $statement->fetchAll();
        return $regulation;
    }

    public function latest(int $championshipId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM regulations WHERE championship_id = ? ORDER BY version_number DESC LIMIT 1');
        $statement->execute([$championshipId]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function draft(int $championshipId): ?array
    {
        $statement = $this->pdo->prepare("SELECT * FROM regulations WHERE championship_id = ? AND status = 'draft' ORDER BY version_number DESC LIMIT 1");
        $statement->execute([$championshipId]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function published(int $championshipId): ?array
    {
        $statement = $this->pdo->prepare("SELECT * FROM regulations WHERE championship_id = ? AND status = 'published' LIMIT 1");
        $statement->execute([$championshipId]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function create(int $championshipId, int $version, string $name, string $status, int $createdBy): int
    {
        $statement = $this->pdo->prepare('INSERT INTO regulations (championship_id, version_number, name, status, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $now = date('Y-m-d H:i:s');
        $statement->execute([$championshipId, $version, $name, $status, $createdBy, $now, $now]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updateMain(int $id, string $name, ?string $effectiveFrom): void
    {
        $statement = $this->pdo->prepare('UPDATE regulations SET name = ?, effective_from = ?, updated_at = ? WHERE id = ? AND status = \'draft\'');
        $statement->execute([$name, $effectiveFrom, date('Y-m-d H:i:s'), $id]);
    }

    public function publishDraft(int $championshipId, int $id): void
    {
        $this->pdo->beginTransaction();
        try {
            $demote = $this->pdo->prepare("UPDATE regulations SET status = 'superseded', updated_at = ? WHERE championship_id = ? AND status = 'published'");
            $demote->execute([date('Y-m-d H:i:s'), $championshipId]);
            $publish = $this->pdo->prepare("UPDATE regulations SET status = 'published', published_at = ?, updated_at = ? WHERE id = ? AND championship_id = ? AND status = 'draft'");
            $now = date('Y-m-d H:i:s');
            $publish->execute([$now, $now, $id, $championshipId]);
            if ($publish->rowCount() !== 1) {
                throw new \RuntimeException('Regulamento nao esta disponivel para publicacao.');
            }
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function saveSettings(int $id, array $format, array $points, array $discipline, array $match, array $tiebreakers): void
    {
        $now = date('Y-m-d H:i:s');
        $upserts = [
            ['regulation_format_settings', ['group_count', 'teams_per_group', 'qualified_per_group', 'group_rounds', 'home_and_away', 'knockout_starts_at', 'third_place_match', 'final_format'], $format],
            ['regulation_points_settings', ['points_win', 'points_draw', 'points_loss', 'wo_winner_goals', 'wo_loser_goals'], $points],
            ['regulation_discipline_settings', ['yellow_cards_for_suspension', 'yellow_suspension_matches', 'red_card_automatic_suspension', 'red_card_suspension_matches', 'reset_cards_enabled', 'reset_cards_stage'], $discipline],
            ['regulation_match_settings', ['regular_time_minutes', 'halftime_minutes', 'substitutions_allowed', 'substitution_windows', 'extra_time_enabled', 'extra_time_minutes', 'penalty_shootout_enabled', 'direct_penalties'], $match],
        ];
        foreach ($upserts as [$table, $columns, $values]) {
            $columnsSql = implode(', ', $columns);
            $placeholders = implode(', ', array_fill(0, count($columns), '?'));
            $updates = implode(', ', array_map(static fn (string $column): string => $column . ' = VALUES(' . $column . ')', $columns));
            $statement = $this->pdo->prepare('INSERT INTO ' . $table . ' (regulation_id, ' . $columnsSql . ', created_at, updated_at) VALUES (?, ' . $placeholders . ', ?, ?) ON DUPLICATE KEY UPDATE ' . $updates . ', updated_at = VALUES(updated_at)');
            $boundValues = array_map(static fn (string $column): mixed => $values[$column] ?? null, $columns);
            $statement->execute(array_merge([$id], $boundValues, [$now, $now]));
        }
        $delete = $this->pdo->prepare('DELETE FROM regulation_tiebreakers WHERE regulation_id = ?');
        $delete->execute([$id]);
        $insert = $this->pdo->prepare('INSERT INTO regulation_tiebreakers (regulation_id, criterion, priority, enabled, created_at) VALUES (?, ?, ?, ?, ?)');
        foreach (array_values($tiebreakers) as $index => $item) {
            $priority = !empty($item['enabled']) ? (int) $item['priority'] : 1000 + $index;
            $insert->execute([$id, $item['criterion'], $priority, $item['enabled'], $now]);
        }
    }

    public function saveRosterSettings(int $id, array $roster, array $requiredDocumentTypeIds): void
    {
        $now = date('Y-m-d H:i:s');
        $settings = $this->pdo->prepare('INSERT INTO regulation_roster_settings (regulation_id, minimum_roster_size, maximum_roster_size, minimum_goalkeepers, allow_multiple_team_registration, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE minimum_roster_size = VALUES(minimum_roster_size), maximum_roster_size = VALUES(maximum_roster_size), minimum_goalkeepers = VALUES(minimum_goalkeepers), allow_multiple_team_registration = VALUES(allow_multiple_team_registration), updated_at = VALUES(updated_at)');
        $settings->execute([$id, (int) ($roster['minimum_roster_size'] ?? 1), (int) ($roster['maximum_roster_size'] ?? 25), (int) ($roster['minimum_goalkeepers'] ?? 1), !empty($roster['allow_multiple_team_registration']) ? 1 : 0, $now, $now]);
        $delete = $this->pdo->prepare('DELETE FROM regulation_required_documents WHERE regulation_id = ?');
        $delete->execute([$id]);
        $insert = $this->pdo->prepare('INSERT INTO regulation_required_documents (regulation_id, document_type_id, required_for_minor, display_order, created_at) VALUES (?, ?, 0, ?, ?)');
        foreach (array_values(array_unique(array_filter(array_map('intval', $requiredDocumentTypeIds)))) as $order => $documentTypeId) {
            $insert->execute([$id, $documentTypeId, $order + 1, $now]);
        }
    }

    public function documents(int $regulationId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM regulation_documents WHERE regulation_id = ? ORDER BY created_at DESC');
        $statement->execute([$regulationId]);
        return $statement->fetchAll();
    }

    public function document(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT rd.*, r.championship_id FROM regulation_documents rd INNER JOIN regulations r ON r.id = rd.regulation_id WHERE rd.id = ? LIMIT 1');
        $statement->execute([$id]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function addDocument(int $regulationId, array $data): int
    {
        $statement = $this->pdo->prepare('INSERT INTO regulation_documents (regulation_id, storage_path, original_name, version_label, visibility, created_at) VALUES (?, ?, ?, ?, ?, ?)');
        $statement->execute([$regulationId, $data['storage_path'], $data['original_name'], $data['version_label'], $data['visibility'], date('Y-m-d H:i:s')]);
        return (int) $this->pdo->lastInsertId();
    }
}
