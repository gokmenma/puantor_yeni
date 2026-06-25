-- System Multi-SMTP Accounts Initialization
-- Sets default usernames and passwords for the four distinct system mailboxes.

INSERT INTO `settings` (`firm_id`, `user_id`, `set_name`, `set_value`)
SELECT 0, 0, 'smtp_info_username', 'bilgi@puantor.com.tr' FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `firm_id` = 0 AND `user_id` = 0 AND `set_name` = 'smtp_info_username');

INSERT INTO `settings` (`firm_id`, `user_id`, `set_name`, `set_value`)
SELECT 0, 0, 'smtp_info_password', 'Us(@ixgfPDwt' FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `firm_id` = 0 AND `user_id` = 0 AND `set_name` = 'smtp_info_password');

INSERT INTO `settings` (`firm_id`, `user_id`, `set_name`, `set_value`)
SELECT 0, 0, 'smtp_support_username', 'destek@puantor.com.tr' FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `firm_id` = 0 AND `user_id` = 0 AND `set_name` = 'smtp_support_username');

INSERT INTO `settings` (`firm_id`, `user_id`, `set_name`, `set_value`)
SELECT 0, 0, 'smtp_support_password', 'Us(@ixgfPDwt' FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `firm_id` = 0 AND `user_id` = 0 AND `set_name` = 'smtp_support_password');

INSERT INTO `settings` (`firm_id`, `user_id`, `set_name`, `set_value`)
SELECT 0, 0, 'smtp_system_username', 'puantor@mbeyazilim.com' FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `firm_id` = 0 AND `user_id` = 0 AND `set_name` = 'smtp_system_username');

INSERT INTO `settings` (`firm_id`, `user_id`, `set_name`, `set_value`)
SELECT 0, 0, 'smtp_system_password', 'Us(@ixgfPDwt' FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `firm_id` = 0 AND `user_id` = 0 AND `set_name` = 'smtp_system_password');
