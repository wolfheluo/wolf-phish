#!/bin/bash

#################################################################
# CRETECH-PHISH 系統檢查腳本
# 檢查系統配置和運行狀態
#################################################################

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# 日誌函數
log_info() { echo -e "${GREEN}[✓]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[!]${NC} $1"; }
log_error() { echo -e "${RED}[✗]${NC} $1"; }
log_blue() { echo -e "${BLUE}[i]${NC} $1"; }

echo -e "${BLUE}################################################################"
echo "#                                                              #"
echo "#              CRETECH-PHISH 系統檢查                          #"
echo "#                                                              #"
echo "################################################################${NC}"
echo

# 檢查作業系統
echo -e "${BLUE}=== 系統信息 ===${NC}"
echo "作業系統: $(uname -s)"
echo "核心版本: $(uname -r)"
echo "架構: $(uname -m)"

if [ -f /etc/os-release ]; then
    . /etc/os-release
    echo "發行版: $NAME $VERSION"
fi

echo "系統時間: $(date)"
echo "運行時間: $(uptime -p 2>/dev/null || uptime)"
echo

# 檢查 PHP
echo -e "${BLUE}=== PHP 環境 ===${NC}"
if command -v php &> /dev/null; then
    PHP_VERSION=$(php -v | head -n1 | cut -d' ' -f2)
    log_info "PHP 版本: $PHP_VERSION"
    
    # 檢查 PHP 版本是否 >= 8.1
    if php -v | head -n1 | grep -E "8\.[1-9]|[9-9]\." &> /dev/null; then
        log_info "PHP 版本符合需求 (>= 8.1)"
    else
        log_warn "PHP 版本可能過舊，建議使用 8.1+"
    fi
    
    # 檢查必要的 PHP 擴展
    echo "檢查 PHP 擴展:"
    extensions=("mysqli" "pdo_mysql" "curl" "gd" "json" "zip" "mbstring" "openssl")
    
    for ext in "${extensions[@]}"; do
        if php -m | grep -q "^$ext$"; then
            log_info "  $ext 已安裝"
        else
            log_error "  $ext 未安裝"
        fi
    done
    
else
    log_error "PHP 未安裝"
fi

echo

# 檢查 Web 服務器
echo -e "${BLUE}=== Web 服務器 ===${NC}"

# 檢查 Nginx
if command -v nginx &> /dev/null; then
    NGINX_VERSION=$(nginx -v 2>&1 | cut -d'/' -f2)
    log_info "Nginx 版本: $NGINX_VERSION"
    
    if systemctl is-active --quiet nginx; then
        log_info "Nginx 服務運行中"
        
        # 檢查配置
        if nginx -t &> /dev/null; then
            log_info "Nginx 配置正確"
        else
            log_warn "Nginx 配置有問題"
        fi
        
    else
        log_warn "Nginx 服務未運行"
    fi
else
    log_error "Nginx 未安裝"
fi

# 檢查 Apache (如果存在)
if command -v apache2 &> /dev/null || command -v httpd &> /dev/null; then
    if systemctl is-active --quiet apache2 || systemctl is-active --quiet httpd; then
        log_warn "檢測到 Apache 正在運行，可能會與 Nginx 衝突"
    fi
fi

echo

# 檢查數據庫
echo -e "${BLUE}=== 數據庫 ===${NC}"

if command -v mysql &> /dev/null; then
    MYSQL_VERSION=$(mysql --version | awk '{print $3}' | cut -d',' -f1)
    log_info "MySQL 版本: $MYSQL_VERSION"
    
    if systemctl is-active --quiet mysql || systemctl is-active --quiet mysqld; then
        log_info "MySQL 服務運行中"
        
        # 測試數據庫連接
        if mysql -u phish_user -pphish_password_2023 -e "USE cretech_phish; SELECT 1;" &> /dev/null; then
            log_info "數據庫連接正常"
            
            # 檢查表結構
            table_count=$(mysql -u phish_user -pphish_password_2023 -D cretech_phish -e "SHOW TABLES;" 2>/dev/null | wc -l)
            if [ $table_count -gt 10 ]; then
                log_info "數據庫表結構完整 ($((table_count-1)) 個表)"
            else
                log_warn "數據庫表可能不完整"
            fi
            
        else
            log_warn "無法連接數據庫，請檢查帳密設定"
        fi
    else
        log_warn "MySQL 服務未運行"
    fi
else
    log_error "MySQL 未安裝"
fi

echo

# 檢查項目文件
echo -e "${BLUE}=== 項目文件 ===${NC}"

PROJECT_DIRS=("/var/www/cretech-phish" "$(pwd)")
PROJECT_DIR=""

for dir in "${PROJECT_DIRS[@]}"; do
    if [ -f "$dir/public/index.php" ]; then
        PROJECT_DIR="$dir"
        break
    fi
done

if [ -n "$PROJECT_DIR" ]; then
    log_info "項目目錄: $PROJECT_DIR"
    
    # 檢查重要文件
    important_files=(
        "public/index.php"
        "config/config.php"
        "config/database.php"
        "src/controllers/AuthController.php"
        "database/schema.sql"
    )
    
    for file in "${important_files[@]}"; do
        if [ -f "$PROJECT_DIR/$file" ]; then
            log_info "  $file 存在"
        else
            log_error "  $file 缺失"
        fi
    done
    
    # 檢查目錄權限
    echo "檢查目錄權限:"
    writable_dirs=("uploads" "tmp" "cache" "logs")
    
    for dir in "${writable_dirs[@]}"; do
        if [ -d "$PROJECT_DIR/$dir" ]; then
            if [ -w "$PROJECT_DIR/$dir" ]; then
                log_info "  $dir 目錄可寫"
            else
                log_warn "  $dir 目錄權限不足"
            fi
        else
            log_warn "  $dir 目錄不存在"
        fi
    done
    
else
    log_error "未找到項目目錄"
fi

echo

# 檢查網路和端口
echo -e "${BLUE}=== 網路檢查 ===${NC}"

# 檢查端口佔用
ports=("80:HTTP" "443:HTTPS" "3306:MySQL" "22:SSH")

for port_info in "${ports[@]}"; do
    port=$(echo $port_info | cut -d':' -f1)
    service=$(echo $port_info | cut -d':' -f2)
    
    if netstat -tlnp 2>/dev/null | grep ":$port " &> /dev/null || ss -tlnp 2>/dev/null | grep ":$port " &> /dev/null; then
        log_info "$service 端口 $port 已開啟"
    else
        log_warn "$service 端口 $port 未開啟"
    fi
done

echo

# 檢查防火牆
echo -e "${BLUE}=== 防火牆檢查 ===${NC}"

if command -v ufw &> /dev/null; then
    if ufw status | grep -q "Status: active"; then
        log_info "UFW 防火牆已啟用"
        if ufw status | grep -q "80/tcp"; then
            log_info "  HTTP (80) 已開放"
        else
            log_warn "  HTTP (80) 未開放"
        fi
    else
        log_warn "UFW 防火牆未啟用"
    fi
elif command -v firewall-cmd &> /dev/null; then
    if systemctl is-active --quiet firewalld; then
        log_info "Firewalld 已啟用"
        if firewall-cmd --list-services | grep -q "http"; then
            log_info "  HTTP 服務已開放"
        else
            log_warn "  HTTP 服務未開放"
        fi
    else
        log_warn "Firewalld 未啟用"
    fi
else
    log_warn "未檢測到防火牆配置"
fi

echo

# 檢查系統資源
echo -e "${BLUE}=== 系統資源 ===${NC}"

# 記憶體
mem_total=$(free -h | awk '/^Mem:/ {print $2}')
mem_used=$(free -h | awk '/^Mem:/ {print $3}')
mem_percent=$(free | awk '/^Mem:/ {printf "%.1f", $3/$2 * 100.0}')

echo "記憶體使用: $mem_used / $mem_total (${mem_percent}%)"
if (( $(echo "$mem_percent > 80" | bc -l) )); then
    log_warn "記憶體使用率較高"
elif (( $(echo "$mem_percent > 90" | bc -l) )); then
    log_error "記憶體使用率過高"
else
    log_info "記憶體使用正常"
fi

# 磁碟空間
df_output=$(df -h / | tail -1)
disk_used=$(echo $df_output | awk '{print $5}' | sed 's/%//')
disk_avail=$(echo $df_output | awk '{print $4}')

echo "磁碟空間: 已使用 ${disk_used}%, 可用 $disk_avail"
if [ "$disk_used" -gt 90 ]; then
    log_error "磁碟空間不足"
elif [ "$disk_used" -gt 80 ]; then
    log_warn "磁碟空間較少"
else
    log_info "磁碟空間充足"
fi

# CPU 負載
if command -v uptime &> /dev/null; then
    load_avg=$(uptime | awk -F'load average:' '{print $2}' | awk '{print $1}' | sed 's/,//')
    echo "CPU 平均負載: $load_avg"
fi

echo

# 檢查服務狀態
echo -e "${BLUE}=== 服務狀態 ===${NC}"

services=("nginx" "php8.1-fpm:php-fpm" "mysql:mysqld")

for service_info in "${services[@]}"; do
    service_name=$(echo $service_info | cut -d':' -f1)
    alt_name=$(echo $service_info | cut -d':' -f2 2>/dev/null)
    
    if systemctl is-active --quiet "$service_name" || \
       ([ -n "$alt_name" ] && systemctl is-active --quiet "$alt_name"); then
        log_info "$service_name 服務運行中"
    else
        log_warn "$service_name 服務未運行"
    fi
done

echo

# 生成總結報告
echo -e "${BLUE}=== 總結 ===${NC}"

# 統計檢查結果
total_checks=0
passed_checks=0

# 這裡可以基於之前的檢查結果進行統計
# 簡化版本，基於關鍵服務狀態

if command -v php &> /dev/null && php -v | grep -E "8\.[1-9]" &> /dev/null; then
    ((passed_checks++))
fi
((total_checks++))

if systemctl is-active --quiet nginx; then
    ((passed_checks++))
fi
((total_checks++))

if systemctl is-active --quiet mysql || systemctl is-active --quiet mysqld; then
    ((passed_checks++))
fi
((total_checks++))

if [ -f "$PROJECT_DIR/public/index.php" ]; then
    ((passed_checks++))
fi
((total_checks++))

pass_rate=$(( passed_checks * 100 / total_checks ))

echo "檢查完成: $passed_checks/$total_checks 項通過 (${pass_rate}%)"

if [ $pass_rate -ge 80 ]; then
    log_info "系統狀態良好，可以正常運行"
elif [ $pass_rate -ge 60 ]; then
    log_warn "系統基本可用，但有一些問題需要處理"
else
    log_error "系統存在嚴重問題，需要修復後才能使用"
fi

echo
echo "建議操作:"
echo "1. 檢查日誌文件: tail -f $PROJECT_DIR/logs/app.log"
echo "2. 測試網站訪問: curl -I http://localhost"
echo "3. 檢查服務狀態: systemctl status nginx php8.1-fpm mysql"

if [ $pass_rate -lt 100 ]; then
    echo "4. 根據上述檢查結果修復問題"
    echo "5. 重新運行此檢查腳本確認修復效果"
fi

echo