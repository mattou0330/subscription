SET NAMES utf8mb4;

-- Add payment_method_id column to subscriptions table
ALTER TABLE subscriptions 
ADD COLUMN payment_method_id INT DEFAULT NULL AFTER payment_method,
ADD CONSTRAINT fk_payment_method FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id) ON DELETE SET NULL;