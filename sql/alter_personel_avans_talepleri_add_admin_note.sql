-- personel_avans_talepleri tablosuna admin_note (yönetici/red açıklaması) sütunu ekleme
ALTER TABLE `personel_avans_talepleri` ADD COLUMN `admin_note` TEXT NULL DEFAULT NULL AFTER `processed_by`;
