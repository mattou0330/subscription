# さくらサーバーへのデプロイガイド

## 前提条件
- PHP 8.2以上
- MySQL 8.0以上
- Composerがインストール済み

## デプロイ手順

### 1. ファイルのアップロード
以下のファイル・ディレクトリをサーバーにアップロードしてください：
```
/config/
/database/complete_schema.sql
/public/
/src/
/templates/
/vendor/ (composer installで生成されるため、後述)
.env (後述の手順で作成)
composer.json
composer.lock
```

### 2. 環境設定ファイルの作成
`.env.example`をコピーして`.env`を作成し、さくらサーバーの設定に合わせて編集してください：

```bash
cp .env.example .env
```

`.env`ファイルの設定例：
```
DB_HOST=mysqlXXX.db.sakura.ne.jp
DB_USER=your_sakura_db_user
DB_PASS=your_sakura_db_password
DB_NAME=your_sakura_db_name
API_EXCHANGE_URL=https://api.exchangerate-api.com/v4/latest/USD
MAIL_FROM=noreply@yourdomain.com
SENDGRID_API_KEY=your_sendgrid_api_key
```

### 3. Composerパッケージのインストール
SSHでサーバーに接続し、アプリケーションのルートディレクトリで以下を実行：

```bash
composer install --no-dev --optimize-autoloader
```

### 4. データベースのセットアップ
phpMyAdminまたはSSHから以下のSQLを実行：

```bash
mysql -u your_db_user -p your_db_name < database/complete_schema.sql
```

### 5. ディレクトリ権限の設定
Webサーバーが書き込みできるように権限を設定（必要に応じて）：

```bash
chmod 755 public/
chmod 755 src/
chmod 755 templates/
```

### 6. ドキュメントルートの設定
さくらサーバーのコントロールパネルから、ドメインのドキュメントルートを`/public`ディレクトリに設定してください。

## 動作確認

1. ブラウザでアプリケーションにアクセス
2. 新規アカウント登録
3. ログイン
4. サブスクリプションの追加
5. 支払い方法の設定

## 注意事項

### 文字コード
- データベースとPHPの接続は必ずUTF-8MB4を使用しています
- 文字化けを防ぐため、データベースの文字コードも`utf8mb4`に設定してください

### 初期データ
- カテゴリとサービスパターンの初期データが自動的に挿入されます
- カテゴリID 1-9は削除できないようになっています（システムで保護）

### セキュリティ
- `.env`ファイルは公開ディレクトリに配置しないでください
- デバッグモードは本番環境では無効にしてください

## トラブルシューティング

### データベース接続エラー
- `.env`ファイルの設定を確認
- データベースユーザーの権限を確認

### 文字化け
- データベースの文字コードが`utf8mb4`になっているか確認
- PHPファイルのエンコーディングがUTF-8になっているか確認

### 500エラー
- PHPのバージョンを確認（8.2以上）
- `.htaccess`ファイルの設定を確認
- エラーログを確認