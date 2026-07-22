CREATE TABLE IF NOT EXISTS `mail_gonderimleri` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `gonderen_id` INT(11) NOT NULL,
    `gonderen_hesabi` VARCHAR(20) NOT NULL,
    `gonderen_email` VARCHAR(255) NOT NULL,
    `alici_turu` VARCHAR(20) NOT NULL,
    `konu` VARCHAR(255) NOT NULL,
    `icerik` MEDIUMTEXT NOT NULL,
    `toplam_alici` INT UNSIGNED NOT NULL DEFAULT 0,
    `basarili_sayisi` INT UNSIGNED NOT NULL DEFAULT 0,
    `basarisiz_sayisi` INT UNSIGNED NOT NULL DEFAULT 0,
    `durum` VARCHAR(20) NOT NULL DEFAULT 'hazirlaniyor',
    `tamamlanma_tarihi` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_mail_gonderimleri_created_at` (`created_at`),
    KEY `idx_mail_gonderimleri_durum` (`durum`),
    KEY `idx_mail_gonderimleri_gonderen` (`gonderen_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mail_gonderim_alicilari` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `gonderim_id` BIGINT UNSIGNED NOT NULL,
    `kullanici_id` INT(11) DEFAULT NULL,
    `alici_adi` VARCHAR(255) DEFAULT NULL,
    `email` VARCHAR(255) NOT NULL,
    `durum` VARCHAR(20) NOT NULL DEFAULT 'bekliyor',
    `hata_mesaji` VARCHAR(500) DEFAULT NULL,
    `gonderilme_tarihi` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_mail_gonderim_alici` (`gonderim_id`, `email`),
    KEY `idx_mail_alicilari_gonderim_durum` (`gonderim_id`, `durum`),
    CONSTRAINT `fk_mail_alicilari_gonderim` FOREIGN KEY (`gonderim_id`) REFERENCES `mail_gonderimleri` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `menu` (`page_name`, `page_link`, `icon`, `parent_id`, `isActive`, `isMenu`, `index_no`, `is_authorize`)
SELECT 'Mail İşlemleri', 'mail-islemleri/index', 'mail-forward', 0, 1, 1, 17, 1
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `menu` WHERE `page_link` = 'mail-islemleri/index');

INSERT INTO `auths` (`title`, `auth_name`, `description`, `parent_id`, `is_active`, `superadmin`)
SELECT 'Mail İşlemleri', 'mail_islemleri', 'Sistem ve harici kullanıcılara e-posta gönderme ve gönderimleri izleme.', 0, 1, 1
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `auths` WHERE `auth_name` = 'mail_islemleri');
