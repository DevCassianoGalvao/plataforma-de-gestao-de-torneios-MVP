<?php
declare(strict_types=1);

namespace App\Database;

use App\Services\RoundRobinGenerator;
use PDO;

final class ScheduleSeed
{
    public static function run(PDO $pdo): void
    {
        if (getenv('APP_ENV') === 'production') throw new \RuntimeException('Seed de demonstracao bloqueado em producao.');
        $championship = $pdo->query("SELECT id FROM championships WHERE slug = 'copa-brasil-de-talentos-2026' LIMIT 1")->fetch();
        $adminId = self::userId($pdo, 'admin@torneios.local');
        if (!$championship || !$adminId) throw new \RuntimeException('Dados necessarios ao seed da tabela nao foram encontrados.');
        $championshipId = (int) $championship['id'];
        $now = date('Y-m-d H:i:s');

        $venue = $pdo->prepare('INSERT INTO venues (championship_id, name, address, city, state, capacity, status, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, \'active\', ?, ?, ?) ON DUPLICATE KEY UPDATE status = \'active\', updated_at = VALUES(updated_at)');
        foreach ([['Estadio Central', 'Avenida dos Campeoes, 100', 'Sao Paulo', 'SP', 12000], ['Campo Municipal', 'Rua do Esporte, 20', 'Sao Paulo', 'SP', 3500]] as $item) $venue->execute([$championshipId, $item[0], $item[1], $item[2], $item[3], $item[4], $adminId, $now, $now]);
        $venues = $pdo->prepare('SELECT id FROM venues WHERE championship_id = ? AND status = \'active\' AND deleted_at IS NULL ORDER BY id');
        $venues->execute([$championshipId]);
        $venueIds = array_map('intval', $venues->fetchAll(PDO::FETCH_COLUMN));

        $phaseSql = 'INSERT INTO competition_phases (championship_id, name, slug, phase_type, sequence_number, group_count, teams_per_group, qualified_per_group, status, published_at, created_by, created_at, updated_at) VALUES (?, ?, ?, \'groups\', 1, 2, 5, 4, \'published\', ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), group_count = 2, teams_per_group = 5, qualified_per_group = 4, status = \'published\', published_at = COALESCE(published_at, VALUES(published_at)), updated_at = VALUES(updated_at)';
        $pdo->prepare($phaseSql)->execute([$championshipId, 'Fase de grupos', 'fase-grupos', $now, $adminId, $now, $now]);
        $phaseStatement = $pdo->prepare('SELECT * FROM competition_phases WHERE championship_id = ? AND slug = ? LIMIT 1');
        $phaseStatement->execute([$championshipId, 'fase-grupos']);
        $phase = $phaseStatement->fetch();

        $groupSql = 'INSERT INTO competition_groups (phase_id, name, code, display_order, teams_limit, qualified_limit, status, published_at, created_at, updated_at) VALUES (?, ?, ?, ?, 5, 4, \'published\', ?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), teams_limit = 5, qualified_limit = 4, status = \'published\', published_at = COALESCE(published_at, VALUES(published_at)), updated_at = VALUES(updated_at)';
        $groupIds = [];
        foreach ([['Grupo A', 'A', 1], ['Grupo B', 'B', 2]] as $item) {
            $pdo->prepare($groupSql)->execute([(int) $phase['id'], $item[0], $item[1], $item[2], $now, $now, $now]);
            $statement = $pdo->prepare('SELECT id FROM competition_groups WHERE phase_id = ? AND code = ? LIMIT 1');
            $statement->execute([(int) $phase['id'], $item[1]]);
            $groupIds[$item[1]] = (int) $statement->fetchColumn();
        }
        $teams = $pdo->prepare('SELECT id FROM teams WHERE championship_id = ? AND deleted_at IS NULL AND status = \'active\' ORDER BY id LIMIT 10');
        $teams->execute([$championshipId]);
        $teamIds = array_map('intval', $teams->fetchAll(PDO::FETCH_COLUMN));
        if (count($teamIds) < 10) throw new \RuntimeException('Seed da tabela exige dez equipes ativas.');
        $membership = $pdo->prepare('INSERT INTO group_teams (phase_id, group_id, team_id, position, status, joined_at, updated_at) VALUES (?, ?, ?, ?, \'active\', ?, ?) ON DUPLICATE KEY UPDATE group_id = VALUES(group_id), position = VALUES(position), status = \'active\', withdrawn_at = NULL, updated_at = VALUES(updated_at)');
        foreach (array_chunk($teamIds, 5) as $index => $groupTeams) foreach ($groupTeams as $position => $teamId) $membership->execute([(int) $phase['id'], array_values($groupIds)[$index], $teamId, $position + 1, $now, $now]);

        $round = $pdo->prepare('INSERT INTO competition_rounds (phase_id, group_id, round_number, period_start, period_end, status, published_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, \'published\', ?, ?, ?) ON DUPLICATE KEY UPDATE period_start = VALUES(period_start), period_end = VALUES(period_end), status = \'published\', updated_at = VALUES(updated_at)');
        $match = $pdo->prepare('INSERT INTO matches (championship_id, phase_id, group_id, round_id, home_team_id, away_team_id, venue_id, fixture_key, leg_number, match_order, match_date, match_time, status, observation, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, \'scheduled\', ?, ?, ?, ?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)');
        foreach (array_values($groupIds) as $groupIndex => $groupId) {
            $groupTeamStatement = $pdo->prepare('SELECT team_id FROM group_teams WHERE group_id = ? AND status = \'active\' ORDER BY position');
            $groupTeamStatement->execute([$groupId]);
            $fixtures = RoundRobinGenerator::generate(array_map('intval', $groupTeamStatement->fetchAll(PDO::FETCH_COLUMN)), false);
            foreach ($fixtures as $roundIndex => $fixturesInRound) {
                $date = (new \DateTimeImmutable('2026-09-01'))->modify('+' . ($roundIndex + $groupIndex * 6) . ' days')->format('Y-m-d');
                $round->execute([(int) $phase['id'], $groupId, $roundIndex + 1, $date, $date, $now, $now, $now]);
                $roundIdStatement = $pdo->prepare('SELECT id FROM competition_rounds WHERE group_id = ? AND round_number = ? LIMIT 1');
                $roundIdStatement->execute([$groupId, $roundIndex + 1]);
                $roundId = (int) $roundIdStatement->fetchColumn();
                foreach ($fixturesInRound as $order => $fixture) {
                    $key = hash('sha256', implode(':', [(int) $phase['id'], $groupId, $roundIndex + 1, 1, $fixture['home_team_id'], $fixture['away_team_id']]));
                    $match->execute([$championshipId, (int) $phase['id'], $groupId, $roundId, $fixture['home_team_id'], $fixture['away_team_id'], $venueIds[$order % count($venueIds)], $key, $order + 1, $date, $order === 0 ? '18:00:00' : '20:00:00', 'Seed ficticio da Etapa 7.', $adminId, $now, $now]);
                }
            }
        }

        // Dados de demonstracao precisam ser visiveis no portal; operacao real exige publicacao explicita.
        $pdo->prepare("INSERT INTO match_publications (match_id, status, published_at, created_at, updated_at) SELECT m.id, 'published', ?, ?, ? FROM matches m WHERE m.championship_id = ? ON DUPLICATE KEY UPDATE status = 'published', published_at = COALESCE(published_at, VALUES(published_at)), updated_at = VALUES(updated_at)")
            ->execute([$now, $now, $now, $championshipId]);
    }

    private static function userId(PDO $pdo, string $email): int
    {
        $statement = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $statement->execute([$email]);
        return (int) $statement->fetchColumn();
    }
}
