CREATE TABLE championship_carousel_slides (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    championship_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    link_url VARCHAR(500) NULL,
    image_path VARCHAR(255) NOT NULL,
    display_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_championship_carousel_slides_championship FOREIGN KEY (championship_id) REFERENCES championships(id) ON DELETE CASCADE,
    INDEX idx_championship_carousel_slides_public (championship_id, is_active, display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
