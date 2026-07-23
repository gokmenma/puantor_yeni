-- personel_avans_talepleri tablosuna processed_by (işlemi yapan kullanıcı) sütunu ekleme
ALTER TABLE `personel_avans_talepleri` ADD COLUMN `processed_by` INT(11) NULL DEFAULT NULL AFTER `durum`;
