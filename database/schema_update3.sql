-- Add notification settings to users table
ALTER TABLE users 
ADD COLUMN notification_days INT DEFAULT 3 AFTER updated_at,
ADD COLUMN notification_email VARCHAR(255) DEFAULT NULL AFTER notification_days,
ADD COLUMN notification_enabled BOOLEAN DEFAULT TRUE AFTER notification_email;