-- 2026 yılı sistem geneli resmi tatil tanımlamaları
-- Önce holiday_work_rules.sql geçişi uygulanmalıdır.

INSERT INTO `national_holidays` (`holiday_name`, `holiday_date`, `description`)
SELECT 'Yılbaşı', '2026-01-01', 'Yılbaşı Tatili'
WHERE NOT EXISTS (SELECT 1 FROM `national_holidays` WHERE `holiday_date` = '2026-01-01');

INSERT INTO `national_holidays` (`holiday_name`, `holiday_date`, `description`)
SELECT 'Ramazan Bayramı Arifesi (Yarım Gün)', '2026-03-19', 'Ramazan Bayramı Arifesi - Yarım Gün Tatili'
WHERE NOT EXISTS (SELECT 1 FROM `national_holidays` WHERE `holiday_date` = '2026-03-19');

INSERT INTO `national_holidays` (`holiday_name`, `holiday_date`, `description`)
SELECT 'Ramazan Bayramı 1. Gün', '2026-03-20', 'Ramazan Bayramı 1. Gün Tatili'
WHERE NOT EXISTS (SELECT 1 FROM `national_holidays` WHERE `holiday_date` = '2026-03-20');

INSERT INTO `national_holidays` (`holiday_name`, `holiday_date`, `description`)
SELECT 'Ramazan Bayramı 2. Gün', '2026-03-21', 'Ramazan Bayramı 2. Gün Tatili'
WHERE NOT EXISTS (SELECT 1 FROM `national_holidays` WHERE `holiday_date` = '2026-03-21');

INSERT INTO `national_holidays` (`holiday_name`, `holiday_date`, `description`)
SELECT 'Ramazan Bayramı 3. Gün', '2026-03-22', 'Ramazan Bayramı 3. Gün Tatili'
WHERE NOT EXISTS (SELECT 1 FROM `national_holidays` WHERE `holiday_date` = '2026-03-22');

INSERT INTO `national_holidays` (`holiday_name`, `holiday_date`, `description`)
SELECT 'Ulusal Egemenlik ve Çocuk Bayramı', '2026-04-23', '23 Nisan Ulusal Egemenlik ve Çocuk Bayramı'
WHERE NOT EXISTS (SELECT 1 FROM `national_holidays` WHERE `holiday_date` = '2026-04-23');

INSERT INTO `national_holidays` (`holiday_name`, `holiday_date`, `description`)
SELECT 'Emek ve Dayanışma Günü', '2026-05-01', '1 Mayıs Emek ve Dayanışma Günü'
WHERE NOT EXISTS (SELECT 1 FROM `national_holidays` WHERE `holiday_date` = '2026-05-01');

INSERT INTO `national_holidays` (`holiday_name`, `holiday_date`, `description`)
SELECT 'Atatürk\'ü Anma, Gençlik ve Spor Bayramı', '2026-05-19', '19 Mayıs Atatürk\'ü Anma, Gençlik ve Spor Bayramı'
WHERE NOT EXISTS (SELECT 1 FROM `national_holidays` WHERE `holiday_date` = '2026-05-19');

INSERT INTO `national_holidays` (`holiday_name`, `holiday_date`, `description`)
SELECT 'Kurban Bayramı Arifesi (Yarım Gün)', '2026-05-26', 'Kurban Bayramı Arifesi - Yarım Gün Tatili'
WHERE NOT EXISTS (SELECT 1 FROM `national_holidays` WHERE `holiday_date` = '2026-05-26');

INSERT INTO `national_holidays` (`holiday_name`, `holiday_date`, `description`)
SELECT 'Kurban Bayramı 1. Gün', '2026-05-27', 'Kurban Bayramı 1. Gün Tatili'
WHERE NOT EXISTS (SELECT 1 FROM `national_holidays` WHERE `holiday_date` = '2026-05-27');

INSERT INTO `national_holidays` (`holiday_name`, `holiday_date`, `description`)
SELECT 'Kurban Bayramı 2. Gün', '2026-05-28', 'Kurban Bayramı 2. Gün Tatili'
WHERE NOT EXISTS (SELECT 1 FROM `national_holidays` WHERE `holiday_date` = '2026-05-28');

INSERT INTO `national_holidays` (`holiday_name`, `holiday_date`, `description`)
SELECT 'Kurban Bayramı 3. Gün', '2026-05-29', 'Kurban Bayramı 3. Gün Tatili'
WHERE NOT EXISTS (SELECT 1 FROM `national_holidays` WHERE `holiday_date` = '2026-05-29');

INSERT INTO `national_holidays` (`holiday_name`, `holiday_date`, `description`)
SELECT 'Kurban Bayramı 4. Gün', '2026-05-30', 'Kurban Bayramı 4. Gün Tatili'
WHERE NOT EXISTS (SELECT 1 FROM `national_holidays` WHERE `holiday_date` = '2026-05-30');

INSERT INTO `national_holidays` (`holiday_name`, `holiday_date`, `description`)
SELECT 'Demokrasi ve Millî Birlik Günü', '2026-07-15', '15 Temmuz Demokrasi ve Millî Birlik Günü'
WHERE NOT EXISTS (SELECT 1 FROM `national_holidays` WHERE `holiday_date` = '2026-07-15');

INSERT INTO `national_holidays` (`holiday_name`, `holiday_date`, `description`)
SELECT 'Zafer Bayramı', '2026-08-30', '30 Ağustos Zafer Bayramı'
WHERE NOT EXISTS (SELECT 1 FROM `national_holidays` WHERE `holiday_date` = '2026-08-30');

INSERT INTO `national_holidays` (`holiday_name`, `holiday_date`, `description`)
SELECT 'Cumhuriyet Bayramı Arifesi (Yarım Gün)', '2026-10-28', 'Cumhuriyet Bayramı Arifesi - Yarım Gün Tatili'
WHERE NOT EXISTS (SELECT 1 FROM `national_holidays` WHERE `holiday_date` = '2026-10-28');

INSERT INTO `national_holidays` (`holiday_name`, `holiday_date`, `description`)
SELECT 'Cumhuriyet Bayramı', '2026-10-29', '29 Ekim Cumhuriyet Bayramı'
WHERE NOT EXISTS (SELECT 1 FROM `national_holidays` WHERE `holiday_date` = '2026-10-29');

UPDATE `national_holidays`
SET `holiday_type` = CASE
        WHEN `holiday_name` LIKE '%Ramazan%' OR `holiday_name` LIKE '%Kurban%' THEN 'religious'
        ELSE 'national'
    END,
    `day_ratio` = CASE
        WHEN `holiday_name` LIKE '%Yarım Gün%' OR `holiday_name` LIKE '%Arife%' THEN 0.50
        ELSE 1.00
    END
WHERE YEAR(`holiday_date`) = 2026;
