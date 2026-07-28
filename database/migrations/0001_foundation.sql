CREATE TABLE IF NOT EXISTS foundation_health (
    id TINYINT UNSIGNED PRIMARY KEY,
    status VARCHAR(32) NOT NULL,
    checked_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO foundation_health (id, status, checked_at)
VALUES (1, 'ok', NOW())
ON DUPLICATE KEY UPDATE status = VALUES(status), checked_at = VALUES(checked_at);
