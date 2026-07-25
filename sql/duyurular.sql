-- Duyurular modülü SQL scripti
-- Tarih: 2026-06-27

CREATE TABLE IF NOT EXISTS `duyurular` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `baslik` varchar(255) NOT NULL,
  `icerik` longtext NOT NULL,
  `kaynak_turu` enum('duyuru','sistem','kullanici') NOT NULL DEFAULT 'duyuru',
  `olusturan_id` int(11) NOT NULL,
  `hedef_tip` enum('aboneler','firma_kullanicilari','firma_personelleri','herkese') NOT NULL DEFAULT 'herkese',
  `hedef_firma_id` int(11) DEFAULT NULL,
  `baslangic_tarihi` date DEFAULT NULL,
  `bitis_tarihi` date DEFAULT NULL,
  `oncelik` enum('normal','onemli','acil') NOT NULL DEFAULT 'normal',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_hedef_firma` (`hedef_firma_id`),
  KEY `idx_olusturan` (`olusturan_id`),
  KEY `idx_kaynak_turu` (`kaynak_turu`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `duyuru_okundu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `duyuru_id` int(11) NOT NULL,
  `kullanici_id` int(11) NOT NULL,
  `okundu_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_duyuru_kullanici` (`duyuru_id`,`kullanici_id`),
  KEY `idx_kullanici` (`kullanici_id`),
  CONSTRAINT `fk_duyuru_okundu_duyuru` FOREIGN KEY (`duyuru_id`) REFERENCES `duyurular` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Menu kaydı (menu tablosunda title kolonu yok, page_name kullanılıyor)
INSERT INTO `menu` (`page_name`, `page_link`, `icon`, `parent_id`, `isMenu`, `isActive`, `index_no`, `is_authorize`)
VALUES ('Duyurular', 'duyurular/list', 'bell', 0, 1, 1, 12, 0);

-- Auths kaydı (auths tablosu: title, auth_name, description, parent_id, is_active)
INSERT INTO `auths` (`title`, `auth_name`, `description`, `parent_id`, `is_active`)
VALUES ('Duyurular', 'duyurular', 'Duyurular modülüne erişim', 0, 1);

-- Alt yetki: duyuru ekleme/güncelleme
-- parent_id = duyurular auth'unun id'si
INSERT INTO `auths` (`title`, `auth_name`, `description`, `parent_id`, `is_active`)
VALUES ('Duyuru Ekle/Güncelle', 'duyuru_ekle', 'Duyuru ekleme ve güncelleme yetkisi', LAST_INSERT_ID(), 1);
