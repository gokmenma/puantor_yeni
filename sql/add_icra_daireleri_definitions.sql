-- İcra Daireleri Tanımlama Sayfası Veritabanı Değişiklikleri

-- 1. icra_daireleri tablosuna IBAN alanının eklenmesi
ALTER TABLE `icra_daireleri` ADD COLUMN IF NOT EXISTS `iban` VARCHAR(34) DEFAULT NULL AFTER `sehir`;

-- 2. Menü kaydı
-- 'Tanımlamalar' üst menüsünün id'sini dinamik olarak al
SET @parent_menu_id = (SELECT `id` FROM `menu` WHERE `page_name` = 'Tanımlamalar' AND `parent_id` = 0 LIMIT 1);

INSERT INTO `menu` (`page_name`, `page_link`, `icon`, `parent_id`, `isActive`, `isMenu`, `index_no`, `is_authorize`)
SELECT 'İcra Daireleri Tanımlama', 'defines/icra-daireleri/list', 'building-bank', @parent_menu_id, 1, 1, 12, 1
WHERE NOT EXISTS (
    SELECT 1 FROM `menu` WHERE `page_link` = 'defines/icra-daireleri/list'
);

-- 3. Yetki kayıtları (auths)
-- 'definitions_page' yetkisinin id'sini dinamik olarak al
SET @parent_auth_id = (SELECT `id` FROM `auths` WHERE `auth_name` = 'definitions_page' LIMIT 1);

INSERT INTO `auths` (`title`, `auth_name`, `description`, `parent_id`, `is_active`)
SELECT 'İcra Daireleri Tanımlama', 'icra_daireleri_list', 'İcra daireleri tanımlama listesini görüntüleme yetkisi.', @parent_auth_id, 1
WHERE NOT EXISTS (
    SELECT 1 FROM `auths` WHERE `auth_name` = 'icra_daireleri_list'
);

INSERT INTO `auths` (`title`, `auth_name`, `description`, `parent_id`, `is_active`)
SELECT 'İcra Daireleri Ekle/Güncelle/Sil', 'icra_daireleri_add_update', 'İcra daireleri ekleme, güncelleme ve silme yetkisi.', @parent_auth_id, 1
WHERE NOT EXISTS (
    SELECT 1 FROM `auths` WHERE `auth_name` = 'icra_daireleri_add_update'
);
