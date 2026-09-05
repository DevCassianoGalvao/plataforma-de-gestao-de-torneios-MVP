<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class TransferRepository
{
    public const TYPES = ['contratacao', 'transferencia', 'emprestimo', 'retorno', 'renovacao', 'saida'];
    public const STATUSES = ['draft', 'pending', 'approved', 'published', 'cancelled'];

    public function __construct(private readonly PDO $pdo) {}

    public function championshipsForUser(int $userId, array $roles, bool $administrator): array
    {
        if ($administrator) return $this->pdo->query("SELECT id, name, slug FROM championships WHERE deleted_at IS NULL ORDER BY name")->fetchAll();
        if (in_array('organizer', $roles, true)) {
            $s = $this->pdo->prepare("SELECT c.id, c.name, c.slug FROM championships c INNER JOIN championship_user_assignments cua ON cua.championship_id = c.id AND cua.user_id = ? AND cua.assignment_type = 'organizer' WHERE c.deleted_at IS NULL ORDER BY c.name");
            $s->execute([$userId]);
            return $s->fetchAll();
        }
        return [];
    }

    public function isOwnTeam(int $teamId, int $userId): bool
    {
        $s = $this->pdo->prepare("SELECT EXISTS (SELECT 1 FROM team_user_assignments tua WHERE tua.team_id = ? AND tua.user_id = ? AND tua.assignment_type IN ('manager', 'head_coach') AND tua.status = 'active')");
        $s->execute([$teamId, $userId]);
        return (bool) $s->fetchColumn();
    }

    public function ownTeams(int $userId, ?int $championshipId = null): array
    {
        $where = ['t.deleted_at IS NULL', "EXISTS (SELECT 1 FROM team_user_assignments tua WHERE tua.team_id = t.id AND tua.user_id = ? AND tua.assignment_type IN ('manager', 'head_coach') AND tua.status = 'active')"];
        $params = [$userId];
        if ($championshipId !== null) { $where[] = 't.championship_id = ?'; $params[] = $championshipId; }
        $s = $this->pdo->prepare('SELECT t.id, t.name, t.championship_id FROM teams t WHERE ' . implode(' AND ', $where) . ' ORDER BY t.name');
        $s->execute($params);
        return $s->fetchAll();
    }

    public function championshipsForOwnTeams(int $userId): array
    {
        $s = $this->pdo->prepare("SELECT DISTINCT c.id, c.name, c.slug FROM championships c INNER JOIN teams t ON t.championship_id = c.id WHERE c.deleted_at IS NULL AND EXISTS (SELECT 1 FROM team_user_assignments tua WHERE tua.team_id = t.id AND tua.user_id = ? AND tua.assignment_type IN ('manager', 'head_coach') AND tua.status = 'active') ORDER BY c.name");
        $s->execute([$userId]);
        return $s->fetchAll();
    }

    public function find(int $id): ?array
    {
        $s = $this->pdo->prepare("SELECT m.*, c.name AS championship_name, c.slug AS championship_slug, c.visibility AS championship_visibility, a.full_name AS athlete_name, a.sporting_name, a.photo_path, pt.name AS previous_team_name, nt.name AS new_team_name, u.name AS author_name FROM transfer_movements m INNER JOIN championships c ON c.id = m.championship_id INNER JOIN athletes a ON a.id = m.athlete_id LEFT JOIN teams pt ON pt.id = m.previous_team_id LEFT JOIN teams nt ON nt.id = m.new_team_id INNER JOIN users u ON u.id = m.author_id WHERE m.id = ? AND m.deleted_at IS NULL LIMIT 1");
        $s->execute([$id]); return $s->fetch() ?: null;
    }

    public function listAdmin(?array $championshipIds, array $filters = [], int $limit = 20, int $offset = 0, ?array $ownTeamIds = null): array
    {
        [$where, $params] = $this->where($championshipIds, $filters, false, $ownTeamIds);
        $sql = "SELECT m.*, c.name AS championship_name, a.full_name AS athlete_name, a.sporting_name, pt.name AS previous_team_name, nt.name AS new_team_name, u.name AS author_name FROM transfer_movements m INNER JOIN championships c ON c.id = m.championship_id INNER JOIN athletes a ON a.id = m.athlete_id LEFT JOIN teams pt ON pt.id = m.previous_team_id LEFT JOIN teams nt ON nt.id = m.new_team_id INNER JOIN users u ON u.id = m.author_id WHERE " . implode(' AND ', $where) . " ORDER BY m.movement_date DESC, m.id DESC LIMIT " . max(1, $limit) . " OFFSET " . max(0, $offset);
        $s = $this->pdo->prepare($sql); $s->execute($params); return $s->fetchAll();
    }

    public function countAdmin(?array $championshipIds, array $filters = [], ?array $ownTeamIds = null): int
    {
        [$where, $params] = $this->where($championshipIds, $filters, false, $ownTeamIds); $s = $this->pdo->prepare('SELECT COUNT(*) FROM transfer_movements m INNER JOIN athletes a ON a.id = m.athlete_id LEFT JOIN teams pt ON pt.id = m.previous_team_id LEFT JOIN teams nt ON nt.id = m.new_team_id WHERE ' . implode(' AND ', $where)); $s->execute($params); return (int) $s->fetchColumn();
    }

    public function publicChampionship(string $slug): ?array
    {
        $s = $this->pdo->prepare("SELECT c.*, s.name AS season_name, cat.name AS category_name FROM championships c INNER JOIN seasons s ON s.id = c.season_id INNER JOIN categories cat ON cat.id = c.category_id WHERE c.slug = ? AND c.visibility = 'public' AND c.status <> 'draft' AND c.deleted_at IS NULL LIMIT 1"); $s->execute([$slug]); return $s->fetch() ?: null;
    }

    public function listPublic(int $championshipId, array $filters = [], int $limit = 12, int $offset = 0): array
    {
        [$where, $params] = $this->where([$championshipId], $filters, true); $where[] = "c.visibility = 'public' AND c.status <> 'draft'";
        $where[] = "m.status = 'published' AND m.published_at IS NOT NULL AND m.published_at <= ?"; $params[] = date('Y-m-d H:i:s');
        $sql = "SELECT m.id, m.athlete_id, m.type, m.movement_date, m.public_observation, a.full_name AS athlete_name, a.sporting_name, a.photo_path, pt.name AS previous_team_name, pt.slug AS previous_team_slug, pt.shield_path AS previous_team_shield_path, nt.name AS new_team_name, nt.slug AS new_team_slug, nt.shield_path AS new_team_shield_path FROM transfer_movements m INNER JOIN athletes a ON a.id = m.athlete_id LEFT JOIN teams pt ON pt.id = m.previous_team_id LEFT JOIN teams nt ON nt.id = m.new_team_id INNER JOIN championships c ON c.id = m.championship_id WHERE " . implode(' AND ', $where) . " ORDER BY m.movement_date DESC, m.id DESC LIMIT " . max(1, $limit) . " OFFSET " . max(0, $offset);
        $s = $this->pdo->prepare($sql); $s->execute($params); return $s->fetchAll();
    }

    public function countPublic(int $championshipId, array $filters = []): int
    {
        [$where, $params] = $this->where([$championshipId], $filters, true); $where[] = "c.visibility = 'public' AND c.status <> 'draft'"; $where[] = "m.status = 'published' AND m.published_at IS NOT NULL AND m.published_at <= ?"; $params[] = date('Y-m-d H:i:s'); $s = $this->pdo->prepare('SELECT COUNT(*) FROM transfer_movements m INNER JOIN championships c ON c.id = m.championship_id INNER JOIN athletes a ON a.id = m.athlete_id LEFT JOIN teams pt ON pt.id = m.previous_team_id LEFT JOIN teams nt ON nt.id = m.new_team_id WHERE ' . implode(' AND ', $where)); $s->execute($params); return (int) $s->fetchColumn();
    }

    public function publicFind(int $id, int $championshipId): ?array
    {
        $rows = $this->listPublic($championshipId, ['id' => $id], 1); return $rows[0] ?? null;
    }

    public function athletesForChampionships(?array $championshipIds): array
    {
        $where = ['a.deleted_at IS NULL']; $params = [];
        $this->scopeIds($championshipIds, 'c.id', $where, $params);
        $s = $this->pdo->prepare('SELECT a.id, a.full_name, a.sporting_name, a.team_id, t.name AS team_name, c.id AS championship_id FROM athletes a INNER JOIN teams t ON t.id = a.team_id INNER JOIN championships c ON c.id = t.championship_id WHERE ' . implode(' AND ', $where) . ' ORDER BY a.full_name'); $s->execute($params); return $s->fetchAll();
    }

    public function teamsForChampionships(?array $championshipIds): array
    {
        $where = ['t.deleted_at IS NULL']; $params = []; $this->scopeIds($championshipIds, 't.championship_id', $where, $params); $s = $this->pdo->prepare('SELECT t.id, t.name, t.championship_id FROM teams t WHERE ' . implode(' AND ', $where) . ' ORDER BY t.name'); $s->execute($params); return $s->fetchAll();
    }

    public function athleteInChampionship(int $athleteId, int $championshipId): ?array
    {
        $s = $this->pdo->prepare('SELECT a.id, a.team_id, a.full_name, c.id AS championship_id FROM athletes a INNER JOIN teams t ON t.id = a.team_id INNER JOIN championships c ON c.id = t.championship_id WHERE a.id = ? AND c.id = ? AND a.deleted_at IS NULL AND t.deleted_at IS NULL LIMIT 1'); $s->execute([$athleteId, $championshipId]); return $s->fetch() ?: null;
    }

    public function teamInChampionship(int $teamId, int $championshipId): bool { $s = $this->pdo->prepare('SELECT id FROM teams WHERE id = ? AND championship_id = ? AND deleted_at IS NULL'); $s->execute([$teamId, $championshipId]); return (bool) $s->fetchColumn(); }

    public function transferWindow(int $championshipId): ?array
    {
        $s = $this->pdo->prepare("SELECT ts.* FROM regulation_transfer_settings ts INNER JOIN regulations r ON r.id = ts.regulation_id WHERE r.championship_id = ? AND r.status = 'published' ORDER BY r.version_number DESC LIMIT 1"); $s->execute([$championshipId]); return $s->fetch() ?: null;
    }

    public function movementCount(int $championshipId, ?string $from, ?string $to, ?int $ignoreId = null): int
    {
        $where = ['championship_id = ?', "status <> 'cancelled'", 'deleted_at IS NULL']; $params = [$championshipId]; if ($from) { $where[] = 'movement_date >= ?'; $params[] = $from; } if ($to) { $where[] = 'movement_date <= ?'; $params[] = $to; } if ($ignoreId) { $where[] = 'id <> ?'; $params[] = $ignoreId; } $s = $this->pdo->prepare('SELECT COUNT(*) FROM transfer_movements WHERE ' . implode(' AND ', $where)); $s->execute($params); return (int) $s->fetchColumn();
    }

    public function create(array $data): int
    {
        $s = $this->pdo->prepare('INSERT INTO transfer_movements (championship_id, athlete_id, previous_team_id, new_team_id, type, movement_date, public_observation, internal_notes, status, published_at, author_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'); $s->execute([$data['championship_id'], $data['athlete_id'], $data['previous_team_id'], $data['new_team_id'], $data['type'], $data['movement_date'], $data['public_observation'], $data['internal_notes'], $data['status'], $data['published_at'], $data['author_id'], $data['created_at'], $data['updated_at']]); return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void { $s = $this->pdo->prepare('UPDATE transfer_movements SET previous_team_id = ?, new_team_id = ?, type = ?, movement_date = ?, public_observation = ?, internal_notes = ?, status = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL'); $s->execute([$data['previous_team_id'], $data['new_team_id'], $data['type'], $data['movement_date'], $data['public_observation'], $data['internal_notes'], $data['status'], $data['updated_at'], $id]); }
    public function setStatus(int $id, string $status, ?string $publishedAt = null): void { $s = $this->pdo->prepare('UPDATE transfer_movements SET status = ?, published_at = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL'); $s->execute([$status, $publishedAt, date('Y-m-d H:i:s'), $id]); }
    public function softDelete(int $id): void { $now = date('Y-m-d H:i:s'); $this->pdo->prepare("UPDATE transfer_movements SET status = 'cancelled', deleted_at = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL")->execute([$now, $now, $id]); }
    public function addHistory(int $id, ?string $from, string $to, string $action, ?string $reason, int $userId): void { $this->pdo->prepare('INSERT INTO transfer_movement_history (transfer_movement_id, from_status, to_status, action, reason, user_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)')->execute([$id, $from, $to, $action, $reason, $userId, date('Y-m-d H:i:s')]); }
    public function history(int $id): array { $s = $this->pdo->prepare('SELECT h.*, u.name AS user_name FROM transfer_movement_history h INNER JOIN users u ON u.id = h.user_id WHERE h.transfer_movement_id = ? ORDER BY h.created_at, h.id'); $s->execute([$id]); return $s->fetchAll(); }

    public function applyOfficial(int $id, int $userId): bool
    {
        $record = $this->find($id);
        if (!$record || !in_array($record['status'], ['approved', 'published'], true) || $record['official_applied_at'] !== null) return false;
        $this->pdo->beginTransaction();
        try {
            $newTeam = $record['type'] === 'saida' ? null : (int) $record['new_team_id'];
            $this->pdo->prepare('UPDATE athletes SET team_id = ?, status = CASE WHEN ? IS NULL THEN \'transferred\' ELSE status END, updated_at = ? WHERE id = ?')->execute([$newTeam, $newTeam, date('Y-m-d H:i:s'), $record['athlete_id']]);
            $this->pdo->prepare('UPDATE transfer_movements SET official_applied_at = ?, official_applied_by = ?, updated_at = ? WHERE id = ? AND official_applied_at IS NULL')->execute([date('Y-m-d H:i:s'), $userId, date('Y-m-d H:i:s'), $id]);
            $this->pdo->commit(); return true;
        } catch (\Throwable $e) { $this->pdo->rollBack(); throw $e; }
    }

    private function where(?array $championshipIds, array $filters, bool $public, ?array $ownTeamIds = null): array
    {
        $where = ['m.deleted_at IS NULL']; $params = []; $this->scopeIds($championshipIds, 'm.championship_id', $where, $params);
        if ($ownTeamIds !== null) {
            if (!$ownTeamIds) { $where[] = '1 = 0'; }
            else { $marks = implode(',', array_fill(0, count($ownTeamIds), '?')); $where[] = "(m.previous_team_id IN ($marks) OR m.new_team_id IN ($marks))"; array_push($params, ...$ownTeamIds, ...$ownTeamIds); }
        }
        if (($filters['q'] ?? '') !== '') { $term = '%' . trim((string) $filters['q']) . '%'; $where[] = '(a.full_name LIKE ? OR a.sporting_name LIKE ? OR pt.name LIKE ? OR nt.name LIKE ? OR m.public_observation LIKE ?)'; array_push($params, $term, $term, $term, $term, $term); }
        if (($filters['type'] ?? '') !== '') { $where[] = 'm.type = ?'; $params[] = $filters['type']; }
        if (($filters['status'] ?? '') !== '' && !$public) { $where[] = 'm.status = ?'; $params[] = $filters['status']; }
        if (($filters['championship_id'] ?? '') !== '' && !$public) { $where[] = 'm.championship_id = ?'; $params[] = (int) $filters['championship_id']; }
        if (($filters['id'] ?? '') !== '') { $where[] = 'm.id = ?'; $params[] = (int) $filters['id']; }
        return [$where, $params];
    }

    private function scopeIds(?array $ids, string $column, array &$where, array &$params): void
    {
        if ($ids === null) return; if (!$ids) { $where[] = '1 = 0'; return; } $where[] = $column . ' IN (' . implode(',', array_fill(0, count($ids), '?')) . ')'; array_push($params, ...array_map('intval', $ids));
    }
}
