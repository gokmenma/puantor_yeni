-- Global resmi tatil sınıfları ve firma bazlı tatil çalışma kuralları

ALTER TABLE `national_holidays`
    ADD COLUMN `holiday_type` ENUM('national', 'religious', 'other') NOT NULL DEFAULT 'national' AFTER `holiday_date`,
    ADD COLUMN `day_ratio` DECIMAL(3,2) NOT NULL DEFAULT 1.00 AFTER `holiday_type`,
    ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `day_ratio`,
    DROP COLUMN `firm_id`;

UPDATE `national_holidays`
SET `holiday_type` = CASE
        WHEN `holiday_name` LIKE '%Ramazan%' OR `holiday_name` LIKE '%Kurban%' THEN 'religious'
        ELSE 'national'
    END,
    `day_ratio` = CASE
        WHEN `holiday_name` LIKE '%Yarım Gün%' OR `holiday_name` LIKE '%Arife%' THEN 0.50
        ELSE 1.00
    END;

ALTER TABLE `puantajturu`
    ADD COLUMN `counts_as_work` TINYINT(1) NOT NULL DEFAULT 0 AFTER `personel_gorsun`;

UPDATE `puantajturu`
SET `counts_as_work` = CASE
    WHEN `Turu` IN ('Normal Çalışma', 'Fazla Çalışma', 'Saatlik') THEN 1
    ELSE 0
END;

CREATE TABLE `holiday_work_policies` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `firm_id` INT(11) NOT NULL,
    `holiday_type` ENUM('national', 'religious', 'other') NOT NULL,
    `additional_day_rate` DECIMAL(4,2) NOT NULL DEFAULT 0.00,
    `calculation_basis` ENUM('pro_rata', 'full_day') NOT NULL DEFAULT 'pro_rata',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_holiday_work_policy_firm_type` (`firm_id`, `holiday_type`),
    KEY `idx_holiday_work_policy_firm` (`firm_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

