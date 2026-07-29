-- Recreate the rate-limit table when an earlier deployment marked 0002 as applied
-- before all of its DDL statements were committed.
CREATE TABLE IF NOT EXISTS login_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email_hash CHAR(64) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    user_agent VARCHAR(500) NULL,
    successful TINYINT(1) NOT NULL DEFAULT 0,
    attempted_at DATETIME NOT NULL,
    INDEX idx_login_attempts_email_time (email_hash, attempted_at),
    INDEX idx_login_attempts_ip_time (ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
