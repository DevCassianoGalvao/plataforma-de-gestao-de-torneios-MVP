INSERT IGNORE INTO user_roles (user_id, role_id, created_at, created_by)
SELECT ur.user_id, admin_role.id, ur.created_at, ur.created_by
FROM user_roles ur
INNER JOIN roles old_role ON old_role.id = ur.role_id AND old_role.`key` IN ('organizer', 'communication')
INNER JOIN roles admin_role ON admin_role.`key` = 'administrator';

DELETE ur FROM user_roles ur
INNER JOIN roles old_role ON old_role.id = ur.role_id
WHERE old_role.`key` IN ('organizer', 'communication');

DELETE rp FROM role_permissions rp
INNER JOIN roles old_role ON old_role.id = rp.role_id
WHERE old_role.`key` IN ('organizer', 'communication');

DELETE FROM roles WHERE `key` IN ('organizer', 'communication');
