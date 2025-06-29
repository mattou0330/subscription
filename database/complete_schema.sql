SET NAMES utf8mb4;

-- Create database
CREATE DATABASE IF NOT EXISTS subscription_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE subscription_manager;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    color VARCHAR(7) DEFAULT '#3498db',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Service categories mapping table
CREATE TABLE IF NOT EXISTS service_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_pattern VARCHAR(255) NOT NULL,
    category_id INT NOT NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Payment methods table
CREATE TABLE IF NOT EXISTS payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    type ENUM('credit_card', 'debit_card', 'paypal', 'bank_transfer', 'apple_pay', 'paypay', 'other') DEFAULT 'credit_card',
    last_four VARCHAR(4),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Service logos table
CREATE TABLE IF NOT EXISTS service_logos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(255) NOT NULL,
    logo_url VARCHAR(255) NOT NULL,
    UNIQUE KEY unique_service (service_name)
);

-- Subscriptions table
CREATE TABLE IF NOT EXISTS subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    service_name VARCHAR(255) NOT NULL,
    monthly_fee DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'JPY',
    renewal_cycle ENUM('monthly', 'yearly', 'quarterly', 'weekly', 'semi_annually') DEFAULT 'monthly',
    category VARCHAR(50) DEFAULT NULL,
    payment_method VARCHAR(50) DEFAULT 'credit_card',
    payment_method_id INT DEFAULT NULL,
    logo_url VARCHAR(255) DEFAULT NULL,
    start_date DATE NOT NULL,
    next_renewal_date DATE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id) ON DELETE SET NULL
);

-- Payment history table
CREATE TABLE IF NOT EXISTS payment_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subscription_id INT NOT NULL,
    payment_date DATE NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'JPY',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE
);

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subscription_id INT NOT NULL,
    notification_date DATE NOT NULL,
    is_sent BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE
);

-- Create indexes
CREATE INDEX idx_subscriptions_user_id ON subscriptions(user_id);
CREATE INDEX idx_subscriptions_next_renewal ON subscriptions(next_renewal_date);
CREATE INDEX idx_payment_history_subscription ON payment_history(subscription_id);
CREATE INDEX idx_notifications_date ON notifications(notification_date);

-- Insert default categories
INSERT INTO categories (name, color) VALUES
('エンターテイメント', '#e74c3c'),
('仕事・ビジネス', '#3498db'),
('学習・教育', '#2ecc71'),
('ニュース・情報', '#f39c12'),
('クラウドストレージ', '#9b59b6'),
('音楽', '#1abc9c'),
('動画配信', '#e67e22'),
('ソフトウェア', '#34495e'),
('その他', '#95a5a6');

-- Insert common service patterns
INSERT INTO service_categories (service_pattern, category_id) VALUES
('netflix', 7), ('amazon prime', 7), ('disney', 7), ('hulu', 7), ('u-next', 7),
('spotify', 6), ('apple music', 6), ('youtube music', 6), ('amazon music', 6),
('microsoft 365', 2), ('google workspace', 2), ('slack', 2), ('zoom', 2),
('chatgpt', 2), ('github', 2), ('adobe', 8),
('icloud', 5), ('google one', 5), ('dropbox', 5), ('onedrive', 5),
('kindle unlimited', 3), ('audible', 3), ('udemy', 3), ('coursera', 3),
('日経電子版', 4), ('newspicks', 4), ('wall street journal', 4);

-- Insert common service logos
INSERT INTO service_logos (service_name, logo_url) VALUES
('netflix', 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/netflix/netflix-original.svg'),
('spotify', 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/spotify/spotify-original.svg'),
('amazon prime', 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/amazonwebservices/amazonwebservices-original.svg'),
('github', 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/github/github-original.svg'),
('slack', 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/slack/slack-original.svg'),
('google', 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/google/google-original.svg'),
('microsoft', 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/microsoft/microsoft-original.svg'),
('apple', 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/apple/apple-original.svg'),
('adobe', 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/adobe/adobe-original.svg'),
('dropbox', 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/dropbox/dropbox-original.svg');