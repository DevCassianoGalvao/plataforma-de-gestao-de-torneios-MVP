<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class PlayerBanRepository
{
    private const BAN_ALLOWED = "EXISTS (SELECT 1 FROM regulations r INNER JOIN regulation_advanced_settings ras ON ras.regulation_id = r.id WHERE r.championship_id = c.id AND r.status = 'published' AND ras.allow_player_ban = 1)";

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function banned(): array
    {
        return $this->pdo->query("SELECT a.id, a.full_name, a.sporting_name, a.banned_at, a.banned_reason, u.name AS banned_by_name, c.name AS championship_name,
                (SELECT GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ', ') FROM athlete_registrations ar INNER JOIN teams t ON t.id = ar.team_id WHERE ar.athlete_id = a.id AND ar.status = 'approved') AS team_names
            FROM athletes a LEFT JOIN users u ON u.id = a.banned_by LEFT JOIN championships c ON c.id = a.banned_championship_id
            WHERE a.banned_at IS NOT NULL AND a.deleted_at IS NULL ORDER BY a.banned_at DESC, a.full_name")->fetchAll();
    }

    /** Atletas com inscricao aprovada em campeonato cujo regulamento publicado permite banimento. */
    public function candidates(): array
    {
        return $this->pdo->query("SELECT ar.athlete_id, ar.championship_id, a.full_name, t.name AS team_name, c.name AS championship_name
            FROM athlete_registrations ar
            INNER JOIN athletes a ON a.id = ar.athlete_id
            INNER JOIN teams t ON t.id = ar.team_id
            INNER JOIN championships c ON c.id = ar.championship_id AND c.deleted_at IS NULL AND c.archived_at IS NULL
            WHERE ar.status = 'approved' AND a.deleted_at IS NULL AND a.banned_at IS NULL AND " . self::BAN_ALLOWED . "
            ORDER BY c.name, t.name, a.full_name")->fetchAll();
    }

    public function banAllowed(int $athleteId, int $championshipId): bool
    {
        $statement = $this->pdo->prepare("SELECT 1 FROM athlete_registrations ar INNER JOIN championships c ON c.id = ar.championship_id WHERE ar.athlete_id = ? AND ar.championship_id = ? AND ar.status = 'approved' AND " . self::BAN_ALLOWED . ' LIMIT 1');
        $statement->execute([$athleteId, $championshipId]);
        return (bool) $statement->fetchColumn();
    }

    public function ban(int $athleteId, int $championshipId, string $bannedAt, string $reason, int $userId): bool
    {
        $statement = $this->pdo->prepare('UPDATE athletes SET banned_at = ?, banned_reason = ?, banned_by = ?, banned_championship_id = ?, updated_at = ? WHERE id = ? AND banned_at IS NULL AND deleted_at IS NULL');
        $statement->execute([$bannedAt, $reason, $userId, $championshipId, date('Y-m-d H:i:s'), $athleteId]);
        return $statement->rowCount() === 1;
    }

    public function unban(int $athleteId): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, full_name, banned_at, banned_reason FROM athletes WHERE id = ? AND banned_at IS NOT NULL LIMIT 1');
        $statement->execute([$athleteId]);
        $row = $statement->fetch();
        if (!$row) return null;
        $this->pdo->prepare('UPDATE athletes SET banned_at = NULL, banned_reason = NULL, banned_by = NULL, banned_championship_id = NULL, updated_at = ? WHERE id = ?')->execute([date('Y-m-d H:i:s'), $athleteId]);
        return $row;
    }
}
