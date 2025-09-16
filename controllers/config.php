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

// SMTP configuration for sending emails (PHPMailer)
// Gmail SMTP configuration
$smtp_config = [
    'enabled' => true, // เปิดใช้งาน SMTP
    'host' => 'smtp.gmail.com', // Gmail SMTP server
    'port' => 587, // TLS port for Gmail
    'username' => 'prawitchaya.game@gmail.com', // Gmail address
    'password' => 'fvci trvk mrno khdl', // App password (not regular password)
    'encryption' => 'tls', // Use TLS encryption
    'from_email' => 'prawitchaya.game@gmail.com', // Sender email
    'from_name' => 'ช้างเหล็กไทย', // Sender name (fixed Thai encoding)
    'reply_to_email' => 'prawitchaya.game@gmail.com',
    'reply_to_name' => 'ช้างเหล็กไทย Support'
];
?>