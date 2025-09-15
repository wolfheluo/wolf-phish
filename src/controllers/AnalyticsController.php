<?php

require_once 'BaseController.php';
require_once dirname(__DIR__) . '/models/Project.php';

class AnalyticsController extends BaseController {
    private $projectModel;
    
    public function __construct() {
        parent::__construct();
        $this->projectModel = new Project();
    }
    
    /**
     * 獲取專案分析數據
     */
    public function getProjectAnalytics() {
        try {
            $this->requireAuth();
            
            $projectId = $this->getParam('project_id');
            
            if (!$projectId) {
                $this->error('Project ID required');
            }
            
            // 獲取基本統計
            $basicStats = $this->getBasicStats($projectId);
            
            // 獲取時間線數據
            $timeline = $this->getTimelineData($projectId);
            
            // 獲取設備統計
            $deviceStats = $this->getDeviceStats($projectId);
            
            // 獲取地理位置統計
            $locationStats = $this->getLocationStats($projectId);
            
            // 獲取行為漏斗
            $funnel = $this->getBehaviorFunnel($projectId);
            
            // 獲取風險評分
            $riskScore = $this->calculateRiskScore($projectId);
            
            $this->success([
                'basic_stats' => $basicStats,
                'timeline' => $timeline,
                'device_stats' => $deviceStats,
                'location_stats' => $locationStats,
                'funnel' => $funnel,
                'risk_score' => $riskScore
            ]);
            
        } catch (Exception $e) {
            $this->log('ERROR', 'Get project analytics error', ['error' => $e->getMessage()]);
            $this->error($e->getMessage());
        }
    }
    
    /**
     * 獲取基本統計數據
     */
    private function getBasicStats($projectId) {
        $stats = [];
        
        // 郵件發送統計
        $sql = "SELECT COUNT(*) as total_sent FROM sent_emails WHERE project_id = ? AND status = 'sent'";
        $stats['emails_sent'] = Database::fetchValue($sql, [$projectId]) ?: 0;
        
        // 郵件開啟統計
        $sql = "SELECT COUNT(DISTINCT email) as unique_opens, COUNT(*) as total_opens 
                FROM track_pixel_logs WHERE project_id = ?";
        $result = Database::fetch($sql, [$projectId]);
        $stats['unique_opens'] = (int)($result['unique_opens'] ?? 0);
        $stats['total_opens'] = (int)($result['total_opens'] ?? 0);
        
        // URL點擊統計
        $sql = "SELECT COUNT(DISTINCT email) as unique_clicks, COUNT(*) as total_clicks 
                FROM track_url_logs WHERE project_id = ?";
        $result = Database::fetch($sql, [$projectId]);
        $stats['unique_clicks'] = (int)($result['unique_clicks'] ?? 0);
        $stats['total_clicks'] = (int)($result['total_clicks'] ?? 0);
        
        // 文件下載統計
        $sql = "SELECT COUNT(DISTINCT email) as unique_downloads, COUNT(*) as total_downloads 
                FROM track_zip_logs WHERE project_id = ?";
        $result = Database::fetch($sql, [$projectId]);
        $stats['unique_downloads'] = (int)($result['unique_downloads'] ?? 0);
        $stats['total_downloads'] = (int)($result['total_downloads'] ?? 0);
        
        // 數據提交統計
        $sql = "SELECT COUNT(DISTINCT email) as unique_submissions, COUNT(*) as total_submissions 
                FROM track_data_logs WHERE project_id = ?";
        $result = Database::fetch($sql, [$projectId]);
        $stats['unique_submissions'] = (int)($result['unique_submissions'] ?? 0);
        $stats['total_submissions'] = (int)($result['total_submissions'] ?? 0);
        
        // 憑證捕獲統計
        $sql = "SELECT COUNT(DISTINCT email) as unique_credentials, COUNT(*) as total_credentials 
                FROM track_credentials WHERE project_id = ?";
        $result = Database::fetch($sql, [$projectId]);
        $stats['unique_credentials'] = (int)($result['unique_credentials'] ?? 0);
        $stats['total_credentials'] = (int)($result['total_credentials'] ?? 0);
        
        // 計算轉換率
        $stats['open_rate'] = $stats['emails_sent'] > 0 ? 
            round(($stats['unique_opens'] / $stats['emails_sent']) * 100, 2) : 0;
        
        $stats['click_rate'] = $stats['unique_opens'] > 0 ? 
            round(($stats['unique_clicks'] / $stats['unique_opens']) * 100, 2) : 0;
            
        $stats['download_rate'] = $stats['unique_clicks'] > 0 ? 
            round(($stats['unique_downloads'] / $stats['unique_clicks']) * 100, 2) : 0;
            
        $stats['submission_rate'] = $stats['unique_downloads'] > 0 ? 
            round(($stats['unique_submissions'] / $stats['unique_downloads']) * 100, 2) : 0;
        
        return $stats;
    }
    
    /**
     * 獲取時間線數據
     */
    private function getTimelineData($projectId) {
        $timeline = [];
        
        // 按小時統計活動
        $sql = "SELECT 
                    DATE(opened_at) as date,
                    HOUR(opened_at) as hour,
                    COUNT(*) as opens
                FROM track_pixel_logs 
                WHERE project_id = ? 
                GROUP BY DATE(opened_at), HOUR(opened_at)
                ORDER BY date, hour";
        $opens = Database::fetchAll($sql, [$projectId]);
        
        $sql = "SELECT 
                    DATE(clicked_at) as date,
                    HOUR(clicked_at) as hour,
                    COUNT(*) as clicks
                FROM track_url_logs 
                WHERE project_id = ? 
                GROUP BY DATE(clicked_at), HOUR(clicked_at)
                ORDER BY date, hour";
        $clicks = Database::fetchAll($sql, [$projectId]);
        
        $sql = "SELECT 
                    DATE(downloaded_at) as date,
                    HOUR(downloaded_at) as hour,
                    COUNT(*) as downloads
                FROM track_zip_logs 
                WHERE project_id = ? 
                GROUP BY DATE(downloaded_at), HOUR(downloaded_at)
                ORDER BY date, hour";
        $downloads = Database::fetchAll($sql, [$projectId]);
        
        // 合併時間線數據
        $timelineMap = [];
        
        foreach ($opens as $item) {
            $key = $item['date'] . '_' . $item['hour'];
            $timelineMap[$key]['date'] = $item['date'];
            $timelineMap[$key]['hour'] = $item['hour'];
            $timelineMap[$key]['opens'] = (int)$item['opens'];
        }
        
        foreach ($clicks as $item) {
            $key = $item['date'] . '_' . $item['hour'];
            if (!isset($timelineMap[$key])) {
                $timelineMap[$key]['date'] = $item['date'];
                $timelineMap[$key]['hour'] = $item['hour'];
            }
            $timelineMap[$key]['clicks'] = (int)$item['clicks'];
        }
        
        foreach ($downloads as $item) {
            $key = $item['date'] . '_' . $item['hour'];
            if (!isset($timelineMap[$key])) {
                $timelineMap[$key]['date'] = $item['date'];
                $timelineMap[$key]['hour'] = $item['hour'];
            }
            $timelineMap[$key]['downloads'] = (int)$item['downloads'];
        }
        
        // 轉換為數組並填充缺失值
        foreach ($timelineMap as $item) {
            $timeline[] = [
                'date' => $item['date'],
                'hour' => $item['hour'],
                'opens' => $item['opens'] ?? 0,
                'clicks' => $item['clicks'] ?? 0,
                'downloads' => $item['downloads'] ?? 0
            ];
        }
        
        return $timeline;
    }
    
    /**
     * 獲取設備統計
     */
    private function getDeviceStats($projectId) {
        $sql = "SELECT 
                    CASE 
                        WHEN user_agent LIKE '%Mobile%' OR user_agent LIKE '%Android%' OR user_agent LIKE '%iPhone%' THEN 'Mobile'
                        WHEN user_agent LIKE '%Tablet%' OR user_agent LIKE '%iPad%' THEN 'Tablet'  
                        ELSE 'Desktop'
                    END as device_type,
                    COUNT(DISTINCT email) as unique_users,
                    COUNT(*) as total_actions
                FROM track_pixel_logs 
                WHERE project_id = ?
                GROUP BY device_type
                ORDER BY total_actions DESC";
                
        $devices = Database::fetchAll($sql, [$projectId]);
        
        // 瀏覽器統計
        $sql = "SELECT 
                    CASE 
                        WHEN user_agent LIKE '%Chrome%' THEN 'Chrome'
                        WHEN user_agent LIKE '%Firefox%' THEN 'Firefox'
                        WHEN user_agent LIKE '%Safari%' AND user_agent NOT LIKE '%Chrome%' THEN 'Safari'
                        WHEN user_agent LIKE '%Edge%' THEN 'Edge'
                        WHEN user_agent LIKE '%Opera%' THEN 'Opera'
                        ELSE 'Other'
                    END as browser,
                    COUNT(DISTINCT email) as unique_users,
                    COUNT(*) as total_actions
                FROM track_pixel_logs 
                WHERE project_id = ?
                GROUP BY browser
                ORDER BY total_actions DESC";
                
        $browsers = Database::fetchAll($sql, [$projectId]);
        
        return [
            'devices' => $devices,
            'browsers' => $browsers
        ];
    }
    
    /**
     * 獲取地理位置統計
     */
    private function getLocationStats($projectId) {
        // 簡化版本的地理統計，基於IP地址
        $sql = "SELECT 
                    ip_address,
                    COUNT(DISTINCT email) as unique_users,
                    COUNT(*) as total_actions
                FROM track_pixel_logs 
                WHERE project_id = ?
                GROUP BY ip_address
                ORDER BY total_actions DESC
                LIMIT 20";
                
        $ips = Database::fetchAll($sql, [$projectId]);
        
        // 在實際應用中，這裡應該使用GeoIP數據庫來解析IP地址
        $locations = [];
        foreach ($ips as $ip) {
            $locations[] = [
                'ip' => $ip['ip_address'],
                'country' => 'Unknown', // 需要GeoIP解析
                'city' => 'Unknown',     // 需要GeoIP解析
                'unique_users' => (int)$ip['unique_users'],
                'total_actions' => (int)$ip['total_actions']
            ];
        }
        
        return $locations;
    }
    
    /**
     * 獲取行為漏斗數據
     */
    private function getBehaviorFunnel($projectId) {
        // 獲取各階段的唯一用戶數
        $sql = "SELECT COUNT(*) as emails_sent FROM sent_emails WHERE project_id = ? AND status = 'sent'";
        $emailsSent = Database::fetchValue($sql, [$projectId]) ?: 0;
        
        $sql = "SELECT COUNT(DISTINCT email) as opened FROM track_pixel_logs WHERE project_id = ?";
        $opened = Database::fetchValue($sql, [$projectId]) ?: 0;
        
        $sql = "SELECT COUNT(DISTINCT email) as clicked FROM track_url_logs WHERE project_id = ?";
        $clicked = Database::fetchValue($sql, [$projectId]) ?: 0;
        
        $sql = "SELECT COUNT(DISTINCT email) as downloaded FROM track_zip_logs WHERE project_id = ?";
        $downloaded = Database::fetchValue($sql, [$projectId]) ?: 0;
        
        $sql = "SELECT COUNT(DISTINCT email) as submitted FROM track_data_logs WHERE project_id = ?";
        $submitted = Database::fetchValue($sql, [$projectId]) ?: 0;
        
        $sql = "SELECT COUNT(DISTINCT email) as compromised FROM track_credentials WHERE project_id = ?";
        $compromised = Database::fetchValue($sql, [$projectId]) ?: 0;
        
        return [
            [
                'stage' => 'Emails Sent',
                'count' => (int)$emailsSent,
                'percentage' => 100
            ],
            [
                'stage' => 'Email Opened',
                'count' => (int)$opened,
                'percentage' => $emailsSent > 0 ? round(($opened / $emailsSent) * 100, 2) : 0
            ],
            [
                'stage' => 'Link Clicked',
                'count' => (int)$clicked,
                'percentage' => $emailsSent > 0 ? round(($clicked / $emailsSent) * 100, 2) : 0
            ],
            [
                'stage' => 'File Downloaded',
                'count' => (int)$downloaded,
                'percentage' => $emailsSent > 0 ? round(($downloaded / $emailsSent) * 100, 2) : 0
            ],
            [
                'stage' => 'Data Submitted',
                'count' => (int)$submitted,
                'percentage' => $emailsSent > 0 ? round(($submitted / $emailsSent) * 100, 2) : 0
            ],
            [
                'stage' => 'Credentials Captured',
                'count' => (int)$compromised,
                'percentage' => $emailsSent > 0 ? round(($compromised / $emailsSent) * 100, 2) : 0
            ]
        ];
    }
    
    /**
     * 計算風險評分
     */
    private function calculateRiskScore($projectId) {
        $stats = $this->getBasicStats($projectId);
        
        // 基於各種指標計算風險評分（0-100）
        $riskScore = 0;
        
        // 郵件開啟率風險
        if ($stats['open_rate'] > 50) $riskScore += 25;
        elseif ($stats['open_rate'] > 30) $riskScore += 15;
        elseif ($stats['open_rate'] > 10) $riskScore += 8;
        
        // 點擊率風險
        if ($stats['click_rate'] > 30) $riskScore += 25;
        elseif ($stats['click_rate'] > 15) $riskScore += 15;
        elseif ($stats['click_rate'] > 5) $riskScore += 8;
        
        // 下載率風險
        if ($stats['download_rate'] > 20) $riskScore += 25;
        elseif ($stats['download_rate'] > 10) $riskScore += 15;
        elseif ($stats['download_rate'] > 3) $riskScore += 8;
        
        // 數據提交率風險
        if ($stats['submission_rate'] > 15) $riskScore += 25;
        elseif ($stats['submission_rate'] > 8) $riskScore += 15;
        elseif ($stats['submission_rate'] > 2) $riskScore += 8;
        
        $riskLevel = 'Low';
        if ($riskScore >= 70) $riskLevel = 'Critical';
        elseif ($riskScore >= 50) $riskLevel = 'High';
        elseif ($riskScore >= 30) $riskLevel = 'Medium';
        
        return [
            'score' => min($riskScore, 100),
            'level' => $riskLevel,
            'recommendations' => $this->generateRecommendations($riskScore, $stats)
        ];
    }
    
    /**
     * 生成安全建議
     */
    private function generateRecommendations($riskScore, $stats) {
        $recommendations = [];
        
        if ($stats['open_rate'] > 30) {
            $recommendations[] = '郵件開啟率偏高，建議加強郵件安全意識培訓';
        }
        
        if ($stats['click_rate'] > 15) {
            $recommendations[] = '鏈接點擊率偏高，建議實施鏈接過濾和安全提醒';
        }
        
        if ($stats['download_rate'] > 10) {
            $recommendations[] = '文件下載率偏高，建議禁用外部附件下載';
        }
        
        if ($stats['submission_rate'] > 5) {
            $recommendations[] = '數據提交率偏高，建議實施多因素驗證';
        }
        
        if ($riskScore > 50) {
            $recommendations[] = '整體風險偏高，建議立即進行全面的安全培訓';
        }
        
        if (empty($recommendations)) {
            $recommendations[] = '安全意識良好，建議保持定期培訓';
        }
        
        return $recommendations;
    }
    
    /**
     * 生成詳細報告
     */
    public function generateReport() {
        try {
            $this->requireAuth();
            
            $projectId = $this->getParam('project_id');
            $format = $this->getParam('format', 'json');
            
            if (!$projectId) {
                $this->error('Project ID required');
            }
            
            // 獲取專案信息
            $project = $this->projectModel->find($projectId);
            if (!$project) {
                $this->error('Project not found');
            }
            
            // 獲取完整分析數據
            $analytics = [
                'project' => $project,
                'generated_at' => date('Y-m-d H:i:s'),
                'basic_stats' => $this->getBasicStats($projectId),
                'timeline' => $this->getTimelineData($projectId),
                'device_stats' => $this->getDeviceStats($projectId),
                'location_stats' => $this->getLocationStats($projectId),
                'funnel' => $this->getBehaviorFunnel($projectId),
                'risk_score' => $this->calculateRiskScore($projectId),
                'detailed_logs' => $this->getDetailedLogs($projectId)
            ];
            
            if ($format === 'pdf') {
                $this->generatePDFReport($analytics);
            } else {
                $this->success($analytics);
            }
            
        } catch (Exception $e) {
            $this->log('ERROR', 'Generate report error', ['error' => $e->getMessage()]);
            $this->error($e->getMessage());
        }
    }
    
    /**
     * 獲取詳細日誌
     */
    private function getDetailedLogs($projectId) {
        $logs = [];
        
        // 获取前100條記錄
        $sql = "SELECT 'pixel' as type, email, ip_address, user_agent, opened_at as timestamp
                FROM track_pixel_logs WHERE project_id = ?
                UNION ALL
                SELECT 'url' as type, email, ip_address, user_agent, clicked_at as timestamp
                FROM track_url_logs WHERE project_id = ?
                UNION ALL
                SELECT 'zip' as type, email, ip_address, user_agent, downloaded_at as timestamp
                FROM track_zip_logs WHERE project_id = ?
                ORDER BY timestamp DESC
                LIMIT 100";
                
        return Database::fetchAll($sql, [$projectId, $projectId, $projectId]);
    }
    
    /**
     * 生成PDF報告（簡化版本）
     */
    private function generatePDFReport($analytics) {
        // 在實際應用中，這裡應該使用TCPDF或類似的PDF生成庫
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="phishing_report_' . $analytics['project']['id'] . '.pdf"');
        
        // 簡化：返回HTML版本
        $html = $this->generateHTMLReport($analytics);
        echo $html;
    }
    
    /**
     * 生成HTML報告
     */
    private function generateHTMLReport($analytics) {
        $html = "<!DOCTYPE html>
        <html>
        <head>
            <title>Phishing Campaign Report</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; }
                .header { background: #f8f9fa; padding: 20px; margin-bottom: 30px; }
                .stats { display: flex; gap: 20px; margin-bottom: 30px; }
                .stat-box { background: #fff; border: 1px solid #ddd; padding: 15px; flex: 1; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background: #f8f9fa; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>釣魚測試報告</h1>
                <p>專案: {$analytics['project']['name']}</p>
                <p>生成時間: {$analytics['generated_at']}</p>
            </div>
            
            <h2>基本統計</h2>
            <div class='stats'>
                <div class='stat-box'>
                    <h3>郵件開啟</h3>
                    <p>{$analytics['basic_stats']['unique_opens']} / {$analytics['basic_stats']['emails_sent']}</p>
                    <p>開啟率: {$analytics['basic_stats']['open_rate']}%</p>
                </div>
                <div class='stat-box'>
                    <h3>鏈接點擊</h3>
                    <p>{$analytics['basic_stats']['unique_clicks']}</p>
                    <p>點擊率: {$analytics['basic_stats']['click_rate']}%</p>
                </div>
                <div class='stat-box'>
                    <h3>風險評分</h3>
                    <p>{$analytics['risk_score']['score']} / 100</p>
                    <p>風險等級: {$analytics['risk_score']['level']}</p>
                </div>
            </div>
            
            <h2>安全建議</h2>
            <ul>";
            
        foreach ($analytics['risk_score']['recommendations'] as $recommendation) {
            $html .= "<li>{$recommendation}</li>";
        }
        
        $html .= "</ul>
        </body>
        </html>";
        
        return $html;
    }
}