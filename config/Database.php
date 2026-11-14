<?php
require_once __DIR__ . '/environment.php';
class Database {
    public $conn;
    public function getConnection() {
        if ($this->conn instanceof PDO) { return $this->conn; }
        $this->conn = new PDO(
            'mysql:host=' . app_env('DB_HOST', '127.0.0.1') .
            ';port=' . app_env('DB_PORT', '3306') .
            ';dbname=' . app_env('DB_NAME', 'greentech') . ';charset=utf8mb4',
            app_env('DB_USER', 'greentech'), app_env('DB_PASSWORD'),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
             PDO::ATTR_EMULATE_PREPARES => false]
        );
        return $this->conn;
    }
}
