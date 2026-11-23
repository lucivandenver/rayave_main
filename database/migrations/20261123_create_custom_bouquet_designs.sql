-- Migration: Add Live Canvas Custom Bouquet Designs table and optional product canvas cutout image
ALTER TABLE products
    ADD COLUMN IF NOT EXISTS canvas_image VARCHAR(255) NULL DEFAULT NULL AFTER image;

CREATE TABLE IF NOT EXISTS custom_bouquet_designs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_id INT NULL DEFAULT NULL,
    status ENUM('in_cart', 'ordered', 'discarded') NOT NULL DEFAULT 'in_cart',
    preview_image VARCHAR(255) NOT NULL,
    canvas_json LONGTEXT NULL,
    items_json LONGTEXT NULL,
    design_notes TEXT NULL,
    total_price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_custom_design_user_status (user_id, status),
    INDEX idx_custom_design_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
