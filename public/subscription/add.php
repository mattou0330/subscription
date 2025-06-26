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
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
        $error = '不正なリクエストです';
    } else {
        $data = [
            'service_name' => filter_input(INPUT_POST, 'service_name', FILTER_SANITIZE_STRING),
            'monthly_fee' => filter_input(INPUT_POST, 'monthly_fee', FILTER_VALIDATE_FLOAT),
            'currency' => filter_input(INPUT_POST, 'currency', FILTER_SANITIZE_STRING),
            'renewal_cycle' => filter_input(INPUT_POST, 'renewal_cycle', FILTER_SANITIZE_STRING),
            'start_date' => $_POST['start_date'] ?? ''
        ];
        
        if (empty($data['service_name']) || !$data['monthly_fee'] || empty($data['start_date'])) {
            $error = '必須項目を入力してください';
        } else {
            $data['next_renewal_date'] = $subscription->calculateNextRenewal($data['start_date'], $data['renewal_cycle']);
            
            if ($subscription->create($auth->getCurrentUserId(), $data)) {
                header('Location: ../dashboard.php');
                exit;
            } else {
                $error = '登録に失敗しました';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>サブスクリプション追加 - サブスクリプション管理</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <header class="page-header">
            <h1>サブスクリプション追加</h1>
            <a href="../dashboard.php" class="btn btn-secondary">戻る</a>
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
                               value="<?= htmlspecialchars($_POST['service_name'] ?? '') ?>">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="monthly_fee">月額料金 <span class="required">*</span></label>
                            <input type="number" id="monthly_fee" name="monthly_fee" step="0.01" required 
                                   value="<?= htmlspecialchars($_POST['monthly_fee'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="currency">通貨</label>
                            <select id="currency" name="currency">
                                <option value="JPY" <?= ($_POST['currency'] ?? 'JPY') === 'JPY' ? 'selected' : '' ?>>JPY (円)</option>
                                <option value="USD" <?= ($_POST['currency'] ?? '') === 'USD' ? 'selected' : '' ?>>USD (ドル)</option>
                                <option value="EUR" <?= ($_POST['currency'] ?? '') === 'EUR' ? 'selected' : '' ?>>EUR (ユーロ)</option>
                                <option value="GBP" <?= ($_POST['currency'] ?? '') === 'GBP' ? 'selected' : '' ?>>GBP (ポンド)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_method">支払い方法</label>
                        <select id="payment_method" name="payment_method">
                            <?php
                            // Fetch user's payment methods
                            $db = \Database::getInstance()->getConnection();
                            $stmt = $db->prepare("SELECT * FROM payment_methods WHERE user_id = :user_id ORDER BY name");
                            $stmt->execute([':user_id' => $auth->getCurrentUserId()]);
                            $paymentMethods = $stmt->fetchAll();
                            
                            if (empty($paymentMethods)): ?>
                                <option value="credit_card">クレジットカード（デフォルト）</option>
                            <?php else: ?>
                                <?php foreach ($paymentMethods as $method): ?>
                                    <option value="<?= $method['id'] ?>"><?= htmlspecialchars($method['name']) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <?php if (empty($paymentMethods)): ?>
                            <small>支払い方法を登録するには<a href="../payment-methods.php">こちら</a></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="renewal_cycle">更新サイクル <span class="required">*</span></label>
                        <select id="renewal_cycle" name="renewal_cycle" required>
                            <option value="monthly" <?= ($_POST['renewal_cycle'] ?? 'monthly') === 'monthly' ? 'selected' : '' ?>>月更新</option>
                            <option value="yearly" <?= ($_POST['renewal_cycle'] ?? '') === 'yearly' ? 'selected' : '' ?>>年更新</option>
                            <option value="quarterly" <?= ($_POST['renewal_cycle'] ?? '') === 'quarterly' ? 'selected' : '' ?>>3ヶ月更新</option>
                            <option value="weekly" <?= ($_POST['renewal_cycle'] ?? '') === 'weekly' ? 'selected' : '' ?>>週更新</option>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_date">開始日 <span class="required">*</span></label>
                            <input type="date" id="start_date" name="start_date" required 
                                   value="<?= htmlspecialchars($_POST['start_date'] ?? date('Y-m-d')) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="next_renewal_date">次回更新日</label>
                            <input type="date" id="next_renewal_date" name="next_renewal_date" readonly 
                                   value="<?= htmlspecialchars($_POST['next_renewal_date'] ?? '') ?>">
                            <small>更新サイクルと開始日から自動計算されます</small>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">登録する</button>
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
        
        // 初期計算
        calculateNextRenewal();
    </script>
</body>
</html>