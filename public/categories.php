<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/utils/csrf.php';

use App\Auth;
use App\Utils\CSRF;

$auth = new Auth();
$auth->requireLogin();

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
            case 'add_category':
                $name = trim($_POST['name'] ?? '');
                $color = $_POST['color'] ?? '#3498db';
                
                if (empty($name)) {
                    $error = 'カテゴリ名を入力してください';
                } else {
                    $stmt = $db->prepare("INSERT INTO categories (name, color) VALUES (:name, :color)");
                    if ($stmt->execute([':name' => $name, ':color' => $color])) {
                        $message = 'カテゴリを追加しました';
                    } else {
                        $error = 'カテゴリの追加に失敗しました';
                    }
                }
                break;
                
            case 'update_category':
                $id = (int)($_POST['id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $color = $_POST['color'] ?? '#3498db';
                
                if ($id && !empty($name)) {
                    $stmt = $db->prepare("UPDATE categories SET name = :name, color = :color WHERE id = :id");
                    if ($stmt->execute([':id' => $id, ':name' => $name, ':color' => $color])) {
                        $message = 'カテゴリを更新しました';
                    }
                }
                break;
                
            case 'delete_category':
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 9) { // Protect default categories (ID 1-9)
                    $stmt = $db->prepare("DELETE FROM categories WHERE id = :id");
                    if ($stmt->execute([':id' => $id])) {
                        $message = 'カテゴリを削除しました';
                    }
                } else {
                    $error = 'デフォルトカテゴリは削除できません';
                }
                break;
                
            case 'add_pattern':
                $pattern = trim($_POST['pattern'] ?? '');
                $category_id = (int)($_POST['category_id'] ?? 0);
                
                if (!empty($pattern) && $category_id) {
                    $stmt = $db->prepare("INSERT INTO service_categories (service_pattern, category_id) VALUES (:pattern, :category_id)");
                    if ($stmt->execute([':pattern' => $pattern, ':category_id' => $category_id])) {
                        $message = 'サービスパターンを追加しました';
                    }
                }
                break;
                
            case 'delete_pattern':
                $id = (int)($_POST['id'] ?? 0);
                if ($id) {
                    $stmt = $db->prepare("DELETE FROM service_categories WHERE id = :id");
                    $stmt->execute([':id' => $id]);
                    $message = 'サービスパターンを削除しました';
                }
                break;
        }
    }
}

// Fetch categories
$stmt = $db->query("SELECT * FROM categories ORDER BY id");
$categories = $stmt->fetchAll();

// Fetch service patterns
$stmt = $db->query("
    SELECT sc.*, c.name as category_name, c.color as category_color 
    FROM service_categories sc 
    JOIN categories c ON sc.category_id = c.id 
    ORDER BY c.name, sc.service_pattern
");
$patterns = $stmt->fetchAll();

$pageTitle = 'カテゴリ設定';
$currentPage = 'categories';

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

<div class="settings-grid">
    <div class="settings-card">
        <h3 class="settings-title">カテゴリ管理</h3>
        
        <form method="POST" class="add-form">
            <?= CSRF::getTokenField() ?>
            <input type="hidden" name="action" value="add_category">
            
            <div class="form-row">
                <input type="text" name="name" class="form-control" placeholder="新しいカテゴリ名" required>
                <input type="color" name="color" class="form-control color-input" value="#3498db">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    追加
                </button>
            </div>
        </form>
        
        <div class="category-list">
            <?php foreach ($categories as $category): ?>
                <div class="category-item">
                    <form method="POST" class="category-form">
                        <?= CSRF::getTokenField() ?>
                        <input type="hidden" name="action" value="update_category">
                        <input type="hidden" name="id" value="<?= $category['id'] ?>">
                        
                        <div class="category-info">
                            <input type="text" name="name" value="<?= htmlspecialchars($category['name']) ?>" 
                                   class="form-control inline-input" required>
                            <input type="color" name="color" value="<?= htmlspecialchars($category['color']) ?>" 
                                   class="form-control color-input">
                            <span class="category-preview" style="background-color: <?= htmlspecialchars($category['color']) ?>">
                                <?= htmlspecialchars($category['name']) ?>
                            </span>
                        </div>
                        
                        <div class="category-actions">
                            <button type="submit" class="btn btn-sm btn-secondary">
                                <i class="fas fa-save"></i>
                            </button>
                            <?php if ($category['id'] > 9): ?>
                                <button type="submit" name="action" value="delete_category" 
                                        class="btn btn-sm btn-danger"
                                        onclick="return confirm('このカテゴリを削除しますか？')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="settings-card">
        <h3 class="settings-title">サービス自動分類設定</h3>
        <p class="settings-description">
            サービス名のパターンを登録すると、新規追加時に自動でカテゴリが設定されます。
        </p>
        
        <form method="POST" class="add-form">
            <?= CSRF::getTokenField() ?>
            <input type="hidden" name="action" value="add_pattern">
            
            <div class="form-row">
                <input type="text" name="pattern" class="form-control" placeholder="サービス名パターン" required>
                <select name="category_id" class="form-control" required>
                    <option value="">カテゴリを選択</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    追加
                </button>
            </div>
        </form>
        
        <div class="pattern-list">
            <?php foreach ($patterns as $pattern): ?>
                <div class="pattern-item">
                    <div class="pattern-info">
                        <span class="pattern-name"><?= htmlspecialchars($pattern['service_pattern']) ?></span>
                        <i class="fas fa-arrow-right"></i>
                        <span class="category-badge" style="background-color: <?= htmlspecialchars($pattern['category_color']) ?>">
                            <?= htmlspecialchars($pattern['category_name']) ?>
                        </span>
                    </div>
                    
                    <form method="POST" class="pattern-delete">
                        <?= CSRF::getTokenField() ?>
                        <input type="hidden" name="action" value="delete_pattern">
                        <input type="hidden" name="id" value="<?= $pattern['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
    gap: 2rem;
}

.settings-card {
    background: var(--surface);
    padding: 2rem;
    border-radius: 1rem;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
}

.settings-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.settings-description {
    color: var(--text-secondary);
    font-size: 0.875rem;
    margin-bottom: 1.5rem;
}

.add-form {
    margin-bottom: 2rem;
}

.form-row {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

.color-input {
    width: 60px;
    height: 40px;
    padding: 0.25rem;
    cursor: pointer;
}

.category-list, .pattern-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.category-item, .pattern-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: var(--background);
    border-radius: 0.5rem;
    border: 1px solid var(--border-color);
}

.category-form {
    display: flex;
    width: 100%;
    justify-content: space-between;
    align-items: center;
}

.category-info {
    display: flex;
    gap: 0.75rem;
    align-items: center;
    flex: 1;
}

.inline-input {
    max-width: 200px;
}

.category-preview {
    padding: 0.375rem 0.75rem;
    border-radius: 999px;
    font-size: 0.875rem;
    color: white;
    font-weight: 500;
}

.category-actions, .pattern-delete {
    display: flex;
    gap: 0.5rem;
}

.pattern-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.pattern-name {
    font-weight: 500;
}

.category-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 999px;
    font-size: 0.75rem;
    color: white;
    font-weight: 500;
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

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../templates/layout.php';
?>