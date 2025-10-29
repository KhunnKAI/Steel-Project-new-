<?php
// ตั้งค่า Session ก่อน session_start()
ini_set('session.gc_maxlifetime', 14400); // 4 hours = 14400 seconds
session_set_cookie_params(14400); // 4 hours cookie lifetime

// ini_set('session.gc_maxlifetime', 300); // 5 minutes = 300 seconds
// session_set_cookie_params(300); // 5 minutes cookie lifetime

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// การตั้งค่าฐานข้อมูล

$host = '26.94.44.21:3307';
$username = 'user';
$password = '12345678';
$db_name = 'SteelShop';

// $host = 'localhost';
// $username = 'root';
// $password = '';
// $db_name = 'teststeel';

try {
    // สร้าง PDO connection พร้อมตั้งค่า charset
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// ฟังก์ชันตรวจสอบว่า admin login หรือไม่
function isLoggedIn() {
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['login_time'])) {
        return false;
    }
    
    // ตรวจสอบว่า session หมดอายุหรือไม่ (4 ชั่วโมง)
    if (time() - $_SESSION['login_time'] > 14400) {
        // หมดอายุ ล้าง session
        session_unset();
        session_destroy();
        return false;
    }
    
    return true;
}

// ฟังก์ชันบังคับ login
function requireLogin() {
    if (!isLoggedIn()) {
        // ตรวจสอบว่า session หมดอายุหรือไม่
        $redirect_params = isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 14400) ? '?timeout=1' : '?access=denied';
        
        // ล้าง session หากยัง active
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
        
        header("Location: login_admin.html" . $redirect_params);
        exit();
    }
    
    // อัปเดตเวลา last_activity
    $_SESSION['last_activity'] = time();
}

// ฟังก์ชันดึงข้อมูล admin ปัจจุบัน
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
