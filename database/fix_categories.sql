-- Set proper character encoding
SET NAMES utf8mb4;

-- Delete existing categories
DELETE FROM service_categories;
DELETE FROM categories;

-- Reset auto increment
ALTER TABLE categories AUTO_INCREMENT = 1;

-- Insert categories with correct encoding
INSERT INTO categories (id, name, color) VALUES
(1, 'エンターテイメント', '#e74c3c'),
(2, '仕事・ビジネス', '#3498db'),
(3, '学習・教育', '#2ecc71'),
(4, 'ニュース・情報', '#f39c12'),
(5, 'クラウドストレージ', '#9b59b6'),
(6, '音楽', '#1abc9c'),
(7, '動画配信', '#e67e22'),
(8, 'ソフトウェア', '#34495e'),
(9, 'その他', '#95a5a6');

-- Re-insert service patterns
INSERT INTO service_categories (service_pattern, category_id) VALUES
('Netflix', 7), ('Amazon Prime', 7), ('Disney+', 7), ('Hulu', 7), ('U-NEXT', 7),
('Spotify', 6), ('Apple Music', 6), ('YouTube Music', 6), ('Amazon Music', 6),
('Microsoft 365', 2), ('Google Workspace', 2), ('Slack', 2), ('Zoom', 2),
('ChatGPT', 2), ('GitHub', 2), ('Adobe', 8),
('iCloud', 5), ('Google One', 5), ('Dropbox', 5), ('OneDrive', 5),
('Kindle Unlimited', 3), ('Audible', 3), ('Udemy', 3), ('Coursera', 3),
('日経電子版', 4), ('NewsPicks', 4), ('Wall Street Journal', 4);