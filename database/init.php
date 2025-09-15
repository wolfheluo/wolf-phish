<?php

/**
 * 數據庫初始化腳本
 * 用於自動創建數據庫表結構和初始數據
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/Database.php';

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
    
    // 插入示例郵件模板
    $templateCount = Database::fetchValue("SELECT COUNT(*) FROM email_templates");
    
    if ($templateCount == 0) {
        echo "插入示例郵件模板...\n";
        
        $templates = [
            [
                'name' => 'Office 365 登錄通知',
                'subject' => '您的 Office 365 帳戶需要驗證',
                'from_name' => 'Microsoft Security Team',
                'from_email' => 'noreply@microsoft-security.com',
                'body' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>帳戶驗證</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #0078d4;">Microsoft Office 365</h2>
        <p>親愛的 {name}，</p>
        <p>我們注意到您的 Office 365 帳戶出現了異常的登錄活動。為了保護您的帳戶安全，請立即點擊下方連結進行身份驗證：</p>
        <p style="text-align: center; margin: 30px 0;">
            <a href="{tracking_url}" style="background-color: #0078d4; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">立即驗證帳戶</a>
        </p>
        <p><strong>注意：</strong>此連結將在24小時後過期。如果您沒有進行此操作，請立即聯繫IT支援部門。</p>
        <p>感謝您的配合。</p>
        <br>
        <p>Microsoft 安全團隊</p>
        <img src="{tracking_pixel}" width="1" height="1" style="display:none;">
    </div>
</body>
</html>',
                'template_type' => 'phishing'
            ],
            [
                'name' => '緊急安全更新通知',
                'subject' => '⚠️ 緊急：系統安全更新 - 立即處理',
                'from_name' => 'IT Security Department',
                'from_email' => 'security@company.internal',
                'body' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>緊急安全更新</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 2px solid #dc3545;">
        <h2 style="color: #dc3545;">⚠️ 緊急安全通知</h2>
        <p>員工編號：{name}</p>
        <p style="background-color: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24;">
            <strong>緊急通知：</strong>系統檢測到嚴重安全漏洞，需要立即安裝安全補丁。
        </p>
        <p>請立即下載並安裝以下安全更新：</p>
        <p style="text-align: center; margin: 30px 0;">
            <a href="{attachment_url}" style="background-color: #dc3545; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">下載安全補丁</a>
        </p>
        <p><strong>⏰ 截止時間：</strong>請在今日下午5點前完成安裝，否則系統訪問權限將被暫時停用。</p>
        <p>如有疑問，請聯繫 IT 支援部門（分機：1234）。</p>
        <br>
        <p>IT 安全部門<br>
        內部信息技術處</p>
        <img src="{tracking_pixel}" width="1" height="1" style="display:none;">
    </div>
</body>
</html>',
                'template_type' => 'phishing'
            ],
            [
                'name' => '人事問卷調查',
                'subject' => '📋 重要：年度員工滿意度調查 - 2分鐘完成',
                'from_name' => 'Human Resources',
                'from_email' => 'hr@company.com',
                'body' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>員工滿意度調查</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #28a745;">📋 年度員工滿意度調查</h2>
        <p>親愛的 {name}，</p>
        <p>又到了一年一度的員工滿意度調查時間！您的意見對我們非常重要，將幫助改善工作環境和福利政策。</p>
        <p><strong>調查內容包括：</strong></p>
        <ul>
            <li>工作環境滿意度</li>
            <li>管理制度評價</li>
            <li>薪資福利建議</li>
            <li>職業發展規劃</li>
        </ul>
        <p style="text-align: center; margin: 30px 0;">
            <a href="{tracking_url}" style="background-color: #28a745; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">開始填寫問卷 (2分鐘)</a>
        </p>
        <p><strong>參與獎勵：</strong>完成問卷的同事將獲得價值500元的禮品券！</p>
        <p><strong>截止日期：</strong>' . date('Y-m-d', strtotime('+7 days')) . '</p>
        <p>感謝您的參與和支持！</p>
        <br>
        <p>人力資源部<br>
        ' . date('Y-m-d') . '</p>
        <img src="{tracking_pixel}" width="1" height="1" style="display:none;">
    </div>
</body>
</html>',
                'template_type' => 'survey'
            ]
        ];
        
        foreach ($templates as $template) {
            Database::query(
                "INSERT INTO email_templates (name, subject, from_name, from_email, body, template_type, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())",
                [
                    $template['name'],
                    $template['subject'],
                    $template['from_name'],
                    $template['from_email'],
                    $template['body'],
                    $template['template_type']
                ]
            );
        }
        
        echo "✓ 示例郵件模板已插入\n\n";
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