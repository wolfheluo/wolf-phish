<?php

class Database {
    private static $host = 'localhost';
    private static $dbname = 'cretech_phish';
    private static $username = 'phish_user';
    private static $password = 'phish_password_2023';
    private static $connection = null;

    public static function getConnection() {
        if (self::$connection === null) {
            try {
                // 首先嘗試不指定資料庫名稱連接，用於創建資料庫
                $dsn = "mysql:host=" . self::$host . ";charset=utf8mb4";
                self::$connection = new PDO(
                    $dsn,
                    self::$username,
                    self::$password,
                    array(
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                    )
                );
                
                // 創建資料庫（如果不存在）
                self::$connection->exec("CREATE DATABASE IF NOT EXISTS " . self::$dbname . " CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
                
                // 切換到目標資料庫
                self::$connection->exec("USE " . self::$dbname);
                
            } catch (PDOException $e) {
                // 如果上面的方法失敗，嘗試直接連接到指定的資料庫
                try {
                    self::$connection = new PDO(
                        "mysql:host=" . self::$host . ";dbname=" . self::$dbname . ";charset=utf8mb4",
                        self::$username,
                        self::$password,
                        array(
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                        )
                    );
                } catch (PDOException $e2) {
                    echo "資料庫連接失敗: " . $e2->getMessage() . "\n";
                    echo "請檢查:\n";
                    echo "1. MySQL 服務是否運行: systemctl status mysql\n";
                    echo "2. 資料庫用戶是否存在: SELECT User, Host FROM mysql.user WHERE User='phish_user';\n";
                    echo "3. 用戶權限是否正確: SHOW GRANTS FOR 'phish_user'@'localhost';\n";
                    echo "4. 資料庫是否存在: SHOW DATABASES LIKE 'cretech_phish';\n";
                    die("Database connection failed: " . $e2->getMessage());
                }
            }
        }
        return self::$connection;
    }

    public static function query($sql, $params = array()) {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetch($sql, $params = array()) {
        $stmt = self::query($sql, $params);
        return $stmt->fetch();
    }

    public static function fetchAll($sql, $params = array()) {
        $stmt = self::query($sql, $params);
        return $stmt->fetchAll();
    }

    public static function fetchValue($sql, $params = array()) {
        $stmt = self::query($sql, $params);
        return $stmt->fetchColumn();
    }

    public static function lastInsertId() {
        return self::getConnection()->lastInsertId();
    }
}