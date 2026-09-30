-- ==========================================================
-- User DataTable States Table
-- Kullanıcı bazlı DataTable kolon sıralaması, görünürlüğü ve genişlik ayarları
-- ==========================================================

CREATE TABLE IF NOT EXISTS `user_datatable_states` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `firm_id` INT NULL DEFAULT NULL,
    `table_key` VARCHAR(150) NOT NULL,
    `state_data` LONGTEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_user_table_key` (`user_id`, `table_key`),
    INDEX `idx_user_firm` (`user_id`, `firm_id`),
    INDEX `idx_table_key` (`table_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
