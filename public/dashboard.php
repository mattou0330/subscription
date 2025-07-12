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

// Fetch user's payment methods for bulk add and display
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT * FROM payment_methods WHERE user_id = :user_id ORDER BY name");
$stmt->execute([':user_id' => $userId]);
$paymentMethods = $stmt->fetchAll();

// Create payment methods lookup array
$paymentMethodsById = [];
foreach ($paymentMethods as $method) {
    $paymentMethodsById[$method['id']] = $method['name'];
}

// Fetch categories from database
$stmt = $db->prepare("SELECT * FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll();

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
            <button class="btn btn-primary" onclick="showAddModal()">
                <i class="fas fa-plus"></i>
                新規追加
            </button>
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
        <button class="btn btn-primary" onclick="showAddModal()">
            <i class="fas fa-plus"></i>
            サブスクリプションを追加
        </button>
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
            // アイコンの取得（データベースに保存されていない場合は自動検出）
            require_once __DIR__ . '/../src/ServiceIcons.php';
            $logoUrl = $sub['logo_url'] ?? App\ServiceIcons::getIconUrl($sub['service_name']);
            $initials = App\ServiceIcons::getInitials($sub['service_name']);
            $bgColor = App\ServiceIcons::getCategoryColor($sub['category'] ?? 'その他');
            
            $paymentMethodLabels = [
                'credit_card' => 'クレジットカード',
                'debit_card' => 'デビットカード',
                'paypal' => 'PayPal',
                'bank_transfer' => '銀行振込',
                'apple_pay' => 'Apple Pay',
                'paypay' => 'PayPay',
                'other' => 'その他'
            ];
            ?>
            <tr class="subscription-row" data-status="<?= $sub['is_active'] ? 'active' : 'inactive' ?>" data-category="<?= htmlspecialchars($sub['category'] ?? 'その他') ?>">
                <td>
                    <div class="service-cell">
                        <div class="service-logo-small" style="background-color: <?= $bgColor ?>">
                            <span><?= htmlspecialchars($initials) ?></span>
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
                        <?php 
                        // Display payment method name
                        if (!empty($sub['payment_method_id']) && isset($paymentMethodsById[$sub['payment_method_id']])) {
                            $paymentName = $paymentMethodsById[$sub['payment_method_id']];
                        } else {
                            $paymentName = $paymentMethodLabels[$sub['payment_method']] ?? 'クレジットカード';
                        }
                        
                        // Get appropriate icon
                        $paymentIcon = 'fa-credit-card';
                        if ($sub['payment_method'] === 'bank_transfer') $paymentIcon = 'fa-building-columns';
                        elseif ($sub['payment_method'] === 'paypal') $paymentIcon = 'fa-brands fa-paypal';
                        elseif ($sub['payment_method'] === 'apple_pay') $paymentIcon = 'fa-brands fa-apple';
                        elseif ($sub['payment_method'] === 'paypay') $paymentIcon = 'fa-mobile-alt';
                        ?>
                        <i class="fas <?= $paymentIcon ?>"></i>
                        <?= htmlspecialchars($paymentName) ?>
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
                        <button class="btn btn-sm btn-primary" onclick="showEditModal(<?= $sub['id'] ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
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
        fetch('api/subscription-toggle.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id, activate: activate })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('エラー: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('エラーが発生しました');
        });
    }
}

function deleteSubscription(id) {
    if (confirm('このサブスクリプションを削除しますか？\n\nこの操作は取り消せません。')) {
        fetch('api/subscription-delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('エラー: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('エラーが発生しました');
        });
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

// Add/Edit Modal Functions
function showAddModal() {
    document.getElementById('modalTitle').textContent = 'サブスクリプション追加';
    document.getElementById('submitButtonText').textContent = '登録する';
    document.getElementById('subscriptionForm').reset();
    document.getElementById('subscriptionId').value = '';
    document.getElementById('activeCheckboxGroup').style.display = 'none';
    document.getElementById('start_date').value = new Date().toISOString().split('T')[0];
    loadPaymentMethods();
    calculateNextRenewal();
    document.getElementById('subscriptionModal').style.display = 'flex';
}

function showEditModal(id) {
    document.getElementById('modalTitle').textContent = 'サブスクリプション編集';
    document.getElementById('submitButtonText').textContent = '更新する';
    document.getElementById('activeCheckboxGroup').style.display = 'block';
    
    // サブスクリプションデータを取得
    fetch(`api/subscription-get.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const sub = data.subscription;
                document.getElementById('subscriptionId').value = sub.id;
                document.getElementById('service_name').value = sub.service_name;
                document.getElementById('monthly_fee').value = sub.monthly_fee;
                document.getElementById('currency').value = sub.currency;
                document.getElementById('renewal_cycle').value = sub.renewal_cycle;
                document.getElementById('start_date').value = sub.start_date;
                document.getElementById('next_renewal_date').value = sub.next_renewal_date;
                document.getElementById('is_active').checked = sub.is_active == 1;
                document.getElementById('logo_url').value = sub.logo_url || '';
                document.getElementById('category').value = sub.category || 'other';
                
                // 支払い方法を設定
                loadPaymentMethods(sub.payment_method);
                
                // アイコンプレビューを更新
                updateIconPreview();
                
                document.getElementById('subscriptionModal').style.display = 'flex';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('データの取得に失敗しました');
        });
}

function closeSubscriptionModal() {
    document.getElementById('subscriptionModal').style.display = 'none';
    document.getElementById('subscriptionForm').reset();
}

function loadPaymentMethods(selectedId = null) {
    // 支払い方法を動的に読み込む
    fetch('payment-methods.php?ajax=1')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('payment_method');
            select.innerHTML = '';
            
            if (data.methods && data.methods.length > 0) {
                data.methods.forEach(method => {
                    const option = document.createElement('option');
                    option.value = method.id;
                    option.textContent = method.name;
                    if (selectedId && method.id == selectedId) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });
            } else {
                const option = document.createElement('option');
                option.value = 'credit_card';
                option.textContent = 'クレジットカード（デフォルト）';
                select.appendChild(option);
            }
        })
        .catch(error => {
            console.error('Error loading payment methods:', error);
            const select = document.getElementById('payment_method');
            select.innerHTML = '<option value="credit_card">クレジットカード（デフォルト）</option>';
        });
}

// 次回更新日を計算
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
            case 'semiannually':
                date.setMonth(date.getMonth() + 6);
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

// アイコンの自動検出
function autoDetectIcon() {
    const serviceName = document.getElementById('service_name').value;
    if (!serviceName) return;
    
    // サーバーサイドのServiceIconsクラスと同じロジックを実装
    const serviceIcons = {
        'netflix': 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/netflix/netflix-original.svg',
        'spotify': 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/spotify/spotify-original.svg',
        'youtube': 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/youtube/youtube-original.svg',
        'disney': 'https://upload.wikimedia.org/wikipedia/commons/3/3e/Disney%2B_logo.svg',
        'amazon prime': 'https://upload.wikimedia.org/wikipedia/commons/1/11/Amazon_Prime_Video_logo.svg',
        'apple music': 'https://upload.wikimedia.org/wikipedia/commons/2/2a/Apple_Music_Icon.svg',
        'google drive': 'https://upload.wikimedia.org/wikipedia/commons/1/12/Google_Drive_icon_%282020%29.svg',
        'dropbox': 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/dropbox/dropbox-original.svg',
        'github': 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/github/github-original.svg',
        'slack': 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/slack/slack-original.svg',
        'notion': 'https://upload.wikimedia.org/wikipedia/commons/4/45/Notion_app_logo.png',
        'chatgpt': 'https://upload.wikimedia.org/wikipedia/commons/0/04/ChatGPT_logo.svg'
    };
    
    // カテゴリの自動検出
    const serviceCategories = {
        'netflix': 'entertainment',
        'spotify': 'entertainment',
        'youtube': 'entertainment',
        'disney': 'entertainment',
        'amazon prime': 'entertainment',
        'apple music': 'entertainment',
        'google drive': 'cloud_storage',
        'dropbox': 'cloud_storage',
        'icloud': 'cloud_storage',
        'github': 'development',
        'slack': 'communication',
        'notion': 'productivity',
        'chatgpt': 'productivity',
        'zoom': 'communication',
        'microsoft teams': 'communication',
        'adobe': 'productivity',
        'figma': 'development',
        'canva': 'productivity'
    };
    
    const serviceNameLower = serviceName.toLowerCase();
    
    // カテゴリの自動設定
    for (const [service, category] of Object.entries(serviceCategories)) {
        if (serviceNameLower.includes(service)) {
            document.getElementById('category').value = category;
            break;
        }
    }
    let iconUrl = null;
    
    // 完全一致または部分一致を確認
    for (const [key, url] of Object.entries(serviceIcons)) {
        if (serviceNameLower.includes(key) || key.includes(serviceNameLower)) {
            iconUrl = url;
            break;
        }
    }
    
    if (iconUrl && !document.getElementById('logo_url').value) {
        document.getElementById('logo_url').value = iconUrl;
        updateIconPreview();
    }
}

// アイコンプレビューの更新
function updateIconPreview() {
    const url = document.getElementById('logo_url').value;
    const preview = document.getElementById('iconPreview');
    
    if (url) {
        preview.innerHTML = `<img src="${url}" alt="Preview" onerror="this.style.display='none'">`;
    } else {
        preview.innerHTML = '';
    }
}

// DOMContentLoadedイベントで要素が読み込まれてからイベントリスナーを追加
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('start_date').addEventListener('change', calculateNextRenewal);
    document.getElementById('renewal_cycle').addEventListener('change', calculateNextRenewal);
    document.getElementById('logo_url').addEventListener('input', updateIconPreview);
    
    // フォーム送信処理
    document.getElementById('subscriptionForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const isEdit = document.getElementById('subscriptionId').value !== '';
        const url = isEdit ? 'api/subscription-update.php' : 'api/subscription-create.php';
        
        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('エラー: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('エラーが発生しました');
        });
    });
    
    // モーダル外クリックで閉じる
    document.getElementById('subscriptionModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeSubscriptionModal();
        }
    });
});
</script>

<!-- Add/Edit Modal -->
<div id="subscriptionModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">サブスクリプション追加</h3>
            <button class="modal-close" onclick="closeSubscriptionModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="subscriptionForm" method="POST">
            <?= CSRF::getTokenField() ?>
            <input type="hidden" id="subscriptionId" name="id" value="">
            
            <div class="form-group">
                <label for="service_name">サービス名 <span class="required">*</span></label>
                <input type="text" id="service_name" name="service_name" class="form-control" required onchange="autoDetectIcon()">
                <small>有名なサービスの場合、アイコンが自動設定されます</small>
            </div>
            
            <div class="form-group">
                <label for="logo_url">アイコンURL（オプション）</label>
                <div class="icon-preview-row">
                    <input type="text" id="logo_url" name="logo_url" class="form-control" placeholder="https://example.com/icon.png">
                    <div id="iconPreview" class="icon-preview"></div>
                </div>
                <small>カスタムアイコンのURLを指定できます</small>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="monthly_fee">月額料金 <span class="required">*</span></label>
                    <input type="number" id="monthly_fee" name="monthly_fee" class="form-control" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label for="currency">通貨</label>
                    <select id="currency" name="currency" class="form-control">
                        <option value="JPY">JPY (円)</option>
                        <option value="USD">USD (ドル)</option>
                        <option value="EUR">EUR (ユーロ)</option>
                        <option value="GBP">GBP (ポンド)</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="payment_method">支払い方法</label>
                <select id="payment_method" name="payment_method" class="form-control">
                    <!-- 動的に追加 -->
                </select>
            </div>
            
            <div class="form-group">
                <label for="category">カテゴリ</label>
                <select id="category" name="category" class="form-control">
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= htmlspecialchars($category['slug']) ?>"><?= htmlspecialchars($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="renewal_cycle">更新サイクル <span class="required">*</span></label>
                <select id="renewal_cycle" name="renewal_cycle" class="form-control" required>
                    <option value="weekly">週更新</option>
                    <option value="monthly" selected>月更新</option>
                    <option value="quarterly">3ヶ月更新</option>
                    <option value="semiannually">6ヶ月更新</option>
                    <option value="yearly">年更新</option>
                </select>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="start_date">開始日 <span class="required">*</span></label>
                    <input type="date" id="start_date" name="start_date" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="next_renewal_date">次回更新日</label>
                    <input type="date" id="next_renewal_date" name="next_renewal_date" class="form-control" readonly>
                    <small>更新サイクルと開始日から自動計算されます</small>
                </div>
            </div>
            
            <div class="form-group" id="activeCheckboxGroup" style="display: none;">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_active" id="is_active" value="1">
                    <span>このサブスクリプションを有効にする</span>
                </label>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeSubscriptionModal()">キャンセル</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    <span id="submitButtonText">登録する</span>
                </button>
            </div>
        </form>
    </div>
</div>

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
                                    <?php if (empty($paymentMethods)): ?>
                                        <!-- デフォルトの支払い方法 -->
                                        <option value="credit_card">クレジットカード</option>
                                        <option value="debit_card">デビットカード</option>
                                        <option value="paypal">PayPal</option>
                                        <option value="bank_transfer">銀行振込</option>
                                        <option value="apple_pay">Apple Pay</option>
                                        <option value="paypay">PayPay</option>
                                        <option value="other">その他</option>
                                    <?php else: ?>
                                        <!-- 登録済みの支払い方法: <?= count($paymentMethods) ?>件 -->
                                        <?php foreach ($paymentMethods as $method): ?>
                                            <option value="<?= $method['id'] ?>"><?= htmlspecialchars($method['name']) ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </td>
                            <td>
                                <select name="services[0][cycle]" class="form-control">
                                    <option value="weekly">週更新</option>
                                    <option value="monthly" selected>月更新</option>
                                    <option value="quarterly">3ヶ月更新</option>
                                    <option value="semi_annually">6ヶ月更新</option>
                                    <option value="yearly">年更新</option>
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
const paymentMethods = <?= json_encode($paymentMethods) ?>;

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

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
                ${paymentMethods.length === 0 ? `
                    <option value="credit_card">クレジットカード</option>
                    <option value="debit_card">デビットカード</option>
                    <option value="paypal">PayPal</option>
                    <option value="bank_transfer">銀行振込</option>
                    <option value="apple_pay">Apple Pay</option>
                    <option value="paypay">PayPay</option>
                    <option value="other">その他</option>
                ` : paymentMethods.map(method => 
                    `<option value="${method.id}">${escapeHtml(method.name)}</option>`
                ).join('')}
            </select>
        </td>
        <td>
            <select name="services[${bulkRowCount}][cycle]" class="form-control">
                <option value="weekly">週更新</option>
                <option value="monthly" selected>月更新</option>
                <option value="quarterly">3ヶ月更新</option>
                <option value="semi_annually">6ヶ月更新</option>
                <option value="yearly">年更新</option>
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

<style>
/* モーダルのスタイル */
.modal .form-group {
    margin-bottom: 1rem;
}

.modal .form-control {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid var(--border-color);
    border-radius: 0.375rem;
    font-size: 0.875rem;
}

.modal .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.modal .checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.modal .checkbox-label input[type="checkbox"] {
    width: auto;
    margin: 0;
}

.modal small {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.75rem;
    color: var(--text-secondary);
}

.modal .modal-footer {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border-color);
    justify-content: flex-end;
}

.required {
    color: var(--danger-color);
}

.icon-preview-row {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.icon-preview {
    width: 48px;
    height: 48px;
    border: 1px solid var(--border-color);
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--background);
    flex-shrink: 0;
}

.icon-preview img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}
</style>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../templates/layout.php';
?>