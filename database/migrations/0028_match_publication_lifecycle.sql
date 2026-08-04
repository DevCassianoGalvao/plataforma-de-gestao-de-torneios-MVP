-- Publicacao e operacao sao estados independentes. Esta migration preserva a
-- visibilidade atual dos campeonatos ja publicos sem alterar seus cadastros.
CREATE TABLE match_publications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    match_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'internal',
    scheduled_at DATETIME NULL,
    published_at DATETIME NULL,
    published_by BIGINT UNSIGNED NULL,
    cancelled_at DATETIME NULL,
    cancelled_by BIGINT UNSIGNED NULL,
    reason VARCHAR(500) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_match_publication_match (match_id),
    INDEX idx_match_publications_due (status, scheduled_at),
    CONSTRAINT fk_match_publications_match FOREIGN KEY (match_id) REFERENCES matches (id),
    CONSTRAINT fk_match_publications_published_by FOREIGN KEY (published_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_match_publications_cancelled_by FOREIGN KEY (cancelled_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO match_publications (match_id, status, published_at, created_at, updated_at)
SELECT m.id, 'published', NOW(), NOW(), NOW()
FROM matches m
INNER JOIN championships c ON c.id = m.championship_id
WHERE c.visibility = 'public'
  AND m.status IN ('scheduled', 'confirmed', 'postponed', 'homologated')
ON DUPLICATE KEY UPDATE match_id = VALUES(match_id);

INSERT INTO permissions (`key`, name, description, module, created_at, updated_at)
VALUES
    ('match_publication.manage', 'Gerenciar publicação de partidas', 'Publica, agenda e cancela a exibição pública de partidas.', 'partidas', NOW(), NOW()),
    ('match_publication.run', 'Processar publicações agendadas', 'Executa o processamento idempotente de publicações vencidas.', 'partidas', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), module = VALUES(module), updated_at = VALUES(updated_at);

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, NOW()
FROM roles r
INNER JOIN permissions p ON p.`key` IN ('match_publication.manage', 'match_publication.run')
WHERE r.`key` = 'administrator';
