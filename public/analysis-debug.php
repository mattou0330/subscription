<?php
// エラー表示を有効化
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../src/Auth.php';
    require_once __DIR__ . '/../src/Subscription.php';

    use App\Auth;
    use App\Subscription;

    $auth = new Auth();
    $auth->requireLogin();

    $subscription = new Subscription();
    $userId = $auth->getCurrentUserId();
    
    // 基本的なデータ取得をテスト
    $subscriptions = $subscription->getByUserId($userId);
    echo "サブスクリプション数: " . count($subscriptions) . "<br>";
    
    $subscriptionsByCategory = $subscription->getByCategory($userId);
    echo "カテゴリ数: " . count($subscriptionsByCategory) . "<br>";
    
    $monthlyTotal = $subscription->getMonthlyTotal($userId);
    echo "月額合計: " . $monthlyTotal . "<br>";

    echo "<h2>分析ページは正常に動作しています</h2>";
    echo '<a href="analysis.php">分析ページへ</a>';
    
} catch (Exception $e) {
    echo "<h2>エラーが発生しました:</h2>";
    echo "<p>メッセージ: " . $e->getMessage() . "</p>";
    echo "<p>ファイル: " . $e->getFile() . "</p>";
    echo "<p>行番号: " . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>