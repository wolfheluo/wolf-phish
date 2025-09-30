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
    
    /**
     * 清理輸入數據
     */
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * 生成隨機字符串
     */
    public static function generateRandomString($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * 檢查是否為AJAX請求
     */
    public static function isAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * 哈希密碼
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID);
    }
    
    /**
     * 驗證密碼
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * 生成安全的文件名
     */
    public static function sanitizeFilename($filename) {
        return preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    }
    
    /**
     * 記錄日誌
     */
    public static function log($level, $message, $userId = null, $data = null) {
        try {
            $db = Database::getConnection();
            
            $stmt = $db->prepare("
                INSERT INTO system_logs (level, message, user_id, data, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            // 確保message是字符串
            $messageStr = is_array($message) || is_object($message) ? json_encode($message, JSON_UNESCAPED_UNICODE) : (string)$message;
            
            // 處理data參數
            $dataJson = null;
            if ($data !== null) {
                if (is_array($data) || is_object($data)) {
                    $dataJson = json_encode($data, JSON_UNESCAPED_UNICODE);
                } else {
                    $dataJson = (string)$data;
                }
            }
            $stmt->execute([$level, $messageStr, $userId, $dataJson]);
        } catch (Exception $e) {
            // 記錄到文件作為備用
            $logMessage = is_array($message) ? json_encode($message) : $message;
            error_log("[" . date('Y-m-d H:i:s') . "] [$level] $logMessage");
        }
    }
    
    /**
     * 格式化文件大小
     */
    public static function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
