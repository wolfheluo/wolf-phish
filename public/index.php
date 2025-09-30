<?php

// 錯誤報告設定
require_once __DIR__ . '/../config/config.php';

// 設置時區
date_default_timezone_set('Asia/Taipei');

// 開始輸出緩衝
ob_start();

// 設置字符編碼
header('Content-Type: text/html; charset=UTF-8');

// 簡單的路由處理
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 載入控制器
require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../src/controllers/DashboardController.php';
require_once __DIR__ . '/../src/controllers/TrackingController.php';
require_once __DIR__ . '/../src/controllers/PhishController.php';
require_once __DIR__ . '/../src/utils/EmailSender.php';

// 處理靜態文件
if (preg_match('/\.(?:css|js|png|jpg|jpeg|gif|ico|svg)$/', $uri)) {
    $filePath = __DIR__ . $uri;
    if (file_exists($filePath) && is_file($filePath)) {
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        
        switch ($ext) {
            case 'css':
                header('Content-Type: text/css');
                break;
            case 'js':
                header('Content-Type: application/javascript');
                break;
            case 'png':
                header('Content-Type: image/png');
                break;
            case 'jpg':
            case 'jpeg':
                header('Content-Type: image/jpeg');
                break;
            case 'gif':
                header('Content-Type: image/gif');
                break;
            case 'svg':
                header('Content-Type: image/svg+xml');
                break;
            case 'ico':
                header('Content-Type: image/x-icon');
                break;
        }
        
        readfile($filePath);
        exit;
    }
}

// 追蹤路由
if (strpos($uri, '/track/') === 0) {
    $controller = new TrackingController();
    $trackType = substr($uri, 7);
    
    switch ($trackType) {
        case 'pixel':
            $controller->pixel();
            break;
        case 'url':
            $controller->url();
            break;
        case 'zip':
            $controller->zip();
            break;
        case 'data':
            $controller->data();
            break;
        case 'stats':
            $controller->stats();
            break;
        default:
            http_response_code(404);
            echo 'Track endpoint not found';
            break;
    }
    exit;
}

// 釣魚頁面路由
if (strpos($uri, '/phish/') === 0) {
    $controller = new PhishController();
    $phishPage = substr($uri, 7);
    
    switch ($phishPage) {
        case 'default':
            $controller->defaultPage();
            break;
        case 'login':
            $controller->loginPage();
            break;
        case 'office365':
            $controller->office365Page();
            break;
        case 'download':
            $controller->downloadPage();
            break;
        case 'survey':
            $controller->surveyPage();
            break;
        case 'success':
            $controller->successPage();
            break;
        case 'warning':
            $controller->warningPage();
            break;
        case 'keylogger':
            $controller->keylogger();
            break;
        case 'screenshot':
            $controller->screenshot();
            break;
        default:
            http_response_code(404);
            echo 'Phish page not found';
            break;
    }
    exit;
}

// API 路由
if (strpos($uri, '/api/') === 0) {
    header('Content-Type: application/json');
    $apiPath = substr($uri, 5);
    
    try {
        switch ($apiPath) {
            case 'auth/check':
                $controller = new AuthController();
                $controller->checkSession();
                break;
                
            case 'auth/user':
                $controller = new AuthController();
                $controller->getUserInfo();
                break;
                
            case 'auth/change-password':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $controller = new AuthController();
                    $controller->changePassword();
                } else {
                    http_response_code(405);
                    echo json_encode(['error' => 'Method not allowed']);
                }
                break;
                
            case 'projects':
                $controller = new DashboardController();
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    $controller->getProjects();
                } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $controller->createProject();
                } else {
                    http_response_code(405);
                    echo json_encode(['error' => 'Method not allowed']);
                }
                break;
                
            case 'templates':
                $controller = new DashboardController();
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    $controller->getTemplates();
                } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $controller->createTemplate();
                } else {
                    http_response_code(405);
                    echo json_encode(['error' => 'Method not allowed']);
                }
                break;
                
            case 'send-emails':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $sender = new EmailSender();
                    $input = json_decode(file_get_contents('php://input'), true);
                    
                    $projectId = $input['project_id'] ?? null;
                    $templateId = $input['template_id'] ?? null;
                    $recipients = $input['recipients'] ?? [];
                    
                    if ($projectId && $templateId && !empty($recipients)) {
                        $results = $sender->sendBulkPhishingEmails($projectId, $templateId, $recipients);
                        echo json_encode(['success' => true, 'results' => $results]);
                    } else {
                        http_response_code(400);
                        echo json_encode(['error' => 'Missing required parameters']);
                    }
                } else {
                    http_response_code(405);
                    echo json_encode(['error' => 'Method not allowed']);
                }
                break;
                
            case 'dashboard/data':
                $controller = new DashboardController();
                $controller->getDashboardData();
                break;
                
            case 'system/health':
                $controller = new DashboardController();
                $controller->healthCheck();
                break;
                
            default:
                http_response_code(404);
                echo json_encode(['error' => 'API endpoint not found']);
                break;
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// 頁面路由
switch ($uri) {
    case '/':
        header('Location: /dashboard');
        exit;
        
    case '/auth/login':
        $controller = new AuthController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->login();
        } else {
            $controller->showLogin();
        }
        break;
        
    case '/auth/logout':
        $controller = new AuthController();
        $controller->logout();
        break;
        
    case '/auth/register':
        $controller = new AuthController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->register();
        } else {
            $controller->showRegister();
        }
        break;
        
    case '/dashboard':
        $controller = new DashboardController();
        $controller->index();
        break;
        
    case '/templates':
        $controller = new DashboardController();
        $controller->templates();
        break;
        
    case '/projects/create':
        $controller = new DashboardController();
        $controller->createProjectPage();
        break;
        
    case '/projects':
    case '/projects/status':
        $controller = new DashboardController();
        $controller->projectStatus();
        break;
        
    case '/reports':
        $controller = new DashboardController();
        $controller->reports();
        break;
        
    default:
        // 檢查是否為報告詳情頁面 /reports/{id}
        if (preg_match('/^\/reports\/(\d+)$/', $uri, $matches)) {
            $controller = new DashboardController();
            $controller->reportDetails($matches[1]);
            break;
        }
        
        // 檢查是否為專案詳情頁面 /projects/{id}
        if (preg_match('/^\/projects\/(\d+)$/', $uri, $matches)) {
            $controller = new DashboardController();
            $controller->projectDetails($matches[1]);
            break;
        }
        
        http_response_code(404);
        echo "<h1>404 - 頁面未找到</h1>";
        echo "<p>請求的頁面不存在。</p>";
        echo "<a href='/dashboard'>返回儀表板</a>";
        break;
}

// 結束輸出緩衝
ob_end_flush();