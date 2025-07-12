# GitHubセキュリティ修正手順

## 緊急対応が必要な理由
現在、以下の機密情報が公開リポジトリに含まれています：
- データベースパスワード（.env.docker, docker-compose.yml）
- データベース構造（各種SQLファイル）

## 修正手順

### 1. リポジトリを一時的にプライベートに変更
GitHubで：
1. リポジトリの Settings へ移動
2. 一番下の "Danger Zone" セクション
3. "Change repository visibility" をクリック
4. "Change to private" を選択

### 2. 機密情報を含むファイルを削除
```bash
# 機密ファイルを削除
git rm .env.docker
git rm docker-compose.yml
git rm -r database/

# コミット
git commit -m "Remove sensitive files"
git push origin main
```

### 3. 履歴から完全に削除（重要）
```bash
# BFG Repo-Cleanerをダウンロード
# https://rtyley.github.io/bfg-repo-cleaner/

# または git filter-branch を使用
git filter-branch --force --index-filter \
'git rm --cached --ignore-unmatch .env.docker docker-compose.yml database/*.sql' \
--prune-empty --tag-name-filter cat -- --all

# 強制プッシュ
git push origin --force --all
git push origin --force --tags
```

### 4. 新しいパスワードに変更
すべてのパスワードを変更してください：
- MySQLのrootパスワード
- データベースユーザーパスワード
- その他の認証情報

### 5. 開発用ファイルを分離
`docker-compose.example.yml`を作成：
```yaml
services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: subscription-app
    ports:
      - "8080:80"
    environment:
      - DB_HOST=db
      - DB_USER=root
      - DB_PASS=YOUR_PASSWORD_HERE
      - DB_NAME=subscription_manager
    depends_on:
      - db
    networks:
      - subscription-network

  db:
    image: mysql:8.0
    container_name: subscription-db
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: YOUR_ROOT_PASSWORD_HERE
      MYSQL_DATABASE: subscription_manager
      MYSQL_USER: appuser
      MYSQL_PASSWORD: YOUR_APP_PASSWORD_HERE
    ports:
      - "3307:3306"
    volumes:
      - db_data:/var/lib/mysql
    networks:
      - subscription-network

volumes:
  db_data:

networks:
  subscription-network:
    driver: bridge
```

## 今後の運用

### 公開リポジトリとして継続する場合
1. 機密情報は絶対にコミットしない
2. `.gitignore`を必ず確認
3. `example`ファイルを使用（.env.example, docker-compose.example.yml）

### プライベートリポジトリを推奨
本番環境のコードを含む場合は、プライベートリポジトリの使用を強く推奨します。

## チェックリスト
- [ ] リポジトリをプライベートに変更
- [ ] 機密ファイルを削除
- [ ] 履歴から完全に削除
- [ ] すべてのパスワードを変更
- [ ] .gitignoreを更新
- [ ] exampleファイルを作成