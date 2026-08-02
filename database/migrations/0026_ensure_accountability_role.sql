INSERT INTO roles (`key`, name, description, created_at, updated_at)
VALUES ('accountability', 'Prestacao de contas', 'Consulta e exporta evidencias autorizadas.', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    updated_at = VALUES(updated_at);

INSERT INTO permissions (`key`, name, description, module, created_at, updated_at)
VALUES
    ('accountability.view', 'Visualizar prestacao de contas', 'Consulta dados consolidados autorizados.', 'prestacao', NOW(), NOW()),
    ('accountability.export', 'Exportar prestacao de contas', 'Exporta planilhas e pacote de evidencias autorizados.', 'prestacao', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    module = VALUES(module),
    updated_at = VALUES(updated_at);

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, NOW()
FROM roles r
INNER JOIN permissions p ON p.`key` IN ('accountability.view', 'accountability.export')
WHERE r.`key` = 'accountability';
