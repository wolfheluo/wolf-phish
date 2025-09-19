<?php

/**
 * 數據庫初始化腳本
 * 用於自動創建數據庫表結構和初始數據
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';

try {
    echo "開始初始化數據庫...\n";
    
    // 讀取並執行 schema.sql
    $schemaFile = __DIR__ . '/schema.sql';
    
    if (!file_exists($schemaFile)) {
        throw new Exception("找不到數據庫結構文件: $schemaFile");
    }
    
    $sql = file_get_contents($schemaFile);
    
    if (!$sql) {
        throw new Exception("無法讀取數據庫結構文件");
    }
    
    // 更智能的SQL語句分割，處理多行語句和註釋
    $statements = [];
    $currentStatement = '';
    $lines = explode("\n", $sql);
    $inComment = false;
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // 跳過空行
        if (empty($line)) continue;
        
        // 跳過註釋行
        if (preg_match('/^--/', $line)) continue;
        
        // 處理多行註釋
        if (strpos($line, '/*') !== false) {
            $inComment = true;
        }
        if (strpos($line, '*/') !== false) {
            $inComment = false;
            continue;
        }
        if ($inComment) continue;
        
        // 累積語句
        $currentStatement .= ' ' . $line;
        
        // 如果行以分號結尾，表示語句結束
        if (substr($line, -1) === ';') {
            $statement = trim($currentStatement);
            if (!empty($statement) && !preg_match('/^(USE|CREATE DATABASE)/', $statement)) {
                $statements[] = $statement;
            } else if (preg_match('/^(USE|CREATE DATABASE)/', $statement)) {
                // 單獨處理 USE 和 CREATE DATABASE 語句
                $statements[] = $statement;
            }
            $currentStatement = '';
        }
    }
    
    echo "發現 " . count($statements) . " 個SQL語句\n";
    
    // 先創建資料庫和切換
    foreach ($statements as $index => $statement) {
        if (preg_match('/^(CREATE DATABASE|USE)/', trim($statement))) {
            try {
                Database::query($statement);
                echo "✓ 資料庫語句 " . ($index + 1) . " 執行成功: " . substr($statement, 0, 50) . "...\n";
            } catch (Exception $e) {
                echo "✗ 資料庫語句 " . ($index + 1) . " 執行失敗: " . $e->getMessage() . "\n";
                if (strpos($statement, 'CREATE DATABASE') !== false) {
                    echo "嘗試繼續執行...\n";
                }
            }
        }
    }
    
    // 然後執行表創建語句
    foreach ($statements as $index => $statement) {
        if (!preg_match('/^(CREATE DATABASE|USE)/', trim($statement))) {
            try {
                Database::query($statement);
                $shortStmt = substr(trim($statement), 0, 50);
                echo "✓ 語句 " . ($index + 1) . " 執行成功: " . $shortStmt . "...\n";
            } catch (Exception $e) {
                $shortStmt = substr(trim($statement), 0, 50);
                echo "✗ 語句 " . ($index + 1) . " 執行失敗: " . $e->getMessage() . "\n";
                echo "  語句內容: " . $shortStmt . "...\n";
                
                // 如果是表已存在的錯誤，繼續執行
                if (strpos($e->getMessage(), 'already exists') !== false || 
                    strpos($e->getMessage(), 'Table') !== false && strpos($e->getMessage(), 'already exists') !== false) {
                    echo "  表已存在，繼續執行下一個語句\n";
                    continue;
                }
                
                // 對於其他關鍵錯誤，記錄但繼續
                echo "  繼續執行其他語句...\n";
            }
        }
    }
    
    // 檢查是否需要插入示例數據
    try {
        $userCount = Database::fetchValue("SELECT COUNT(*) FROM users");
        
        if ($userCount == 0) {
            echo "插入默認管理員用戶...\n";
            
            // 創建默認管理員用戶
            $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
            
            Database::query(
                "INSERT INTO users (username, email, password_hash, role, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())",
                ['admin', 'admin@example.com', $adminPassword, 'admin', 'active']
            );
            
            echo "✓ 默認管理員用戶已創建\n";
            echo "  用戶名: admin\n";
            echo "  密碼: admin123\n";
            echo "  ⚠️  請立即更改默認密碼！\n\n";
        } else {
            echo "✓ 管理員用戶已存在，跳過插入\n";
        }
    } catch (Exception $e) {
        echo "⚠️  無法檢查用戶表，可能表還未創建: " . $e->getMessage() . "\n";
        
        // 嘗試直接創建用戶
        try {
            $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
            Database::query(
                "INSERT INTO users (username, email, password_hash, role, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())",
                ['admin', 'admin@example.com', $adminPassword, 'admin', 'active']
            );
            echo "✓ 默認管理員用戶已創建\n";
        } catch (Exception $e2) {
            echo "✗ 創建管理員用戶失敗: " . $e2->getMessage() . "\n";
        }
    }
    
    // 驗證數據庫結構
    echo "驗證數據庫結構...\n";
    
    $tables = [
        'users', 'projects', 'email_templates', 'phishing_sites',
        'target_emails', 'sent_emails', 'track_pixel_logs', 
        'track_url_logs', 'track_zip_logs', 'track_data_logs',
        'track_credentials', 'track_page_visits', 'system_logs',
        'sessions'
    ];
    
    $missingTables = [];
    
    foreach ($tables as $table) {
        try {
            $exists = Database::fetchValue("SHOW TABLES LIKE '$table'");
            if ($exists) {
                echo "✓ 表 '$table' 存在\n";
            } else {
                echo "✗ 表 '$table' 不存在\n";
                $missingTables[] = $table;
            }
        } catch (Exception $e) {
            echo "⚠️  檢查表 '$table' 時出錯: " . $e->getMessage() . "\n";
            $missingTables[] = $table;
        }
    }
    
    if (empty($missingTables)) {
        echo "\n🎉 數據庫初始化完成！\n";
        echo "\n系統已準備就緒，您可以開始使用 Cretech-PHISH 平台。\n";
        echo "\n重要提醒：\n";
        echo "1. 請立即更改默認管理員密碼\n";
        echo "2. 建議配置SMTP服務器以發送郵件\n";
        echo "3. 檢查文件權限設置\n";
        echo "4. 配置定期備份\n\n";
    } else {
        echo "\n⚠️  以下表創建失敗或不完整，請檢查：\n";
        foreach ($missingTables as $table) {
            echo "  - $table\n";
        }
        echo "\n建議解決方案：\n";
        echo "1. 檢查 MySQL 用戶權限: GRANT ALL PRIVILEGES ON cretech_phish.* TO 'phish_user'@'localhost';\n";
        echo "2. 手動執行 schema.sql: mysql -u phish_user -p cretech_phish < database/schema.sql\n";
        echo "3. 檢查 MySQL 錯誤日誌: sudo tail -f /var/log/mysql/error.log\n";
        
        // 嘗試手動創建缺失的重要表
        if (in_array('users', $missingTables)) {
            echo "\n嘗試手動創建 users 表...\n";
            try {
                $createUsersSQL = "CREATE TABLE users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(50) NOT NULL UNIQUE,
                    password_hash VARCHAR(255) NOT NULL,
                    email VARCHAR(100) NOT NULL UNIQUE,
                    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
                    full_name VARCHAR(100),
                    department VARCHAR(100),
                    status ENUM('active', 'inactive', 'blocked') DEFAULT 'active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )";
                Database::query($createUsersSQL);
                echo "✓ users 表創建成功\n";
                
                // 重新插入管理員用戶
                $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
                Database::query(
                    "INSERT INTO users (username, email, password_hash, role, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())",
                    ['admin', 'admin@example.com', $adminPassword, 'admin', 'active']
                );
                echo "✓ 默認管理員用戶已創建\n";
            } catch (Exception $e) {
                echo "✗ 手動創建 users 表失敗: " . $e->getMessage() . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "\n❌ 數據庫初始化失敗：" . $e->getMessage() . "\n";
    echo "\n請檢查：\n";
    echo "1. 數據庫連接設置是否正確\n";
    echo "2. 數據庫用戶是否有足夠權限\n";
    echo "3. schema.sql 文件是否存在\n\n";
    
    exit(1);
}