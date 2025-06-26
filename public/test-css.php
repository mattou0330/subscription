<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Auth.php';

use App\Auth;

$auth = new Auth();
$auth->requireLogin();

$pageTitle = 'CSS Test';
$currentPage = 'home';
$additionalStyles = '<link rel="stylesheet" href="css/dashboard-style.css">';

ob_start();
?>

<div class="stats-grid">
    <div class="stat-card">
        <h3>テスト</h3>
        <div class="stat-value">
            <span class="currency">¥</span>12,345
        </div>
    </div>
</div>

<div class="alert alert-success">
    <i class="fas fa-check-circle"></i>
    CSSが正しく適用されています
</div>

<button class="btn btn-primary">プライマリボタン</button>
<button class="btn btn-secondary">セカンダリボタン</button>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../templates/layout.php';
?>