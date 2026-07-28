CREATE TABLE IF NOT EXISTS tactical_formations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    name VARCHAR(80) NOT NULL,
    player_count TINYINT UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS formation_slots (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    formation_id BIGINT UNSIGNED NOT NULL,
    slot_key VARCHAR(30) NOT NULL,
    position_name VARCHAR(80) NOT NULL,
    position_code VARCHAR(40) NOT NULL,
    position_group VARCHAR(30) NOT NULL,
    abbreviation VARCHAR(10) NOT NULL,
    role_label VARCHAR(80) NOT NULL,
    horizontal DECIMAL(5,2) NOT NULL,
    vertical DECIMAL(5,2) NOT NULL,
    slot_order TINYINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY formation_slot_key(formation_id,slot_key),
    FOREIGN KEY(formation_id) REFERENCES tactical_formations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS team_default_formations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tournament_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NOT NULL,
    formation_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY tournament_team_default(tournament_id,team_id),
    FOREIGN KEY(tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
    FOREIGN KEY(team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY(formation_id) REFERENCES tactical_formations(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS match_lineup_positions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    match_lineup_id BIGINT UNSIGNED NOT NULL,
    match_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NOT NULL,
    formation_id BIGINT UNSIGNED NOT NULL,
    slot_id BIGINT UNSIGNED NOT NULL,
    person_id BIGINT UNSIGNED NOT NULL,
    position_source VARCHAR(20) NOT NULL,
    is_out_of_position TINYINT(1) NOT NULL DEFAULT 0,
    manual_override TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY lineup_person_position(match_lineup_id,person_id),
    UNIQUE KEY match_team_slot(match_id,team_id,slot_id),
    FOREIGN KEY(match_lineup_id) REFERENCES match_lineups(id) ON DELETE CASCADE,
    FOREIGN KEY(match_id) REFERENCES matches(id) ON DELETE CASCADE,
    FOREIGN KEY(team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY(formation_id) REFERENCES tactical_formations(id),
    FOREIGN KEY(slot_id) REFERENCES formation_slots(id),
    FOREIGN KEY(person_id) REFERENCES people(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
