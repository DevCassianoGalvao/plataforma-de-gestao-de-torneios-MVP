<?php
declare(strict_types=1);

namespace App\Database;

use PDO;

/** Creates only missing official lineups for the two simulated semifinals. */
final class MatchLineupDemoSeed
{
    public static function run(PDO $pdo): void
    {
        if (getenv('APP_ENV') === 'production' && getenv('ALLOW_DEMO_SIMULATION') !== '1') {
            throw new \RuntimeException('Escalações fictícias bloqueadas em produção. Defina ALLOW_DEMO_SIMULATION=1 para a simulação autorizada.');
        }
        $championshipId = (int) $pdo->query("SELECT id FROM championships WHERE slug = 'copa-brasil-de-talentos-2026' AND deleted_at IS NULL LIMIT 1")->fetchColumn();
        $adminId = (int) $pdo->query("SELECT id FROM users WHERE email = 'admin@torneios.local' LIMIT 1")->fetchColumn();
        $formationId = (int) $pdo->query("SELECT id FROM tactical_formations WHERE slug = '4-3-3' AND active = 1 LIMIT 1")->fetchColumn();
        if (!$championshipId || !$adminId || !$formationId) throw new \RuntimeException('Dados necessários para criar escalações fictícias não foram encontrados.');

        $slots = $pdo->prepare('SELECT slot_key, position_code, position_group, display_order FROM tactical_formation_slots WHERE tactical_formation_id = ? ORDER BY display_order, id');
        $slots->execute([$formationId]);
        $slots = $slots->fetchAll();
        if (count($slots) !== 11) throw new \RuntimeException('A formação de demonstração precisa de onze posições.');

        $matches = $pdo->prepare("SELECT m.id, m.home_team_id, m.away_team_id FROM matches m INNER JOIN knockout_ties kt ON kt.match_id = m.id INNER JOIN knockout_rounds kr ON kr.id = kt.knockout_round_id WHERE m.championship_id = ? AND kr.stage = 'semifinals' AND m.status IN ('scheduled', 'confirmed') ORDER BY m.id");
        $matches->execute([$championshipId]);
        $now = date('Y-m-d H:i:s');
        foreach ($matches->fetchAll() as $match) {
            foreach ([(int) $match['home_team_id'], (int) $match['away_team_id']] as $teamId) self::createIfMissing($pdo, (int) $match['id'], $teamId, $championshipId, $formationId, $slots, $adminId, $now);
        }
    }

    private static function createIfMissing(PDO $pdo, int $matchId, int $teamId, int $championshipId, int $formationId, array $slots, int $adminId, string $now): void
    {
        $exists = $pdo->prepare('SELECT id FROM match_lineups WHERE match_id = ? AND team_id = ? LIMIT 1');
        $exists->execute([$matchId, $teamId]);
        if ($exists->fetchColumn()) return;

        $athletes = $pdo->prepare("SELECT a.id, a.preferred_number, p.code AS position_code, p.position_group FROM athlete_registrations ar INNER JOIN athletes a ON a.id = ar.athlete_id INNER JOIN positions p ON p.id = a.primary_position_id WHERE ar.championship_id = ? AND ar.team_id = ? AND ar.status = 'approved' AND a.team_id = ? AND a.status = 'active' AND a.deleted_at IS NULL ORDER BY a.preferred_number IS NULL, a.preferred_number, a.id");
        $athletes->execute([$championshipId, $teamId, $teamId]);
        $available = $athletes->fetchAll();
        if (count($available) < 11) return;

        $chosen = [];
        foreach ($slots as $slot) {
            $bestIndex = 0;
            $bestScore = -1;
            foreach ($available as $index => $athlete) {
                $score = ((string) $athlete['position_code'] === (string) $slot['position_code']) ? 100 : ((string) $athlete['position_group'] === (string) $slot['position_group'] ? 60 : 0);
                if ($score > $bestScore) { $bestScore = $score; $bestIndex = $index; }
            }
            $athlete = $available[$bestIndex];
            array_splice($available, $bestIndex, 1);
            $chosen[] = ['slot' => $slot, 'athlete' => $athlete, 'out_of_position' => $bestScore < 100];
        }

        $goalkeeper = (int) ($chosen[0]['athlete']['id'] ?? 0);
        $captain = (int) ($chosen[1]['athlete']['id'] ?? $goalkeeper);
        $insertLineup = $pdo->prepare("INSERT INTO match_lineups (match_id, team_id, tactical_formation_id, status, version, captain_athlete_id, goalkeeper_athlete_id, confirmed_by, confirmed_at, created_by, created_at, updated_at) VALUES (?, ?, ?, 'confirmed', 1, ?, ?, ?, ?, ?, ?, ?)");
        $insertLineup->execute([$matchId, $teamId, $formationId, $captain, $goalkeeper, $adminId, $now, $adminId, $now, $now]);
        $lineupId = (int) $pdo->lastInsertId();
        $insertPlayer = $pdo->prepare('INSERT INTO match_lineup_players (lineup_id, athlete_id, role, slot_key, position_code, shirt_number, is_captain, is_goalkeeper, is_out_of_position, display_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($chosen as $order => $item) {
            $athlete = $item['athlete'];
            $slot = $item['slot'];
            $insertPlayer->execute([$lineupId, (int) $athlete['id'], 'starter', $slot['slot_key'], $slot['position_code'], $athlete['preferred_number'] ?: null, (int) $athlete['id'] === $captain ? 1 : 0, (int) $athlete['id'] === $goalkeeper ? 1 : 0, $item['out_of_position'] ? 1 : 0, $order + 1, $now, $now]);
        }
        foreach (array_slice($available, 0, 7) as $order => $athlete) {
            $insertPlayer->execute([$lineupId, (int) $athlete['id'], 'reserve', null, $athlete['position_code'], $athlete['preferred_number'] ?: null, 0, 0, 0, $order + 1, $now, $now]);
        }
        $history = $pdo->prepare("INSERT INTO match_lineup_history (lineup_id, action, version, status, tactical_formation_id, reason, changed_by, created_at) VALUES (?, 'seeded', 1, 'confirmed', ?, 'Simulação autorizada: escalação oficial de demonstração.', ?, ?)");
        $history->execute([$lineupId, $formationId, $adminId, $now]);
    }
}
