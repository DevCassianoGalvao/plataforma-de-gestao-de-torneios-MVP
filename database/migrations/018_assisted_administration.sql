ALTER TABLE teams
    ADD COLUMN acronym VARCHAR(12) NULL AFTER short_name,
    ADD COLUMN slug VARCHAR(180) NULL AFTER acronym,
    ADD COLUMN primary_color CHAR(7) NULL AFTER logo_path,
    ADD COLUMN secondary_color CHAR(7) NULL AFTER primary_color,
    ADD COLUMN city VARCHAR(120) NULL AFTER secondary_color,
    ADD COLUMN contact_name VARCHAR(160) NULL AFTER city,
    ADD COLUMN contact_phone VARCHAR(40) NULL AFTER contact_name,
    ADD COLUMN contact_email VARCHAR(190) NULL AFTER contact_phone;

ALTER TABLE team_tournament_entries
    ADD COLUMN category_id BIGINT UNSIGNED NULL AFTER team_id,
    ADD INDEX team_entry_category(category_id),
    ADD CONSTRAINT fk_team_entry_category FOREIGN KEY (category_id) REFERENCES categories(id);

CREATE UNIQUE INDEX teams_project_slug ON teams(project_id, slug);
