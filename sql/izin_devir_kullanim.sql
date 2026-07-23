-- ============================================================
-- Yıllık İzin Modülü — Devir Kullanım Tablosu Scripti
-- ============================================================

CREATE TABLE IF NOT EXISTS `izin_devir_kullanim` (
  `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `firma_id`          INT             NOT NULL,
  `personel_id`       INT UNSIGNED    NOT NULL,
  `kullanilan_gun`    INT             NOT NULL,
  `aciklama`          VARCHAR(255)    DEFAULT NULL,
  `olusturan_id`      INT             DEFAULT NULL,
  `olusturma_tarihi`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `guncelleme_tarihi` DATETIME        DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_firma_personel` (`firma_id`, `personel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
