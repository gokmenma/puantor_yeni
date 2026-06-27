-- Yıllık izin modülü onaylı/reddedilmiş izinleri silme yetkisi ekleme
INSERT INTO `auths` (`title`, `auth_name`, `description`, `parent_id`, `is_active`, `superadmin`)
SELECT 
    'Onaylı/Reddedilmiş İzinleri Sil' AS `title`, 
    'onayli_izinleri_sil' AS `auth_name`, 
    'Onaylı veya reddedilmiş izin taleplerini silme yetkisi' AS `description`, 
    `id` AS `parent_id`, 
    1 AS `is_active`, 
    0 AS `superadmin`
FROM `auths` 
WHERE `auth_name` = 'izin_talepler' 
LIMIT 1;
