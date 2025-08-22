<?php
// Turn off all error reporting to prevent HTML output
error_reporting(0);
ini_set('display_errors', 0);

session_start();

// Set content type to JSON immediately
header('Content-Type: application/json; charset=utf-8');

try {
    // Check if config.php exists
    if (!file_exists('config.php')) {
        throw new Exception('Config file not found');
    }
    
    require_once 'config.php';
    
    // Check if database connection exists
    if (!isset($pdo)) {
        throw new Exception('Database connection failed');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'ระบบไม่พร้อมใช้งาน: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate input
if (!$data || !isset($data['username']) || !isset($data['password'])) {
    echo json_encode([
        'success' => false,
        'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$username = trim($data['username']);
$password = trim($data['password']);

// Basic validation
if (empty($username)) {
    echo json_encode([
        'success' => false,
        'message' => 'กรุณากรอกรหัสพนักงาน'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

if (empty($password)) {
    echo json_encode([
        'success' => false,
        'message' => 'กรุณากรอกรหัสผ่าน'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

try {
    // Query to check admin credentials
    $stmt = $pdo->prepare("
        SELECT admin_id, fullname, password, position, department, status 
        FROM Admin 
        WHERE admin_id = ? AND status = 'active'
    ");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if (!$admin) {
        // Log failed login attempt (optional)
        error_log("Failed login attempt for username: " . $username . " at " . date('Y-m-d H:i:s'));
        
        echo json_encode([
            'success' => false,
            'message' => 'รหัสพนักงานหรือรหัสผ่านไม่ถูกต้อง'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Verify password (assuming plain text for now, should use password_hash in production)
    if ($password !== $admin['password']) {
        // Log failed login attempt (optional)
        error_log("Failed password attempt for username: " . $username . " at " . date('Y-m-d H:i:s'));
        
        echo json_encode([
            'success' => false,
            'message' => 'รหัสพนักงานหรือรหัสผ่านไม่ถูกต้อง'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Check if account is active
    if ($admin['status'] !== 'active') {
        echo json_encode([
            'success' => false,
            'message' => 'บัญชีผู้ใช้ถูกระงับ กรุณาติดต่อผู้ดูแลระบบ'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Login successful - Create session
    session_regenerate_id(true); // Prevent session fixation
    
    $_SESSION['admin_id'] = $admin['admin_id'];
    $_SESSION['fullname'] = $admin['fullname'];
    $_SESSION['position'] = $admin['position'];
    $_SESSION['department'] = $admin['department'];
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();

    // Update last login (optional - add last_login column to Admin table)
    try {
        $update_stmt = $pdo->prepare("UPDATE Admin SET updated_at = NOW() WHERE admin_id = ?");
        $update_stmt->execute([$admin['admin_id']]);
    } catch (PDOException $e) {
        // Log error but don't fail login
        error_log("Error updating last login: " . $e->getMessage());
    }

    // Log successful login (optional)
    error_log("Successful login for username: " . $username . " at " . date('Y-m-d H:i:s'));

    echo json_encode([
        'success' => true,
        'message' => 'เข้าสู่ระบบสำเร็จ',
        'redirect' => 'dashboard_admin.php',
        'user' => [
            'admin_id' => $admin['admin_id'],
            'fullname' => $admin['fullname'],
            'position' => $admin['position'],
            'department' => $admin['department']
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    // Log database error
    error_log("Database error during login: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในระบบ กรุณาลองใหม่อีกครั้ง'
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    // Log general error
    error_log("General error during login: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดที่ไม่คาดคิด กรุณาลองใหม่อีกครั้ง'
    ], JSON_UNESCAPED_UNICODE);
}
?>