-- Add default_firm_id to users table if it does not exist
ALTER TABLE `users` ADD COLUMN `default_firm_id` INT(11) DEFAULT 0 AFTER `firm_id`;
