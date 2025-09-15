#!/bin/bash

# Docker 容器啟動腳本

set -e

echo "正在啟動 Cretech-PHISH 容器..."

# 等待資料庫就緒
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

# 確保目錄權限正確
echo "設置目錄權限..."
chown -R www-data:www-data /var/www/html/uploads
chown -R www-data:www-data /var/www/html/templates
chmod -R 775 /var/www/html/uploads
chmod -R 775 /var/www/html/templates

# 創建必要的目錄
mkdir -p /var/log/supervisor
mkdir -p /var/log/nginx
mkdir -p /var/run/php

# 配置 Postfix
echo "配置 Postfix..."
postconf -e "myhostname = $(hostname)"
postconf -e "mydomain = $(hostname -d)"
postconf -e "myorigin = \$mydomain"
postconf -e "inet_interfaces = loopback-only"
postconf -e "mydestination = \$myhostname, localhost.\$mydomain, localhost"

# 啟動 Postfix
echo "啟動 Postfix..."
service postfix start

# 啟動 Supervisor
echo "啟動 Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf