INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, NOW()
FROM roles r, permissions p
WHERE r.`key` = 'team_manager' AND p.`key` = 'teams.manage_identity';
