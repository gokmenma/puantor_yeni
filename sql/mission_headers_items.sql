-- --------------------------------------------------------
-- Sunucu:                       127.0.0.1
-- Sunucu sürümü:                10.4.32-MariaDB - mariadb.org binary distribution
-- Sunucu İşletim Sistemi:       Win64
-- HeidiSQL Sürüm:               12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- puantoryeni için veritabanı yapısı dökülüyor
CREATE DATABASE IF NOT EXISTS `puantoryeni` /*!40100 DEFAULT CHARACTER SET latin5 COLLATE latin5_turkish_ci */;
USE `puantoryeni`;

-- tablo yapısı dökülüyor puantoryeni.mission_process_mapping
DROP TABLE IF EXISTS `mission_process_mapping`;
CREATE TABLE IF NOT EXISTS `mission_process_mapping` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mission_id` int(11) NOT NULL,
  `process_id` int(11) NOT NULL,
  `process_order` int(11) NOT NULL,
  `created_at` varchar(20) DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci;

-- puantoryeni.mission_process_mapping: ~8 rows (yaklaşık) tablosu için veriler indiriliyor
DELETE FROM `mission_process_mapping`;
INSERT INTO `mission_process_mapping` (`id`, `mission_id`, `process_id`, `process_order`, `created_at`) VALUES
	(1, 13, 8, 0, '2024-10-06 11:23:14'),
	(3, 6, 7, 0, '2024-10-06 11:23:36'),
	(4, 6, 4, 0, '2024-10-06 11:23:36'),
	(5, 14, 7, 0, '2024-10-06 11:23:36'),
	(6, 15, 3, 0, '2024-10-06 11:23:36'),
	(7, 22, 1, 0, '2024-10-06 18:52:08'),
	(8, 23, 8, 0, '2024-10-06 20:28:52'),
	(9, 24, 8, 0, '2024-10-06 20:29:35');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
