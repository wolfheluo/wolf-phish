<?php ob_start(); ?>

<div class="min-vh-100 d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <h2 class="brand-logo text-primary mb-2">CRETECH-PHISH</h2>
                            <p class="text-muted">社交工程測試平台</p>
                            
                            <?php if (isset($timeout) && $timeout): ?>
                            <div class="alert alert-warning" role="alert">
                                <i class="fas fa-clock me-2"></i>
                                會話已過期，請重新登入
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <form id="loginForm">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    <i class="fas fa-user me-2"></i>用戶名或郵件
                                </label>
                                <input type="text" class="form-control form-control-lg" id="username" name="username" required autocomplete="username">
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-2"></i>密碼
                                </label>
                                <input type="password" class="form-control form-control-lg" id="password" name="password" required autocomplete="current-password">
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg" id="loginBtn">
                                    <i class="fas fa-sign-in-alt me-2"></i>登入
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-4">
                            <small class="text-muted">
                                還沒有帳號？ <a href="/auth/register" class="text-decoration-none">立即註冊</a>
                            </small>
                        </div>
                        
                        <div class="text-center mt-4">
                            <small class="text-muted">
                                預設管理員帳號：admin / admin123
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');
    const usernameField = document.getElementById('username');
    const passwordField = document.getElementById('password');
    
    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // 禁用按鈕防止重複提交
        loginBtn.disabled = true;
        loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>登入中...';
        
        try {
            const formData = new FormData(loginForm);
            const data = Object.fromEntries(formData);
            
            const response = await fetch('/auth/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.error) {
                // 顯示錯誤訊息
                showAlert(result.message, 'danger');
            } else {
                // 登入成功
                showAlert('登入成功，正在跳轉...', 'success');
                setTimeout(() => {
                    window.location.href = result.data.redirect || '/dashboard';
                }, 1000);
            }
            
        } catch (error) {
            console.error('Login error:', error);
            showAlert('登入失敗，請稍後再試', 'danger');
        } finally {
            // 恢復按鈕狀態
            loginBtn.disabled = false;
            loginBtn.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i>登入';
        }
    });
    
    // 顯示警告訊息的函數
    function showAlert(message, type) {
        // 移除現有警告
        const existingAlert = document.querySelector('.alert');
        if (existingAlert) {
            existingAlert.remove();
        }
        
        // 創建新警告
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // 插入到表單前面
        loginForm.parentNode.insertBefore(alert, loginForm);
        
        // 自動消失
        setTimeout(() => {
            if (alert && alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    }
    
    // Enter 鍵處理
    usernameField.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            passwordField.focus();
        }
    });
    
    passwordField.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            loginForm.dispatchEvent(new Event('submit'));
        }
    });
});
</script>

<?php 
$content = ob_get_clean(); 
include __DIR__ . '/../layout.php'; 
?>