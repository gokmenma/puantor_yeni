CREATE TABLE IF NOT EXISTS `kvkk_aydinlatma` (
    `id`              INT(11)      NOT NULL AUTO_INCREMENT,
    `person_id`       INT(11)      NOT NULL,
    `firma_id`        INT(11)      NOT NULL,
    `metin_versiyonu` VARCHAR(10)  NOT NULL DEFAULT 'v1.0',
    `onay_tarihi`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `onaylayan_kullanici` INT(11)  NOT NULL,
    `ip_adresi`       VARCHAR(45)  DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_person`  (`person_id`),
    KEY `idx_firma`   (`firma_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
