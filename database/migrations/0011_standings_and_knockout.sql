CREATE TABLE competition_standings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    championship_id BIGINT UNSIGNED NOT NULL,
    phase_id BIGINT UNSIGNED NOT NULL,
    group_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NOT NULL,
    matches_played SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    wins SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    draws SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    losses SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    goals_for SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    goals_against SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    goal_difference INT NOT NULL DEFAULT 0,
    points INT NOT NULL DEFAULT 0,
    win_percentage DECIMAL(6,2) NOT NULL DEFAULT 0,
    position SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    situation VARCHAR(30) NOT NULL DEFAULT 'pending',
    separated_by VARCHAR(40) NULL,
    calculation_hash CHAR(64) NOT NULL,
    calculated_at DATETIME NOT NULL,
    UNIQUE KEY uq_competition_standing_team (group_id, team_id),
    CONSTRAINT fk_standings_championship FOREIGN KEY (championship_id) REFERENCES championships (id),
    CONSTRAINT fk_standings_phase FOREIGN KEY (phase_id) REFERENCES competition_phases (id),
    CONSTRAINT fk_standings_group FOREIGN KEY (group_id) REFERENCES competition_groups (id),
    CONSTRAINT fk_standings_team FOREIGN KEY (team_id) REFERENCES teams (id),
    INDEX idx_standings_group_position (group_id, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE standings_calculation_runs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    championship_id BIGINT UNSIGNED NOT NULL,
    phase_id BIGINT UNSIGNED NOT NULL,
    group_id BIGINT UNSIGNED NOT NULL,
    source_hash CHAR(64) NOT NULL,
    calculated_by BIGINT UNSIGNED NOT NULL,
    calculated_at DATETIME NOT NULL,
    UNIQUE KEY uq_standings_run_source (group_id, source_hash),
    CONSTRAINT fk_standings_run_championship FOREIGN KEY (championship_id) REFERENCES championships (id),
    CONSTRAINT fk_standings_run_phase FOREIGN KEY (phase_id) REFERENCES competition_phases (id),
    CONSTRAINT fk_standings_run_group FOREIGN KEY (group_id) REFERENCES competition_groups (id),
    CONSTRAINT fk_standings_run_user FOREIGN KEY (calculated_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE knockout_rounds (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    championship_id BIGINT UNSIGNED NOT NULL,
    phase_id BIGINT UNSIGNED NOT NULL,
    stage VARCHAR(30) NOT NULL,
    sequence_number SMALLINT UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_knockout_round_stage (phase_id, stage),
    CONSTRAINT fk_knockout_round_championship FOREIGN KEY (championship_id) REFERENCES championships (id),
    CONSTRAINT fk_knockout_round_phase FOREIGN KEY (phase_id) REFERENCES competition_phases (id),
    CONSTRAINT fk_knockout_round_creator FOREIGN KEY (created_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE knockout_ties (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    knockout_round_id BIGINT UNSIGNED NOT NULL,
    tie_number SMALLINT UNSIGNED NOT NULL,
    home_source VARCHAR(80) NOT NULL,
    away_source VARCHAR(80) NOT NULL,
    home_team_id BIGINT UNSIGNED NULL,
    away_team_id BIGINT UNSIGNED NULL,
    match_id BIGINT UNSIGNED NULL,
    winner_team_id BIGINT UNSIGNED NULL,
    loser_team_id BIGINT UNSIGNED NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    decided_by VARCHAR(30) NULL,
    decided_at DATETIME NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_knockout_tie_number (knockout_round_id, tie_number),
    CONSTRAINT fk_knockout_tie_round FOREIGN KEY (knockout_round_id) REFERENCES knockout_rounds (id) ON DELETE CASCADE,
    CONSTRAINT fk_knockout_tie_home FOREIGN KEY (home_team_id) REFERENCES teams (id),
    CONSTRAINT fk_knockout_tie_away FOREIGN KEY (away_team_id) REFERENCES teams (id),
    CONSTRAINT fk_knockout_tie_match FOREIGN KEY (match_id) REFERENCES matches (id),
    CONSTRAINT fk_knockout_tie_winner FOREIGN KEY (winner_team_id) REFERENCES teams (id),
    CONSTRAINT fk_knockout_tie_loser FOREIGN KEY (loser_team_id) REFERENCES teams (id),
    INDEX idx_knockout_tie_round_status (knockout_round_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE competition_results (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    championship_id BIGINT UNSIGNED NOT NULL,
    phase_id BIGINT UNSIGNED NOT NULL,
    champion_team_id BIGINT UNSIGNED NULL,
    runner_up_team_id BIGINT UNSIGNED NULL,
    decided_by BIGINT UNSIGNED NOT NULL,
    decided_at DATETIME NOT NULL,
    UNIQUE KEY uq_competition_result (championship_id, phase_id),
    CONSTRAINT fk_competition_result_championship FOREIGN KEY (championship_id) REFERENCES championships (id),
    CONSTRAINT fk_competition_result_phase FOREIGN KEY (phase_id) REFERENCES competition_phases (id),
    CONSTRAINT fk_competition_result_champion FOREIGN KEY (champion_team_id) REFERENCES teams (id),
    CONSTRAINT fk_competition_result_runner_up FOREIGN KEY (runner_up_team_id) REFERENCES teams (id),
    CONSTRAINT fk_competition_result_user FOREIGN KEY (decided_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
