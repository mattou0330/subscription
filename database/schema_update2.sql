-- Add payment method and logo columns to subscriptions table
ALTER TABLE subscriptions 
ADD COLUMN payment_method VARCHAR(50) DEFAULT 'credit_card' AFTER category,
ADD COLUMN logo_url VARCHAR(255) DEFAULT NULL AFTER payment_method;

-- Create payment methods table
CREATE TABLE IF NOT EXISTS payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    type ENUM('credit_card', 'debit_card', 'paypal', 'bank_transfer', 'other') DEFAULT 'credit_card',
    last_four VARCHAR(4),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Add service logos
UPDATE service_categories SET service_pattern = 'netflix' WHERE service_pattern = 'Netflix';
UPDATE service_categories SET service_pattern = 'amazon prime' WHERE service_pattern = 'Amazon Prime';
UPDATE service_categories SET service_pattern = 'disney' WHERE service_pattern = 'Disney+';
UPDATE service_categories SET service_pattern = 'spotify' WHERE service_pattern = 'Spotify';

-- Create service logos table
CREATE TABLE IF NOT EXISTS service_logos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(255) NOT NULL,
    logo_url VARCHAR(255) NOT NULL,
    UNIQUE KEY unique_service (service_name)
);

-- Insert common service logos (using placeholder URLs)
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