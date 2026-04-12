-- Migration: Add full_name, status, created_at columns to users table
-- Run this against inventra_db

ALTER TABLE `users` ADD COLUMN `full_name` VARCHAR(100) NOT NULL DEFAULT '' AFTER `id`;
ALTER TABLE `users` ADD COLUMN `status` ENUM('active','inactive') NOT NULL DEFAULT 'active' AFTER `role`;
ALTER TABLE `users` ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `status`;

-- Backfill existing users with display names
UPDATE `users` SET `full_name` = 'Admin User' WHERE `username` = 'admin';
UPDATE `users` SET `full_name` = 'Staff User' WHERE `username` = 'staff';
UPDATE `users` SET `full_name` = 'Regular User' WHERE `username` = 'user';
