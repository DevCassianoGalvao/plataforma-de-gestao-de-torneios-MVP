CREATE TABLE positions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(40) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    position_group VARCHAR(40) NOT NULL,
    display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_positions_status (status, display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE athletes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id BIGINT UNSIGNED NOT NULL,
    full_name VARCHAR(180) NOT NULL,
    sporting_name VARCHAR(120) NULL,
    photo_path VARCHAR(255) NULL,
    birth_date DATE NOT NULL,
    gender VARCHAR(20) NULL,
    primary_position_id BIGINT UNSIGNED NOT NULL,
    preferred_number TINYINT UNSIGNED NULL,
    dominant_foot VARCHAR(20) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    private_notes TEXT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_athletes_team FOREIGN KEY (team_id) REFERENCES teams (id),
    CONSTRAINT fk_athletes_primary_position FOREIGN KEY (primary_position_id) REFERENCES positions (id),
    CONSTRAINT fk_athletes_creator FOREIGN KEY (created_by) REFERENCES users (id),
    INDEX idx_athletes_team_status (team_id, status),
    INDEX idx_athletes_birth_date (birth_date),
    INDEX idx_athletes_position (primary_position_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE athlete_secondary_positions (
    athlete_id BIGINT UNSIGNED NOT NULL,
    position_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (athlete_id, position_id),
    CONSTRAINT fk_athlete_secondary_athlete FOREIGN KEY (athlete_id) REFERENCES athletes (id) ON DELETE CASCADE,
    CONSTRAINT fk_athlete_secondary_position FOREIGN KEY (position_id) REFERENCES positions (id),
    INDEX idx_secondary_position (position_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE legal_guardians (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(180) NOT NULL,
    phone VARCHAR(40) NOT NULL,
    email VARCHAR(180) NULL,
    document_ciphertext TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME NULL,
    INDEX idx_guardians_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE athlete_guardians (
    athlete_id BIGINT UNSIGNED NOT NULL,
    guardian_id BIGINT UNSIGNED NOT NULL,
    relationship VARCHAR(80) NOT NULL,
    authorization_status VARCHAR(20) NOT NULL DEFAULT 'pending',
    authorization_note TEXT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (athlete_id, guardian_id),
    CONSTRAINT fk_athlete_guardians_athlete FOREIGN KEY (athlete_id) REFERENCES athletes (id) ON DELETE CASCADE,
    CONSTRAINT fk_athlete_guardians_guardian FOREIGN KEY (guardian_id) REFERENCES legal_guardians (id) ON DELETE CASCADE,
    INDEX idx_guardian_links_guardian (guardian_id, authorization_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE athlete_document_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    description VARCHAR(255) NULL,
    guardian_applicable TINYINT(1) NOT NULL DEFAULT 0,
    required_for_minor TINYINT(1) NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_document_types_active (active, display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE athlete_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    athlete_id BIGINT UNSIGNED NOT NULL,
    guardian_id BIGINT UNSIGNED NULL,
    document_type_id BIGINT UNSIGNED NOT NULL,
    storage_path VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    size_bytes BIGINT UNSIGNED NOT NULL,
    expires_at DATE NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    observation TEXT NULL,
    rejection_reason TEXT NULL,
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_athlete_documents_athlete FOREIGN KEY (athlete_id) REFERENCES athletes (id),
    CONSTRAINT fk_athlete_documents_guardian FOREIGN KEY (guardian_id) REFERENCES legal_guardians (id) ON DELETE SET NULL,
    CONSTRAINT fk_athlete_documents_type FOREIGN KEY (document_type_id) REFERENCES athlete_document_types (id),
    CONSTRAINT fk_athlete_documents_reviewer FOREIGN KEY (reviewed_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_athlete_documents_creator FOREIGN KEY (created_by) REFERENCES users (id),
    INDEX idx_athlete_documents_athlete (athlete_id, status),
    INDEX idx_athlete_documents_type (document_type_id),
    INDEX idx_athlete_documents_expiry (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
