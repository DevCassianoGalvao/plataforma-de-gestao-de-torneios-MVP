CREATE TABLE staff_roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(40) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    description VARCHAR(255) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tactical_formations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(40) NOT NULL UNIQUE,
    slug VARCHAR(60) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    player_count TINYINT UNSIGNED NOT NULL DEFAULT 11,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tactical_formation_slots (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tactical_formation_id BIGINT UNSIGNED NOT NULL,
    slot_key VARCHAR(40) NOT NULL,
    position_code VARCHAR(40) NOT NULL,
    label VARCHAR(80) NOT NULL,
    position_group VARCHAR(40) NOT NULL,
    horizontal_position DECIMAL(5,2) NOT NULL,
    vertical_position DECIMAL(5,2) NOT NULL,
    display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_formation_slot (tactical_formation_id, slot_key),
    CONSTRAINT fk_slots_formation FOREIGN KEY (tactical_formation_id) REFERENCES tactical_formations (id),
    INDEX idx_slots_order (tactical_formation_id, display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE teams (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    championship_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(180) NOT NULL,
    short_name VARCHAR(80) NOT NULL,
    slug VARCHAR(190) NOT NULL,
    abbreviation VARCHAR(8) NOT NULL,
    description TEXT NULL,
    city VARCHAR(120) NULL,
    state VARCHAR(80) NULL,
    primary_color CHAR(7) NOT NULL DEFAULT '#123C32',
    secondary_color CHAR(7) NOT NULL DEFAULT '#D9A441',
    shield_path VARCHAR(255) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    default_tactical_formation_id BIGINT UNSIGNED NULL,
    default_formation_changed_at DATETIME NULL,
    default_formation_changed_by BIGINT UNSIGNED NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME NULL,
    UNIQUE KEY uq_team_championship_slug (championship_id, slug),
    UNIQUE KEY uq_team_championship_name (championship_id, name),
    CONSTRAINT fk_teams_championship FOREIGN KEY (championship_id) REFERENCES championships (id),
    CONSTRAINT fk_teams_formation FOREIGN KEY (default_tactical_formation_id) REFERENCES tactical_formations (id) ON DELETE SET NULL,
    CONSTRAINT fk_teams_formation_user FOREIGN KEY (default_formation_changed_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_teams_creator FOREIGN KEY (created_by) REFERENCES users (id),
    INDEX idx_teams_status (status),
    INDEX idx_teams_championship (championship_id),
    INDEX idx_teams_city (city)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE team_user_assignments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    assignment_type VARCHAR(30) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    starts_at DATE NOT NULL,
    ends_at DATE NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_team_user_assignment_history (team_id, user_id, assignment_type, starts_at),
    CONSTRAINT fk_team_assignment_team FOREIGN KEY (team_id) REFERENCES teams (id),
    CONSTRAINT fk_team_assignment_user FOREIGN KEY (user_id) REFERENCES users (id),
    CONSTRAINT fk_team_assignment_creator FOREIGN KEY (created_by) REFERENCES users (id),
    INDEX idx_team_assignment_user (user_id, status),
    INDEX idx_team_assignment_team (team_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE team_staff (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id BIGINT UNSIGNED NOT NULL,
    staff_role_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    full_name VARCHAR(180) NOT NULL,
    display_name VARCHAR(120) NULL,
    email VARCHAR(180) NULL,
    phone VARCHAR(40) NULL,
    document_number VARCHAR(80) NULL,
    photo_path VARCHAR(255) NULL,
    registration_number VARCHAR(80) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    starts_at DATE NULL,
    ends_at DATE NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME NULL,
    UNIQUE KEY uq_team_staff_name (team_id, full_name),
    CONSTRAINT fk_team_staff_team FOREIGN KEY (team_id) REFERENCES teams (id),
    CONSTRAINT fk_team_staff_role FOREIGN KEY (staff_role_id) REFERENCES staff_roles (id),
    CONSTRAINT fk_team_staff_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL,
    INDEX idx_team_staff_status (team_id, status),
    INDEX idx_team_staff_role (staff_role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
