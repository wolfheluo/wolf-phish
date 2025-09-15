<?php

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
     * 安全的密碼哈希
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * 驗證密碼
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * 生成隨機字符串
     */
    public static function generateRandomString($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * 清理輸入數據
     */
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * 驗證郵件格式
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * 獲取客戶端IP地址
     */
    public static function getClientIP() {
        $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * 獲取用戶代理
     */
    public static function getUserAgent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
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
     * 記錄系統日誌
     */
    public static function log($level, $message, $context = []) {
        try {
            $stmt = Database::getConnection()->prepare(
                "INSERT INTO system_logs (level, message, context, user_id, ip_address) VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                strtoupper($level),
                $message,
                json_encode($context),
                $_SESSION['user_id'] ?? null,
                self::getClientIP()
            ]);
        } catch (Exception $e) {
            // 記錄失敗，不影響主要程序
            error_log("Failed to log message: " . $e->getMessage());
        }
    }
    
    /**
     * 上傳文件處理
     */
    public static function handleFileUpload($file, $allowedTypes = null, $maxSize = null) {
        if (!$allowedTypes) {
            $allowedTypes = ALLOWED_EXTENSIONS;
        }
        if (!$maxSize) {
            $maxSize = MAX_UPLOAD_SIZE;
        }
        
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('文件上傳失敗');
        }
        
        if ($file['size'] > $maxSize) {
            throw new Exception('文件大小超出限制');
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedTypes)) {
            throw new Exception('不支持的文件類型');
        }
        
        $filename = self::generateRandomString() . '.' . $extension;
        $filepath = UPLOADS_PATH . '/' . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('文件保存失敗');
        }
        
        return $filename;
    }
    
    /**
     * 解析CSV文件
     */
    public static function parseCSV($filepath) {
        $emails = [];
        if (($handle = fopen($filepath, "r")) !== FALSE) {
            $headers = fgetcsv($handle, 1000, ",");
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (count($data) >= 1 && self::validateEmail($data[0])) {
                    $emails[] = [
                        'email' => $data[0],
                        'name' => $data[1] ?? '',
                        'department' => $data[2] ?? ''
                    ];
                }
            }
            fclose($handle);
        }
        return $emails;
    }
}