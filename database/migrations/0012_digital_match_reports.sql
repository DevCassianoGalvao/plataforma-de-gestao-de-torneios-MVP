CREATE TABLE match_reports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    match_id BIGINT UNSIGNED NOT NULL UNIQUE,
    championship_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'available',
    current_version_id BIGINT UNSIGNED NULL,
    homologated_by BIGINT UNSIGNED NULL,
    homologated_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_match_reports_match FOREIGN KEY (match_id) REFERENCES matches (id),
    CONSTRAINT fk_match_reports_championship FOREIGN KEY (championship_id) REFERENCES championships (id),
    CONSTRAINT fk_match_reports_homologator FOREIGN KEY (homologated_by) REFERENCES users (id) ON DELETE SET NULL,
    INDEX idx_match_reports_championship (championship_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE match_report_versions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    match_report_id BIGINT UNSIGNED NOT NULL,
    version_number SMALLINT UNSIGNED NOT NULL,
    verification_code CHAR(32) NOT NULL,
    content_hash CHAR(64) NOT NULL,
    storage_path VARCHAR(500) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL DEFAULT 'application/pdf',
    file_size INT UNSIGNED NOT NULL,
    html_snapshot MEDIUMTEXT NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    supersedes_version_id BIGINT UNSIGNED NULL,
    UNIQUE KEY uq_match_report_version (match_report_id, version_number),
    UNIQUE KEY uq_match_report_verification (verification_code),
    CONSTRAINT fk_match_report_version_report FOREIGN KEY (match_report_id) REFERENCES match_reports (id) ON DELETE CASCADE,
    CONSTRAINT fk_match_report_version_creator FOREIGN KEY (created_by) REFERENCES users (id),
    CONSTRAINT fk_match_report_version_previous FOREIGN KEY (supersedes_version_id) REFERENCES match_report_versions (id) ON DELETE SET NULL,
    INDEX idx_match_report_version_hash (match_report_id, content_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE match_reports
    ADD CONSTRAINT fk_match_reports_current_version FOREIGN KEY (current_version_id) REFERENCES match_report_versions (id) ON DELETE SET NULL;
