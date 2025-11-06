<?php
// ========================
// INITIALIZATION
// ========================
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ========================
// MAIN LOGIC
// ========================
try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    // ========================
    // UPDATE ADMIN (PUT)
    // ========================
    if ($method === 'PUT') {
        // FUNCTION: ดึงและ validate ข้อมูล JSON input
        $json_input = file_get_contents('php://input');
        $input_data = json_decode($json_input, true);
        
        if (!isset($input_data['admin_id'])) {
            throw new Exception('กรุณาระบุรหัสผู้ดำเนินการ');
        }
        
        $admin_id = $input_data['admin_id'];
        
        // FUNCTION: ตรวจสอบว่า admin ที่ต้องการแก้ไขมีอยู่ในระบบ
        $check_stmt = $pdo->prepare("SELECT admin_id FROM Admin WHERE admin_id = ?");
        $check_stmt->execute([$admin_id]);
        if (!$check_stmt->fetch()) {
            throw new Exception('ไม่พบผู้ดำเนินการรายการนี้');
        }
        
        // FUNCTION: ตรวจสอบการเปลี่ยนแปลงสถานะ (ป้องกันลบ super admin)
        if (isset($input_data['status'])) {
            validateStatusChange($admin_id, $input_data['status'], $pdo);
        }
        
        // FUNCTION: สร้าง update query แบบไดนามิก
        $update_fields = [];
        $update_params = [];
        $allowed_fields = ['fullname', 'position', 'department', 'phone', 'status'];
        
        foreach ($allowed_fields as $field) {
            if (isset($input_data[$field])) {
                $update_fields[] = "$field = ?";
                $update_params[] = $input_data[$field];
            }
        }
        
        // FUNCTION: อัพเดต password หากมีการส่งมา
        if (isset($input_data['password']) && !empty($input_data['password'])) {
            $update_fields[] = "password = ?";
            $update_params[] = password_hash($input_data['password'], PASSWORD_DEFAULT);
        }
        
        if (empty($update_fields)) {
            throw new Exception('ไม่มีข้อมูลที่ต้องการอัพเดตได้');
        }
        
        $update_fields[] = "updated_at = NOW()";
        $update_params[] = $admin_id;
        
        // FUNCTION: ดำเนินการ update
        $sql = "UPDATE Admin SET " . implode(', ', $update_fields) . " WHERE admin_id = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($update_params);
        
        if ($result && $stmt->rowCount() > 0) {
            // FUNCTION: ดึงข้อมูล admin ที่อัพเดตแล้ว
            $get_stmt = $pdo->prepare("SELECT admin_id, fullname, position, department, phone, status, created_at, updated_at FROM Admin WHERE admin_id = ?");
            $get_stmt->execute([$admin_id]);
            $updated_admin = $get_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($updated_admin) {
                $updated_admin['created_at_formatted'] = date('d/m/Y H:i', strtotime($updated_admin['created_at']));
                $updated_admin['updated_at_formatted'] = date('d/m/Y H:i', strtotime($updated_admin['updated_at']));
                
                $dept_thai = [
                    'management' => 'บริหาร', 'sales' => 'ขาย', 'warehouse' => 'คลังสินค้า',
                    'logistics' => 'ขนส่ง', 'accounting' => 'บัญชี', 'it' => 'เทคโนโลยีสารสนเทศ'
                ];
                $position_thai = [
                    'manager' => 'ผู้จัดการ', 'sales' => 'พนักงานขาย', 'warehouse' => 'พนักงานคลัง',
                    'shipping' => 'พนักงานขนส่ง', 'accounting' => 'พนักงานบัญชี', 'super' => 'ผู้ดูแลระบบ'
                ];
                
                $updated_admin['department_thai'] = $dept_thai[$updated_admin['department']] ?? $updated_admin['department'];
                $updated_admin['position_thai'] = $position_thai[$updated_admin['position']] ?? $updated_admin['position'];
                $updated_admin['status_thai'] = $updated_admin['status'] === 'active' ? 'ใช้งานอยู่' : 'ไม่ได้ใช้งาน';
            }
            
            // FUNCTION: บันทึกการแก้ไข (audit log)
            logAdminChange($_SESSION['admin_id'] ?? 'SYSTEM', 'UPDATE', ['target_admin_id' => $admin_id], $pdo);
            
            echo json_encode([
                'success' => true,
                'message' => 'อัพเดตข้อมูลผู้ดำเนินการเรียบร้อยแล้ว',
                'data' => $updated_admin
            ], JSON_UNESCAPED_UNICODE);
        } else {
            throw new Exception('ไม่สามารถอัพเดตข้อมูลได้');
        }
        
    } elseif ($method === 'DELETE') {
        // ========================
        // DELETE ADMIN (DELETE)
        // ========================
        $admin_id = $_GET['admin_id'] ?? null;
        
        if (!$admin_id) {
            throw new Exception('กรุณาระบุรหัสผู้ดำเนินการ');
        }
        
        // FUNCTION: ดึงข้อมูล admin ก่อนลบ
        $check_stmt = $pdo->prepare("SELECT admin_id, fullname, position FROM Admin WHERE admin_id = ?");
        $check_stmt->execute([$admin_id]);
        $admin_to_delete = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$admin_to_delete) {
            throw new Exception('ไม่พบผู้ดำเนินการรายการนี้');
        }
        
        // FUNCTION: ป้องกันลบ super admin คนสุดท้าย
        if ($admin_to_delete['position'] === 'super') {
            $count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM Admin WHERE position = 'super' AND status = 'active'");
            $count_stmt->execute();
            $super_count = $count_stmt->fetch()['count'];
            
            if ($super_count <= 1) {
                throw new Exception('ไม่สามารถลบผู้ดูแลระบบคนสุดท้ายได้');
            }
        }
        
        // FUNCTION: ลบ admin
        $delete_stmt = $pdo->prepare("DELETE FROM Admin WHERE admin_id = ?");
        $result = $delete_stmt->execute([$admin_id]);
        
        if ($result && $delete_stmt->rowCount() > 0) {
            // FUNCTION: บันทึกการลบ (audit log)
            logAdminChange($_SESSION['admin_id'] ?? 'SYSTEM', 'DELETE', ['deleted_admin' => $admin_to_delete], $pdo);
            
            echo json_encode([
                'success' => true,
                'message' => "ลบผู้ดำเนินการ '{$admin_to_delete['fullname']}' เรียบร้อยแล้ว",
                'deleted_admin' => $admin_to_delete
            ], JSON_UNESCAPED_UNICODE);
        } else {
            throw new Exception('ไม่สามารถลบข้อมูลได้');
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

// ========================
// UTILITY FUNCTIONS
// ========================
// FUNCTION: ตรวจสอบความถูกต้องของการเปลี่ยนสถานะ
function validateStatusChange($admin_id, $new_status, $pdo) {
    $stmt = $pdo->prepare("SELECT position FROM Admin WHERE admin_id = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch();
    
    if ($admin && $admin['position'] === 'super' && $new_status === 'inactive') {
        $count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM Admin WHERE position = 'super' AND status = 'active' AND admin_id != ?");
        $count_stmt->execute([$admin_id]);
        $count = $count_stmt->fetch()['count'];
        
        if ($count < 1) {
            throw new Exception('ต้องมีผู้ดูแลระบบที่ใช้งานอยู่อย่างน้อย 1 คน');
        }
    }
    
    return true;
}

// FUNCTION: บันทึกการเปลี่ยนแปลง (audit trail)
function logAdminChange($admin_id, $action, $details, $pdo) {
    try {
        $log_stmt = $pdo->prepare("INSERT INTO admin_log (admin_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
        $log_stmt->execute([$admin_id, $action, json_encode($details)]);
    } catch (Exception $e) {
        error_log("Admin log error: " . $e->getMessage());
    }
}

// FUNCTION: ตรวจสอบหมายเลขโทรศัพท์ (ไทย)
function validatePhoneNumber($phone) {
    if (empty($phone)) return true;
    
    $clean_phone = preg_replace('/[^0-9]/', '', $phone);
    
    if (preg_match('/^0[6-9][0-9]{8}$/', $clean_phone)) {
        return true;
    }
    
    if (preg_match('/^0[2-5][0-9]{7}$/', $clean_phone)) {
        return true;
    }
    
    return false;
}
?>