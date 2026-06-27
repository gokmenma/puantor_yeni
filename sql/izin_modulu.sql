-- ============================================================
-- Yıllık İzin Modülü — Veritabanı Kurulum Scripti
-- ============================================================

-- persons tablosuna doğum tarihi alanı ekle (18/50 yaş kuralı için)
ALTER TABLE `persons`
  ADD COLUMN `birth_date` DATE DEFAULT NULL AFTER `job_start_date`;

-- ============================================================
-- 1. İzin Türleri
-- ============================================================
CREATE TABLE `izin_turler` (
  `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `ad`                VARCHAR(100)    NOT NULL,
  `kod`               VARCHAR(20)     NOT NULL,
  `ucretli`           TINYINT(1)      NOT NULL DEFAULT 1,
  `puantaj_turu_id`   INT             DEFAULT NULL COMMENT 'puantajturu.id - onay anında otomatik puantaj yazımı için',
  `cakisma_kontrol`   TINYINT(1)      NOT NULL DEFAULT 1 COMMENT 'yeni talep açılırken bu türle çakışma kontrol edilsin mi',
  `aktif`             TINYINT(1)      NOT NULL DEFAULT 1,
  `olusturma_tarihi`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_kod` (`kod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `izin_turler` (`ad`, `kod`, `ucretli`, `puantaj_turu_id`, `cakisma_kontrol`) VALUES
('Yıllık İzin',       'yillik',        1, 45,   1),
('Rapor (Ücretsiz)',  'rapor',         0, 57,   1),
('Rapor (Ücretli)',   'rapor_ucretli', 1, 46,   1),
('Ücretsiz İzin',     'ucretsiz',      0, 52,   1),
('Doğum İzni',        'dogum',         1, NULL, 1),
('Askerlik İzni',     'askerlik',      0, NULL, 1);

-- ============================================================
-- 2. Yıllık İzin Hakedişleri
-- ============================================================
CREATE TABLE `izin_hakedis` (
  `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `firma_id`          INT             NOT NULL,
  `personel_id`       INT UNSIGNED    NOT NULL,
  `yil`               INT             NOT NULL COMMENT 'işe giriş yılından itibaren kaçıncı yıl (1, 2, 3 ...)',
  `hakedis_tarihi`    DATE            NOT NULL COMMENT 'hak kazanım tarihi',
  `gun_sayisi`        INT             NOT NULL,
  `tip`               ENUM('otomatik','manuel') NOT NULL DEFAULT 'otomatik',
  `aciklama`          VARCHAR(255)    DEFAULT NULL,
  `olusturan_id`      INT             DEFAULT NULL,
  `olusturma_tarihi`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_personel_yil` (`personel_id`, `yil`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. İzin Talepleri
-- ============================================================
CREATE TABLE `izin_talepler` (
  `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `firma_id`          INT             NOT NULL,
  `personel_id`       INT UNSIGNED    NOT NULL,
  `tur_id`            INT UNSIGNED    NOT NULL,
  `baslangic_tarihi`  DATE            NOT NULL,
  `bitis_tarihi`      DATE            NOT NULL,
  `gun_sayisi`        INT             NOT NULL COMMENT 'iş günü sayısı (pazar ve resmi tatiller çıkarılmış)',
  `durum`             ENUM('beklemede','onaylandi','reddedildi','iptal') NOT NULL DEFAULT 'beklemede',
  `aciklama`          TEXT            DEFAULT NULL,
  `yonetici_notu`     TEXT            DEFAULT NULL,
  `onaylayan_id`      INT             DEFAULT NULL,
  `onay_tarihi`       DATETIME        DEFAULT NULL,
  `olusturan_id`      INT             DEFAULT NULL,
  `olusturma_tarihi`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `guncelleme_tarihi` DATETIME        DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_personel_tarih` (`personel_id`, `baslangic_tarihi`, `bitis_tarihi`),
  KEY `idx_firma_durum` (`firma_id`, `durum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. FIFO Kullanım Takibi
-- ============================================================
CREATE TABLE `izin_kullanim` (
  `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `talep_id`          INT UNSIGNED    NOT NULL,
  `hakedis_id`        INT UNSIGNED    NOT NULL,
  `kullanilan_gun`    INT             NOT NULL,
  `olusturma_tarihi`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_talep_hakedis` (`talep_id`, `hakedis_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. Resmi Tatiller (şimdilik boş, ilerleyen zamanda doldurulur)
-- ============================================================
CREATE TABLE `resmi_tatiller` (
  `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `tarih`             DATE            NOT NULL,
  `ad`                VARCHAR(200)    NOT NULL,
  `yil`               INT             NOT NULL,
  `olusturma_tarihi`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tarih` (`tarih`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
