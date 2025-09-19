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
    
    // 分割SQL語句（基於分號分隔）
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    echo "發現 " . count($statements) . " 個SQL語句\n";
    
    // 執行每個SQL語句
    foreach ($statements as $index => $statement) {
        try {
            if (trim($statement)) {
                Database::query($statement . ';');
                echo "✓ 語句 " . ($index + 1) . " 執行成功\n";
            }
        } catch (Exception $e) {
            echo "✗ 語句 " . ($index + 1) . " 執行失敗: " . $e->getMessage() . "\n";
            // 繼續執行其他語句，某些表可能已存在
        }
    }
    
    // 檢查是否需要插入示例數據
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
    }
    
    // 驗證數據庫結構
    echo "驗證數據庫結構...\n";
    
    $tables = [
        'users', 'projects', 'email_templates', 'sent_emails',
        'track_pixel_logs', 'track_url_logs', 'track_zip_logs',
        'track_data_logs', 'track_credentials', 'track_page_visits',
        'system_logs'
    ];
    
    $missingTables = [];
    
    foreach ($tables as $table) {
        $exists = Database::fetchValue("SHOW TABLES LIKE '$table'");
        if ($exists) {
            echo "✓ 表 '$table' 存在\n";
        } else {
            echo "✗ 表 '$table' 不存在\n";
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
        echo "\n⚠️  以下表創建失敗，請檢查：\n";
        foreach ($missingTables as $table) {
            echo "  - $table\n";
        }
        echo "\n請檢查數據庫權限和 schema.sql 文件\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ 數據庫初始化失敗：" . $e->getMessage() . "\n";
    echo "\n請檢查：\n";
    echo "1. 數據庫連接設置是否正確\n";
    echo "2. 數據庫用戶是否有足夠權限\n";
    echo "3. schema.sql 文件是否存在\n\n";
    
    exit(1);
}