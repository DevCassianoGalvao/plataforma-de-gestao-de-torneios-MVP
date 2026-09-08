-- As permissoes de comissao tecnica so eram criadas no AuthSeed (dev).
-- Em producao o perfil team_manager ficava sem acesso ao cadastro da
-- comissao. Esta migration garante as permissoes e os vinculos.
INSERT INTO permissions (`key`, name, description, module, created_at, updated_at)
VALUES
    ('team_staff.view', 'Visualizar comissao tecnica', 'Consulta membros da comissao.', 'comissao', NOW(), NOW()),
    ('team_staff.create', 'Cadastrar comissao tecnica', 'Cadastra membros da comissao.', 'comissao', NOW(), NOW()),
    ('team_staff.update', 'Editar comissao tecnica', 'Edita membros da comissao.', 'comissao', NOW(), NOW()),
    ('team_staff.deactivate', 'Inativar comissao tecnica', 'Inativa membros da comissao.', 'comissao', NOW(), NOW()),
    ('team_staff.manage_own', 'Gerenciar comissao propria', 'Gerencia a comissao da propria equipe.', 'comissao', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), module = VALUES(module), updated_at = VALUES(updated_at);

-- Administrador e organizador recebem todas as permissoes de comissao.
INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, NOW()
FROM roles r
INNER JOIN permissions p ON p.`key` IN ('team_staff.view', 'team_staff.create', 'team_staff.update', 'team_staff.deactivate', 'team_staff.manage_own')
WHERE r.`key` IN ('administrator', 'organizer');

-- Treinador/Gestor gerencia a comissao da propria equipe.
INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, NOW()
FROM roles r
INNER JOIN permissions p ON p.`key` IN ('team_staff.view', 'team_staff.create', 'team_staff.update', 'team_staff.deactivate', 'team_staff.manage_own')
WHERE r.`key` = 'team_manager';
