<?php
// データベースの構造を確認するスクリプト
require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h2>データベース構造確認</h2>";
    
    // usersテーブルの構造を確認
    echo "<h3>users テーブル</h3>";
    echo "<pre>";
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($columns);
    echo "</pre>";
    
    // payment_methodsテーブルの確認
    echo "<h3>payment_methods テーブル</h3>";
    try {
        $stmt = $db->query("DESCRIBE payment_methods");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($columns);
        echo "</pre>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>テーブルが存在しません: " . $e->getMessage() . "</p>";
    }
    
    // subscriptionsテーブルの構造を確認
    echo "<h3>subscriptions テーブル</h3>";
    echo "<pre>";
    $stmt = $db->query("DESCRIBE subscriptions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($columns);
    echo "</pre>";
    
    // notification関連カラムの確認
    echo "<h3>notification関連カラムの確認</h3>";
    $hasNotificationDays = false;
    $hasNotificationEmail = false;
    $hasNotificationEnabled = false;
    
    $stmt = $db->query("DESCRIBE users");
    $userColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($userColumns as $column) {
        if ($column['Field'] == 'notification_days') $hasNotificationDays = true;
        if ($column['Field'] == 'notification_email') $hasNotificationEmail = true;
        if ($column['Field'] == 'notification_enabled') $hasNotificationEnabled = true;
    }
    
    echo "<ul>";
    echo "<li>notification_days: " . ($hasNotificationDays ? "✓ 存在" : "✗ 存在しない") . "</li>";
    echo "<li>notification_email: " . ($hasNotificationEmail ? "✓ 存在" : "✗ 存在しない") . "</li>";
    echo "<li>notification_enabled: " . ($hasNotificationEnabled ? "✓ 存在" : "✗ 存在しない") . "</li>";
    echo "</ul>";
    
    // logo_urlカラムの確認
    echo "<h3>logo_urlカラムの確認</h3>";
    $hasLogoUrl = false;
    $stmt = $db->query("DESCRIBE subscriptions");
    $subColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($subColumns as $column) {
        if ($column['Field'] == 'logo_url') $hasLogoUrl = true;
    }
    echo "<p>subscriptions.logo_url: " . ($hasLogoUrl ? "✓ 存在" : "✗ 存在しない") . "</p>";
    
    // 必要な修正SQLの表示
    echo "<h2>必要な修正SQL</h2>";
    echo "<p>以下のSQLをデータベースで実行してください：</p>";
    echo "<pre style='background-color: #f0f0f0; padding: 10px; border: 1px solid #ccc;'>";
    
    if (!$hasNotificationDays || !$hasNotificationEmail || !$hasNotificationEnabled) {
        echo "-- usersテーブルにnotification関連カラムを追加\n";
        if (!$hasNotificationDays) {
            echo "ALTER TABLE users ADD COLUMN notification_days INT DEFAULT 3 AFTER updated_at;\n";
        }
        if (!$hasNotificationEmail) {
            echo "ALTER TABLE users ADD COLUMN notification_email VARCHAR(255) DEFAULT NULL AFTER notification_days;\n";
        }
        if (!$hasNotificationEnabled) {
            echo "ALTER TABLE users ADD COLUMN notification_enabled BOOLEAN DEFAULT TRUE AFTER notification_email;\n";
        }
        echo "\n";
    }
    
    if (!$hasLogoUrl) {
        echo "-- subscriptionsテーブルにlogo_urlカラムを追加\n";
        echo "ALTER TABLE subscriptions ADD COLUMN logo_url VARCHAR(500) DEFAULT NULL AFTER service_name;\n";
    }
    
    echo "</pre>";
    
    echo "<p><a href='/subscription/database/fix_database.sql'>修正用SQLファイルを確認</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>データベース接続エラー: " . $e->getMessage() . "</p>";
}
?>