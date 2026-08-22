ALTER TABLE championships
    ADD COLUMN allow_underage_athletes TINYINT(1) NOT NULL DEFAULT 0 AFTER requires_guardian;
