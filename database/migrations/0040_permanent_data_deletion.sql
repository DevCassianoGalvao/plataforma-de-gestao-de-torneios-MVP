UPDATE retention_policies
SET allow_hard_delete = 1, protected = 0, updated_at = NOW()
WHERE scope_key = 'sports_history';

INSERT INTO permissions (`key`, name, description, module, created_at, updated_at)
VALUES ('retention.purge', 'Excluir dados definitivamente', 'Exclui em definitivo dados esportivos selecionados pelo administrador.', 'retencao', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), module = VALUES(module), updated_at = VALUES(updated_at);

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, NOW()
FROM roles r
INNER JOIN permissions p ON p.`key` = 'retention.purge'
WHERE r.`key` = 'administrator';
