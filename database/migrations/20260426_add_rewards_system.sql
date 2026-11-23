-- Rewards system schema
-- Adds user points balance, reward tier configuration, reward point logs,
-- and order reward tracking columns.

ALTER TABLE users
    ADD COLUMN reward_points INT UNSIGNED NOT NULL DEFAULT 0 AFTER birthday;

CREATE TABLE IF NOT EXISTS reward_tiers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    required_orders INT UNSIGNED NOT NULL,
    discount_percent DECIMAL(5,2) NOT NULL,
    points_cost INT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_required_orders (required_orders)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reward_point_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    order_id INT UNSIGNED NULL,
    points_change INT NOT NULL,
    balance_after INT UNSIGNED NOT NULL,
    reason VARCHAR(50) NOT NULL,
    meta_json TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_reward_logs_user (user_id, created_at),
    UNIQUE KEY uniq_user_order_reason (user_id, order_id, reason)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE orders
    ADD COLUMN reward_discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER coupon_discount,
    ADD COLUMN reward_discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER reward_discount_percent,
    ADD COLUMN reward_points_earned INT UNSIGNED NOT NULL DEFAULT 0 AFTER reward_discount_amount,
    ADD COLUMN reward_points_spent INT UNSIGNED NOT NULL DEFAULT 0 AFTER reward_points_earned;
