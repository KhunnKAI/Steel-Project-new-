<?php
// get_current_user.php - Returns current user's permissions and role
require_once 'config.php';

header('Content-Type: application/json');

try {
    // Get current admin from session
    $current_admin = getCurrentAdmin();
    
    if (!$current_admin) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'ไม่พบข้อมูลผู้ใช้ กรุณาเข้าสู่ระบบใหม่'
        ]);
        exit;
    }

    // Default permissions based on role
    $role_permissions = [
        'manager' => ['dashboard', 'products', 'orders', 'admins', 'reports'],
        'sales' => ['dashboard', 'products', 'orders'],
        'warehouse' => ['dashboard', 'products', 'orders'],
        'shipping' => ['dashboard', 'orders'],
        'accounting' => ['dashboard', 'orders', 'reports'],
        'super' => ['dashboard', 'products', 'orders', 'admins', 'reports']
    ];

    // Get user's permissions from database or use default
    $permissions = [];
    
    if (isset($current_admin['permissions']) && !empty($current_admin['permissions'])) {
        // If permissions are stored as JSON string
        if (is_string($current_admin['permissions'])) {
            $permissions = json_decode($current_admin['permissions'], true) ?: [];
        } else {
            $permissions = $current_admin['permissions'];
        }
    } else {
        // Use default permissions based on role
        $permissions = $role_permissions[$current_admin['position']] ?? ['dashboard'];
    }

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