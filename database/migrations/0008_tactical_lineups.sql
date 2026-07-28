CREATE TABLE match_lineups (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    match_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NOT NULL,
    tactical_formation_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    version SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    captain_athlete_id BIGINT UNSIGNED NULL,
    goalkeeper_athlete_id BIGINT UNSIGNED NULL,
    confirmed_by BIGINT UNSIGNED NULL,
    confirmed_at DATETIME NULL,
    reopened_by BIGINT UNSIGNED NULL,
    reopened_at DATETIME NULL,
    reopen_reason TEXT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_match_lineup_team (match_id, team_id),
    CONSTRAINT fk_lineups_match FOREIGN KEY (match_id) REFERENCES matches (id) ON DELETE CASCADE,
    CONSTRAINT fk_lineups_team FOREIGN KEY (team_id) REFERENCES teams (id),
    CONSTRAINT fk_lineups_formation FOREIGN KEY (tactical_formation_id) REFERENCES tactical_formations (id),
    CONSTRAINT fk_lineups_captain FOREIGN KEY (captain_athlete_id) REFERENCES athletes (id) ON DELETE SET NULL,
    CONSTRAINT fk_lineups_goalkeeper FOREIGN KEY (goalkeeper_athlete_id) REFERENCES athletes (id) ON DELETE SET NULL,
    CONSTRAINT fk_lineups_confirmed_by FOREIGN KEY (confirmed_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_lineups_reopened_by FOREIGN KEY (reopened_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_lineups_creator FOREIGN KEY (created_by) REFERENCES users (id),
    INDEX idx_lineups_match_status (match_id, status),
    INDEX idx_lineups_team_status (team_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE match_lineup_players (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lineup_id BIGINT UNSIGNED NOT NULL,
    athlete_id BIGINT UNSIGNED NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'starter',
    slot_key VARCHAR(60) NULL,
    position_code VARCHAR(40) NOT NULL,
    shirt_number TINYINT UNSIGNED NULL,
    is_captain TINYINT(1) NOT NULL DEFAULT 0,
    is_goalkeeper TINYINT(1) NOT NULL DEFAULT 0,
    is_out_of_position TINYINT(1) NOT NULL DEFAULT 0,
    display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_lineup_players_lineup FOREIGN KEY (lineup_id) REFERENCES match_lineups (id) ON DELETE CASCADE,
    CONSTRAINT fk_lineup_players_athlete FOREIGN KEY (athlete_id) REFERENCES athletes (id),
    UNIQUE KEY uq_lineup_player (lineup_id, athlete_id),
    UNIQUE KEY uq_lineup_slot (lineup_id, slot_key),
    INDEX idx_lineup_players_role (lineup_id, role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE match_lineup_staff (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lineup_id BIGINT UNSIGNED NOT NULL,
    team_staff_id BIGINT UNSIGNED NOT NULL,
    present TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_lineup_staff_lineup FOREIGN KEY (lineup_id) REFERENCES match_lineups (id) ON DELETE CASCADE,
    CONSTRAINT fk_lineup_staff_member FOREIGN KEY (team_staff_id) REFERENCES team_staff (id),
    UNIQUE KEY uq_lineup_staff (lineup_id, team_staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE match_lineup_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lineup_id BIGINT UNSIGNED NOT NULL,
    action VARCHAR(30) NOT NULL,
    version SMALLINT UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL,
    tactical_formation_id BIGINT UNSIGNED NULL,
    reason TEXT NULL,
    changed_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_lineup_history_lineup FOREIGN KEY (lineup_id) REFERENCES match_lineups (id) ON DELETE CASCADE,
    CONSTRAINT fk_lineup_history_formation FOREIGN KEY (tactical_formation_id) REFERENCES tactical_formations (id) ON DELETE SET NULL,
    CONSTRAINT fk_lineup_history_user FOREIGN KEY (changed_by) REFERENCES users (id),
    INDEX idx_lineup_history_lineup (lineup_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
