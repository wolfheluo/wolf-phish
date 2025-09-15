<?php

class Session {
    private static $started = false;
    
    /**
     * 開始會話
     */
    public static function start() {
        if (!self::$started) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            ini_set('session.use_strict_mode', 1);
            
            session_start();
            self::$started = true;
            
            // 檢查會話超時
            self::checkTimeout();
            
            // 更新最後活動時間
            $_SESSION['last_activity'] = time();
        }
    }
    
    /**
     * 檢查會話超時
     */
    private static function checkTimeout() {
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
            self::destroy();
            header('Location: /auth/login?timeout=1');
            exit;
        }
    }
    
    /**
     * 設置會話數據
     */
    public static function set($key, $value) {
        self::start();
        $_SESSION[$key] = $value;
    }
    
    /**
     * 獲取會話數據
     */
    public static function get($key, $default = null) {
        self::start();
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * 刪除會話數據
     */
    public static function remove($key) {
        self::start();
        unset($_SESSION[$key]);
    }
    
    /**
     * 檢查用戶是否已登入
     */
    public static function isLoggedIn() {
        return self::get('user_id') !== null;
    }
    
    /**
     * 獲取當前用戶ID
     */
    public static function getUserId() {
        return self::get('user_id');
    }
    
    /**
     * 獲取當前用戶角色
     */
    public static function getUserRole() {
        return self::get('user_role', 'user');
    }
    
    /**
     * 檢查是否為管理員
     */
    public static function isAdmin() {
        return self::getUserRole() === 'admin';
    }
    
    /**
     * 用戶登入
     */
    public static function login($user) {
        self::start();
        
        // 重新生成會話ID防止會話固定攻擊
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        // 更新資料庫中的會話信息
        try {
            $stmt = Database::getConnection()->prepare(
                "INSERT INTO sessions (id, user_id, ip_address, user_agent, data) 
                 VALUES (?, ?, ?, ?, ?) 
                 ON DUPLICATE KEY UPDATE 
                 user_id = VALUES(user_id), 
                 ip_address = VALUES(ip_address), 
                 user_agent = VALUES(user_agent), 
                 data = VALUES(data),
                 last_activity = CURRENT_TIMESTAMP"
            );
            
            $stmt->execute([
                session_id(),
                $user['id'],
                Utils::getClientIP(),
                Utils::getUserAgent(),
                json_encode($_SESSION)
            ]);
        } catch (Exception $e) {
            Utils::log('ERROR', 'Failed to save session data', ['error' => $e->getMessage()]);
        }
        
        Utils::log('INFO', 'User logged in', ['user_id' => $user['id'], 'username' => $user['username']]);
    }
    
    /**
     * 用戶登出
     */
    public static function logout() {
        self::start();
        
        $userId = self::getUserId();
        $username = self::get('username');
        
        // 從資料庫刪除會話記錄
        try {
            $stmt = Database::getConnection()->prepare("DELETE FROM sessions WHERE id = ?");
            $stmt->execute([session_id()]);
        } catch (Exception $e) {
            Utils::log('ERROR', 'Failed to delete session data', ['error' => $e->getMessage()]);
        }
        
        Utils::log('INFO', 'User logged out', ['user_id' => $userId, 'username' => $username]);
        
        // 清除所有會話數據
        $_SESSION = array();
        
        // 刪除會話cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
        self::$started = false;
    }
    
    /**
     * 銷毀會話
     */
    public static function destroy() {
        self::logout();
    }
    
    /**
     * 添加Flash消息
     */
    public static function addFlash($type, $message) {
        self::start();
        if (!isset($_SESSION['flash'])) {
            $_SESSION['flash'] = [];
        }
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
    }
    
    /**
     * 獲取並清除Flash消息
     */
    public static function getFlashes() {
        self::start();
        $flashes = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $flashes;
    }
    
    /**
     * 檢查權限
     */
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: /auth/login');
            exit;
        }
    }
    
    /**
     * 檢查管理員權限
     */
    public static function requireAdmin() {
        self::requireLogin();
        if (!self::isAdmin()) {
            Utils::errorResponse('Access denied: Admin privileges required', 403);
        }
    }
}