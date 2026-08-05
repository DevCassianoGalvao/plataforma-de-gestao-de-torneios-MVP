ALTER TABLE match_operation_rectifications
    ADD COLUMN requested_field VARCHAR(80) NOT NULL DEFAULT 'operacao' AFTER reason,
    ADD COLUMN critical TINYINT(1) NOT NULL DEFAULT 0 AFTER requested_field,
    ADD COLUMN correction_by BIGINT UNSIGNED NULL AFTER decided_at,
    ADD COLUMN correction_started_at DATETIME NULL AFTER correction_by,
    ADD COLUMN correction_completed_at DATETIME NULL AFTER correction_started_at,
    ADD COLUMN reapproved_by BIGINT UNSIGNED NULL AFTER correction_completed_at,
    ADD COLUMN reapproved_at DATETIME NULL AFTER reapproved_by,
    ADD CONSTRAINT fk_match_rectification_correction_user FOREIGN KEY (correction_by) REFERENCES users (id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_match_rectification_reapproval_user FOREIGN KEY (reapproved_by) REFERENCES users (id) ON DELETE SET NULL;

CREATE TABLE match_rectification_changes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rectification_id BIGINT UNSIGNED NOT NULL,
    match_id BIGINT UNSIGNED NOT NULL,
    entity_type VARCHAR(40) NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,
    field_name VARCHAR(80) NOT NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    reason VARCHAR(1000) NULL,
    changed_by BIGINT UNSIGNED NOT NULL,
    changed_at DATETIME NOT NULL,
    CONSTRAINT fk_rectification_change_rectification FOREIGN KEY (rectification_id) REFERENCES match_operation_rectifications (id) ON DELETE CASCADE,
    CONSTRAINT fk_rectification_change_match FOREIGN KEY (match_id) REFERENCES matches (id),
    CONSTRAINT fk_rectification_change_user FOREIGN KEY (changed_by) REFERENCES users (id),
    INDEX idx_rectification_changes_lookup (rectification_id, changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE championship_rectification_settings (
    championship_id BIGINT UNSIGNED PRIMARY KEY,
    require_second_approval TINYINT(1) NOT NULL DEFAULT 0,
    updated_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_rectification_settings_championship FOREIGN KEY (championship_id) REFERENCES championships (id) ON DELETE CASCADE,
    CONSTRAINT fk_rectification_settings_user FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (`key`, name, description, module, created_at, updated_at) VALUES
    ('match_operation.rectify.edit', 'Editar retificação autorizada', 'Registra alterações pontuais em uma retificação aprovada.', 'partidas', NOW(), NOW()),
    ('match_operation.rectify.complete', 'Concluir correção de partida', 'Envia uma retificação corrigida para nova aprovação.', 'partidas', NOW(), NOW()),
    ('match_operation.rectify.approve', 'Aprovar retificação corrigida', 'Aprova novamente uma partida após correção autorizada.', 'partidas', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), module = VALUES(module), updated_at = VALUES(updated_at);

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, NOW() FROM roles r INNER JOIN permissions p ON p.`key` IN ('match_operation.rectify.edit','match_operation.rectify.complete','match_operation.rectify.approve') WHERE r.`key` = 'administrator';
