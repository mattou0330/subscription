<?php
if (!isset($auth) || !$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'カンリー' ?> - カンリー</title>
    <link rel="stylesheet" href="css/modern-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php if (isset($additionalStyles)): ?>
        <?= $additionalStyles ?>
    <?php endif; ?>
</head>
<body>
    <div class="app-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1 class="logo">カンリー</h1>
                <p class="tagline">Subscription Manager</p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item <?= $currentPage === 'home' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i>
                    <span>ホーム</span>
                </a>
                <a href="analysis.php" class="nav-item <?= $currentPage === 'analysis' ? 'active' : '' ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>分析</span>
                </a>
                <a href="categories.php" class="nav-item <?= $currentPage === 'categories' ? 'active' : '' ?>">
                    <i class="fas fa-tags"></i>
                    <span>カテゴリ設定</span>
                </a>
                <a href="payment-methods.php" class="nav-item <?= $currentPage === 'payment' ? 'active' : '' ?>">
                    <i class="fas fa-credit-card"></i>
                    <span>支払い方法設定</span>
                </a>
                <a href="notifications.php" class="nav-item <?= $currentPage === 'notifications' ? 'active' : '' ?>">
                    <i class="fas fa-bell"></i>
                    <span>通知設定</span>
                </a>
                <a href="account.php" class="nav-item <?= $currentPage === 'account' ? 'active' : '' ?>">
                    <i class="fas fa-user-circle"></i>
                    <span>アカウント設定</span>
                </a>
                <a href="logout.php" class="nav-item logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>ログアウト</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <i class="fas fa-user"></i>
                    <span><?= htmlspecialchars($_SESSION['user_name'] ?: $_SESSION['user_email']) ?></span>
                </div>
            </div>
        </aside>
        
        <main class="main-content">
            <header class="top-header">
                <h2><?= $pageTitle ?? 'ホーム' ?></h2>
                <div class="header-actions">
                    <button class="notification-btn">
                        <i class="fas fa-bell"></i>
                        <?php if (isset($upcomingRenewals) && count($upcomingRenewals) > 0): ?>
                            <span class="notification-badge"><?= count($upcomingRenewals) ?></span>
                        <?php endif; ?>
                    </button>
                </div>
            </header>
            
            <div class="content-wrapper">
                <?= $content ?>
            </div>
        </main>
    </div>
    
    <?php if (isset($additionalScripts)): ?>
        <?= $additionalScripts ?>
    <?php endif; ?>
</body>
</html>