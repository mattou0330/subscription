<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Subscription.php';
require_once __DIR__ . '/../../src/utils/csrf.php';

use App\Auth;
use App\Subscription;
use App\Utils\CSRF;

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// CSRFトークンの検証
if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '不正なリクエストです']);
    exit;
}

$subscription = new Subscription();
$subscriptionId = (int)($_POST['id'] ?? 0);

if (!$subscriptionId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid subscription ID']);
    exit;
}

// 所有権確認
$subData = $subscription->getById($subscriptionId, $auth->getCurrentUserId());
if (!$subData) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'サブスクリプションが見つかりません']);
    exit;
}

$data = [
    'service_name' => filter_input(INPUT_POST, 'service_name', FILTER_SANITIZE_STRING),
    'monthly_fee' => filter_input(INPUT_POST, 'monthly_fee', FILTER_VALIDATE_FLOAT),
    'currency' => filter_input(INPUT_POST, 'currency', FILTER_SANITIZE_STRING),
    'renewal_cycle' => filter_input(INPUT_POST, 'renewal_cycle', FILTER_SANITIZE_STRING),
    'payment_method' => $_POST['payment_method'] ?? 'credit_card',
    'start_date' => $_POST['start_date'] ?? '',
    'is_active' => isset($_POST['is_active']) ? 1 : 0
];

if (empty($data['service_name']) || !$data['monthly_fee'] || empty($data['start_date'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '必須項目を入力してください']);
    exit;
}

$data['next_renewal_date'] = $subscription->calculateNextRenewal($data['start_date'], $data['renewal_cycle']);

if ($subscription->update($subscriptionId, $auth->getCurrentUserId(), $data)) {
    echo json_encode(['success' => true, 'message' => 'サブスクリプションを更新しました']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '更新に失敗しました']);
}