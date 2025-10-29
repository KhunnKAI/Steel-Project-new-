<?php
// ========================
// GET CURRENT USER - API ENDPOINT
// ========================
// ดึงข้อมูลผู้ใช้ปัจจุบันและสิทธิ์การเข้าถึง

error_reporting(0);
ini_set('display_errors', 0);

require_once 'config.php';

header('Content-Type: application/json');

try {
    // ========================
    // GET CURRENT ADMIN
    // ========================
    $current_admin = getCurrentAdmin();

    if (!$current_admin) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'ไม่พบข้อมูลผู้ใช้ กรุณาเข้าสู่ระบบใหม่'
        ]);
        exit;
    }

    // ========================
    // ROLE PERMISSIONS CONFIG
    // ========================
    $role_permissions = [
        'manager' => ['dashboard', 'products', 'orders', 'admins', 'reports'],
        'sales' => ['dashboard', 'products', 'orders'],
        'warehouse' => ['dashboard', 'products', 'orders'],
        'shipping' => ['dashboard', 'orders'],
        'accounting' => ['dashboard', 'orders', 'reports'],
        'super' => ['dashboard', 'products', 'orders', 'admins', 'reports']
    ];

    // ========================
    // FUNCTION: ดึงสิทธิ์ผู้ใช้
    // ========================
    function getUserPermissions($admin, $rolePermissions) {
        $permissions = [];

        if (isset($admin['permissions']) && !empty($admin['permissions'])) {
            if (is_string($admin['permissions'])) {
                $permissions = json_decode($admin['permissions'], true) ?: [];
            } else {
                $permissions = $admin['permissions'];
            }
        } else {
            $permissions = $rolePermissions[$admin['position']] ?? ['dashboard'];
        }

        return $permissions;
    }

    // ได้รับสิทธิ์
    $permissions = getUserPermissions($current_admin, $role_permissions);

    // ========================
    // SEND RESPONSE
    // ========================
    echo json_encode([
        'success' => true,
        'data' => [
            'admin_id' => $current_admin['admin_id'],
            'fullname' => $current_admin['fullname'],
            'position' => $current_admin['position'],
            'department' => $current_admin['department'],
            'permissions' => $permissions
        ]
    ]);

} catch (Exception $e) {
    error_log("Get current user error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในการโหลดข้อมูลผู้ใช้'
    ]);
}