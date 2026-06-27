-- Duyurular hedef güncellemesi
ALTER TABLE `duyurular` MODIFY COLUMN `hedef_tip` varchar(50) NOT NULL DEFAULT 'herkese';

CREATE TABLE IF NOT EXISTS `duyuru_hedefler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `duyuru_id` int(11) NOT NULL,
  `hedef_tip` enum('firma','kullanici','personel') NOT NULL,
  `hedef_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_duyuru_hedef` (`duyuru_id`, `hedef_tip`, `hedef_id`),
  CONSTRAINT `fk_duyuru_hedefler_duyuru` FOREIGN KEY (`duyuru_id`) REFERENCES `duyurular` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
