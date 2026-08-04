-- Checklist de evidencias por campeonato. Nenhum item e criado automaticamente.
CREATE TABLE championship_evidence_checklist_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    championship_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(180) NOT NULL,
    description VARCHAR(500) NULL,
    is_required TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    display_order INT UNSIGNED NOT NULL DEFAULT 1,
    expected_moment VARCHAR(30) NOT NULL DEFAULT 'after_match',
    allowed_mime_types VARCHAR(500) NOT NULL DEFAULT 'image/jpeg,image/png,image/webp,application/pdf',
    min_files SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    max_files SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    max_file_size_bytes INT UNSIGNED NOT NULL DEFAULT 10485760,
    notes_required TINYINT(1) NOT NULL DEFAULT 0,
    blocks_operation_start TINYINT(1) NOT NULL DEFAULT 0,
    blocks_approval_submission TINYINT(1) NOT NULL DEFAULT 0,
    blocks_document_completion TINYINT(1) NOT NULL DEFAULT 0,
    show_in_accountability TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    INDEX idx_evidence_checklist_championship (championship_id, is_active, display_order, deleted_at),
    CONSTRAINT fk_evidence_checklist_championship FOREIGN KEY (championship_id) REFERENCES championships (id),
    CONSTRAINT fk_evidence_checklist_created_by FOREIGN KEY (created_by) REFERENCES users (id),
    CONSTRAINT fk_evidence_checklist_deleted_by FOREIGN KEY (deleted_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE match_media
    ADD COLUMN checklist_item_id BIGINT UNSIGNED NULL AFTER championship_id,
    ADD COLUMN file_hash CHAR(64) NULL AFTER mime_type,
    ADD COLUMN review_status VARCHAR(20) NOT NULL DEFAULT 'approved' AFTER status,
    ADD COLUMN reviewed_by BIGINT UNSIGNED NULL AFTER review_status,
    ADD COLUMN reviewed_at DATETIME NULL AFTER reviewed_by,
    ADD COLUMN rejection_reason VARCHAR(1000) NULL AFTER reviewed_at,
    ADD COLUMN supersedes_media_id BIGINT UNSIGNED NULL AFTER rejection_reason,
    ADD COLUMN replaced_by_media_id BIGINT UNSIGNED NULL AFTER supersedes_media_id,
    ADD COLUMN removed_by BIGINT UNSIGNED NULL AFTER deleted_at,
    ADD COLUMN removed_reason VARCHAR(1000) NULL AFTER removed_by,
    ADD INDEX idx_match_media_checklist (match_id, checklist_item_id, review_status, deleted_at),
    ADD CONSTRAINT fk_match_media_checklist_item FOREIGN KEY (checklist_item_id) REFERENCES championship_evidence_checklist_items (id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_match_media_reviewed_by FOREIGN KEY (reviewed_by) REFERENCES users (id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_match_media_supersedes FOREIGN KEY (supersedes_media_id) REFERENCES match_media (id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_match_media_replaced_by FOREIGN KEY (replaced_by_media_id) REFERENCES match_media (id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_match_media_removed_by FOREIGN KEY (removed_by) REFERENCES users (id) ON DELETE SET NULL;

CREATE TABLE match_evidence_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    match_media_id BIGINT UNSIGNED NULL,
    match_id BIGINT UNSIGNED NOT NULL,
    checklist_item_id BIGINT UNSIGNED NULL,
    action VARCHAR(40) NOT NULL,
    details TEXT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_match_evidence_history_match (match_id, created_at),
    CONSTRAINT fk_match_evidence_history_media FOREIGN KEY (match_media_id) REFERENCES match_media (id) ON DELETE SET NULL,
    CONSTRAINT fk_match_evidence_history_match FOREIGN KEY (match_id) REFERENCES matches (id),
    CONSTRAINT fk_match_evidence_history_item FOREIGN KEY (checklist_item_id) REFERENCES championship_evidence_checklist_items (id) ON DELETE SET NULL,
    CONSTRAINT fk_match_evidence_history_user FOREIGN KEY (created_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE match_evidence_exceptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    match_id BIGINT UNSIGNED NOT NULL,
    checklist_item_id BIGINT UNSIGNED NULL,
    exception_type VARCHAR(30) NOT NULL,
    reason VARCHAR(1000) NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_match_evidence_exceptions_match (match_id, exception_type),
    CONSTRAINT fk_match_evidence_exception_match FOREIGN KEY (match_id) REFERENCES matches (id),
    CONSTRAINT fk_match_evidence_exception_item FOREIGN KEY (checklist_item_id) REFERENCES championship_evidence_checklist_items (id) ON DELETE SET NULL,
    CONSTRAINT fk_match_evidence_exception_user FOREIGN KEY (created_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (`key`, name, description, module, created_at, updated_at) VALUES
    ('evidence.checklist.manage', 'Gerenciar checklist de evidencias', 'Configura evidencias exigidas por campeonato.', 'evidencias', NOW(), NOW()),
    ('evidence.upload', 'Enviar evidencias', 'Envia arquivos da partida autorizada.', 'evidencias', NOW(), NOW()),
    ('evidence.remove', 'Remover evidencias', 'Remove ou substitui arquivos antes da aprovacao.', 'evidencias', NOW(), NOW()),
    ('evidence.review', 'Revisar evidencias', 'Consulta evidencias enviadas para revisao.', 'evidencias', NOW(), NOW()),
    ('evidence.approve', 'Aprovar evidencias', 'Aprova ou rejeita evidencias de partida.', 'evidencias', NOW(), NOW()),
    ('evidence.override', 'Autorizar excecao de evidencia', 'Libera bloqueio documental com justificativa.', 'evidencias', NOW(), NOW()),
    ('evidence.download', 'Baixar evidencias', 'Visualiza e baixa arquivos autorizados.', 'evidencias', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), module = VALUES(module), updated_at = VALUES(updated_at);

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, NOW() FROM roles r INNER JOIN permissions p ON p.`key` IN
('evidence.checklist.manage','evidence.upload','evidence.remove','evidence.review','evidence.approve','evidence.override','evidence.download')
WHERE r.`key` = 'administrator';
INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, NOW() FROM roles r INNER JOIN permissions p ON p.`key` IN ('evidence.upload','evidence.remove','evidence.download')
WHERE r.`key` = 'match_operator';
INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, NOW() FROM roles r INNER JOIN permissions p ON p.`key` = 'evidence.download'
WHERE r.`key` = 'accountability';
