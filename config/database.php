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
            } catch (PDOException $e) {
                die("Database connection failed: " . $e->getMessage());
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