<?php
// ========================
// CHECK SESSION
// ========================
// ตรวจสอบสถานะเซสชั่นและข้อมูล Admin ปัจจุบัน

error_reporting(0);
ini_set('display_errors', 0);

ob_start();
header('Content-Type: application/json; charset=utf-8');

try {
    // ตรวจสอบไฟล์ config
    if (!file_exists('config.php')) {
        throw new Exception('Config file not found');
    }

    require_once 'config.php';

    if (!isset($pdo)) {
        throw new Exception('Database connection failed');
    }

    ob_clean();

    // ========================
    // FUNCTION: สร้างการตอบกลับ
    // ========================
    function createResponse($loggedIn, $adminInfo = null, $timeRemaining = 0, $loginTime = 0, $lastActivity = 0) {
        return [
            'logged_in' => $loggedIn,
            'admin_info' => $adminInfo,
            'time_remaining' => $timeRemaining,
            'login_time' => $loginTime,
            'last_activity' => $lastActivity
        ];
    }

    // ========================
    // FUNCTION: ดึงข้อมูล Admin ปัจจุบัน
    // ========================
    function getAdminData($pdo, $adminId) {
        $stmt = $pdo->prepare("
            SELECT admin_id, fullname, position, department
            FROM Admin
            WHERE admin_id = ? AND status = 'active'
        ");
        $stmt->execute([$adminId]);
        return $stmt->fetch();
    }

    // ตรวจสอบเซสชั่น
    if (isLoggedIn()) {
        $admin = getAdminData($pdo, $_SESSION['admin_id']);

        if ($admin) {
            $timeRemaining = 36000 - (time() - $_SESSION['login_time']);
            $response = createResponse(
                true,
                [
                    'admin_id' => $admin['admin_id'],
                    'fullname' => $admin['fullname'],
                    'position' => $admin['position'],
                    'department' => $admin['department']
                ],
                max(0, $timeRemaining),
                $_SESSION['login_time'],
                $_SESSION['last_activity'] ?? $_SESSION['login_time']
            );
        } else {
            $response = createResponse(false);
        }
    } else {
        $response = createResponse(false);
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(createResponse(false), JSON_UNESCAPED_UNICODE);
}

ob_end_flush();
exit();
?>