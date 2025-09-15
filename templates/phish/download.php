<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>重要文件 - 請立即查看</title>
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .download-container {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        .download-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .download-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .download-body {
            padding: 40px;
        }
        .file-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }
        .btn-download {
            background: #28a745;
            border: none;
            color: white;
            padding: 15px 30px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 16px;
            transition: all 0.3s;
        }
        .btn-download:hover {
            background: #218838;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(40,167,69,0.3);
        }
        .urgency-indicator {
            background: #dc3545;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 15px;
        }
        .security-badge {
            background: #17a2b8;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="download-container">
            <div class="download-header">
                <div class="download-icon">📁</div>
                <h2>重要文件等待下載</h2>
                <p class="mb-0">您有一個重要的文件需要立即查看</p>
            </div>
            
            <div class="download-body">
                <div class="urgency-indicator">⚡ 緊急</div>
                
                <h4>您好，<?php echo isset($email) ? htmlspecialchars(explode('@', $email)[0]) : '用戶'; ?>，</h4>
                
                <p>根據我們的系統記錄，您有一個重要的檔案需要下載和審核。此檔案包含了重要的安全更新和政策變更信息。</p>
                
                <div class="file-info">
                    <h5>📄 檔案信息</h5>
                    <ul class="list-unstyled">
                        <li><strong>檔案名稱：</strong><?php echo htmlspecialchars($filename ?? 'security_update.zip'); ?> <span class="security-badge">已驗證</span></li>
                        <li><strong>檔案大小：</strong>2.3 MB</li>
                        <li><strong>檔案類型：</strong>壓縮檔案 (.zip)</li>
                        <li><strong>到期時間：</strong><?php echo date('Y-m-d H:i', strtotime('+24 hours')); ?></li>
                        <li><strong>發送者：</strong>IT 安全部門</li>
                    </ul>
                </div>
                
                <div class="alert alert-warning">
                    <h6>⚠️ 重要通知</h6>
                    <ul class="mb-0">
                        <li>此檔案包含最新的安全政策更新</li>
                        <li>請在24小時內完成下載和審核</li>
                        <li>逾期未處理可能影響系統存取權限</li>
                        <li>如有疑問請聯繫 IT 支援部門</li>
                    </ul>
                </div>
                
                <div class="text-center mt-4">
                    <a href="/track/zip?project_id=<?php echo urlencode($project_id); ?>&email=<?php echo urlencode($email); ?>&file=<?php echo urlencode($filename ?? 'security_update.zip'); ?>" 
                       class="btn btn-download"
                       onclick="trackDownload()">
                        🔽 立即下載檔案
                    </a>
                </div>
                
                <div class="mt-4 text-center">
                    <small class="text-muted">
                        這是一個安全的下載連結，已經過病毒掃描<br>
                        下載ID: <?php echo substr(md5($project_id . $email), 0, 8); ?>
                    </small>
                </div>
                
                <div class="mt-4">
                    <h6>🛡️ 安全提醒：</h6>
                    <ul class="text-muted small">
                        <li>請確認您的防病毒軟件已更新到最新版本</li>
                        <li>下載後請使用防病毒軟件掃描檔案</li>
                        <li>不要在公共電腦上下載敏感檔案</li>
                        <li>如果檔案無法開啟，請聯繫 IT 支援</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- 替代下載選項 -->
        <div class="text-center mt-3">
            <small>
                <a href="#" onclick="showAlternativeOptions()" class="text-decoration-none">
                    無法下載？點擊這裡查看替代選項
                </a>
            </small>
        </div>
    </div>

    <!-- 追蹤像素 -->
    <img src="/track/pixel?project_id=<?php echo urlencode($project_id); ?>&email=<?php echo urlencode($email); ?>" 
         width="1" height="1" style="display:none;" alt="">

    <script src="/js/bootstrap.bundle.min.js"></script>
    <script>
        // 記錄頁面訪問
        fetch('/track/page-visit', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                project_id: '<?php echo $project_id; ?>',
                email: '<?php echo $email; ?>',
                page_type: 'download',
                timestamp: new Date().toISOString()
            })
        }).catch(() => {});
        
        function trackDownload() {
            // 記錄下載按鈕點擊
            fetch('/track/button-click', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    project_id: '<?php echo $project_id; ?>',
                    email: '<?php echo $email; ?>',
                    button_type: 'download',
                    filename: '<?php echo $filename ?? 'security_update.zip'; ?>',
                    timestamp: new Date().toISOString()
                })
            }).catch(() => {});
            
            // 顯示下載提示
            setTimeout(() => {
                alert('檔案下載已開始，請查看您的下載資料夾。');
            }, 1000);
        }
        
        function showAlternativeOptions() {
            alert('替代下載選項：\n\n1. 請聯繫 IT 支援部門\n2. 使用企業內網下載\n3. 通過郵件重新發送下載連結\n\n如需協助請撥打分機 1234');
        }
        
        // 模擬真實的緊急感
        let countdown = 86400; // 24小時倒計時
        const countdownElement = document.createElement('div');
        countdownElement.className = 'alert alert-danger mt-3';
        countdownElement.innerHTML = '<strong>⏰ 剩餘時間：</strong><span id="countdown">24:00:00</span>';
        
        document.querySelector('.download-body').appendChild(countdownElement);
        
        function updateCountdown() {
            const hours = Math.floor(countdown / 3600);
            const minutes = Math.floor((countdown % 3600) / 60);
            const seconds = countdown % 60;
            
            document.getElementById('countdown').textContent = 
                `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            countdown--;
            
            if (countdown < 0) {
                document.getElementById('countdown').textContent = '已過期';
                document.getElementById('countdown').parentElement.innerHTML = '<strong>⚠️ 下載連結已過期</strong>';
            }
        }
        
        // 每秒更新倒計時
        setInterval(updateCountdown, 1000);
        
        // 創建虛假的安全掃描效果
        setTimeout(() => {
            const scanResult = document.createElement('div');
            scanResult.className = 'alert alert-success mt-3';
            scanResult.innerHTML = '✅ <strong>安全掃描完成</strong> - 檔案安全無病毒威脅';
            document.querySelector('.download-body').appendChild(scanResult);
        }, 3000);
        
        // 滑鼠懸停效果
        document.querySelector('.btn-download').addEventListener('mouseenter', function() {
            this.textContent = '🔽 點擊開始下載';
        });
        
        document.querySelector('.btn-download').addEventListener('mouseleave', function() {
            this.textContent = '🔽 立即下載檔案';
        });
    </script>
</body>
</html>