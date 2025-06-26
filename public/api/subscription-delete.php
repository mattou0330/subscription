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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$subscriptionId = (int)($data['id'] ?? 0);

if (!$subscriptionId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid subscription ID']);
    exit;
}

$subscription = new Subscription();
$userId = $auth->getCurrentUserId();

// Verify ownership
$subData = $subscription->getById($subscriptionId, $userId);
if (!$subData) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Subscription not found']);
    exit;
}

// Delete subscription
$db = \Database::getInstance()->getConnection();
$stmt = $db->prepare("DELETE FROM subscriptions WHERE id = :id AND user_id = :user_id");
$success = $stmt->execute([
    ':id' => $subscriptionId,
    ':user_id' => $userId
]);

if ($success) {
    echo json_encode([
        'success' => true,
        'message' => 'サブスクリプションを削除しました'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '削除に失敗しました']);
}