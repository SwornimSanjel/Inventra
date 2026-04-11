ALTER TABLE password_resets
    MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT;

ALTER TABLE password_resets
    ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
        AFTER created_at;

ALTER TABLE password_resets
    ADD INDEX idx_password_resets_email_status_created (email, is_verified, expires_at, created_at),
    ADD INDEX idx_password_resets_reset_token (reset_token);
