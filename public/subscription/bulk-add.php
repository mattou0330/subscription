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
$db = Database::getInstance()->getConnection();
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
        'renewal_cycle' => $service['cycle'] ?? 'monthly',
        'start_date' => $service['start_date'] ?? date('Y-m-d')
    ];
    
    // Handle payment method
    $paymentValue = $service['payment'] ?? null;
    if ($paymentValue && is_numeric($paymentValue)) {
        // If it's a payment method ID from payment_methods table
        $data['payment_method_id'] = (int)$paymentValue;
        // Get the payment type from the payment_methods table
        $stmt = $db->prepare("SELECT type FROM payment_methods WHERE id = :id AND user_id = :user_id");
        $stmt->execute([':id' => $paymentValue, ':user_id' => $userId]);
        $paymentType = $stmt->fetchColumn();
        $data['payment_method'] = $paymentType ?: 'credit_card';
    } else {
        // If it's a direct payment type (credit_card, paypal, etc.)
        $data['payment_method'] = $paymentValue ?: 'credit_card';
        $data['payment_method_id'] = null;
    }
    
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