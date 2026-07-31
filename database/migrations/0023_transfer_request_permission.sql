INSERT INTO permissions (`key`, name, description, module, created_at, updated_at)
VALUES ('transfers.request', 'Solicitar Vai e Vem', 'Cria e acompanha solicitacoes de transferencia da propria equipe.', 'vai-e-vem', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), module = VALUES(module), updated_at = VALUES(updated_at);

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, NOW()
FROM roles r, permissions p
WHERE r.`key` = 'team_manager' AND p.`key` = 'transfers.request';
