<?php

require_once 'BaseController.php';
require_once dirname(__DIR__) . '/models/Project.php';
require_once dirname(__DIR__) . '/models/EmailTemplate.php';
require_once dirname(__DIR__) . '/models/UserModel.php';

class DashboardController extends BaseController {
    private $projectModel;
    private $emailTemplateModel;
    private $userModel;
    
    public function __construct() {
        parent::__construct();
        $this->projectModel = new Project();
        $this->emailTemplateModel = new EmailTemplate();
        $this->userModel = new UserModel();
    }
    
    /**
     * 顯示儀表板
     */
    public function index() {
        try {
            $this->requireAuth();
            
            $userId = Session::getUserId();
            $userRole = Session::getUserRole();
            
            // 獲取系統統計
            $stats = $this->getSystemStats($userId, $userRole);
            
            // 獲取最近專案
            $recentProjects = $this->getRecentProjects($userId, $userRole);
            
            $this->render('dashboard/index', [
                'title' => '儀表板',
                'stats' => $stats,
                'recent_projects' => $recentProjects,
                'user_role' => $userRole
            ]);
            
        } catch (Exception $e) {
            $this->log('ERROR', 'Dashboard error', ['error' => $e->getMessage()]);
            $this->error($e->getMessage());
        }
    }
    
    /**
     * 模板管理頁面
     */
    public function templates() {
        try {
            $this->requireAuth();
            
            $this->render('templates/index', [
                'title' => '範本管理'
            ]);
            
        } catch (Exception $e) {
            $this->log('ERROR', 'Templates page error', ['error' => $e->getMessage()]);
            $this->error($e->getMessage());
        }
    }
    
    /**
     * 創建專案頁面
     */
    public function createProjectPage() {
        try {
            $this->requireAuth();
            
            $this->render('projects/create', [
                'title' => '開始演練'
            ]);
            
        } catch (Exception $e) {
            $this->log('ERROR', 'Create project page error', ['error' => $e->getMessage()]);
            $this->error($e->getMessage());
        }
    }
    
    /**
     * 專案狀態頁面
     */
    public function projectStatus() {
        try {
            $this->requireAuth();
            
            $this->render('projects/status', [
                'title' => '演練狀態'
            ]);
            
        } catch (Exception $e) {
            $this->log('ERROR', 'Project status page error', ['error' => $e->getMessage()]);
            $this->error($e->getMessage());
        }
    }
    
    /**
     * 報告頁面
     */
    public function reports() {
        try {
            $this->requireAuth();
            
            $this->render('reports/index', [
                'title' => '結果查詢'
            ]);
            
        } catch (Exception $e) {
            $this->log('ERROR', 'Reports page error', ['error' => $e->getMessage()]);
            $this->error($e->getMessage());
        }
    }
    
    /**
     * 報告詳情頁面
     */
    public function reportDetails($projectId) {
        try {
            $this->requireAuth();
            
            $project = $this->projectModel->find($projectId);
            if (!$project) {
                $this->redirect('/reports');
                return;
            }
            
            $this->render('reports/details', [
                'title' => '報告詳情 - ' . $project['project_name'],
                'project' => $project
            ]);
            
        } catch (Exception $e) {
            $this->log('ERROR', 'Report details error', ['error' => $e->getMessage()]);
            $this->error($e->getMessage());
        }
    }
    
    /**
     * 專案詳情頁面
     */
    public function projectDetails($projectId) {
        try {
            $this->requireAuth();
            
            $project = $this->projectModel->find($projectId);
            if (!$project) {
                $this->redirect('/projects');
                return;
            }
            
            $this->render('projects/details', [
                'title' => '專案詳情 - ' . $project['project_name'],
                'project' => $project
            ]);
            
        } catch (Exception $e) {
            $this->log('ERROR', 'Project details error', ['error' => $e->getMessage()]);
            $this->error($e->getMessage());
        }
    }
    
    /**
     * API: 獲取儀表板數據
     */
    public function getDashboardData() {
        try {
            $this->requireAuth();
            
            $userId = Session::getUserId();
            $userRole = Session::getUserRole();
            
            $data = [
                'stats' => $this->getSystemStats($userId, $userRole),
                'recent_projects' => $this->getRecentProjects($userId, $userRole),
                'project_timeline' => $this->getProjectTimeline($userId, $userRole),
                'template_usage' => $this->getTemplateUsage($userId, $userRole)
            ];
            
            $this->success($data);
            
        } catch (Exception $e) {
            $this->log('ERROR', 'Get dashboard data error', ['error' => $e->getMessage()]);
            $this->error($e->getMessage());
        }
    }
    
    /**
     * 獲取系統統計
     */
    private function getSystemStats($userId, $userRole) {
        if ($userRole === 'admin') {
            // 管理員查看全系統統計
            $stats = $this->projectModel->getSystemStats();
            
            // 添加用戶統計
            $stats['total_users'] = $this->userModel->count(['is_active' => 1]);
            $stats['admin_users'] = $this->userModel->count(['role' => 'admin', 'is_active' => 1]);
            
            // 郵件模板統計
            $stats['total_templates'] = $this->emailTemplateModel->count(['is_active' => 1]);
            
        } else {
            // 普通用戶查看個人統計
            $stats = $this->userModel->getUserStats($userId);
            
            // 個人專案統計
            $userProjects = $this->projectModel->all(['created_by' => $userId]);
            $stats['total_projects'] = count($userProjects);
            $stats['active_projects'] = count(array_filter($userProjects, function($p) { return $p['status'] === 'active'; }));
            $stats['completed_projects'] = count(array_filter($userProjects, function($p) { return $p['status'] === 'completed'; }));
            
            // 計算總目標數
            $totalTargets = 0;
            foreach ($userProjects as $project) {
                $targets = $this->projectModel->getTargetEmails($project['id']);
                $totalTargets += count($targets);
            }
            $stats['total_targets'] = $totalTargets;
        }
        
        return $stats;
    }
    
    /**
     * 獲取最近專案
     */
    private function getRecentProjects($userId, $userRole, $limit = 5) {
        if ($userRole === 'admin') {
            return $this->projectModel->all([], 'created_at DESC', $limit);
        } else {
            return $this->projectModel->all(['created_by' => $userId], 'created_at DESC', $limit);
        }
    }
    
    /**
     * 獲取專案時間線數據
     */
    private function getProjectTimeline($userId, $userRole) {
        $sql = "SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as count
                FROM projects 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        
        $params = [];
        if ($userRole !== 'admin') {
            $sql .= " AND created_by = ?";
            $params[] = $userId;
        }
        
        $sql .= " GROUP BY DATE(created_at) ORDER BY date";
        
        return Database::fetchAll($sql, $params);
    }
    
    /**
     * 獲取模板使用統計
     */
    private function getTemplateUsage($userId, $userRole) {
        $sql = "SELECT 
                    et.name as template_name,
                    COUNT(p.id) as usage_count
                FROM email_templates et
                LEFT JOIN projects p ON et.id = p.email_template_id";
        
        $params = [];
        if ($userRole !== 'admin') {
            $sql .= " AND p.created_by = ?";
            $params[] = $userId;
        }
        
        $sql .= " WHERE et.is_active = 1
                  GROUP BY et.id, et.name
                  ORDER BY usage_count DESC
                  LIMIT 10";
        
        return Database::fetchAll($sql, $params);
    }
    
    /**
     * 系統健康檢查
     */
    public function healthCheck() {
        try {
            $this->requireAdmin();
            
            $health = [
                'database' => $this->checkDatabaseConnection(),
                'disk_space' => $this->checkDiskSpace(),
                'uploads_writable' => $this->checkUploadsDirectory(),
                'mail_config' => $this->checkMailConfiguration(),
                'recent_errors' => $this->getRecentErrors()
            ];
            
            $health['status'] = $this->calculateOverallHealth($health);
            
            $this->success($health);
            
        } catch (Exception $e) {
            $this->log('ERROR', 'Health check error', ['error' => $e->getMessage()]);
            $this->error($e->getMessage());
        }
    }
    
    /**
     * 檢查資料庫連接
     */
    private function checkDatabaseConnection() {
        try {
            Database::query("SELECT 1");
            return ['status' => 'ok', 'message' => '資料庫連接正常'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => '資料庫連接失敗: ' . $e->getMessage()];
        }
    }
    
    /**
     * 檢查磁碟空間
     */
    private function checkDiskSpace() {
        $freeSpace = disk_free_space(ROOT_PATH);
        $totalSpace = disk_total_space(ROOT_PATH);
        $usedPercent = (($totalSpace - $freeSpace) / $totalSpace) * 100;
        
        $status = $usedPercent > 90 ? 'warning' : 'ok';
        
        return [
            'status' => $status,
            'used_percent' => round($usedPercent, 2),
            'free_space' => $this->formatBytes($freeSpace),
            'total_space' => $this->formatBytes($totalSpace)
        ];
    }
    
    /**
     * 檢查上傳目錄
     */
    private function checkUploadsDirectory() {
        $writable = is_writable(UPLOADS_PATH);
        return [
            'status' => $writable ? 'ok' : 'error',
            'message' => $writable ? '上傳目錄可寫' : '上傳目錄不可寫',
            'path' => UPLOADS_PATH
        ];
    }
    
    /**
     * 檢查郵件配置
     */
    private function checkMailConfiguration() {
        // 檢查sendmail是否可用
        $sendmailPath = trim(shell_exec('which sendmail 2>/dev/null'));
        $sendmailAvailable = !empty($sendmailPath) && file_exists($sendmailPath);
        
        return [
            'status' => $sendmailAvailable ? 'ok' : 'warning',
            'message' => $sendmailAvailable ? 'Sendmail 可用' : 'Sendmail 未安裝或不可用',
            'sendmail_path' => $sendmailPath
        ];
    }
    
    /**
     * 獲取最近錯誤
     */
    private function getRecentErrors() {
        $sql = "SELECT level, message, created_at 
                FROM system_logs 
                WHERE level IN ('ERROR', 'WARNING') 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ORDER BY created_at DESC 
                LIMIT 10";
        
        $errors = Database::fetchAll($sql);
        
        return [
            'count' => count($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * 計算整體健康狀態
     */
    private function calculateOverallHealth($health) {
        $errorCount = 0;
        $warningCount = 0;
        
        foreach ($health as $key => $check) {
            if (is_array($check) && isset($check['status'])) {
                if ($check['status'] === 'error') {
                    $errorCount++;
                } elseif ($check['status'] === 'warning') {
                    $warningCount++;
                }
            }
        }
        
        if ($errorCount > 0) {
            return 'error';
        } elseif ($warningCount > 0) {
            return 'warning';
        }
        
        return 'ok';
    }
    
    /**
     * 格式化文件大小
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}