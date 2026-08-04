-- Revisao e retificacao preservam o historico esportivo e documental ja gerado.
ALTER TABLE match_operations
    ADD COLUMN review_status VARCHAR(30) NOT NULL DEFAULT 'not_submitted' AFTER status,
    ADD COLUMN review_reason VARCHAR(1000) NULL AFTER review_status,
    ADD COLUMN reviewed_by BIGINT UNSIGNED NULL AFTER review_reason,
    ADD COLUMN reviewed_at DATETIME NULL AFTER reviewed_by,
    ADD CONSTRAINT fk_match_operations_reviewed_by FOREIGN KEY (reviewed_by) REFERENCES users (id);

UPDATE match_operations
SET review_status = CASE status
    WHEN 'awaiting_homologation' THEN 'awaiting_review'
    WHEN 'homologated' THEN 'approved'
    ELSE 'not_submitted'
END;

CREATE TABLE match_operation_rectifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    match_id BIGINT UNSIGNED NOT NULL,
    operation_id BIGINT UNSIGNED NOT NULL,
    requested_by BIGINT UNSIGNED NOT NULL,
    requested_at DATETIME NOT NULL,
    reason VARCHAR(1000) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    decided_by BIGINT UNSIGNED NULL,
    decided_at DATETIME NULL,
    decision_reason VARCHAR(1000) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_match_rectifications_match FOREIGN KEY (match_id) REFERENCES matches (id),
    CONSTRAINT fk_match_rectifications_operation FOREIGN KEY (operation_id) REFERENCES match_operations (id),
    CONSTRAINT fk_match_rectifications_requested_by FOREIGN KEY (requested_by) REFERENCES users (id),
    CONSTRAINT fk_match_rectifications_decided_by FOREIGN KEY (decided_by) REFERENCES users (id),
    INDEX idx_match_rectifications_match (match_id, status),
    INDEX idx_match_rectifications_requested (requested_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (`key`, name, description, module, created_at, updated_at)
VALUES
    ('match_operation.review', 'Revisar partidas', 'Devolve, rejeita ou aprova operacoes enviadas.', 'partidas', NOW(), NOW()),
    ('match_operation.rectify', 'Decidir retificacoes', 'Analisa pedidos de retificacao de partidas aprovadas.', 'partidas', NOW(), NOW()),
    ('match_operation.cancel_event', 'Anular registros da partida', 'Anula registros antes da aprovacao com justificativa.', 'partidas', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), module = VALUES(module), updated_at = VALUES(updated_at);

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, NOW()
FROM roles r
INNER JOIN permissions p ON p.`key` IN ('match_operation.review', 'match_operation.rectify', 'match_operation.cancel_event')
WHERE r.`key` = 'administrator';
