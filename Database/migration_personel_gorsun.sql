-- Puantaj türlerine 'Personel Görsün' seçeneği ekleme
ALTER TABLE `puantajturu` ADD COLUMN `personel_gorsun` TINYINT(1) NOT NULL DEFAULT 0;

-- Varsayılan yıllık ve ücretsiz izin türlerini personel_gorsun olarak ayarla
UPDATE `puantajturu` SET `personel_gorsun` = 1 WHERE `id` IN (45, 52);
