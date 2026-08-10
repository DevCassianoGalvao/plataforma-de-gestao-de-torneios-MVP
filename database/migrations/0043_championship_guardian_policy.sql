ALTER TABLE championships
    ADD COLUMN requires_guardian TINYINT(1) NOT NULL DEFAULT 0 AFTER category_id;
