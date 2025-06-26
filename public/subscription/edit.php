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

$subscription = new Subscription();
$db = \Database::getInstance()->getConnection();
$error = '';
$success = '';

// Get subscription ID
$subscriptionId = (int)($_GET['id'] ?? 0);
if (!$subscriptionId) {
    header('Location: ../dashboard.php');
    exit;
}

// Fetch subscription data
$subData = $subscription->getById($subscriptionId, $auth->getCurrentUserId());
if (!$subData) {
    header('Location: ../dashboard.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
        $error = '不正なリクエストです';
    } else {
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
            $error = '必須項目を入力してください';
        } else {
            // Calculate next renewal date
            $data['next_renewal_date'] = $subscription->calculateNextRenewal($data['start_date'], $data['renewal_cycle']);
            
            if ($subscription->update($subscriptionId, $auth->getCurrentUserId(), $data)) {
                $_SESSION['message'] = 'サブスクリプションを更新しました';
                header('Location: ../dashboard.php');
                exit;
            } else {
                $error = '更新に失敗しました';
            }
        }
    }
}

// Fetch payment methods
$stmt = $db->prepare("SELECT * FROM payment_methods WHERE user_id = :user_id ORDER BY name");
$stmt->execute([':user_id' => $auth->getCurrentUserId()]);
$paymentMethods = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>サブスクリプション編集 - カンリー</title>
    <link rel="stylesheet" href="../css/modern-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <header class="page-header">
            <h1>サブスクリプション編集</h1>
            <a href="../dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i>
                戻る
            </a>
        </header>
        
        <main>
            <div class="form-container">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <?= CSRF::getTokenField() ?>
                    
                    <div class="form-group">
                        <label for="service_name">サービス名 <span class="required">*</span></label>
                        <input type="text" id="service_name" name="service_name" required 
                               value="<?= htmlspecialchars($_POST['service_name'] ?? $subData['service_name']) ?>"
                               class="form-control">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="monthly_fee">月額料金 <span class="required">*</span></label>
                            <input type="number" id="monthly_fee" name="monthly_fee" step="0.01" required 
                                   value="<?= htmlspecialchars($_POST['monthly_fee'] ?? $subData['monthly_fee']) ?>"
                                   class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="currency">通貨</label>
                            <select id="currency" name="currency" class="form-control">
                                <?php
                                $currencies = ['JPY' => '円', 'USD' => 'ドル', 'EUR' => 'ユーロ', 'GBP' => 'ポンド'];
                                $currentCurrency = $_POST['currency'] ?? $subData['currency'];
                                foreach ($currencies as $code => $name): ?>
                                    <option value="<?= $code ?>" <?= $currentCurrency === $code ? 'selected' : '' ?>>
                                        <?= $code ?> (<?= $name ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_method">支払い方法</label>
                        <select id="payment_method" name="payment_method" class="form-control">
                            <?php if (empty($paymentMethods)): ?>
                                <option value="credit_card">クレジットカード（デフォルト）</option>
                            <?php else: ?>
                                <?php 
                                $currentPayment = $_POST['payment_method'] ?? $subData['payment_method'];
                                foreach ($paymentMethods as $method): ?>
                                    <option value="<?= $method['id'] ?>" <?= $currentPayment == $method['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($method['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="renewal_cycle">更新サイクル <span class="required">*</span></label>
                        <select id="renewal_cycle" name="renewal_cycle" required class="form-control">
                            <?php
                            $cycles = [
                                'weekly' => '週更新',
                                'monthly' => '月更新',
                                'quarterly' => '3ヶ月更新',
                                'yearly' => '年更新'
                            ];
                            $currentCycle = $_POST['renewal_cycle'] ?? $subData['renewal_cycle'];
                            foreach ($cycles as $value => $label): ?>
                                <option value="<?= $value ?>" <?= $currentCycle === $value ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_date">開始日 <span class="required">*</span></label>
                            <input type="date" id="start_date" name="start_date" required 
                                   value="<?= htmlspecialchars($_POST['start_date'] ?? $subData['start_date']) ?>"
                                   class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="next_renewal_date">次回更新日</label>
                            <input type="date" id="next_renewal_date" name="next_renewal_date" readonly 
                                   value="<?= htmlspecialchars($subData['next_renewal_date']) ?>"
                                   class="form-control">
                            <small>更新サイクルと開始日から自動計算されます</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_active" value="1" 
                                   <?= ($subData['is_active'] ?? 1) ? 'checked' : '' ?>>
                            <span>このサブスクリプションを有効にする</span>
                        </label>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            更新する
                        </button>
                        <a href="../dashboard.php" class="btn btn-secondary">キャンセル</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
    
    <script>
        // 自動で次回更新日を計算
        function calculateNextRenewal() {
            const startDate = document.getElementById('start_date').value;
            const cycle = document.getElementById('renewal_cycle').value;
            
            if (!startDate) return;
            
            const date = new Date(startDate);
            const today = new Date();
            
            while (date <= today) {
                switch (cycle) {
                    case 'weekly':
                        date.setDate(date.getDate() + 7);
                        break;
                    case 'monthly':
                        date.setMonth(date.getMonth() + 1);
                        break;
                    case 'quarterly':
                        date.setMonth(date.getMonth() + 3);
                        break;
                    case 'yearly':
                        date.setFullYear(date.getFullYear() + 1);
                        break;
                }
            }
            
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            
            document.getElementById('next_renewal_date').value = `${year}-${month}-${day}`;
        }
        
        document.getElementById('start_date').addEventListener('change', calculateNextRenewal);
        document.getElementById('renewal_cycle').addEventListener('change', calculateNextRenewal);
    </script>
    
    <style>
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .form-container {
            background: var(--surface);
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: var(--shadow-sm);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }
        
        .checkbox-label input[type="checkbox"] {
            width: auto;
            margin: 0;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        
        .required {
            color: var(--danger-color);
        }
        
        small {
            display: block;
            margin-top: 0.25rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
    </style>
</body>
</html>