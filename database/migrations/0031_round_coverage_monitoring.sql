CREATE TABLE championship_document_deadlines (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    championship_id BIGINT UNSIGNED NOT NULL,
    deadline_mode VARCHAR(30) NOT NULL DEFAULT 'next_day',
    custom_value SMALLINT UNSIGNED NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_document_deadline_championship (championship_id),
    CONSTRAINT fk_document_deadline_championship FOREIGN KEY (championship_id) REFERENCES championships (id),
    CONSTRAINT fk_document_deadline_creator FOREIGN KEY (created_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (`key`, name, description, module, created_at, updated_at) VALUES
    ('round.monitor.view', 'Visualizar acompanhamento por rodada', 'Consulta cobertura esportiva e documental por rodada.', 'rodadas', NOW(), NOW()),
    ('round.monitor.manage', 'Configurar acompanhamento por rodada', 'Configura prazos documentais por campeonato.', 'rodadas', NOW(), NOW()),
    ('round.report.generate', 'Gerar relatorios de rodada', 'Gera arquivos estruturados para acompanhamento da rodada.', 'rodadas', NOW(), NOW()),
    ('round.package.download', 'Baixar pacote documental da rodada', 'Baixa pacote de sumulas e evidencias autorizadas.', 'rodadas', NOW(), NOW()),
    ('round.bulk.review', 'Enviar partidas para revisao em lote', 'Permite preparar envio seguro de partidas completas.', 'rodadas', NOW(), NOW()),
    ('round.bulk.approve', 'Aprovar documentos em lote', 'Permite aprovar somente documentos ja validados.', 'rodadas', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), module = VALUES(module), updated_at = VALUES(updated_at);

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, NOW() FROM roles r INNER JOIN permissions p ON p.`key` IN
('round.monitor.view','round.monitor.manage','round.report.generate','round.package.download','round.bulk.review','round.bulk.approve')
WHERE r.`key` = 'administrator';

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, NOW() FROM roles r INNER JOIN permissions p ON p.`key` IN
('round.monitor.view','round.report.generate','round.package.download','match_reports.package')
WHERE r.`key` = 'accountability';
