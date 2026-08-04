CREATE TABLE application_backup_settings (
    id TINYINT UNSIGNED PRIMARY KEY,
    provider VARCHAR(30) NOT NULL DEFAULT 'local',
    google_drive_folder_link VARCHAR(1000) NULL,
    google_drive_folder_id VARCHAR(255) NULL,
    schedule_enabled TINYINT(1) NOT NULL DEFAULT 0,
    schedule_time CHAR(5) NOT NULL DEFAULT '03:00',
    updated_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_backup_settings_updater FOREIGN KEY (updated_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO application_backup_settings (id, provider, google_drive_folder_link, google_drive_folder_id, schedule_enabled, schedule_time, created_at, updated_at)
VALUES (1, 'local', NULL, NULL, 0, '03:00', NOW(), NOW());
