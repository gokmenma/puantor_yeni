-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: mbeyazil_puantoryeni
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firm_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_type` varchar(50) NOT NULL,
  `action` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `firm_id` (`firm_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1144 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `auths`
--

DROP TABLE IF EXISTS `auths`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auths` (
  `id` tinyint(4) NOT NULL AUTO_INCREMENT,
  `title` varchar(60) NOT NULL,
  `auth_name` varchar(60) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `parent_id` tinyint(4) DEFAULT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=116 DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `case_transactions`
--

DROP TABLE IF EXISTS `case_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `case_transactions` (
  `id` int(100) NOT NULL AUTO_INCREMENT,
  `type_id` int(100) NOT NULL,
  `date` varchar(10) NOT NULL,
  `case_id` int(100) NOT NULL,
  `project_id` int(100) NOT NULL,
  `person_id` int(100) NOT NULL,
  `company_id` int(100) DEFAULT NULL,
  `sub_type` int(100) NOT NULL,
  `users_type_id` int(100) NOT NULL,
  `amount` double NOT NULL DEFAULT 0,
  `amount_money` int(11) DEFAULT NULL,
  `account_name` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` varchar(30) NOT NULL DEFAULT current_timestamp(),
  `updated_at` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=383 DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cases`
--

DROP TABLE IF EXISTS `cases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` int(11) NOT NULL DEFAULT 0,
  `firm_id` int(11) DEFAULT NULL,
  `start_budget` decimal(10,2) DEFAULT NULL,
  `case_name` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `branch_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `case_money_unit` varchar(10) DEFAULT NULL,
  `isDefault` int(11) DEFAULT NULL,
  `user_ids` varchar(255) DEFAULT NULL,
  `created_at` varchar(50) DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=94 DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cgroups`
--

DROP TABLE IF EXISTS `cgroups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cgroups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(155) NOT NULL,
  `regdate` timestamp NOT NULL DEFAULT current_timestamp(),
  `statu` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=104 DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `grp` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `company_name` varchar(100) DEFAULT NULL,
  `yetkili` varchar(255) DEFAULT NULL,
  `phone` varchar(155) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `city` int(11) DEFAULT NULL,
  `town` varchar(100) DEFAULT NULL,
  `address` varchar(250) DEFAULT NULL,
  `tax_number` varchar(100) DEFAULT NULL,
  `tax_office` varchar(100) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `creativer` int(11) DEFAULT NULL,
  `OdemeVade` varchar(10) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `updater` int(10) NOT NULL,
  `updated_at` varchar(30) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=133 DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `define_numbers`
--

DROP TABLE IF EXISTS `define_numbers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `define_numbers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ysc` int(20) NOT NULL,
  `hst` int(20) NOT NULL,
  `met` int(20) NOT NULL,
  `yas` int(20) NOT NULL,
  `purchase` int(20) NOT NULL,
  `purchase_demand` int(20) NOT NULL,
  `aas` int(11) NOT NULL,
  `oys` int(11) NOT NULL,
  `offer` int(255) DEFAULT NULL,
  `service` int(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `defines`
--

DROP TABLE IF EXISTS `defines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `defines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `type_id` int(2) NOT NULL DEFAULT 0,
  `firm_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(50) NOT NULL DEFAULT '0',
  `sub_type` int(1) DEFAULT 0,
  `description` varchar(30) DEFAULT NULL,
  `icon_code` varchar(30) DEFAULT NULL,
  `icon_color` varchar(30) DEFAULT NULL,
  `created_at` varchar(20) DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=53 DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `feedback`
--

DROP TABLE IF EXISTS `feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firm_id` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `subject` varchar(50) NOT NULL DEFAULT '0',
  `message` varchar(1000) NOT NULL DEFAULT '0',
  `status` int(11) NOT NULL DEFAULT 0,
  `created_at` varchar(20) NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `gorev_listeleri`
--

DROP TABLE IF EXISTS `gorev_listeleri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gorev_listeleri` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firma_id` int(11) NOT NULL,
  `baslik` varchar(255) NOT NULL,
  `sira` int(11) DEFAULT 0,
  `renk` varchar(7) DEFAULT NULL,
  `olusturan_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_firma_id` (`firma_id`),
  KEY `idx_sira` (`sira`),
  KEY `idx_olusturan` (`olusturan_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `gorevler`
--

DROP TABLE IF EXISTS `gorevler`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gorevler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `liste_id` int(11) NOT NULL,
  `firma_id` int(11) NOT NULL,
  `baslik` varchar(500) NOT NULL,
  `aciklama` text DEFAULT NULL,
  `tarih` date DEFAULT NULL,
  `saat` time DEFAULT NULL,
  `tamamlandi` tinyint(1) DEFAULT 0,
  `bildirim_gonderildi` tinyint(1) DEFAULT 0,
  `on_bildirim_gonderildi` tinyint(1) DEFAULT 0,
  `tam_vakit_bildirim_gonderildi` tinyint(1) DEFAULT 0,
  `tamamlanma_tarihi` datetime DEFAULT NULL,
  `sira` int(11) DEFAULT 0,
  `yildizli` tinyint(1) DEFAULT 0,
  `yineleme_sikligi` int(11) DEFAULT NULL,
  `yineleme_birimi` enum('gun','hafta','ay','yil') DEFAULT NULL,
  `yineleme_gunleri` varchar(50) DEFAULT NULL,
  `yineleme_baslangic` date DEFAULT NULL,
  `yineleme_bitis_tipi` enum('asla','tarih','adet') DEFAULT NULL,
  `yineleme_bitis_tarihi` date DEFAULT NULL,
  `yineleme_bitis_adet` int(11) DEFAULT NULL,
  `olusturan_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `gorev_kullanicilari` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_liste_id` (`liste_id`),
  KEY `idx_firma_id` (`firma_id`),
  KEY `idx_tamamlandi` (`tamamlandi`),
  KEY `idx_sira` (`sira`),
  KEY `idx_tarih` (`tarih`),
  KEY `idx_gorevler_bildirim` (`tarih`,`tamamlandi`,`bildirim_gonderildi`),
  CONSTRAINT `gorevler_ibfk_1` FOREIGN KEY (`liste_id`) REFERENCES `gorev_listeleri` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `il`
--

DROP TABLE IF EXISTS `il`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `il` (
  `id` tinyint(4) NOT NULL DEFAULT 0,
  `city_name` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  FULLTEXT KEY `ad` (`city_name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ilce`
--

DROP TABLE IF EXISTS `ilce`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ilce` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `il_id` tinyint(4) NOT NULL,
  `ilce_adi` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=959 DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `job_groups`
--

DROP TABLE IF EXISTS `job_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firm_id` int(11) NOT NULL DEFAULT 0,
  `group_name` varchar(100) NOT NULL DEFAULT '0',
  `description` varchar(100) NOT NULL DEFAULT '0',
  `created_at` varchar(20) NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `login_logs`
--

DROP TABLE IF EXISTS `login_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `logout_time` varchar(20) NOT NULL DEFAULT '',
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=544 DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `maas_gelir_kesinti`
--

DROP TABLE IF EXISTS `maas_gelir_kesinti`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maas_gelir_kesinti` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `person_id` int(11) NOT NULL DEFAULT 0,
  `project_id` int(11) NOT NULL DEFAULT 0,
  `case_id` int(11) NOT NULL DEFAULT 0,
  `gun` varchar(8) NOT NULL DEFAULT '0',
  `ay` int(11) NOT NULL DEFAULT 0,
  `yil` int(11) NOT NULL DEFAULT 0,
  `tutar` decimal(20,2) NOT NULL DEFAULT 0.00,
  `kategori` int(11) NOT NULL DEFAULT 0,
  `turu` varchar(255) NOT NULL DEFAULT '0',
  `aciklama` varchar(255) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=385 DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `menu`
--

DROP TABLE IF EXISTS `menu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_name` varchar(50) NOT NULL DEFAULT '0',
  `page_link` varchar(255) NOT NULL DEFAULT '0',
  `icon` varchar(255) NOT NULL DEFAULT '0',
  `parent_id` int(11) NOT NULL DEFAULT 0,
  `isActive` int(11) DEFAULT 1,
  `isMenu` int(11) DEFAULT 1,
  `index_no` int(11) DEFAULT NULL,
  `is_authorize` int(11) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci COMMENT='isMenu alanı, menüde görünüp görünmesi için, \r\nindex_no alanı ile menülerin sırası belirlenir\r\nis_authorize alanı, yetki kontolü yapılıp yapılmayacağını belirler';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mission_headers`
--

DROP TABLE IF EXISTS `mission_headers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mission_headers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `firm_id` int(11) NOT NULL DEFAULT 0,
  `header_name` varchar(255) NOT NULL DEFAULT '0',
  `header_order` int(3) NOT NULL DEFAULT 0,
  `description` varchar(255) NOT NULL DEFAULT '0',
  `status` int(3) NOT NULL DEFAULT 1,
  `created_at` varchar(20) NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=29 DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mission_headers_items`
--

DROP TABLE IF EXISTS `mission_headers_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mission_headers_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mission_id` int(11) NOT NULL,
  `header_id` int(11) NOT NULL,
  `created_at` varchar(20) DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mission_process`
--

DROP TABLE IF EXISTS `mission_process`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mission_process` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `firm_id` int(11) NOT NULL DEFAULT 0,
  `status` int(1) NOT NULL DEFAULT 0,
  `process_name` varchar(100) NOT NULL DEFAULT '0',
  `process_order` int(3) NOT NULL DEFAULT 0,
  `description` varchar(255) NOT NULL DEFAULT '0',
  `created_at` varchar(20) NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mission_process_mapping`
--

DROP TABLE IF EXISTS `mission_process_mapping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mission_process_mapping` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mission_id` int(11) NOT NULL,
  `process_id` int(11) NOT NULL,
  `process_order` int(11) NOT NULL,
  `created_at` varchar(20) DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `missions`
--

DROP TABLE IF EXISTS `missions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `missions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `firm_id` int(11) NOT NULL DEFAULT 0,
  `header_id` int(11) NOT NULL DEFAULT 0,
  `user_ids` varchar(50) NOT NULL DEFAULT '0',
  `name` varchar(100) NOT NULL DEFAULT '0',
  `priority` int(11) NOT NULL DEFAULT 0,
  `start_date` varchar(20) NOT NULL DEFAULT '0',
  `end_date` varchar(20) NOT NULL DEFAULT '0',
  `description` text NOT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `created_at` varchar(20) NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=68 DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `myfirms`
--

DROP TABLE IF EXISTS `myfirms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `myfirms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `firm_name` varchar(255) NOT NULL DEFAULT '0',
  `phone` varchar(255) NOT NULL DEFAULT '0',
  `email` varchar(255) NOT NULL DEFAULT '0',
  `start_budget` float NOT NULL DEFAULT 0,
  `tax_number` varchar(50) NOT NULL DEFAULT '0',
  `tax_office` varchar(100) NOT NULL DEFAULT '0',
  `yetkili_adi` varchar(100) NOT NULL DEFAULT '0',
  `brand_logo` varchar(255) NOT NULL DEFAULT '0',
  `description` varchar(255) NOT NULL DEFAULT '0',
  `created_at` varchar(20) NOT NULL DEFAULT current_timestamp(),
  `creator` int(11) NOT NULL DEFAULT 0,
  `deleted_at` varchar(255) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=193 DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `offer_products`
--

DROP TABLE IF EXISTS `offer_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `offer_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `xid` int(11) NOT NULL,
  `oid` int(11) NOT NULL,
  `stokKodu` varchar(50) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `unit` varchar(255) NOT NULL,
  `amount` varchar(255) NOT NULL,
  `buyprice` double(10,2) DEFAULT NULL,
  `buycur` varchar(20) DEFAULT NULL,
  `saleprice` double(10,2) NOT NULL,
  `salecur` varchar(20) DEFAULT NULL,
  `total_price` double(10,2) NOT NULL,
  `satirno` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=631 DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `offers`
--

DROP TABLE IF EXISTS `offers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `offers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `offerNumber` varchar(20) DEFAULT NULL,
  `cid` int(11) NOT NULL,
  `company_authors` varchar(255) DEFAULT NULL,
  `total_price` varchar(255) DEFAULT NULL,
  `mycompany` varchar(150) NOT NULL,
  `authors` varchar(50) NOT NULL,
  `regdate` date NOT NULL DEFAULT current_timestamp(),
  `reg_date` varchar(20) DEFAULT NULL,
  `tax` int(7) NOT NULL,
  `creativer` int(11) NOT NULL,
  `notes` text NOT NULL,
  `currency` varchar(11) NOT NULL,
  `statu` int(11) NOT NULL,
  `dollar` float NOT NULL,
  `euro` float NOT NULL,
  `payment_period` varchar(10) DEFAULT NULL,
  `offer_header` int(10) DEFAULT NULL,
  `offer_header_content` text DEFAULT NULL,
  `offer_footer` int(10) DEFAULT NULL,
  `offer_footer_content` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL,
  `Kdv` decimal(10,0) DEFAULT NULL,
  `iskonto` decimal(10,0) DEFAULT NULL,
  `subdescription` varchar(255) NOT NULL,
  `buyTotal` float(10,2) DEFAULT NULL,
  `saleTotal` varchar(255) DEFAULT NULL,
  `amountTotal` double(20,2) DEFAULT NULL,
  `curDollar` double(30,4) DEFAULT NULL,
  `curEuro` double(30,4) DEFAULT NULL,
  `DolarTotal` varchar(25) DEFAULT NULL,
  `EuroTotal` varchar(255) DEFAULT NULL,
  `TLTotal` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=304 DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(60) NOT NULL DEFAULT '0',
  `token` varchar(90) NOT NULL DEFAULT '0',
  `created_at` varchar(50) NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `person_daily_wages`
--

DROP TABLE IF EXISTS `person_daily_wages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `person_daily_wages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `person_id` int(11) DEFAULT NULL,
  `wage_name` varchar(255) DEFAULT NULL,
  `start_date` varchar(8) DEFAULT NULL,
  `end_date` varchar(8) DEFAULT NULL,
  `amount` decimal(20,4) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=75 DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `persons`
--

DROP TABLE IF EXISTS `persons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `persons` (
  `id` int(255) unsigned NOT NULL AUTO_INCREMENT,
  `firm_id` varchar(25) DEFAULT NULL,
  `full_name` varchar(25) DEFAULT NULL,
  `kimlik_no` varchar(255) DEFAULT NULL,
  `sigorta_no` bigint(10) DEFAULT NULL,
  `phone` varchar(16) DEFAULT NULL,
  `address` varchar(100) DEFAULT NULL,
  `project_id` varchar(255) DEFAULT NULL,
  `company_id` bigint(255) NOT NULL,
  `daily_wages` decimal(20,2) DEFAULT NULL,
  `iban_number` varchar(255) DEFAULT NULL,
  `wage_type` tinyint(1) DEFAULT NULL,
  `job_start_date` varchar(10) NOT NULL,
  `job_end_date` varchar(10) DEFAULT NULL,
  `job` varchar(40) DEFAULT NULL,
  `state` varchar(5) DEFAULT NULL,
  `email` varchar(33) DEFAULT NULL,
  `job_group` varchar(33) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `ekip` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `team_id` int(11) DEFAULT NULL,
  `deleted_at` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=583 DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` int(29) NOT NULL AUTO_INCREMENT,
  `urun_adi` varchar(89) DEFAULT NULL,
  `Turu` varchar(10) DEFAULT NULL,
  `TedarikciID` bigint(100) DEFAULT NULL,
  `stok_kodu` varchar(30) DEFAULT NULL,
  `UrunGrubu` varchar(10) DEFAULT NULL,
  `birimi` varchar(20) DEFAULT NULL,
  `alis_fiyati` varchar(10) DEFAULT NULL,
  `alis_para_birimi` varchar(20) DEFAULT NULL,
  `satis_fiyati` varchar(10) DEFAULT NULL,
  `satis_para_birimi` varchar(20) DEFAULT NULL,
  `ExtraMaliyet` varchar(10) DEFAULT NULL,
  `Barkod` varchar(10) DEFAULT NULL,
  `RafKodu` varchar(10) DEFAULT NULL,
  `MinStok` varchar(10) DEFAULT NULL,
  `aciklama` varchar(1000) DEFAULT NULL,
  `Durum` varchar(10) DEFAULT NULL,
  `PersonelID` varchar(10) DEFAULT NULL,
  `OlusturmaTarihi` varchar(10) DEFAULT NULL,
  `Duzenleyen` varchar(10) DEFAULT NULL,
  `DuzenlemeTarihi` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=387 DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `project_gelir_gider`
--

DROP TABLE IF EXISTS `project_gelir_gider`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_gelir_gider` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firm_id` int(11) NOT NULL DEFAULT 0,
  `case_id` int(11) NOT NULL DEFAULT 0,
  `project_id` int(11) DEFAULT NULL,
  `tarih` varchar(50) DEFAULT NULL,
  `ay` int(11) DEFAULT NULL,
  `yil` int(11) DEFAULT NULL,
  `tutar` double DEFAULT NULL,
  `kategori` int(11) DEFAULT NULL,
  `turu` varchar(50) DEFAULT NULL,
  `aciklama` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=72 DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `project_person`
--

DROP TABLE IF EXISTS `project_person`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_person` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) DEFAULT NULL,
  `person_id` int(11) DEFAULT NULL,
  `state` int(11) DEFAULT NULL,
  `create_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=412 DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` int(2) NOT NULL,
  `account_id` int(255) NOT NULL,
  `company_id` int(11) DEFAULT NULL,
  `firm_id` int(255) DEFAULT NULL,
  `project_name` varchar(255) DEFAULT NULL,
  `budget` double DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `city` int(4) DEFAULT NULL,
  `town` int(6) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `account_number` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `start_date` varchar(10) DEFAULT NULL,
  `end_date` varchar(10) DEFAULT NULL,
  `project_file` varchar(255) DEFAULT NULL,
  `creator` varchar(255) DEFAULT NULL,
  `created_at` date NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=86 DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `puantaj`
--

DROP TABLE IF EXISTS `puantaj`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `puantaj` (
  `id` int(20) NOT NULL AUTO_INCREMENT,
  `company_id` int(255) NOT NULL,
  `project_id` int(255) NOT NULL,
  `person` varchar(255) NOT NULL,
  `puantaj_id` int(1) DEFAULT 1,
  `gun` varchar(50) DEFAULT NULL,
  `saat` decimal(4,2) DEFAULT NULL,
  `tutar` decimal(20,2) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` varchar(30) NOT NULL DEFAULT current_timestamp(),
  `updated_at` varchar(30) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4079 DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `puantajturu`
--

DROP TABLE IF EXISTS `puantajturu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `puantajturu` (
  `id` int(2) NOT NULL AUTO_INCREMENT,
  `PuantajAdi` varchar(55) DEFAULT NULL,
  `PuantajKod` varchar(6) DEFAULT NULL,
  `PuantajSaati` double DEFAULT NULL,
  `operant` varchar(1) DEFAULT NULL,
  `EklenecekSaat` double(4,2) DEFAULT NULL,
  `Turu` varchar(14) DEFAULT NULL,
  `FontRengi` varchar(7) DEFAULT NULL,
  `ArkaPlanRengi` varchar(7) DEFAULT NULL,
  `isActive` int(1) DEFAULT NULL,
  `IzinRapor` int(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `report_ysc_content`
--

DROP TABLE IF EXISTS `report_ysc_content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `report_ysc_content` (
  `id` int(50) NOT NULL AUTO_INCREMENT,
  `report_id` int(20) NOT NULL,
  `cihaz_no` varchar(50) DEFAULT NULL,
  `bulundugu_bolge` varchar(100) DEFAULT NULL,
  `cinsi` varchar(255) DEFAULT NULL,
  `cihaz_dolum_tarihi` varchar(20) DEFAULT NULL,
  `cihaz_sonkullanma_tarihi` varchar(20) DEFAULT NULL,
  `kontrol_tarihi_1` varchar(20) DEFAULT NULL,
  `kontrol_tarihi_2` varchar(20) DEFAULT NULL,
  `islem_kontrol_tarihi_1` varchar(20) DEFAULT NULL,
  `islem_kontrol_tarihi_2` varchar(20) DEFAULT NULL,
  `dis_muhafaza` varchar(2) DEFAULT NULL,
  `cevre_kontrolu` varchar(2) DEFAULT NULL,
  `pim_kontrolu` varchar(2) DEFAULT NULL,
  `manometre_kontrolu` varchar(2) DEFAULT NULL,
  `hortum_kontrolu` varchar(2) DEFAULT NULL,
  `talimat_kontrolu` varchar(2) DEFAULT NULL,
  `agirlik_kontrolu` varchar(2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4040 DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `reports`
--

DROP TABLE IF EXISTS `reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reports` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `report_number` varchar(50) DEFAULT NULL,
  `report_type` varchar(255) NOT NULL,
  `isemrino` varchar(255) NOT NULL,
  `last_control_date` varchar(20) NOT NULL,
  `control_date` varchar(30) NOT NULL,
  `next_control_date` varchar(20) NOT NULL,
  `control_period` varchar(30) NOT NULL,
  `validity_date` varchar(30) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `standarts` text DEFAULT NULL,
  `equipments` text DEFAULT NULL,
  `warnings` text DEFAULT NULL,
  `notes` text NOT NULL,
  `subNotes` text DEFAULT NULL,
  `controller_id` int(100) NOT NULL,
  `company_official` int(100) NOT NULL,
  `test_date` varchar(20) NOT NULL,
  `servis_no` varchar(50) NOT NULL,
  `report_matters` longtext DEFAULT NULL CHECK (json_valid(`report_matters`)),
  `bakim_bilgileri` longtext DEFAULT NULL,
  `dedektor_info` longtext DEFAULT NULL CHECK (json_valid(`dedektor_info`)),
  `oys_general_matters` longtext DEFAULT NULL,
  `controller_peak_info` longtext NOT NULL,
  `creator` int(10) NOT NULL,
  `create_time` varchar(20) NOT NULL DEFAULT current_timestamp(),
  `updater` int(20) NOT NULL,
  `update_time` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=190 DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `role_auths`
--

DROP TABLE IF EXISTS `role_auths`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_auths` (
  `id` int(25) NOT NULL AUTO_INCREMENT,
  `role_id` int(10) NOT NULL,
  `auth_ids` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=154 DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firm_id` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `set_name` varchar(100) DEFAULT NULL,
  `set_value` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `sql_case_transactions`
--

DROP TABLE IF EXISTS `sql_case_transactions`;
/*!50001 DROP VIEW IF EXISTS `sql_case_transactions`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `sql_case_transactions` AS SELECT
 1 AS `id`,
  1 AS `type_id`,
  1 AS `date`,
  1 AS `case_id`,
  1 AS `project_id`,
  1 AS `person_id`,
  1 AS `company_id`,
  1 AS `sub_type`,
  1 AS `users_type_id`,
  1 AS `amount`,
  1 AS `amount_money`,
  1 AS `account_name`,
  1 AS `description`,
  1 AS `created_at`,
  1 AS `tablename` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `sql_project_gelir_gider`
--

DROP TABLE IF EXISTS `sql_project_gelir_gider`;
/*!50001 DROP VIEW IF EXISTS `sql_project_gelir_gider`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `sql_project_gelir_gider` AS SELECT
 1 AS `id`,
  1 AS `tarih`,
  1 AS `ay`,
  1 AS `yil`,
  1 AS `kategori`,
  1 AS `turu`,
  1 AS `tutar`,
  1 AS `project_id`,
  1 AS `aciklama`,
  1 AS `created_at`,
  1 AS `tablename` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `sqlmaas_gelir_kesinti`
--

DROP TABLE IF EXISTS `sqlmaas_gelir_kesinti`;
/*!50001 DROP VIEW IF EXISTS `sqlmaas_gelir_kesinti`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `sqlmaas_gelir_kesinti` AS SELECT
 1 AS `id`,
  1 AS `person_id`,
  1 AS `turu`,
  1 AS `kategori`,
  1 AS `ay`,
  1 AS `yil`,
  1 AS `person`,
  1 AS `puantaj_turu`,
  1 AS `gun`,
  1 AS `saat`,
  1 AS `tutar`,
  1 AS `aciklama`,
  1 AS `created_at`,
  1 AS `tablename` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `sqlmaas_gelir_kesinti_puantaj_toplam`
--

DROP TABLE IF EXISTS `sqlmaas_gelir_kesinti_puantaj_toplam`;
/*!50001 DROP VIEW IF EXISTS `sqlmaas_gelir_kesinti_puantaj_toplam`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `sqlmaas_gelir_kesinti_puantaj_toplam` AS SELECT
 1 AS `id`,
  1 AS `person_id`,
  1 AS `turu`,
  1 AS `kategori`,
  1 AS `ay`,
  1 AS `yil`,
  1 AS `person`,
  1 AS `puantaj_turu`,
  1 AS `gun`,
  1 AS `saat`,
  1 AS `tutar`,
  1 AS `aciklama`,
  1 AS `created_at`,
  1 AS `tablename` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `teams`
--

DROP TABLE IF EXISTS `teams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firm_id` int(11) DEFAULT NULL,
  `team_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `todos`
--

DROP TABLE IF EXISTS `todos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `todos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firm_id` int(11) NOT NULL DEFAULT 0,
  `project_id` int(11) NOT NULL DEFAULT 0,
  `subject` varchar(150) NOT NULL DEFAULT '0',
  `title` varchar(150) NOT NULL DEFAULT '0',
  `description` varchar(150) NOT NULL DEFAULT '0',
  `status` varchar(50) NOT NULL DEFAULT '0',
  `due_date` varchar(50) NOT NULL,
  `created_at` varchar(50) NOT NULL DEFAULT 'current_timestamp()',
  `updated_at` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `userroles`
--

DROP TABLE IF EXISTS `userroles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `userroles` (
  `id` int(25) NOT NULL AUTO_INCREMENT,
  `firm_id` int(25) NOT NULL DEFAULT 0,
  `roleName` varchar(255) NOT NULL,
  `roleDescription` varchar(255) NOT NULL,
  `isActive` int(1) NOT NULL,
  `main_role` int(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=157 DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_type` int(11) DEFAULT 1,
  `parent_id` int(11) NOT NULL DEFAULT 0,
  `firm_id` int(11) NOT NULL DEFAULT 0,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `job` varchar(50) DEFAULT NULL,
  `title` varchar(50) DEFAULT NULL,
  `user_roles` int(11) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `is_main_user` int(1) DEFAULT NULL,
  `sicil_no` varchar(50) DEFAULT NULL,
  `yetkinlik_no` varchar(50) DEFAULT NULL,
  `session_token` varchar(255) DEFAULT NULL,
  `activate_token` varchar(255) DEFAULT NULL,
  `remember_token` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `responsible_persons` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`,`firm_id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=250 DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Final view structure for view `sql_case_transactions`
--

/*!50001 DROP VIEW IF EXISTS `sql_case_transactions`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `sql_case_transactions` AS select `ct`.`id` AS `id`,`ct`.`type_id` AS `type_id`,`ct`.`date` AS `date`,`ct`.`case_id` AS `case_id`,`ct`.`project_id` AS `project_id`,`ct`.`person_id` AS `person_id`,`ct`.`company_id` AS `company_id`,`ct`.`sub_type` AS `sub_type`,`ct`.`users_type_id` AS `users_type_id`,`ct`.`amount` AS `amount`,`ct`.`amount_money` AS `amount_money`,case when `ct`.`project_id` > 0 then (select `p`.`project_name` from `projects` `p` where `p`.`id` = `ct`.`project_id`) when `ct`.`person_id` > 0 then (select `pr`.`full_name` from `persons` `pr` where `pr`.`id` = `ct`.`person_id`) when `ct`.`company_id` > 0 then (select `c`.`company_name` from `companies` `c` where `c`.`id` = `ct`.`company_id`) else `ct`.`account_name` end AS `account_name`,`ct`.`description` AS `description`,`ct`.`created_at` AS `created_at`,'case_transactions' AS `tablename` from `case_transactions` `ct` union all select `mgk`.`id` AS `id`,2 AS `type_id`,`mgk`.`gun` AS `date`,`mgk`.`case_id` AS `case_id`,`mgk`.`project_id` AS `project_id`,`mgk`.`person_id` AS `person_id`,'' AS `Name_exp_7`,`mgk`.`kategori` AS `sub_type`,0 AS `users_type_id`,`mgk`.`tutar` AS `amount`,1 AS `amount_type`,case when `mgk`.`project_id` > 0 then (select `p`.`project_name` from `projects` `p` where `p`.`id` = `mgk`.`project_id`) when `mgk`.`person_id` > 0 then (select `pr`.`full_name` from `persons` `pr` where `pr`.`id` = `mgk`.`person_id`) else '' end AS `account_name`,`mgk`.`aciklama` AS `description`,`mgk`.`created_at` AS `created_at`,'maas_gelir_kesinti' AS `tablename` from (`maas_gelir_kesinti` `mgk` left join `persons` `p` on(`p`.`id` = `mgk`.`person_id`)) where `mgk`.`kategori` = 7 union all select `pg`.`id` AS `id`,case when `pg`.`turu` in (5,12) then 2 when `pg`.`turu` = 10 then 1 else 0 end AS `type_id`,`pg`.`tarih` AS `tarih`,`pg`.`case_id` AS `case_id`,`pg`.`project_id` AS `project_id`,0 AS `person_id`,0 AS `company_id`,`pg`.`turu` AS `turu`,0 AS `users_type_id`,`pg`.`tutar` AS `tutar`,1 AS `amount_type`,case when `pg`.`project_id` > 0 then (select `p`.`project_name` from `projects` `p` where `p`.`id` = `pg`.`project_id`) else '' end AS `account_name`,`pg`.`aciklama` AS `aciklama`,`pg`.`created_at` AS `created_at`,'project_gelir_gider' AS `tablename` from (`project_gelir_gider` `pg` left join `projects` `p` on(`p`.`id` = `pg`.`project_id`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `sql_project_gelir_gider`
--

/*!50001 DROP VIEW IF EXISTS `sql_project_gelir_gider`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `sql_project_gelir_gider` AS select `ct`.`id` AS `id`,`ct`.`date` AS `tarih`,'' AS `ay`,'' AS `yil`,'' AS `kategori`,`ct`.`sub_type` AS `turu`,`ct`.`amount` AS `tutar`,`ct`.`project_id` AS `project_id`,`ct`.`description` AS `aciklama`,`ct`.`created_at` AS `created_at`,'case_transaction' AS `tablename` from `case_transactions` `ct` union all select `pg`.`id` AS `id`,`pg`.`tarih` AS `tarih`,`pg`.`ay` AS `ay`,`pg`.`yil` AS `yil`,`pg`.`kategori` AS `kategori`,`pg`.`turu` AS `turu`,`pg`.`tutar` AS `tutar`,`pg`.`project_id` AS `project_id`,`pg`.`aciklama` AS `aciklama`,`pg`.`created_at` AS `created_at`,'project_gelir_gider' AS `tablename` from `project_gelir_gider` `pg` union all select '' AS `id`,'' AS `gun`,'' AS `ay`,'' AS `yil`,'' AS `kategori`,14 AS `turu`,sum(`pt`.`tutar`) AS `sum(pt.tutar)`,`pt`.`project_id` AS `project_id`,'Proje Toplam Çalışma' AS `description`,'' AS `created_at`,'puantaj' AS `tablename` from (`puantaj` `pt` left join `persons` `p` on(`p`.`id` = `pt`.`person`)) group by `pt`.`project_id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `sqlmaas_gelir_kesinti`
--

/*!50001 DROP VIEW IF EXISTS `sqlmaas_gelir_kesinti`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `sqlmaas_gelir_kesinti` AS select '' AS `id`,`pt`.`person` AS `person_id`,'Puantaj Çalışma' AS `turu`,14 AS `kategori`,'' AS `ay`,'' AS `yil`,`pt`.`person` AS `person`,'' AS `puantaj_turu`,`pt`.`gun` AS `gun`,coalesce(`pt`.`saat`,0) AS `saat`,coalesce(`pt`.`tutar`,0) AS `tutar`,'' AS `aciklama`,'' AS `created_at`,'puantaj' AS `tablename` from `puantaj` `pt` union all select `mgk`.`id` AS `id`,`mgk`.`person_id` AS `person_id`,`mgk`.`turu` AS `turu`,`mgk`.`kategori` AS `kategori`,`mgk`.`ay` AS `ay`,`mgk`.`yil` AS `yil`,`mgk`.`person_id` AS `person`,'' AS `pt_turu`,`mgk`.`gun` AS `gun`,'' AS `saat`,`mgk`.`tutar` AS `tutar`,`mgk`.`aciklama` AS `aciklama`,`mgk`.`created_at` AS `created_at`,'maas_gelir_kesinti' AS `tablename` from `maas_gelir_kesinti` `mgk` where `mgk`.`person_id` > 0 union all select `ct`.`id` AS `id`,`ct`.`person_id` AS `person_id`,'Personel Ödemesi' AS `turu`,`ct`.`users_type_id` AS `kategori`,'' AS `ay`,'' AS `yil`,`ct`.`person_id` AS `person`,0 AS `puantaj_turu`,`ct`.`date` AS `gun`,'' AS `saat`,`ct`.`amount` AS `tutar`,'' AS `aciklama`,`ct`.`created_at` AS `created_at`,'case_transactions' AS `tablename` from `case_transactions` `ct` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `sqlmaas_gelir_kesinti_puantaj_toplam`
--

/*!50001 DROP VIEW IF EXISTS `sqlmaas_gelir_kesinti_puantaj_toplam`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `sqlmaas_gelir_kesinti_puantaj_toplam` AS select '' AS `id`,`pt`.`person` AS `person_id`,'Puantaj Çalışma' AS `turu`,14 AS `kategori`,'' AS `ay`,'' AS `yil`,`pt`.`person` AS `person`,'' AS `puantaj_turu`,'' AS `gun`,coalesce(sum(`pt`.`saat`),0) AS `saat`,coalesce(sum(`pt`.`tutar`),0) AS `tutar`,'' AS `aciklama`,'' AS `created_at`,'puantaj' AS `tablename` from `puantaj` `pt` group by `pt`.`person` union all select `mgk`.`id` AS `id`,`mgk`.`person_id` AS `person_id`,`mgk`.`turu` AS `turu`,`mgk`.`kategori` AS `kategori`,`mgk`.`ay` AS `ay`,`mgk`.`yil` AS `yil`,`mgk`.`person_id` AS `person`,'' AS `pt_turu`,`mgk`.`gun` AS `gun`,'' AS `saat`,`mgk`.`tutar` AS `tutar`,`mgk`.`aciklama` AS `aciklama`,`mgk`.`created_at` AS `created_at`,'maas_gelir_kesinti' AS `tablename` from `maas_gelir_kesinti` `mgk` where `mgk`.`person_id` > 0 group by `mgk`.`person_id`,`mgk`.`kategori` union all select `ct`.`id` AS `id`,`ct`.`person_id` AS `person_id`,`d`.`name` AS `turu`,'' AS `kategori`,'' AS `ay`,'' AS `yil`,`ct`.`person_id` AS `person`,0 AS `puantaj_turu`,`ct`.`date` AS `gun`,'' AS `saat`,`ct`.`amount` AS `tutar`,'' AS `aciklama`,`ct`.`created_at` AS `created_at`,'case_transactions' AS `tablename` from (`case_transactions` `ct` left join `defines` `d` on(`d`.`id` = `ct`.`users_type_id`)) where `ct`.`person_id` > 0 */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-10 13:09:19
