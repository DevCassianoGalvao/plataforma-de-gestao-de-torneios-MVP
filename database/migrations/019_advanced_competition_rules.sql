ALTER TABLE standings_snapshots ADD COLUMN tiebreak_criterion VARCHAR(80) NULL AFTER position;

CREATE TABLE IF NOT EXISTS match_substitutions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    match_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NOT NULL,
    player_out_id BIGINT UNSIGNED NOT NULL,
    player_in_id BIGINT UNSIGNED NOT NULL,
    period VARCHAR(30) NOT NULL,
    minute INT UNSIGNED NULL,
    window_number INT UNSIGNED NOT NULL DEFAULT 1,
    is_extra_time TINYINT(1) NOT NULL DEFAULT 0,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    deleted_at DATETIME NULL,
    INDEX substitution_match_team(match_id,team_id,deleted_at),
    FOREIGN KEY (match_id) REFERENCES matches(id),
    FOREIGN KEY (team_id) REFERENCES teams(id),
    FOREIGN KEY (player_out_id) REFERENCES people(id),
    FOREIGN KEY (player_in_id) REFERENCES people(id),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS match_shootout_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    match_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NOT NULL,
    person_id BIGINT UNSIGNED NULL,
    attempt_number INT UNSIGNED NOT NULL,
    round_number INT UNSIGNED NOT NULL,
    result VARCHAR(30) NOT NULL,
    is_sudden_death TINYINT(1) NOT NULL DEFAULT 0,
    is_valid TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME NULL,
    UNIQUE KEY shootout_attempt(match_id,team_id,attempt_number),
    FOREIGN KEY (match_id) REFERENCES matches(id),
    FOREIGN KEY (team_id) REFERENCES teams(id),
    FOREIGN KEY (person_id) REFERENCES people(id),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS card_cleanups (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tournament_id BIGINT UNSIGNED NOT NULL,
    stage_id BIGINT UNSIGNED NULL,
    rule_key VARCHAR(80) NOT NULL,
    executed_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY cleanup_once(tournament_id,stage_id,rule_key),
    FOREIGN KEY (tournament_id) REFERENCES tournaments(id),
    FOREIGN KEY (stage_id) REFERENCES stages(id),
    FOREIGN KEY (executed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS match_rule_exceptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    match_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NOT NULL,
    person_id BIGINT UNSIGNED NOT NULL,
    exception_type VARCHAR(60) NOT NULL,
    reason TEXT NOT NULL,
    authorized_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (match_id) REFERENCES matches(id),
    FOREIGN KEY (team_id) REFERENCES teams(id),
    FOREIGN KEY (person_id) REFERENCES people(id),
    FOREIGN KEY (authorized_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
