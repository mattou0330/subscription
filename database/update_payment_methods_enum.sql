SET NAMES utf8mb4;

-- Update payment_methods table to include apple_pay and paypay
ALTER TABLE payment_methods 
MODIFY COLUMN type ENUM('credit_card', 'debit_card', 'paypal', 'bank_transfer', 'apple_pay', 'paypay', 'other') DEFAULT 'credit_card';