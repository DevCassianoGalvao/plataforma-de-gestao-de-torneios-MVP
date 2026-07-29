<?php
declare(strict_types=1);

namespace App\Database;

use PDO;

final class DisciplineSeed
{
    public static function run(PDO $pdo): void
    {
        if (getenv('APP_ENV') === 'production') throw new \RuntimeException('Seed de demonstracao bloqueado em producao.');
        $admin = (int) $pdo->query("SELECT id FROM users WHERE email = 'admin@torneios.local' LIMIT 1")->fetchColumn();
        $match = $pdo->query('SELECT id, championship_id, phase_id, match_date, match_time, home_team_id, away_team_id FROM matches ORDER BY id LIMIT 1')->fetch();
        if (!$admin || !$match) return;
        $athletes = $pdo->prepare('SELECT id, team_id FROM athletes WHERE team_id IN (?, ?) AND deleted_at IS NULL ORDER BY id LIMIT 4');
        $athletes->execute([(int) $match['home_team_id'], (int) $match['away_team_id']]);
        $people = $athletes->fetchAll();
        if (count($people) < 2) return;
        $now = date('Y-m-d H:i:s');
        $event = $pdo->prepare("INSERT INTO match_operation_events (match_id, team_id, person_type, athlete_id, event_type, period, minute, notes, valid, created_by, created_at, updated_at) SELECT ?, ?, 'athlete', ?, ?, 'regular', ?, ?, 1, ?, ?, ? WHERE NOT EXISTS (SELECT 1 FROM match_operation_events WHERE match_id = ? AND athlete_id = ? AND event_type = ? AND notes = ?)");
        $event->execute([(int) $match['id'], (int) $people[0]['team_id'], (int) $people[0]['id'], 'yellow', 20, 'Seed disciplina: amarelo.', $admin, $now, $now, (int) $match['id'], (int) $people[0]['id'], 'yellow', 'Seed disciplina: amarelo.']);
        $event->execute([(int) $match['id'], (int) $people[1]['team_id'], (int) $people[1]['id'], 'red', 70, 'Seed disciplina: vermelho.', $admin, $now, $now, (int) $match['id'], (int) $people[1]['id'], 'red', 'Seed disciplina: vermelho.']);
        $ledger = $pdo->prepare('INSERT INTO discipline_ledger (championship_id, match_id, phase_id, team_id, person_type, athlete_id, card_type, source, source_key, status, occurred_at, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, \'athlete\', ?, ?, \'seed\', ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)');
        $occurred = ((string) ($match['match_date'] ?: date('Y-m-d')) . ' ' . ((string) ($match['match_time'] ?: '00:00:00')));
        $ledger->execute([(int) $match['championship_id'], (int) $match['id'], (int) $match['phase_id'], (int) $people[0]['team_id'], (int) $people[0]['id'], 'yellow', 'seed:yellow', 'considered', $occurred, $admin, $now, $now]);
        $ledger->execute([(int) $match['championship_id'], (int) $match['id'], (int) $match['phase_id'], (int) $people[1]['team_id'], (int) $people[1]['id'], 'red', 'seed:red', 'considered', $occurred, $admin, $now, $now]);
        $suspension = $pdo->prepare('INSERT INTO discipline_suspensions (championship_id, team_id, person_type, athlete_id, origin, suspension_type, total_matches, fulfilled_matches, status, generating_match_id, source_key, notes, created_by, created_at, updated_at) VALUES (?, ?, \'athlete\', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)');
        $suspension->execute([(int) $match['championship_id'], (int) $people[1]['team_id'], (int) $people[1]['id'], 'red_card', 'automatic_card', 1, 0, 'active', (int) $match['id'], 'seed:active', 'Seed disciplina: suspensao ativa.', $admin, $now, $now]);
        $suspension->execute([(int) $match['championship_id'], (int) $people[0]['team_id'], (int) $people[0]['id'], 'manual', 'manual', 1, 1, 'fulfilled', null, 'seed:fulfilled', 'Seed disciplina: suspensao cumprida.', $admin, $now, $now]);
        $suspension->execute([(int) $match['championship_id'], (int) $people[0]['team_id'], (int) $people[0]['id'], 'manual', 'manual', 1, 0, 'revoked', null, 'seed:revoked', 'Seed disciplina: suspensao revogada.', $admin, $now, $now]);
    }
}
