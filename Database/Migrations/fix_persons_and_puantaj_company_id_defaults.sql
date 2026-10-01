-- Fix default values for persons and puantaj tables to prevent SQL 1364 errors in MySQL strict mode
ALTER TABLE `persons`
  MODIFY COLUMN `company_id` BIGINT(255) NULL DEFAULT 0,
  MODIFY COLUMN `job_start_date` VARCHAR(10) NULL DEFAULT NULL;

ALTER TABLE `puantaj`
  MODIFY COLUMN `company_id` INT(255) NOT NULL DEFAULT 0,
  MODIFY COLUMN `project_id` INT(255) NOT NULL DEFAULT 0;
