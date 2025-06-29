SET NAMES utf8mb4;

-- Add semi_annually option to renewal_cycle ENUM
ALTER TABLE subscriptions 
MODIFY COLUMN renewal_cycle ENUM('monthly', 'yearly', 'quarterly', 'weekly', 'semi_annually') DEFAULT 'monthly';