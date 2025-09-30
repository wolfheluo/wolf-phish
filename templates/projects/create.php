<?php ob_start(); ?>

<div id="projectsApp">
    <!-- 頁面標題 -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-plus-circle me-2"></i>開始演練</h1>
    </div>

    <!-- 創建專案表單 -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">創建新的釣魚測試專案</h5>
        </div>
        <div class="card-body">
            <form @submit.prevent="createProject" class="needs-validation" novalidate>
                <!-- 基本信息 -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">專案代號 *</label>
                        <input type="text" 
                               class="form-control" 
                               v-model="projectForm.project_code"
                               placeholder="自動生成或手動輸入"
                               required>
                        <div class="form-text">
                            <button type="button" class="btn btn-sm btn-link p-0" @click="generateProjectCode">
                                <i class="fas fa-random me-1"></i>自動生成
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">專案名稱 *</label>
                        <input type="text" 
                               class="form-control" 
                               v-model="projectForm.project_name"
                               placeholder="輸入專案名稱"
                               required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">專案描述</label>
                    <textarea class="form-control" 
                              rows="3"
                              v-model="projectForm.description"
                              placeholder="簡述此次測試的目的和範圍"></textarea>
                </div>

                <!-- 受測人帳號配置 -->
                <h6 class="mt-4 mb-3"><i class="fas fa-users me-2"></i>受測人帳號配置</h6>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">測試帳號 *</label>
                        <input type="text" 
                               class="form-control" 
                               v-model="projectForm.test_username"
                               placeholder="用於釣魚頁面的測試帳號"
                               required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">測試密碼 *</label>
                        <input type="password" 
                               class="form-control" 
                               v-model="projectForm.test_password"
                               placeholder="用於釣魚頁面的測試密碼"
                               required>
                    </div>
                </div>

                <!-- 目標清單上傳 -->
                <h6 class="mt-4 mb-3"><i class="fas fa-upload me-2"></i>目標清單上傳</h6>
                <div class="mb-3">
                    <label class="form-label">CSV 文件上傳 *</label>
                    <input type="file" 
                           class="form-control" 
                           accept=".csv"
                           @change="handleFileUpload"
                           required>
                    <div class="form-text">
                        CSV 格式：email,name,department<br>
                        範例：john@company.com,John Doe,IT Department
                    </div>
                </div>

                <!-- 郵件模板選擇 -->
                <h6 class="mt-4 mb-3"><i class="fas fa-envelope me-2"></i>電子郵件配置</h6>
                <div class="mb-3">
                    <label class="form-label">郵件模板 *</label>
                    <select class="form-select" v-model="projectForm.email_template_id" required>
                        <option value="">選擇郵件模板</option>
                        <option v-for="template in emailTemplates" 
                                :key="template.id" 
                                :value="template.id">
                            {{ template.name }}
                        </option>
                    </select>
                </div>

                <!-- 發件人信息 -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">發件人姓名 *</label>
                        <input type="text" 
                               class="form-control" 
                               v-model="projectForm.sender_name"
                               placeholder="發件人顯示名稱"
                               required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">發件人郵箱 *</label>
                        <input type="email" 
                               class="form-control" 
                               v-model="projectForm.sender_email"
                               placeholder="sender@company.com"
                               required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">郵件主旨 *</label>
                        <input type="text" 
                               class="form-control" 
                               v-model="projectForm.subject"
                               placeholder="郵件主旨"
                               required>
                    </div>
                </div>

                <!-- 追蹤URL配置 -->
                <h6 class="mt-4 mb-3"><i class="fas fa-link me-2"></i>追蹤URL配置</h6>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" v-model="projectForm.enable_pixel_tracking">
                            <label class="form-check-label">
                                追蹤像素 (郵件開啟)
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" v-model="projectForm.enable_zip_tracking">
                            <label class="form-check-label">
                                壓縮包下載追蹤
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" v-model="projectForm.enable_phish_site">
                            <label class="form-check-label">
                                釣魚網站連結
                            </label>
                        </div>
                    </div>
                </div>

                <!-- 釣魚網站模板選擇 -->
                <div class="mb-3" v-if="projectForm.enable_phish_site">
                    <label class="form-label">釣魚網站模板</label>
                    <select class="form-select" v-model="projectForm.phishing_site_id">
                        <option value="">選擇釣魚網站模板</option>
                        <option v-for="site in phishingSites" 
                                :key="site.id" 
                                :value="site.id">
                            {{ site.name }} ({{ site.site_type }})
                        </option>
                    </select>
                </div>

                <!-- 時間設定 -->
                <h6 class="mt-4 mb-3"><i class="fas fa-clock me-2"></i>專案時間設定</h6>
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label">開始日期 *</label>
                        <input type="date" 
                               class="form-control" 
                               v-model="projectForm.start_date"
                               required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">結束日期 *</label>
                        <input type="date" 
                               class="form-control" 
                               v-model="projectForm.end_date"
                               required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">每日發送開始時間</label>
                        <input type="time" 
                               class="form-control" 
                               v-model="projectForm.send_start_time"
                               value="09:00">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">每日發送結束時間</label>
                        <input type="time" 
                               class="form-control" 
                               v-model="projectForm.send_end_time"
                               value="17:00">
                    </div>
                </div>

                <!-- 提交按鈕 -->
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary" @click="resetForm">
                        <i class="fas fa-undo me-1"></i>重置
                    </button>
                    <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
                        <i class="fas fa-plus me-1"></i>
                        {{ isSubmitting ? '創建中...' : '創建專案' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const { createApp } = Vue;

createApp({
    data() {
        return {
            projectForm: {
                project_code: '',
                project_name: '',
                description: '',
                test_username: '',
                test_password: '',
                email_template_id: '',
                phishing_site_id: '',
                sender_name: '',
                sender_email: '',
                subject: '',
                start_date: '',
                end_date: '',
                send_start_time: '09:00',
                send_end_time: '17:00',
                enable_pixel_tracking: true,
                enable_zip_tracking: false,
                enable_phish_site: true
            },
            emailTemplates: [],
            phishingSites: [],
            targetEmails: [],
            isSubmitting: false
        }
    },
    
    mounted() {
        this.loadTemplates();
        this.generateProjectCode();
        this.setDefaultDates();
    },
    
    methods: {
        async loadTemplates() {
            try {
                // 載入郵件模板
                const emailResponse = await fetch('/api/templates?type=email');
                if (emailResponse.ok) {
                    this.emailTemplates = await emailResponse.json();
                }
                
                // 載入釣魚網站模板
                const siteResponse = await fetch('/api/templates?type=phishing');
                if (siteResponse.ok) {
                    this.phishingSites = await siteResponse.json();
                }
            } catch (error) {
                console.error('載入模板失敗:', error);
            }
        },
        
        generateProjectCode() {
            const now = new Date();
            const dateStr = now.toISOString().slice(0, 10).replace(/-/g, '');
            const randomStr = Math.random().toString(36).substr(2, 4).toUpperCase();
            this.projectForm.project_code = `PHISH-${dateStr}-${randomStr}`;
        },
        
        setDefaultDates() {
            const now = new Date();
            const nextWeek = new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000);
            
            this.projectForm.start_date = now.toISOString().slice(0, 10);
            this.projectForm.end_date = nextWeek.toISOString().slice(0, 10);
        },
        
        handleFileUpload(event) {
            const file = event.target.files[0];
            if (file && file.type === 'text/csv') {
                const formData = new FormData();
                formData.append('csv_file', file);
                
                // 解析CSV並預覽
                this.parseCSV(file);
            }
        },
        
        parseCSV(file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                const csv = e.target.result;
                const lines = csv.split('\n');
                const emails = [];
                
                for (let i = 1; i < lines.length; i++) {
                    const line = lines[i].trim();
                    if (line) {
                        const columns = line.split(',');
                        if (columns.length >= 1) {
                            emails.push({
                                email: columns[0]?.trim(),
                                name: columns[1]?.trim() || '',
                                department: columns[2]?.trim() || ''
                            });
                        }
                    }
                }
                
                this.targetEmails = emails;
                console.log(`解析到 ${emails.length} 個目標郵箱`);
            };
            reader.readAsText(file);
        },
        
        async createProject() {
            if (!this.validateForm()) return;
            
            this.isSubmitting = true;
            
            try {
                const formData = new FormData();
                Object.keys(this.projectForm).forEach(key => {
                    formData.append(key, this.projectForm[key]);
                });
                
                // 添加目標郵件清單
                formData.append('target_emails', JSON.stringify(this.targetEmails));
                
                const response = await fetch('/api/projects', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.error) {
                    throw new Error(result.message);
                }
                
                // 成功後跳轉到專案列表
                window.location.href = '/projects';
                
            } catch (error) {
                alert('創建專案失敗: ' + error.message);
            } finally {
                this.isSubmitting = false;
            }
        },
        
        validateForm() {
            if (!this.projectForm.project_code || !this.projectForm.project_name) {
                alert('請填寫必填欄位');
                return false;
            }
            
            if (this.targetEmails.length === 0) {
                alert('請上傳目標郵件清單');
                return false;
            }
            
            return true;
        },
        
        resetForm() {
            this.projectForm = {
                project_code: '',
                project_name: '',
                description: '',
                test_username: '',
                test_password: '',
                email_template_id: '',
                phishing_site_id: '',
                sender_name: '',
                sender_email: '',
                subject: '',
                start_date: '',
                end_date: '',
                send_start_time: '09:00',
                send_end_time: '17:00',
                enable_pixel_tracking: true,
                enable_zip_tracking: false,
                enable_phish_site: true
            };
            this.targetEmails = [];
            this.generateProjectCode();
            this.setDefaultDates();
        }
    }
}).mount('#projectsApp');
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>