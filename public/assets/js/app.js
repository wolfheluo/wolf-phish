/**
 * Cretech-PHISH 主要JavaScript文件
 * 包含通用功能和工具函數
 */

// 全局應用程序對象
window.App = {
    // 配置
    config: {
        baseUrl: window.baseUrl || '',
        csrfToken: window.csrfToken || '',
        apiTimeout: 30000
    },
    
    // 工具函數
    utils: {
        // 顯示載入狀態
        showLoading: function() {
            const spinner = document.getElementById('loadingSpinner');
            if (spinner) {
                spinner.style.display = 'flex';
            }
        },
        
        // 隱藏載入狀態
        hideLoading: function() {
            const spinner = document.getElementById('loadingSpinner');
            if (spinner) {
                spinner.style.display = 'none';
            }
        },
        
        // 顯示警告訊息
        showAlert: function(message, type = 'info', duration = 5000) {
            // 移除現有警告
            const existingAlerts = document.querySelectorAll('.alert.auto-dismiss');
            existingAlerts.forEach(alert => alert.remove());
            
            // 創建新警告
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show auto-dismiss`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            // 插入到頁面頂部
            const mainContent = document.querySelector('.main-content') || document.body;
            const firstChild = mainContent.firstElementChild;
            if (firstChild) {
                mainContent.insertBefore(alertDiv, firstChild);
            } else {
                mainContent.appendChild(alertDiv);
            }
            
            // 自動消失
            if (duration > 0) {
                setTimeout(() => {
                    if (alertDiv && alertDiv.parentNode) {
                        alertDiv.remove();
                    }
                }, duration);
            }
            
            return alertDiv;
        },
        
        // 確認對話框
        confirm: function(message, title = '確認') {
            return new Promise((resolve) => {
                const result = window.confirm(`${title}\n\n${message}`);
                resolve(result);
            });
        },
        
        // 格式化日期
        formatDate: function(dateString, includeTime = true) {
            if (!dateString) return '';
            
            const date = new Date(dateString);
            const options = {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            };
            
            if (includeTime) {
                options.hour = '2-digit';
                options.minute = '2-digit';
            }
            
            return date.toLocaleDateString('zh-TW', options);
        },
        
        // 格式化文件大小
        formatFileSize: function(bytes) {
            if (bytes === 0) return '0 Bytes';
            
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },
        
        // 防抖函數
        debounce: function(func, wait, immediate) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    timeout = null;
                    if (!immediate) func.apply(this, args);
                };
                const callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(this, args);
            };
        },
        
        // 節流函數
        throttle: function(func, limit) {
            let inThrottle;
            return function() {
                const args = arguments;
                const context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        },
        
        // 複製到剪貼板
        copyToClipboard: function(text) {
            if (navigator.clipboard) {
                return navigator.clipboard.writeText(text).then(() => {
                    this.showAlert('已複製到剪貼板', 'success', 2000);
                });
            } else {
                // 備用方法
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                
                try {
                    document.execCommand('copy');
                    this.showAlert('已複製到剪貼板', 'success', 2000);
                } catch (err) {
                    console.error('複製失敗:', err);
                    this.showAlert('複製失敗', 'error', 3000);
                }
                
                document.body.removeChild(textArea);
            }
        }
    },
    
    // HTTP 請求封裝
    http: {
        // GET 請求
        get: async function(url, options = {}) {
            return this.request(url, {
                method: 'GET',
                ...options
            });
        },
        
        // POST 請求
        post: async function(url, data, options = {}) {
            return this.request(url, {
                method: 'POST',
                body: JSON.stringify(data),
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers
                },
                ...options
            });
        },
        
        // PUT 請求
        put: async function(url, data, options = {}) {
            return this.request(url, {
                method: 'PUT',
                body: JSON.stringify(data),
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers
                },
                ...options
            });
        },
        
        // DELETE 請求
        delete: async function(url, options = {}) {
            return this.request(url, {
                method: 'DELETE',
                ...options
            });
        },
        
        // 文件上傳
        upload: async function(url, formData, options = {}) {
            return this.request(url, {
                method: 'POST',
                body: formData,
                ...options
            });
        },
        
        // 通用請求方法
        request: async function(url, options = {}) {
            const defaultHeaders = {
                'X-CSRF-Token': App.config.csrfToken
            };
            
            const config = {
                headers: {
                    ...defaultHeaders,
                    ...options.headers
                },
                timeout: App.config.apiTimeout,
                ...options
            };
            
            try {
                const response = await fetch(url, config);
                
                // 檢查HTTP狀態
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                // 嘗試解析JSON
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const data = await response.json();
                    
                    // 檢查業務邏輯錯誤
                    if (data.error) {
                        throw new Error(data.message || '請求失敗');
                    }
                    
                    return data;
                } else {
                    return await response.text();
                }
                
            } catch (error) {
                console.error('HTTP請求錯誤:', error);
                
                // 處理特定錯誤
                if (error.name === 'AbortError') {
                    throw new Error('請求超時');
                } else if (!navigator.onLine) {
                    throw new Error('網路連接失敗');
                } else {
                    throw error;
                }
            }
        }
    },
    
    // 表單處理
    form: {
        // 序列化表單數據
        serialize: function(form) {
            const formData = new FormData(form);
            const data = {};
            
            for (const [key, value] of formData.entries()) {
                if (data[key]) {
                    // 處理多值字段
                    if (Array.isArray(data[key])) {
                        data[key].push(value);
                    } else {
                        data[key] = [data[key], value];
                    }
                } else {
                    data[key] = value;
                }
            }
            
            return data;
        },
        
        // 驗證表單
        validate: function(form, rules = {}) {
            const errors = {};
            const elements = form.elements;
            
            for (let i = 0; i < elements.length; i++) {
                const element = elements[i];
                const name = element.name;
                const value = element.value.trim();
                
                if (!name || !rules[name]) continue;
                
                const rule = rules[name];
                
                // 必填驗證
                if (rule.required && !value) {
                    errors[name] = rule.requiredMessage || `${name} 為必填欄位`;
                    continue;
                }
                
                // 最小長度
                if (rule.minLength && value.length < rule.minLength) {
                    errors[name] = rule.minLengthMessage || `${name} 至少需要 ${rule.minLength} 個字符`;
                    continue;
                }
                
                // 最大長度
                if (rule.maxLength && value.length > rule.maxLength) {
                    errors[name] = rule.maxLengthMessage || `${name} 不能超過 ${rule.maxLength} 個字符`;
                    continue;
                }
                
                // 正則表達式
                if (rule.pattern && !rule.pattern.test(value)) {
                    errors[name] = rule.patternMessage || `${name} 格式不正確`;
                    continue;
                }
                
                // 郵件驗證
                if (rule.email && value) {
                    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailPattern.test(value)) {
                        errors[name] = rule.emailMessage || '請輸入有效的郵件地址';
                    }
                }
            }
            
            return {
                isValid: Object.keys(errors).length === 0,
                errors: errors
            };
        },
        
        // 顯示驗證錯誤
        showErrors: function(form, errors) {
            // 清除現有錯誤
            const existingErrors = form.querySelectorAll('.invalid-feedback');
            existingErrors.forEach(error => error.remove());
            
            const inputs = form.querySelectorAll('.form-control');
            inputs.forEach(input => input.classList.remove('is-invalid'));
            
            // 顯示新錯誤
            for (const [fieldName, message] of Object.entries(errors)) {
                const field = form.querySelector(`[name="${fieldName}"]`);
                if (field) {
                    field.classList.add('is-invalid');
                    
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'invalid-feedback';
                    errorDiv.textContent = message;
                    
                    field.parentNode.appendChild(errorDiv);
                }
            }
        }
    },
    
    // 初始化
    init: function() {
        // 全局錯誤處理
        window.addEventListener('unhandledrejection', (event) => {
            console.error('未處理的Promise錯誤:', event.reason);
            this.utils.showAlert('發生未知錯誤，請刷新頁面重試', 'danger');
        });
        
        // 全局點擊處理
        document.addEventListener('click', (event) => {
            // 複製按鈕
            if (event.target.classList.contains('btn-copy')) {
                const text = event.target.dataset.copy;
                if (text) {
                    this.utils.copyToClipboard(text);
                }
            }
            
            // 確認刪除按鈕
            if (event.target.classList.contains('btn-delete-confirm')) {
                event.preventDefault();
                const message = event.target.dataset.confirm || '確定要刪除嗎？此操作不可恢復。';
                this.utils.confirm(message).then(confirmed => {
                    if (confirmed) {
                        const href = event.target.href || event.target.dataset.href;
                        if (href) {
                            window.location.href = href;
                        }
                    }
                });
            }
        });
        
        // AJAX表單處理
        document.addEventListener('submit', async (event) => {
            if (event.target.classList.contains('ajax-form')) {
                event.preventDefault();
                
                const form = event.target;
                const submitBtn = form.querySelector('[type="submit"]');
                const originalText = submitBtn.innerHTML;
                
                try {
                    // 禁用提交按鈕
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>處理中...';
                    
                    // 序列化表單數據
                    const data = this.form.serialize(form);
                    
                    // 發送請求
                    const result = await this.http.post(form.action || window.location.pathname, data);
                    
                    // 處理成功響應
                    if (result.data && result.data.redirect) {
                        this.utils.showAlert(result.message || '操作成功', 'success');
                        setTimeout(() => {
                            window.location.href = result.data.redirect;
                        }, 1500);
                    } else {
                        this.utils.showAlert(result.message || '操作成功', 'success');
                        
                        // 觸發自定義事件
                        form.dispatchEvent(new CustomEvent('ajaxSuccess', { detail: result }));
                    }
                    
                } catch (error) {
                    console.error('表單提交錯誤:', error);
                    this.utils.showAlert(error.message || '操作失敗', 'danger');
                    
                    // 觸發自定義事件
                    form.dispatchEvent(new CustomEvent('ajaxError', { detail: error }));
                    
                } finally {
                    // 恢復提交按鈕
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            }
        });
        
        console.log('Cretech-PHISH 應用程序已初始化');
    }
};

// 頁面載入完成後初始化
document.addEventListener('DOMContentLoaded', () => {
    App.init();
});

// 導出到全局
window.App = App;