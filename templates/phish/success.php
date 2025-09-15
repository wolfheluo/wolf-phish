<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安全警告 - Security Warning</title>
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .warning-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            max-width: 600px;
            margin: 50px auto;
            overflow: hidden;
        }
        .warning-header {
            background: #dc3545;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .warning-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .warning-body {
            padding: 30px;
        }
        .security-tip {
            background: #f8f9fa;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 20px 0;
        }
        .btn-education {
            background: #28a745;
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s;
        }
        .btn-education:hover {
            background: #218838;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40,167,69,0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="warning-card">
            <div class="warning-header">
                <div class="warning-icon">⚠️</div>
                <h2>安全警告 - Security Warning</h2>
                <p class="mb-0">您剛才的行為已被記錄用於安全意識培訓</p>
                <small>Your action has been logged for security awareness training</small>
            </div>
            
            <div class="warning-body">
                <h4 class="text-danger mb-3">🎯 這是一個社交工程測試！</h4>
                <p class="lead">恭喜您參與了這次安全意識測試。您的行為數據將幫助組織改善安全防護策略。</p>
                
                <div class="alert alert-info">
                    <h5>📊 您的行為記錄：</h5>
                    <ul class="mb-0">
                        <li>郵件開啟時間：已記錄</li>
                        <li>連結點擊：已記錄</li>
                        <li>IP 地址和設備信息：已記錄</li>
                        <li>訪問的頁面：已記錄</li>
                    </ul>
                </div>
                
                <div class="security-tip">
                    <h5>🛡️ 重要安全提醒：</h5>
                    
                    <h6>1. 識別可疑郵件的方法：</h6>
                    <ul>
                        <li>檢查發送者郵件地址是否正確</li>
                        <li>注意語法錯誤或不自然的措辭</li>
                        <li>懷疑緊急或威脅性的內容</li>
                        <li>不要點擊來源不明的連結</li>
                    </ul>
                    
                    <h6>2. 安全最佳實踐：</h6>
                    <ul>
                        <li>定期更新密碼並使用多因素驗證</li>
                        <li>不在公共場所輸入敏感信息</li>
                        <li>定期備份重要數據</li>
                        <li>使用最新版本的防病毒軟件</li>
                    </ul>
                    
                    <h6>3. 遇到可疑情況時：</h6>
                    <ul>
                        <li>立即聯繫 IT 安全部門</li>
                        <li>不要提供個人或公司敏感信息</li>
                        <li>報告可疑郵件或網站</li>
                        <li>參加定期的安全意識培訓</li>
                    </ul>
                </div>
                
                <div class="alert alert-warning">
                    <h5>⚡ 記住：</h5>
                    <p class="mb-0">
                        <strong>真正的攻擊者不會告訴您這是測試。</strong><br>
                        保持警惕，學習識別和報告潛在威脅是每個人的責任。
                    </p>
                </div>
                
                <div class="text-center mt-4">
                    <button onclick="startEducationModule()" class="btn btn-education">
                        開始安全教育課程
                    </button>
                </div>
                
                <div class="mt-4 text-center">
                    <small class="text-muted">
                        此測試由 Cretech-PHISH 平台執行<br>
                        測試時間：<?php echo date('Y-m-d H:i:s'); ?><br>
                        <?php if (isset($project_id)): ?>
                        測試項目 ID：<?php echo htmlspecialchars($project_id); ?>
                        <?php endif; ?>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script src="/js/bootstrap.bundle.min.js"></script>
    <script>
        function startEducationModule() {
            alert('安全教育模組將在新視窗中開啟。\n\nSecurity education module will open in a new window.');
            
            // 這裡可以重定向到具體的安全教育資源
            window.open('https://www.cisa.gov/cybersecurity-best-practices', '_blank');
        }
        
        // 記錄頁面停留時間
        let startTime = new Date().getTime();
        
        window.addEventListener('beforeunload', function() {
            let timeSpent = new Date().getTime() - startTime;
            
            // 發送時間統計（可選）
            fetch('/api/track-time', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    page: 'success',
                    time_spent: Math.round(timeSpent / 1000),
                    project_id: '<?php echo $project_id ?? ""; ?>'
                })
            }).catch(() => {}); // 忽略錯誤
        });
    </script>
</body>
</html>