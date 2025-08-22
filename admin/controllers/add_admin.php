<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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
            throw new Exception("กรุณากรอก{$field}");
        }
    }
    
    // Generate admin_id with EMP + 4 random digits
    $admin_id = generateUniqueEmployeeCode($pdo);
    
    // Hash password
    $hashed_password = password_hash($input_data['password'], PASSWORD_DEFAULT);
    
    // Prepare insert statement
    $sql = "INSERT INTO admin (admin_id, fullname, password, position, department, phone, status, created_at, updated_at) 
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
        // Get the inserted admin data
        $stmt = $pdo->prepare("SELECT admin_id, fullname, position, department, phone, status, created_at FROM admin WHERE admin_id = ?");
        $stmt->execute([$admin_id]);
        $new_admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
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
        $stmt = $pdo->prepare("SELECT admin_id FROM admin WHERE admin_id = ?");
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
        
        $stmt = $pdo->prepare("SELECT admin_id FROM admin WHERE admin_id = ?");
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
    $stmt = $pdo->prepare("SELECT admin_id FROM admin WHERE admin_id = ?");
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