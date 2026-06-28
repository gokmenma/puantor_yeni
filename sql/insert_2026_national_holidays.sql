-- 2026 yılı Resmi Tatil Tanımlamaları (firm_id = 186 için)

INSERT INTO `national_holidays` (`firm_id`, `holiday_name`, `holiday_date`, `description`)
SELECT 186, 'Yılbaşı', '2026-01-01', 'Yılbaşı Tatili'
WHERE NOT EXISTS (SELECT 1 FROM `national_holidays` WHERE `firm_id` = 186 AND `holiday_date` = '2026-01-01');

INSERT INTO `national_holidays` (`firm_id`, `holiday_name`, `holiday_date`, `description`)
SELECT 186, 'Ramazan Bayramı Arifesi (Yarım Gün)', '2026-03-19', 'Ramazan Bayramı Arifesi - Yarım Gün Tatili'
WHERE NOT EXISTS (SELECT 1 FROM `national_holidays` WHERE `firm_id` = 186 AND `holiday_date` = '2026-03-19');

INSERT INTO `national_holidays` (`firm_id`, `holiday_name`, `holiday_date`, `description`)
SELECT 186, 'Ramazan Bayramı 1. Gün', '2026-03-20', 'Ramazan Bayramı 1. Gün Tatili'
WHERE NOT EXISTS (SELECT 1 FROM `national_holidays` WHERE `firm_id` = 186 AND `holiday_date` = '2026-03-20');

INSERT INTO `national_holidays` (`firm_id`, `holiday_name`, `holiday_date`, `description`)
SELECT 186, 'Ramazan Bayramı 2. Gün', '2026-03-21', 'Ramazan Bayramı 2. Gün Tatili'
WHERE NOT EXISTS (SELECT 1 FROM `national_holidays` WHERE `firm_id` = 186 AND `holiday_date` = '2026-03-21');

INSERT INTO `national_holidays` (`firm_id`, `holiday_name`, `holiday_date`, `description`)
SELECT 186, 'Ramazan Bayramı 3. Gün', '2026-03-22', 'Ramazan Bayramı 3. Gün Tatili'
WHERE NOT EXISTS (SELECT 1 FROM `national_holidays` WHERE `firm_id` = 186 AND `holiday_date` = '2026-03-22');

INSERT INTO `national_holidays` (`firm_id`, `holiday_name`, `holiday_date`, `description`)
SELECT 186, 'Ulusal Egemenlik ve Çocuk Bayramı', '2026-04-23', '23 Nisan Ulusal Egemenlik ve Çocuk Bayramı'
WHERE NOT EXISTS (SELECT 1 FROM `national_holidays` WHERE `firm_id` = 186 AND `holiday_date` = '2026-04-23');

INSERT INTO `national_holidays` (`firm_id`, `holiday_name`, `holiday_date`, `description`)
SELECT 186, 'Emek ve Dayanışma Günü', '2026-05-01', '1 Mayıs Emek ve Dayanışma Günü'
WHERE NOT EXISTS (SELECT 1 FROM `national_holidays` WHERE `firm_id` = 186 AND `holiday_date` = '2026-05-01');

INSERT INTO `national_holidays` (`firm_id`, `holiday_name`, `holiday_date`, `description`)
SELECT 186, 'Atatürk\'ü Anma, Gençlik ve Spor Bayramı', '2026-05-19', '19 Mayıs Atatürk\'ü Anma, Gençlik ve Spor Bayramı'
WHERE NOT EXISTS (SELECT 1 FROM `national_holidays` WHERE `firm_id` = 186 AND `holiday_date` = '2026-05-19');

INSERT INTO `national_holidays` (`firm_id`, `holiday_name`, `holiday_date`, `description`)
SELECT 186, 'Kurban Bayramı Arifesi (Yarım Gün)', '2026-05-26', 'Kurban Bayramı Arifesi - Yarım Gün Tatili'
WHERE NOT EXISTS (SELECT 1 FROM `national_holidays` WHERE `firm_id` = 186 AND `holiday_date` = '2026-05-26');

INSERT INTO `national_holidays` (`firm_id`, `holiday_name`, `holiday_date`, `description`)
SELECT 186, 'Kurban Bayramı 1. Gün', '2026-05-27', 'Kurban Bayramı 1. Gün Tatili'
WHERE NOT EXISTS (SELECT 1 FROM `national_holidays` WHERE `firm_id` = 186 AND `holiday_date` = '2026-05-27');

INSERT INTO `national_holidays` (`firm_id`, `holiday_name`, `holiday_date`, `description`)
SELECT 186, 'Kurban Bayramı 2. Gün', '2026-05-28', 'Kurban Bayramı 2. Gün Tatili'
WHERE NOT EXISTS (SELECT 1 FROM `national_holidays` WHERE `firm_id` = 186 AND `holiday_date` = '2026-05-28');

INSERT INTO `national_holidays` (`firm_id`, `holiday_name`, `holiday_date`, `description`)
SELECT 186, 'Kurban Bayramı 3. Gün', '2026-05-29', 'Kurban Bayramı 3. Gün Tatili'
WHERE NOT EXISTS (SELECT 1 FROM `national_holidays` WHERE `firm_id` = 186 AND `holiday_date` = '2026-05-29');

INSERT INTO `national_holidays` (`firm_id`, `holiday_name`, `holiday_date`, `description`)
SELECT 186, 'Kurban Bayramı 4. Gün', '2026-05-30', 'Kurban Bayramı 4. Gün Tatili'
WHERE NOT EXISTS (SELECT 1 FROM `national_holidays` WHERE `firm_id` = 186 AND `holiday_date` = '2026-05-30');

INSERT INTO `national_holidays` (`firm_id`, `holiday_name`, `holiday_date`, `description`)
SELECT 186, 'Demokrasi ve Millî Birlik Günü', '2026-07-15', '15 Temmuz Demokrasi ve Millî Birlik Günü'
WHERE NOT EXISTS (SELECT 1 FROM `national_holidays` WHERE `firm_id` = 186 AND `holiday_date` = '2026-07-15');

INSERT INTO `national_holidays` (`firm_id`, `holiday_name`, `holiday_date`, `description`)
SELECT 186, 'Zafer Bayramı', '2026-08-30', '30 Ağustos Zafer Bayramı'
WHERE NOT EXISTS (SELECT 1 FROM `national_holidays` WHERE `firm_id` = 186 AND `holiday_date` = '2026-08-30');

INSERT INTO `national_holidays` (`firm_id`, `holiday_name`, `holiday_date`, `description`)
SELECT 186, 'Cumhuriyet Bayramı Arifesi (Yarım Gün)', '2026-10-28', 'Cumhuriyet Bayramı Arifesi - Yarım Gün Tatili'
WHERE NOT EXISTS (SELECT 1 FROM `national_holidays` WHERE `firm_id` = 186 AND `holiday_date` = '2026-10-28');

INSERT INTO `national_holidays` (`firm_id`, `holiday_name`, `holiday_date`, `description`)
SELECT 186, 'Cumhuriyet Bayramı', '2026-10-29', '29 Ekim Cumhuriyet Bayramı'
WHERE NOT EXISTS (SELECT 1 FROM `national_holidays` WHERE `firm_id` = 186 AND `holiday_date` = '2026-10-29');
