<?php

require_once 'BaseController.php';
require_once dirname(__DIR__) . '/models/Project.php';

class TrackingController extends BaseController {
    private $projectModel;
    
    public function __construct() {
        parent::__construct();
        $this->projectModel = new Project();
    }
    
    /**
     * 追蹤像素 - 郵件開啟
     */
    public function pixel() {
        try {
            $projectId = $this->getParam('project_id');
            $email = $this->getParam('email');
            
            if (!$projectId || !$email) {
                $this->serve1x1Pixel();
                return;
            }
            
            // 記錄追蹤信息
            $this->recordPixelTracking($projectId, $email);
            
            // 返回 1x1 透明像素
            $this->serve1x1Pixel();
            
        } catch (Exception $e) {
            $this->log('ERROR', 'Pixel tracking error', [
                'error' => $e->getMessage(),
                'project_id' => $projectId ?? '',
                'email' => $email ?? ''
            ]);
            
            $this->serve1x1Pixel();
        }
    }
    
    /**
     * URL 點擊追蹤
     */
    public function url() {
        try {
            $projectId = $this->getParam('project_id');
            $email = $this->getParam('email');
            $targetUrl = $this->getParam('url');
            
            if (!$projectId || !$email) {
                $this->redirect('/');
                return;
            }
            
            // 記錄點擊追蹤
            $this->recordUrlTracking($projectId, $email, $targetUrl);
            
            // 獲取專案信息以確定重定向URL
            $project = $this->projectModel->find($projectId);
            
            if ($project && $project['phish_url']) {
                // 重定向到釣魚頁面
                $redirectUrl = $project['phish_url'] . '?project_id=' . urlencode($projectId) . '&email=' . urlencode($email);
                $this->redirect($redirectUrl);
            } else {
                // 如果沒有設定釣魚頁面，重定向到預設頁面
                $this->redirect('/phish/default?project_id=' . urlencode($projectId) . '&email=' . urlencode($email));
            }
            
        } catch (Exception $e) {
            $this->log('ERROR', 'URL tracking error', [
                'error' => $e->getMessage(),
                'project_id' => $projectId ?? '',
                'email' => $email ?? ''
            ]);
            
            $this->redirect('/');
        }
    }
    
    /**
     * 附件下載追蹤
     */
    public function zip() {
        try {
            $projectId = $this->getParam('project_id');
            $email = $this->getParam('email');
            $filename = $this->getParam('file', 'attachment.zip');
            
            if (!$projectId || !$email) {
                http_response_code(404);
                echo 'File not found';
                return;
            }
            
            // 記錄下載追蹤
            $this->recordZipTracking($projectId, $email, $filename);
            
            // 提供假的ZIP文件下載
            $this->serveZipFile($filename);
            
        } catch (Exception $e) {
            $this->log('ERROR', 'ZIP tracking error', [
                'error' => $e->getMessage(),
                'project_id' => $projectId ?? '',
                'email' => $email ?? ''
            ]);
            
            http_response_code(500);
            echo 'Download failed';
        }
    }
    
    /**
     * 數據提交追蹤
     */
    public function data() {
        try {
            $projectId = $this->getParam('project_id');
            $email = $this->getParam('email');
            
            if (!$projectId || !$email) {
                $this->error('Invalid tracking parameters', 400);
            }
            
            $formData = $this->getPostData();
            
            // 記錄數據提交
            $this->recordDataTracking($projectId, $email, $formData);
            
            $this->success([
                'message' => '數據已記錄',
                'redirect' => '/phish/success?project_id=' . urlencode($projectId)
            ]);
            
        } catch (Exception $e) {
            $this->log('ERROR', 'Data tracking error', [
                'error' => $e->getMessage(),
                'project_id' => $projectId ?? '',
                'email' => $email ?? ''
            ]);
            
            $this->error($e->getMessage());
        }
    }
    
    /**
     * 記錄像素追蹤
     */
    private function recordPixelTracking($projectId, $email) {
        $sql = "INSERT INTO track_pixel_logs (project_id, email, ip_address, user_agent, opened_at) 
                VALUES (?, ?, ?, ?, NOW())";
        
        Database::query($sql, [
            $projectId,
            $email,
            Utils::getClientIP(),
            Utils::getUserAgent()
        ]);
        
        $this->log('INFO', 'Email opened', [
            'project_id' => $projectId,
            'email' => $email
        ]);
    }
    
    /**
     * 記錄URL點擊追蹤
     */
    private function recordUrlTracking($projectId, $email, $url = null) {
        $sql = "INSERT INTO track_url_logs (project_id, email, url, ip_address, user_agent, referrer, clicked_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        Database::query($sql, [
            $projectId,
            $email,
            $url,
            Utils::getClientIP(),
            Utils::getUserAgent(),
            $_SERVER['HTTP_REFERER'] ?? null
        ]);
        
        $this->log('INFO', 'URL clicked', [
            'project_id' => $projectId,
            'email' => $email,
            'url' => $url
        ]);
    }
    
    /**
     * 記錄附件下載追蹤
     */
    private function recordZipTracking($projectId, $email, $filename) {
        $sql = "INSERT INTO track_zip_logs (project_id, email, filename, ip_address, user_agent, downloaded_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        
        Database::query($sql, [
            $projectId,
            $email,
            $filename,
            Utils::getClientIP(),
            Utils::getUserAgent()
        ]);
        
        $this->log('INFO', 'File downloaded', [
            'project_id' => $projectId,
            'email' => $email,
            'filename' => $filename
        ]);
    }
    
    /**
     * 記錄數據提交追蹤
     */
    private function recordDataTracking($projectId, $email, $formData) {
        // 過濾敏感數據
        $filteredData = $this->filterSensitiveData($formData);
        
        $sql = "INSERT INTO track_data_logs (project_id, email, form_data, ip_address, user_agent, submitted_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        
        Database::query($sql, [
            $projectId,
            $email,
            json_encode($filteredData, JSON_UNESCAPED_UNICODE),
            Utils::getClientIP(),
            Utils::getUserAgent()
        ]);
        
        $this->log('WARNING', 'Form data submitted', [
            'project_id' => $projectId,
            'email' => $email,
            'fields_count' => count($filteredData)
        ]);
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
                    $filtered[$key] = str_repeat('*', strlen($value));
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
     * 提供 1x1 透明像素
     */
    private function serve1x1Pixel() {
        header('Content-Type: image/png');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // 1x1 透明 PNG 圖像的二進制數據
        $pixel = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
        echo $pixel;
        exit;
    }
    
    /**
     * 提供ZIP文件下載
     */
    private function serveZipFile($filename) {
        // 創建一個包含警告信息的ZIP文件
        $zipContent = $this->createWarningZip();
        
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($zipContent));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo $zipContent;
        exit;
    }
    
    /**
     * 創建包含警告信息的ZIP文件
     */
    private function createWarningZip() {
        // 創建臨時ZIP文件
        $tempFile = tempnam(sys_get_temp_dir(), 'phish_warning_');
        $zip = new ZipArchive();
        
        if ($zip->open($tempFile, ZipArchive::CREATE) === TRUE) {
            $warningContent = "安全警告 - Security Warning\n\n";
            $warningContent .= "這是一個社交工程測試！\n";
            $warningContent .= "This is a social engineering test!\n\n";
            $warningContent .= "您剛才的行為被記錄下來用於安全意識培訓。\n";
            $warningContent .= "Your action has been logged for security awareness training.\n\n";
            $warningContent .= "請記住以下安全原則：\n";
            $warningContent .= "Please remember these security principles:\n\n";
            $warningContent .= "1. 不要下載來源不明的附件\n";
            $warningContent .= "   Do not download attachments from unknown sources\n\n";
            $warningContent .= "2. 懷疑可疑郵件時請聯繫IT部門\n";
            $warningContent .= "   Contact IT department when suspicious emails are received\n\n";
            $warningContent .= "3. 定期參加安全意識培訓\n";
            $warningContent .= "   Attend security awareness training regularly\n\n";
            $warningContent .= "生成時間 Generated at: " . date('Y-m-d H:i:s') . "\n";
            
            $zip->addFromString('安全警告_Security_Warning.txt', $warningContent);
            $zip->close();
        }
        
        $content = file_get_contents($tempFile);
        unlink($tempFile);
        
        return $content;
    }
    
    /**
     * 獲取追蹤統計
     */
    public function stats() {
        try {
            $this->requireAuth();
            
            $projectId = $this->getParam('project_id');
            
            if (!$projectId) {
                $this->error('Project ID required');
            }
            
            $stats = $this->getTrackingStats($projectId);
            
            $this->success($stats);
            
        } catch (Exception $e) {
            $this->log('ERROR', 'Get tracking stats error', ['error' => $e->getMessage()]);
            $this->error($e->getMessage());
        }
    }
    
    /**
     * 獲取追蹤統計數據
     */
    private function getTrackingStats($projectId) {
        $stats = [];
        
        // 基本統計
        $sql = "SELECT 
                    COUNT(DISTINCT email) as unique_opens,
                    COUNT(*) as total_opens
                FROM track_pixel_logs WHERE project_id = ?";
        $result = Database::fetch($sql, [$projectId]);
        $stats['pixel'] = $result;
        
        $sql = "SELECT 
                    COUNT(DISTINCT email) as unique_clicks,
                    COUNT(*) as total_clicks
                FROM track_url_logs WHERE project_id = ?";
        $result = Database::fetch($sql, [$projectId]);
        $stats['url'] = $result;
        
        $sql = "SELECT 
                    COUNT(DISTINCT email) as unique_downloads,
                    COUNT(*) as total_downloads
                FROM track_zip_logs WHERE project_id = ?";
        $result = Database::fetch($sql, [$projectId]);
        $stats['zip'] = $result;
        
        $sql = "SELECT 
                    COUNT(DISTINCT email) as unique_submissions,
                    COUNT(*) as total_submissions
                FROM track_data_logs WHERE project_id = ?";
        $result = Database::fetch($sql, [$projectId]);
        $stats['data'] = $result;
        
        // 時間分佈
        $sql = "SELECT 
                    DATE(opened_at) as date,
                    COUNT(*) as count
                FROM track_pixel_logs 
                WHERE project_id = ? 
                GROUP BY DATE(opened_at) 
                ORDER BY date";
        $stats['timeline_opens'] = Database::fetchAll($sql, [$projectId]);
        
        $sql = "SELECT 
                    DATE(clicked_at) as date,
                    COUNT(*) as count
                FROM track_url_logs 
                WHERE project_id = ? 
                GROUP BY DATE(clicked_at) 
                ORDER BY date";
        $stats['timeline_clicks'] = Database::fetchAll($sql, [$projectId]);
        
        // 設備統計
        $sql = "SELECT 
                    CASE 
                        WHEN user_agent LIKE '%Mobile%' OR user_agent LIKE '%Android%' OR user_agent LIKE '%iPhone%' THEN 'Mobile'
                        WHEN user_agent LIKE '%Tablet%' OR user_agent LIKE '%iPad%' THEN 'Tablet'  
                        ELSE 'Desktop'
                    END as device_type,
                    COUNT(DISTINCT email) as count
                FROM track_pixel_logs 
                WHERE project_id = ?
                GROUP BY device_type";
        $stats['devices'] = Database::fetchAll($sql, [$projectId]);
        
        return $stats;
    }
}