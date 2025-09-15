<?php

require_once 'BaseController.php';
require_once dirname(__DIR__) . '/models/Project.php';

class PhishController extends BaseController {
    private $projectModel;
    
    public function __construct() {
        parent::__construct();
        $this->projectModel = new Project();
    }
    
    /**
     * 預設釣魚頁面
     */
    public function defaultPage() {
        $projectId = $this->getParam('project_id');
        $email = $this->getParam('email');
        
        if (!$projectId || !$email) {
            $this->redirect('/');
            return;
        }
        
        // 獲取專案信息
        $project = $this->projectModel->find($projectId);
        
        if (!$project) {
            $this->redirect('/');
            return;
        }
        
        // 記錄頁面訪問
        $this->logPageVisit($projectId, $email, 'default');
        
        // 顯示釣魚頁面
        $this->render('phish/default', [
            'project' => $project,
            'project_id' => $projectId,
            'email' => $email
        ]);
    }
    
    /**
     * 登錄釣魚頁面
     */
    public function loginPage() {
        $projectId = $this->getParam('project_id');
        $email = $this->getParam('email');
        
        if (!$projectId || !$email) {
            $this->redirect('/');
            return;
        }
        
        // 記錄頁面訪問
        $this->logPageVisit($projectId, $email, 'login');
        
        // 處理表單提交
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleLoginSubmit($projectId, $email);
            return;
        }
        
        // 顯示登錄頁面
        $this->render('phish/login', [
            'project_id' => $projectId,
            'email' => $email,
            'target_site' => $this->getParam('site', 'Office 365')
        ]);
    }
    
    /**
     * 處理登錄表單提交
     */
    private function handleLoginSubmit($projectId, $email) {
        $username = $this->getParam('username');
        $password = $this->getParam('password');
        
        if ($username && $password) {
            // 記錄憑證（敏感信息會被過濾）
            $this->recordCredentials($projectId, $email, $username, $password);
        }
        
        // 重定向到成功頁面
        $this->redirect('/phish/success?project_id=' . urlencode($projectId));
    }
    
    /**
     * 檔案下載釣魚頁面
     */
    public function downloadPage() {
        $projectId = $this->getParam('project_id');
        $email = $this->getParam('email');
        $filename = $this->getParam('file', 'document.pdf');
        
        if (!$projectId || !$email) {
            $this->redirect('/');
            return;
        }
        
        // 記錄頁面訪問
        $this->logPageVisit($projectId, $email, 'download');
        
        // 顯示下載頁面
        $this->render('phish/download', [
            'project_id' => $projectId,
            'email' => $email,
            'filename' => $filename
        ]);
    }
    
    /**
     * 調查問卷釣魚頁面
     */
    public function surveyPage() {
        $projectId = $this->getParam('project_id');
        $email = $this->getParam('email');
        
        if (!$projectId || !$email) {
            $this->redirect('/');
            return;
        }
        
        // 記錄頁面訪問
        $this->logPageVisit($projectId, $email, 'survey');
        
        // 處理表單提交
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSurveySubmit($projectId, $email);
            return;
        }
        
        // 顯示問卷頁面
        $this->render('phish/survey', [
            'project_id' => $projectId,
            'email' => $email
        ]);
    }
    
    /**
     * 處理問卷提交
     */
    private function handleSurveySubmit($projectId, $email) {
        $formData = $this->getPostData();
        
        // 記錄表單數據
        $this->recordFormData($projectId, $email, $formData);
        
        // 重定向到成功頁面
        $this->redirect('/phish/success?project_id=' . urlencode($projectId));
    }
    
    /**
     * 成功頁面
     */
    public function successPage() {
        $projectId = $this->getParam('project_id');
        
        // 顯示安全教育頁面
        $this->render('phish/success', [
            'project_id' => $projectId,
            'show_education' => true
        ]);
    }
    
    /**
     * 安全警告頁面
     */
    public function warningPage() {
        $projectId = $this->getParam('project_id');
        $email = $this->getParam('email');
        
        $this->render('phish/warning', [
            'project_id' => $projectId,
            'email' => $email
        ]);
    }
    
    /**
     * Office 365 釣魚頁面
     */
    public function office365Page() {
        $projectId = $this->getParam('project_id');
        $email = $this->getParam('email');
        
        if (!$projectId || !$email) {
            $this->redirect('/');
            return;
        }
        
        // 記錄頁面訪問
        $this->logPageVisit($projectId, $email, 'office365');
        
        // 處理表單提交
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleOffice365Submit($projectId, $email);
            return;
        }
        
        // 顯示Office 365登錄頁面
        $this->render('phish/office365', [
            'project_id' => $projectId,
            'email' => $email
        ]);
    }
    
    /**
     * 處理Office 365表單提交
     */
    private function handleOffice365Submit($projectId, $email) {
        $username = $this->getParam('username');
        $password = $this->getParam('password');
        
        if ($username && $password) {
            $this->recordCredentials($projectId, $email, $username, $password);
        }
        
        // 模擬登錄失敗，要求再次輸入
        $attempts = (int)$this->getParam('attempts', 0) + 1;
        
        if ($attempts < 3) {
            $this->render('phish/office365', [
                'project_id' => $projectId,
                'email' => $email,
                'error' => '用戶名或密碼錯誤，請重試',
                'attempts' => $attempts
            ]);
        } else {
            // 第三次嘗試後顯示成功頁面
            $this->redirect('/phish/success?project_id=' . urlencode($projectId));
        }
    }
    
    /**
     * 記錄頁面訪問
     */
    private function logPageVisit($projectId, $email, $pageType) {
        try {
            $sql = "INSERT INTO track_page_visits (project_id, email, page_type, ip_address, user_agent, visited_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            
            Database::query($sql, [
                $projectId,
                $email,
                $pageType,
                Utils::getClientIP(),
                Utils::getUserAgent()
            ]);
            
            $this->log('INFO', 'Page visited', [
                'project_id' => $projectId,
                'email' => $email,
                'page_type' => $pageType
            ]);
            
        } catch (Exception $e) {
            $this->log('ERROR', 'Log page visit error', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * 記錄憑證信息
     */
    private function recordCredentials($projectId, $email, $username, $password) {
        try {
            // 為了安全，密碼只記錄長度和特徵
            $passwordInfo = [
                'length' => strlen($password),
                'has_upper' => preg_match('/[A-Z]/', $password) ? 1 : 0,
                'has_lower' => preg_match('/[a-z]/', $password) ? 1 : 0,
                'has_digit' => preg_match('/\d/', $password) ? 1 : 0,
                'has_special' => preg_match('/[^A-Za-z0-9]/', $password) ? 1 : 0
            ];
            
            $sql = "INSERT INTO track_credentials (project_id, email, username, password_hash, password_info, ip_address, user_agent, captured_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            
            Database::query($sql, [
                $projectId,
                $email,
                $username,
                hash('sha256', $password), // 只存儲哈希值
                json_encode($passwordInfo),
                Utils::getClientIP(),
                Utils::getUserAgent()
            ]);
            
            $this->log('WARNING', 'Credentials captured', [
                'project_id' => $projectId,
                'email' => $email,
                'username' => $username
            ]);
            
        } catch (Exception $e) {
            $this->log('ERROR', 'Record credentials error', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * 記錄表單數據
     */
    private function recordFormData($projectId, $email, $formData) {
        try {
            // 過濾敏感數據
            $filteredData = $this->filterSensitiveData($formData);
            
            $sql = "INSERT INTO track_form_data (project_id, email, form_data, ip_address, user_agent, submitted_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            
            Database::query($sql, [
                $projectId,
                $email,
                json_encode($filteredData, JSON_UNESCAPED_UNICODE),
                Utils::getClientIP(),
                Utils::getUserAgent()
            ]);
            
            $this->log('INFO', 'Form data captured', [
                'project_id' => $projectId,
                'email' => $email,
                'fields_count' => count($filteredData)
            ]);
            
        } catch (Exception $e) {
            $this->log('ERROR', 'Record form data error', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * 過濾敏感數據
     */
    private function filterSensitiveData($data) {
        $sensitiveFields = ['password', 'passwd', 'pwd', 'pin', 'ssn', 'credit_card', 'cvv'];
        $filtered = [];
        
        foreach ($data as $key => $value) {
            $lowerKey = strtolower($key);
            $isSensitive = false;
            
            foreach ($sensitiveFields as $sensitive) {
                if (strpos($lowerKey, $sensitive) !== false) {
                    $filtered[$key] = [
                        'length' => strlen($value),
                        'hash' => hash('sha256', $value)
                    ];
                    $isSensitive = true;
                    break;
                }
            }
            
            if (!$isSensitive) {
                $filtered[$key] = $value;
            }
        }
        
        return $filtered;
    }
    
    /**
     * API: 記錄鍵盤記錄
     */
    public function keylogger() {
        try {
            $projectId = $this->getParam('project_id');
            $email = $this->getParam('email');
            $keystrokes = $this->getParam('keystrokes');
            
            if (!$projectId || !$email || !$keystrokes) {
                $this->error('Missing required parameters', 400);
            }
            
            // 記錄鍵盤記錄（僅用於教育目的）
            $sql = "INSERT INTO track_keylogger (project_id, email, keystrokes, ip_address, user_agent, recorded_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            
            Database::query($sql, [
                $projectId,
                $email,
                hash('sha256', $keystrokes), // 只存儲哈希值
                Utils::getClientIP(),
                Utils::getUserAgent()
            ]);
            
            $this->success(['message' => 'Keystrokes recorded']);
            
        } catch (Exception $e) {
            $this->log('ERROR', 'Keylogger error', ['error' => $e->getMessage()]);
            $this->error($e->getMessage());
        }
    }
    
    /**
     * API: 記錄屏幕截圖（Base64）
     */
    public function screenshot() {
        try {
            $projectId = $this->getParam('project_id');
            $email = $this->getParam('email');
            $screenshot = $this->getParam('screenshot');
            
            if (!$projectId || !$email || !$screenshot) {
                $this->error('Missing required parameters', 400);
            }
            
            // 保存截圖（實際應用中可能需要限制大小）
            $filename = 'screenshot_' . $projectId . '_' . md5($email . time()) . '.png';
            $filepath = '/var/log/phish/screenshots/' . $filename;
            
            // 確保目錄存在
            $dir = dirname($filepath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // 解碼並保存Base64圖片
            $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $screenshot));
            file_put_contents($filepath, $imageData);
            
            // 記錄到數據庫
            $sql = "INSERT INTO track_screenshots (project_id, email, filename, filepath, ip_address, user_agent, captured_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            Database::query($sql, [
                $projectId,
                $email,
                $filename,
                $filepath,
                Utils::getClientIP(),
                Utils::getUserAgent()
            ]);
            
            $this->success(['message' => 'Screenshot recorded']);
            
        } catch (Exception $e) {
            $this->log('ERROR', 'Screenshot error', ['error' => $e->getMessage()]);
            $this->error($e->getMessage());
        }
    }
}