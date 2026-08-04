<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class AccountabilityRepository
{
    public function __construct(private readonly PDO $pdo) {}
    public function championshipsFor(int $userId, bool $administrator): array
    {
        if ($administrator) return $this->pdo->query("SELECT id, name, slug FROM championships WHERE deleted_at IS NULL ORDER BY name")->fetchAll();
        $statement = $this->pdo->prepare("SELECT c.id, c.name, c.slug FROM championships c INNER JOIN championship_user_assignments a ON a.championship_id = c.id WHERE a.user_id = ? AND a.assignment_type = 'accountability' AND c.deleted_at IS NULL ORDER BY c.name");
        $statement->execute([$userId]); return $statement->fetchAll();
    }
    public function allowed(int $championshipId, int $userId, bool $administrator): bool
    {
        if ($administrator) return true;
        $statement = $this->pdo->prepare("SELECT 1 FROM championship_user_assignments WHERE championship_id = ? AND user_id = ? AND assignment_type = 'accountability'");
        $statement->execute([$championshipId, $userId]); return (bool) $statement->fetchColumn();
    }
    public function summary(int $id): array
    {
        $count = fn (string $sql) => (int) $this->pdo->prepare($sql)->execute([$id]);
        $queries = [
            'partidas_aprovadas' => "SELECT COUNT(*) FROM matches WHERE championship_id = ? AND status = 'homologated'",
            'partidas_publicadas' => "SELECT COUNT(*) FROM matches m INNER JOIN match_publications mp ON mp.match_id = m.id AND mp.status = 'published' WHERE m.championship_id = ? AND m.status = 'homologated'",
            'sumulas' => "SELECT COUNT(*) FROM match_report_versions v INNER JOIN match_reports r ON r.id = v.match_report_id INNER JOIN matches m ON m.id = r.match_id WHERE m.championship_id = ?",
            'evidencias' => "SELECT COUNT(*) FROM match_media WHERE championship_id = ? AND status = 'approved' AND deleted_at IS NULL",
            'atletas_aprovados' => "SELECT COUNT(*) FROM athlete_registrations WHERE championship_id = ? AND status = 'approved'",
        ];
        $result = []; foreach ($queries as $key => $sql) { $s = $this->pdo->prepare($sql); $s->execute([$id]); $result[$key] = (int) $s->fetchColumn(); } return $result;
    }
    public function rows(int $id, string $kind): array
    {
        $sql = match ($kind) {
            'partidas' => "SELECT m.id, p.name AS fase, g.name AS grupo, ht.name AS mandante, at.name AS visitante, m.match_date AS data, m.match_time AS horario, 'Aprovada' AS situacao FROM matches m INNER JOIN competition_phases p ON p.id = m.phase_id INNER JOIN competition_groups g ON g.id = m.group_id INNER JOIN teams ht ON ht.id = m.home_team_id INNER JOIN teams at ON at.id = m.away_team_id WHERE m.championship_id = ? AND m.status = 'homologated' ORDER BY m.match_date, m.id",
            'atletas' => "SELECT a.full_name AS atleta, a.sporting_name AS nome_esportivo, t.name AS equipe, 'Aprovada' AS inscricao, ar.decided_at AS aprovado_em FROM athlete_registrations ar INNER JOIN athletes a ON a.id = ar.athlete_id INNER JOIN teams t ON t.id = ar.team_id WHERE ar.championship_id = ? AND ar.status = 'approved' ORDER BY t.name, a.full_name",
            'sumulas' => "SELECT m.id AS partida_id, ht.name AS mandante, at.name AS visitante, v.version_number AS versao, v.verification_code AS codigo, v.created_at AS gerada_em FROM match_report_versions v INNER JOIN match_reports r ON r.id = v.match_report_id INNER JOIN matches m ON m.id = r.match_id INNER JOIN teams ht ON ht.id = m.home_team_id INNER JOIN teams at ON at.id = m.away_team_id WHERE m.championship_id = ? AND m.status = 'homologated' ORDER BY v.created_at DESC",
            'evidencias' => "SELECT mm.id, m.id AS partida_id, mm.title, mm.caption, mm.visibility, mm.captured_at, mm.created_at AS enviada_em FROM match_media mm INNER JOIN matches m ON m.id = mm.match_id WHERE mm.championship_id = ? AND mm.status = 'approved' AND mm.deleted_at IS NULL ORDER BY mm.created_at DESC",
            default => [],
        };
        if ($sql === []) return []; $s = $this->pdo->prepare($sql); $s->execute([$id]); return $s->fetchAll();
    }
    public function log(int $championshipId, int $userId, string $kind, int $count): void
    { $s = $this->pdo->prepare('INSERT INTO accountability_export_logs (championship_id, user_id, export_kind, file_count, created_at) VALUES (?, ?, ?, ?, ?)'); $s->execute([$championshipId, $userId, $kind, $count, date('Y-m-d H:i:s')]); }
}
