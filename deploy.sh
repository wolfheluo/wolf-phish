#!/bin/bash

#################################################################
# CRETECH-PHISH 一鍵部署腳本
# 支持 Ubuntu/Debian 和 CentOS/RHEL 系統
# 版本: 1.0.0
# 作者: Cretech Security Team
#################################################################

set -e  # 遇到錯誤立即退出

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 日誌函數
log_info() { echo -e "${GREEN}[INFO]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }
log_blue() { echo -e "${BLUE}[INFO]${NC} $1"; }

# 配置變數
DOMAIN="localhost"
DB_NAME="cretech_phish"
DB_USER="phish_user"
DB_PASS="phish_password_2023"
ADMIN_USER="admin"
ADMIN_PASS="admin123"
PROJECT_DIR="/var/www/cretech-phish"
CURRENT_DIR=$(pwd)

# 檢測作業系統
detect_os() {
    if [ -f /etc/redhat-release ]; then
        OS="centos"
        PKG_MANAGER="yum"
        if command -v dnf &> /dev/null; then
            PKG_MANAGER="dnf"
        fi
    elif [ -f /etc/debian_version ]; then
        OS="ubuntu"
        PKG_MANAGER="apt-get"
    else
        log_error "不支援的作業系統"
        exit 1
    fi
    log_info "偵測到作業系統: $OS"
}

# 檢查是否為root用戶
check_root() {
    if [ "$EUID" -ne 0 ]; then 
        log_error "請使用 root 權限執行此腳本"
        log_info "使用 'sudo $0' 執行"
        exit 1
    fi
}

# 顯示歡迎信息
show_banner() {
    echo -e "${BLUE}"
    echo "################################################################"
    echo "#                                                              #"
    echo "#              CRETECH-PHISH 一鍵部署腳本                      #"
    echo "#                                                              #"
    echo "#              社交工程安全測試平台                              #"
    echo "#              版本: 1.0.0                                    #"
    echo "#                                                              #"
    echo "################################################################"
    echo -e "${NC}"
}

# 更新系統
update_system() {
    log_info "更新系統套件..."
    if [ "$OS" = "ubuntu" ]; then
        $PKG_MANAGER update -y
        $PKG_MANAGER upgrade -y
    else
        $PKG_MANAGER update -y
        $PKG_MANAGER upgrade -y
    fi
}

# 安裝基本套件
install_basic_packages() {
    log_info "安裝基本套件..."
    
    if [ "$OS" = "ubuntu" ]; then
        $PKG_MANAGER install -y \
            curl \
            wget \
            unzip \
            git \
            software-properties-common \
            ca-certificates \
            gnupg \
            lsb-release
    else
        $PKG_MANAGER install -y \
            curl \
            wget \
            unzip \
            git \
            epel-release \
            yum-utils
    fi
}

# 安裝 Nginx
install_nginx() {
    log_info "安裝 Nginx..."
    
    if [ "$OS" = "ubuntu" ]; then
        $PKG_MANAGER install -y nginx
    else
        $PKG_MANAGER install -y nginx
    fi
    
    systemctl enable nginx
    systemctl start nginx
    
    # 檢查 Nginx 狀態
    if systemctl is-active --quiet nginx; then
        log_info "Nginx 安裝成功並已啟動"
    else
        log_error "Nginx 啟動失敗"
        exit 1
    fi
}

# 安裝 PHP 8.1
install_php() {
    log_info "安裝 PHP 8.1..."
    
    if [ "$OS" = "ubuntu" ]; then
        # 添加 PHP PPA
        add-apt-repository -y ppa:ondrej/php
        $PKG_MANAGER update -y
        
        $PKG_MANAGER install -y \
            php8.1 \
            php8.1-fpm \
            php8.1-mysql \
            php8.1-json \
            php8.1-curl \
            php8.1-gd \
            php8.1-intl \
            php8.1-bcmath \
            php8.1-bz2 \
            php8.1-readline \
            php8.1-zip \
            php8.1-xml \
            php8.1-mbstring \
            php8.1-opcache \
            php8.1-cli
    else
        # CentOS/RHEL
        $PKG_MANAGER install -y \
            https://dl.fedoraproject.org/pub/epel/epel-release-latest-8.noarch.rpm
        $PKG_MANAGER install -y \
            https://rpms.remirepo.net/enterprise/remi-release-8.rpm
        
        dnf module reset php -y
        dnf module enable php:remi-8.1 -y
        
        $PKG_MANAGER install -y \
            php \
            php-fpm \
            php-mysqlnd \
            php-json \
            php-curl \
            php-gd \
            php-intl \
            php-bcmath \
            php-bz2 \
            php-zip \
            php-xml \
            php-mbstring \
            php-opcache \
            php-cli
    fi
    
    # 啟動 PHP-FPM
    systemctl enable php8.1-fpm 2>/dev/null || systemctl enable php-fpm
    systemctl start php8.1-fpm 2>/dev/null || systemctl start php-fpm
    
    log_info "PHP 8.1 安裝完成"
}

# 安裝 MySQL 8.0
install_mysql() {
    log_info "安裝 MySQL 8.0..."
    
    if [ "$OS" = "ubuntu" ]; then
        # 預設回答 MySQL 安裝問題
        export DEBIAN_FRONTEND=noninteractive
        
        $PKG_MANAGER install -y mysql-server-8.0
        
    else
        # CentOS/RHEL
        $PKG_MANAGER install -y mysql-server
    fi
    
    # 啟動 MySQL
    systemctl enable mysql 2>/dev/null || systemctl enable mysqld
    systemctl start mysql 2>/dev/null || systemctl start mysqld
    
    # 等待 MySQL 啟動
    sleep 10
    
    log_info "MySQL 安裝完成"
}

# 配置 MySQL
configure_mysql() {
    log_info "配置 MySQL..."
    
    # 設置 root 密碼
    mysql -u root -e "ALTER USER 'root'@'localhost' IDENTIFIED BY 'root123';" 2>/dev/null || true
    
    # 創建資料庫和用戶
    mysql -u root -proot123 -e "
        CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
        CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
        GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
        FLUSH PRIVILEGES;
    " 2>/dev/null || {
        # 如果上面失敗，嘗試沒有密碼
        mysql -u root -e "
            ALTER USER 'root'@'localhost' IDENTIFIED BY 'root123';
            CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
            CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
            GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
            FLUSH PRIVILEGES;
        "
    }
    
    log_info "MySQL 配置完成"
}

# 複製項目文件
copy_project_files() {
    log_info "複製項目文件..."
    
    # 創建項目目錄
    mkdir -p $PROJECT_DIR
    
    # 複製文件
    cp -r $CURRENT_DIR/* $PROJECT_DIR/
    
    # 設置權限
    chown -R www-data:www-data $PROJECT_DIR 2>/dev/null || chown -R nginx:nginx $PROJECT_DIR
    chmod -R 755 $PROJECT_DIR
    chmod -R 777 $PROJECT_DIR/uploads
    chmod -R 777 $PROJECT_DIR/tmp
    chmod -R 777 $PROJECT_DIR/cache
    chmod -R 777 $PROJECT_DIR/logs
    
    log_info "項目文件複製完成"
}

# 配置 Nginx
configure_nginx() {
    log_info "配置 Nginx..."
    
    # 備份原始配置
    cp /etc/nginx/nginx.conf /etc/nginx/nginx.conf.backup 2>/dev/null || true
    
    # 創建網站配置
    cat > /etc/nginx/sites-available/cretech-phish << 'EOF'
server {
    listen 80;
    server_name localhost;
    root /var/www/cretech-phish/public;
    index index.php index.html;
    
    # 安全頭
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    
    # 隱藏 Nginx 版本
    server_tokens off;
    
    # 主要路由
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # PHP 處理
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }
    
    # 靜態文件快取
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|pdf|txt)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # 拒絕訪問隱藏文件
    location ~ /\. {
        deny all;
    }
    
    # 拒絕訪問敏感文件
    location ~* \.(log|sql|conf)$ {
        deny all;
    }
    
    # 限制上傳大小
    client_max_body_size 10M;
}
EOF
    
    # 啟用網站
    if [ -d "/etc/nginx/sites-enabled" ]; then
        ln -sf /etc/nginx/sites-available/cretech-phish /etc/nginx/sites-enabled/
        rm -f /etc/nginx/sites-enabled/default
    else
        # CentOS 風格配置
        cp /etc/nginx/sites-available/cretech-phish /etc/nginx/conf.d/cretech-phish.conf
    fi
    
    # 測試配置
    nginx -t
    
    # 重新載入 Nginx
    systemctl reload nginx
    
    log_info "Nginx 配置完成"
}

# 初始化資料庫
initialize_database() {
    log_info "初始化資料庫..."
    
    # 執行資料庫初始化腳本
    cd $PROJECT_DIR
    php database/init.php
    
    log_info "資料庫初始化完成"
}

# 設定防火牆
configure_firewall() {
    log_info "配置防火牆..."
    
    if command -v ufw &> /dev/null; then
        # Ubuntu UFW
        ufw --force enable
        ufw allow 22/tcp
        ufw allow 80/tcp
        ufw allow 443/tcp
    elif command -v firewall-cmd &> /dev/null; then
        # CentOS firewalld
        systemctl enable firewalld
        systemctl start firewalld
        firewall-cmd --permanent --add-service=http
        firewall-cmd --permanent --add-service=https
        firewall-cmd --permanent --add-service=ssh
        firewall-cmd --reload
    fi
    
    log_info "防火牆配置完成"
}

# 安裝 SSL 憑證 (Let's Encrypt)
install_ssl() {
    if [ "$DOMAIN" != "localhost" ]; then
        log_info "安裝 SSL 憑證..."
        
        if [ "$OS" = "ubuntu" ]; then
            $PKG_MANAGER install -y certbot python3-certbot-nginx
        else
            $PKG_MANAGER install -y certbot python3-certbot-nginx
        fi
        
        # 申請憑證
        certbot --nginx -d $DOMAIN --non-interactive --agree-tos --email admin@$DOMAIN
        
        # 設定自動更新
        echo "0 12 * * * /usr/bin/certbot renew --quiet" | crontab -
        
        log_info "SSL 憑證安裝完成"
    fi
}

# 系統優化
optimize_system() {
    log_info "優化系統性能..."
    
    # PHP 優化
    if [ -f "/etc/php/8.1/fpm/php.ini" ]; then
        PHP_INI="/etc/php/8.1/fpm/php.ini"
    else
        PHP_INI="/etc/php.ini"
    fi
    
    # 備份原始配置
    cp $PHP_INI ${PHP_INI}.backup
    
    # 優化 PHP 設定
    sed -i 's/upload_max_filesize = .*/upload_max_filesize = 10M/' $PHP_INI
    sed -i 's/post_max_size = .*/post_max_size = 10M/' $PHP_INI
    sed -i 's/max_execution_time = .*/max_execution_time = 300/' $PHP_INI
    sed -i 's/memory_limit = .*/memory_limit = 256M/' $PHP_INI
    sed -i 's/;date.timezone.*/date.timezone = Asia\/Taipei/' $PHP_INI
    
    # 重啟服務
    systemctl restart php8.1-fpm 2>/dev/null || systemctl restart php-fpm
    systemctl restart nginx
    
    log_info "系統優化完成"
}

# 創建管理腳本
create_management_scripts() {
    log_info "創建管理腳本..."
    
    # 創建服務管理腳本
    cat > /usr/local/bin/cretech-phish << 'EOF'
#!/bin/bash

SERVICES=(nginx mysql php-fpm)
PROJECT_DIR="/var/www/cretech-phish"

case "$1" in
    start)
        echo "啟動 Cretech-PHISH 服務..."
        for service in "${SERVICES[@]}"; do
            systemctl start $service 2>/dev/null || systemctl start ${service/php-fpm/php8.1-fpm}
        done
        echo "服務啟動完成"
        ;;
    stop)
        echo "停止 Cretech-PHISH 服務..."
        for service in "${SERVICES[@]}"; do
            systemctl stop $service 2>/dev/null || systemctl stop ${service/php-fpm/php8.1-fpm}
        done
        echo "服務停止完成"
        ;;
    restart)
        echo "重啟 Cretech-PHISH 服務..."
        for service in "${SERVICES[@]}"; do
            systemctl restart $service 2>/dev/null || systemctl restart ${service/php-fpm/php8.1-fpm}
        done
        echo "服務重啟完成"
        ;;
    status)
        echo "Cretech-PHISH 服務狀態:"
        for service in "${SERVICES[@]}"; do
            if systemctl is-active --quiet $service 2>/dev/null || systemctl is-active --quiet ${service/php-fpm/php8.1-fpm} 2>/dev/null; then
                echo "  $service: 運行中"
            else
                echo "  $service: 已停止"
            fi
        done
        ;;
    update)
        echo "更新 Cretech-PHISH..."
        cd $PROJECT_DIR
        git pull origin main
        chown -R www-data:www-data $PROJECT_DIR 2>/dev/null || chown -R nginx:nginx $PROJECT_DIR
        systemctl restart nginx
        echo "更新完成"
        ;;
    logs)
        echo "查看系統日誌:"
        tail -f $PROJECT_DIR/logs/app.log
        ;;
    backup)
        BACKUP_DIR="/backup/cretech-phish-$(date +%Y%m%d-%H%M%S)"
        mkdir -p $BACKUP_DIR
        
        # 備份文件
        cp -r $PROJECT_DIR $BACKUP_DIR/
        
        # 備份資料庫
        mysqldump -u root -proot123 cretech_phish > $BACKUP_DIR/database.sql
        
        echo "備份完成: $BACKUP_DIR"
        ;;
    *)
        echo "用法: $0 {start|stop|restart|status|update|logs|backup}"
        exit 1
        ;;
esac
EOF
    
    chmod +x /usr/local/bin/cretech-phish
    
    log_info "管理腳本創建完成"
}

# 顯示完成信息
show_completion() {
    echo
    echo -e "${GREEN}################################################################"
    echo "#                                                              #"
    echo "#              🎉 部署完成！                                    #"
    echo "#                                                              #"
    echo "################################################################${NC}"
    echo
    log_info "訪問地址: http://$DOMAIN"
    log_info "管理員帳號: $ADMIN_USER"
    log_info "管理員密碼: $ADMIN_PASS"
    echo
    log_info "系統管理命令:"
    echo "  cretech-phish start    - 啟動服務"
    echo "  cretech-phish stop     - 停止服務" 
    echo "  cretech-phish restart  - 重啟服務"
    echo "  cretech-phish status   - 查看狀態"
    echo "  cretech-phish logs     - 查看日誌"
    echo "  cretech-phish backup   - 備份系統"
    echo
    log_info "項目目錄: $PROJECT_DIR"
    log_info "配置文件: $PROJECT_DIR/config/"
    echo
    log_warn "⚠️  請妥善保管管理員密碼，首次登入後請立即修改！"
    log_warn "⚠️  本系統僅供合法安全測試使用！"
    echo
}

# 主函數
main() {
    show_banner
    check_root
    detect_os
    
    log_info "開始部署 Cretech-PHISH..."
    
    update_system
    install_basic_packages
    install_nginx
    install_php
    install_mysql
    configure_mysql
    copy_project_files
    configure_nginx
    initialize_database
    configure_firewall
    optimize_system
    create_management_scripts
    
    # 如果不是 localhost，安裝 SSL
    if [ "$DOMAIN" != "localhost" ]; then
        install_ssl
    fi
    
    show_completion
}

# 處理參數
while [[ $# -gt 0 ]]; do
    case $1 in
        --domain)
            DOMAIN="$2"
            shift 2
            ;;
        --db-pass)
            DB_PASS="$2"
            shift 2
            ;;
        --admin-pass)
            ADMIN_PASS="$2"
            shift 2
            ;;
        --help)
            echo "用法: $0 [選項]"
            echo "選項:"
            echo "  --domain DOMAIN        設置域名 (預設: localhost)"
            echo "  --db-pass PASSWORD     設置資料庫密碼"
            echo "  --admin-pass PASSWORD  設置管理員密碼"
            echo "  --help                 顯示此幫助信息"
            exit 0
            ;;
        *)
            log_error "未知參數: $1"
            exit 1
            ;;
    esac
done

# 執行主函數
main