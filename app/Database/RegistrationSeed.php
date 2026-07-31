<?php
declare(strict_types=1);

namespace App\Database;

use PDO;

final class RegistrationSeed
{
    public static function run(PDO $pdo): void
    {
        if (getenv('APP_ENV') === 'production') throw new \RuntimeException('Seed de demonstracao bloqueado em producao.');
        $championship = $pdo->query("SELECT id, category_id FROM championships WHERE slug = 'copa-brasil-de-talentos-2026' LIMIT 1")->fetch();
        $type = $pdo->query("SELECT id FROM athlete_document_types WHERE `key` = 'guardian_authorization' LIMIT 1")->fetchColumn();
        $adminId = self::userId($pdo, 'admin@torneios.local');
        if (!$championship || !$type) throw new \RuntimeException('Dados necessarios ao seed de inscricoes nao foram encontrados.');
        $regulationStatement = $pdo->prepare("SELECT id FROM regulations WHERE championship_id = ? AND status = 'published' LIMIT 1");
        $regulationStatement->execute([(int) $championship['id']]);
        $regulation = $regulationStatement->fetchColumn();
        if (!$regulation) throw new \RuntimeException('Regulamento publicado nao encontrado para seed de inscricoes.');
        $now = date('Y-m-d H:i:s');
        $pdo->prepare("UPDATE championships SET status = 'configured', registration_starts_at = '2026-01-01', registration_ends_at = '2026-12-31', updated_at = ? WHERE id = ?")->execute([$now, (int) $championship['id']]);
        $settings = $pdo->prepare('INSERT INTO regulation_roster_settings (regulation_id, minimum_roster_size, maximum_roster_size, minimum_goalkeepers, allow_multiple_team_registration, created_at, updated_at) VALUES (?, 1, 25, 1, 0, ?, ?) ON DUPLICATE KEY UPDATE minimum_roster_size = 1, maximum_roster_size = 25, minimum_goalkeepers = 1, allow_multiple_team_registration = 0, updated_at = VALUES(updated_at)');
        $settings->execute([(int) $regulation, $now, $now]);
        $required = $pdo->prepare('INSERT INTO regulation_required_documents (regulation_id, document_type_id, required_for_minor, display_order, created_at) VALUES (?, ?, 1, 1, ?) ON DUPLICATE KEY UPDATE required_for_minor = 1');
        $required->execute([(int) $regulation, (int) $type, $now]);
        $teams = $pdo->query("SELECT id FROM teams WHERE championship_id = {$championship['id']} AND deleted_at IS NULL ORDER BY id LIMIT 10")->fetchAll(PDO::FETCH_COLUMN);
        $athletes = $pdo->prepare('SELECT id FROM athletes WHERE team_id = ? AND deleted_at IS NULL ORDER BY id LIMIT 1');
        $find = $pdo->prepare('SELECT id FROM athlete_registrations WHERE championship_id = ? AND team_id = ? AND athlete_id = ? LIMIT 1');
        $insert = $pdo->prepare('INSERT INTO athlete_registrations (championship_id, team_id, athlete_id, category_id, requested_number, status, submitted_at, pending_issues, rejection_reason, reviewed_by, reviewed_at, decided_at, observations, created_by, updated_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $history = $pdo->prepare('INSERT INTO athlete_registration_history (registration_id, from_status, to_status, action, notes, user_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $states = ['approved', 'submitted', 'under_review', 'pending_correction', 'approved', 'rejected', 'suspended', 'cancelled', 'draft', 'approved'];
        foreach ($teams as $index => $teamId) {
            $athletes->execute([(int) $teamId]);
            $athleteId = (int) $athletes->fetchColumn();
            if (!$athleteId) continue;
            $find->execute([(int) $championship['id'], (int) $teamId, $athleteId]);
            if ($find->fetchColumn()) continue;
            $status = $states[$index];
            $submitted = in_array($status, ['submitted', 'under_review', 'pending_correction', 'approved', 'rejected', 'suspended'], true) ? $now : null;
            $reviewed = in_array($status, ['under_review', 'pending_correction', 'approved', 'rejected', 'suspended'], true) ? $adminId : null;
            $decided = in_array($status, ['approved', 'rejected', 'suspended'], true) ? $now : null;
            $issues = $status === 'pending_correction' ? 'Anexe o documento obrigatorio atualizado.' : null;
            $reason = $status === 'rejected' ? 'Dados ficticios rejeitados para demonstracao.' : null;
            $insert->execute([(int) $championship['id'], (int) $teamId, $athleteId, (int) $championship['category_id'], $index + 1, $status, $submitted, $issues, $reason, $reviewed, $reviewed ? $now : null, $decided, 'Inscricao ficticia da Etapa 6.', $adminId, $reviewed ?: $adminId, $now, $now]);
            $registrationId = (int) $pdo->lastInsertId();
            $history->execute([$registrationId, null, 'draft', 'created', null, $adminId, $now]);
            foreach (self::path($status) as [$from, $to, $action, $user, $notes]) $history->execute([$registrationId, $from, $to, $action, $notes, $adminId, $now]);
        }
    }

    private static function path(string $status): array
    {
        return match ($status) {
            'submitted' => [['draft', 'submitted', 'submitted', 'team', null]],
            'under_review' => [['draft', 'submitted', 'submitted', 'team', null], ['submitted', 'under_review', 'review_started', 'organizer', null]],
            'pending_correction' => [['draft', 'submitted', 'submitted', 'team', null], ['submitted', 'under_review', 'review_started', 'organizer', null], ['under_review', 'pending_correction', 'correction_requested', 'organizer', 'Anexe o documento obrigatorio atualizado.']],
            'approved' => [['draft', 'submitted', 'submitted', 'team', null], ['submitted', 'under_review', 'review_started', 'organizer', null], ['under_review', 'approved', 'approved', 'organizer', null]],
            'rejected' => [['draft', 'submitted', 'submitted', 'team', null], ['submitted', 'under_review', 'review_started', 'organizer', null], ['under_review', 'rejected', 'rejected', 'organizer', 'Dados ficticios rejeitados para demonstracao.']],
            'suspended' => [['draft', 'submitted', 'submitted', 'team', null], ['submitted', 'under_review', 'review_started', 'organizer', null], ['under_review', 'approved', 'approved', 'organizer', null], ['approved', 'suspended', 'suspended', 'organizer', 'Suspensao ficticia para demonstracao.']],
            'cancelled' => [['draft', 'cancelled', 'cancelled', 'team', null]],
            default => [],
        };
    }

    private static function userId(PDO $pdo, string $email): int
    {
        $statement = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $statement->execute([$email]);
        return (int) $statement->fetchColumn();
    }
}
