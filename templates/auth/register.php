<?php ob_start(); ?>

<div class="min-vh-100 d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <h2 class="brand-logo text-primary mb-2">CRETECH-PHISH</h2>
                            <p class="text-muted">建立新帳戶</p>
                        </div>
                        
                        <form id="registerForm" class="ajax-form" method="POST" action="/auth/register">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="username" class="form-label">
                                        <i class="fas fa-user me-2"></i>用戶名 *
                                    </label>
                                    <input type="text" class="form-control" id="username" name="username" required 
                                           minlength="3" maxlength="50" autocomplete="username">
                                    <small class="form-text text-muted">3-50個字符，字母數字或下劃線</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="full_name" class="form-label">
                                        <i class="fas fa-id-card me-2"></i>姓名 *
                                    </label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" required 
                                           maxlength="100" autocomplete="name">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-2"></i>郵件地址 *
                                </label>
                                <input type="email" class="form-control" id="email" name="email" required 
                                       maxlength="100" autocomplete="email">
                            </div>
                            
                            <div class="mb-3">
                                <label for="department" class="form-label">
                                    <i class="fas fa-building me-2"></i>部門
                                </label>
                                <input type="text" class="form-control" id="department" name="department" 
                                       maxlength="100" autocomplete="organization">
                                <small class="form-text text-muted">選填，便於管理和統計</small>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">
                                        <i class="fas fa-lock me-2"></i>密碼 *
                                    </label>
                                    <input type="password" class="form-control" id="password" name="password" required 
                                           minlength="6" autocomplete="new-password">
                                    <small class="form-text text-muted">至少6個字符</small>
                                </div>
                                
                                <div class="col-md-6 mb-4">
                                    <label for="password_confirm" class="form-label">
                                        <i class="fas fa-lock me-2"></i>確認密碼 *
                                    </label>
                                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" required 
                                           autocomplete="new-password">
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="agree_terms" required>
                                    <label class="form-check-label" for="agree_terms">
                                        我已閱讀並同意 <a href="/terms" target="_blank">使用條款</a> 和 <a href="/privacy" target="_blank">隱私政策</a>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg" id="registerBtn">
                                    <i class="fas fa-user-plus me-2"></i>建立帳戶
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center">
                            <small class="text-muted">
                                已有帳號？ <a href="/auth/login" class="text-decoration-none">立即登入</a>
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
    const registerForm = document.getElementById('registerForm');
    const passwordField = document.getElementById('password');
    const passwordConfirmField = document.getElementById('password_confirm');
    
    // 密碼確認驗證
    function validatePasswordMatch() {
        if (passwordField.value !== passwordConfirmField.value) {
            passwordConfirmField.setCustomValidity('密碼不匹配');
        } else {
            passwordConfirmField.setCustomValidity('');
        }
    }
    
    passwordField.addEventListener('input', validatePasswordMatch);
    passwordConfirmField.addEventListener('input', validatePasswordMatch);
    
    // 用戶名格式驗證
    const usernameField = document.getElementById('username');
    usernameField.addEventListener('input', function() {
        const value = this.value;
        const pattern = /^[a-zA-Z0-9_]+$/;
        
        if (value && !pattern.test(value)) {
            this.setCustomValidity('只能包含字母、數字和下劃線');
        } else {
            this.setCustomValidity('');
        }
    });
    
    // 表單提交處理
    registerForm.addEventListener('ajaxSuccess', function(event) {
        const result = event.detail;
        
        // 清空表單
        registerForm.reset();
        
        // 顯示成功訊息並跳轉
        App.utils.showAlert('註冊成功！請使用新帳戶登入', 'success');
        setTimeout(() => {
            window.location.href = '/auth/login';
        }, 2000);
    });
    
    registerForm.addEventListener('ajaxError', function(event) {
        const error = event.detail;
        
        // 如果是驗證錯誤，顯示字段錯誤
        if (error.validation_errors) {
            App.form.showErrors(registerForm, error.validation_errors);
        }
    });
});
</script>

<style>
.form-check-input:checked {
    background-color: #667eea;
    border-color: #667eea;
}

.form-text {
    font-size: 0.8rem;
}

.invalid-feedback {
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    .card-body {
        padding: 2rem !important;
    }
    
    .row .col-md-6 {
        margin-bottom: 1rem;
    }
}
</style>

<?php 
$content = ob_get_clean(); 
include __DIR__ . '/../layout.php'; 
?>