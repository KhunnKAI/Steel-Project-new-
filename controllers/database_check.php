<?php
// database_check.php - ตรวจสอบ database และ table structure
header('Content-Type: text/html; charset=utf-8');

echo "<h2>Database Connection Test</h2>";

try {
    // Test config file
    if (!file_exists('config.php')) {
        die("❌ config.php file not found!");
    }
    
    require_once 'config.php';
    
    echo "✅ config.php loaded successfully<br>";
    
    // Test database connection
    if (!isset($pdo) || !$pdo) {
        die("❌ PDO connection not established!");
    }
    
    echo "✅ PDO connection established<br>";
    
    // Test basic query
    $stmt = $pdo->query("SELECT 1");
    if ($stmt) {
        echo "✅ Database query test successful<br>";
    }
    
    // Check if Users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'Users'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Users table exists<br>";
        
        // Show table structure
        echo "<h3>Users Table Structure:</h3>";
        $stmt = $pdo->query("DESCRIBE Users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check required columns
        $required_columns = ['user_id', 'name', 'email', 'phone', 'password_hash', 'created_at', 'updated_at'];
        $existing_columns = array_column($columns, 'Field');
        
        echo "<h3>Column Check:</h3>";
        foreach ($required_columns as $req_col) {
            if (in_array($req_col, $existing_columns)) {
                echo "✅ {$req_col}<br>";
            } else {
                echo "❌ {$req_col} - MISSING!<br>";
            }
        }
        
        // Show current data count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM Users");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<br>📊 Current users count: " . $count['count'];
        
    } else {
        echo "❌ Users table does not exist!<br>";
        echo "<h3>Create Users Table SQL:</h3>";
        echo "<pre>";
        echo "CREATE TABLE Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);";
        echo "</pre>";
    }
    
    // Test UserController
    echo "<h3>UserController Test:</h3>";
    if (file_exists('UserController.php')) {
        require_once 'UserController.php';
        $userController = new UserController($pdo);
        echo "✅ UserController loaded successfully<br>";
        
        // Test table structure check
        $debug = $userController->debugTableStructure();
        echo "Table structure debug: <pre>" . print_r($debug, true) . "</pre>";
        
    } else {
        echo "❌ UserController.php not found<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}

echo "<br><h3>Database Configuration:</h3>";
echo "Host: localhost:3307<br>";
echo "Database: SteelShop<br>";
echo "Username: user<br>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Current Time: " . date('Y-m-d H:i:s') . "<br>";
?>