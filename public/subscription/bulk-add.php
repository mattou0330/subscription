<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Subscription.php';
require_once __DIR__ . '/../../src/utils/csrf.php';

use App\Auth;
use App\Subscription;
use App\Utils\CSRF;

$auth = new Auth();
$auth->requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboard.php');
    exit;
}

if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = '不正なリクエストです';
    header('Location: ../dashboard.php');
    exit;
}

$subscription = new Subscription();
$userId = $auth->getCurrentUserId();
$services = $_POST['services'] ?? [];
$successCount = 0;
$errorCount = 0;

foreach ($services as $service) {
    if (empty($service['name']) || empty($service['fee'])) {
        $errorCount++;
        continue;
    }
    
    $data = [
        'service_name' => $service['name'],
        'monthly_fee' => (float)$service['fee'],
        'currency' => $service['currency'] ?? 'JPY',
        'payment_method' => $service['payment'] ?? 'credit_card',
        'renewal_cycle' => $service['cycle'] ?? 'monthly',
        'start_date' => $service['start_date'] ?? date('Y-m-d')
    ];
    
    // Calculate next renewal date
    $data['next_renewal_date'] = $subscription->calculateNextRenewal($data['start_date'], $data['renewal_cycle']);
    
    if ($subscription->create($userId, $data)) {
        $successCount++;
    } else {
        $errorCount++;
    }
}

if ($successCount > 0) {
    $_SESSION['message'] = "{$successCount}件のサブスクリプションを追加しました";
}

if ($errorCount > 0) {
    $_SESSION['error'] = "{$errorCount}件の追加に失敗しました";
}

header('Location: ../dashboard.php');
exit;