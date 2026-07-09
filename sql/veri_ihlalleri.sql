CREATE TABLE IF NOT EXISTS `veri_ihlalleri` (
    `id`                  INT(11)      NOT NULL AUTO_INCREMENT,
    `firma_id`            INT(11)      NOT NULL,
    `ihlal_tarihi`        DATETIME     NOT NULL,
    `tespit_tarihi`       DATETIME     NOT NULL,
    `ihlal_turu`          VARCHAR(150) NOT NULL,
    `etkilenen_veri`      TEXT         DEFAULT NULL,
    `etkilenen_kisi_sayisi` INT(11)    DEFAULT 0,
    `onlem_alinan`        TEXT         DEFAULT NULL,
    `bildiri_durum`       ENUM('bekliyor','kvkk_bildirildi') NOT NULL DEFAULT 'bekliyor',
    `bildiri_tarihi`      DATETIME     NULL DEFAULT NULL,
    `kvkk_referans_no`   VARCHAR(100) DEFAULT NULL,
    `aciklama`            TEXT         DEFAULT NULL,
    `olusturan_id`        INT(11)      NOT NULL,
    `created_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_firma`       (`firma_id`),
    KEY `idx_bildiri`     (`bildiri_durum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
