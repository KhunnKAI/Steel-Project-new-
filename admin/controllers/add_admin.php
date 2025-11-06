<?php
// ========================
// INITIALIZATION
// ========================
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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
    // FUNCTION: ตรวจสอบ request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }
    
    // FUNCTION: ดึงและ validate JSON input
    $json_input = file_get_contents('php://input');
    $input_data = json_decode($json_input, true);
    
    $required_fields = ['fullname', 'password', 'position', 'department'];
    foreach ($required_fields as $field) {
        if (empty($input_data[$field])) {
            throw new Exception("กรุณากรอก $field");
        }
    }
    
    // FUNCTION: ตรวจสอบหมายเลขโทรศัพท์ (ถ้ามี)
    if (isset($input_data['phone']) && !empty($input_data['phone'])) {
        if (!validatePhoneNumber($input_data['phone'])) {
            throw new Exception('รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง');
        }
    }
    
    // FUNCTION: สร้างรหัสผู้ดำเนินการ (admin_id)
    if (!empty($input_data['admin_id'])) {
        $admin_id = $input_data['admin_id'];
        $check_stmt = $pdo->prepare("SELECT admin_id FROM Admin WHERE admin_id = ?");
        $check_stmt->execute([$admin_id]);
        if ($check_stmt->fetch()) {
            $admin_id = generateUniqueEmployeeCode($pdo);
        }
    } else {
        $admin_id = generateUniqueEmployeeCode($pdo);
    }
    
    // FUNCTION: Hash password
    $hashed_password = password_hash($input_data['password'], PASSWORD_DEFAULT);
    
    // FUNCTION: Insert admin ใหม่
    $sql = "INSERT INTO Admin (admin_id, fullname, password, position, department, phone, status, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $admin_id,
        $input_data['fullname'],
        $hashed_password,
        $input_data['position'],
        $input_data['department'],
        $input_data['phone'] ?? null,
        $input_data['status'] ?? 'active'
    ]);
    
    if ($result) {
        // FUNCTION: ดึงข้อมูล admin ที่เพิ่มใหม่
        $stmt = $pdo->prepare("SELECT admin_id, fullname, position, department, phone, status, created_at FROM Admin WHERE admin_id = ?");
        $stmt->execute([$admin_id]);
        $new_admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($new_admin) {
            $new_admin['created_at_formatted'] = date('d/m/Y H:i', strtotime($new_admin['created_at']));
            
            $dept_thai = [
                'management' => 'บริหาร', 'sales' => 'ขาย', 'warehouse' => 'คลังสินค้า',
                'logistics' => 'ขนส่ง', 'accounting' => 'บัญชี', 'it' => 'เทคโนโลยีสารสนเทศ'
            ];
            $position_thai = [
                'manager' => 'ผู้จัดการ', 'sales' => 'พนักงานขาย', 'warehouse' => 'พนักงานคลัง',
                'shipping' => 'พนักงานขนส่ง', 'accounting' => 'พนักงานบัญชี', 'super' => 'ผู้ดูแลระบบ'
            ];
            
            $new_admin['department_thai'] = $dept_thai[$new_admin['department']] ?? $new_admin['department'];
            $new_admin['position_thai'] = $position_thai[$new_admin['position']] ?? $new_admin['position'];
            $new_admin['status_thai'] = $new_admin['status'] === 'active' ? 'ใช้งานอยู่' : 'ไม่ได้ใช้งาน';
        }
        
        // FUNCTION: บันทึกการเพิ่ม (audit log)
        logAdminChange($_SESSION['admin_id'] ?? 'SYSTEM', 'ADD', [
            'new_admin_id' => $admin_id,
            'fullname' => $input_data['fullname'],
            'position' => $input_data['position']
        ], $pdo);
        
        echo json_encode([
            'success' => true,
            'message' => 'เพิ่มผู้ดำเนินการเรียบร้อยแล้ว',
            'data' => $new_admin,
            'admin_id' => $admin_id
        ], JSON_UNESCAPED_UNICODE);
    } else {
        throw new Exception('ไม่สามารถเพิ่มผู้ดำเนินการได้');
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
// FUNCTION: สร้างรหัสผู้ดำเนินการอนন่ที่ (EMP + 4 digits)
function generateUniqueEmployeeCode($pdo) {
    $max_attempts = 100;
    $attempt = 0;
    
    do {
        $random_number = str_pad(mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        $admin_id = 'EMP' . $random_number;
        
        $stmt = $pdo->prepare("SELECT admin_id FROM Admin WHERE admin_id = ?");
        $stmt->execute([$admin_id]);
        $exists = $stmt->fetch();
        
        $attempt++;
        
        if (!$exists) {
            return $admin_id;
        }
        
    } while ($attempt < $max_attempts);
    
    throw new Exception('ไม่สามารถสร้างรหัสผู้ดำเนินการได้');
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