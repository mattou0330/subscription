<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Auth.php';

use App\Auth;

try {
    $auth = new Auth();
    $auth->requireLogin();
    
    echo "認証OK<br>";
    
    $db = Database::getInstance()->getConnection();
    echo "データベース接続OK<br>";
    
    $userId = $auth->getCurrentUserId();
    echo "ユーザーID: " . $userId . "<br>";
    
    // 簡単なクエリテスト
    $stmt = $db->prepare("SELECT COUNT(*) FROM subscriptions WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $userId]);
    $count = $stmt->fetchColumn();
    echo "サブスクリプション数: " . $count . "<br>";
    
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "<br>";
    echo "ファイル: " . $e->getFile() . "<br>";
    echo "行: " . $e->getLine() . "<br>";
}
?>