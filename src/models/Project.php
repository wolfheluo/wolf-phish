<?php

require_once 'BaseModel.php';

class Project extends BaseModel {
    protected $table = 'projects';
    protected $fillable = [
        'project_code', 'project_name', 'description', 'test_username', 'test_password',
        'email_template_id', 'phishing_site_id', 'sender_name', 'sender_email', 'subject',
        'track_pixel_url', 'track_zip_url', 'phish_url', 'start_date', 'end_date',
        'send_start_time', 'send_end_time', 'status', 'created_by'
    ];
    
    /**
     * 根據專案代號查找專案
     */
    public function findByCode($projectCode) {
        return $this->findWhere(['project_code' => $projectCode]);
    }
    
    /**
     * 獲取用戶的專案列表
     */
    public function getUserProjects($userId, $role = 'user') {
        if ($role === 'admin') {
            // 管理員可以看到所有專案
            $sql = "SELECT p.*, u.full_name as creator_name, et.name as template_name 
                    FROM projects p 
                    LEFT JOIN users u ON p.created_by = u.id 
                    LEFT JOIN email_templates et ON p.email_template_id = et.id 
                    ORDER BY p.created_at DESC";
            return Database::fetchAll($sql);
        } else {
            // 普通用戶只能看到自己創建的專案
            $sql = "SELECT p.*, u.full_name as creator_name, et.name as template_name 
                    FROM projects p 
                    LEFT JOIN users u ON p.created_by = u.id 
                    LEFT JOIN email_templates et ON p.email_template_id = et.id 
                    WHERE p.created_by = ? 
                    ORDER BY p.created_at DESC";
            return Database::fetchAll($sql, [$userId]);
        }
    }
    
    /**
     * 創建專案
     */
    public function createProject($data, $targetEmails = []) {
        try {
            Database::getConnection()->beginTransaction();
            
            // 創建專案
            $project = $this->create($data);
            
            // 添加目標郵件
            if (!empty($targetEmails) && $project) {
                $this->addTargetEmails($project['id'], $targetEmails);
            }
            
            Database::getConnection()->commit();
            return $project;
            
        } catch (Exception $e) {
            Database::getConnection()->rollBack();
            throw $e;
        }
    }
    
    /**
     * 添加目標郵件
     */
    public function addTargetEmails($projectId, $emails) {
        $sql = "INSERT INTO target_emails (project_id, email, name, department) VALUES (?, ?, ?, ?)";
        $stmt = Database::getConnection()->prepare($sql);
        
        foreach ($emails as $email) {
            $stmt->execute([
                $projectId,
                $email['email'],
                $email['name'] ?? '',
                $email['department'] ?? ''
            ]);
        }
    }
    
    /**
     * 獲取專案的目標郵件列表
     */
    public function getTargetEmails($projectId) {
        $sql = "SELECT * FROM target_emails WHERE project_id = ? ORDER BY created_at ASC";
        return Database::fetchAll($sql, [$projectId]);
    }
    
    /**
     * 更新專案狀態
     */
    public function updateStatus($projectId, $status) {
        return $this->update($projectId, ['status' => $status]);
    }
    
    /**
     * 獲取進行中的專案
     */
    public function getActiveProjects() {
        $sql = "SELECT * FROM projects WHERE status = 'active' AND start_date <= CURDATE() AND end_date >= CURDATE()";
        return Database::fetchAll($sql);
    }
    
    /**
     * 獲取專案統計信息
     */
    public function getProjectStats($projectId) {
        $stats = [];
        
        // 基本統計
        $sql = "SELECT 
                    COUNT(*) as total_targets,
                    SUM(CASE WHEN is_sent = 1 THEN 1 ELSE 0 END) as sent_count,
                    SUM(CASE WHEN send_status = 'failed' THEN 1 ELSE 0 END) as failed_count
                FROM target_emails WHERE project_id = ?";
        $result = Database::fetch($sql, [$projectId]);
        $stats = array_merge($stats, $result);
        
        // 郵件開啟統計
        $sql = "SELECT COUNT(DISTINCT email) as opened_count FROM track_pixel_logs WHERE project_id = ?";
        $result = Database::fetch($sql, [$projectId]);
        $stats['opened_count'] = $result['opened_count'];
        
        // URL點擊統計
        $sql = "SELECT COUNT(DISTINCT email) as clicked_count FROM track_url_logs WHERE project_id = ?";
        $result = Database::fetch($sql, [$projectId]);
        $stats['clicked_count'] = $result['clicked_count'];
        
        // 附件下載統計
        $sql = "SELECT COUNT(DISTINCT email) as downloaded_count FROM track_zip_logs WHERE project_id = ?";
        $result = Database::fetch($sql, [$projectId]);
        $stats['downloaded_count'] = $result['downloaded_count'];
        
        // 數據提交統計
        $sql = "SELECT COUNT(DISTINCT email) as submitted_count FROM track_data_logs WHERE project_id = ?";
        $result = Database::fetch($sql, [$projectId]);
        $stats['submitted_count'] = $result['submitted_count'];
        
        // 計算百分比
        if ($stats['sent_count'] > 0) {
            $stats['open_rate'] = round(($stats['opened_count'] / $stats['sent_count']) * 100, 2);
            $stats['click_rate'] = round(($stats['clicked_count'] / $stats['sent_count']) * 100, 2);
            $stats['download_rate'] = round(($stats['downloaded_count'] / $stats['sent_count']) * 100, 2);
            $stats['submit_rate'] = round(($stats['submitted_count'] / $stats['sent_count']) * 100, 2);
        } else {
            $stats['open_rate'] = 0;
            $stats['click_rate'] = 0;
            $stats['download_rate'] = 0;
            $stats['submit_rate'] = 0;
        }
        
        return $stats;
    }
    
    /**
     * 獲取專案詳細報告
     */
    public function getProjectReport($projectId) {
        $report = [];
        
        // 專案基本信息
        $sql = "SELECT p.*, et.name as template_name, ps.name as phish_site_name, u.full_name as creator_name
                FROM projects p 
                LEFT JOIN email_templates et ON p.email_template_id = et.id
                LEFT JOIN phishing_sites ps ON p.phishing_site_id = ps.id
                LEFT JOIN users u ON p.created_by = u.id
                WHERE p.id = ?";
        $report['project'] = Database::fetch($sql, [$projectId]);
        
        // 統計數據
        $report['stats'] = $this->getProjectStats($projectId);
        
        // 目標用戶詳細信息
        $sql = "SELECT 
                    te.*,
                    (SELECT COUNT(*) FROM track_pixel_logs tpl WHERE tpl.project_id = te.project_id AND tpl.email = te.email) as opened,
                    (SELECT COUNT(*) FROM track_url_logs tul WHERE tul.project_id = te.project_id AND tul.email = te.email) as clicked,
                    (SELECT COUNT(*) FROM track_zip_logs tzl WHERE tzl.project_id = te.project_id AND tzl.email = te.email) as downloaded,
                    (SELECT COUNT(*) FROM track_data_logs tdl WHERE tdl.project_id = te.project_id AND tdl.email = te.email) as submitted
                FROM target_emails te 
                WHERE te.project_id = ?
                ORDER BY te.email";
        $report['targets'] = Database::fetchAll($sql, [$projectId]);
        
        // 時間序列數據（過去30天的活動）
        $sql = "SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as opens
                FROM track_pixel_logs 
                WHERE project_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date";
        $report['timeline_opens'] = Database::fetchAll($sql, [$projectId]);
        
        $sql = "SELECT 
                    DATE(clicked_at) as date,
                    COUNT(*) as clicks
                FROM track_url_logs 
                WHERE project_id = ? AND clicked_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(clicked_at)
                ORDER BY date";
        $report['timeline_clicks'] = Database::fetchAll($sql, [$projectId]);
        
        return $report;
    }
    
    /**
     * 檢查專案代號是否存在
     */
    public function codeExists($code, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM projects WHERE project_code = ?";
        $params = [$code];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = Database::fetch($sql, $params);
        return $result['count'] > 0;
    }
    
    /**
     * 獲取系統統計
     */
    public function getSystemStats() {
        $stats = [];
        
        // 專案總數
        $stats['total_projects'] = $this->count();
        
        // 進行中的專案
        $stats['active_projects'] = $this->count(['status' => 'active']);
        
        // 已完成的專案
        $stats['completed_projects'] = $this->count(['status' => 'completed']);
        
        // 總目標郵件數
        $sql = "SELECT COUNT(*) as count FROM target_emails";
        $result = Database::fetch($sql);
        $stats['total_targets'] = $result['count'];
        
        // 最近活動（過去7天的專案）
        $sql = "SELECT COUNT(*) as count FROM projects WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $result = Database::fetch($sql);
        $stats['recent_projects'] = $result['count'];
        
        return $stats;
    }
}