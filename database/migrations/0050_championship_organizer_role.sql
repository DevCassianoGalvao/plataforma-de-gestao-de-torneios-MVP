-- Papel "organizer": administra integralmente os campeonatos atribuidos,
-- sem acesso a nada global do sistema. O escopo por campeonato usa
-- championship_user_assignments com assignment_type = 'organizer'.
INSERT INTO roles (`key`, name, description, created_at, updated_at)
VALUES ('organizer', 'Organizador do campeonato', 'Administra integralmente os campeonatos atribuidos, sem acesso a configuracoes globais.', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    updated_at = VALUES(updated_at);

-- Concede ao organizador todas as permissoes existentes, exceto as globais.
INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, NOW()
FROM roles r
CROSS JOIN permissions p
WHERE r.`key` = 'organizer'
  AND p.`key` NOT IN (
    'system.configure',
    'users.view', 'users.create', 'users.update', 'users.deactivate', 'users.delete', 'users.manage_roles',
    'audit.view',
    'championships.create', 'championships.archive',
    'seasons.manage', 'categories.manage', 'positions.manage', 'tactical_formations.manage',
    'backup.view', 'backup.run', 'backup.download', 'backup.retry', 'backup.delete', 'backup.restore', 'backup.configure',
    'retention.view', 'retention.manage', 'retention.archive', 'retention.restore', 'retention.purge',
    'simulation.manage',
    'match_publication.run'
  );

-- Garante que permissoes globais concedidas antes sejam retiradas do organizador.
DELETE rp FROM role_permissions rp
INNER JOIN roles r ON r.id = rp.role_id
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE r.`key` = 'organizer'
  AND p.`key` IN (
    'system.configure',
    'users.view', 'users.create', 'users.update', 'users.deactivate', 'users.delete', 'users.manage_roles',
    'audit.view',
    'championships.create', 'championships.archive',
    'seasons.manage', 'categories.manage', 'positions.manage', 'tactical_formations.manage',
    'backup.view', 'backup.run', 'backup.download', 'backup.retry', 'backup.delete', 'backup.restore', 'backup.configure',
    'retention.view', 'retention.manage', 'retention.archive', 'retention.restore', 'retention.purge',
    'simulation.manage',
    'match_publication.run'
  );
