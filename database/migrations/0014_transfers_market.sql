CREATE TABLE regulation_transfer_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    regulation_id BIGINT UNSIGNED NOT NULL,
    window_starts_at DATE NULL,
    window_ends_at DATE NULL,
    maximum_movements INT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_transfer_settings_regulation (regulation_id),
    CONSTRAINT fk_transfer_settings_regulation FOREIGN KEY (regulation_id) REFERENCES regulations (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE transfer_movements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    championship_id BIGINT UNSIGNED NOT NULL,
    athlete_id BIGINT UNSIGNED NOT NULL,
    previous_team_id BIGINT UNSIGNED NULL,
    new_team_id BIGINT UNSIGNED NULL,
    type VARCHAR(30) NOT NULL,
    movement_date DATE NOT NULL,
    public_observation TEXT NULL,
    internal_notes TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    published_at DATETIME NULL,
    author_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME NULL,
    UNIQUE KEY uq_transfer_movement (championship_id, athlete_id, movement_date, type),
    INDEX idx_transfer_filters (championship_id, status, type, movement_date, deleted_at),
    CONSTRAINT fk_transfer_championship FOREIGN KEY (championship_id) REFERENCES championships (id),
    CONSTRAINT fk_transfer_athlete FOREIGN KEY (athlete_id) REFERENCES athletes (id),
    CONSTRAINT fk_transfer_previous_team FOREIGN KEY (previous_team_id) REFERENCES teams (id) ON DELETE SET NULL,
    CONSTRAINT fk_transfer_new_team FOREIGN KEY (new_team_id) REFERENCES teams (id) ON DELETE SET NULL,
    CONSTRAINT fk_transfer_author FOREIGN KEY (author_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE transfer_movement_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transfer_movement_id BIGINT UNSIGNED NOT NULL,
    from_status VARCHAR(20) NULL,
    to_status VARCHAR(20) NOT NULL,
    action VARCHAR(40) NOT NULL,
    reason VARCHAR(500) NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_transfer_history_movement (transfer_movement_id, created_at),
    CONSTRAINT fk_transfer_history_movement FOREIGN KEY (transfer_movement_id) REFERENCES transfer_movements (id) ON DELETE CASCADE,
    CONSTRAINT fk_transfer_history_user FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
