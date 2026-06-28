CREATE TABLE IF NOT EXISTS `gonderilen_bildirimler` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `firma_id` INT(11) NOT NULL,
    `gonderen_id` INT(11) NOT NULL,
    `hedef` VARCHAR(50) NOT NULL,
    `personel_ids` TEXT DEFAULT NULL,
    `baslik` VARCHAR(255) NOT NULL,
    `icerik` TEXT NOT NULL,
    `url` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_firma` (`firma_id`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
