<?php
// ========================
// LOGOUT
// ========================
// ออกจากระบบและทำลายเซสชั่น

session_start();
require_once 'config.php';

// ========================
// FUNCTION: บันทึก Logout
// ========================
function logLogout($adminId) {
    if ($adminId) {
        error_log("User logout: " . $adminId . " at " . date('Y-m-d H:i:s'));
    }
}

// ========================
// FUNCTION: ทำลายเซสชั่น
// ========================
function destroySession() {
    $_SESSION = array();

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
}

// ========================
// FUNCTION: ส่งการตอบกลับ
// ========================
function sendResponse($isAjax) {
    $responseData = [
        'success' => true,
        'message' => 'ออกจากระบบเรียบร้อยแล้ว',
        'redirect' => '../login_admin.html?logout=success'
    ];

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($responseData, JSON_UNESCAPED_UNICODE);
    } else {
        header("Location: ../login_admin.html?logout=success");
    }
}

// เก็บ Admin ID ก่อนทำลายเซสชั่น
$adminId = $_SESSION['admin_id'] ?? null;

// บันทึก Logout
logLogout($adminId);

// ทำลายเซสชั่น
destroySession();

// ตรวจสอบประเภท Request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// ส่งการตอบกลับ
sendResponse($isAjax);
?>