CREATE TABLE IF NOT EXISTS `gonderilen_bildirimler` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `firma_id` INT(11) NOT NULL,
    `gonderen_id` INT(11) NOT NULL,
    `hedef_turu` VARCHAR(20) NOT NULL DEFAULT 'personel',
    `hedef` VARCHAR(50) NOT NULL,
    `personel_ids` TEXT DEFAULT NULL,
    `kullanici_ids` TEXT DEFAULT NULL,
    `baslik` VARCHAR(255) NOT NULL,
    `icerik` TEXT NOT NULL,
    `url` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_firma` (`firma_id`),
    KEY `idx_firma_hedef_turu` (`firma_id`, `hedef_turu`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
