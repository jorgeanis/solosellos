-- Migration for Referral System
-- Run this on your remote database to enable the referral features

-- 1. Add configuration fields to 'users' table
ALTER TABLE `users` ADD COLUMN `referral_active` TINYINT(1) DEFAULT 0;
ALTER TABLE `users` ADD COLUMN `referral_type` ENUM('percent', 'fixed') DEFAULT 'percent';
ALTER TABLE `users` ADD COLUMN `referral_value` DECIMAL(10,2) DEFAULT 0.00;

-- 2. Add tracking field to 'orders' table
ALTER TABLE `orders` ADD COLUMN `referred_by` VARCHAR(20) DEFAULT NULL AFTER `comments`;
