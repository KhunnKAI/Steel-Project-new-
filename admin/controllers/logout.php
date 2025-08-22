<?php
session_start();
require_once 'config.php';

// Log logout attempt (optional)
if (isset($_SESSION['admin_id'])) {
    error_log("User logout: " . $_SESSION['admin_id'] . " at " . date('Y-m-d H:i:s'));
}

// Check if it's an AJAX request
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Destroy all session data
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Response based on request type
if ($is_ajax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'message' => 'ออกจากระบบเรียบร้อยแล้ว',
        'redirect' => '../login_admin.html?logout=success'
    ], JSON_UNESCAPED_UNICODE);
} else {
    // Regular redirect
    header("Location: ../login_admin.html?logout=success");
    exit();
}
?>