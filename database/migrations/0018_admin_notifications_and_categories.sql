-- Central de notificacoes administrativas e catalogo inicial de categorias.
CREATE TABLE admin_notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    audit_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    message VARCHAR(500) NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uq_admin_notifications_audit (audit_id),
    INDEX idx_admin_notifications_created (created_at, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_notification_reads (
    notification_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    read_at DATETIME NOT NULL,
    PRIMARY KEY (notification_id, user_id),
    CONSTRAINT fk_notification_reads_notification FOREIGN KEY (notification_id) REFERENCES admin_notifications (id) ON DELETE CASCADE,
    CONSTRAINT fk_notification_reads_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO admin_notifications (audit_id, title, message, created_at)
SELECT id, 'Atividade do sistema', CONCAT('Evento registrado: ', action), created_at
FROM audit_logs;

INSERT IGNORE INTO categories (name, slug, description, minimum_age, maximum_age, gender_rule, status, created_at, updated_at)
VALUES
    ('Sub-09 Masculino', 'sub-09-masculino', 'Categoria juvenil de demonstracao.', 7, 9, 'male', 'active', NOW(), NOW()),
    ('Sub-11 Masculino', 'sub-11-masculino', 'Categoria juvenil de demonstracao.', 9, 11, 'male', 'active', NOW(), NOW()),
    ('Sub-13 Masculino', 'sub-13-masculino', 'Categoria juvenil de demonstracao.', 11, 13, 'male', 'active', NOW(), NOW()),
    ('Sub-17 Masculino', 'sub-17-masculino', 'Categoria juvenil de demonstracao.', 15, 17, 'male', 'active', NOW(), NOW()),
    ('Sub-20 Masculino', 'sub-20-masculino', 'Categoria juvenil de demonstracao.', 17, 20, 'male', 'active', NOW(), NOW()),
    ('Adulto Masculino', 'adulto-masculino', 'Categoria adulta configuravel.', 18, NULL, 'male', 'active', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), minimum_age = VALUES(minimum_age), maximum_age = VALUES(maximum_age), gender_rule = VALUES(gender_rule), status = VALUES(status), updated_at = VALUES(updated_at);
