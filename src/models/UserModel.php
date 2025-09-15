<?php

require_once 'BaseModel.php';

class User extends BaseModel {
    protected $table = 'users';
    protected $fillable = ['username', 'password', 'email', 'role', 'full_name', 'department', 'is_active'];
    protected $hidden = ['password'];
    
    /**
     * 用戶登入驗證
     */
    public function authenticate($username, $password) {
        $sql = "SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1";
        $user = Database::fetch($sql, [$username, $username]);
        
        if ($user && Utils::verifyPassword($password, $user['password'])) {
            return $this->hideFields([$user])[0];
        }
        
        return false;
    }
    
    /**
     * 創建用戶
     */
    public function createUser($data) {
        // 檢查用戶名是否已存在
        if ($this->usernameExists($data['username'])) {
            throw new Exception('用戶名已存在');
        }
        
        // 檢查郵件是否已存在
        if ($this->emailExists($data['email'])) {
            throw new Exception('郵件地址已存在');
        }
        
        // 密碼哈希處理
        if (isset($data['password'])) {
            $data['password'] = Utils::hashPassword($data['password']);
        }
        
        return $this->create($data);
    }
    
    /**
     * 更新用戶信息
     */
    public function updateUser($userId, $data) {
        // 如果更新密碼，需要哈希處理
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = Utils::hashPassword($data['password']);
        } else {
            // 如果密碼為空，不更新密碼字段
            unset($data['password']);
        }
        
        // 檢查用戶名唯一性
        if (isset($data['username'])) {
            if ($this->usernameExists($data['username'], $userId)) {
                throw new Exception('用戶名已存在');
            }
        }
        
        // 檢查郵件唯一性
        if (isset($data['email'])) {
            if ($this->emailExists($data['email'], $userId)) {
                throw new Exception('郵件地址已存在');
            }
        }
        
        return $this->update($userId, $data);
    }
    
    /**
     * 更改密碼
     */
    public function changePassword($userId, $oldPassword, $newPassword) {
        $user = Database::fetch("SELECT password FROM users WHERE id = ?", [$userId]);
        
        if (!$user || !Utils::verifyPassword($oldPassword, $user['password'])) {
            throw new Exception('原密碼錯誤');
        }
        
        $hashedPassword = Utils::hashPassword($newPassword);
        return $this->update($userId, ['password' => $hashedPassword]);
    }
    
    /**
     * 重置密碼
     */
    public function resetPassword($userId, $newPassword) {
        $hashedPassword = Utils::hashPassword($newPassword);
        return $this->update($userId, ['password' => $hashedPassword]);
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
     * 根據用戶名查找用戶
     */
    public function findByUsername($username) {
        return $this->findWhere(['username' => $username]);
    }
    
    /**
     * 根據郵件查找用戶
     */
    public function findByEmail($email) {
        return $this->findWhere(['email' => $email]);
    }
    
    /**
     * 獲取活躍用戶列表
     */
    public function getActiveUsers($role = null) {
        $sql = "SELECT * FROM users WHERE is_active = 1";
        $params = [];
        
        if ($role) {
            $sql .= " AND role = ?";
            $params[] = $role;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $users = Database::fetchAll($sql, $params);
        return $this->hideFields($users);
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
     * 獲取用戶統計信息
     */
    public function getUserStats($userId) {
        $stats = [];
        
        // 創建的專案數量
        $sql = "SELECT COUNT(*) as count FROM projects WHERE created_by = ?";
        $result = Database::fetch($sql, [$userId]);
        $stats['projects_created'] = $result['count'];
        
        // 創建的模板數量
        $sql = "SELECT COUNT(*) as count FROM email_templates WHERE created_by = ?";
        $result = Database::fetch($sql, [$userId]);
        $stats['templates_created'] = $result['count'];
        
        // 最後登入時間
        $sql = "SELECT MAX(last_activity) as last_login FROM sessions WHERE user_id = ?";
        $result = Database::fetch($sql, [$userId]);
        $stats['last_login'] = $result['last_login'];
        
        // 登入次數（過去30天）
        $sql = "SELECT COUNT(*) as count FROM system_logs 
                WHERE user_id = ? AND message LIKE '%logged in%' 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $result = Database::fetch($sql, [$userId]);
        $stats['login_count_30d'] = $result['count'];
        
        return $stats;
    }
    
    /**
     * 搜索用戶
     */
    public function searchUsers($keyword, $role = null, $limit = 50) {
        $sql = "SELECT * FROM users WHERE 
                (username LIKE ? OR email LIKE ? OR full_name LIKE ?) 
                AND is_active = 1";
        $params = ["%$keyword%", "%$keyword%", "%$keyword%"];
        
        if ($role) {
            $sql .= " AND role = ?";
            $params[] = $role;
        }
        
        $sql .= " ORDER BY full_name ASC LIMIT ?";
        $params[] = $limit;
        
        $users = Database::fetchAll($sql, $params);
        return $this->hideFields($users);
    }
    
    /**
     * 獲取管理員列表
     */
    public function getAdmins() {
        return $this->getActiveUsers('admin');
    }
    
    /**
     * 檢查用戶是否為管理員
     */
    public function isAdmin($userId) {
        $user = $this->find($userId);
        return $user && $user['role'] === 'admin';
    }
    
    /**
     * 設置用戶角色
     */
    public function setRole($userId, $role) {
        if (!in_array($role, ['admin', 'user'])) {
            throw new Exception('無效的用戶角色');
        }
        
        return $this->update($userId, ['role' => $role]);
    }
}