<?php

require_once dirname(__DIR__) . '/../config/config.php';
require_once dirname(__DIR__) . '/../config/database.php';
require_once 'Session.php';

class Utils {
    /**
     * 生成CSRF令牌
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }
    
    /**
     * 驗證CSRF令牌
     */
    public static function validateCSRFToken($token) {
        return isset($_SESSION[CSRF_TOKEN_NAME]) && 
               hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }
    
    /**
     * JSON響應
     */
    public static function jsonResponse($data, $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * 錯誤響應
     */
    public static function errorResponse($message, $code = 400) {
        self::jsonResponse(['error' => true, 'message' => $message], $code);
    }
    
    /**
     * 成功響應
     */
    public static function successResponse($data = [], $message = 'Success') {
        self::jsonResponse([
            'error' => false,
            'message' => $message,
            'data' => $data
        ]);
    }
    
    /**
     * 獲取客戶端IP
     */
    public static function getClientIP() {
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    /**
     * 獲取用戶代理
     */
    public static function getUserAgent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }
    
    /**
     * 驗證郵件地址
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
