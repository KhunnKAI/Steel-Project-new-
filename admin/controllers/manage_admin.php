<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'teststeel';

try {
    // Create database connection
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'PUT') {
        // UPDATE ADMIN
        $json_input = file_get_contents('php://input');
        $input_data = json_decode($json_input, true);
        
        if (!isset($input_data['admin_id'])) {
            throw new Exception('กรุณาระบุรหัสผู้ดูแล');
        }
        
        $admin_id = $input_data['admin_id'];
        
        // Check if admin exists
        $check_stmt = $pdo->prepare("SELECT admin_id FROM admin WHERE admin_id = ?");
        $check_stmt->execute([$admin_id]);
        if (!$check_stmt->fetch()) {
            throw new Exception('ไม่พบผู้ดูแลรายการนี้');
        }
        
        // Build dynamic update query
        $update_fields = [];
        $update_params = [];
        
        $allowed_fields = ['fullname', 'position', 'department', 'phone', 'status'];
        
        foreach ($allowed_fields as $field) {
            if (isset($input_data[$field])) {
                $update_fields[] = "$field = ?";
                $update_params[] = $input_data[$field];
            }
        }
        
        // Handle password update separately
        if (isset($input_data['password']) && !empty($input_data['password'])) {
            $update_fields[] = "password = ?";
            $update_params[] = password_hash($input_data['password'], PASSWORD_DEFAULT);
        }
        
        if (empty($update_fields)) {
            throw new Exception('ไม่มีข้อมูลที่ต้องการอัพเดท');
        }
        
        // Add updated_at and admin_id to params
        $update_fields[] = "updated_at = NOW()";
        $update_params[] = $admin_id;
        
        $sql = "UPDATE admin SET " . implode(', ', $update_fields) . " WHERE admin_id = ?";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($update_params);
        
        if ($result && $stmt->rowCount() > 0) {
            // Get updated admin data
            $get_stmt = $pdo->prepare("SELECT admin_id, fullname, position, department, phone, status, created_at, updated_at FROM admin WHERE admin_id = ?");
            $get_stmt->execute([$admin_id]);
            $updated_admin = $get_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Format response
            if ($updated_admin) {
                $updated_admin['created_at_formatted'] = date('d/m/Y H:i', strtotime($updated_admin['created_at']));
                $updated_admin['updated_at_formatted'] = date('d/m/Y H:i', strtotime($updated_admin['updated_at']));
                
                // Add Thai translations
                $dept_thai = [
                    'management' => 'บริหาร',
                    'sales' => 'ขาย', 
                    'warehouse' => 'คลังสินค้า',
                    'logistics' => 'ขนส่ง',
                    'accounting' => 'บัญชี',
                    'it' => 'เทคโนโลยีสารสนเทศ'
                ];
                
                $position_thai = [
                    'manager' => 'ผู้จัดการ',
                    'sales' => 'พนักงานขาย',
                    'warehouse' => 'พนักงานคลัง', 
                    'shipping' => 'พนักงานขนส่ง',
                    'accounting' => 'พนักงานบัญชี',
                    'super' => 'ผู้ดูแลระบบ'
                ];
                
                $updated_admin['department_thai'] = $dept_thai[$updated_admin['department']] ?? $updated_admin['department'];
                $updated_admin['position_thai'] = $position_thai[$updated_admin['position']] ?? $updated_admin['position'];
                $updated_admin['status_thai'] = $updated_admin['status'] === 'active' ? 'ใช้งานอยู่' : 'ไม่ได้ใช้งาน';
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'อัพเดทข้อมูลผู้ดูแลเรียบร้อยแล้ว',
                'data' => $updated_admin
            ], JSON_UNESCAPED_UNICODE);
            
        } else {
            throw new Exception('ไม่สามารถอัพเดทข้อมูลได้ หรือไม่มีการเปลี่ยนแปลงข้อมูล');
        }
        
    } elseif ($method === 'DELETE') {
        // DELETE ADMIN
        $admin_id = $_GET['admin_id'] ?? null;
        
        if (!$admin_id) {
            throw new Exception('กรุณาระบุรหัสผู้ดูแล');
        }
        
        // Check if admin exists and get info before deletion
        $check_stmt = $pdo->prepare("SELECT admin_id, fullname, position FROM admin WHERE admin_id = ?");
        $check_stmt->execute([$admin_id]);
        $admin_to_delete = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$admin_to_delete) {
            throw new Exception('ไม่พบผู้ดูแลรายการนี้');
        }
        
        // Prevent deletion of super admin or critical roles (optional security)
        if ($admin_to_delete['position'] === 'super' || $admin_to_delete['admin_id'] === 'SYS001') {
            throw new Exception('ไม่สามารถลบผู้ดูแลระบบหลักได้');
        }
        
        // Delete admin
        $delete_stmt = $pdo->prepare("DELETE FROM admin WHERE admin_id = ?");
        $result = $delete_stmt->execute([$admin_id]);
        
        if ($result && $delete_stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => "ลบผู้ดูแล '{$admin_to_delete['fullname']}' เรียบร้อยแล้ว",
                'deleted_admin' => $admin_to_delete
            ], JSON_UNESCAPED_UNICODE);
        } else {
            throw new Exception('ไม่สามารถลบผู้ดูแลได้');
        }
        
    } else {
        throw new Exception('Method not allowed');
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

// Additional utility functions for admin management

/**
 * Validate admin status change
 */
function validateStatusChange($admin_id, $new_status, $pdo) {
    // Check if changing super admin status
    $stmt = $pdo->prepare("SELECT position FROM admin WHERE admin_id = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch();
    
    if ($admin && $admin['position'] === 'super' && $new_status === 'inactive') {
        // Count active super admins
        $count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM admin WHERE position = 'super' AND status = 'active' AND admin_id != ?");
        $count_stmt->execute([$admin_id]);
        $count = $count_stmt->fetch()['count'];
        
        if ($count < 1) {
            throw new Exception('ต้องมีผู้ดูแลระบบที่ใช้งานอยู่อย่างน้อย 1 คน');
        }
    }
    
    return true;
}

/**
 * Log admin changes (for audit trail)
 */
function logAdminChange($admin_id, $action, $details, $pdo) {
    try {
        $log_stmt = $pdo->prepare("INSERT INTO admin_log (admin_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
        $log_stmt->execute([$admin_id, $action, json_encode($details)]);
    } catch (Exception $e) {
        // Log silently fails - don't break main operation
        error_log("Admin log error: " . $e->getMessage());
    }
}

/**
 * Generate secure random password
 */
function generateSecurePassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    
    return $password;
}

/**
 * Validate phone number format
 */
function validatePhoneNumber($phone) {
    if (empty($phone)) return true; // Phone is optional
    
    // Remove all non-digits
    $clean_phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Check if it's a valid Thai mobile number
    if (preg_match('/^0[6-9][0-9]{8}$/', $clean_phone)) {
        return true;
    }
    
    // Check if it's a valid Thai landline
    if (preg_match('/^0[2-5][0-9]{7}$/', $clean_phone)) {
        return true;
    }
    
    return false;
}
?>