<?php

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {

        // config/database.php を読み込む
        $config = require __DIR__ . '/../config/database.php';

        $host = $config['host'];
        $db   = $config['dbname'];
        $user = $config['user'];
        $pass = $config['password'];

        $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

        $this->pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance->pdo;
    }
}
