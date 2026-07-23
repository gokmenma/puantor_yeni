-- Add avatar column to users table if it does not exist
ALTER TABLE `users` ADD COLUMN `avatar` VARCHAR(255) NULL AFTER `job`;
