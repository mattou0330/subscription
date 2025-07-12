# さくらサーバーへのバージョンアップデプロイ手順

## 更新内容の概要

### 1. 更新されたファイル
- `/public/css/modern-style.css` - サイドバーの幅を220pxに変更
- `/public/dashboard.php` - 以下の機能を追加/修正
  - サブスクリプションアイコンをURLから頭文字表示に変更
  - 新規追加画面のデフォルト更新サイクルを月更新に変更
  - カテゴリ選択機能を追加（データベースから動的に取得）
  - 一括追加画面のデフォルト更新サイクルを月更新に変更
- `/public/categories.php` - カテゴリ追加/更新時にslugフィールドを自動生成

### 2. データベースの変更
- `categories`テーブルにslugカラムを追加（既に追加済みの場合あり）
- カテゴリデータの再挿入（文字化け修正）

## デプロイ手順

### 1. バックアップの作成（重要）
```bash
# サーバー上で実行
# ファイルのバックアップ
tar -czf backup_$(date +%Y%m%d_%H%M%S).tar.gz public/

# データベースのバックアップ
mysqldump -u [ユーザー名] -p [データベース名] > db_backup_$(date +%Y%m%d_%H%M%S).sql
```

### 2. ファイルのアップロード
以下のファイルをFTP/SFTPでアップロード：
- `/public/css/modern-style.css`
- `/public/dashboard.php`
- `/public/categories.php`

### 3. データベースの更新
以下のSQLを実行（phpMyAdminまたはSSH経由）：

```sql
-- 文字セットを設定
SET NAMES utf8mb4;

-- slugカラムが存在しない場合のみ追加
ALTER TABLE categories ADD COLUMN slug VARCHAR(50) AFTER name;

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
```

### 4. 動作確認
1. ブラウザのキャッシュをクリア（Ctrl+Shift+R または Cmd+Shift+R）
2. 以下の機能を確認：
   - サブスクリプション一覧でアイコンが頭文字表示になっているか
   - 新規追加画面でカテゴリが選択でき、デフォルトが月更新になっているか
   - カテゴリ設定から新規カテゴリが追加できるか
   - 追加したカテゴリがサブスクリプション追加画面に反映されるか

## 注意事項
- データベースの文字コードはUTF-8MB4を使用してください
- カテゴリテーブルのデータは一度削除されて再作成されます
- 既存のサブスクリプションのカテゴリ設定は維持されます

## トラブルシューティング
- CSSが反映されない場合：ブラウザキャッシュをクリア
- カテゴリが文字化けする場合：データベース接続の文字コード設定を確認
- エラーが発生する場合：エラーログを確認し、PHPのバージョン互換性をチェック