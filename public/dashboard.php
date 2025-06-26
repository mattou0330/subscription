<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Subscription.php';
require_once __DIR__ . '/../src/utils/csrf.php';

use App\Auth;
use App\Subscription;
use App\Utils\CSRF;

$auth = new Auth();
$auth->requireLogin();

$subscription = new Subscription();
$userId = $auth->getCurrentUserId();
$subscriptions = $subscription->getByUserId($userId);
$subscriptionsByCategory = $subscription->getByCategory($userId);
$monthlyTotal = $subscription->getMonthlyTotal($userId);
$upcomingRenewals = $subscription->getUpcomingRenewals($userId);

// Calculate yearly total
$yearlyTotal = 0;
foreach ($subscriptions as $sub) {
    $multiplier = 1;
    switch ($sub['renewal_cycle']) {
        case 'weekly':
            $multiplier = 52;
            break;
        case 'monthly':
            $multiplier = 12;
            break;
        case 'quarterly':
            $multiplier = 4;
            break;
        case 'yearly':
            $multiplier = 1;
            break;
    }
    $yearlyTotal += $sub['monthly_fee'] * $multiplier;
}

$pageTitle = 'ホーム';
$currentPage = 'home';
$additionalStyles = '<link rel="stylesheet" href="css/dashboard-style.css">';

ob_start();
?>

<div class="stats-grid">
    <div class="stat-card">
        <h3>月額合計</h3>
        <div class="stat-value">
            <span class="currency">¥</span><?= number_format($monthlyTotal) ?>
        </div>
        <div class="stat-trend up">
            <i class="fas fa-arrow-up"></i>
            <span>前月比 +5.2%</span>
        </div>
    </div>
    
    <div class="stat-card">
        <h3>年額合計</h3>
        <div class="stat-value">
            <span class="currency">¥</span><?= number_format($yearlyTotal) ?>
        </div>
        <div class="stat-trend down">
            <i class="fas fa-arrow-down"></i>
            <span>前年比 -2.1%</span>
        </div>
    </div>
    
    <div class="stat-card">
        <h3>契約中のサービス</h3>
        <div class="stat-value"><?= count($subscriptions) ?></div>
        <div class="stat-trend">
            <span><?= count($upcomingRenewals) ?>件の更新予定</span>
        </div>
    </div>
</div>

<?php if (count($upcomingRenewals) > 0): ?>
<div class="alert-banner">
    <i class="fas fa-exclamation-circle"></i>
    <span>まもなく更新されるサービスが<?= count($upcomingRenewals) ?>件あります</span>
</div>
<?php endif; ?>

<?php
// Handle messages from session
$message = $_SESSION['message'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['message'], $_SESSION['error']);
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

<div class="section-header">
    <h2 class="section-title">サブスクリプション</h2>
    <div class="header-controls">
        <div class="status-tabs">
            <button class="status-tab active" onclick="filterByStatus('active')">
                <i class="fas fa-play-circle"></i>
                使用中 (<?= count(array_filter($subscriptions, fn($s) => $s['is_active'])) ?>)
            </button>
            <button class="status-tab" onclick="filterByStatus('inactive')">
                <i class="fas fa-pause-circle"></i>
                停止中 (<?= count(array_filter($subscriptions, fn($s) => !$s['is_active'])) ?>)
            </button>
        </div>
        <div class="action-buttons">
            <button class="btn btn-secondary" onclick="showBulkAddModal()">
                <i class="fas fa-file-excel"></i>
                一括追加
            </button>
            <a href="subscription/add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                新規追加
            </a>
        </div>
    </div>
</div>

<?php if (empty($subscriptions)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">
            <i class="fas fa-inbox"></i>
        </div>
        <h3>サブスクリプションがありません</h3>
        <p>右下の追加ボタンから新しいサブスクリプションを登録しましょう</p>
        <a href="subscription/add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i>
            サブスクリプションを追加
        </a>
    </div>
<?php else: ?>
    <div class="subscriptions-list">
        <table class="subscriptions-table">
            <thead>
                <tr>
                    <th>サービス</th>
                    <th>月額料金</th>
                    <th>次回支払日</th>
                    <th>支払い方法</th>
                    <th>カテゴリ</th>
                    <th>ステータス</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
        <?php foreach ($subscriptions as $sub): ?>
            <?php
            // Get logo URL
            $logoUrl = $sub['logo_url'];
            if (!$logoUrl) {
                // Try to match with known services
                $serviceLower = strtolower($sub['service_name']);
                if (strpos($serviceLower, 'netflix') !== false) {
                    $logoUrl = 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/netflix/netflix-original.svg';
                } elseif (strpos($serviceLower, 'spotify') !== false) {
                    $logoUrl = 'https://upload.wikimedia.org/wikipedia/commons/2/26/Spotify_logo_with_text.svg';
                } elseif (strpos($serviceLower, 'amazon') !== false) {
                    $logoUrl = 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/amazonwebservices/amazonwebservices-original.svg';
                }
            }
            
            $paymentMethodLabels = [
                'credit_card' => 'クレジットカード',
                'debit_card' => 'デビットカード',
                'paypal' => 'PayPal',
                'bank_transfer' => '銀行振込',
                'other' => 'その他'
            ];
            ?>
            <tr class="subscription-row" data-status="<?= $sub['is_active'] ? 'active' : 'inactive' ?>" data-category="<?= htmlspecialchars($sub['category'] ?? 'その他') ?>">
                <td>
                    <div class="service-cell">
                        <div class="service-logo-small">
                            <?php if ($logoUrl): ?>
                                <img src="<?= htmlspecialchars($logoUrl) ?>" alt="<?= htmlspecialchars($sub['service_name']) ?>">
                            <?php else: ?>
                                <span><?= strtoupper(substr($sub['service_name'], 0, 2)) ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="service-name"><?= htmlspecialchars($sub['service_name']) ?></span>
                    </div>
                </td>
                <td>
                    <span class="price-value"><?= $sub['currency'] ?> <?= number_format($sub['monthly_fee']) ?></span>
                </td>
                <td><?= date('Y/m/d', strtotime($sub['next_renewal_date'])) ?></td>
                <td>
                    <span class="payment-method">
                        <i class="fas fa-credit-card"></i>
                        <?= $paymentMethodLabels[$sub['payment_method']] ?? 'クレジットカード' ?>
                    </span>
                </td>
                <td>
                    <span class="category-badge" style="background-color: <?= htmlspecialchars($sub['category_color'] ?? '#95a5a6') ?>">
                        <?= htmlspecialchars($sub['category'] ?? 'その他') ?>
                    </span>
                </td>
                <td>
                    <?php if ($sub['is_active']): ?>
                        <span class="status-badge active">
                            <i class="fas fa-check-circle"></i> 使用中
                        </span>
                    <?php else: ?>
                        <span class="status-badge inactive">
                            <i class="fas fa-pause-circle"></i> 停止中
                        </span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="table-actions">
                        <?php if ($sub['is_active']): ?>
                            <button class="btn btn-sm btn-secondary" onclick="toggleSubscription(<?= $sub['id'] ?>, false)">
                                <i class="fas fa-pause"></i>
                            </button>
                        <?php else: ?>
                            <button class="btn btn-sm btn-success" onclick="toggleSubscription(<?= $sub['id'] ?>, true)">
                                <i class="fas fa-play"></i>
                            </button>
                        <?php endif; ?>
                        <a href="subscription/edit.php?id=<?= $sub['id'] ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button class="btn btn-sm btn-danger" onclick="deleteSubscription(<?= $sub['id'] ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>




<script>
function filterByStatus(status) {
    const rows = document.querySelectorAll('.subscription-row');
    const tabs = document.querySelectorAll('.status-tab');
    
    // Update active tab
    tabs.forEach(tab => {
        if (tab.textContent.includes(status === 'active' ? '使用中' : '停止中')) {
            tab.classList.add('active');
        } else {
            tab.classList.remove('active');
        }
    });
    
    // Filter rows
    rows.forEach(row => {
        if (row.dataset.status === status) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function toggleSubscription(id, activate) {
    const action = activate ? '再開' : '停止';
    const message = activate ? 
        'このサブスクリプションを再開しますか？' : 
        'このサブスクリプションを停止しますか？\n\n停止すると次回の更新が行われなくなります。';
    
    if (confirm(message)) {
        // Implementation for toggling subscription
        console.log(`${action} subscription:`, id);
        // TODO: Ajax call to update subscription status
        location.reload();
    }
}

function deleteSubscription(id) {
    if (confirm('このサブスクリプションを削除しますか？\n\nこの操作は取り消せません。')) {
        // Implementation for deleting subscription
        console.log('Deleting subscription:', id);
        // TODO: Ajax call to delete subscription
        location.reload();
    }
}

function showBulkAddModal() {
    document.getElementById('bulkAddModal').style.display = 'flex';
}

function closeBulkAddModal() {
    document.getElementById('bulkAddModal').style.display = 'none';
    document.getElementById('bulkAddForm').reset();
}

// Initialize with active subscriptions
window.onload = function() {
    filterByStatus('active');
};
</script>

<!-- Bulk Add Modal -->
<div id="bulkAddModal" class="modal" style="display: none;">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h3>サブスクリプション一括追加</h3>
            <button class="modal-close" onclick="closeBulkAddModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="bulkAddForm" method="POST" action="subscription/bulk-add.php">
            <?= CSRF::getTokenField() ?>
            
            <div class="bulk-add-instructions">
                <p><i class="fas fa-info-circle"></i> 複数のサブスクリプションを一度に追加できます。各行に1つのサービスを入力してください。</p>
            </div>
            
            <div class="bulk-add-table">
                <table>
                    <thead>
                        <tr>
                            <th>サービス名 <span class="required">*</span></th>
                            <th>月額料金 <span class="required">*</span></th>
                            <th>通貨</th>
                            <th>支払い方法</th>
                            <th>更新サイクル</th>
                            <th>開始日</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="bulkAddRows">
                        <tr class="bulk-add-row">
                            <td><input type="text" name="services[0][name]" class="form-control" required></td>
                            <td><input type="number" name="services[0][fee]" class="form-control" step="0.01" required></td>
                            <td>
                                <select name="services[0][currency]" class="form-control">
                                    <option value="JPY">JPY</option>
                                    <option value="USD">USD</option>
                                    <option value="EUR">EUR</option>
                                </select>
                            </td>
                            <td>
                                <select name="services[0][payment]" class="form-control">
                                    <option value="credit_card">クレジットカード</option>
                                    <option value="debit_card">デビットカード</option>
                                    <option value="paypal">PayPal</option>
                                    <option value="bank_transfer">銀行振込</option>
                                </select>
                            </td>
                            <td>
                                <select name="services[0][cycle]" class="form-control">
                                    <option value="monthly">月更新</option>
                                    <option value="yearly">年更新</option>
                                    <option value="quarterly">3ヶ月更新</option>
                                </select>
                            </td>
                            <td><input type="date" name="services[0][start_date]" class="form-control" value="<?= date('Y-m-d') ?>"></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-danger" onclick="removeBulkRow(this)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="bulk-add-actions">
                <button type="button" class="btn btn-secondary" onclick="addBulkRow()">
                    <i class="fas fa-plus"></i> 行を追加
                </button>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeBulkAddModal()">キャンセル</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check"></i> 一括追加
                </button>
            </div>
        </form>
    </div>
</div>


<script>
let bulkRowCount = 1;

function addBulkRow() {
    const tbody = document.getElementById('bulkAddRows');
    const newRow = document.createElement('tr');
    newRow.className = 'bulk-add-row';
    newRow.innerHTML = `
        <td><input type="text" name="services[${bulkRowCount}][name]" class="form-control" required></td>
        <td><input type="number" name="services[${bulkRowCount}][fee]" class="form-control" step="0.01" required></td>
        <td>
            <select name="services[${bulkRowCount}][currency]" class="form-control">
                <option value="JPY">JPY</option>
                <option value="USD">USD</option>
                <option value="EUR">EUR</option>
            </select>
        </td>
        <td>
            <select name="services[${bulkRowCount}][payment]" class="form-control">
                <option value="credit_card">クレジットカード</option>
                <option value="debit_card">デビットカード</option>
                <option value="paypal">PayPal</option>
                <option value="bank_transfer">銀行振込</option>
            </select>
        </td>
        <td>
            <select name="services[${bulkRowCount}][cycle]" class="form-control">
                <option value="monthly">月更新</option>
                <option value="yearly">年更新</option>
                <option value="quarterly">3ヶ月更新</option>
            </select>
        </td>
        <td><input type="date" name="services[${bulkRowCount}][start_date]" class="form-control" value="${new Date().toISOString().split('T')[0]}"></td>
        <td>
            <button type="button" class="btn btn-sm btn-danger" onclick="removeBulkRow(this)">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(newRow);
    bulkRowCount++;
}

function removeBulkRow(button) {
    const row = button.closest('tr');
    const tbody = row.parentElement;
    if (tbody.children.length > 1) {
        row.remove();
    }
}

// Close modal when clicking outside
document.getElementById('bulkAddModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeBulkAddModal();
    }
});
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../templates/layout.php';
?>