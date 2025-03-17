<?php

class Database {
    private $host = "localhost";
    private $db_name = "events_ms";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        if ($this->conn !== null) {
            return $this->conn;
        }

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                )
            );
            return $this->conn;
        } catch(PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            throw new Exception("Failed to connect to database. Please try again later.");
        }
    }
}