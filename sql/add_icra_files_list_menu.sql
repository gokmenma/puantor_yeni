-- İcra Dosyaları Listesi Menü ve Yetki Tanımları Scripti

-- 1. Auths tablosuna 'İcra Dosyaları Listesi' yetkisinin eklenmesi
INSERT INTO `auths` (`title`, `auth_name`, `description`, `parent_id`, `is_active`, `superadmin`)
SELECT 
    'İcra Dosyaları Listesi' AS `title`, 
    'icra_files_list' AS `auth_name`, 
    'Tüm personellerin icra dosyalarını listeleme ve yönetme yetkisi' AS `description`, 
    `id` AS `parent_id`, 
    1 AS `is_active`, 
    0 AS `superadmin`
FROM `auths` 
WHERE `auth_name` = 'personnel_page' 
LIMIT 1;

-- 2. Menu tablosunda Personeller (id=33) altına 'Personeller' alt menüsünün eklenmesi (eğer yoksa)
INSERT INTO `menu` (`page_name`, `page_link`, `icon`, `parent_id`, `isActive`, `isMenu`, `index_no`, `is_authorize`)
SELECT 'Personeller', 'persons/list', 'users', 33, 1, 1, 1, 1
FROM DUAL 
WHERE NOT EXISTS (
    SELECT 1 FROM `menu` WHERE `parent_id` = 33 AND `page_link` = 'persons/list' AND `isMenu` = 1
);

-- 3. Menu tablosuna 'İcra Dosyaları Listesi' menü tanımının eklenmesi
INSERT INTO `menu` (`page_name`, `page_link`, `icon`, `parent_id`, `isActive`, `isMenu`, `index_no`, `is_authorize`)
SELECT 'İcra Dosyaları Listesi', 'persons/icra-list', 'file-invoice', 33, 1, 1, 2, 1
FROM DUAL 
WHERE NOT EXISTS (
    SELECT 1 FROM `menu` WHERE `page_link` = 'persons/icra-list'
);

-- 4. Ana yönetici (main_role=1) rollerine yeni yetkinin (icra_files_list) atanması
UPDATE `role_auths` ra
JOIN `userroles` ur ON ur.id = ra.role_id
SET ra.auth_ids = CONCAT(ra.auth_ids, ',', (SELECT id FROM `auths` WHERE `auth_name` = 'icra_files_list' LIMIT 1))
WHERE ur.main_role = 1 
  AND FIND_IN_SET((SELECT id FROM `auths` WHERE `auth_name` = 'icra_files_list' LIMIT 1), ra.auth_ids) = 0;
