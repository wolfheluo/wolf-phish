FROM php:8.1-fpm-alpine

# 安裝系統依賴
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    mysql-client

# 安裝 PHP 擴展
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        pdo_mysql \
        mysqli \
        zip \
        intl \
        mbstring \
        bcmath \
        opcache

# 安裝 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 設置工作目錄
WORKDIR /var/www/cretech-phish

# 複製 PHP 配置
COPY php.ini /usr/local/etc/php/conf.d/99-custom.ini

# 設置權限
RUN addgroup -g 1000 www && \
    adduser -u 1000 -G www -s /bin/sh -D www

# 創建必要目錄
RUN mkdir -p /var/www/cretech-phish/logs \
             /var/www/cretech-phish/cache \
             /var/www/cretech-phish/tmp \
             /var/www/cretech-phish/uploads && \
    chown -R www:www /var/www/cretech-phish

USER www

EXPOSE 9000

CMD ["php-fpm"]