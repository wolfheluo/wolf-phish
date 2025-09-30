# Cretech-PHISH Docker 映像檔
# 注意：此 Dockerfile 主要用於開發和測試環境
# 生產環境建議直接在 aaPanel 中部署，請參考 install.sh 中的 aaPanel 配置說明

FROM php:8.1-fpm

# 安裝基本系統依賴（不包含 nginx, supervisor, postfix）
# 這些服務在 aaPanel 環境中由面板管理
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    cron \
    && rm -rf /var/lib/apt/lists/*

# 安裝 PHP 擴展
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip

# 安裝 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 設置工作目錄
WORKDIR /var/www/html

# 複製應用程序文件
COPY . /var/www/html/

# 安裝 PHP 依賴
RUN composer install --no-dev --optimize-autoloader 2>/dev/null || echo "No composer.json found, skipping..."

# 設置權限
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/uploads \
    && chmod -R 775 /var/www/html/templates

# 注意：Nginx 和 Supervisor 配置已移除
# 在 aaPanel 環境中，這些服務由面板統一管理
# 請參考 install.sh 中的詳細配置說明

# 配置 PHP
RUN echo "upload_max_filesize = 20M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 25M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini

# 創建必要的目錄
RUN mkdir -p /var/log/supervisor \
    && mkdir -p /var/log/nginx \
    && mkdir -p /var/run/php

# 郵件服務配置已移除
# 在 aaPanel 環境中請根據需要配置 Postfix 或使用 SMTP 服務

# 創建簡化的啟動腳本
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# 僅暴露 PHP-FPM 端口（在 aaPanel 環境中 Nginx 由面板管理）
EXPOSE 9000

# 注意：此 Docker 映像主要用於應用程式運行
# Web 服務器和其他服務請在 aaPanel 中配置

# 啟動 PHP-FPM
CMD ["php-fpm"]