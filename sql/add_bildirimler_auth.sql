-- Bildirimler ve Push Bildirim Yetkisi Ekleme Scripti

-- 1. Bildirimler Ana Yetkisi
INSERT INTO `auths` (`title`, `auth_name`, `description`, `parent_id`, `is_active`, `superadmin`)
SELECT 'Bildirimler', 'bildirimler', 'Bildirim modülü ve push bildirim yönetimi', 0, 1, 0
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `auths` WHERE `auth_name` = 'bildirimler');

-- 2. Push Bildirim Gönder Alt Yetkisi
INSERT INTO `auths` (`title`, `auth_name`, `description`, `parent_id`, `is_active`, `superadmin`)
SELECT 'Push Bildirim Gönder', 'push_bildirim_gonder', 'Personellere ve sistem kullanıcılarına push bildirim gönderme yetkisi', (SELECT id FROM `auths` WHERE `auth_name` = 'bildirimler' LIMIT 1), 1, 0
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `auths` WHERE `auth_name` = 'push_bildirim_gonder');

-- 3. Menu tablosundaki Bildirimler satırını yetkiye bağla
UPDATE `menu` SET `is_authorize` = 1 WHERE `page_link` = 'bildirimler/push';
