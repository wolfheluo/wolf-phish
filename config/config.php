<?php

// 應用程序設定
define('APP_NAME', 'Cretech-PHISH');
define('APP_VERSION', '1.0.0');
define('APP_DEBUG', true);

// URL 設定
define('BASE_URL', 'http://localhost/wolf-phish');
define('ASSETS_URL', BASE_URL . '/public/assets');

// 文件路徑設定
define('ROOT_PATH', dirname(__DIR__));
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('TEMPLATES_PATH', ROOT_PATH . '/templates');

// 安全設定
define('SESSION_TIMEOUT', 3600); // 1 hour
define('CSRF_TOKEN_NAME', 'csrf_token');

// 郵件設定
define('MAIL_FROM_NAME', 'Cretech Security Team');
define('MAIL_FROM_EMAIL', 'security@cretech.com');
define('MAIL_SMTP_HOST', 'localhost');
define('MAIL_SMTP_PORT', 25);

// 追蹤設定
define('TRACK_PIXEL_URL', BASE_URL . '/track/pixel');
define('TRACK_URL_BASE', BASE_URL . '/track/url');
define('TRACK_ZIP_URL', BASE_URL . '/track/zip');
define('PHISH_SITE_URL', BASE_URL . '/phish');

// 文件上傳設定
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXTENSIONS', array('csv', 'html', 'zip'));

// 時區設定
date_default_timezone_set('Asia/Taipei');

// 錯誤報告
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}