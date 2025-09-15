-- Cretech-PHISH 資料庫結構
-- 創建時間: 2025-09-15

CREATE DATABASE IF NOT EXISTS cretech_phish CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE cretech_phish;

-- 1. 用戶表
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    full_name VARCHAR(100),
    department VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. 郵件模板表
CREATE TABLE email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    subject VARCHAR(255) NOT NULL,
    html_content LONGTEXT NOT NULL,
    created_by INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- 3. 釣魚網站模板表
CREATE TABLE phishing_sites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    html_content LONGTEXT NOT NULL,
    site_type ENUM('login', 'download', 'form', 'redirect') DEFAULT 'login',
    created_by INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- 4. 測試專案表
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_code VARCHAR(50) NOT NULL UNIQUE,
    project_name VARCHAR(100) NOT NULL,
    description TEXT,
    test_username VARCHAR(50) NOT NULL,
    test_password VARCHAR(255) NOT NULL,
    email_template_id INT,
    phishing_site_id INT,
    sender_name VARCHAR(100) NOT NULL,
    sender_email VARCHAR(100) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    track_pixel_url VARCHAR(255),
    track_zip_url VARCHAR(255),
    phish_url VARCHAR(255),
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    send_start_time TIME DEFAULT '09:00:00',
    send_end_time TIME DEFAULT '17:00:00',
    status ENUM('pending', 'active', 'completed', 'paused') DEFAULT 'pending',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (email_template_id) REFERENCES email_templates(id) ON DELETE SET NULL,
    FOREIGN KEY (phishing_site_id) REFERENCES phishing_sites(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- 5. 目標郵件清單表
CREATE TABLE target_emails (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    email VARCHAR(100) NOT NULL,
    name VARCHAR(100),
    department VARCHAR(100),
    is_sent BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP NULL,
    send_status ENUM('pending', 'sent', 'failed', 'bounced') DEFAULT 'pending',
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_project_email (project_id, email),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- 6. 郵件開啟追蹤表
CREATE TABLE track_pixel_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    email VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    opened_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_project_email (project_id, email),
    INDEX idx_opened_at (opened_at),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- 7. URL點擊追蹤表
CREATE TABLE track_url_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    email VARCHAR(100) NOT NULL,
    url VARCHAR(500),
    ip_address VARCHAR(45),
    user_agent TEXT,
    referrer VARCHAR(500),
    clicked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_project_email (project_id, email),
    INDEX idx_clicked_at (clicked_at),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- 8. 附件下載追蹤表
CREATE TABLE track_zip_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    email VARCHAR(100) NOT NULL,
    filename VARCHAR(255),
    ip_address VARCHAR(45),
    user_agent TEXT,
    downloaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_project_email (project_id, email),
    INDEX idx_downloaded_at (downloaded_at),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- 9. 數據提交追蹤表
CREATE TABLE track_data_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    email VARCHAR(100) NOT NULL,
    form_data JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_project_email (project_id, email),
    INDEX idx_submitted_at (submitted_at),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- 10. 系統日誌表
CREATE TABLE system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level ENUM('INFO', 'WARNING', 'ERROR', 'DEBUG') DEFAULT 'INFO',
    message TEXT NOT NULL,
    context JSON,
    user_id INT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_level (level),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- 11. 會話表
CREATE TABLE sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    data TEXT,
    INDEX idx_user_id (user_id),
    INDEX idx_last_activity (last_activity),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 插入預設管理員帳戶
INSERT INTO users (username, password, email, role, full_name) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@cretech.com', 'admin', 'System Administrator');

-- 插入範例郵件模板
INSERT INTO email_templates (name, description, subject, html_content, created_by) VALUES
('銀行詐騙模板', '模擬銀行安全通知的釣魚郵件', '【重要】您的帳戶安全驗證', 
'<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>帳戶安全驗證</title>
</head>
<body>
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
        <h2 style="color: #d32f2f;">重要安全通知</h2>
        <p>親愛的客戶，</p>
        <p>我們偵測到您的帳戶有異常登入嘗試，為了保護您的帳戶安全，請立即點擊以下連結進行身份驗證：</p>
        <p style="text-align: center; margin: 30px 0;">
            <a href="{TRACK_URL}" style="background-color: #1976d2; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px;">立即驗證</a>
        </p>
        <p>如果您沒有進行任何登入操作，請忽略此郵件。</p>
        <p>此連結將在24小時後失效。</p>
        <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;">
        <p style="font-size: 12px; color: #666;">
            此郵件由系統自動發送，請勿回覆。<br>
            如有疑問，請聯繫客服專線：0800-123-456
        </p>
        <img src="{TRACK_PIXEL}" width="1" height="1" style="display: none;">
    </div>
</body>
</html>', 1);

-- 插入範例釣魚網站
INSERT INTO phishing_sites (name, description, html_content, site_type, created_by) VALUES
('銀行登入頁面', '模擬銀行登入頁面', 
'<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>網路銀行登入</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f5f5f5; margin: 0; padding: 20px; }
        .login-container { max-width: 400px; margin: 100px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .logo { text-align: center; margin-bottom: 30px; font-size: 24px; font-weight: bold; color: #1976d2; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="password"] { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .login-btn { width: 100%; padding: 12px; background-color: #1976d2; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; }
        .login-btn:hover { background-color: #1565c0; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">網路銀行</div>
        <form id="loginForm" method="post">
            <div class="form-group">
                <label>使用者代號:</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>密碼:</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="login-btn">登入</button>
        </form>
    </div>
    <script>
        document.getElementById("loginForm").onsubmit = function(e) {
            e.preventDefault();
            alert("感謝您的參與！這是一個安全意識測試。請記住：\n1. 不要在可疑網站輸入個人資訊\n2. 檢查網址的真實性\n3. 當收到可疑郵件時，請聯繫IT部門");
            window.location.href = "https://www.cretech.com";
        };
    </script>
</body>
</html>', 'login', 1);