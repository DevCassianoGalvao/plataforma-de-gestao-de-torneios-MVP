CREATE TABLE regulation_roster_settings (
    regulation_id BIGINT UNSIGNED PRIMARY KEY,
    minimum_roster_size SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    maximum_roster_size SMALLINT UNSIGNED NOT NULL DEFAULT 25,
    minimum_goalkeepers SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    allow_multiple_team_registration TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_roster_settings_regulation FOREIGN KEY (regulation_id) REFERENCES regulations (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE regulation_required_documents (
    regulation_id BIGINT UNSIGNED NOT NULL,
    document_type_id BIGINT UNSIGNED NOT NULL,
    required_for_minor TINYINT(1) NOT NULL DEFAULT 0,
    display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (regulation_id, document_type_id),
    CONSTRAINT fk_required_documents_regulation FOREIGN KEY (regulation_id) REFERENCES regulations (id) ON DELETE CASCADE,
    CONSTRAINT fk_required_documents_type FOREIGN KEY (document_type_id) REFERENCES athlete_document_types (id),
    INDEX idx_required_documents_type (document_type_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE athlete_registrations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    championship_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NOT NULL,
    athlete_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    requested_number TINYINT UNSIGNED NULL,
    status VARCHAR(24) NOT NULL DEFAULT 'draft',
    submitted_at DATETIME NULL,
    pending_issues TEXT NULL,
    rejection_reason TEXT NULL,
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    decided_at DATETIME NULL,
    observations TEXT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_registrations_championship FOREIGN KEY (championship_id) REFERENCES championships (id),
    CONSTRAINT fk_registrations_team FOREIGN KEY (team_id) REFERENCES teams (id),
    CONSTRAINT fk_registrations_athlete FOREIGN KEY (athlete_id) REFERENCES athletes (id),
    CONSTRAINT fk_registrations_category FOREIGN KEY (category_id) REFERENCES categories (id),
    CONSTRAINT fk_registrations_creator FOREIGN KEY (created_by) REFERENCES users (id),
    CONSTRAINT fk_registrations_updater FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_registrations_reviewer FOREIGN KEY (reviewed_by) REFERENCES users (id) ON DELETE SET NULL,
    UNIQUE KEY uq_registration_team_athlete (championship_id, team_id, athlete_id),
    INDEX idx_registrations_championship_status (championship_id, status),
    INDEX idx_registrations_team_status (team_id, status),
    INDEX idx_registrations_athlete (athlete_id, championship_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE athlete_registration_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    registration_id BIGINT UNSIGNED NOT NULL,
    from_status VARCHAR(24) NULL,
    to_status VARCHAR(24) NOT NULL,
    action VARCHAR(40) NOT NULL,
    notes TEXT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_registration_history_registration FOREIGN KEY (registration_id) REFERENCES athlete_registrations (id) ON DELETE CASCADE,
    CONSTRAINT fk_registration_history_user FOREIGN KEY (user_id) REFERENCES users (id),
    INDEX idx_registration_history_registration (registration_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
