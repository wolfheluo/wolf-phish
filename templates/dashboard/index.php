<?php ob_start(); ?>

<div id="dashboardApp">
    <!-- 頁面標題 -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-tachometer-alt me-2"></i>儀錶板</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <button class="btn btn-outline-secondary btn-sm" @click="refreshData">
                <i class="fas fa-sync-alt me-1"></i>重新載入
            </button>
        </div>
    </div>

    <!-- 統計卡片 -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">總專案數</div>
                            <div class="h5 mb-0 font-weight-bold">{{ stats.total_projects || 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-folder-open fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card-success h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">進行中專案</div>
                            <div class="h5 mb-0 font-weight-bold">{{ stats.active_projects || 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-play-circle fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card-info h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">
                                <?= $user_role === 'admin' ? '郵件模板數' : '我的模板' ?>
                            </div>
                            <div class="h5 mb-0 font-weight-bold">{{ stats.total_templates || 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-envelope fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card-warning h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">目標郵件數</div>
                            <div class="h5 mb-0 font-weight-bold">{{ stats.total_targets || 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- 近期專案列表 -->
        <div class="col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>近期專案
                    </h6>
                    <a href="/projects" class="btn btn-sm btn-outline-primary">查看全部</a>
                </div>
                <div class="card-body">
                    <div v-if="loading" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">載入中...</span>
                        </div>
                    </div>
                    
                    <div v-else-if="recentProjects.length === 0" class="text-center py-4 text-muted">
                        <i class="fas fa-folder-open fa-3x mb-3 text-muted"></i>
                        <p>還沒有任何專案</p>
                        <a href="/projects/create" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>建立第一個專案
                        </a>
                    </div>
                    
                    <div v-else>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>專案代號</th>
                                        <th>專案名稱</th>
                                        <th>狀態</th>
                                        <th>建立時間</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="project in recentProjects" :key="project.id">
                                        <td>
                                            <code>{{ project.project_code }}</code>
                                        </td>
                                        <td>
                                            <strong>{{ project.project_name }}</strong>
                                        </td>
                                        <td>
                                            <span :class="getStatusClass(project.status)">
                                                {{ getStatusText(project.status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ formatDate(project.created_at) }}</small>
                                        </td>
                                        <td>
                                            <a :href="'/projects/' + project.id" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 專案時間線圖表 -->
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-chart-line me-2"></i>專案趨勢
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="projectTimelineChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($user_role === 'admin'): ?>
    <!-- 系統健康狀態 (僅管理員可見) -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-heartbeat me-2"></i>系統健康狀態
                    </h6>
                </div>
                <div class="card-body">
                    <div v-if="healthStatus" class="row">
                        <div class="col-md-3">
                            <div class="text-center">
                                <div :class="'badge badge-' + getHealthColor(healthStatus.status) + ' mb-2 p-2'">
                                    {{ getHealthText(healthStatus.status) }}
                                </div>
                                <p class="small text-muted">整體狀態</p>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="row">
                                <div class="col-sm-6 mb-2">
                                    <small class="text-muted">資料庫:</small>
                                    <span :class="'badge badge-' + getHealthColor(healthStatus.database?.status)">
                                        {{ healthStatus.database?.status }}
                                    </span>
                                </div>
                                <div class="col-sm-6 mb-2">
                                    <small class="text-muted">郵件系統:</small>
                                    <span :class="'badge badge-' + getHealthColor(healthStatus.mail_config?.status)">
                                        {{ healthStatus.mail_config?.status }}
                                    </span>
                                </div>
                                <div class="col-sm-6 mb-2">
                                    <small class="text-muted">磁碟使用:</small>
                                    <span v-if="healthStatus.disk_space">
                                        {{ healthStatus.disk_space.used_percent }}%
                                    </span>
                                </div>
                                <div class="col-sm-6 mb-2">
                                    <small class="text-muted">最近錯誤:</small>
                                    <span class="text-danger">{{ healthStatus.recent_errors?.count || 0 }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// 等待Vue.js載入完成
window.addEventListener('load', function() {
    if (typeof Vue !== 'undefined') {
        Vue.createApp({
    data() {
        return {
            loading: true,
            stats: {},
            recentProjects: [],
            projectTimeline: [],
            healthStatus: null,
            timelineChart: null
        }
    },
    
    mounted() {
        this.loadDashboardData();
        <?php if ($user_role === 'admin'): ?>
        this.loadHealthStatus();
        <?php endif; ?>
    },
    
    methods: {
        async loadDashboardData() {
            try {
                this.loading = true;
                const response = await fetch('/api/dashboard/data', {
                    credentials: 'same-origin'
                });
                const result = await response.json();
                
                if (!result.error) {
                    this.stats = result.data.stats;
                    this.recentProjects = result.data.recent_projects || [];
                    this.projectTimeline = result.data.project_timeline || [];
                    
                    this.$nextTick(() => {
                        this.renderTimelineChart();
                    });
                }
            } catch (error) {
                console.error('Load dashboard data error:', error);
            } finally {
                this.loading = false;
            }
        },
        
        <?php if ($user_role === 'admin'): ?>
        async loadHealthStatus() {
            try {
                const response = await fetch('/api/system/health', {
                    credentials: 'same-origin'
                });
                const result = await response.json();
                
                if (!result.error) {
                    this.healthStatus = result.data;
                }
            } catch (error) {
                console.error('Load health status error:', error);
            }
        },
        <?php endif; ?>
        
        renderTimelineChart() {
            const ctx = document.getElementById('projectTimelineChart');
            if (!ctx || this.timelineChart) return;
            
            const labels = this.projectTimeline.map(item => item.date);
            const data = this.projectTimeline.map(item => item.count);
            
            this.timelineChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '專案數量',
                        data: data,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        },
        
        refreshData() {
            this.loadDashboardData();
            <?php if ($user_role === 'admin'): ?>
            this.loadHealthStatus();
            <?php endif; ?>
        },
        
        getStatusClass(status) {
            const classes = {
                'pending': 'badge bg-warning text-dark',
                'active': 'badge bg-success',
                'completed': 'badge bg-primary',
                'paused': 'badge bg-secondary'
            };
            return classes[status] || 'badge bg-secondary';
        },
        
        getStatusText(status) {
            const texts = {
                'pending': '待處理',
                'active': '進行中',
                'completed': '已完成',
                'paused': '已暫停'
            };
            return texts[status] || status;
        },
        
        getHealthColor(status) {
            const colors = {
                'ok': 'success',
                'warning': 'warning',
                'error': 'danger'
            };
            return colors[status] || 'secondary';
        },
        
        getHealthText(status) {
            const texts = {
                'ok': '正常',
                'warning': '警告',
                'error': '錯誤'
            };
            return texts[status] || status;
        },
        
        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('zh-TW') + ' ' + date.toLocaleTimeString('zh-TW', {hour: '2-digit', minute: '2-digit'});
        }
    }
    }).mount('#dashboardApp');
    } else {
        console.error('Vue.js not loaded');
    }
});
</script>

<style>
.badge-success {
    background-color: #28a745 !important;
}
.badge-warning {
    background-color: #ffc107 !important;
}
.badge-danger {
    background-color: #dc3545 !important;
}
.badge-secondary {
    background-color: #6c757d !important;
}
</style>

<?php 
$content = ob_get_clean(); 
include __DIR__ . '/../layout.php'; 
?>