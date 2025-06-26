<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/utils/csrf.php';

use App\Auth;
use App\Utils\CSRF;

$auth = new Auth();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
        $error = '不正なリクエストです';
    } else {
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        
        if (!$email) {
            $error = '有効なメールアドレスを入力してください';
        } elseif (strlen($password) < 8) {
            $error = 'パスワードは8文字以上で入力してください';
        } elseif ($password !== $password_confirm) {
            $error = 'パスワードが一致しません';
        } else {
            $userId = $auth->register($email, $password, $name);
            if ($userId) {
                $auth->login($email, $password);
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'このメールアドレスは既に登録されています';
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
    <title>新規登録 - サブスクリプション管理</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-form">
            <h1>新規登録</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <?= CSRF::getTokenField() ?>
                
                <div class="form-group">
                    <label for="name">お名前</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">メールアドレス <span class="required">*</span></label>
                    <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">パスワード <span class="required">*</span></label>
                    <input type="password" id="password" name="password" required minlength="8">
                    <small>8文字以上で入力してください</small>
                </div>
                
                <div class="form-group">
                    <label for="password_confirm">パスワード（確認） <span class="required">*</span></label>
                    <input type="password" id="password_confirm" name="password_confirm" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">登録する</button>
            </form>
            
            <p class="auth-link">
                既にアカウントをお持ちの方は<a href="login.php">ログイン</a>
            </p>
        </div>
    </div>
</body>
</html>