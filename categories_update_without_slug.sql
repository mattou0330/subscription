-- 文字セットを設定
SET NAMES utf8mb4;

-- 外部キー制約を一時的に無効化
SET FOREIGN_KEY_CHECKS = 0;

-- 既存のカテゴリを削除
DELETE FROM categories;

-- カテゴリを再挿入
INSERT INTO categories (name, slug, color) VALUES
('エンターテインメント', 'entertainment', '#e74c3c'),
('クラウドストレージ', 'cloud_storage', '#3498db'),
('仕事効率化', 'productivity', '#2ecc71'),
('開発ツール', 'development', '#f39c12'),
('コミュニケーション', 'communication', '#9b59b6'),
('学習・教育', 'education', '#1abc9c'),
('健康・フィットネス', 'health', '#e67e22'),
('ニュース・情報', 'news', '#34495e'),
('ショッピング', 'shopping', '#16a085'),
('その他', 'other', '#95a5a6');

-- 外部キー制約を再度有効化
SET FOREIGN_KEY_CHECKS = 1;