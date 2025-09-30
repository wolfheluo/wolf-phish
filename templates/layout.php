<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Cretech-PHISH' ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom CSS -->
    <link href="/assets/css/app.css" rel="stylesheet">
    
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 1rem 1.5rem;
            border-radius: 0;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover {
            color: white;
            background-color: rgba(255,255,255,0.1);
            transform: translateX(5px);
        }
        
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.2);
            border-left: 4px solid #fff;
        }
        
        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        
        .navbar {
            background: white !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: box-shadow 0.15s ease-in-out;
        }
        
        .card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .stat-card-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        
        .stat-card-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .stat-card-info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .brand-logo {
            font-weight: bold;
            font-size: 1.2rem;
            color: #667eea;
        }
        
        .loading-spinner {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
        }
        
        .loading-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: white;
        }
    </style>
</head>
<body>
    <div class="loading-spinner" id="loadingSpinner">
        <div class="loading-content">
            <div class="spinner-border text-light" role="status">
                <span class="visually-hidden">載入中...</span>
            </div>
            <div class="mt-2">載入中...</div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <?php if (isset($user) && $user): ?>
            <!-- 側邊欄 -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white">CRETECH-PHISH</h4>
                        <small class="text-white-50">社交工程測試平台</small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?= ($_SERVER['REQUEST_URI'] === '/dashboard') ? 'active' : '' ?>" href="/dashboard">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                儀錶板
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/templates">
                                <i class="fas fa-envelope me-2"></i>
                                範本管理
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/projects/create">
                                <i class="fas fa-plus-circle me-2"></i>
                                開始演練
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/projects">
                                <i class="fas fa-tasks me-2"></i>
                                演練狀態
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/reports">
                                <i class="fas fa-chart-bar me-2"></i>
                                結果查詢
                            </a>
                        </li>
                        
                        <?php if ($user['role'] === 'admin'): ?>
                        <hr class="text-white-50">
                        <li class="nav-item">
                            <a class="nav-link" href="/users">
                                <i class="fas fa-users me-2"></i>
                                用戶管理
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/system">
                                <i class="fas fa-cog me-2"></i>
                                系統設定
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </nav>
            
            <!-- 主要內容 -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <!-- 頂部導航 -->
                <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
                    <div class="container-fluid">
                        <div class="navbar-brand brand-logo d-md-none">
                            CRETECH-PHISH
                        </div>
                        
                        <div class="navbar-nav ms-auto">
                            <div class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-user-circle me-2"></i>
                                    <?= htmlspecialchars($user['full_name'] ?? $user['username']) ?>
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="/profile"><i class="fas fa-user me-2"></i>個人資料</a></li>
                                    <li><a class="dropdown-item" href="/settings"><i class="fas fa-cog me-2"></i>設定</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="/auth/logout"><i class="fas fa-sign-out-alt me-2"></i>登出</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </nav>

                <!-- Flash 訊息 -->
                <?php if (isset($flash_messages) && !empty($flash_messages)): ?>
                <div class="alert-container">
                    <?php foreach ($flash_messages as $flash): ?>
                    <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($flash['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- 頁面內容 -->
                <?= $content ?? '' ?>
            </main>
            
            <?php else: ?>
            <!-- 未登入狀態 -->
            <div class="col-12">
                <?= $content ?? '' ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Vue.js 3 -->
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Custom JS -->
    <script src="/assets/js/app.js"></script>
    
    <!-- CSRF Token -->
    <script>
        window.csrfToken = '<?= $csrf_token ?? '' ?>';
        window.baseUrl = '<?= BASE_URL ?>';
    </script>
</body>
</html>