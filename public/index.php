<?php
require_once __DIR__ . '/../config/config.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>サブスクリプション管理</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>サブスクリプション管理</h1>
            <p>すべてのサブスクリプションを一元管理</p>
        </header>
        
        <main>
            <div class="hero">
                <h2>月額費用を見える化し、無駄を削減</h2>
                <p>契約中のサブスクリプションを登録して、総額や更新日を管理しましょう</p>
                <div class="cta-buttons">
                    <a href="register.php" class="btn btn-primary">新規登録</a>
                    <a href="login.php" class="btn btn-secondary">ログイン</a>
                </div>
            </div>
            
            <section class="features">
                <h3>主な機能</h3>
                <div class="feature-grid">
                    <div class="feature-card">
                        <h4>一元管理</h4>
                        <p>すべてのサブスクリプションを一箇所で管理</p>
                    </div>
                    <div class="feature-card">
                        <h4>費用の可視化</h4>
                        <p>月額合計や推移をグラフで確認</p>
                    </div>
                    <div class="feature-card">
                        <h4>更新通知</h4>
                        <p>更新日の3日前にメールでお知らせ</p>
                    </div>
                    <div class="feature-card">
                        <h4>多通貨対応</h4>
                        <p>外貨建てサービスも円換算で表示</p>
                    </div>
                </div>
            </section>
        </main>
        
        <footer>
            <p>&copy; 2024 Subscription Manager. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>