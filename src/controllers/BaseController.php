<?php

abstract class BaseController {
    protected $request;
    protected $response;
    
    public function __construct() {
        $this->request = $_REQUEST;
        $this->response = [];
        
        // 包含所需的類文件
        $this->loadDependencies();
    }
    
    /**
     * 載入依賴文件
     */
    protected function loadDependencies() {
        require_once dirname(__DIR__, 2) . '/config/config.php';
        require_once dirname(__DIR__, 2) . '/config/database.php';
        require_once dirname(__DIR__) . '/utils/Utils.php';
        require_once dirname(__DIR__) . '/utils/Session.php';
        require_once dirname(__DIR__) . '/models/BaseModel.php';
        
        // 啟動會話
        Session::start();
    }
    
    /**
     * 獲取POST數據
     */
    protected function getPostData() {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        }
        
        return $_POST;
    }
    
    /**
     * 獲取GET參數
     */
    protected function getParam($key, $default = null) {
        return $_GET[$key] ?? $default;
    }
    
    /**
     * 驗證必要字段
     */
    protected function validateRequired($data, $fields) {
        $missing = [];
        
        foreach ($fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            Utils::errorResponse('缺少必要字段: ' . implode(', ', $missing), 400);
        }
    }
    
    /**
     * 驗證CSRF令牌
     */
    protected function validateCSRF() {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        
        if (!Utils::validateCSRFToken($token)) {
            Utils::errorResponse('CSRF token invalid', 403);
        }
    }
    
    /**
     * 檢查登入狀態
     */
    protected function requireAuth() {
        if (!Session::isLoggedIn()) {
            Utils::errorResponse('Authentication required', 401);
        }
    }
    
    /**
     * 檢查管理員權限
     */
    protected function requireAdmin() {
        $this->requireAuth();
        
        if (!Session::isAdmin()) {
            Utils::errorResponse('Admin privileges required', 403);
        }
    }
    
    /**
     * 處理文件上傳
     */
    protected function handleFileUpload($fileKey, $allowedTypes = null, $maxSize = null) {
        if (!isset($_FILES[$fileKey])) {
            throw new Exception('未找到上傳文件');
        }
        
        return Utils::handleFileUpload($_FILES[$fileKey], $allowedTypes, $maxSize);
    }
    
    /**
     * 渲染視圖
     */
    protected function render($view, $data = []) {
        $viewPath = ROOT_PATH . '/templates/' . $view . '.php';
        
        if (!file_exists($viewPath)) {
            throw new Exception("View file not found: {$view}");
        }
        
        // 提取數據到局部變量
        extract($data);
        
        // 包含公共數據
        $user = Session::isLoggedIn() ? [
            'id' => Session::getUserId(),
            'username' => Session::get('username'),
            'role' => Session::getUserRole(),
            'full_name' => Session::get('full_name')
        ] : null;
        
        $csrf_token = Utils::generateCSRFToken();
        $flash_messages = Session::getFlashes();
        
        include $viewPath;
    }
    
    /**
     * JSON響應
     */
    protected function jsonResponse($data, $code = 200) {
        Utils::jsonResponse($data, $code);
    }
    
    /**
     * 成功響應
     */
    protected function success($data = [], $message = 'Success') {
        Utils::successResponse($data, $message);
    }
    
    /**
     * 錯誤響應
     */
    protected function error($message, $code = 400) {
        Utils::errorResponse($message, $code);
    }
    
    /**
     * 重定向
     */
    protected function redirect($url, $code = 302) {
        http_response_code($code);
        header("Location: {$url}");
        exit;
    }
    
    /**
     * 記錄日誌
     */
    protected function log($level, $message, $context = []) {
        $userId = Session::getUserId();
        Utils::log($level, $message, $userId, $context);
    }
    
    /**
     * 驗證郵件格式
     */
    protected function validateEmail($email) {
        if (!Utils::validateEmail($email)) {
            throw new Exception('無效的郵件格式');
        }
    }
    
    /**
     * 驗證日期格式
     */
    protected function validateDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    /**
     * 分頁處理
     */
    protected function getPagination() {
        $page = max(1, (int) $this->getParam('page', 1));
        $perPage = max(1, min(100, (int) $this->getParam('per_page', 20)));
        
        return [$page, $perPage];
    }
    
    /**
     * 搜索條件處理
     */
    protected function getSearchConditions($allowedFields) {
        $conditions = [];
        
        foreach ($allowedFields as $field) {
            $value = $this->getParam($field);
            if ($value !== null && $value !== '') {
                $conditions[$field] = $value;
            }
        }
        
        return $conditions;
    }
    
    /**
     * 處理排序
     */
    protected function getOrderBy($allowedFields, $default = 'id DESC') {
        $sortBy = $this->getParam('sort_by');
        $sortOrder = $this->getParam('sort_order', 'DESC');
        
        if ($sortBy && in_array($sortBy, $allowedFields)) {
            $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';
            return "{$sortBy} {$sortOrder}";
        }
        
        return $default;
    }
    
    /**
     * 清理輸入數據
     */
    protected function sanitizeInput($data) {
        return Utils::sanitizeInput($data);
    }
}