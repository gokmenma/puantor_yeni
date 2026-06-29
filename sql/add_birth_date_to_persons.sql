-- persons tablosuna dogum tarihi alani ekleme scripti
ALTER TABLE `persons` ADD COLUMN `birth_date` DATE DEFAULT NULL AFTER `job_start_date`;
