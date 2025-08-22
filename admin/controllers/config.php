<?php
// ตั้งค่า Session ก่อน session_start()
ini_set('session.gc_maxlifetime', 14400); // 4 hours = 14400 seconds
session_set_cookie_params(14400); // 4 hours cookie lifetime

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// การตั้งค่าฐานข้อมูล
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'teststeel';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Function to check if user is logged in
function isLoggedIn() {
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['login_time'])) {
        return false;
    }
    
    // Check if session has expired (4 hours)
    if (time() - $_SESSION['login_time'] > 14400) {
        // Session expired, destroy it
        session_unset();
        session_destroy();
        return false;
    }
    
    return true;
}

// Function to require login
function requireLogin() {
    if (!isLoggedIn()) {
        // Check if it's timeout
        $redirect_params = isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 14400) ? '?timeout=1' : '?access=denied';
        
        // Destroy session if exists
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
        
        header("Location: login_admin.html" . $redirect_params);
        exit();
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
}

// Function to get current admin info
function getCurrentAdmin() {
    global $pdo;
    
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM Admin WHERE admin_id = ? AND status = 'active'");
        $stmt->execute([$_SESSION['admin_id']]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        return null;
    }
}
?>