-- Repair databases that registered the portal migration before partner_type was available.
-- The dynamic statement keeps clean installations and legacy databases on the same schema.
SET @partner_type_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'championship_sponsors'
      AND column_name = 'partner_type'
);
SET @partner_type_sql := IF(
    @partner_type_exists = 0,
    'ALTER TABLE championship_sponsors ADD COLUMN partner_type VARCHAR(20) NOT NULL DEFAULT ''sponsor'' AFTER championship_id',
    'SELECT 1'
);
PREPARE partner_type_statement FROM @partner_type_sql;
EXECUTE partner_type_statement;
DEALLOCATE PREPARE partner_type_statement;

SET @partner_type_index_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'championship_sponsors'
      AND index_name = 'idx_sponsors_type'
);
SET @partner_type_index_sql := IF(
    @partner_type_index_exists = 0,
    'ALTER TABLE championship_sponsors ADD INDEX idx_sponsors_type (championship_id, partner_type, status, display_order)',
    'SELECT 1'
);
PREPARE partner_type_index_statement FROM @partner_type_index_sql;
EXECUTE partner_type_index_statement;
DEALLOCATE PREPARE partner_type_index_statement;
