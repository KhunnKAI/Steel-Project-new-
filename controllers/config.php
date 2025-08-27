<?php
// config.php - Database configuration
class Database {
    private $host = "26.94.44.21:3307";
    private $db_name = "SteelShop";
    private $username = "user";
    private $password = "12345678";
    public $conn;

    // private $host = "localhost";
    // private $db_name = "teststeel";
    // private $username = "root";
    // private $password = "";
    // public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                                $this->username, $this->password);
            $this->conn->exec("set names utf8mb4");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            die("Database connection failed. Please check your configuration.");
        }
        return $this->conn;
    }
}

// Create global PDO connection for backward compatibility
try {
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    error_log("Database initialization error: " . $e->getMessage());
    die("Database initialization failed.");
}
?>