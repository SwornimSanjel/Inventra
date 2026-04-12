CREATE DATABASE IF NOT EXISTS inventra_merge_app
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE inventra_merge_app;

CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'admin',
    phone VARCHAR(30) NULL,
    avatar VARCHAR(255) NULL,
    notify_low_stock TINYINT(1) NOT NULL DEFAULT 1,
    notify_weekly_summary TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS admin_password_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin_password_history_admin_id (admin_id),
    CONSTRAINT fk_admin_password_history_admin
        FOREIGN KEY (admin_id) REFERENCES admin(id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    attempts INT NOT NULL DEFAULT 0,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    reset_token VARCHAR(255) DEFAULT NULL,
    expires_at DATETIME NOT NULL,
    verified_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_password_resets_email_status_created (email, is_verified, expires_at, created_at),
    INDEX idx_password_resets_reset_token (reset_token)
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    message VARCHAR(255) NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notifications_user_id_created_at (user_id, created_at),
    INDEX idx_notifications_user_id_is_read (user_id, is_read),
    CONSTRAINT fk_notifications_admin
        FOREIGN KEY (user_id) REFERENCES admin(id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','staff') NOT NULL DEFAULT 'staff',
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT DEFAULT NULL,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100) DEFAULT NULL,
    qty INT NOT NULL DEFAULT 0,
    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    lower_limit INT NOT NULL DEFAULT 0,
    upper_limit INT NOT NULL DEFAULT 0,
    image VARCHAR(255) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_products_category_id (category_id),
    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id) REFERENCES categories(id)
        ON DELETE SET NULL
);

DELETE FROM notifications;
DELETE FROM password_resets;
DELETE FROM admin_password_history;
DELETE FROM users;
DELETE FROM products;
DELETE FROM categories;
DELETE FROM admin;

INSERT INTO admin (id, full_name, email, password_hash, role, phone, avatar, notify_low_stock, notify_weekly_summary)
VALUES
(
    1,
    'Admin User',
    'xettrikenzon@gmail.com',
    '$2y$10$372WvB9JUch0B7aLZdnRiugFLR4bYKxhw.XuU7LWcRs8Jy9GCSHdW',
    'admin',
    '9800000000',
    NULL,
    1,
    1
);

INSERT INTO categories (id, name, description) VALUES
(1, 'Electronics', 'Electronic office equipment'),
(2, 'Accessories', 'Computer and desk accessories'),
(3, 'Furniture', 'Office furniture and fixtures');

INSERT INTO products (id, category_id, name, category, qty, unit_price, lower_limit, upper_limit, image, description) VALUES
(1, 1, 'MacBook Pro 14\" M2', 'Electronics', 4, 285000.00, 5, 12, NULL, 'Apple laptop for office and development work'),
(2, 2, 'Logitech MX Master 3S', 'Accessories', 0, 14500.00, 3, 15, NULL, 'Wireless productivity mouse'),
(3, 2, 'Dell 27\" Monitor', 'Accessories', 8, 42000.00, 4, 16, NULL, '27-inch QHD monitor for the design team'),
(4, 3, 'Ergonomic Chair', 'Furniture', 18, 18500.00, 5, 30, NULL, 'Adjustable office chair with lumbar support'),
(5, 1, 'USB-C Docking Station', 'Electronics', 22, 9500.00, 6, 25, NULL, 'Docking station for laptops and dual-display setups');

INSERT INTO users (id, full_name, username, email, password, role, status) VALUES
(1, 'Admin User', 'admin', 'xettrikenzon@gmail.com', '$2y$10$372WvB9JUch0B7aLZdnRiugFLR4bYKxhw.XuU7LWcRs8Jy9GCSHdW', 'admin', 'active'),
(2, 'Swornim Sanjel', 'swornim', 'swornim@inventra.com', '$2y$10$n4UDXg9cqefcoZ6sOtKeseQvQZQcYgd1DiB3HnRcSXpHU/cYr4vOK', 'staff', 'active'),
(3, 'Shirish Gurung', 'shirish', 'shirish@inventra.com', '$2y$10$n4UDXg9cqefcoZ6sOtKeseQvQZQcYgd1DiB3HnRcSXpHU/cYr4vOK', 'staff', 'active');

INSERT INTO notifications (id, user_id, type, message, is_read, created_at) VALUES
(1, 1, 'low_stock', 'Low Stock: MacBook Pro 14\" M2 is below threshold (4 remaining).', 0, DATE_SUB(NOW(), INTERVAL 10 MINUTE)),
(2, 1, 'new_user', 'New user accounts are ready for review.', 0, DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(3, 1, 'system_update', 'Merged integration database setup completed successfully.', 1, DATE_SUB(NOW(), INTERVAL 1 DAY));

ALTER TABLE admin AUTO_INCREMENT = 2;
ALTER TABLE categories AUTO_INCREMENT = 4;
ALTER TABLE products AUTO_INCREMENT = 6;
ALTER TABLE users AUTO_INCREMENT = 4;
ALTER TABLE notifications AUTO_INCREMENT = 4;
