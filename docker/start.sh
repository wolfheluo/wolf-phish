#!/bin/bash

# Docker 容器啟動腳本 - 簡化版本（適用於開發環境）
# 注意：此腳本已簡化，生產環境請使用 aaPanel 進行部署

set -e

echo "正在啟動 Cretech-PHISH PHP-FPM 服務..."

# 等待資料庫就緒（如果使用 Docker Compose）
if [ "${APP_ENV:-}" = "development" ]; then
    echo "等待資料庫連接..."
    until php -r "
    try {
        \$pdo = new PDO('mysql:host=database;dbname=cretech_phish', 'phish_user', 'phish_secure_password_2023');
        echo 'Database connected successfully\n';
        exit(0);
    } catch (Exception \$e) {
        exit(1);
    }
    "; do
        echo "資料庫尚未就緒，等待中..."
        sleep 5
    done

    # 初始化資料庫（如果需要）
    if [ ! -f /var/www/html/.db-initialized ]; then
        echo "初始化資料庫..."
        php /var/www/html/database/init.php
        touch /var/www/html/.db-initialized
    fi
fi

# 確保目錄權限正確
echo "設置目錄權限..."
chown -R www-data:www-data /var/www/html/uploads 2>/dev/null || true
chown -R www-data:www-data /var/www/html/templates 2>/dev/null || true
chmod -R 775 /var/www/html/uploads 2>/dev/null || true
chmod -R 775 /var/www/html/templates 2>/dev/null || true

# 創建必要的目錄
mkdir -p /var/run/php

echo "啟動 PHP-FPM..."
echo "注意：在 aaPanel 環境中，請使用面板來管理 Nginx 和其他服務"

# 啟動 PHP-FPM
exec php-fpm -F