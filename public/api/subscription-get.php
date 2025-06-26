<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Subscription.php';

use App\Auth;
use App\Subscription;

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$subscriptionId = (int)($_GET['id'] ?? 0);

if (!$subscriptionId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid subscription ID']);
    exit;
}

$subscription = new Subscription();
$subData = $subscription->getById($subscriptionId, $auth->getCurrentUserId());

if (!$subData) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'サブスクリプションが見つかりません']);
    exit;
}

// 支払い方法一覧も取得
$db = \Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT * FROM payment_methods WHERE user_id = :user_id ORDER BY name");
$stmt->execute([':user_id' => $auth->getCurrentUserId()]);
$paymentMethods = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'subscription' => $subData,
    'paymentMethods' => $paymentMethods
]);