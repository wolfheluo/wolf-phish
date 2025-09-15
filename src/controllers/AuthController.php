<?php

require_once 'BaseController.php';
require_once dirname(__DIR__) . '/models/UserModel.php';

class AuthController extends BaseController {
    private $userModel;
    
    public function __construct() {
        parent::__construct();
        $this->userModel = new UserModel();
    }
    
    /**
     * 顯示登入頁面
     */
    public function showLogin() {
        if (Session::isLoggedIn()) {
            $this->redirect('/dashboard');
        }
        
        $this->render('auth/login', [
            'title' => '用戶登入',
            'timeout' => $this->getParam('timeout', false)
        ]);
    }
    
    /**
     * 處理登入請求
     */
    public function login() {
        try {
            $data = $this->getPostData();
            $this->validateRequired($data, ['username', 'password']);
            
            $username = $this->sanitizeInput($data['username']);
            $password = $data['password'];
            
            // 驗證用戶
            $user = $this->userModel->authenticate($username, $password);
            
            if (!$user) {
                $this->log('WARNING', 'Login failed', ['username' => $username]);
                $this->error('用戶名或密碼錯誤');
            }
            
            // 登入成功
            Session::login($user);
            
            $this->success([
                'user' => $user,
                'redirect' => '/dashboard'
            ], '登入成功');
            
        } catch (Exception $e) {
            $this->log('ERROR', 'Login error', ['error' => $e->getMessage()]);
            $this->error($e->getMessage());
        }
    }
    
    /**
     * 用戶登出
     */
    public function logout() {
        Session::logout();
        $this->redirect('/auth/login');
    }
    
    /**
     * 顯示註冊頁面
     */
    public function showRegister() {
        if (Session::isLoggedIn()) {
            $this->redirect('/dashboard');
        }
        
        $this->render('auth/register', [
            'title' => '用戶註冊'
        ]);
    }
    
    /**
     * 處理註冊請求
     */
    public function register() {
        try {
            $data = $this->getPostData();
            $this->validateRequired($data, ['username', 'password', 'email', 'full_name']);
            $this->validateCSRF();
            
            $userData = $this->sanitizeInput([
                'username' => $data['username'],
                'password' => $data['password'],
                'email' => $data['email'],
                'full_name' => $data['full_name'],
                'department' => $data['department'] ?? '',
                'role' => 'user',
                'is_active' => 1
            ]);
            
            // 驗證郵件格式
            $this->validateEmail($userData['email']);
            
            // 密碼強度驗證
            if (strlen($userData['password']) < 6) {
                $this->error('密碼長度至少6位');
            }
            
            // 創建用戶
            $user = $this->userModel->createUser($userData);
            
            $this->log('INFO', 'User registered', ['user_id' => $user['id'], 'username' => $user['username']]);
            
            $this->success([
                'user' => $user,
                'redirect' => '/auth/login'
            ], '註冊成功，請登入');
            
        } catch (Exception $e) {
            $this->log('ERROR', 'Registration error', ['error' => $e->getMessage()]);
            $this->error($e->getMessage());
        }
    }
    
    /**
     * 更改密碼
     */
    public function changePassword() {
        try {
            $this->requireAuth();
            
            $data = $this->getPostData();
            $this->validateRequired($data, ['old_password', 'new_password']);
            $this->validateCSRF();
            
            $userId = Session::getUserId();
            $oldPassword = $data['old_password'];
            $newPassword = $data['new_password'];
            
            // 密碼強度驗證
            if (strlen($newPassword) < 6) {
                $this->error('新密碼長度至少6位');
            }
            
            $this->userModel->changePassword($userId, $oldPassword, $newPassword);
            
            $this->log('INFO', 'Password changed', ['user_id' => $userId]);
            
            $this->success([], '密碼更改成功');
            
        } catch (Exception $e) {
            $this->log('ERROR', 'Change password error', ['error' => $e->getMessage()]);
            $this->error($e->getMessage());
        }
    }
    
    /**
     * 個人資料更新
     */
    public function updateProfile() {
        try {
            $this->requireAuth();
            
            $data = $this->getPostData();
            $this->validateCSRF();
            
            $userId = Session::getUserId();
            
            $updateData = $this->sanitizeInput([
                'full_name' => $data['full_name'] ?? '',
                'email' => $data['email'] ?? '',
                'department' => $data['department'] ?? ''
            ]);
            
            // 移除空值
            $updateData = array_filter($updateData, function($value) {
                return $value !== '';
            });
            
            // 驗證郵件格式
            if (isset($updateData['email'])) {
                $this->validateEmail($updateData['email']);
            }
            
            $user = $this->userModel->updateUser($userId, $updateData);
            
            // 更新會話信息
            Session::set('full_name', $user['full_name']);
            
            $this->log('INFO', 'Profile updated', ['user_id' => $userId]);
            
            $this->success($user, '個人資料更新成功');
            
        } catch (Exception $e) {
            $this->log('ERROR', 'Update profile error', ['error' => $e->getMessage()]);
            $this->error($e->getMessage());
        }
    }
    
    /**
     * 獲取當前用戶信息
     */
    public function getUserInfo() {
        try {
            $this->requireAuth();
            
            $userId = Session::getUserId();
            $user = $this->userModel->find($userId);
            
            if (!$user) {
                $this->error('用戶不存在', 404);
            }
            
            $stats = $this->userModel->getUserStats($userId);
            
            $this->success([
                'user' => $user,
                'stats' => $stats
            ]);
            
        } catch (Exception $e) {
            $this->log('ERROR', 'Get user info error', ['error' => $e->getMessage()]);
            $this->error($e->getMessage());
        }
    }
    
    /**
     * 檢查會話狀態
     */
    public function checkSession() {
        $this->success([
            'logged_in' => Session::isLoggedIn(),
            'user' => Session::isLoggedIn() ? [
                'id' => Session::getUserId(),
                'username' => Session::get('username'),
                'role' => Session::getUserRole(),
                'full_name' => Session::get('full_name')
            ] : null
        ]);
    }
}