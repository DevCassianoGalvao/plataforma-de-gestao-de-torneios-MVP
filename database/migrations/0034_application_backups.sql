CREATE TABLE application_backups (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    backup_key VARCHAR(120) NOT NULL UNIQUE,
    type VARCHAR(20) NOT NULL DEFAULT 'manual',
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    local_status VARCHAR(30) NOT NULL DEFAULT 'pending',
    validation_status VARCHAR(30) NOT NULL DEFAULT 'pending',
    remote_status VARCHAR(30) NOT NULL DEFAULT 'pending',
    local_path VARCHAR(1000) NULL,
    remote_provider VARCHAR(60) NULL,
    remote_id VARCHAR(500) NULL,
    remote_path VARCHAR(1000) NULL,
    size_bytes BIGINT UNSIGNED NULL,
    sha256 CHAR(64) NULL,
    duration_seconds INT UNSIGNED NULL,
    attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    error_message VARCHAR(1200) NULL,
    expires_at DATETIME NULL,
    started_at DATETIME NULL,
    completed_at DATETIME NULL,
    created_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_application_backups_status (status, created_at),
    CONSTRAINT fk_application_backups_creator FOREIGN KEY (created_by) REFERENCES users (id),
    CONSTRAINT fk_application_backups_deleter FOREIGN KEY (deleted_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (`key`, name, description, module, created_at, updated_at) VALUES
('backup.view', 'Visualizar backups', 'Consulta historico de backups.', 'backup', NOW(), NOW()),
('backup.run', 'Executar backup', 'Cria backup manual.', 'backup', NOW(), NOW()),
('backup.download', 'Baixar backup', 'Baixa arquivos de backup privados.', 'backup', NOW(), NOW()),
('backup.retry', 'Reenviar backup', 'Reenvia backup local ao destino remoto.', 'backup', NOW(), NOW()),
('backup.delete', 'Excluir backup', 'Exclui backup antigo de forma auditada.', 'backup', NOW(), NOW()),
('backup.restore', 'Restaurar backup', 'Autoriza procedimento de restauracao controlado.', 'backup', NOW(), NOW()),
('backup.configure', 'Configurar backup', 'Configura operacao de backup.', 'backup', NOW(), NOW())
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), updated_at=VALUES(updated_at);

INSERT INTO role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, NOW() FROM roles r INNER JOIN permissions p ON p.`key` LIKE 'backup.%' WHERE r.`key`='administrator'
ON DUPLICATE KEY UPDATE created_at=VALUES(created_at);
