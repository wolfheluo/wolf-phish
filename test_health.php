<?php

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/controllers/DashboardController.php';

// 模擬admin用戶session
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';
$_SESSION['username'] = 'admin';

echo "測試系統健康狀態API...\n\n";

try {
    $controller = new DashboardController();
    
    // 手動測試各項健康檢查
    echo "1. 測試資料庫連接...\n";
    $db = Database::getConnection();
    $result = $db->query("SELECT 1");
    echo "   資料庫連接: OK\n\n";
    
    echo "2. 測試磁碟空間...\n";
    $freeSpace = disk_free_space(ROOT_PATH);
    $totalSpace = disk_total_space(ROOT_PATH);
    $usedPercent = (($totalSpace - $freeSpace) / $totalSpace) * 100;
    echo "   使用率: " . round($usedPercent, 2) . "%\n\n";
    
    echo "3. 測試上傳目錄...\n";
    $writable = is_writable(UPLOADS_PATH);
    echo "   上傳目錄可寫: " . ($writable ? 'YES' : 'NO') . "\n";
    echo "   路徑: " . UPLOADS_PATH . "\n\n";
    
    echo "4. 測試sendmail...\n";
    $sendmailPath = trim(shell_exec('which sendmail 2>/dev/null'));
    echo "   Sendmail路徑: " . ($sendmailPath ?: 'NOT FOUND') . "\n\n";
    
    echo "5. 測試系統日誌...\n";
    $sql = "SELECT COUNT(*) as count FROM system_logs WHERE level IN ('ERROR', 'WARNING') AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch();
    echo "   最近24小時錯誤/警告數: " . $result['count'] . "\n\n";
    
    echo "6. 測試完整健康檢查API...\n";
    ob_start();
    $controller->healthCheck();
    $output = ob_get_clean();
    echo "   API輸出: " . $output . "\n";
    
} catch (Exception $e) {
    echo "錯誤: " . $e->getMessage() . "\n";
    echo "追蹤: " . $e->getTraceAsString() . "\n";
}