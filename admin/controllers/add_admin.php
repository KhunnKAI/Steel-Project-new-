<?php
// Include config file for database connection and session management
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

// Check if user is logged in (optional - uncomment if needed)
// requireLogin();

try {
    // Use the PDO connection from config.php
    // $pdo is already available from config.php
    
    // Check if request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }
    
    // Get JSON input
    $json_input = file_get_contents('php://input');
    $input_data = json_decode($json_input, true);
    
    // Validate required fields
    $required_fields = ['fullname', 'password', 'position', 'department'];
    foreach ($required_fields as $field) {
        if (empty($input_data[$field])) {
            throw new Exception("กรุณากรอก $field");
        }
    }
    
    // Validate optional phone number
    if (isset($input_data['phone']) && !empty($input_data['phone'])) {
        if (!validatePhoneNumber($input_data['phone'])) {
            throw new Exception('รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง');
        }
    }
    
    // Generate admin_id with EMP + 4 random digits
    $admin_id = generateUniqueEmployeeCode($pdo);
    
    // Hash password
    $hashed_password = password_hash($input_data['password'], PASSWORD_DEFAULT);
    
    // Prepare insert statement
    $sql = "INSERT INTO Admin (admin_id, fullname, password, position, department, phone, status, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    
    $stmt = $pdo->prepare($sql);
    
    // Execute insert
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
        // Get the inserted admin data (excluding password)
        $stmt = $pdo->prepare("SELECT admin_id, fullname, position, department, phone, status, created_at FROM Admin WHERE admin_id = ?");
        $stmt->execute([$admin_id]);
        $new_admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Format the response
        if ($new_admin) {
            $new_admin['created_at_formatted'] = date('d/m/Y H:i', strtotime($new_admin['created_at']));
            
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
            
            $new_admin['department_thai'] = $dept_thai[$new_admin['department']] ?? $new_admin['department'];
            $new_admin['position_thai'] = $position_thai[$new_admin['position']] ?? $new_admin['position'];
            $new_admin['status_thai'] = $new_admin['status'] === 'active' ? 'ใช้งานอยู่' : 'ไม่ได้ใช้งาน';
        }
        
        // Log the addition
        logAdminChange($_SESSION['admin_id'] ?? 'SYSTEM', 'ADD', [
            'new_admin_id' => $admin_id,
            'fullname' => $input_data['fullname'],
            'position' => $input_data['position'],
            'department' => $input_data['department']
        ], $pdo);
        
        echo json_encode([
            'success' => true,
            'message' => 'เพิ่มผู้ดูแลเรียบร้อยแล้ว',
            'data' => $new_admin,
            'admin_id' => $admin_id
        ], JSON_UNESCAPED_UNICODE);
    } else {
        throw new Exception('ไม่สามารถเพิ่มผู้ดูแลได้');
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

/**
 * Generate unique employee code with format EMP + 4 random digits
 * @param PDO $pdo Database connection
 * @return string Unique employee code
 */
function generateUniqueEmployeeCode($pdo) {
    $max_attempts = 100; // Prevent infinite loop
    $attempt = 0;
    
    do {
        // Generate random 4-digit number
        $random_number = str_pad(mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        $admin_id = 'EMP' . $random_number;
        
        // Check if this ID already exists
        $stmt = $pdo->prepare("SELECT admin_id FROM Admin WHERE admin_id = ?");
        $stmt->execute([$admin_id]);
        $exists = $stmt->fetch();
        
        $attempt++;
        
        // If ID doesn't exist, we can use it
        if (!$exists) {
            return $admin_id;
        }
        
    } while ($attempt < $max_attempts);
    
    // If we've reached max attempts, throw an exception
    throw new Exception('ไม่สามารถสร้างรหัสพนักงานที่ไม่ซ้ำได้ กรุณาลองใหม่');
}

/**
 * Log admin changes (for audit trail)
 */
function logAdminChange($admin_id, $action, $details, $pdo) {
    try {
        // Create admin_log table if it doesn't exist
        $pdo->exec("CREATE TABLE IF NOT EXISTS admin_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id VARCHAR(20) NOT NULL,
            action VARCHAR(50) NOT NULL,
            details JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        $log_stmt = $pdo->prepare("INSERT INTO admin_log (admin_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
        $log_stmt->execute([$admin_id, $action, json_encode($details)]);
    } catch (Exception $e) {
        // Log silently fails - don't break main operation
        error_log("Admin log error: " . $e->getMessage());
    }
}

/**
 * Validate phone number format (Thai format)
 */
function validatePhoneNumber($phone) {
    if (empty($phone)) return true; // Phone is optional
    
    // Remove all non-digits
    $clean_phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Check if it's a valid Thai mobile number (06X, 08X, 09X)
    if (preg_match('/^0[6-9][0-9]{8}$/', $clean_phone)) {
        return true;
    }
    
    // Check if it's a valid Thai landline (02X, 03X, 04X, 05X)
    if (preg_match('/^0[2-5][0-9]{7}$/', $clean_phone)) {
        return true;
    }
    
    return false;
}

/**
 * Alternative function to generate employee code with timestamp fallback
 * This ensures uniqueness even if random generation fails
 * @param PDO $pdo Database connection
 * @return string Unique employee code
 */
function generateUniqueEmployeeCodeWithFallback($pdo) {
    $max_attempts = 50;
    $attempt = 0;
    
    // First try random generation
    do {
        $random_number = str_pad(mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        $admin_id = 'EMP' . $random_number;
        
        $stmt = $pdo->prepare("SELECT admin_id FROM Admin WHERE admin_id = ?");
        $stmt->execute([$admin_id]);
        $exists = $stmt->fetch();
        
        if (!$exists) {
            return $admin_id;
        }
        
        $attempt++;
    } while ($attempt < $max_attempts);
    
    // Fallback: use timestamp-based generation
    $timestamp = time();
    $last_four_digits = substr($timestamp, -4);
    $admin_id = 'EMP' . $last_four_digits;
    
    // Check if timestamp-based ID exists
    $stmt = $pdo->prepare("SELECT admin_id FROM Admin WHERE admin_id = ?");
    $stmt->execute([$admin_id]);
    $exists = $stmt->fetch();
    
    if (!$exists) {
        return $admin_id;
    }
    
    // Final fallback: use microseconds
    $microtime = (int)(microtime(true) * 1000);
    $last_four_digits = substr($microtime, -4);
    $admin_id = 'EMP' . $last_four_digits;
    
    return $admin_id;
}
?>