-- System Settings Initialization
-- In Puantor, system configurations are stored in the settings table with firm_id = 0 and user_id = 0.

INSERT INTO `settings` (`firm_id`, `user_id`, `set_name`, `set_value`)
SELECT 0, 0, 'system_title', 'İşçi Maaş' FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `firm_id` = 0 AND `user_id` = 0 AND `set_name` = 'system_title');

INSERT INTO `settings` (`firm_id`, `user_id`, `set_name`, `set_value`)
SELECT 0, 0, 'system_email', 'bilgi@iscimaas.com.tr' FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `firm_id` = 0 AND `user_id` = 0 AND `set_name` = 'system_email');

INSERT INTO `settings` (`firm_id`, `user_id`, `set_name`, `set_value`)
SELECT 0, 0, 'system_language', 'tr' FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `firm_id` = 0 AND `user_id` = 0 AND `set_name` = 'system_language');

INSERT INTO `settings` (`firm_id`, `user_id`, `set_name`, `set_value`)
SELECT 0, 0, 'maintenance_mode', '0' FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `firm_id` = 0 AND `user_id` = 0 AND `set_name` = 'maintenance_mode');

INSERT INTO `settings` (`firm_id`, `user_id`, `set_name`, `set_value`)
SELECT 0, 0, 'kvkk_consent', '0' FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `firm_id` = 0 AND `user_id` = 0 AND `set_name` = 'kvkk_consent');

INSERT INTO `settings` (`firm_id`, `user_id`, `set_name`, `set_value`)
SELECT 0, 0, 'smtp_host', 'mail.iscimaas.com.tr' FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `firm_id` = 0 AND `user_id` = 0 AND `set_name` = 'smtp_host');

INSERT INTO `settings` (`firm_id`, `user_id`, `set_name`, `set_value`)
SELECT 0, 0, 'smtp_port', '465' FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `firm_id` = 0 AND `user_id` = 0 AND `set_name` = 'smtp_port');

INSERT INTO `settings` (`firm_id`, `user_id`, `set_name`, `set_value`)
SELECT 0, 0, 'smtp_username', 'bilgi@iscimaas.com.tr' FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `firm_id` = 0 AND `user_id` = 0 AND `set_name` = 'smtp_username');

INSERT INTO `settings` (`firm_id`, `user_id`, `set_name`, `set_value`)
SELECT 0, 0, 'smtp_password', 'Us(@ixgfPDwt' FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `firm_id` = 0 AND `user_id` = 0 AND `set_name` = 'smtp_password');

INSERT INTO `settings` (`firm_id`, `user_id`, `set_name`, `set_value`)
SELECT 0, 0, 'smtp_encryption', 'ssl' FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `firm_id` = 0 AND `user_id` = 0 AND `set_name` = 'smtp_encryption');

INSERT INTO `settings` (`firm_id`, `user_id`, `set_name`, `set_value`)
SELECT 0, 0, 'smtp_from_name', 'İşçi Maaş - Kamu İşçi Maaş Programı' FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `firm_id` = 0 AND `user_id` = 0 AND `set_name` = 'smtp_from_name');
