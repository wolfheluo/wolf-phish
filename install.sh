#!/bin/bash

# Cretech-PHISH 安裝腳本
# 適用於 Ubuntu/Debian 系統

set -e

echo "======================================"
echo "Cretech-PHISH 社交工程測試平台安裝程序"
echo "======================================"

# 檢查系統
if [[ $EUID -ne 0 ]]; then
   echo "此腳本需要 root 權限運行"
   echo "請使用: sudo bash install.sh"
   exit 1
fi

# 更新系統
echo "正在更新系統包..."
apt update && apt upgrade -y

# 安裝必要的系統包
echo "正在安裝系統依賴..."
apt install -y \
    nginx \
    php8.1 \
    php8.1-fpm \
    php8.1-mysql \
    php8.1-mbstring \
    php8.1-xml \
    php8.1-curl \
    php8.1-zip \
    php8.1-gd \
    mysql-server \
    postfix \
    supervisor \
    git \
    curl \
    wget \
    unzip

# 安裝 Composer
if ! command -v composer &> /dev/null; then
    echo "正在安裝 Composer..."
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
    chmod +x /usr/local/bin/composer
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

# 設置目錄權限
echo "正在設置目錄權限..."
chown -R www-data:www-data "$PROJECT_DIR"
chmod -R 755 "$PROJECT_DIR"
chmod -R 775 "$PROJECT_DIR/uploads"
chmod -R 775 "$PROJECT_DIR/templates"

# 配置 MySQL
echo "正在配置 MySQL..."
mysql -e "CREATE DATABASE IF NOT EXISTS cretech_phish CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
mysql -e "CREATE USER IF NOT EXISTS 'phish_user'@'localhost' IDENTIFIED BY 'phish_password_2023';"
mysql -e "GRANT ALL PRIVILEGES ON cretech_phish.* TO 'phish_user'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

# 初始化資料庫
echo "正在初始化資料庫..."
cd "$PROJECT_DIR"
php database/init.php

# 配置 Nginx
echo "正在配置 Nginx..."
cat > /etc/nginx/sites-available/cretech-phish << 'EOF'
server {
    listen 80;
    server_name localhost;
    root /var/www/cretech-phish/public;
    index index.php index.html;

    # 安全頭
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # 主要位置配置
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP 處理
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # 靜態文件緩存
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    # 隱藏敏感文件
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

    location ~ /\.git {
        deny all;
        access_log off;
        log_not_found off;
    }

    # 上傳文件訪問
    location /uploads/ {
        alias /var/www/cretech-phish/uploads/;
        autoindex off;
    }

    # API路由
    location /api/ {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # 追蹤端點
    location /track/ {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # 釣魚頁面
    location /phish/ {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # 日志配置
    access_log /var/log/nginx/cretech-phish.access.log;
    error_log /var/log/nginx/cretech-phish.error.log;
}
EOF

# 啟用站點
ln -sf /etc/nginx/sites-available/cretech-phish /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# 測試 Nginx 配置
nginx -t

# 配置 PHP-FPM
echo "正在配置 PHP-FPM..."
sed -i 's/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/' /etc/php/8.1/fpm/php.ini
sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 20M/' /etc/php/8.1/fpm/php.ini
sed -i 's/post_max_size = 8M/post_max_size = 25M/' /etc/php/8.1/fpm/php.ini
sed -i 's/max_execution_time = 30/max_execution_time = 300/' /etc/php/8.1/fpm/php.ini
sed -i 's/memory_limit = 128M/memory_limit = 256M/' /etc/php/8.1/fpm/php.ini

# 配置 Postfix (簡單配置)
echo "正在配置 Postfix..."
debconf-set-selections <<< "postfix postfix/mailname string $(hostname -f)"
debconf-set-selections <<< "postfix postfix/main_mailer_type string 'Internet Site'"

# 配置 Supervisor (用於後台任務)
echo "正在配置 Supervisor..."
cat > /etc/supervisor/conf.d/cretech-phish.conf << 'EOF'
[program:phish-queue]
command=php /var/www/cretech-phish/scripts/queue-worker.php
directory=/var/www/cretech-phish
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/supervisor/phish-queue.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5

[program:phish-scheduler]
command=php /var/www/cretech-phish/scripts/scheduler.php
directory=/var/www/cretech-phish
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/supervisor/phish-scheduler.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
EOF

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

# 啟動服務
echo "正在啟動服務..."
systemctl restart nginx
systemctl restart php8.1-fpm
systemctl restart mysql
systemctl restart postfix
systemctl restart supervisor

# 設置服務開機啟動
systemctl enable nginx
systemctl enable php8.1-fpm
systemctl enable mysql
systemctl enable postfix
systemctl enable supervisor

# 更新 Supervisor 配置
supervisorctl reread
supervisorctl update

# 創建防火牆規則
echo "正在配置防火牆..."
if command -v ufw &> /dev/null; then
    ufw allow 22    # SSH
    ufw allow 80    # HTTP
    ufw allow 443   # HTTPS
    ufw --force enable
fi

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
echo "服務狀態檢查："
echo "- Nginx: $(systemctl is-active nginx)"
echo "- PHP-FPM: $(systemctl is-active php8.1-fpm)"
echo "- MySQL: $(systemctl is-active mysql)"
echo "- Postfix: $(systemctl is-active postfix)"
echo "- Supervisor: $(systemctl is-active supervisor)"
echo ""
echo "如有問題，請檢查日誌："
echo "- nginx -t  # 檢查配置"
echo "- systemctl status nginx"
echo "- tail -f /var/log/nginx/cretech-phish.error.log"
echo "======================================"