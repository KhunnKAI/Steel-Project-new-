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
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION = [];
    if (isset($_COOKIE[session_name()])) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600,
            $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
    $cookies = ['user_session','user_id','email','name','phone','login_time'];
    foreach ($cookies as $c) {
        setcookie($c, '', time() - 3600, '/', '', false, false);
        setcookie($c, '', time() - 3600, '/', '', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off', true);
    }
    return true;
}

$logout_success = performLogout();
$message = $logout_success ? 'ออกจากระบบสำเร็จ' : 'เกิดข้อผิดพลาดในการออกจากระบบ';
$redirect_url = 'home.php?logout=' . ($logout_success ? 'success' : 'error') . '&message=' . urlencode($message);

// **ลบ header redirect**
ob_end_clean();
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>Logging out...</title>
<script>
    // ล้าง cart และ checkout data
    localStorage.removeItem('shopping_cart');
    localStorage.removeItem('checkout_data');

    // อัพเดท CartManager ถ้ามี
    if (window.cartManager) {
        window.cartManager.clearCart();
    }

    // Redirect หลังล้างทุกอย่าง
    window.location.href = '<?php echo $redirect_url; ?>';
</script>
</head>
<body>
<p>กำลังออกจากระบบ...</p>
</body>
</html>