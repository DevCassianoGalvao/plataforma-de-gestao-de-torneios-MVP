-- A deleted account must not remain an active coach, manager, or match operator.
-- The assignment rows stay intact for audit and sporting history.
UPDATE team_user_assignments a
INNER JOIN users u ON u.id = a.user_id
SET a.status = 'ended',
    a.ends_at = COALESCE(a.ends_at, CURDATE()),
    a.updated_at = NOW()
WHERE u.deleted_at IS NOT NULL
  AND a.status = 'active';

UPDATE match_operator_assignments a
INNER JOIN users u ON u.id = a.user_id
SET a.status = 'ended',
    a.ended_at = COALESCE(a.ended_at, NOW())
WHERE u.deleted_at IS NOT NULL
  AND a.status = 'active';
