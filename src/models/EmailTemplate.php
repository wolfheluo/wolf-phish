<?php

require_once 'BaseModel.php';

class EmailTemplate extends BaseModel {
    protected $table = 'email_templates';
    protected $fillable = ['name', 'description', 'subject', 'html_content', 'created_by', 'is_active'];
    
    /**
     * 獲取可用的郵件模板
     */
    public function getActiveTemplates() {
        $sql = "SELECT et.*, u.full_name as creator_name 
                FROM email_templates et 
                LEFT JOIN users u ON et.created_by = u.id 
                WHERE et.is_active = 1 
                ORDER BY et.created_at DESC";
        return Database::fetchAll($sql);
    }
    
    /**
     * 創建郵件模板
     */
    public function createTemplate($data) {
        return $this->create($data);
    }
    
    /**
     * 處理郵件模板中的動態標記
     */
    public function processTemplate($templateId, $projectId, $email) {
        $template = $this->find($templateId);
        if (!$template) {
            throw new Exception('郵件模板不存在');
        }
        
        // 獲取專案信息
        $projectModel = new Project();
        $project = $projectModel->find($projectId);
        if (!$project) {
            throw new Exception('專案不存在');
        }
        
        $content = $template['html_content'];
        $subject = $template['subject'];
        
        // 替換標記
        $replacements = [
            '{TRACK_PIXEL}' => $project['track_pixel_url'] . '?project_id=' . $projectId . '&email=' . urlencode($email),
            '{TRACK_URL}' => $project['phish_url'] . '?project_id=' . $projectId . '&email=' . urlencode($email),
            '{TRACK_ZIP}' => $project['track_zip_url'] . '?project_id=' . $projectId . '&email=' . urlencode($email),
            '{PROJECT_ID}' => $project['project_code'],
            '{EMAIL}' => $email,
            '{PHISH_URL}' => $project['phish_url'],
        ];
        
        foreach ($replacements as $placeholder => $value) {
            $content = str_replace($placeholder, $value, $content);
            $subject = str_replace($placeholder, $value, $subject);
        }
        
        return [
            'subject' => $subject,
            'html_content' => $content,
            'text_content' => strip_tags($content) // 純文字版本
        ];
    }
    
    /**
     * 預覽模板
     */
    public function previewTemplate($templateId, $sampleData = []) {
        $template = $this->find($templateId);
        if (!$template) {
            throw new Exception('郵件模板不存在');
        }
        
        $content = $template['html_content'];
        $subject = $template['subject'];
        
        // 使用樣本數據替換標記
        $defaultSampleData = [
            '{TRACK_PIXEL}' => TRACK_PIXEL_URL . '?project_id=SAMPLE&email=sample@example.com',
            '{TRACK_URL}' => PHISH_SITE_URL . '?project_id=SAMPLE&email=sample@example.com',
            '{TRACK_ZIP}' => TRACK_ZIP_URL . '?project_id=SAMPLE&email=sample@example.com',
            '{PROJECT_ID}' => 'SAMPLE_PROJECT',
            '{EMAIL}' => 'sample@example.com',
            '{PHISH_URL}' => PHISH_SITE_URL,
        ];
        
        $sampleData = array_merge($defaultSampleData, $sampleData);
        
        foreach ($sampleData as $placeholder => $value) {
            $content = str_replace($placeholder, $value, $content);
            $subject = str_replace($placeholder, $value, $subject);
        }
        
        return [
            'subject' => $subject,
            'html_content' => $content
        ];
    }
    
    /**
     * 驗證模板內容
     */
    public function validateTemplate($htmlContent) {
        $errors = [];
        
        // 檢查基本HTML結構
        if (strpos($htmlContent, '<html') === false) {
            $errors[] = '模板應包含完整的HTML文檔結構';
        }
        
        if (strpos($htmlContent, '<head>') === false) {
            $errors[] = '模板應包含<head>標籤';
        }
        
        if (strpos($htmlContent, '<body>') === false) {
            $errors[] = '模板應包含<body>標籤';
        }
        
        // 檢查字符編碼
        if (strpos($htmlContent, 'charset') === false) {
            $errors[] = '建議在模板中指定字符編碼(UTF-8)';
        }
        
        // 檢查是否包含追蹤像素
        if (strpos($htmlContent, '{TRACK_PIXEL}') === false) {
            $errors[] = '模板應包含追蹤像素標記 {TRACK_PIXEL}';
        }
        
        // 檢查HTML標籤是否正確閉合
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $valid = $dom->loadHTML($htmlContent);
        if (!$valid) {
            $errors[] = 'HTML格式有誤，請檢查標籤是否正確閉合';
        }
        libxml_clear_errors();
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * 獲取模板使用統計
     */
    public function getTemplateStats($templateId) {
        $stats = [];
        
        // 使用此模板的專案數量
        $sql = "SELECT COUNT(*) as count FROM projects WHERE email_template_id = ?";
        $result = Database::fetch($sql, [$templateId]);
        $stats['projects_count'] = $result['count'];
        
        // 使用此模板發送的郵件數量
        $sql = "SELECT COUNT(te.id) as count 
                FROM target_emails te 
                JOIN projects p ON te.project_id = p.id 
                WHERE p.email_template_id = ? AND te.is_sent = 1";
        $result = Database::fetch($sql, [$templateId]);
        $stats['emails_sent'] = $result['count'];
        
        // 平均開啟率
        $sql = "SELECT 
                    COUNT(DISTINCT tpl.email) as opened,
                    COUNT(DISTINCT te.email) as total
                FROM target_emails te 
                JOIN projects p ON te.project_id = p.id 
                LEFT JOIN track_pixel_logs tpl ON te.project_id = tpl.project_id AND te.email = tpl.email
                WHERE p.email_template_id = ? AND te.is_sent = 1";
        $result = Database::fetch($sql, [$templateId]);
        
        if ($result['total'] > 0) {
            $stats['open_rate'] = round(($result['opened'] / $result['total']) * 100, 2);
        } else {
            $stats['open_rate'] = 0;
        }
        
        // 最後使用時間
        $sql = "SELECT MAX(p.created_at) as last_used 
                FROM projects p 
                WHERE p.email_template_id = ?";
        $result = Database::fetch($sql, [$templateId]);
        $stats['last_used'] = $result['last_used'];
        
        return $stats;
    }
    
    /**
     * 複製模板
     */
    public function cloneTemplate($templateId, $newName, $createdBy) {
        $template = $this->find($templateId);
        if (!$template) {
            throw new Exception('原始模板不存在');
        }
        
        $newTemplate = [
            'name' => $newName,
            'description' => $template['description'] . ' (複製)',
            'subject' => $template['subject'],
            'html_content' => $template['html_content'],
            'created_by' => $createdBy,
            'is_active' => 1
        ];
        
        return $this->create($newTemplate);
    }
    
    /**
     * 停用模板
     */
    public function deactivate($templateId) {
        return $this->update($templateId, ['is_active' => 0]);
    }
    
    /**
     * 啟用模板
     */
    public function activate($templateId) {
        return $this->update($templateId, ['is_active' => 1]);
    }
}