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

// Fetch current user settings
$stmt = $db->prepare("SELECT notification_days, notification_email, notification_enabled FROM users WHERE id = :user_id");
$stmt->execute([':user_id' => $userId]);
$userSettings = $stmt->fetch();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
        $error = '不正なリクエストです';
    } else {
        $notificationDays = (int)($_POST['notification_days'] ?? 3);
        $notificationEmail = filter_input(INPUT_POST, 'notification_email', FILTER_VALIDATE_EMAIL);
        $notificationEnabled = isset($_POST['notification_enabled']) ? 1 : 0;
        
        if ($notificationDays < 1 || $notificationDays > 30) {
            $error = '通知日数は1日から30日の間で設定してください';
        } elseif (!empty($_POST['notification_email']) && !$notificationEmail) {
            $error = '有効なメールアドレスを入力してください';
        } else {
            $stmt = $db->prepare("
                UPDATE users 
                SET notification_days = :days, 
                    notification_email = :email, 
                    notification_enabled = :enabled 
                WHERE id = :user_id
            ");
            
            if ($stmt->execute([
                ':days' => $notificationDays,
                ':email' => $notificationEmail ?: null,
                ':enabled' => $notificationEnabled,
                ':user_id' => $userId
            ])) {
                $message = '通知設定を更新しました';
                // Refresh settings
                $userSettings = [
                    'notification_days' => $notificationDays,
                    'notification_email' => $notificationEmail,
                    'notification_enabled' => $notificationEnabled
                ];
            } else {
                $error = '設定の更新に失敗しました';
            }
        }
    }
}

$pageTitle = '通知設定';
$currentPage = 'notifications';

ob_start();
?>

<div class="settings-container">
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

    <div class="settings-card">
        <h3 class="settings-title">
            <i class="fas fa-bell"></i>
            更新通知設定
        </h3>
        <p class="settings-description">
            サブスクリプションの更新日が近づいたときに、メールで通知を受け取る設定ができます。
        </p>

        <form method="POST" class="settings-form">
            <?= CSRF::getTokenField() ?>
            
            <div class="form-group">
                <label class="form-label">
                    <input type="checkbox" name="notification_enabled" value="1" 
                           <?= $userSettings['notification_enabled'] ? 'checked' : '' ?>>
                    <span>更新通知を有効にする</span>
                </label>
            </div>

            <div class="notification-settings" id="notificationSettings">
                <div class="form-group">
                    <label for="notification_days" class="form-label">通知タイミング</label>
                    <div class="input-group">
                        <span class="input-prefix">更新日の</span>
                        <input type="number" id="notification_days" name="notification_days" 
                               class="form-control inline-number" 
                               value="<?= htmlspecialchars($userSettings['notification_days'] ?? 3) ?>" 
                               min="1" max="30" required>
                        <span class="input-suffix">日前に通知</span>
                    </div>
                    <small class="form-hint">1〜30日の間で設定できます</small>
                </div>

                <div class="form-group">
                    <label for="notification_email" class="form-label">通知先メールアドレス</label>
                    <input type="email" id="notification_email" name="notification_email" 
                           class="form-control" 
                           value="<?= htmlspecialchars($userSettings['notification_email'] ?? $_SESSION['user_email']) ?>"
                           placeholder="notification@example.com">
                    <small class="form-hint">空欄の場合は、ログイン用のメールアドレスに送信されます</small>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i>
                設定を保存
            </button>
        </form>
    </div>

    <div class="settings-card">
        <h3 class="settings-title">
            <i class="fas fa-info-circle"></i>
            通知の仕組み
        </h3>
        <div class="info-box">
            <ul class="info-list">
                <li>毎日午前9時に、設定した日数以内に更新されるサブスクリプションをチェックします</li>
                <li>該当するサービスがある場合、登録されたメールアドレスに通知メールを送信します</li>
                <li>通知メールには、更新予定のサービス名と金額が含まれます</li>
                <li>通知を無効にすると、メールは送信されません</li>
            </ul>
        </div>
    </div>

    <div class="settings-card">
        <h3 class="settings-title">
            <i class="fas fa-history"></i>
            最近の通知履歴
        </h3>
        <?php
        // Fetch recent notifications
        $stmt = $db->prepare("
            SELECT n.*, s.service_name 
            FROM notifications n
            JOIN subscriptions s ON n.subscription_id = s.id
            WHERE n.user_id = :user_id
            ORDER BY n.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([':user_id' => $userId]);
        $notifications = $stmt->fetchAll();
        ?>
        
        <?php if (empty($notifications)): ?>
            <p class="empty-state">まだ通知履歴がありません</p>
        <?php else: ?>
            <div class="notification-history">
                <?php foreach ($notifications as $notification): ?>
                    <div class="history-item">
                        <div class="history-info">
                            <strong><?= htmlspecialchars($notification['service_name']) ?></strong>
                            <span class="history-date">
                                更新日: <?= date('Y年m月d日', strtotime($notification['notification_date'])) ?>
                            </span>
                        </div>
                        <div class="history-status">
                            <?php if ($notification['is_sent']): ?>
                                <span class="badge badge-success">
                                    <i class="fas fa-check"></i> 送信済み
                                </span>
                                <small><?= date('Y/m/d H:i', strtotime($notification['sent_at'])) ?></small>
                            <?php else: ?>
                                <span class="badge badge-pending">
                                    <i class="fas fa-clock"></i> 送信予定
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.settings-container {
    max-width: 800px;
    margin: 0 auto;
}

.settings-card {
    background: var(--surface);
    padding: 2rem;
    border-radius: 1rem;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
    margin-bottom: 2rem;
}

.settings-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.settings-description {
    color: var(--text-secondary);
    margin-bottom: 2rem;
}

.settings-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.input-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.input-prefix, .input-suffix {
    color: var(--text-secondary);
    font-size: 0.875rem;
}

.inline-number {
    width: 80px;
    text-align: center;
}

.form-hint {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.75rem;
    color: var(--text-secondary);
}

.form-label input[type="checkbox"] {
    margin-right: 0.5rem;
}

.notification-settings {
    margin-left: 1.5rem;
    padding-left: 1rem;
    border-left: 3px solid var(--border-color);
}

.info-box {
    background: var(--background);
    padding: 1.5rem;
    border-radius: 0.5rem;
}

.info-list {
    list-style: none;
    padding: 0;
}

.info-list li {
    padding: 0.5rem 0;
    padding-left: 1.5rem;
    position: relative;
}

.info-list li::before {
    content: "•";
    position: absolute;
    left: 0;
    color: var(--primary-color);
}

.notification-history {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.history-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: var(--background);
    border-radius: 0.5rem;
    border: 1px solid var(--border-color);
}

.history-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.history-date {
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.history-status {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.25rem;
}

.badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.75rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 500;
}

.badge-success {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success-color);
}

.badge-pending {
    background: rgba(245, 158, 11, 0.1);
    color: var(--warning-color);
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

.empty-state {
    text-align: center;
    padding: 2rem;
    color: var(--text-secondary);
}
</style>

<script>
// Toggle notification settings based on checkbox
document.querySelector('input[name="notification_enabled"]').addEventListener('change', function() {
    const settings = document.getElementById('notificationSettings');
    if (this.checked) {
        settings.style.display = 'block';
    } else {
        settings.style.display = 'none';
    }
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const checkbox = document.querySelector('input[name="notification_enabled"]');
    const settings = document.getElementById('notificationSettings');
    if (!checkbox.checked) {
        settings.style.display = 'none';
    }
});
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../templates/layout.php';
?>