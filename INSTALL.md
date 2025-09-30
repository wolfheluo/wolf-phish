# 🎯 CRETECH-PHISH 安裝和使用指南

## 📋 系統需求

### 最低需求
- **作業系統**: Linux (Ubuntu 20.04+ / CentOS 7+)
- **PHP**: 8.1 或更高版本
- **MySQL**: 8.0 或更高版本
- **Nginx**: 1.18 或更高版本
- **記憶體**: 2GB RAM (建議 4GB+)
- **儲存**: 10GB 可用空間

### 推薦需求
- **作業系統**: Ubuntu 22.04 LTS
- **PHP**: 8.1 + 必要擴展 (mysqli, gd, curl, zip, mbstring)
- **MySQL**: 8.0 + 完整權限
- **Nginx**: 最新穩定版
- **記憶體**: 4GB+ RAM
- **儲存**: 20GB+ SSD

## 🚀 快速部署

### 方式一：一鍵部署腳本 (推薦)

```bash
# 1. 下載項目
git clone https://github.com/wolfheluo/wolf-phish.git
cd wolf-phish

# 2. 執行一鍵部署
sudo chmod +x deploy.sh
sudo ./deploy.sh

# 3. 自定義參數部署 (可選)
sudo ./deploy.sh --domain your-domain.com --admin-pass your-password
```

### 方式二：Docker 部署

```bash
# 1. 確保 Docker 和 Docker Compose 已安裝
docker --version
docker-compose --version

# 2. 執行 Docker 部署
chmod +x deploy-docker.sh
./deploy-docker.sh deploy

# 3. 其他 Docker 管理命令
./deploy-docker.sh start     # 啟動服務
./deploy-docker.sh stop      # 停止服務
./deploy-docker.sh logs      # 查看日誌
./deploy-docker.sh backup    # 備份數據
```

## 🔧 手動部署

### 1. 環境準備

```bash
# Ubuntu/Debian 系統
sudo apt update
sudo apt install nginx php8.1-fpm php8.1-mysql php8.1-curl php8.1-gd \
                 php8.1-json php8.1-zip php8.1-mbstring php8.1-xml \
                 mysql-server git curl

# CentOS/RHEL 系統
sudo yum update
sudo yum install epel-release
sudo yum install nginx php-fpm php-mysql php-curl php-gd \
                 php-json php-zip php-mbstring php-xml \
                 mysql-server git curl
```

### 2. 數據庫配置

```bash
# 啟動 MySQL 服務
sudo systemctl start mysql
sudo systemctl enable mysql

# 創建數據庫和用戶
sudo mysql -u root -p
```

```sql
CREATE DATABASE cretech_phish CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER 'phish_user'@'localhost' IDENTIFIED BY 'phish_password_2023';
GRANT ALL PRIVILEGES ON cretech_phish.* TO 'phish_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. 項目部署

```bash
# 下載項目到 Web 目錄
sudo git clone https://github.com/wolfheluo/wolf-phish.git /var/www/cretech-phish
cd /var/www/cretech-phish

# 設置權限
sudo chown -R www-data:www-data /var/www/cretech-phish
sudo chmod -R 755 /var/www/cretech-phish
sudo chmod -R 777 /var/www/cretech-phish/uploads
sudo chmod -R 777 /var/www/cretech-phish/tmp
sudo chmod -R 777 /var/www/cretech-phish/cache
sudo chmod -R 777 /var/www/cretech-phish/logs

# 初始化數據庫
php database/init.php
```

### 4. Nginx 配置

```bash
# 創建 Nginx 配置文件
sudo nano /etc/nginx/sites-available/cretech-phish
```

```nginx
server {
    listen 80;
    server_name your-domain.com;  # 修改為您的域名
    root /var/www/cretech-phish/public;
    index index.php index.html;
    
    # 安全頭設置
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    
    # 主路由
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
    
    # 拒絕訪問敏感文件
    location ~* \.(log|sql|conf)$ {
        deny all;
    }
    
    client_max_body_size 10M;
}
```

```bash
# 啟用網站
sudo ln -s /etc/nginx/sites-available/cretech-phish /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default  # 移除默認網站

# 測試並重新載入 Nginx
sudo nginx -t
sudo systemctl reload nginx
```

### 5. 啟動服務

```bash
sudo systemctl start nginx
sudo systemctl start php8.1-fpm
sudo systemctl start mysql

sudo systemctl enable nginx
sudo systemctl enable php8.1-fpm
sudo systemctl enable mysql
```

## 🎯 使用指南

### 首次登入

1. 訪問 `http://your-domain.com`
2. 使用默認管理員帳號登入：
   - 用戶名：`admin`
   - 密碼：`admin123`
3. 首次登入後請立即修改密碼！

### 創建釣魚測試項目

#### 步驟 1：準備郵件模板
1. 進入「範本管理」
2. 點擊「新增範本」→「郵件範本」
3. 填寫模板信息：
   - 範本名稱：如「Office 365 安全更新通知」
   - 郵件主旨：如「重要：您的帳戶需要驗證」
   - HTML 內容：設計釣魚郵件內容

#### 步驟 2：準備釣魚網站模板
1. 在「範本管理」中選擇「釣魚網站範本」
2. 創建登錄頁面模板
3. 選擇網站類型（登錄頁面、下載頁面等）

#### 步驟 3：創建測試項目
1. 進入「開始演練」
2. 填寫項目基本信息：
   - 項目代號：自動生成或手動輸入
   - 項目名稱：描述性名稱
   - 描述：測試目的和範圍

3. 配置受測人帳號：
   - 測試帳號：用於釣魚頁面的假帳號
   - 測試密碼：對應的假密碼

4. 上傳目標清單：
   - 準備 CSV 文件，格式：`email,name,department`
   - 範例：`john@company.com,John Doe,IT Department`

5. 選擇郵件模板和發件人信息

6. 配置追蹤選項：
   - ☑ 追蹤像素（郵件開啟）
   - ☑ 壓縮包下載追蹤
   - ☑ 釣魚網站連結

7. 設置時間範圍和發送時間

#### 步驟 4：啟動測試
1. 檢查所有配置
2. 點擊「創建專案」
3. 在「演練狀態」中啟動項目

### 監控測試進度

#### 實時監控
1. 訪問「演練狀態」頁面
2. 查看各項目的進度統計
3. 監控發送狀態、開啟率、點擊率

#### 詳細分析
1. 點擊項目的「查看詳情」
2. 分析目標用戶的具體行為
3. 查看時間線和設備信息

### 生成分析報告

1. 進入「結果查詢」
2. 選擇要分析的項目
3. 查看統計圖表：
   - 關鍵指標概覽
   - 時間線分析
   - 設備類型分佈
   - 風險等級評估

4. 導出報告：
   - 點擊「匯出報告」生成 Excel
   - 點擊「PDF報告」生成 PDF

### CSV 目標清單格式

```csv
email,name,department
john.doe@company.com,John Doe,IT Department
jane.smith@company.com,Jane Smith,HR Department
bob.wilson@company.com,Bob Wilson,Finance Department
```

## 🔒 安全注意事項

### ⚠️ 重要警告

- **本系統僅供合法的安全測試使用**
- **使用前必須獲得組織正式授權**
- **不得用於惡意攻擊或非法活動**
- **遵守當地法律法規和公司政策**

### 安全最佳實踐

1. **權限管理**
   - 定期審查用戶權限
   - 使用強密碼策略
   - 啟用雙因素認證（如需要）

2. **數據保護**
   - 定期備份重要數據
   - 加密敏感信息
   - 限制日誌記錄的敏感內容

3. **網路安全**
   - 使用 HTTPS 加密傳輸
   - 配置防火牆規則
   - 定期更新系統和軟體

4. **合規性**
   - 遵守 GDPR 等隱私法規
   - 建立數據保留政策
   - 記錄安全測試授權文件

## 🛠️ 故障排除

### 常見問題

#### Q: 無法訪問網站
**A:** 檢查以下項目：
```bash
# 檢查 Nginx 狀態
sudo systemctl status nginx

# 檢查 PHP-FPM 狀態
sudo systemctl status php8.1-fpm

# 檢查防火牆
sudo ufw status
sudo firewall-cmd --list-all  # CentOS
```

#### Q: 數據庫連接失敗
**A:** 檢查數據庫配置：
```bash
# 測試數據庫連接
mysql -u phish_user -p cretech_phish

# 檢查 MySQL 狀態
sudo systemctl status mysql

# 查看 MySQL 錯誤日誌
sudo tail -f /var/log/mysql/error.log
```

#### Q: 郵件發送失敗
**A:** 檢查 SMTP 配置：
- 驗證 SMTP 服務器設置
- 檢查防火牆是否阻擋 25/587 端口
- 測試郵件服務器連通性

#### Q: 檔案上傳失敗
**A:** 檢查權限設置：
```bash
# 檢查目錄權限
ls -la /var/www/cretech-phish/

# 重設權限
sudo chown -R www-data:www-data /var/www/cretech-phish/uploads
sudo chmod -R 777 /var/www/cretech-phish/uploads
```

### 日誌檢查

```bash
# 應用日誌
tail -f /var/www/cretech-phish/logs/app.log

# Nginx 日誌
tail -f /var/log/nginx/cretech-phish.error.log
tail -f /var/log/nginx/cretech-phish.access.log

# PHP 日誌
tail -f /var/log/php8.1-fpm.log

# MySQL 日誌
sudo tail -f /var/log/mysql/error.log
```

## 📞 支援與聯繫

### 技術支援
- **問題回報**: [GitHub Issues](https://github.com/wolfheluo/wolf-phish/issues)
- **功能請求**: [GitHub Discussions](https://github.com/wolfheluo/wolf-phish/discussions)
- **郵件支援**: security@cretech.com

### 社群資源
- **官方文檔**: [wiki 頁面](https://github.com/wolfheluo/wolf-phish/wiki)
- **視頻教程**: [YouTube 頻道](https://youtube.com/@cretech-security)
- **更新通知**: [Release 頁面](https://github.com/wolfheluo/wolf-phish/releases)

---

**⚠️ 免責聲明**: 本工具僅供合法的安全測試使用。使用者需承擔使用本工具的所有法律責任。開發團隊不對任何誤用或損害承擔責任。