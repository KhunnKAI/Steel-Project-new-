<?php
// ========================
// DATABASE & SESSION CONFIG
// ========================

// ========================
// SESSION CONFIGURATION
// ========================
ini_set('session.gc_maxlifetime', 14400); // 4 hours = 14400 seconds
session_set_cookie_params(14400); // 4 hours cookie lifetime

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ========================
// DATABASE CONFIGURATION
// ========================
$host = '26.94.44.21:3307';
$username = 'user';
$password = '12345678';
$db_name = 'SteelShop';

// ========================
// DATABASE CONNECTION
// ========================
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db_name;charset=utf8mb4",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// ========================
// FUNCTION: ตรวจสอบ Login Status
// ========================
function isLoggedIn() {
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['login_time'])) {
        return false;
    }

    // ตรวจสอบเวลาหมดอายุ (4 ชั่วโมง)
    if (time() - $_SESSION['login_time'] > 14400) {
        session_unset();
        session_destroy();
        return false;
    }

    return true;
}

// ========================
// FUNCTION: บังคับเข้าสู่ระบบ
// ========================
function requireLogin() {
    if (!isLoggedIn()) {
        $redirect_params = isset($_SESSION['login_time']) &&
                          (time() - $_SESSION['login_time'] > 14400)
                          ? '?timeout=1'
                          : '?access=denied';

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }

        header("Location: login_admin.html" . $redirect_params);
        exit();
    }

    $_SESSION['last_activity'] = time();
}

// ========================
// FUNCTION: ดึงข้อมูล Admin ปัจจุบัน
// ========================
function getCurrentAdmin() {
    global $pdo;

    if (!isLoggedIn()) {
        return null;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT * FROM Admin
            WHERE admin_id = ? AND status = 'active'
        ");
        $stmt->execute([$_SESSION['admin_id']]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}
?>