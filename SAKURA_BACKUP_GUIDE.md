# さくらサーバーでのバックアップ方法

## 方法1: さくらのコントロールパネルを使用（推奨）

### ファイルのバックアップ
1. さくらのコントロールパネルにログイン
2. 「ファイルマネージャー」を開く
3. `public`フォルダを右クリック
4. 「圧縮」または「ダウンロード」を選択
5. ローカルPCに保存

### データベースのバックアップ
1. さくらのコントロールパネルにログイン
2. 「データベース」→「phpMyAdmin」を開く
3. 対象のデータベースを選択
4. 上部メニューの「エクスポート」をクリック
5. 以下の設定で実行：
   - エクスポート方法：「簡易」
   - フォーマット：「SQL」
   - 文字セット：「utf8mb4」
6. 「実行」をクリックしてダウンロード

## 方法2: FTPクライアントを使用

### ファイルのバックアップ
1. FTPクライアント（FileZilla等）でサーバーに接続
2. 以下のフォルダを丸ごとダウンロード：
   - `/public/css/`
   - `/public/dashboard.php`
   - `/public/categories.php`
3. ローカルPCの安全な場所に保存

## 方法3: SSHが使える場合（上級者向け）

もしSSH接続が可能な場合は、以下のコマンドを実行：

```bash
# publicフォルダ全体をバックアップ
cd /home/[ユーザー名]/www/
tar -czf backup_20250712.tar.gz public/

# 特定のファイルのみバックアップ
cp public/css/modern-style.css public/css/modern-style.css.bak
cp public/dashboard.php public/dashboard.php.bak
cp public/categories.php public/categories.php.bak
```

## バックアップの保存場所
- ローカルPC上の日付付きフォルダ（例：backup_20250712/）
- クラウドストレージ（Google Drive、Dropbox等）にもコピーを保存することを推奨

## 重要な注意点
- アップロード前に必ずバックアップを取る
- データベースのバックアップは特に重要（カテゴリデータが変更されるため）
- バックアップファイルには日付を付けて管理