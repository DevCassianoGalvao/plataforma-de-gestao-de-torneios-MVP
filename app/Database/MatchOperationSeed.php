<?php
declare(strict_types=1);

namespace App\Database;

use PDO;

final class MatchOperationSeed
{
    public static function run(PDO $pdo): void
    {
        if (getenv('APP_ENV') === 'production') throw new \RuntimeException('Seed de demonstracao bloqueado em producao.');
        $operator = (int) $pdo->query("SELECT id FROM users WHERE email = 'operador@torneios.local' LIMIT 1")->fetchColumn();
        $admin = (int) $pdo->query("SELECT id FROM users WHERE email = 'admin@torneios.local' LIMIT 1")->fetchColumn();
        $match = $pdo->query('SELECT id FROM matches ORDER BY id LIMIT 1')->fetch();
        if (!$operator || !$admin || !$match) return;
        $matchId = (int) $match['id'];
        $now = date('Y-m-d H:i:s');
        $pdo->prepare("INSERT INTO match_operator_assignments (match_id, user_id, assignment_type, status, created_by, created_at) VALUES (?, ?, 'operator', 'active', ?, ?) ON DUPLICATE KEY UPDATE status = 'active', ended_at = NULL")->execute([$matchId, $operator, $admin, $now]);
        $officials = [['referee', 'Arbitro Demo'], ['assistant_1', 'Assistente 1 Demo'], ['assistant_2', 'Assistente 2 Demo'], ['scorekeeper', 'Mesario Demo']];
        $insert = $pdo->prepare('INSERT INTO match_officials (match_id, role, display_name, display_order, created_by, created_at, updated_at) VALUES (?, ?, ?, 1, ?, ?, ?) ON DUPLICATE KEY UPDATE display_name = VALUES(display_name), updated_at = VALUES(updated_at)');
        foreach ($officials as [$role, $name]) $insert->execute([$matchId, $role, $name, $admin, $now, $now]);
    }
}
