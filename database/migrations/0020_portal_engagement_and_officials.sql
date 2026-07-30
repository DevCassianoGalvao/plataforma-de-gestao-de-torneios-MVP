-- Dados editoriais e operacionais que podem ser publicados no portal sem expor dados privados.
ALTER TABLE championship_sponsors ADD COLUMN partner_type VARCHAR(20) NOT NULL DEFAULT 'sponsor' AFTER championship_id;
ALTER TABLE championship_sponsors ADD INDEX idx_sponsors_type (championship_id, partner_type, status, display_order);

CREATE TABLE championship_officials (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    championship_id BIGINT UNSIGNED NOT NULL,
    full_name VARCHAR(180) NOT NULL,
    public_name VARCHAR(180) NULL,
    role VARCHAR(40) NOT NULL DEFAULT 'referee',
    photo_path VARCHAR(255) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME NULL,
    INDEX idx_championship_officials_public (championship_id, status, deleted_at, full_name),
    CONSTRAINT fk_championship_officials_championship FOREIGN KEY (championship_id) REFERENCES championships (id),
    CONSTRAINT fk_championship_officials_user FOREIGN KEY (created_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE public_contact_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    championship_id BIGINT UNSIGNED NULL,
    name VARCHAR(160) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(40) NULL,
    subject VARCHAR(40) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'new',
    handled_by BIGINT UNSIGNED NULL,
    handled_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_public_contacts_status (status, created_at),
    INDEX idx_public_contacts_championship (championship_id, created_at),
    CONSTRAINT fk_public_contacts_championship FOREIGN KEY (championship_id) REFERENCES championships (id) ON DELETE SET NULL,
    CONSTRAINT fk_public_contacts_handler FOREIGN KEY (handled_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
