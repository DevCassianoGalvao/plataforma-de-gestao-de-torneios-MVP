CREATE TABLE seasons (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    year SMALLINT UNSIGNED NOT NULL,
    starts_at DATE NULL,
    ends_at DATE NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME NULL,
    UNIQUE KEY uq_seasons_name_year (name, year),
    INDEX idx_seasons_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    slug VARCHAR(180) NOT NULL UNIQUE,
    description TEXT NULL,
    minimum_age TINYINT UNSIGNED NULL,
    maximum_age TINYINT UNSIGNED NULL,
    gender_rule VARCHAR(40) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME NULL,
    INDEX idx_categories_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE championships (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(180) NOT NULL,
    short_name VARCHAR(80) NOT NULL,
    slug VARCHAR(190) NOT NULL UNIQUE,
    description TEXT NULL,
    season_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    starts_at DATE NULL,
    ends_at DATE NULL,
    registration_starts_at DATE NULL,
    registration_ends_at DATE NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    visibility VARCHAR(20) NOT NULL DEFAULT 'private',
    default_theme VARCHAR(20) NOT NULL DEFAULT 'light',
    primary_color CHAR(7) NOT NULL DEFAULT '#123C32',
    secondary_color CHAR(7) NOT NULL DEFAULT '#245C4A',
    accent_color CHAR(7) NOT NULL DEFAULT '#D9A441',
    logo_path VARCHAR(255) NULL,
    logo_light_path VARCHAR(255) NULL,
    logo_dark_path VARCHAR(255) NULL,
    banner_path VARCHAR(255) NULL,
    favicon_path VARCHAR(255) NULL,
    social_image_path VARCHAR(255) NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_championships_season FOREIGN KEY (season_id) REFERENCES seasons (id),
    CONSTRAINT fk_championships_category FOREIGN KEY (category_id) REFERENCES categories (id),
    CONSTRAINT fk_championships_creator FOREIGN KEY (created_by) REFERENCES users (id),
    INDEX idx_championships_status (status),
    INDEX idx_championships_season (season_id),
    INDEX idx_championships_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE championship_user_assignments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    championship_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    assignment_type VARCHAR(30) NOT NULL,
    created_at DATETIME NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    UNIQUE KEY uq_championship_user_assignment (championship_id, user_id, assignment_type),
    CONSTRAINT fk_assignment_championship FOREIGN KEY (championship_id) REFERENCES championships (id),
    CONSTRAINT fk_assignment_user FOREIGN KEY (user_id) REFERENCES users (id),
    CONSTRAINT fk_assignment_creator FOREIGN KEY (created_by) REFERENCES users (id),
    INDEX idx_assignment_user (user_id),
    INDEX idx_assignment_type (assignment_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE regulations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    championship_id BIGINT UNSIGNED NOT NULL,
    version_number INT UNSIGNED NOT NULL,
    name VARCHAR(180) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    effective_from DATE NULL,
    published_at DATETIME NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_regulation_version (championship_id, version_number),
    CONSTRAINT fk_regulations_championship FOREIGN KEY (championship_id) REFERENCES championships (id),
    CONSTRAINT fk_regulations_creator FOREIGN KEY (created_by) REFERENCES users (id),
    INDEX idx_regulations_status (championship_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE regulation_format_settings (
    regulation_id BIGINT UNSIGNED PRIMARY KEY,
    group_count SMALLINT UNSIGNED NOT NULL DEFAULT 2,
    teams_per_group SMALLINT UNSIGNED NOT NULL DEFAULT 5,
    qualified_per_group SMALLINT UNSIGNED NOT NULL DEFAULT 4,
    group_rounds VARCHAR(20) NOT NULL DEFAULT 'single',
    home_and_away TINYINT(1) NOT NULL DEFAULT 0,
    knockout_starts_at VARCHAR(30) NOT NULL DEFAULT 'quarterfinals',
    third_place_match TINYINT(1) NOT NULL DEFAULT 0,
    final_format VARCHAR(20) NOT NULL DEFAULT 'single_match',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_format_regulation FOREIGN KEY (regulation_id) REFERENCES regulations (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE regulation_points_settings (
    regulation_id BIGINT UNSIGNED PRIMARY KEY,
    points_win SMALLINT NOT NULL DEFAULT 3,
    points_draw SMALLINT NOT NULL DEFAULT 1,
    points_loss SMALLINT NOT NULL DEFAULT 0,
    wo_winner_goals SMALLINT UNSIGNED NOT NULL DEFAULT 3,
    wo_loser_goals SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_points_regulation FOREIGN KEY (regulation_id) REFERENCES regulations (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE regulation_tiebreakers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    regulation_id BIGINT UNSIGNED NOT NULL,
    criterion VARCHAR(40) NOT NULL,
    priority SMALLINT UNSIGNED NOT NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uq_tiebreaker_criterion (regulation_id, criterion),
    UNIQUE KEY uq_tiebreaker_priority (regulation_id, priority),
    CONSTRAINT fk_tiebreakers_regulation FOREIGN KEY (regulation_id) REFERENCES regulations (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE regulation_discipline_settings (
    regulation_id BIGINT UNSIGNED PRIMARY KEY,
    yellow_cards_for_suspension SMALLINT UNSIGNED NOT NULL DEFAULT 3,
    yellow_suspension_matches SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    red_card_automatic_suspension TINYINT(1) NOT NULL DEFAULT 1,
    red_card_suspension_matches SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    reset_cards_enabled TINYINT(1) NOT NULL DEFAULT 0,
    reset_cards_stage VARCHAR(40) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_discipline_regulation FOREIGN KEY (regulation_id) REFERENCES regulations (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE regulation_match_settings (
    regulation_id BIGINT UNSIGNED PRIMARY KEY,
    regular_time_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 40,
    halftime_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 10,
    substitutions_allowed SMALLINT UNSIGNED NOT NULL DEFAULT 5,
    substitution_windows SMALLINT UNSIGNED NOT NULL DEFAULT 3,
    extra_time_enabled TINYINT(1) NOT NULL DEFAULT 0,
    extra_time_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 10,
    penalty_shootout_enabled TINYINT(1) NOT NULL DEFAULT 1,
    direct_penalties TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_match_regulation FOREIGN KEY (regulation_id) REFERENCES regulations (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE regulation_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    regulation_id BIGINT UNSIGNED NOT NULL,
    storage_path VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    version_label VARCHAR(80) NULL,
    visibility VARCHAR(20) NOT NULL DEFAULT 'private',
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_regulation_documents_regulation FOREIGN KEY (regulation_id) REFERENCES regulations (id),
    INDEX idx_regulation_documents_regulation (regulation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
