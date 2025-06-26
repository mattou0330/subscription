<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/utils/csrf.php';

use App\Auth;
use App\Utils\CSRF;

$auth = new Auth();
$auth->requireLogin();
$userId = $auth->getCurrentUserId();

$db = Database::getInstance()->getConnection();
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
        $error = '不正なリクエストです';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'add_payment_method':
                $name = trim($_POST['name'] ?? '');
                $type = $_POST['type'] ?? 'credit_card';
                $last_four = preg_replace('/[^0-9]/', '', $_POST['last_four'] ?? '');
                
                if (empty($name)) {
                    $error = '支払い方法の名前を入力してください';
                } elseif (!empty($last_four) && strlen($last_four) !== 4) {
                    $error = '下4桁は4文字で入力してください';
                } else {
                    $stmt = $db->prepare("
                        INSERT INTO payment_methods (user_id, name, type, last_four) 
                        VALUES (:user_id, :name, :type, :last_four)
                    ");
                    if ($stmt->execute([
                        ':user_id' => $userId,
                        ':name' => $name,
                        ':type' => $type,
                        ':last_four' => $last_four ?: null
                    ])) {
                        $message = '支払い方法を追加しました';
                    } else {
                        $error = '支払い方法の追加に失敗しました';
                    }
                }
                break;
                
            case 'update_payment_method':
                $id = (int)($_POST['id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $type = $_POST['type'] ?? 'credit_card';
                $last_four = preg_replace('/[^0-9]/', '', $_POST['last_four'] ?? '');
                
                if ($id && !empty($name)) {
                    if (!empty($last_four) && strlen($last_four) !== 4) {
                        $error = '下4桁は4文字で入力してください';
                    } else {
                        $stmt = $db->prepare("
                            UPDATE payment_methods 
                            SET name = :name, type = :type, last_four = :last_four 
                            WHERE id = :id AND user_id = :user_id
                        ");
                        if ($stmt->execute([
                            ':id' => $id,
                            ':user_id' => $userId,
                            ':name' => $name,
                            ':type' => $type,
                            ':last_four' => $last_four ?: null
                        ])) {
                            $message = '支払い方法を更新しました';
                        }
                    }
                }
                break;
                
            case 'delete_payment_method':
                $id = (int)($_POST['id'] ?? 0);
                if ($id) {
                    // Check if payment method is in use
                    $stmt = $db->prepare("
                        SELECT COUNT(*) FROM subscriptions 
                        WHERE user_id = :user_id AND payment_method = :id AND is_active = 1
                    ");
                    $stmt->execute([':user_id' => $userId, ':id' => $id]);
                    $inUse = $stmt->fetchColumn() > 0;
                    
                    if ($inUse) {
                        $error = 'この支払い方法は使用中のため削除できません';
                    } else {
                        $stmt = $db->prepare("DELETE FROM payment_methods WHERE id = :id AND user_id = :user_id");
                        if ($stmt->execute([':id' => $id, ':user_id' => $userId])) {
                            $message = '支払い方法を削除しました';
                        }
                    }
                }
                break;
                
            case 'set_default':
                $id = (int)($_POST['id'] ?? 0);
                if ($id) {
                    // This is a placeholder for future default payment method functionality
                    $message = 'デフォルトの支払い方法を設定しました';
                }
                break;
        }
    }
}

// Fetch payment methods
$stmt = $db->prepare("SELECT * FROM payment_methods WHERE user_id = :user_id ORDER BY created_at DESC");
$stmt->execute([':user_id' => $userId]);
$paymentMethods = $stmt->fetchAll();

// Count subscriptions per payment method
$stmt = $db->prepare("
    SELECT payment_method, COUNT(*) as count 
    FROM subscriptions 
    WHERE user_id = :user_id AND is_active = 1 
    GROUP BY payment_method
");
$stmt->execute([':user_id' => $userId]);
$usageCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$pageTitle = '支払い方法設定';
$currentPage = 'payment';

$paymentTypeIcons = [
    'credit_card' => 'fa-credit-card',
    'debit_card' => 'fa-credit-card',
    'paypal' => 'fa-brands fa-paypal',
    'bank_transfer' => 'fa-building-columns',
    'other' => 'fa-wallet'
];

$paymentTypeLabels = [
    'credit_card' => 'クレジットカード',
    'debit_card' => 'デビットカード',
    'paypal' => 'PayPal',
    'bank_transfer' => '銀行振込',
    'other' => 'その他'
];

ob_start();
?>

<?php if ($message): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="payment-methods-container">
    <div class="add-payment-card">
        <h3 class="card-title">新しい支払い方法を追加</h3>
        
        <form method="POST" class="payment-form">
            <?= CSRF::getTokenField() ?>
            <input type="hidden" name="action" value="add_payment_method">
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="name" class="form-label">支払い方法の名前 <span class="required">*</span></label>
                    <input type="text" id="name" name="name" class="form-control" 
                           placeholder="例：楽天カード" required>
                </div>
                
                <div class="form-group">
                    <label for="type" class="form-label">種類 <span class="required">*</span></label>
                    <select id="type" name="type" class="form-control" required>
                        <?php foreach ($paymentTypeLabels as $value => $label): ?>
                            <option value="<?= $value ?>"><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="last_four" class="form-label">カード番号下4桁</label>
                    <input type="text" id="last_four" name="last_four" class="form-control" 
                           placeholder="1234" maxlength="4" pattern="[0-9]{4}">
                    <small class="form-hint">クレジット/デビットカードの場合のみ</small>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                支払い方法を追加
            </button>
        </form>
    </div>
    
    <div class="payment-methods-list">
        <h3 class="section-title">登録済みの支払い方法</h3>
        
        <?php if (empty($paymentMethods)): ?>
            <div class="empty-state">
                <i class="fas fa-credit-card empty-icon"></i>
                <p>まだ支払い方法が登録されていません</p>
            </div>
        <?php else: ?>
            <div class="payment-cards">
                <?php foreach ($paymentMethods as $method): ?>
                    <?php $usage = $usageCounts[$method['id']] ?? 0; ?>
                    <div class="payment-card">
                        <div class="payment-icon">
                            <i class="fas <?= $paymentTypeIcons[$method['type']] ?? 'fa-credit-card' ?>"></i>
                        </div>
                        
                        <div class="payment-info">
                            <h4><?= htmlspecialchars($method['name']) ?></h4>
                            <div class="payment-details">
                                <span class="payment-type"><?= $paymentTypeLabels[$method['type']] ?? 'その他' ?></span>
                                <?php if ($method['last_four']): ?>
                                    <span class="payment-number">**** <?= htmlspecialchars($method['last_four']) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ($usage > 0): ?>
                                <div class="usage-info">
                                    <i class="fas fa-info-circle"></i>
                                    <?= $usage ?>件のサブスクリプションで使用中
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="payment-actions">
                            <button class="btn btn-sm btn-secondary" onclick="editPaymentMethod(<?= $method['id'] ?>, '<?= htmlspecialchars($method['name'], ENT_QUOTES) ?>', '<?= $method['type'] ?>', '<?= $method['last_four'] ?>')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if ($usage == 0): ?>
                                <form method="POST" style="display: inline;">
                                    <?= CSRF::getTokenField() ?>
                                    <input type="hidden" name="action" value="delete_payment_method">
                                    <input type="hidden" name="id" value="<?= $method['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" 
                                            onclick="return confirm('この支払い方法を削除しますか？')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3>支払い方法を編集</h3>
        <form method="POST" class="payment-form">
            <?= CSRF::getTokenField() ?>
            <input type="hidden" name="action" value="update_payment_method">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="form-group">
                <label for="edit_name" class="form-label">支払い方法の名前</label>
                <input type="text" id="edit_name" name="name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="edit_type" class="form-label">種類</label>
                <select id="edit_type" name="type" class="form-control" required>
                    <?php foreach ($paymentTypeLabels as $value => $label): ?>
                        <option value="<?= $value ?>"><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="edit_last_four" class="form-label">カード番号下4桁</label>
                <input type="text" id="edit_last_four" name="last_four" class="form-control" 
                       maxlength="4" pattern="[0-9]{4}">
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">キャンセル</button>
                <button type="submit" class="btn btn-primary">更新</button>
            </div>
        </form>
    </div>
</div>

<style>
.payment-methods-container {
    display: grid;
    gap: 2rem;
}

.add-payment-card, .payment-methods-list {
    background: var(--surface);
    padding: 2rem;
    border-radius: 1rem;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
}

.card-title, .section-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
}

.payment-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.form-hint {
    font-size: 0.75rem;
    color: var(--text-secondary);
    margin-top: 0.25rem;
}

.payment-cards {
    display: grid;
    gap: 1rem;
}

.payment-card {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    padding: 1.5rem;
    background: var(--background);
    border-radius: 0.75rem;
    border: 1px solid var(--border-color);
    transition: all 0.2s;
}

.payment-card:hover {
    border-color: var(--primary-color);
    box-shadow: var(--shadow-sm);
}

.payment-icon {
    width: 60px;
    height: 60px;
    background: var(--primary-light);
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.payment-info {
    flex: 1;
}

.payment-info h4 {
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.payment-details {
    display: flex;
    gap: 1rem;
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.payment-type {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.payment-number {
    font-family: monospace;
}

.usage-info {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.5rem;
    font-size: 0.8125rem;
    color: var(--warning-color);
    background: rgba(245, 158, 11, 0.1);
    padding: 0.25rem 0.75rem;
    border-radius: 999px;
}

.payment-actions {
    display: flex;
    gap: 0.5rem;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: var(--text-secondary);
}

.empty-icon {
    font-size: 3rem;
    color: var(--text-light);
    margin-bottom: 1rem;
}

.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: var(--surface);
    padding: 2rem;
    border-radius: 1rem;
    width: 90%;
    max-width: 500px;
}

.modal-content h3 {
    margin-bottom: 1.5rem;
}

.modal-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 2rem;
}

.required {
    color: var(--danger-color);
}

.alert {
    padding: 1rem 1.5rem;
    border-radius: 0.75rem;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-weight: 500;
}

.alert-success {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success-color);
    border: 1px solid rgba(16, 185, 129, 0.2);
}

.alert-error {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger-color);
    border: 1px solid rgba(239, 68, 68, 0.2);
}
</style>

<script>
function editPaymentMethod(id, name, type, lastFour) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_type').value = type;
    document.getElementById('edit_last_four').value = lastFour || '';
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../templates/layout.php';
?>