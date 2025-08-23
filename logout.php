<?php
// logout.php - ระบบออกจากระบบ (แบบ redirect โดยตรง)
// Prevent any output before headers
ob_start();

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_name('user_session');
    session_start();
}

// Include required files
try {
    if (!file_exists('config.php')) {
        throw new Exception('Configuration file not found');
    }
    require_once 'config.php';
} catch (Exception $e) {
    error_log("Logout error: " . $e->getMessage());
}

/**
 * ฟังก์ชันออกจากระบบ
 */
function performLogout() {
    try {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // บันทึก log การออกจากระบบ
        if (isset($_SESSION['user_id']) || isset($_COOKIE['user_id'])) {
            $user_id = $_SESSION['user_id'] ?? $_COOKIE['user_id'] ?? '';
            $user_name = $_SESSION['name'] ?? $_COOKIE['name'] ?? '';
            error_log("User logout - ID: $user_id, Name: $user_name, Time: " . date('Y-m-d H:i:s'));
        }

        // Clear all session variables
        $_SESSION = array();

        // Delete the session cookie
        if (isset($_COOKIE[session_name()])) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Destroy the session
        session_destroy();

        // Delete custom authentication cookies
        $cookies_to_delete = ['user_session', 'user_id', 'email', 'name', 'phone', 'login_time'];
        foreach ($cookies_to_delete as $cookie) {
            if (isset($_COOKIE[$cookie])) {
                // Set cookie to expire in the past
                setcookie($cookie, '', time() - 3600, '/', '', false, false);
                // Also try with secure and httponly flags
                setcookie($cookie, '', time() - 3600, '/', '', 
                    isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', true);
            }
        }

        return true;
    } catch (Exception $e) {
        error_log("Logout error: " . $e->getMessage());
        return false;
    }
}

// ทำการ logout
$logout_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    // ตรวจสอบว่าผู้ใช้ล็อกอินอยู่หรือไม่
    $was_logged_in = isset($_SESSION['user_id']) || isset($_COOKIE['user_id']);
    
    if ($was_logged_in) {
        $logout_success = performLogout();
    } else {
        $logout_success = true; // ถือว่าสำเร็จถ้าไม่ได้ล็อกอินอยู่แล้ว
    }
}

// หากเป็น AJAX request ให้ return JSON
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    // Clean output buffer
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    
    echo json_encode([
        'success' => $logout_success,
        'message' => $logout_success ? 'ออกจากระบบสำเร็จ' : 'เกิดข้อผิดพลาดในการออกจากระบบ',
        'redirect' => 'home.php'
    ]);
    
    ob_end_flush();
    exit();
}

// สำหรับ regular request ให้ redirect ไปหน้า home โดยตรง
ob_end_clean();

// เพิ่ม query parameter เพื่อแสดง message ใน home page
$redirect_url = 'home.php';
if ($logout_success) {
    $redirect_url .= '?logout=success&message=' . urlencode('ออกจากระบบสำเร็จ');
} else {
    $redirect_url .= '?logout=error&message=' . urlencode('เกิดข้อผิดพลาดในการออกจากระบบ');
}

// Redirect ไปหน้า home
header('Location: ' . $redirect_url);
exit();
?>