#!/bin/bash

#################################################################
# CRETECH-PHISH Docker 部署腳本
# 使用 Docker Compose 進行容器化部署
#################################################################

set -e

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# 日誌函數
log_info() { echo -e "${GREEN}[INFO]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }
log_blue() { echo -e "${BLUE}[INFO]${NC} $1"; }

# 檢查 Docker 和 Docker Compose
check_docker() {
    if ! command -v docker &> /dev/null; then
        log_error "Docker 未安裝，請先安裝 Docker"
        exit 1
    fi
    
    if ! command -v docker-compose &> /dev/null; then
        log_error "Docker Compose 未安裝，請先安裝 Docker Compose"
        exit 1
    fi
    
    log_info "Docker 環境檢查通過"
}

# 顯示 Banner
show_banner() {
    echo -e "${BLUE}"
    echo "################################################################"
    echo "#                                                              #"
    echo "#              CRETECH-PHISH Docker 部署                       #"
    echo "#                                                              #"
    echo "#              容器化部署方案                                    #"
    echo "#              版本: 1.0.0                                    #"
    echo "#                                                              #"
    echo "################################################################"
    echo -e "${NC}"
}

# 創建必要目錄
create_directories() {
    log_info "創建必要目錄..."
    
    mkdir -p logs cache tmp uploads
    mkdir -p docker/nginx/ssl
    
    # 設置權限
    chmod 777 logs cache tmp uploads
    
    log_info "目錄創建完成"
}

# 生成 SSL 證書 (自簽名)
generate_ssl() {
    log_info "生成 SSL 自簽名證書..."
    
    if [ ! -f "docker/nginx/ssl/cert.pem" ]; then
        openssl req -x509 -newkey rsa:4096 -keyout docker/nginx/ssl/key.pem -out docker/nginx/ssl/cert.pem -days 365 -nodes -subj "/C=TW/ST=Taiwan/L=Taipei/O=Cretech/OU=Security/CN=localhost"
        log_info "SSL 證書生成完成"
    else
        log_info "SSL 證書已存在"
    fi
}

# 構建並啟動容器
build_and_start() {
    log_info "構建並啟動 Docker 容器..."
    
    # 停止現有容器
    docker-compose down --remove-orphans 2>/dev/null || true
    
    # 構建鏡像
    docker-compose build --no-cache
    
    # 啟動服務
    docker-compose up -d
    
    log_info "等待服務啟動..."
    sleep 30
}

# 初始化數據庫
init_database() {
    log_info "初始化數據庫..."
    
    # 等待 MySQL 完全啟動
    while ! docker-compose exec mysql mysql -u root -proot123 -e "SELECT 1" >/dev/null 2>&1; do
        log_info "等待 MySQL 啟動..."
        sleep 5
    done
    
    # 執行數據庫初始化
    docker-compose exec php php /var/www/cretech-phish/database/init.php
    
    log_info "數據庫初始化完成"
}

# 檢查服務狀態
check_services() {
    log_info "檢查服務狀態..."
    
    services=("nginx" "php" "mysql" "redis")
    
    for service in "${services[@]}"; do
        if docker-compose ps | grep -q "${service}.*Up"; then
            log_info "✓ ${service} 運行正常"
        else
            log_error "✗ ${service} 運行異常"
        fi
    done
}

# 顯示完成信息
show_completion() {
    echo
    echo -e "${GREEN}################################################################"
    echo "#                                                              #"
    echo "#              🎉 Docker 部署完成！                            #"
    echo "#                                                              #"
    echo "################################################################${NC}"
    echo
    log_info "服務地址:"
    echo "  • 主應用: http://localhost"
    echo "  • MailHog: http://localhost:8025 (郵件測試界面)"
    echo "  • MySQL: localhost:3306"
    echo "  • Redis: localhost:6379"
    echo
    log_info "默認登錄信息:"
    echo "  • 用戶名: admin"
    echo "  • 密碼: admin123"
    echo
    log_info "Docker 管理命令:"
    echo "  • 查看日誌: docker-compose logs -f"
    echo "  • 重啟服務: docker-compose restart"
    echo "  • 停止服務: docker-compose down"
    echo "  • 查看狀態: docker-compose ps"
    echo
    log_warn "⚠️  請妥善保管管理員密碼，首次登入後請立即修改！"
    echo
}

# 清理函數
cleanup() {
    log_info "停止並清理容器..."
    docker-compose down --remove-orphans
    docker-compose rm -f
    log_info "清理完成"
}

# 備份函數
backup() {
    local backup_dir="backup/$(date +%Y%m%d-%H%M%S)"
    mkdir -p "$backup_dir"
    
    log_info "備份數據到 $backup_dir..."
    
    # 備份數據庫
    docker-compose exec mysql mysqldump -u root -proot123 cretech_phish > "$backup_dir/database.sql"
    
    # 備份上傳文件
    cp -r uploads "$backup_dir/" 2>/dev/null || true
    cp -r logs "$backup_dir/" 2>/dev/null || true
    
    log_info "備份完成: $backup_dir"
}

# 主函數
main() {
    case "${1:-deploy}" in
        "deploy")
            show_banner
            check_docker
            create_directories
            generate_ssl
            build_and_start
            init_database
            check_services
            show_completion
            ;;
        "start")
            log_info "啟動服務..."
            docker-compose start
            check_services
            ;;
        "stop")
            log_info "停止服務..."
            docker-compose stop
            ;;
        "restart")
            log_info "重啟服務..."
            docker-compose restart
            check_services
            ;;
        "status")
            docker-compose ps
            ;;
        "logs")
            docker-compose logs -f "${2:-}"
            ;;
        "cleanup")
            cleanup
            ;;
        "backup")
            backup
            ;;
        "rebuild")
            log_info "重建並部署..."
            docker-compose down
            docker-compose build --no-cache
            docker-compose up -d
            init_database
            check_services
            ;;
        *)
            echo "用法: $0 {deploy|start|stop|restart|status|logs|cleanup|backup|rebuild}"
            echo
            echo "命令說明:"
            echo "  deploy   - 完整部署 (默認)"
            echo "  start    - 啟動服務"
            echo "  stop     - 停止服務"
            echo "  restart  - 重啟服務"
            echo "  status   - 查看狀態"
            echo "  logs     - 查看日誌"
            echo "  cleanup  - 清理容器"
            echo "  backup   - 備份數據"
            echo "  rebuild  - 重建部署"
            exit 1
            ;;
    esac
}

# 執行主函數
main "$@"