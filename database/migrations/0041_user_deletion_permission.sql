INSERT INTO permissions (`key`, name, description, module, created_at, updated_at)
VALUES ('users.delete', 'Excluir usuarios', 'Remove usuarios da operacao sem apagar historicos.', 'usuarios', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), module = VALUES(module), updated_at = VALUES(updated_at);

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, NOW()
FROM roles r
INNER JOIN permissions p ON p.`key` = 'users.delete'
WHERE r.`key` = 'administrator';
