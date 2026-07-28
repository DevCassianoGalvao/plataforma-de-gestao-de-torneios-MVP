ALTER TABLE match_lineups ADD COLUMN is_captain TINYINT(1) NOT NULL DEFAULT 0 AFTER lineup_role, ADD COLUMN is_goalkeeper TINYINT(1) NOT NULL DEFAULT 0 AFTER is_captain;
ALTER TABLE rounds ADD COLUMN status VARCHAR(30) NOT NULL DEFAULT 'draft' AFTER round_order;
ALTER TABLE matches ADD COLUMN field_name VARCHAR(120) NULL AFTER venue_id, ADD COLUMN report_pdf_path VARCHAR(255) NULL AFTER homologated_at;
