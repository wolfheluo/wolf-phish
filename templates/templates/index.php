<?php ob_start(); ?>

<div id="templatesApp">
    <!-- 頁面標題 -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-envelope me-2"></i>範本管理</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <button class="btn btn-primary btn-sm" @click="showCreateModal = true">
                <i class="fas fa-plus me-1"></i>新增範本
            </button>
        </div>
    </div>

    <!-- 標籤頁 -->
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <button class="nav-link" 
                    :class="{ active: activeTab === 'email' }"
                    @click="activeTab = 'email'">
                <i class="fas fa-envelope me-2"></i>郵件範本
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link"
                    :class="{ active: activeTab === 'phishing' }"
                    @click="activeTab = 'phishing'">
                <i class="fas fa-globe me-2"></i>釣魚網站範本
            </button>
        </li>
    </ul>

    <!-- 搜尋欄 -->
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" 
                       class="form-control" 
                       v-model="searchQuery"
                       placeholder="搜尋範本名稱或描述...">
            </div>
        </div>
    </div>

    <!-- 郵件範本列表 -->
    <div v-if="activeTab === 'email'">
        <div class="row">
            <div class="col-md-4 mb-3" v-for="template in filteredEmailTemplates" :key="template.id">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">{{ template.name }}</h6>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" @click="previewTemplate(template)">
                                    <i class="fas fa-eye me-2"></i>預覽
                                </a></li>
                                <li><a class="dropdown-item" href="#" @click="testSend(template)">
                                    <i class="fas fa-paper-plane me-2"></i>測試發送
                                </a></li>
                                <li><a class="dropdown-item" href="#" @click="editTemplate(template)">
                                    <i class="fas fa-edit me-2"></i>編輯
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="#" @click="deleteTemplate(template)">
                                    <i class="fas fa-trash me-2"></i>刪除
                                </a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="card-text text-muted small">{{ template.description }}</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                {{ formatDate(template.created_at) }}
                            </small>
                            <span class="badge" :class="template.is_active ? 'bg-success' : 'bg-secondary'">
                                {{ template.is_active ? '啟用' : '停用' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="filteredEmailTemplates.length === 0" class="text-center py-5">
            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
            <p class="text-muted">暫無郵件範本</p>
        </div>
    </div>

    <!-- 釣魚網站範本列表 -->
    <div v-if="activeTab === 'phishing'">
        <div class="row">
            <div class="col-md-4 mb-3" v-for="site in filteredPhishingSites" :key="site.id">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">{{ site.name }}</h6>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" @click="previewSite(site)">
                                    <i class="fas fa-eye me-2"></i>預覽
                                </a></li>
                                <li><a class="dropdown-item" href="#" @click="editSite(site)">
                                    <i class="fas fa-edit me-2"></i>編輯
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="#" @click="deleteSite(site)">
                                    <i class="fas fa-trash me-2"></i>刪除
                                </a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="card-text text-muted small">{{ site.description }}</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="fas fa-tag me-1"></i>
                                {{ site.site_type }}
                            </small>
                            <span class="badge" :class="site.is_active ? 'bg-success' : 'bg-secondary'">
                                {{ site.is_active ? '啟用' : '停用' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="filteredPhishingSites.length === 0" class="text-center py-5">
            <i class="fas fa-globe fa-3x text-muted mb-3"></i>
            <p class="text-muted">暫無釣魚網站範本</p>
        </div>
    </div>

    <!-- 創建/編輯範本模態框 -->
    <div class="modal fade" id="templateModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        {{ isEditing ? '編輯' : '新增' }}{{ activeTab === 'email' ? '郵件' : '釣魚網站' }}範本
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- 郵件範本表單 -->
                    <form v-if="activeTab === 'email'" @submit.prevent="saveEmailTemplate">
                        <div class="mb-3">
                            <label class="form-label">範本名稱 *</label>
                            <input type="text" class="form-control" v-model="emailTemplateForm.name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">描述</label>
                            <textarea class="form-control" rows="2" v-model="emailTemplateForm.description"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">郵件主旨 *</label>
                            <input type="text" class="form-control" v-model="emailTemplateForm.subject" required>
                            <div class="form-text">可使用變數: {{name}}, {{email}}, {{department}}</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">HTML 內容 *</label>
                            <textarea class="form-control" 
                                      rows="15" 
                                      v-model="emailTemplateForm.html_content" 
                                      placeholder="輸入 HTML 郵件內容..."
                                      required></textarea>
                            <div class="form-text">
                                可用變數: {{name}}, {{email}}, {{department}}, {{tracking_pixel}}, {{phish_url}}, {{download_url}}
                            </div>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" v-model="emailTemplateForm.is_active">
                            <label class="form-check-label">啟用此範本</label>
                        </div>
                    </form>

                    <!-- 釣魚網站範本表單 -->
                    <form v-if="activeTab === 'phishing'" @submit.prevent="savePhishingSite">
                        <div class="mb-3">
                            <label class="form-label">範本名稱 *</label>
                            <input type="text" class="form-control" v-model="phishingSiteForm.name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">描述</label>
                            <textarea class="form-control" rows="2" v-model="phishingSiteForm.description"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">網站類型 *</label>
                            <select class="form-select" v-model="phishingSiteForm.site_type" required>
                                <option value="login">登錄頁面</option>
                                <option value="download">下載頁面</option>
                                <option value="form">表單頁面</option>
                                <option value="redirect">重定向頁面</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">HTML 內容 *</label>
                            <textarea class="form-control" 
                                      rows="15" 
                                      v-model="phishingSiteForm.html_content" 
                                      placeholder="輸入 HTML 頁面內容..."
                                      required></textarea>
                            <div class="form-text">
                                可用變數: {{project_id}}, {{email}}, {{target_username}}
                            </div>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" v-model="phishingSiteForm.is_active">
                            <label class="form-check-label">啟用此範本</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" 
                            class="btn btn-primary" 
                            @click="activeTab === 'email' ? saveEmailTemplate() : savePhishingSite()">
                        {{ isEditing ? '更新' : '創建' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const { createApp } = Vue;

createApp({
    data() {
        return {
            activeTab: 'email',
            searchQuery: '',
            showCreateModal: false,
            isEditing: false,
            emailTemplates: [],
            phishingSites: [],
            emailTemplateForm: {
                name: '',
                description: '',
                subject: '',
                html_content: '',
                is_active: true
            },
            phishingSiteForm: {
                name: '',
                description: '',
                site_type: 'login',
                html_content: '',
                is_active: true
            }
        }
    },
    
    computed: {
        filteredEmailTemplates() {
            return this.emailTemplates.filter(template => 
                template.name.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                template.description.toLowerCase().includes(this.searchQuery.toLowerCase())
            );
        },
        
        filteredPhishingSites() {
            return this.phishingSites.filter(site => 
                site.name.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                site.description.toLowerCase().includes(this.searchQuery.toLowerCase())
            );
        }
    },
    
    mounted() {
        this.loadTemplates();
    },
    
    methods: {
        async loadTemplates() {
            try {
                // 載入郵件範本
                const emailResponse = await fetch('/api/templates?type=email');
                if (emailResponse.ok) {
                    this.emailTemplates = await emailResponse.json();
                }
                
                // 載入釣魚網站範本
                const siteResponse = await fetch('/api/templates?type=phishing');
                if (siteResponse.ok) {
                    this.phishingSites = await siteResponse.json();
                }
            } catch (error) {
                console.error('載入範本失敗:', error);
            }
        },
        
        formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('zh-TW');
        },
        
        previewTemplate(template) {
            // 在新視窗預覽範本
            const previewWindow = window.open('', '_blank');
            previewWindow.document.write(template.html_content);
        },
        
        previewSite(site) {
            // 在新視窗預覽釣魚網站
            const previewWindow = window.open('', '_blank');
            previewWindow.document.write(site.html_content);
        }
    }
}).mount('#templatesApp');
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>