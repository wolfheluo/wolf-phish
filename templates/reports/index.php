<?php ob_start(); ?>

<div id="reportsApp">
    <!-- 頁面標題 -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-chart-bar me-2"></i>結果查詢</h1>
    </div>

    <!-- 專案選擇 -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="card-title mb-0">選擇專案</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <select class="form-select" v-model="selectedProjectId" @change="loadProjectData">
                        <option value="">請選擇專案...</option>
                        <option v-for="project in projects" :key="project.id" :value="project.id">
                            {{ project.project_code }} - {{ project.project_name }}
                        </option>
                    </select>
                </div>
                <div class="col-md-6" v-if="selectedProject">
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary" @click="exportReport">
                            <i class="fas fa-download me-1"></i>匯出報告
                        </button>
                        <button class="btn btn-outline-success" @click="generatePDF">
                            <i class="fas fa-file-pdf me-1"></i>PDF報告
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 專案概覽 -->
    <div v-if="selectedProject" class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">專案概覽</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="120"><strong>專案代號:</strong></td>
                                    <td>{{ selectedProject.project_code }}</td>
                                    <td width="120"><strong>專案名稱:</strong></td>
                                    <td>{{ selectedProject.project_name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>開始日期:</strong></td>
                                    <td>{{ formatDate(selectedProject.start_date) }}</td>
                                    <td><strong>結束日期:</strong></td>
                                    <td>{{ formatDate(selectedProject.end_date) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>狀態:</strong></td>
                                    <td>
                                        <span class="badge" :class="getStatusClass(selectedProject.status)">
                                            {{ getStatusText(selectedProject.status) }}
                                        </span>
                                    </td>
                                    <td><strong>目標數量:</strong></td>
                                    <td>{{ selectedProject.target_count || 0 }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <canvas id="overallChart" width="200" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 統計圖表 -->
    <div v-if="selectedProject" class="row mb-4">
        <!-- 關鍵指標 -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">郵件發送</div>
                            <div class="h5 mb-0 font-weight-bold text-primary">
                                {{ analytics.sent_count || 0 }}
                            </div>
                            <div class="text-xs text-muted">
                                {{ getPercentage(analytics.sent_count, selectedProject.target_count) }}%
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-paper-plane fa-2x text-primary opacity-50"></i>
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
                            <div class="text-xs font-weight-bold text-uppercase mb-1">郵件開啟</div>
                            <div class="h5 mb-0 font-weight-bold text-success">
                                {{ analytics.opened_count || 0 }}
                            </div>
                            <div class="text-xs text-muted">
                                {{ getPercentage(analytics.opened_count, analytics.sent_count) }}%
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-envelope-open fa-2x text-success opacity-50"></i>
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
                            <div class="text-xs font-weight-bold text-uppercase mb-1">連結點擊</div>
                            <div class="h5 mb-0 font-weight-bold text-warning">
                                {{ analytics.clicked_count || 0 }}
                            </div>
                            <div class="text-xs text-muted">
                                {{ getPercentage(analytics.clicked_count, analytics.opened_count) }}%
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-mouse-pointer fa-2x text-warning opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card-danger h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">數據提交</div>
                            <div class="h5 mb-0 font-weight-bold text-danger">
                                {{ analytics.submitted_count || 0 }}
                            </div>
                            <div class="text-xs text-muted">
                                {{ getPercentage(analytics.submitted_count, analytics.clicked_count) }}%
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-danger opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 詳細統計 -->
    <div v-if="selectedProject" class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">時間線分析</h6>
                </div>
                <div class="card-body">
                    <canvas id="timelineChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">設備類型分佈</h6>
                </div>
                <div class="card-body">
                    <canvas id="deviceChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- 目標詳細資料 -->
    <div v-if="selectedProject" class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0">目標詳細資料</h6>
            <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-secondary" @click="filterStatus = ''">全部</button>
                <button class="btn btn-outline-danger" @click="filterStatus = 'submitted'">已提交</button>
                <button class="btn btn-outline-warning" @click="filterStatus = 'clicked'">已點擊</button>
                <button class="btn btn-outline-success" @click="filterStatus = 'opened'">已開啟</button>
                <button class="btn btn-outline-secondary" @click="filterStatus = 'sent'">已發送</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>郵箱</th>
                        <th>姓名</th>
                        <th>部門</th>
                        <th>發送狀態</th>
                        <th>開啟時間</th>
                        <th>點擊時間</th>
                        <th>提交時間</th>
                        <th>風險等級</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="target in filteredTargets" :key="target.id">
                        <td>{{ target.email }}</td>
                        <td>{{ target.name || '-' }}</td>
                        <td>{{ target.department || '-' }}</td>
                        <td>
                            <span class="badge" :class="getSendStatusClass(target.send_status)">
                                {{ getSendStatusText(target.send_status) }}
                            </span>
                        </td>
                        <td>
                            <small>{{ target.opened_at ? formatDateTime(target.opened_at) : '-' }}</small>
                        </td>
                        <td>
                            <small>{{ target.clicked_at ? formatDateTime(target.clicked_at) : '-' }}</small>
                        </td>
                        <td>
                            <small>{{ target.submitted_at ? formatDateTime(target.submitted_at) : '-' }}</small>
                        </td>
                        <td>
                            <span class="badge" :class="getRiskClass(target.risk_level)">
                                {{ getRiskText(target.risk_level) }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const { createApp } = Vue;

createApp({
    data() {
        return {
            projects: [],
            selectedProjectId: '',
            selectedProject: null,
            analytics: {},
            targets: [],
            filterStatus: '',
            charts: {}
        }
    },
    
    computed: {
        filteredTargets() {
            if (!this.filterStatus) return this.targets;
            
            return this.targets.filter(target => {
                switch (this.filterStatus) {
                    case 'submitted': return target.submitted_at;
                    case 'clicked': return target.clicked_at && !target.submitted_at;
                    case 'opened': return target.opened_at && !target.clicked_at;
                    case 'sent': return target.send_status === 'sent' && !target.opened_at;
                    default: return true;
                }
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
        
        async loadProjectData() {
            if (!this.selectedProjectId) return;
            
            try {
                // 載入專案詳情
                const projectResponse = await fetch(`/api/projects/${this.selectedProjectId}`);
                if (projectResponse.ok) {
                    this.selectedProject = await projectResponse.json();
                }
                
                // 載入統計數據
                const analyticsResponse = await fetch(`/api/analytics/${this.selectedProjectId}`);
                if (analyticsResponse.ok) {
                    this.analytics = await analyticsResponse.json();
                }
                
                // 載入目標數據
                const targetsResponse = await fetch(`/api/projects/${this.selectedProjectId}/targets`);
                if (targetsResponse.ok) {
                    this.targets = await targetsResponse.json();
                }
                
                this.$nextTick(() => {
                    this.initCharts();
                });
                
            } catch (error) {
                console.error('載入專案數據失敗:', error);
            }
        },
        
        initCharts() {
            this.initOverallChart();
            this.initTimelineChart();
            this.initDeviceChart();
        },
        
        initOverallChart() {
            const ctx = document.getElementById('overallChart');
            if (!ctx) return;
            
            if (this.charts.overall) {
                this.charts.overall.destroy();
            }
            
            this.charts.overall = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['已提交', '已點擊', '已開啟', '已發送', '未發送'],
                    datasets: [{
                        data: [
                            this.analytics.submitted_count || 0,
                            (this.analytics.clicked_count || 0) - (this.analytics.submitted_count || 0),
                            (this.analytics.opened_count || 0) - (this.analytics.clicked_count || 0),
                            (this.analytics.sent_count || 0) - (this.analytics.opened_count || 0),
                            (this.selectedProject.target_count || 0) - (this.analytics.sent_count || 0)
                        ],
                        backgroundColor: [
                            '#dc3545',
                            '#ffc107',
                            '#28a745',
                            '#007bff',
                            '#6c757d'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        },
        
        getPercentage(numerator, denominator) {
            if (!denominator || denominator === 0) return 0;
            return Math.round((numerator / denominator) * 100);
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
        }
    }
}).mount('#reportsApp');
</script>

<?php
$content = ob_get_clean();
$title = '結果查詢';
include 'layout.php';
?>