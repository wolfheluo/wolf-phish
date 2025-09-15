<?php

require_once 'BaseModel.php';

class User extends BaseModel {
    protected $table = 'users';
    protected $fillable = ['username', 'password', 'email', 'role', 'full_name', 'department', 'is_active'];
    protected $hidden = ['password'];
    
    /**
     * 根據用戶名查找用戶
     */
    public function findByUsername($username) {
        $sql = "SELECT * FROM users WHERE username = ? AND is_active = 1";
        return Database::fetch($sql, [$username]);
    }
    
    /**
     * 根據郵件查找用戶
     */
    public function findByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = ? AND is_active = 1";
        return Database::fetch($sql, [$email]);
    }
    
    /**
     * 創建用戶（自動哈希密碼）
     */
    public function createUser($data) {
        if (isset($data['password'])) {
            $data['password'] = Utils::hashPassword($data['password']);
        }
        return $this->create($data);
    }
    
    /**
     * 更新用戶密碼
     */
    public function updatePassword($userId, $newPassword) {
        $hashedPassword = Utils::hashPassword($newPassword);
        return $this->update($userId, ['password' => $hashedPassword]);
    }
    
    /**
     * 驗證用戶登入
     */
    public function authenticate($username, $password) {
        $user = $this->findByUsername($username);
        
        if (!$user) {
            return false;
        }
        
        if (!Utils::verifyPassword($password, $user['password'])) {
            return false;
        }
        
        // 更新最後登入時間
        $this->update($user['id'], ['updated_at' => date('Y-m-d H:i:s')]);
        
        return $user;
    }
    
    /**
     * 檢查用戶名是否存在
     */
    public function usernameExists($username, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM users WHERE username = ?";
        $params = [$username];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = Database::fetch($sql, $params);
        return $result['count'] > 0;
    }
    
    /**
     * 檢查郵件是否存在
     */
    public function emailExists($email, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM users WHERE email = ?";
        $params = [$email];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = Database::fetch($sql, $params);
        return $result['count'] > 0;
    }
    
    /**
     * 獲取所有管理員
     */
    public function getAdmins() {
        return $this->all(['role' => 'admin', 'is_active' => 1]);
    }
    
    /**
     * 獲取所有普通用戶
     */
    public function getUsers() {
        return $this->all(['role' => 'user', 'is_active' => 1]);
    }
    
    /**
     * 停用用戶
     */
    public function deactivate($userId) {
        return $this->update($userId, ['is_active' => 0]);
    }
    
    /**
     * 啟用用戶
     */
    public function activate($userId) {
        return $this->update($userId, ['is_active' => 1]);
    }
    
    /**
     * 獲取用戶統計
     */
    public function getStats() {
        $stats = [];
        
        // 總用戶數
        $stats['total'] = $this->count(['is_active' => 1]);
        
        // 管理員數量
        $stats['admins'] = $this->count(['role' => 'admin', 'is_active' => 1]);
        
        // 普通用戶數量
        $stats['users'] = $this->count(['role' => 'user', 'is_active' => 1]);
        
        // 最近註冊用戶（最近30天）
        $sql = "SELECT COUNT(*) as count FROM users WHERE is_active = 1 AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $result = Database::fetch($sql);
        $stats['recent'] = $result['count'];
        
        return $stats;
    }
}