<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class RegistrationRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function listForUser(int $userId, string $scope, array $filters = []): array
    {
        [$scopeSql, $scopeParams] = $this->scopeSql($userId, $scope);
        $conditions = ['ar.' . 'id IS NOT NULL', $scopeSql];
        $params = $scopeParams;
        foreach (['status' => 'ar.status', 'championship_id' => 'ar.championship_id', 'team_id' => 'ar.team_id', 'athlete_id' => 'ar.athlete_id'] as $key => $column) {
            if (($filters[$key] ?? '') !== '') {
                $conditions[] = $column . ' = ?';
                $params[] = $filters[$key];
            }
        }
        $sql = $this->baseSelect() . ' WHERE ' . implode(' AND ', $conditions) . ' ORDER BY FIELD(ar.status, \'draft\', \'submitted\', \'under_review\', \'pending_correction\', \'approved\', \'rejected\', \'suspended\', \'cancelled\'), ar.updated_at DESC, ar.id DESC';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function findForUser(int $id, int $userId, string $scope): ?array
    {
        [$scopeSql, $scopeParams] = $this->scopeSql($userId, $scope);
        $statement = $this->pdo->prepare($this->baseSelect() . ' WHERE ar.id = ? AND ' . $scopeSql . ' LIMIT 1');
        $statement->execute(array_merge([$id], $scopeParams));
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function findByPair(int $championshipId, int $teamId, int $athleteId): ?array
    {
        $statement = $this->pdo->prepare($this->baseSelect() . ' WHERE ar.championship_id = ? AND ar.team_id = ? AND ar.athlete_id = ? LIMIT 1');
        $statement->execute([$championshipId, $teamId, $athleteId]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function create(array $data, int $userId): int
    {
        $now = date('Y-m-d H:i:s');
        $statement = $this->pdo->prepare('INSERT INTO athlete_registrations (championship_id, team_id, athlete_id, category_id, requested_number, status, observations, created_by, updated_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, \'draft\', ?, ?, ?, ?, ?)');
        $statement->execute([$data['championship_id'], $data['team_id'], $data['athlete_id'], $data['category_id'], $data['requested_number'], $data['observations'], $userId, $userId, $now, $now]);
        $id = (int) $this->pdo->lastInsertId();
        $history = $this->pdo->prepare('INSERT INTO athlete_registration_history (registration_id, from_status, to_status, action, notes, user_id, created_at) VALUES (?, NULL, \'draft\', \'created\', NULL, ?, ?)');
        $history->execute([$id, $userId, $now]);
        return $id;
    }

    public function updateDraft(int $id, ?int $number, string $observations, int $userId): void
    {
        $now = date('Y-m-d H:i:s');
        $statement = $this->pdo->prepare('UPDATE athlete_registrations SET requested_number = ?, observations = ?, pending_issues = NULL, updated_by = ?, updated_at = ? WHERE id = ? AND status IN (\'draft\', \'pending_correction\')');
        $statement->execute([$number, $observations, $userId, $now, $id]);
        if ($statement->rowCount() === 1) {
            $status = $this->pdo->prepare('SELECT status FROM athlete_registrations WHERE id = ? LIMIT 1');
            $status->execute([$id]);
            $current = (string) $status->fetchColumn();
            $history = $this->pdo->prepare('INSERT INTO athlete_registration_history (registration_id, from_status, to_status, action, notes, user_id, created_at) VALUES (?, ?, ?, \'corrected\', NULL, ?, ?)');
            $history->execute([$id, $current, $current, $userId, $now]);
        }
    }

    public function setIssues(int $id, array $issues, int $userId): void
    {
        $statement = $this->pdo->prepare('UPDATE athlete_registrations SET pending_issues = ?, updated_by = ?, updated_at = ? WHERE id = ?');
        $statement->execute([$issues === [] ? null : implode("\n", $issues), $userId, date('Y-m-d H:i:s'), $id]);
    }

    public function transition(int $id, string $from, string $to, int $userId, string $action, ?string $notes = null): void
    {
        $now = date('Y-m-d H:i:s');
        $submittedAt = $to === 'submitted' ? $now : null;
        $reviewedAt = in_array($to, ['under_review', 'pending_correction', 'approved', 'rejected'], true) ? $now : null;
        $decidedAt = in_array($to, ['approved', 'rejected', 'cancelled'], true) ? $now : null;
        $statement = $this->pdo->prepare('UPDATE athlete_registrations SET status = ?, submitted_at = COALESCE(?, submitted_at), pending_issues = ?, rejection_reason = ?, reviewed_by = COALESCE(?, reviewed_by), reviewed_at = COALESCE(?, reviewed_at), decided_at = COALESCE(?, decided_at), updated_by = ?, updated_at = ? WHERE id = ? AND status = ?');
        $rejectionReason = $to === 'rejected' ? $notes : null;
        $pending = $to === 'pending_correction' ? $notes : null;
        $statement->execute([$to, $submittedAt, $pending, $rejectionReason, $reviewedAt ? $userId : null, $reviewedAt, $decidedAt, $userId, $now, $id, $from]);
        if ($statement->rowCount() !== 1) throw new \RuntimeException('A inscricao mudou antes da transicao.');
        $history = $this->pdo->prepare('INSERT INTO athlete_registration_history (registration_id, from_status, to_status, action, notes, user_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $history->execute([$id, $from, $to, $action, $notes, $userId, $now]);
    }

    public function history(int $registrationId): array
    {
        $statement = $this->pdo->prepare('SELECT h.*, u.name AS user_name FROM athlete_registration_history h INNER JOIN users u ON u.id = h.user_id WHERE h.registration_id = ? ORDER BY h.created_at, h.id');
        $statement->execute([$registrationId]);
        return $statement->fetchAll();
    }

    public function officialRoster(int $championshipId, ?int $teamId = null): array
    {
        $conditions = ['ar.championship_id = ?', "ar.status = 'approved'"];
        $params = [$championshipId];
        if ($teamId !== null) {
            $conditions[] = 'ar.team_id = ?';
            $params[] = $teamId;
        }
        $statement = $this->pdo->prepare($this->baseSelect() . ' WHERE ' . implode(' AND ', $conditions) . ' ORDER BY t.name, a.full_name');
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function approvedCount(int $championshipId, int $teamId, ?int $exceptId = null): int
    {
        $sql = "SELECT COUNT(*) FROM athlete_registrations WHERE championship_id = ? AND team_id = ? AND status = 'approved'";
        $params = [$championshipId, $teamId];
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return (int) $statement->fetchColumn();
    }

    public function approvedGoalkeeperCount(int $championshipId, int $teamId, ?int $exceptId = null): int
    {
        $sql = "SELECT COUNT(*) FROM athlete_registrations ar INNER JOIN athletes a ON a.id = ar.athlete_id INNER JOIN positions p ON p.id = a.primary_position_id WHERE ar.championship_id = ? AND ar.team_id = ? AND ar.status = 'approved' AND p.code = 'goalkeeper'";
        $params = [$championshipId, $teamId];
        if ($exceptId !== null) {
            $sql .= ' AND ar.id <> ?';
            $params[] = $exceptId;
        }
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return (int) $statement->fetchColumn();
    }

    public function numberTaken(int $championshipId, int $teamId, int $number, ?int $exceptId = null): bool
    {
        $sql = "SELECT id FROM athlete_registrations WHERE championship_id = ? AND team_id = ? AND requested_number = ? AND status IN ('submitted', 'under_review', 'pending_correction', 'approved', 'suspended')";
        $params = [$championshipId, $teamId, $number];
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }
        $sql .= ' LIMIT 1';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return (bool) $statement->fetchColumn();
    }

    public function hasOtherTeamRegistration(int $championshipId, int $athleteId, int $teamId): bool
    {
        $statement = $this->pdo->prepare("SELECT id FROM athlete_registrations WHERE championship_id = ? AND athlete_id = ? AND team_id <> ? AND status IN ('submitted', 'under_review', 'pending_correction', 'approved', 'suspended') LIMIT 1");
        $statement->execute([$championshipId, $athleteId, $teamId]);
        return (bool) $statement->fetchColumn();
    }

    private function baseSelect(): string
    {
        return 'SELECT ar.*, c.name AS championship_name, c.slug AS championship_slug, c.status AS championship_status, c.registration_starts_at, c.registration_ends_at, t.name AS team_name, t.short_name AS team_short_name, a.full_name AS athlete_name, a.sporting_name, a.birth_date, a.gender, a.team_id AS athlete_team_id, TIMESTAMPDIFF(YEAR, a.birth_date, CURDATE()) AS athlete_age, p.name AS primary_position_name, p.code AS primary_position_code, cat.name AS category_name, cat.slug AS category_slug, cat.minimum_age, cat.maximum_age, cat.gender_rule, u.name AS reviewer_name FROM athlete_registrations ar INNER JOIN championships c ON c.id = ar.championship_id INNER JOIN teams t ON t.id = ar.team_id INNER JOIN athletes a ON a.id = ar.athlete_id INNER JOIN positions p ON p.id = a.primary_position_id INNER JOIN categories cat ON cat.id = ar.category_id LEFT JOIN users u ON u.id = ar.reviewed_by';
    }

    private function scopeSql(int $userId, string $scope): array
    {
        if ($scope === 'administrator') return ['1 = 1', []];
        return ["EXISTS (SELECT 1 FROM team_user_assignments tua WHERE tua.team_id = ar.team_id AND tua.user_id = ? AND tua.assignment_type IN ('manager', 'head_coach') AND tua.status = 'active')", [$userId]];
    }
}
