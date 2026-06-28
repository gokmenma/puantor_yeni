CREATE TABLE IF NOT EXISTS `push_subscriptions` (
    `id`         INT(11) NOT NULL AUTO_INCREMENT,
    `user_type`  ENUM('user', 'personel') NOT NULL,
    `user_id`    INT(11) NOT NULL,
    `firma_id`   INT(11) NOT NULL,
    `endpoint`   TEXT NOT NULL,
    `p256dh`     TEXT NOT NULL,
    `auth`       VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_endpoint` (`endpoint`(191)),
    KEY `idx_firma_user` (`firma_id`, `user_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
