# サブスクリプション管理アプリ「カンリー」

## 概要
月々のサブスクリプションサービスを一元管理できるWebアプリケーションです。
複数のサブスクの料金、更新日、支払い方法を見える化し、無駄な支出を防ぎます。

## 主な機能
- ✅ サブスクリプションの登録・編集・削除
- ✅ カテゴリ別管理（エンタメ、仕事効率化、開発ツール等）
- ✅ 月額・年額料金の自動計算
- ✅ 更新日リマインダー
- ✅ 支払い方法の管理
- ✅ ダッシュボードでの一覧表示

## 技術スタック
- **フロントエンド**: HTML, CSS, JavaScript
- **バックエンド**: PHP 8.2
- **データベース**: MySQL 8.0
- **環境**: Docker, Docker Compose

## 2025年7月12日の更新内容
1. **UI改善**
   - サイドバーの幅を最適化（220px）
   - サブスクアイコンを頭文字表示に変更（URLアイコンから変更）

2. **機能追加**
   - サブスク新規追加時にカテゴリ選択機能を実装
   - カテゴリ設定画面から追加したカテゴリが自動的に反映される仕組みを構築

3. **UX改善**
   - 新規追加・一括追加のデフォルト更新サイクルを「月更新」に変更
   - サービス名入力時のカテゴリ自動検出機能を実装

4. **バグ修正**
   - カテゴリデータの文字化け問題を解決
   - カテゴリ追加時のslugフィールドエラーを修正

## セットアップ方法
```bash
# リポジトリのクローン
git clone [repository-url]
cd subscription

# Docker環境の起動
docker-compose up -d

# ブラウザでアクセス
http://localhost:8080
```

## 今後の展望
- 通知機能の実装
- カレンダー連携
- モバイル対応の強化
