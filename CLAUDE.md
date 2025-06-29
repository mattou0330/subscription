# プロジェクト情報

## サブスクリプション管理アプリケーション

### 概要
PHPベースのサブスクリプション管理Webアプリケーション。Docker環境で動作。

### Docker環境
- **アプリケーション**: http://localhost:8080
- **phpMyAdmin**: http://localhost:8081 (ユーザー: root, パスワード: rootpassword)
- **MySQL**: localhost:3307

### 起動方法
```bash
docker-compose up -d
```

### 重要な注意事項

#### 文字化け問題の防止
データベースに日本語データを挿入する際は、必ず以下の点に注意すること：

1. **MySQLクライアントの文字セット指定**
   - コマンドライン実行時: `--default-character-set=utf8mb4` を指定
   - SQLファイルの先頭に: `SET NAMES utf8mb4;` を記述

2. **PDO接続設定**
   - `config/database.php`に以下の設定が必要：
   ```php
   PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
   ```

3. **データベーススキーマ更新時の注意**
   - 初期スキーマファイルはすべて `/database/init/` ディレクトリに配置
   - 更新用SQLファイルは `/database/` ディレクトリに配置
   - 日本語を含むINSERT文を実行する際は必ず文字セットを確認

#### 既知の問題と解決方法
- **問題**: カテゴリ名などの日本語が文字化けする
- **原因**: MySQLクライアントのデフォルト文字セットがlatin1
- **解決**: 
  1. データベース接続時にUTF-8MB4を明示的に指定
  2. SQLファイル実行時も文字セットを指定
  3. PHPのPDO接続でもINIT_COMMANDで文字セットを設定

### ディレクトリ構成
```
/subscription/
├── docker-compose.yml
├── Dockerfile
├── .env.docker
├── config/
│   ├── config.php
│   └── database.php
├── database/
│   ├── init/
│   │   └── 01-schema.sql
│   ├── schema_update.sql
│   ├── schema_update2.sql
│   └── fix_categories.sql
├── public/
│   ├── index.php
│   ├── dashboard.php
│   ├── categories.php
│   └── ...
└── src/
    ├── Auth.php
    ├── Subscription.php
    └── ...
```

### テスト・検証コマンド
```bash
# Lint実行
# npm run lint

# 型チェック
# npm run typecheck
```

※ 上記コマンドは提供されていないため、ユーザーに確認が必要