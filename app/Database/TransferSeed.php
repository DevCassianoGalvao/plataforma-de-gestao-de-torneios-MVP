<?php
declare(strict_types=1);

namespace App\Database;

use PDO;

final class TransferSeed
{
    public static function run(PDO $pdo): void
    {
        if (getenv('APP_ENV') === 'production') throw new \RuntimeException('Seed de demonstracao bloqueado em producao.');
        $championshipId = (int) $pdo->query("SELECT id FROM championships WHERE slug = 'copa-brasil-de-talentos-2026' AND deleted_at IS NULL LIMIT 1")->fetchColumn();
        $adminId = (int) $pdo->query("SELECT id FROM users WHERE email = 'admin@torneios.local' LIMIT 1")->fetchColumn();
        if (!$championshipId || !$adminId) throw new \RuntimeException('Campeonato ou administrador necessario ao seed de transferencias nao encontrado.');
        $pdo->prepare("UPDATE championships SET visibility = 'public', updated_at = ? WHERE id = ?")->execute([date('Y-m-d H:i:s'), $championshipId]);
        $teams = $pdo->prepare('SELECT id FROM teams WHERE championship_id = ? AND deleted_at IS NULL ORDER BY id LIMIT 3'); $teams->execute([$championshipId]); $teamIds = array_map('intval', $teams->fetchAll(\PDO::FETCH_COLUMN));
        $athletes = $pdo->prepare('SELECT a.id, a.team_id FROM athletes a INNER JOIN teams t ON t.id = a.team_id WHERE t.championship_id = ? AND a.deleted_at IS NULL ORDER BY a.id LIMIT 2'); $athletes->execute([$championshipId]); $athleteRows = $athletes->fetchAll();
        if (count($teamIds) < 2 || count($athleteRows) < 2) throw new \RuntimeException('Equipes ou atletas necessarios ao seed de transferencias nao encontrados.');
        $now = date('Y-m-d H:i:s'); $rows = [[$athleteRows[0]['id'], $teamIds[0], $teamIds[1], 'transferencia', date('Y-m-d', strtotime('-2 days')), 'published', 'Movimentacao publicada de demonstracao.'], [$athleteRows[1]['id'], $teamIds[1], $teamIds[0], 'emprestimo', date('Y-m-d'), 'pending', 'Aguardando analise administrativa.']];
        $insert = $pdo->prepare('INSERT INTO transfer_movements (championship_id, athlete_id, previous_team_id, new_team_id, type, movement_date, public_observation, internal_notes, status, published_at, author_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE public_observation = VALUES(public_observation), internal_notes = VALUES(internal_notes), status = VALUES(status), published_at = VALUES(published_at), updated_at = VALUES(updated_at), deleted_at = NULL');
        foreach ($rows as [$athleteId, $previous, $new, $type, $movementDate, $status, $observation]) {
            $publishedAt = $status === 'published' ? $now : null; $insert->execute([$championshipId, $athleteId, $previous, $new, $type, $movementDate, $observation, 'Nota interna ficticia para validar privacidade.', $status, $publishedAt, $adminId, $now, $now]); $find = $pdo->prepare('SELECT id, status FROM transfer_movements WHERE championship_id = ? AND athlete_id = ? AND movement_date = ? AND type = ? LIMIT 1'); $find->execute([$championshipId, $athleteId, $movementDate, $type]); $movement = $find->fetch(); $history = $pdo->prepare('SELECT COUNT(*) FROM transfer_movement_history WHERE transfer_movement_id = ?'); $history->execute([$movement['id']]); if ((int) $history->fetchColumn() === 0) { $pdo->prepare('INSERT INTO transfer_movement_history (transfer_movement_id, from_status, to_status, action, reason, user_id, created_at) VALUES (?, NULL, ?, ?, ?, ?, ?)')->execute([$movement['id'], $status, 'seeded', 'Seed idempotente', $adminId, $now]); }
        }
    }
}
