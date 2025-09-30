# Cretech-PHISH 部署指南

本項目支援兩種部署方式：**aaPanel 生產環境部署**（推薦）和 **Docker 開發環境**。

## 🎯 推薦：aaPanel 生產環境部署

### 適用場景
- 生產環境部署
- 穩定的服務器環境
- 需要完整的系統管理功能

### 部署步驟
1. 確保服務器已安裝 aaPanel 並配置好 LNMP 環境
2. 執行安裝腳本：
   ```bash
   sudo bash install.sh
   ```
3. 按照腳本輸出的詳細說明在 aaPanel 中配置：
   - MySQL 資料庫和用戶
   - Nginx 網站配置
   - PHP 參數調整
   - Supervisor 後台任務（可選）
   - 防火牆和 SSL 設置

### 優勢
- ✅ 生產環境穩定性高
- ✅ 完整的系統監控和管理
- ✅ 簡單的圖形化管理界面
- ✅ 自動備份和恢復功能
- ✅ 內建安全防護措施

## 🛠️ 備選：Docker 開發環境

### 適用場景
- 本地開發和測試
- 快速環境搭建
- 多環境隔離需求

### 部署步驟
1. 確保已安裝 Docker 和 Docker Compose
2. 複製項目文件到本地
3. 執行開發環境：
   ```bash
   docker-compose up -d
   ```
4. 訪問應用：
   - 主應用：http://localhost:9000 (通過 Nginx 代理)
   - 郵件測試：http://localhost:8025 (MailHog)
   - 資料庫：localhost:3306

### 注意事項
- ⚠️ Docker 配置已簡化，主要用於開發環境
- ⚠️ 不包含完整的生產環境安全配置
- ⚠️ 需要額外配置 Nginx 反向代理
- ⚠️ 郵件服務使用 MailHog（僅用於測試）

## 📋 配置對比

| 功能 | aaPanel 部署 | Docker 部署 |
|------|-------------|-------------|
| Web 伺服器 | aaPanel 管理的 Nginx | 需要外部 Nginx |
| 資料庫 | aaPanel MySQL | Docker MySQL |
| PHP 管理 | aaPanel PHP 管理器 | Docker PHP-FPM |
| 後台任務 | Supervisor/Cron | 需要手動配置 |
| 日誌管理 | aaPanel 日誌查看器 | Docker logs |
| 備份恢復 | aaPanel 自動備份 | 手動管理 |
| SSL 證書 | aaPanel 一鍵申請 | 手動配置 |
| 防火牆 | aaPanel 安全面板 | 系統防火牆 |
| 監控報警 | aaPanel 監控 | 需要額外工具 |

## 🔧 開發環境 Docker 配置說明

如果選擇使用 Docker 進行開發，請注意以下配置：

### 服務說明
- **app**: PHP-FPM 服務，處理 PHP 應用邏輯
- **database**: MySQL 8.0 資料庫
- **redis**: Redis 緩存服務
- **mailhog**: 郵件測試服務

### 端口映射
- `9000`: PHP-FPM 端口
- `3306`: MySQL 資料庫
- `6379`: Redis 服務
- `1025`: SMTP 測試端口
- `8025`: MailHog Web 界面

### 環境變數
```env
APP_ENV=development
DB_HOST=database
DB_NAME=cretech_phish
DB_USER=phish_user
DB_PASSWORD=phish_secure_password_2023
```

## 🚀 建議的部署流程

### 生產環境（aaPanel）
1. 準備服務器並安裝 aaPanel
2. 在 aaPanel 中安裝 LNMP 環境
3. 上傳項目文件到服務器
4. 執行 `install.sh` 腳本
5. 按照輸出說明配置 aaPanel
6. 測試應用功能
7. 配置 SSL 證書和域名
8. 設置定期備份

### 開發環境（Docker）
1. 克隆項目代碼
2. 執行 `docker-compose up -d`
3. 等待所有服務啟動
4. 訪問 http://localhost:8025 查看郵件
5. 進行開發和測試

## 📞 技術支援

如果在部署過程中遇到問題：

1. **aaPanel 部署問題**：
   - 檢查 aaPanel 日誌
   - 確認 LNMP 環境正常運作
   - 驗證資料庫連接設置

2. **Docker 部署問題**：
   - 檢查 Docker 容器狀態：`docker-compose ps`
   - 查看容器日誌：`docker-compose logs [service_name]`
   - 確認端口沒有被占用

3. **通用問題**：
   - 檢查 PHP 錯誤日誌
   - 驗證資料庫連接
   - 確認文件權限設置

## 🔐 安全建議

無論選擇哪種部署方式，都請注意：

- 修改預設管理員密碼
- 配置 HTTPS 證書
- 設置適當的防火牆規則
- 定期更新系統和應用
- 配置日誌監控和備份策略
- 限制資料庫訪問權限
- 定期進行安全審計