<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

/** Public read model: every query selects only fields safe for the portal. */
final class PublicPortalRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function championship(string $slug): ?array
    {
        $sql = "SELECT c.id, c.name, c.short_name, c.slug, c.description, c.starts_at, c.ends_at, c.status, c.visibility, c.default_theme, c.primary_color, c.secondary_color, c.accent_color, c.logo_path, c.logo_light_path, c.logo_dark_path, c.banner_path, c.favicon_path, c.social_image_path, s.name AS season_name, s.year AS season_year, cat.name AS category_name, cat.slug AS category_slug FROM championships c INNER JOIN seasons s ON s.id = c.season_id INNER JOIN categories cat ON cat.id = c.category_id WHERE c.slug = ? AND c.visibility = 'public' AND c.status <> 'draft' AND c.deleted_at IS NULL LIMIT 1";
        $statement = $this->pdo->prepare($sql); $statement->execute([$slug]); return $statement->fetch() ?: null;
    }

    public function publicChampionships(): array
    {
        return $this->pdo->query("SELECT slug, name, updated_at FROM championships WHERE visibility = 'public' AND status <> 'draft' AND deleted_at IS NULL ORDER BY name")->fetchAll();
    }

    public function phases(int $championshipId): array
    {
        $statement = $this->pdo->prepare("SELECT id, name, slug, phase_type, sequence_number, status FROM competition_phases WHERE championship_id = ? AND status <> 'draft' ORDER BY sequence_number, id"); $statement->execute([$championshipId]); return $statement->fetchAll();
    }

    public function currentPhase(int $championshipId): ?array
    {
        $statement = $this->pdo->prepare("SELECT id, name, slug, phase_type, sequence_number, status FROM competition_phases WHERE championship_id = ? AND status IN ('configured', 'published', 'in_progress', 'finished') ORDER BY CASE status WHEN 'in_progress' THEN 0 WHEN 'published' THEN 1 ELSE 2 END, sequence_number DESC, id DESC LIMIT 1"); $statement->execute([$championshipId]); return $statement->fetch() ?: null;
    }

    public function nextMatches(int $championshipId, int $limit = 8): array
    {
        $sql = "SELECT m.id, m.match_date, m.match_time, m.status, p.name AS phase_name, g.name AS group_name, r.round_number, v.name AS venue_name, ht.id AS home_team_id, ht.name AS home_team_name, ht.short_name AS home_team_short_name, ht.shield_path AS home_shield_path, at.id AS away_team_id, at.name AS away_team_name, at.short_name AS away_team_short_name, at.shield_path AS away_shield_path FROM matches m INNER JOIN competition_phases p ON p.id = m.phase_id INNER JOIN competition_groups g ON g.id = m.group_id INNER JOIN competition_rounds r ON r.id = m.round_id INNER JOIN teams ht ON ht.id = m.home_team_id INNER JOIN teams at ON at.id = m.away_team_id LEFT JOIN venues v ON v.id = m.venue_id WHERE m.championship_id = ? AND m.status IN ('scheduled', 'confirmed', 'postponed') AND (m.match_date IS NULL OR m.match_date >= CURDATE()) AND p.status <> 'draft' ORDER BY m.match_date IS NULL, m.match_date, m.match_time, m.id LIMIT " . max(1, $limit);
        $statement = $this->pdo->prepare($sql); $statement->execute([$championshipId]); return $statement->fetchAll();
    }

    public function results(int $championshipId, int $limit = 12): array
    {
        $sql = "SELECT m.id, m.match_date, m.match_time, m.status, p.name AS phase_name, g.name AS group_name, r.round_number, ht.name AS home_team_name, at.name AS away_team_name, ht.short_name AS home_team_short_name, at.short_name AS away_team_short_name, COALESCE((SELECT SUM(CASE WHEN e.team_id = m.home_team_id THEN 1 ELSE 0 END) FROM match_operation_events e WHERE e.match_id = m.id AND e.valid = 1 AND e.event_type IN ('goal', 'own_goal') AND e.period <> 'penalties'), 0) AS home_score, COALESCE((SELECT SUM(CASE WHEN e.team_id = m.away_team_id THEN 1 ELSE 0 END) FROM match_operation_events e WHERE e.match_id = m.id AND e.valid = 1 AND e.event_type IN ('goal', 'own_goal') AND e.period <> 'penalties'), 0) AS away_score, mo.administrative_home_score, mo.administrative_away_score FROM matches m INNER JOIN competition_phases p ON p.id = m.phase_id INNER JOIN competition_groups g ON g.id = m.group_id INNER JOIN competition_rounds r ON r.id = m.round_id INNER JOIN teams ht ON ht.id = m.home_team_id INNER JOIN teams at ON at.id = m.away_team_id LEFT JOIN match_operations mo ON mo.match_id = m.id WHERE m.championship_id = ? AND m.status = 'homologated' ORDER BY m.match_date DESC, m.match_time DESC, m.id DESC LIMIT " . max(1, $limit);
        $statement = $this->pdo->prepare($sql); $statement->execute([$championshipId]); $rows = $statement->fetchAll(); foreach ($rows as &$row) { if ($row['administrative_home_score'] !== null) { $row['home_score'] = (int) $row['administrative_home_score']; $row['away_score'] = (int) $row['administrative_away_score']; } } unset($row); return $rows;
    }

    public function match(int $championshipId, int $matchId): ?array
    {
        $sql = "SELECT m.id, m.match_date, m.match_time, m.status, p.name AS phase_name, g.name AS group_name, r.round_number, v.name AS venue_name, ht.id AS home_team_id, ht.name AS home_team_name, ht.short_name AS home_team_short_name, ht.shield_path AS home_shield_path, at.id AS away_team_id, at.name AS away_team_name, at.short_name AS away_team_short_name, at.shield_path AS away_shield_path, mo.administrative_home_score, mo.administrative_away_score, mo.first_half_started_at, mo.second_half_ended_at FROM matches m INNER JOIN competition_phases p ON p.id = m.phase_id INNER JOIN competition_groups g ON g.id = m.group_id INNER JOIN competition_rounds r ON r.id = m.round_id LEFT JOIN venues v ON v.id = m.venue_id INNER JOIN teams ht ON ht.id = m.home_team_id INNER JOIN teams at ON at.id = m.away_team_id LEFT JOIN match_operations mo ON mo.match_id = m.id WHERE m.championship_id = ? AND m.id = ? AND m.status NOT IN ('draft', 'cancelled') LIMIT 1";
        $statement = $this->pdo->prepare($sql); $statement->execute([$championshipId, $matchId]); $match = $statement->fetch(); if (!$match) return null;
        $events = $this->pdo->prepare("SELECT e.event_type, e.period, e.minute, e.team_id, t.name AS team_name, a.full_name AS athlete_name, a.sporting_name, ra.full_name AS related_athlete_name, ra.sporting_name AS related_sporting_name FROM match_operation_events e LEFT JOIN teams t ON t.id = e.team_id LEFT JOIN athletes a ON a.id = e.athlete_id LEFT JOIN athletes ra ON ra.id = e.related_athlete_id WHERE e.match_id = ? AND e.valid = 1 AND e.event_type IN ('goal', 'own_goal', 'assist', 'yellow', 'second_yellow', 'red') ORDER BY e.minute IS NULL, e.minute, e.id"); $events->execute([$matchId]);
        $officials = $this->pdo->prepare('SELECT role, display_name, display_order FROM match_officials WHERE match_id = ? ORDER BY display_order, role, id'); $officials->execute([$matchId]);
        $lineups = $this->pdo->prepare("SELECT ml.team_id, t.name AS team_name, f.name AS formation_name, p.athlete_id, p.role, p.shirt_number, a.full_name AS athlete_name, a.sporting_name FROM match_lineups ml INNER JOIN teams t ON t.id = ml.team_id INNER JOIN tactical_formations f ON f.id = ml.tactical_formation_id INNER JOIN match_lineup_players p ON p.lineup_id = ml.id INNER JOIN athletes a ON a.id = p.athlete_id WHERE ml.match_id = ? AND ml.status = 'confirmed' ORDER BY t.name, p.role, p.display_order"); $lineups->execute([$matchId]);
        $match['events'] = $events->fetchAll(); $match['officials'] = $officials->fetchAll(); $match['lineups'] = $lineups->fetchAll(); $match['score'] = $this->score($matchId, $match); return $match;
    }

    public function groups(int $championshipId): array
    {
        $sql = "SELECT g.id, g.name, g.code, g.display_order, g.qualified_limit, p.id AS phase_id, p.name AS phase_name FROM competition_groups g INNER JOIN competition_phases p ON p.id = g.phase_id WHERE p.championship_id = ? AND p.status <> 'draft' AND g.status <> 'draft' ORDER BY p.sequence_number, g.display_order, g.id"; $statement = $this->pdo->prepare($sql); $statement->execute([$championshipId]); $groups = $statement->fetchAll(); $teams = $this->pdo->prepare("SELECT gt.group_id, gt.position, t.id, t.name, t.short_name, t.slug, t.shield_path FROM group_teams gt INNER JOIN teams t ON t.id = gt.team_id WHERE gt.status = 'active' AND gt.group_id = ? ORDER BY gt.position IS NULL, gt.position, t.name"); foreach ($groups as &$group) { $teams->execute([(int) $group['id']]); $group['teams'] = $teams->fetchAll(); } unset($group); return $groups;
    }

    public function standings(int $championshipId): array
    {
        $sql = "SELECT s.group_id, s.position, s.matches_played, s.wins, s.draws, s.losses, s.goals_for, s.goals_against, s.goal_difference, s.points, s.win_percentage, s.situation, g.name AS group_name, p.name AS phase_name, t.id AS team_id, t.name AS team_name, t.short_name AS team_short_name, t.slug, t.shield_path FROM competition_standings s INNER JOIN competition_groups g ON g.id = s.group_id INNER JOIN competition_phases p ON p.id = s.phase_id INNER JOIN teams t ON t.id = s.team_id WHERE s.championship_id = ? AND p.status <> 'draft' ORDER BY p.sequence_number, g.display_order, s.position"; $statement = $this->pdo->prepare($sql); $statement->execute([$championshipId]); return $statement->fetchAll();
    }

    public function knockout(int $championshipId): array
    {
        $sql = "SELECT kr.stage, kr.sequence_number, kt.tie_number, kt.home_source, kt.away_source, kt.status, kt.decided_by, ht.name AS home_team_name, at.name AS away_team_name, wt.name AS winner_team_name, m.id AS match_id, m.status AS match_status FROM knockout_rounds kr INNER JOIN competition_phases p ON p.id = kr.phase_id INNER JOIN knockout_ties kt ON kt.knockout_round_id = kr.id LEFT JOIN teams ht ON ht.id = kt.home_team_id LEFT JOIN teams at ON at.id = kt.away_team_id LEFT JOIN teams wt ON wt.id = kt.winner_team_id LEFT JOIN matches m ON m.id = kt.match_id WHERE kr.championship_id = ? AND p.status <> 'draft' ORDER BY kr.sequence_number, kt.tie_number"; $statement = $this->pdo->prepare($sql); $statement->execute([$championshipId]); return $statement->fetchAll();
    }

    public function teams(int $championshipId, string $search = ''): array
    {
        $where = ["t.championship_id = ?", "t.deleted_at IS NULL", "t.status NOT IN ('draft', 'archived')"]; $params = [$championshipId]; if ($search !== '') { $where[] = '(t.name LIKE ? OR t.short_name LIKE ? OR t.abbreviation LIKE ?)'; $term = '%' . $search . '%'; array_push($params, $term, $term, $term); } $statement = $this->pdo->prepare('SELECT t.id, t.name, t.short_name, t.slug, t.abbreviation, t.description, t.city, t.state, t.primary_color, t.secondary_color, t.shield_path FROM teams t WHERE ' . implode(' AND ', $where) . ' ORDER BY t.name'); $statement->execute($params); return $statement->fetchAll();
    }

    public function team(int $championshipId, string $slug): ?array
    {
        $statement = $this->pdo->prepare("SELECT t.id, t.name, t.short_name, t.slug, t.abbreviation, t.description, t.city, t.state, t.primary_color, t.secondary_color, t.shield_path FROM teams t WHERE t.championship_id = ? AND t.slug = ? AND t.deleted_at IS NULL AND t.status NOT IN ('draft', 'archived') LIMIT 1"); $statement->execute([$championshipId, $slug]); $team = $statement->fetch(); if (!$team) return null;
        $players = $this->pdo->prepare("SELECT DISTINCT a.id, a.full_name, a.sporting_name, a.photo_path, a.preferred_number, a.status, p.name AS position_name FROM athlete_registrations ar INNER JOIN athletes a ON a.id = ar.athlete_id INNER JOIN positions p ON p.id = a.primary_position_id WHERE ar.championship_id = ? AND ar.team_id = ? AND ar.status = 'approved' AND a.status = 'active' AND a.deleted_at IS NULL ORDER BY a.preferred_number IS NULL, a.preferred_number, a.full_name"); $players->execute([$championshipId, $team['id']]); $team['athletes'] = $players->fetchAll(); return $team;
    }

    public function athletes(int $championshipId, string $search = ''): array
    {
        $where = ["ar.championship_id = ?", "ar.status = 'approved'", "a.status = 'active'", 'a.deleted_at IS NULL', "t.status NOT IN ('draft', 'archived')"]; $params = [$championshipId]; if ($search !== '') { $where[] = '(a.full_name LIKE ? OR a.sporting_name LIKE ? OR t.name LIKE ?)'; $term = '%' . $search . '%'; array_push($params, $term, $term, $term); } $sql = 'SELECT DISTINCT a.id, a.full_name, a.sporting_name, a.photo_path, a.preferred_number, p.name AS position_name, t.name AS team_name, t.slug AS team_slug FROM athlete_registrations ar INNER JOIN athletes a ON a.id = ar.athlete_id INNER JOIN teams t ON t.id = ar.team_id INNER JOIN positions p ON p.id = a.primary_position_id WHERE ' . implode(' AND ', $where) . ' ORDER BY a.full_name'; $statement = $this->pdo->prepare($sql); $statement->execute($params); return $statement->fetchAll();
    }

    public function athlete(int $championshipId, int $athleteId): ?array
    {
        $statement = $this->pdo->prepare("SELECT DISTINCT a.id, a.full_name, a.sporting_name, a.photo_path, a.preferred_number, p.name AS position_name, t.name AS team_name, t.slug AS team_slug FROM athlete_registrations ar INNER JOIN athletes a ON a.id = ar.athlete_id INNER JOIN teams t ON t.id = ar.team_id INNER JOIN positions p ON p.id = a.primary_position_id WHERE ar.championship_id = ? AND ar.athlete_id = ? AND ar.status = 'approved' AND a.status = 'active' AND a.deleted_at IS NULL LIMIT 1"); $statement->execute([$championshipId, $athleteId]); return $statement->fetch() ?: null;
    }

    public function leaderboard(int $championshipId, string $kind, int $limit = 10): array
    {
        $event = $kind === 'assists' ? 'assist' : 'goal'; $sql = "SELECT e.athlete_id, a.full_name, a.sporting_name, t.name AS team_name, t.slug AS team_slug, COUNT(*) AS total FROM match_operation_events e INNER JOIN matches m ON m.id = e.match_id INNER JOIN athletes a ON a.id = e.athlete_id INNER JOIN teams t ON t.id = e.team_id WHERE m.championship_id = ? AND m.status = 'homologated' AND e.valid = 1 AND e.event_type = ? AND e.period <> 'penalties' GROUP BY e.athlete_id, a.full_name, a.sporting_name, t.name, t.slug ORDER BY total DESC, a.full_name LIMIT " . max(1, $limit); $statement = $this->pdo->prepare($sql); $statement->execute([$championshipId, $event]); return $statement->fetchAll();
    }

    public function cards(int $championshipId, int $limit = 20): array
    {
        $sql = "SELECT l.athlete_id, a.full_name, a.sporting_name, t.name AS team_name, t.slug AS team_slug, SUM(l.card_type = 'yellow') AS yellows, SUM(l.card_type IN ('red', 'second_yellow')) AS reds, COUNT(*) AS total FROM discipline_ledger l INNER JOIN athletes a ON a.id = l.athlete_id INNER JOIN teams t ON t.id = l.team_id WHERE l.championship_id = ? AND l.person_type = 'athlete' AND l.status = 'considered' GROUP BY l.athlete_id, a.full_name, a.sporting_name, t.name, t.slug ORDER BY total DESC, a.full_name LIMIT " . max(1, $limit); $statement = $this->pdo->prepare($sql); $statement->execute([$championshipId]); return $statement->fetchAll();
    }

    public function regulation(int $championshipId): ?array
    {
        $statement = $this->pdo->prepare("SELECT r.id, r.name, r.version_number, r.effective_from, r.published_at, ps.points_win, ps.points_draw, ps.points_loss, fs.qualified_per_group, ms.regular_time_minutes, ms.extra_time_enabled, ms.penalty_shootout_enabled FROM regulations r LEFT JOIN regulation_points_settings ps ON ps.regulation_id = r.id LEFT JOIN regulation_format_settings fs ON fs.regulation_id = r.id LEFT JOIN regulation_match_settings ms ON ms.regulation_id = r.id WHERE r.championship_id = ? AND r.status = 'published' ORDER BY r.version_number DESC LIMIT 1"); $statement->execute([$championshipId]); return $statement->fetch() ?: null;
    }

    public function champion(int $championshipId): ?array
    {
        $statement = $this->pdo->prepare("SELECT cr.champion_team_id, cr.runner_up_team_id, c.name AS champion_name, c.slug AS champion_slug, c.shield_path AS champion_shield_path, r.name AS runner_up_name, r.slug AS runner_up_slug, r.shield_path AS runner_up_shield_path, p.name AS phase_name FROM competition_results cr INNER JOIN competition_phases p ON p.id = cr.phase_id INNER JOIN teams c ON c.id = cr.champion_team_id LEFT JOIN teams r ON r.id = cr.runner_up_team_id WHERE cr.championship_id = ? AND p.status <> 'draft' ORDER BY p.sequence_number DESC LIMIT 1"); $statement->execute([$championshipId]); return $statement->fetch() ?: null;
    }

    private function score(int $matchId, array $match): array
    {
        $statement = $this->pdo->prepare("SELECT COALESCE(SUM(CASE WHEN team_id = ? THEN 1 ELSE 0 END), 0), COALESCE(SUM(CASE WHEN team_id = ? THEN 1 ELSE 0 END), 0) FROM match_operation_events WHERE match_id = ? AND valid = 1 AND event_type IN ('goal', 'own_goal') AND period <> 'penalties'"); $statement->execute([(int) $match['home_team_id'], (int) $match['away_team_id'], $matchId]); $row = $statement->fetch(PDO::FETCH_NUM); if ($match['administrative_home_score'] !== null) return ['home' => (int) $match['administrative_home_score'], 'away' => (int) $match['administrative_away_score']]; return ['home' => (int) ($row[0] ?? 0), 'away' => (int) ($row[1] ?? 0)];
    }
}
