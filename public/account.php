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

// Fetch current user data
$stmt = $db->prepare("SELECT * FROM users WHERE id = :user_id");
$stmt->execute([':user_id' => $userId]);
$userData = $stmt->fetch();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
        $error = '不正なリクエストです';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'update_profile':
                $name = trim($_POST['name'] ?? '');
                $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
                
                if (!$email) {
                    $error = '有効なメールアドレスを入力してください';
                } else {
                    // Check if email is already in use by another user
                    $stmt = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :user_id");
                    $stmt->execute([':email' => $email, ':user_id' => $userId]);
                    
                    if ($stmt->fetch()) {
                        $error = 'このメールアドレスは既に使用されています';
                    } else {
                        $stmt = $db->prepare("UPDATE users SET name = :name, email = :email WHERE id = :user_id");
                        if ($stmt->execute([':name' => $name, ':email' => $email, ':user_id' => $userId])) {
                            $message = 'プロフィールを更新しました';
                            $_SESSION['user_email'] = $email;
                            $_SESSION['user_name'] = $name;
                            $userData['name'] = $name;
                            $userData['email'] = $email;
                        } else {
                            $error = 'プロフィールの更新に失敗しました';
                        }
                    }
                }
                break;
                
            case 'change_password':
                $currentPassword = $_POST['current_password'] ?? '';
                $newPassword = $_POST['new_password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';
                
                if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                    $error = 'すべてのパスワードフィールドを入力してください';
                } elseif (strlen($newPassword) < 8) {
                    $error = '新しいパスワードは8文字以上で入力してください';
                } elseif ($newPassword !== $confirmPassword) {
                    $error = '新しいパスワードが一致しません';
                } elseif (!password_verify($currentPassword, $userData['password'])) {
                    $error = '現在のパスワードが正しくありません';
                } else {
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE users SET password = :password WHERE id = :user_id");
                    if ($stmt->execute([':password' => $hashedPassword, ':user_id' => $userId])) {
                        $message = 'パスワードを変更しました';
                    } else {
                        $error = 'パスワードの変更に失敗しました';
                    }
                }
                break;
                
            case 'delete_account':
                $confirmDelete = $_POST['confirm_delete'] ?? '';
                if ($confirmDelete === 'DELETE') {
                    // Delete user and all related data (cascading delete)
                    $stmt = $db->prepare("DELETE FROM users WHERE id = :user_id");
                    if ($stmt->execute([':user_id' => $userId])) {
                        session_destroy();
                        header('Location: index.php');
                        exit;
                    } else {
                        $error = 'アカウントの削除に失敗しました';
                    }
                } else {
                    $error = '確認テキストが正しくありません';
                }
                break;
        }
    }
}

// Calculate account statistics
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_subscriptions,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_subscriptions,
        MIN(created_at) as first_subscription
    FROM subscriptions 
    WHERE user_id = :user_id
");
$stmt->execute([':user_id' => $userId]);
$stats = $stmt->fetch();

$accountAge = new DateTime($userData['created_at']);
$now = new DateTime();
$interval = $accountAge->diff($now);

$pageTitle = 'アカウント設定';
$currentPage = 'account';

ob_start();
?>

<div class="account-container">
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

    <div class="account-header">
        <div class="account-avatar">
            <i class="fas fa-user-circle"></i>
        </div>
        <div class="account-info">
            <h2><?= htmlspecialchars($userData['name'] ?: 'ユーザー') ?></h2>
            <p><?= htmlspecialchars($userData['email']) ?></p>
            <p class="account-meta">
                登録日: <?= date('Y年m月d日', strtotime($userData['created_at'])) ?>
                （<?= $interval->y ?>年<?= $interval->m ?>ヶ月）
            </p>
        </div>
    </div>

    <div class="stats-row">
        <div class="stat-item">
            <div class="stat-value"><?= $stats['total_subscriptions'] ?></div>
            <div class="stat-label">総登録数</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= $stats['active_subscriptions'] ?></div>
            <div class="stat-label">アクティブ</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= $interval->days ?></div>
            <div class="stat-label">利用日数</div>
        </div>
    </div>

    <div class="settings-section">
        <h3 class="section-title">
            <i class="fas fa-user-edit"></i>
            プロフィール設定
        </h3>
        
        <form method="POST" class="settings-form">
            <?= CSRF::getTokenField() ?>
            <input type="hidden" name="action" value="update_profile">
            
            <div class="form-group">
                <label for="name" class="form-label">お名前</label>
                <input type="text" id="name" name="name" class="form-control" 
                       value="<?= htmlspecialchars($userData['name'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="email" class="form-label">メールアドレス <span class="required">*</span></label>
                <input type="email" id="email" name="email" class="form-control" 
                       value="<?= htmlspecialchars($userData['email']) ?>" required>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i>
                プロフィールを更新
            </button>
        </form>
    </div>

    <div class="settings-section">
        <h3 class="section-title">
            <i class="fas fa-lock"></i>
            パスワード変更
        </h3>
        
        <form method="POST" class="settings-form">
            <?= CSRF::getTokenField() ?>
            <input type="hidden" name="action" value="change_password">
            
            <div class="form-group">
                <label for="current_password" class="form-label">現在のパスワード <span class="required">*</span></label>
                <input type="password" id="current_password" name="current_password" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="new_password" class="form-label">新しいパスワード <span class="required">*</span></label>
                <input type="password" id="new_password" name="new_password" class="form-control" 
                       minlength="8" required>
                <small class="form-hint">8文字以上で入力してください</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password" class="form-label">新しいパスワード（確認） <span class="required">*</span></label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                       minlength="8" required>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-key"></i>
                パスワードを変更
            </button>
        </form>
    </div>

    <div class="settings-section danger-zone">
        <h3 class="section-title">
            <i class="fas fa-exclamation-triangle"></i>
            危険な操作
        </h3>
        
        <div class="danger-box">
            <h4>アカウントの削除</h4>
            <p>アカウントを削除すると、すべてのデータが完全に削除され、復元することはできません。</p>
            
            <button type="button" class="btn btn-danger" onclick="showDeleteModal()">
                <i class="fas fa-trash"></i>
                アカウントを削除
            </button>
        </div>
    </div>
</div>

<!-- Delete Account Modal -->
<div id="deleteModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3 class="modal-title">本当にアカウントを削除しますか？</h3>
        
        <div class="warning-box">
            <p><strong>警告：</strong>この操作は取り消せません。</p>
            <p>アカウントを削除すると、以下のデータがすべて失われます：</p>
            <ul>
                <li>登録されているすべてのサブスクリプション</li>
                <li>支払い履歴</li>
                <li>通知設定</li>
                <li>その他すべての個人データ</li>
            </ul>
        </div>
        
        <form method="POST" class="delete-form">
            <?= CSRF::getTokenField() ?>
            <input type="hidden" name="action" value="delete_account">
            
            <div class="form-group">
                <label class="form-label">
                    確認のため「DELETE」と入力してください
                </label>
                <input type="text" name="confirm_delete" class="form-control" 
                       placeholder="DELETE" required>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">
                    キャンセル
                </button>
                <button type="submit" class="btn btn-danger">
                    アカウントを完全に削除
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.account-container {
    max-width: 800px;
    margin: 0 auto;
}

.account-header {
    display: flex;
    align-items: center;
    gap: 2rem;
    background: var(--surface);
    padding: 2rem;
    border-radius: 1rem;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
    margin-bottom: 2rem;
}

.account-avatar {
    font-size: 5rem;
    color: var(--primary-color);
}

.account-info h2 {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.account-info p {
    color: var(--text-secondary);
    margin-bottom: 0.25rem;
}

.account-meta {
    font-size: 0.875rem;
}

.stats-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-item {
    background: var(--surface);
    padding: 1.5rem;
    border-radius: 0.75rem;
    text-align: center;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--primary-color);
}

.stat-label {
    font-size: 0.875rem;
    color: var(--text-secondary);
    margin-top: 0.5rem;
}

.settings-section {
    background: var(--surface);
    padding: 2rem;
    border-radius: 1rem;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
    margin-bottom: 2rem;
}

.section-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.settings-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.danger-zone {
    border: 2px solid rgba(239, 68, 68, 0.2);
}

.danger-zone .section-title {
    color: var(--danger-color);
}

.danger-box {
    background: rgba(239, 68, 68, 0.05);
    padding: 1.5rem;
    border-radius: 0.5rem;
    border: 1px solid rgba(239, 68, 68, 0.1);
}

.danger-box h4 {
    margin-bottom: 0.5rem;
}

.danger-box p {
    color: var(--text-secondary);
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

.modal-title {
    margin-bottom: 1.5rem;
    color: var(--danger-color);
}

.warning-box {
    background: rgba(239, 68, 68, 0.05);
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
    border: 1px solid rgba(239, 68, 68, 0.1);
}

.warning-box ul {
    margin-top: 0.5rem;
    margin-left: 1.5rem;
}

.delete-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.modal-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
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

.required {
    color: var(--danger-color);
}

.form-hint {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.75rem;
    color: var(--text-secondary);
}
</style>

<script>
function showDeleteModal() {
    document.getElementById('deleteModal').style.display = 'flex';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
    document.querySelector('input[name="confirm_delete"]').value = '';
}

// Close modal when clicking outside
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../templates/layout.php';
?>