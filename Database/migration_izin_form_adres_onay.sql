-- İzin taleplerine adres alanı ekleme
ALTER TABLE `izin_talepler` ADD COLUMN `adres` TEXT NULL AFTER `aciklama`;

-- İzin formu dinamik onaylama seçenekleri tablosu
CREATE TABLE IF NOT EXISTS `izin_form_secenekler` (
  `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
  `firma_id` INT(11) NOT NULL,
  `tip` VARCHAR(50) NOT NULL, -- 'unvan' veya 'isim'
  `deger` VARCHAR(255) NOT NULL,
  INDEX (`firma_id`, `tip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
