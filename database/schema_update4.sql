-- サブスクリプションテーブルにlogo_urlカラムを追加
ALTER TABLE subscriptions 
ADD COLUMN logo_url VARCHAR(500) DEFAULT NULL AFTER service_name;

-- 既存のデータに対してアイコンURLを設定（オプション）
-- UPDATE subscriptions SET logo_url = 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/netflix/netflix-original.svg' WHERE LOWER(service_name) LIKE '%netflix%';
-- UPDATE subscriptions SET logo_url = 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/spotify/spotify-original.svg' WHERE LOWER(service_name) LIKE '%spotify%';