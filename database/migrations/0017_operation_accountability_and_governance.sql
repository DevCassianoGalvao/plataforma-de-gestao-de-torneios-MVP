-- Operational ownership, evidence, public partners and multi-organization governance.
-- This migration is additive: existing championships keep working without a project.
CREATE TABLE organizations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(180) NOT NULL,
    slug VARCHAR(180) NOT NULL,
    legal_name VARCHAR(255) NULL,
    document_reference VARCHAR(80) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME NULL,
    UNIQUE KEY uq_organizations_slug (slug),
    CONSTRAINT fk_organizations_user FOREIGN KEY (created_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE projects (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(180) NOT NULL,
    slug VARCHAR(180) NOT NULL,
    description TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    starts_at DATE NULL,
    ends_at DATE NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME NULL,
    UNIQUE KEY uq_projects_organization_slug (organization_id, slug),
    INDEX idx_projects_status (organization_id, status, deleted_at),
    CONSTRAINT fk_projects_organization FOREIGN KEY (organization_id) REFERENCES organizations (id),
    CONSTRAINT fk_projects_user FOREIGN KEY (created_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE championships ADD COLUMN project_id BIGINT UNSIGNED NULL AFTER category_id;
ALTER TABLE championships ADD INDEX idx_championship_project (project_id);
ALTER TABLE championships ADD CONSTRAINT fk_championship_project FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE SET NULL;
ALTER TABLE transfer_movements ADD COLUMN official_applied_at DATETIME NULL AFTER published_at;
ALTER TABLE transfer_movements ADD COLUMN official_applied_by BIGINT UNSIGNED NULL AFTER official_applied_at;
ALTER TABLE transfer_movements ADD CONSTRAINT fk_transfer_official_applied_by FOREIGN KEY (official_applied_by) REFERENCES users (id) ON DELETE SET NULL;

CREATE TABLE match_media (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    match_id BIGINT UNSIGNED NOT NULL,
    championship_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    caption VARCHAR(500) NULL,
    storage_path VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    visibility VARCHAR(20) NOT NULL DEFAULT 'accountability',
    status VARCHAR(20) NOT NULL DEFAULT 'approved',
    captured_at DATETIME NULL,
    uploaded_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME NULL,
    INDEX idx_match_media_match (match_id, status, deleted_at),
    INDEX idx_match_media_championship (championship_id, visibility, status, deleted_at),
    CONSTRAINT fk_match_media_match FOREIGN KEY (match_id) REFERENCES matches (id),
    CONSTRAINT fk_match_media_championship FOREIGN KEY (championship_id) REFERENCES championships (id),
    CONSTRAINT fk_match_media_user FOREIGN KEY (uploaded_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE championship_sponsors (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    championship_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(180) NOT NULL,
    website_url VARCHAR(500) NULL,
    logo_path VARCHAR(255) NULL,
    display_order INT UNSIGNED NOT NULL DEFAULT 1,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME NULL,
    INDEX idx_sponsors_public (championship_id, status, display_order, deleted_at),
    CONSTRAINT fk_sponsors_championship FOREIGN KEY (championship_id) REFERENCES championships (id),
    CONSTRAINT fk_sponsors_user FOREIGN KEY (created_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE regulation_knockout_pairings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    regulation_id BIGINT UNSIGNED NOT NULL,
    stage VARCHAR(30) NOT NULL,
    tie_number INT UNSIGNED NOT NULL,
    home_source VARCHAR(30) NOT NULL,
    away_source VARCHAR(30) NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_regulation_pairing (regulation_id, stage, tie_number),
    CONSTRAINT fk_regulation_pairing_regulation FOREIGN KEY (regulation_id) REFERENCES regulations (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO regulation_knockout_pairings (regulation_id, stage, tie_number, home_source, away_source, created_at, updated_at)
SELECT r.id, p.stage, p.tie_number, p.home_source, p.away_source, NOW(), NOW()
FROM regulations r
CROSS JOIN (
    SELECT 'quarterfinals' AS stage, 1 AS tie_number, 'A1' AS home_source, 'B4' AS away_source
    UNION ALL SELECT 'quarterfinals', 2, 'B1', 'A4'
    UNION ALL SELECT 'quarterfinals', 3, 'A2', 'B3'
    UNION ALL SELECT 'quarterfinals', 4, 'B2', 'A3'
    UNION ALL SELECT 'semifinals', 1, 'QF1', 'QF3'
    UNION ALL SELECT 'semifinals', 2, 'QF2', 'QF4'
    UNION ALL SELECT 'final', 1, 'SF1', 'SF2'
) p
WHERE NOT EXISTS (SELECT 1 FROM regulation_knockout_pairings x WHERE x.regulation_id = r.id AND x.stage = p.stage AND x.tie_number = p.tie_number);

CREATE TABLE accountability_export_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    championship_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    export_kind VARCHAR(50) NOT NULL,
    file_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    INDEX idx_accountability_export (championship_id, created_at),
    CONSTRAINT fk_accountability_export_championship FOREIGN KEY (championship_id) REFERENCES championships (id),
    CONSTRAINT fk_accountability_export_user FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
