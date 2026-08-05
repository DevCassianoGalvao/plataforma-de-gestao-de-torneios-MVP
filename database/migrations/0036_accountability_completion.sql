CREATE TABLE championship_accountability_settings (
    championship_id BIGINT UNSIGNED PRIMARY KEY,
    require_current_report TINYINT(1) NOT NULL DEFAULT 1,
    require_signed_report TINYINT(1) NOT NULL DEFAULT 0,
    require_approved_evidence TINYINT(1) NOT NULL DEFAULT 0,
    updated_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_accountability_settings_championship FOREIGN KEY (championship_id) REFERENCES championships (id) ON DELETE CASCADE,
    CONSTRAINT fk_accountability_settings_user FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE match_report_versions
    ADD COLUMN signed_storage_path VARCHAR(500) NULL AFTER storage_path,
    ADD COLUMN signed_original_name VARCHAR(255) NULL AFTER signed_storage_path,
    ADD COLUMN signed_mime_type VARCHAR(100) NULL AFTER signed_original_name,
    ADD COLUMN signed_file_size INT UNSIGNED NULL AFTER signed_mime_type,
    ADD COLUMN signed_hash CHAR(64) NULL AFTER signed_file_size,
    ADD COLUMN signed_uploaded_by BIGINT UNSIGNED NULL AFTER signed_hash,
    ADD COLUMN signed_uploaded_at DATETIME NULL AFTER signed_uploaded_by,
    ADD CONSTRAINT fk_match_report_version_signed_user FOREIGN KEY (signed_uploaded_by) REFERENCES users (id) ON DELETE SET NULL;

ALTER TABLE accountability_export_logs
    ADD COLUMN format VARCHAR(20) NOT NULL DEFAULT 'csv' AFTER export_kind,
    ADD COLUMN filters_json TEXT NULL AFTER file_count,
    ADD COLUMN match_ids TEXT NULL AFTER filters_json,
    ADD COLUMN file_name VARCHAR(255) NULL AFTER match_ids,
    ADD COLUMN file_hash CHAR(64) NULL AFTER file_name;

INSERT INTO permissions (`key`, name, description, module, created_at, updated_at) VALUES
    ('accountability.detail', 'Consultar detalhe da prestação', 'Consulta a composição oficial de uma partida.', 'prestacao', NOW(), NOW()),
    ('accountability.export_pdf', 'Exportar prestação em PDF', 'Gera relatório consolidado em PDF.', 'prestacao', NOW(), NOW()),
    ('accountability.export_xlsx', 'Exportar prestação em Excel', 'Gera relatório consolidado em XLSX.', 'prestacao', NOW(), NOW()),
    ('accountability.export_zip', 'Baixar pacote da prestação', 'Baixa pacote privado com dados e documentos autorizados.', 'prestacao', NOW(), NOW()),
    ('match_reports.signed_upload', 'Anexar súmula assinada', 'Vincula uma cópia assinada à versão atual da súmula.', 'sumulas', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), module = VALUES(module), updated_at = VALUES(updated_at);

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, NOW() FROM roles r INNER JOIN permissions p ON p.`key` IN ('accountability.detail','accountability.export_pdf','accountability.export_xlsx','accountability.export_zip','match_reports.signed_upload')
WHERE r.`key` = 'administrator';

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, NOW() FROM roles r INNER JOIN permissions p ON p.`key` IN ('accountability.detail','accountability.export_pdf','accountability.export_xlsx','accountability.export_zip','match_reports.signed_upload')
WHERE r.`key` = 'accountability';
