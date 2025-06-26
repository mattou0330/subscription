<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Subscription.php';

use App\Auth;
use App\Subscription;

$auth = new Auth();
$auth->requireLogin();

$pageTitle = '分析（シンプル版）';
$currentPage = 'analysis';

ob_start();
?>

<h1>分析ページ（シンプル版）</h1>

<?php
try {
    $subscription = new Subscription();
    $userId = $auth->getCurrentUserId();
    
    echo "<h2>基本情報</h2>";
    echo "<p>ユーザーID: " . $userId . "</p>";
    
    // 月額合計を取得
    $monthlyTotal = $subscription->getMonthlyTotal($userId);
    echo "<p>月額合計: ¥" . number_format($monthlyTotal) . "</p>";
    
    // サブスクリプション一覧を取得
    $subscriptions = $subscription->getByUserId($userId);
    echo "<p>サブスクリプション数: " . count($subscriptions) . "</p>";
    
    echo "<h2>サブスクリプション一覧</h2>";
    echo "<ul>";
    foreach ($subscriptions as $sub) {
        echo "<li>" . htmlspecialchars($sub['service_name']) . " - ¥" . number_format($sub['monthly_fee']) . "</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>";
    echo "<h2>エラーが発生しました:</h2>";
    echo "<p>メッセージ: " . $e->getMessage() . "</p>";
    echo "<p>ファイル: " . $e->getFile() . "</p>";
    echo "<p>行番号: " . $e->getLine() . "</p>";
    echo "</div>";
}
?>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../templates/layout.php';
?>