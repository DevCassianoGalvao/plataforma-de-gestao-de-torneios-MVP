CREATE TABLE retention_policies (
    scope_key VARCHAR(60) PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    retention_days INT UNSIGNED NULL,
    allow_archive TINYINT(1) NOT NULL DEFAULT 1,
    allow_restore TINYINT(1) NOT NULL DEFAULT 1,
    allow_soft_delete TINYINT(1) NOT NULL DEFAULT 1,
    allow_hard_delete TINYINT(1) NOT NULL DEFAULT 0,
    protected TINYINT(1) NOT NULL DEFAULT 0,
    updated_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_retention_policy_user FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE retention_actions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    scope_key VARCHAR(60) NOT NULL,
    entity_type VARCHAR(60) NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,
    action VARCHAR(30) NOT NULL,
    previous_status VARCHAR(40) NULL,
    new_status VARCHAR(40) NULL,
    reason VARCHAR(1000) NOT NULL,
    metadata TEXT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_retention_action_user FOREIGN KEY (user_id) REFERENCES users (id),
    INDEX idx_retention_actions_entity (entity_type, entity_id, created_at),
    INDEX idx_retention_actions_scope (scope_key, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO retention_policies (scope_key, name, retention_days, allow_archive, allow_restore, allow_soft_delete, allow_hard_delete, protected, created_at, updated_at) VALUES
    ('operational_drafts', 'Rascunhos operacionais', 365, 1, 1, 1, 0, 0, NOW(), NOW()),
    ('sports_history', 'Histórico esportivo', NULL, 1, 1, 1, 0, 1, NOW(), NOW()),
    ('official_documents', 'Documentos oficiais', NULL, 1, 0, 1, 0, 1, NOW(), NOW()),
    ('audit_data', 'Logs e auditoria', NULL, 1, 0, 1, 0, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name), retention_days = VALUES(retention_days), updated_at = VALUES(updated_at);

INSERT INTO permissions (`key`, name, description, module, created_at, updated_at) VALUES
    ('retention.view', 'Consultar retenção', 'Consulta políticas de retenção e histórico de ações.', 'retencao', NOW(), NOW()),
    ('retention.manage', 'Configurar retenção', 'Altera prazos e regras de arquivamento.', 'retencao', NOW(), NOW()),
    ('retention.archive', 'Arquivar registros', 'Arquiva registros não protegidos.', 'retencao', NOW(), NOW()),
    ('retention.restore', 'Restaurar registros', 'Restaura registros arquivados quando permitido.', 'retencao', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), module = VALUES(module), updated_at = VALUES(updated_at);

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, NOW() FROM roles r INNER JOIN permissions p ON p.`key` LIKE 'retention.%' WHERE r.`key` = 'administrator';
