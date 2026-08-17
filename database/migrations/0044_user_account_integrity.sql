-- Normalize accounts already soft-deleted before the user-account fix.
-- Keeps historical rows while allowing their original e-mails to be reused.
UPDATE users
SET email = CONCAT('__deleted_', id, '_', LEFT(SHA2(email, 256), 24), '@invalid.local')
WHERE deleted_at IS NOT NULL
  AND email NOT LIKE '__deleted_%@invalid.local';
