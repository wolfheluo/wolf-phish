<?php ob_start(); ?>

<div id="projectStatusApp">
    <!-- 頁面標題 -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-tasks me-2"></i>演練狀態</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <button class="btn btn-outline-secondary btn-sm me-2" @click="refreshData">
                <i class="fas fa-sync-alt me-1"></i>重新載入
            </button>
        </div>
    </div>

    <!-- 篩選和搜尋 -->
    <div class="row mb-3">
        <div class="col-md-4">
            <select class="form-select" v-model="statusFilter">
                <option value="">全部狀態</option>
                <option value="pending">待開始</option>
                <option value="active">進行中</option>
                <option value="completed">已完成</option>
                <option value="paused">已暫停</option>
            </select>
        </div>
        <div class="col-md-6">
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" 
                       class="form-control" 
                       v-model="searchQuery"
                       placeholder="搜尋專案名稱或代號...">
            </div>
        </div>
    </div>

    <!-- 專案列表 -->
    <div class="card">
        <div class="card-header">
            <h6 class="card-title mb-0">專案列表</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>專案代號</th>
                        <th>專案名稱</th>
                        <th>開始日期</th>
                        <th>結束日期</th>
                        <th>目標數量</th>
                        <th>使用模板</th>
                        <th>狀態</th>
                        <th>進度</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="project in filteredProjects" :key="project.id">
                        <td>
                            <span class="badge bg-secondary">{{ project.project_code }}</span>
                        </td>
                        <td>
                            <strong>{{ project.project_name }}</strong>
                            <br>
                            <small class="text-muted">{{ project.description }}</small>
                        </td>
                        <td>{{ formatDate(project.start_date) }}</td>
                        <td>{{ formatDate(project.end_date) }}</td>
                        <td>
                            <span class="badge bg-info">{{ project.target_count || 0 }}</span>
                        </td>
                        <td>
                            <small class="text-muted">
                                <i class="fas fa-envelope me-1"></i>
                                {{ project.email_template_name || '未設定' }}
                            </small>
                        </td>
                        <td>
                            <span class="badge" :class="getStatusClass(project.status)">
                                {{ getStatusText(project.status) }}
                            </span>
                        </td>
                        <td>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar" 
                                     :class="getProgressClass(project.status)"
                                     :style="{ width: getProgress(project) + '%' }">
                                    {{ getProgress(project) }}%
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" @click="viewDetails(project)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-outline-success" 
                                        v-if="project.status === 'pending'"
                                        @click="startProject(project)">
                                    <i class="fas fa-play"></i>
                                </button>
                                <button class="btn btn-outline-warning"
                                        v-if="project.status === 'active'"
                                        @click="pauseProject(project)">
                                    <i class="fas fa-pause"></i>
                                </button>
                                <button class="btn btn-outline-danger"
                                        v-if="project.status !== 'completed'"
                                        @click="stopProject(project)">
                                    <i class="fas fa-stop"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div v-if="filteredProjects.length === 0" class="card-body text-center py-5">
            <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
            <p class="text-muted">暫無專案資料</p>
        </div>
    </div>

    <!-- 專案詳情模態框 -->
    <div class="modal fade" id="projectDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">專案詳情</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" v-if="selectedProject">
                    <!-- 基本信息 -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>基本信息</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>專案代號:</strong></td>
                                    <td>{{ selectedProject.project_code }}</td>
                                </tr>
                                <tr>
                                    <td><strong>專案名稱:</strong></td>
                                    <td>{{ selectedProject.project_name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>狀態:</strong></td>
                                    <td>
                                        <span class="badge" :class="getStatusClass(selectedProject.status)">
                                            {{ getStatusText(selectedProject.status) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>創建時間:</strong></td>
                                    <td>{{ formatDateTime(selectedProject.created_at) }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>統計信息</h6>
                            <div class="row">
                                <div class="col-6">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h5 class="text-primary">{{ selectedProject.stats?.sent_count || 0 }}</h5>
                                            <small>已發送</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h5 class="text-success">{{ selectedProject.stats?.opened_count || 0 }}</h5>
                                            <small>已開啟</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 mt-2">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h5 class="text-warning">{{ selectedProject.stats?.clicked_count || 0 }}</h5>
                                            <small>已點擊</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 mt-2">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h5 class="text-danger">{{ selectedProject.stats?.submitted_count || 0 }}</h5>
                                            <small>已提交</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 目標清單 -->
                    <div class="mb-4">
                        <h6>目標清單</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>郵箱</th>
                                        <th>姓名</th>
                                        <th>部門</th>
                                        <th>發送狀態</th>
                                        <th>最後活動</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="target in selectedProject.targets" :key="target.id">
                                        <td>{{ target.email }}</td>
                                        <td>{{ target.name || '-' }}</td>
                                        <td>{{ target.department || '-' }}</td>
                                        <td>
                                            <span class="badge" :class="getSendStatusClass(target.send_status)">
                                                {{ getSendStatusText(target.send_status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ target.last_activity ? formatDateTime(target.last_activity) : '-' }}
                                            </small>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">關閉</button>
                    <a :href="'/reports/' + (selectedProject?.id || '')" class="btn btn-primary">
                        <i class="fas fa-chart-bar me-1"></i>查看報告
                    </a>
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
            projects: [],
            selectedProject: null,
            statusFilter: '',
            searchQuery: ''
        }
    },
    
    computed: {
        filteredProjects() {
            return this.projects.filter(project => {
                const matchesStatus = !this.statusFilter || project.status === this.statusFilter;
                const matchesSearch = !this.searchQuery || 
                    project.project_name.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                    project.project_code.toLowerCase().includes(this.searchQuery.toLowerCase());
                
                return matchesStatus && matchesSearch;
            });
        }
    },
    
    mounted() {
        this.loadProjects();
    },
    
    methods: {
        async loadProjects() {
            try {
                const response = await fetch('/api/projects');
                if (response.ok) {
                    this.projects = await response.json();
                }
            } catch (error) {
                console.error('載入專案失敗:', error);
            }
        },
        
        formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('zh-TW');
        },
        
        formatDateTime(dateString) {
            return new Date(dateString).toLocaleString('zh-TW');
        },
        
        getStatusClass(status) {
            const classes = {
                'pending': 'bg-secondary',
                'active': 'bg-success',
                'completed': 'bg-primary',
                'paused': 'bg-warning'
            };
            return classes[status] || 'bg-secondary';
        },
        
        getStatusText(status) {
            const texts = {
                'pending': '待開始',
                'active': '進行中',
                'completed': '已完成',
                'paused': '已暫停'
            };
            return texts[status] || '未知';
        },
        
        getProgress(project) {
            if (!project.stats) return 0;
            
            const total = project.target_count || 0;
            const sent = project.stats.sent_count || 0;
            
            return total > 0 ? Math.round((sent / total) * 100) : 0;
        },
        
        getProgressClass(status) {
            return status === 'completed' ? 'bg-success' : 'bg-primary';
        },
        
        async viewDetails(project) {
            try {
                const response = await fetch(`/api/projects/${project.id}`);
                if (response.ok) {
                    this.selectedProject = await response.json();
                    new bootstrap.Modal(document.getElementById('projectDetailsModal')).show();
                }
            } catch (error) {
                console.error('載入專案詳情失敗:', error);
            }
        },
        
        async startProject(project) {
            if (confirm('確定要啟動此專案嗎？')) {
                try {
                    const response = await fetch(`/api/projects/${project.id}/start`, {
                        method: 'POST'
                    });
                    
                    if (response.ok) {
                        this.loadProjects();
                    }
                } catch (error) {
                    console.error('啟動專案失敗:', error);
                }
            }
        },
        
        async pauseProject(project) {
            if (confirm('確定要暫停此專案嗎？')) {
                try {
                    const response = await fetch(`/api/projects/${project.id}/pause`, {
                        method: 'POST'
                    });
                    
                    if (response.ok) {
                        this.loadProjects();
                    }
                } catch (error) {
                    console.error('暫停專案失敗:', error);
                }
            }
        },
        
        async stopProject(project) {
            if (confirm('確定要停止此專案嗎？此操作不可撤銷！')) {
                try {
                    const response = await fetch(`/api/projects/${project.id}/stop`, {
                        method: 'POST'
                    });
                    
                    if (response.ok) {
                        this.loadProjects();
                    }
                } catch (error) {
                    console.error('停止專案失敗:', error);
                }
            }
        },
        
        refreshData() {
            this.loadProjects();
        }
    }
}).mount('#projectStatusApp');
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>