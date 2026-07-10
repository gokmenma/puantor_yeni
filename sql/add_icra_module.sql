-- İcra Dosyaları Modülü Veritabanı Değişiklikleri

-- 1. İcra dosyalarını tutacak tablo
CREATE TABLE IF NOT EXISTS `person_icra_files` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `person_id` INT(11) NOT NULL,
  `firm_id` VARCHAR(25) NOT NULL,
  `icra_sirasi` INT(11) NOT NULL DEFAULT 1,
  `icra_dairesi` VARCHAR(255) NOT NULL,
  `dosya_no` VARCHAR(100) NOT NULL,
  `alacakli` VARCHAR(255) NOT NULL,
  `toplam_borc` DECIMAL(20,2) NOT NULL DEFAULT 0.00,
  `kesinti_yontemi` VARCHAR(20) NOT NULL, -- 'oran' veya 'sabit'
  `kesinti_orani` VARCHAR(50) DEFAULT NULL, -- örn: '1/4' veya '%25'
  `kesinti_tutari` DECIMAL(20,2) DEFAULT NULL,
  `durum` VARCHAR(50) NOT NULL DEFAULT 'Bekliyor', -- 'Bekliyor', 'Aktif', 'Ödendi', 'Kapatıldı'
  `aciklama` TEXT DEFAULT NULL,
  `gelen_evrak` VARCHAR(255) DEFAULT NULL,
  `giden_evrak` VARCHAR(255) DEFAULT NULL,
  `belge_yolu` VARCHAR(255) DEFAULT NULL,
  `belge_adi` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci;

-- 2. Personel tablosuna icra kesinti tetiği eklenmesi
ALTER TABLE `persons` ADD COLUMN `icra_kesintisi_aktif` TINYINT(1) DEFAULT 0;

-- 3. Yetkilendirme tablosuna (auths) yeni yetki tanımının eklenmesi
INSERT INTO `auths` (`title`, `auth_name`, `description`, `parent_id`, `is_active`, `superadmin`)
SELECT 
    'Personel Sayfası İcra Dosyaları Bilgileri' AS `title`, 
    'person_page_icra_info' AS `auth_name`, 
    'Personel sayfasındaki İcra Dosyaları sekmesini görme ve yönetme yetkisi' AS `description`, 
    `id` AS `parent_id`, 
    1 AS `is_active`, 
    0 AS `superadmin`
FROM `auths` 
WHERE `auth_name` = 'personnel_page' 
LIMIT 1;
