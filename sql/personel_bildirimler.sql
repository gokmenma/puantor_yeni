CREATE TABLE IF NOT EXISTS `personel_bildirimler` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `personel_id` INT(11) NOT NULL,
    `firma_id` INT(11) NOT NULL,
    `baslik` VARCHAR(255) NOT NULL,
    `icerik` TEXT,
    `url` VARCHAR(255) DEFAULT NULL,
    `okundu` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_personel_firma` (`personel_id`, `firma_id`),
    KEY `idx_okundu` (`okundu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
