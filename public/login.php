<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/utils/csrf.php';

use App\Auth;
use App\Utils\CSRF;

$auth = new Auth();
$error = '';

if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
        $error = '不正なリクエストです';
    } else {
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        
        if (!$email || !$password) {
            $error = 'メールアドレスとパスワードを入力してください';
        } else {
            if ($auth->login($email, $password)) {
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'メールアドレスまたはパスワードが正しくありません';
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
    <title>ログイン - カンリー</title>
    <link rel="stylesheet" href="css/modern-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .auth-container {
            background: var(--surface);
            padding: 3rem;
            border-radius: 1.5rem;
            box-shadow: var(--shadow-xl);
            width: 100%;
            max-width: 420px;
        }
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .auth-logo {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        .auth-tagline {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        .auth-form {
            margin: 0;
        }
        .form-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
        }
        .form-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        .form-footer a:hover {
            text-decoration: underline;
        }
        .btn-block {
            width: 100%;
            margin-top: 1rem;
        }
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <h1 class="auth-logo">カンリー</h1>
            <p class="auth-tagline">サブスクリプション管理サービス</p>
        </div>
        
        <div class="auth-form">
            <h2 style="text-align: center; margin-bottom: 2rem;">ログイン</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <?= CSRF::getTokenField() ?>
                
                <div class="form-group">
                    <label for="email" class="form-label">メールアドレス</label>
                    <input type="email" id="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">パスワード</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">ログイン</button>
            </form>
            
            <div class="form-footer">
                <p>アカウントをお持ちでない方は<br><a href="register.php">新規登録</a></p>
            </div>
        </div>
    </div>
</body>
</html>