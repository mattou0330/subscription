-- データベース修正用SQL
-- payment_methodsテーブルとusersテーブルのnotification関連カラムを追加

USE subscription_manager;

-- payment_methodsテーブルが存在しない場合の作成
CREATE TABLE IF NOT EXISTS payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    type ENUM('credit_card', 'debit_card', 'paypal', 'bank_transfer', 'other') DEFAULT 'credit_card',
    last_four VARCHAR(4),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- usersテーブルにnotification関連カラムを追加（存在しない場合のみ）
-- MySQLのバージョンによってはIF NOT EXISTSが使えないため、エラーハンドリングを使用
DELIMITER $$

DROP PROCEDURE IF EXISTS AddColumnIfNotExists$$
CREATE PROCEDURE AddColumnIfNotExists()
BEGIN
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION BEGIN END;
    
    -- notification_daysカラムの追加
    ALTER TABLE users ADD COLUMN notification_days INT DEFAULT 3 AFTER updated_at;
    
    -- notification_emailカラムの追加
    ALTER TABLE users ADD COLUMN notification_email VARCHAR(255) DEFAULT NULL AFTER notification_days;
    
    -- notification_enabledカラムの追加
    ALTER TABLE users ADD COLUMN notification_enabled BOOLEAN DEFAULT TRUE AFTER notification_email;
END$$

DELIMITER ;

-- プロシージャの実行
CALL AddColumnIfNotExists();

-- プロシージャの削除
DROP PROCEDURE IF EXISTS AddColumnIfNotExists;

-- subscriptionsテーブルにlogo_urlカラムが存在しない場合の追加
DELIMITER $$

DROP PROCEDURE IF EXISTS AddLogoUrlColumn$$
CREATE PROCEDURE AddLogoUrlColumn()
BEGIN
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION BEGIN END;
    
    -- logo_urlカラムの追加（schema_update2.sqlとschema_update4.sqlで重複している可能性があるため）
    ALTER TABLE subscriptions ADD COLUMN logo_url VARCHAR(500) DEFAULT NULL AFTER service_name;
END$$

DELIMITER ;

-- プロシージャの実行
CALL AddLogoUrlColumn();

-- プロシージャの削除
DROP PROCEDURE IF EXISTS AddLogoUrlColumn;

-- テーブル構造の確認用クエリ
-- DESCRIBE users;
-- DESCRIBE payment_methods;
-- DESCRIBE subscriptions;