<?php

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__) . '/models/EmailTemplate.php';
require_once dirname(__DIR__) . '/models/Project.php';
require_once dirname(__DIR__) . '/utils/Utils.php';

class EmailSender {
    private $emailTemplate;
    private $projectModel;
    private $smtpHost;
    private $smtpPort;
    private $smtpUser;
    private $smtpPass;
    
    public function __construct() {
        $this->emailTemplate = new EmailTemplate();
        $this->projectModel = new Project();
        
        // 從配置文件讀取SMTP設置
        $this->smtpHost = Config::SMTP_HOST ?? 'localhost';
        $this->smtpPort = Config::SMTP_PORT ?? 587;
        $this->smtpUser = Config::SMTP_USER ?? '';
        $this->smtpPass = Config::SMTP_PASS ?? '';
    }
    
    /**
     * 發送釣魚郵件
     */
    public function sendPhishingEmail($projectId, $templateId, $recipientEmail, $recipientName = '') {
        try {
            // 獲取專案和模板信息
            $project = $this->projectModel->find($projectId);
            $template = $this->emailTemplate->find($templateId);
            
            if (!$project || !$template) {
                throw new Exception('Project or template not found');
            }
            
            // 生成追蹤URL
            $trackingUrls = $this->generateTrackingUrls($projectId, $recipientEmail);
            
            // 處理郵件內容
            $subject = $this->processTemplate($template['subject'], $recipientName, $trackingUrls);
            $body = $this->processTemplate($template['body'], $recipientName, $trackingUrls);
            
            // 發送郵件
            $result = $this->sendEmail(
                $recipientEmail,
                $template['from_email'],
                $template['from_name'],
                $subject,
                $body,
                $trackingUrls['pixel']
            );
            
            if ($result) {
                $this->logEmailSent($projectId, $templateId, $recipientEmail, 'sent');
                return true;
            } else {
                $this->logEmailSent($projectId, $templateId, $recipientEmail, 'failed');
                return false;
            }
            
        } catch (Exception $e) {
            $this->logError('Send email error', $e->getMessage(), [
                'project_id' => $projectId,
                'template_id' => $templateId,
                'email' => $recipientEmail
            ]);
            
            $this->logEmailSent($projectId, $templateId, $recipientEmail, 'error', $e->getMessage());
            
            throw $e;
        }
    }
    
    /**
     * 批量發送釣魚郵件
     */
    public function sendBulkPhishingEmails($projectId, $templateId, $recipients, $delaySeconds = 5) {
        $results = [
            'sent' => 0,
            'failed' => 0,
            'total' => count($recipients)
        ];
        
        foreach ($recipients as $recipient) {
            try {
                $email = is_array($recipient) ? $recipient['email'] : $recipient;
                $name = is_array($recipient) ? ($recipient['name'] ?? '') : '';
                
                $success = $this->sendPhishingEmail($projectId, $templateId, $email, $name);
                
                if ($success) {
                    $results['sent']++;
                } else {
                    $results['failed']++;
                }
                
                // 添加延遲以避免被標記為垃圾郵件
                if ($delaySeconds > 0 && $results['sent'] + $results['failed'] < $results['total']) {
                    sleep($delaySeconds);
                }
                
            } catch (Exception $e) {
                $results['failed']++;
                $this->logError('Bulk email error', $e->getMessage(), [
                    'project_id' => $projectId,
                    'template_id' => $templateId,
                    'email' => $email ?? 'unknown'
                ]);
            }
        }
        
        return $results;
    }
    
    /**
     * 生成追蹤URL
     */
    private function generateTrackingUrls($projectId, $email) {
        $baseUrl = Config::BASE_URL ?? 'http://localhost';
        $encodedProjectId = urlencode($projectId);
        $encodedEmail = urlencode($email);
        
        return [
            'pixel' => "{$baseUrl}/track/pixel?project_id={$encodedProjectId}&email={$encodedEmail}",
            'url' => "{$baseUrl}/track/url?project_id={$encodedProjectId}&email={$encodedEmail}",
            'zip' => "{$baseUrl}/track/zip?project_id={$encodedProjectId}&email={$encodedEmail}&file=attachment.zip",
            'data' => "{$baseUrl}/track/data?project_id={$encodedProjectId}&email={$encodedEmail}"
        ];
    }
    
    /**
     * 處理郵件模板變量
     */
    private function processTemplate($content, $recipientName, $trackingUrls) {
        // 替換基本變量
        $content = str_replace('{name}', $recipientName, $content);
        $content = str_replace('{email}', '', $content); // 出於安全考慮不顯示郵件地址
        
        // 替換追蹤URL
        $content = str_replace('{tracking_pixel}', $trackingUrls['pixel'], $content);
        $content = str_replace('{tracking_url}', $trackingUrls['url'], $content);
        $content = str_replace('{attachment_url}', $trackingUrls['zip'], $content);
        
        // 添加追蹤像素（如果內容是HTML）
        if (strpos($content, '<html') !== false || strpos($content, '<body') !== false) {
            $pixelImg = '<img src="' . $trackingUrls['pixel'] . '" width="1" height="1" style="display:none;" alt="">';
            $content = str_replace('</body>', $pixelImg . '</body>', $content);
        }
        
        return $content;
    }
    
    /**
     * 發送郵件（使用PHP mail函數或SMTP）
     */
    private function sendEmail($to, $fromEmail, $fromName, $subject, $body, $pixelUrl) {
        // 設置郵件頭
        $headers = [];
        $headers[] = "From: {$fromName} <{$fromEmail}>";
        $headers[] = "Reply-To: {$fromEmail}";
        $headers[] = "X-Mailer: Cretech-PHISH";
        $headers[] = "MIME-Version: 1.0";
        
        // 檢查是否為HTML內容
        if (strpos($body, '<html') !== false || strpos($body, '<body') !== false) {
            $headers[] = "Content-Type: text/html; charset=UTF-8";
        } else {
            $headers[] = "Content-Type: text/plain; charset=UTF-8";
        }
        
        $headerString = implode("\r\n", $headers);
        
        // 如果配置了SMTP，使用SMTP發送
        if ($this->smtpHost && $this->smtpHost !== 'localhost') {
            return $this->sendViaSMTP($to, $fromEmail, $fromName, $subject, $body, $headers);
        } else {
            // 使用系統的mail函數
            return mail($to, $subject, $body, $headerString);
        }
    }
    
    /**
     * 通過SMTP發送郵件
     */
    private function sendViaSMTP($to, $fromEmail, $fromName, $subject, $body, $headers) {
        try {
            // 這裡可以使用PHPMailer或其他SMTP庫
            // 為了簡化，這裡使用基本的socket連接
            
            $socket = fsockopen($this->smtpHost, $this->smtpPort, $errno, $errstr, 30);
            
            if (!$socket) {
                throw new Exception("SMTP connection failed: {$errstr} ({$errno})");
            }
            
            // SMTP對話
            $this->smtpCommand($socket, null, "220"); // 等待歡迎訊息
            $this->smtpCommand($socket, "HELO " . $_SERVER['HTTP_HOST'], "250");
            
            if ($this->smtpUser) {
                $this->smtpCommand($socket, "AUTH LOGIN", "334");
                $this->smtpCommand($socket, base64_encode($this->smtpUser), "334");
                $this->smtpCommand($socket, base64_encode($this->smtpPass), "235");
            }
            
            $this->smtpCommand($socket, "MAIL FROM: <{$fromEmail}>", "250");
            $this->smtpCommand($socket, "RCPT TO: <{$to}>", "250");
            $this->smtpCommand($socket, "DATA", "354");
            
            // 發送郵件內容
            $mailData = "Subject: {$subject}\r\n";
            foreach ($headers as $header) {
                $mailData .= $header . "\r\n";
            }
            $mailData .= "\r\n" . $body . "\r\n.\r\n";
            
            fwrite($socket, $mailData);
            $response = fgets($socket, 515);
            
            $this->smtpCommand($socket, "QUIT", "221");
            fclose($socket);
            
            return strpos($response, '250') === 0;
            
        } catch (Exception $e) {
            $this->logError('SMTP send error', $e->getMessage());
            return false;
        }
    }
    
    /**
     * SMTP命令處理
     */
    private function smtpCommand($socket, $command, $expectedCode) {
        if ($command !== null) {
            fwrite($socket, $command . "\r\n");
        }
        
        $response = fgets($socket, 515);
        
        if ($expectedCode && strpos($response, $expectedCode) !== 0) {
            throw new Exception("SMTP error: Expected {$expectedCode}, got {$response}");
        }
        
        return $response;
    }
    
    /**
     * 記錄已發送郵件
     */
    private function logEmailSent($projectId, $templateId, $recipientEmail, $status, $errorMessage = null) {
        try {
            $sql = "INSERT INTO sent_emails (project_id, template_id, recipient_email, status, error_message, sent_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            
            Database::query($sql, [
                $projectId,
                $templateId,
                $recipientEmail,
                $status,
                $errorMessage
            ]);
            
        } catch (Exception $e) {
            $this->logError('Log email error', $e->getMessage());
        }
    }
    
    /**
     * 記錄錯誤日誌
     */
    private function logError($title, $message, $context = []) {
        error_log("[Cretech-PHISH EmailSender] {$title}: {$message} | Context: " . json_encode($context));
    }
    
    /**
     * 驗證郵件地址
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * 從CSV文件導入收件人列表
     */
    public static function importRecipientsFromCSV($csvFile) {
        $recipients = [];
        
        if (($handle = fopen($csvFile, "r")) !== FALSE) {
            $header = fgetcsv($handle, 1000, ","); // 讀取標題行
            
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $recipient = [];
                
                foreach ($header as $index => $columnName) {
                    $recipient[$columnName] = $data[$index] ?? '';
                }
                
                // 驗證必需的email字段
                if (isset($recipient['email']) && self::validateEmail($recipient['email'])) {
                    $recipients[] = $recipient;
                }
            }
            
            fclose($handle);
        }
        
        return $recipients;
    }
    
    /**
     * 獲取發送統計
     */
    public function getSendingStats($projectId) {
        $sql = "SELECT 
                    status,
                    COUNT(*) as count
                FROM sent_emails 
                WHERE project_id = ? 
                GROUP BY status";
        
        $results = Database::fetchAll($sql, [$projectId]);
        
        $stats = [
            'sent' => 0,
            'failed' => 0,
            'error' => 0,
            'total' => 0
        ];
        
        foreach ($results as $result) {
            $stats[$result['status']] = (int)$result['count'];
            $stats['total'] += (int)$result['count'];
        }
        
        return $stats;
    }
    
    /**
     * 獲取發送歷史
     */
    public function getSendingHistory($projectId, $limit = 100) {
        $sql = "SELECT 
                    se.*,
                    et.name as template_name
                FROM sent_emails se
                LEFT JOIN email_templates et ON se.template_id = et.id
                WHERE se.project_id = ?
                ORDER BY se.sent_at DESC
                LIMIT ?";
        
        return Database::fetchAll($sql, [$projectId, $limit]);
    }
}