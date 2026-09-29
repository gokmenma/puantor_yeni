-- Fix default values for case_transactions table to prevent SQL 1364 errors in MySQL strict mode
ALTER TABLE `case_transactions`
  MODIFY COLUMN `project_id` INT(100) NOT NULL DEFAULT 0,
  MODIFY COLUMN `person_id` INT(100) NOT NULL DEFAULT 0,
  MODIFY COLUMN `company_id` INT(100) NULL DEFAULT 0,
  MODIFY COLUMN `users_type_id` INT(100) NOT NULL DEFAULT 0,
  MODIFY COLUMN `sub_type` INT(100) NOT NULL DEFAULT 0,
  MODIFY COLUMN `amount_money` INT(11) NULL DEFAULT 1,
  MODIFY COLUMN `description` VARCHAR(255) NULL DEFAULT '';
