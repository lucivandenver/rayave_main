ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `google_id` VARCHAR(255) NULL AFTER `role`,
    ADD COLUMN IF NOT EXISTS `email_verified_at` DATETIME NULL AFTER `google_id`,
    ADD COLUMN IF NOT EXISTS `password_reset_token` VARCHAR(255) NULL AFTER `email_verified_at`,
    ADD COLUMN IF NOT EXISTS `password_reset_expires_at` DATETIME NULL AFTER `password_reset_token`,
    ADD COLUMN IF NOT EXISTS `email_opt_in` TINYINT(1) NOT NULL DEFAULT 1 AFTER `password_reset_expires_at`,
    ADD COLUMN IF NOT EXISTS `avatar_url` VARCHAR(512) NULL AFTER `email_opt_in`;

ALTER TABLE `users`
    ADD UNIQUE INDEX IF NOT EXISTS `users_google_id_unique` (`google_id`),
    ADD INDEX IF NOT EXISTS `users_password_reset_token_index` (`password_reset_token`);

CREATE TABLE IF NOT EXISTS `email_campaign_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT UNSIGNED NULL,
    `subject` VARCHAR(255) NOT NULL,
    `segments` VARCHAR(255) NOT NULL,
    `sent_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `fail_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `email_campaign_logs_admin_id_fk` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
