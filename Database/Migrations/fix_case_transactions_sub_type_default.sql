-- Fix sub_type default value in case_transactions table
ALTER TABLE `case_transactions` MODIFY COLUMN `sub_type` INT(100) NOT NULL DEFAULT 0;
