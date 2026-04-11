-- //admin settings sql:

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
