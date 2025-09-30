#!/bin/bash

# Cretech-PHISH 安裝腳本
# 適用於 aaPanel 環境的 Ubuntu/Debian 系統
# 需要預先安裝和配置 aaPanel LNMP 環境

set -e

echo "======================================"
echo "Cretech-PHISH 社交工程測試平台安裝程序"
echo "適用於 aaPanel 環境"
echo "======================================"

# 檢查系統
if [[ $EUID -ne 0 ]]; then
   echo "此腳本需要 root 權限運行"
   echo "請使用: sudo bash install.sh"
   exit 1
fi

echo "請先完成 aaPanel 環境配置，再執行此腳本"
echo "aaPanel 配置要求請參考腳本最後的說明"

# 檢查 Composer 是否已安裝
if ! command -v composer &> /dev/null; then
    echo "警告：未檢測到 Composer"
    echo "請在 aaPanel 中安裝 Composer 或手動安裝："
    echo "curl -sS https://getcomposer.org/installer | php"
    echo "mv composer.phar /usr/local/bin/composer"
    echo "chmod +x /usr/local/bin/composer"
    echo ""
    echo "如需繼續安裝，請先安裝 Composer 後重新執行此腳本"
    exit 1
else
    echo "Composer 已安裝，版本：$(composer --version)"
fi

# 創建項目目錄
PROJECT_DIR="/var/www/cretech-phish"
echo "正在創建項目目錄: $PROJECT_DIR"

if [ -d "$PROJECT_DIR" ]; then
    echo "目錄已存在，正在備份..."
    mv "$PROJECT_DIR" "${PROJECT_DIR}.backup.$(date +%Y%m%d_%H%M%S)"
fi

mkdir -p "$PROJECT_DIR"
cp -r ./* "$PROJECT_DIR/"

# 創建必要的目錄
echo "正在創建必要的目錄..."
mkdir -p "$PROJECT_DIR/uploads"
mkdir -p "$PROJECT_DIR/logs"
mkdir -p "$PROJECT_DIR/cache"
mkdir -p "$PROJECT_DIR/tmp"

# 設置目錄權限
echo "正在設置目錄權限..."
chown -R www-data:www-data "$PROJECT_DIR"
chmod -R 755 "$PROJECT_DIR"
chmod -R 775 "$PROJECT_DIR/uploads"
chmod -R 775 "$PROJECT_DIR/templates"
chmod -R 775 "$PROJECT_DIR/logs"
chmod -R 775 "$PROJECT_DIR/cache"
chmod -R 775 "$PROJECT_DIR/tmp"

# aaPanel MySQL 配置
echo "請在 aaPanel 中手動創建 MySQL 資料庫和用戶"
echo "資料庫名稱: cretech_phish"
echo "用戶名稱: phish_user"
echo "密碼: phish_password_2023"
echo "權限: 授予該用戶對 cretech_phish 資料庫的完整權限"

# 初始化資料庫
echo "正在初始化資料庫..."
cd "$PROJECT_DIR"
php database/init.php

# aaPanel Nginx 配置
echo "請在 aaPanel 中手動配置網站"
echo "網站根目錄: /var/www/cretech-phish/public"
echo "PHP 版本: 8.1"
echo "請參考腳本最後的 Nginx 配置範例進行設置"

# aaPanel PHP 配置
echo "請在 aaPanel 中手動調整 PHP 設置"
echo "需要修改的 PHP 參數："
echo "- cgi.fix_pathinfo = 0"
echo "- upload_max_filesize = 20M"
echo "- post_max_size = 25M"
echo "- max_execution_time = 300"
echo "- memory_limit = 256M"
echo ""
echo "請安裝 Postfix 郵件服務或在 aaPanel 中配置 SMTP 設置"

# aaPanel Supervisor 配置
echo "請在 aaPanel 中手動配置 Supervisor 守護程序"
echo "或使用系統級別的 Supervisor 配置"
echo "請參考腳本最後的 Supervisor 配置範例"

# 創建後台腳本目錄
mkdir -p "$PROJECT_DIR/scripts"

# 創建佇列工作程序
cat > "$PROJECT_DIR/scripts/queue-worker.php" << 'EOF'
<?php
/**
 * 佇列工作程序 - 處理郵件發送任務
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';

// 設置為守護程序模式
set_time_limit(0);
ini_set('memory_limit', '256M');

echo "佇列工作程序啟動...\n";

while (true) {
    try {
        // 檢查待發送的郵件
        $sql = "SELECT te.*, p.* FROM target_emails te 
                JOIN projects p ON te.project_id = p.id 
                WHERE te.send_status = 'pending' 
                AND p.status = 'active' 
                AND p.start_date <= CURDATE() 
                AND p.end_date >= CURDATE()
                AND TIME(NOW()) BETWEEN p.send_start_time AND p.send_end_time
                LIMIT 10";
        
        $emails = Database::fetchAll($sql);
        
        if (empty($emails)) {
            // 沒有待發送郵件，休息10秒
            sleep(10);
            continue;
        }
        
        foreach ($emails as $emailData) {
            try {
                // 標記為處理中
                Database::query(
                    "UPDATE target_emails SET send_status = 'sending' WHERE id = ?",
                    [$emailData['id']]
                );
                
                // 處理郵件發送邏輯
                $success = sendPhishingEmail($emailData);
                
                if ($success) {
                    Database::query(
                        "UPDATE target_emails SET send_status = 'sent', is_sent = 1, sent_at = NOW() WHERE id = ?",
                        [$emailData['id']]
                    );
                    echo "郵件發送成功: {$emailData['email']}\n";
                } else {
                    Database::query(
                        "UPDATE target_emails SET send_status = 'failed', error_message = 'Send failed' WHERE id = ?",
                        [$emailData['id']]
                    );
                    echo "郵件發送失敗: {$emailData['email']}\n";
                }
                
            } catch (Exception $e) {
                Database::query(
                    "UPDATE target_emails SET send_status = 'failed', error_message = ? WHERE id = ?",
                    [$e->getMessage(), $emailData['id']]
                );
                echo "處理郵件錯誤: {$e->getMessage()}\n";
            }
            
            // 防止發送過快
            sleep(2);
        }
        
    } catch (Exception $e) {
        echo "佇列工作程序錯誤: {$e->getMessage()}\n";
        sleep(30);
    }
}

function sendPhishingEmail($emailData) {
    // 這裡實現實際的郵件發送邏輯
    // 使用 mail() 函數或 PHPMailer
    
    $to = $emailData['email'];
    $subject = $emailData['subject'];
    $headers = "From: {$emailData['sender_name']} <{$emailData['sender_email']}>\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    // 處理郵件內容中的追蹤標記
    // 這裡需要整合 EmailTemplate 模型的處理邏輯
    
    return mail($to, $subject, $emailData['html_content'], $headers);
}
EOF

# 創建調度程序
cat > "$PROJECT_DIR/scripts/scheduler.php" << 'EOF'
<?php
/**
 * 調度程序 - 處理定時任務
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';

set_time_limit(0);

echo "調度程序啟動...\n";

while (true) {
    try {
        $now = date('H:i:s');
        
        // 每分鐘執行一次
        if (date('s') === '00') {
            
            // 檢查專案狀態更新
            updateProjectStatus();
            
            // 清理過期會話
            cleanExpiredSessions();
            
            // 每小時執行的任務
            if (date('i') === '00') {
                
                // 生成統計報告
                generateHourlyStats();
                
                // 清理舊日誌
                cleanOldLogs();
            }
            
            // 每日執行的任務
            if ($now === '00:00:00') {
                
                // 生成日報
                generateDailyReports();
                
                // 備份資料庫
                backupDatabase();
            }
        }
        
        sleep(1);
        
    } catch (Exception $e) {
        echo "調度程序錯誤: {$e->getMessage()}\n";
        sleep(60);
    }
}

function updateProjectStatus() {
    // 更新過期專案狀態
    Database::query(
        "UPDATE projects SET status = 'completed' 
         WHERE status = 'active' AND end_date < CURDATE()"
    );
}

function cleanExpiredSessions() {
    // 清理超過24小時的會話
    Database::query(
        "DELETE FROM sessions 
         WHERE last_activity < DATE_SUB(NOW(), INTERVAL 24 HOUR)"
    );
}

function generateHourlyStats() {
    echo "生成每小時統計...\n";
    // 實現統計邏輯
}

function cleanOldLogs() {
    // 清理超過30天的日誌
    Database::query(
        "DELETE FROM system_logs 
         WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );
}

function generateDailyReports() {
    echo "生成日報...\n";
    // 實現報告生成邏輯
}

function backupDatabase() {
    echo "備份資料庫...\n";
    $backupFile = "/var/backups/cretech-phish-" . date('Y-m-d') . ".sql";
    $command = "mysqldump cretech_phish > $backupFile";
    exec($command);
}
EOF

# 設置腳本權限
chmod +x "$PROJECT_DIR/scripts/"*.php
chown -R www-data:www-data "$PROJECT_DIR/scripts"

# aaPanel 服務管理
echo "請在 aaPanel 中檢查和啟動相關服務："
echo "- Nginx"
echo "- PHP-FPM 8.1"
echo "- MySQL"
echo "- Supervisor (如有安裝)"

# aaPanel 防火牆配置
echo "請在 aaPanel 安全面板中配置防火牆規則："
echo "- 開放端口 22 (SSH)"
echo "- 開放端口 80 (HTTP)"
echo "- 開放端口 443 (HTTPS)"
echo "- 根據需要開放其他端口"

# 創建日誌輪換配置
cat > /etc/logrotate.d/cretech-phish << 'EOF'
/var/log/nginx/cretech-phish.*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 www-data adm
    postrotate
        if [ -f /var/run/nginx.pid ]; then
            kill -USR1 `cat /var/run/nginx.pid`
        fi
    endscript
}

/var/log/supervisor/phish-*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 root root
    postrotate
        supervisorctl restart phish-queue
        supervisorctl restart phish-scheduler
    endscript
}
EOF

echo "======================================"
echo "安裝完成！"
echo "======================================"
echo ""
echo "系統信息："
echo "- 網站地址: http://$(hostname -I | awk '{print $1}')"
echo "- 項目目錄: $PROJECT_DIR"
echo "- 配置文件: /etc/nginx/sites-available/cretech-phish"
echo "- 日誌目錄: /var/log/nginx/"
echo ""
echo "預設管理員帳戶："
echo "- 用戶名: admin"
echo "- 密碼: admin123"
echo ""
echo "重要提醒："
echo "1. 請立即更改預設管理員密碼"
echo "2. 建議配置 HTTPS 證書"
echo "3. 檢查防火牆設定"
echo "4. 定期備份資料庫"
echo ""
echo "請在 aaPanel 中檢查服務狀態："
echo "- Nginx"
echo "- PHP-FPM 8.1"
echo "- MySQL"
echo "- Postfix (如有安裝)"
echo "- Supervisor (如有安裝)"
echo ""
echo "如有問題，請檢查 aaPanel 中的相關日誌"
echo ""
echo "======================================"
echo "aaPanel 配置指南"
echo "======================================"
echo ""
echo "1. MySQL 配置："
echo "   - 登入 aaPanel > 資料庫"
echo "   - 創建資料庫：cretech_phish"
echo "   - 字符集：utf8mb4"
echo "   - 創建用戶：phish_user"
echo "   - 密碼：phish_password_2023"
echo "   - 權限：選擇 cretech_phish 資料庫的完整權限"
echo ""
echo "2. 網站配置："
echo "   - 登入 aaPanel > 網站"
echo "   - 添加站點，域名設置為您的域名或 IP"
echo "   - 網站目錄：/var/www/cretech-phish/public"
echo "   - PHP 版本：8.1"
echo "   - 在站點設置中添加以下 Nginx 規則："
echo ""
echo "   location / {"
echo "       try_files \$uri \$uri/ /index.php?\$query_string;"
echo "   }"
echo ""
echo "   location /uploads/ {"
echo "       alias /var/www/cretech-phish/uploads/;"
echo "       autoindex off;"
echo "   }"
echo ""
echo "   location /api/ {"
echo "       try_files \$uri \$uri/ /index.php?\$query_string;"
echo "   }"
echo ""
echo "   location /track/ {"
echo "       try_files \$uri \$uri/ /index.php?\$query_string;"
echo "   }"
echo ""
echo "   location /phish/ {"
echo "       try_files \$uri \$uri/ /index.php?\$query_string;"
echo "   }"
echo ""
echo "   # 安全頭設置"
echo "   add_header X-Frame-Options \"SAMEORIGIN\" always;"
echo "   add_header X-XSS-Protection \"1; mode=block\" always;"
echo "   add_header X-Content-Type-Options \"nosniff\" always;"
echo "   add_header Referrer-Policy \"no-referrer-when-downgrade\" always;"
echo "   add_header Content-Security-Policy \"default-src 'self' http: https: data: blob: 'unsafe-inline'\" always;"
echo ""
echo "3. PHP 設置："
echo "   - 登入 aaPanel > 軟體商店 > PHP 8.1 > 設置"
echo "   - 調整以下參數："
echo "     * cgi.fix_pathinfo = 0"
echo "     * upload_max_filesize = 20M"
echo "     * post_max_size = 25M"
echo "     * max_execution_time = 300"
echo "     * memory_limit = 256M"
echo "   - 安裝必要的擴展："
echo "     * mysqli, pdo_mysql, mbstring, xml, curl, zip, gd"
echo ""
echo "4. Supervisor 配置 (可選，用於後台任務)："
echo "   - 如果 aaPanel 有 Supervisor 管理器，添加以下配置："
echo ""
echo "   [program:phish-queue]"
echo "   command=php /var/www/cretech-phish/scripts/queue-worker.php"
echo "   directory=/var/www/cretech-phish"
echo "   autostart=true"
echo "   autorestart=true"
echo "   user=www-data"
echo "   numprocs=2"
echo "   redirect_stderr=true"
echo "   stdout_logfile=/var/log/supervisor/phish-queue.log"
echo "   stdout_logfile_maxbytes=10MB"
echo "   stdout_logfile_backups=5"
echo ""
echo "   [program:phish-scheduler]"
echo "   command=php /var/www/cretech-phish/scripts/scheduler.php"
echo "   directory=/var/www/cretech-phish"
echo "   autostart=true"
echo "   autorestart=true"
echo "   user=www-data"
echo "   redirect_stderr=true"
echo "   stdout_logfile=/var/log/supervisor/phish-scheduler.log"
echo "   stdout_logfile_maxbytes=10MB"
echo "   stdout_logfile_backups=5"
echo ""
echo "5. 防火牆配置："
echo "   - 登入 aaPanel > 安全"
echo "   - 開放端口："
echo "     * 22 (SSH)"
echo "     * 80 (HTTP)"
echo "     * 443 (HTTPS)"
echo "     * 根據需要開放其他端口"
echo ""
echo "6. SSL 證書配置 (建議)："
echo "   - 登入 aaPanel > 網站 > 您的站點 > SSL"
echo "   - 申請 Let's Encrypt 免費證書或上傳自己的證書"
echo ""
echo "7. 郵件服務配置："
echo "   - 選項 1：在 aaPanel 中安裝和配置 Postfix"
echo "   - 選項 2：使用外部 SMTP 服務（如 Gmail, SendGrid 等）"
echo "   - 修改 /var/www/cretech-phish/config/config.php 中的郵件設置"
echo ""
echo "注意事項："
echo "- 執行此腳本前請確保 aaPanel 已正確安裝並運行"
echo "- 建議先在 aaPanel 中安裝 LNMP 環境"
echo "- 資料庫初始化需要在 MySQL 配置完成後執行"
echo "- 定期備份資料庫和網站文件"
echo "======================================"