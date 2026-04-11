-- // All db code for otp backend task 2 3

-- CREATE TABLE password_resets (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     user_id INT NOT NULL,
--     email VARCHAR(255) NOT NULL,
--     otp_code VARCHAR(6) NOT NULL,
--     attempts INT NOT NULL DEFAULT 0,
--     is_verified TINYINT(1) NOT NULL DEFAULT 0,
--     reset_token VARCHAR(255) DEFAULT NULL,
--     expires_at DATETIME NOT NULL,
--     verified_at DATETIME DEFAULT NULL,
--     created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
--     updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
--     INDEX idx_password_resets_email_status_created (email, is_verified, expires_at, created_at),
--     INDEX idx_password_resets_reset_token (reset_token)
-- );

-- CREATE TABLE admin (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     full_name VARCHAR(100) NOT NULL,
--     email VARCHAR(255) NOT NULL UNIQUE,
--     password_hash VARCHAR(255) DEFAULT NULL,
--     role VARCHAR(20) NOT NULL DEFAULT 'admin'
-- );

-- INSERT INTO admin (full_name, email, role)
-- VALUES ('Swornim Sanjel', 'yourgmail@gmail.com', 'admin');

-- //email connection: otp one

-- SELECT * FROM admin;

-- UPDATE admin
-- SET email = 'xettrikenzon@gmail.com'
-- WHERE email = 'yourgmail@gmail.com';



-- // admin settings sql:

-- CREATE TABLE IF NOT EXISTS admin_password_history (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     admin_id INT NOT NULL,
--     password_hash VARCHAR(255) NOT NULL,
--     changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
--     INDEX idx_admin_password_history_admin_id (admin_id),
--     CONSTRAINT fk_admin_password_history_admin
--         FOREIGN KEY (admin_id) REFERENCES admin(id)
--         ON DELETE CASCADE
-- );

-- ALTER TABLE password_resets
--     MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT;

-- ALTER TABLE password_resets
--     ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
--         ON UPDATE CURRENT_TIMESTAMP
--         AFTER created_at;

-- ALTER TABLE password_resets
--     ADD INDEX idx_password_resets_email_status_created (email, is_verified, expires_at, created_at),
--     ADD INDEX idx_password_resets_reset_token (reset_token);

-- ALTER TABLE admin
--     ADD COLUMN IF NOT EXISTS phone VARCHAR(30) NULL AFTER email,
--     ADD COLUMN IF NOT EXISTS avatar VARCHAR(255) NULL AFTER role,
--     ADD COLUMN IF NOT EXISTS notify_low_stock TINYINT(1) NOT NULL DEFAULT 1 AFTER avatar,
--     ADD COLUMN IF NOT EXISTS notify_weekly_summary TINYINT(1) NOT NULL DEFAULT 1 AFTER notify_low_stock;



-- //notification tabel in database:

-- CREATE TABLE IF NOT EXISTS notifications (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     user_id INT NOT NULL,
--     type VARCHAR(50) NOT NULL,
--     message VARCHAR(255) NOT NULL,
--     is_read TINYINT(1) NOT NULL DEFAULT 0,
--     created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
--     INDEX idx_notifications_user_id_created_at (user_id, created_at),
--     INDEX idx_notifications_user_id_is_read (user_id, is_read),
--     CONSTRAINT fk_notifications_admin
--         FOREIGN KEY (user_id) REFERENCES admin(id)
--         ON DELETE CASCADE
-- );

-- INSERT INTO notifications (user_id, type, message, is_read, created_at) VALUES
-- (1, 'low_stock', 'Low Stock: ''MacBook Pro 14" M2'' is below threshold (4 remaining).', 0, DATE_SUB(NOW(), INTERVAL 2 MINUTE)),
-- (1, 'request_approved', 'Request Approved: ''Ergonomic Chair'' request approved by System Admin.', 0, DATE_SUB(NOW(), INTERVAL 45 MINUTE)),
-- (1, 'new_user', 'New User: ''Swornim'' has been added to the system.', 0, DATE_SUB(NOW(), INTERVAL 3 HOUR)),
-- (1, 'system_update', 'System Update: Maintenance scheduled for midnight.', 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
-- (1, 'security_alert', 'Security: New login detected', 1, DATE_SUB(NOW(), INTERVAL 2 DAY));
