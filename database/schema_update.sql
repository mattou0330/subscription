-- Add new columns to subscriptions table
ALTER TABLE subscriptions 
ADD COLUMN renewal_cycle ENUM('monthly', 'yearly', 'quarterly', 'weekly') DEFAULT 'monthly' AFTER currency,
ADD COLUMN category VARCHAR(50) DEFAULT NULL AFTER renewal_cycle;

-- Create categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    color VARCHAR(7) DEFAULT '#3498db',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

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

-- Service to category mapping table
CREATE TABLE IF NOT EXISTS service_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_pattern VARCHAR(255) NOT NULL,
    category_id INT NOT NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Insert common service patterns
INSERT INTO service_categories (service_pattern, category_id) VALUES
('Netflix', 7), ('Amazon Prime', 7), ('Disney+', 7), ('Hulu', 7), ('U-NEXT', 7),
('Spotify', 6), ('Apple Music', 6), ('YouTube Music', 6), ('Amazon Music', 6),
('Microsoft 365', 2), ('Google Workspace', 2), ('Slack', 2), ('Zoom', 2),
('ChatGPT', 2), ('GitHub', 2), ('Adobe', 8),
('iCloud', 5), ('Google One', 5), ('Dropbox', 5), ('OneDrive', 5),
('Kindle Unlimited', 3), ('Audible', 3), ('Udemy', 3), ('Coursera', 3),
('日経電子版', 4), ('NewsPicks', 4), ('Wall Street Journal', 4);