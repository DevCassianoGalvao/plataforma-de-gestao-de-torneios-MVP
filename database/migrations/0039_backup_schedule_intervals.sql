ALTER TABLE application_backup_settings
    ADD COLUMN schedule_interval_days TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER schedule_time;
