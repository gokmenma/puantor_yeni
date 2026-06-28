-- 1. Create national_holidays table
CREATE TABLE IF NOT EXISTS `national_holidays` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firm_id` int(11) NOT NULL DEFAULT 0,
  `holiday_name` varchar(255) NOT NULL,
  `holiday_date` date NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` varchar(20) NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin5 COLLATE=latin5_turkish_ci;

-- 2. Insert Menu Entries
-- Get parent menu id for 'Tanımlamalar' dynamically
SET @parent_menu_id = (SELECT `id` FROM `menu` WHERE `page_name` = 'Tanımlamalar' AND `parent_id` = 0 LIMIT 1);

-- Resmi Tatil Tanımlama list page menu
INSERT INTO `menu` (`page_name`, `page_link`, `icon`, `parent_id`, `isActive`, `isMenu`, `index_no`, `is_authorize`)
SELECT 'Resmi Tatil Tanımlama', 'defines/national-holidays/list', 'calendar-event', @parent_menu_id, 1, 1, 10, 1
WHERE NOT EXISTS (
    SELECT 1 FROM `menu` WHERE `page_link` = 'defines/national-holidays/list'
);

-- Resmi Tatil Ekle/Güncelle manage page menu
INSERT INTO `menu` (`page_name`, `page_link`, `icon`, `parent_id`, `isActive`, `isMenu`, `index_no`, `is_authorize`)
SELECT 'Resmi Tatil Ekle/Güncelle', 'defines/national-holidays/manage', '0', @parent_menu_id, 1, 0, NULL, 1
WHERE NOT EXISTS (
    SELECT 1 FROM `menu` WHERE `page_link` = 'defines/national-holidays/manage'
);

-- Puantaj Türü Tanımlama list page menu
INSERT INTO `menu` (`page_name`, `page_link`, `icon`, `parent_id`, `isActive`, `isMenu`, `index_no`, `is_authorize`)
SELECT 'Puantaj Türü Tanımlama', 'defines/timesheet-types/list', 'calendar-time', @parent_menu_id, 1, 1, 11, 1
WHERE NOT EXISTS (
    SELECT 1 FROM `menu` WHERE `page_link` = 'defines/timesheet-types/list'
);

-- Puantaj Türü Ekle/Güncelle manage page menu
INSERT INTO `menu` (`page_name`, `page_link`, `icon`, `parent_id`, `isActive`, `isMenu`, `index_no`, `is_authorize`)
SELECT 'Puantaj Türü Ekle/Güncelle', 'defines/timesheet-types/manage', '0', @parent_menu_id, 1, 0, NULL, 1
WHERE NOT EXISTS (
    SELECT 1 FROM `menu` WHERE `page_link` = 'defines/timesheet-types/manage'
);

-- 3. Insert Auths (Permissions)
-- Get parent auth id for 'definitions_page' dynamically
SET @parent_auth_id = (SELECT `id` FROM `auths` WHERE `auth_name` = 'definitions_page' LIMIT 1);

INSERT INTO `auths` (`title`, `auth_name`, `description`, `parent_id`, `is_active`) 
SELECT 'Resmi Tatil Tanımlama', 'national_holidays_list', 'Resmi tatil tanımlama listesini görüntüleme yetkisi.', @parent_auth_id, 1
WHERE NOT EXISTS (
    SELECT 1 FROM `auths` WHERE `auth_name` = 'national_holidays_list'
);

INSERT INTO `auths` (`title`, `auth_name`, `description`, `parent_id`, `is_active`) 
SELECT 'Resmi Tatil Ekle/Güncelle', 'national_holidays_add_update', 'Resmi tatil ekleme ve güncelleme yetkisi.', @parent_auth_id, 1
WHERE NOT EXISTS (
    SELECT 1 FROM `auths` WHERE `auth_name` = 'national_holidays_add_update'
);

INSERT INTO `auths` (`title`, `auth_name`, `description`, `parent_id`, `is_active`) 
SELECT 'Puantaj Türü Tanımlama', 'timesheet_types_list', 'Puantaj türü tanımlama listesini görüntüleme yetkisi.', @parent_auth_id, 1
WHERE NOT EXISTS (
    SELECT 1 FROM `auths` WHERE `auth_name` = 'timesheet_types_list'
);

INSERT INTO `auths` (`title`, `auth_name`, `description`, `parent_id`, `is_active`) 
SELECT 'Puantaj Türü Ekle/Güncelle', 'timesheet_types_add_update', 'Puantaj türü ekleme ve güncelleme yetkisi.', @parent_auth_id, 1
WHERE NOT EXISTS (
    SELECT 1 FROM `auths` WHERE `auth_name` = 'timesheet_types_add_update'
);
