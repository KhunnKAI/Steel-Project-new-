<?php
// login_process.php - Enhanced version with better error handling
error_reporting(0);
ini_set('display_errors', 0);

session_start();

header('Content-Type: application/json; charset=utf-8');

// Enhanced error logging function
function logError($message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = empty($context) ? '' : ' Context: ' . json_encode($context);
    error_log("[$timestamp] LOGIN ERROR: $message$contextStr");
}

try {
    // Test config file exists
    if (!file_exists('config.php')) {
        logError('Config file not found');
        throw new Exception('Config file not found');
    }
    
    require_once 'config.php';

    if (!isset($pdo)) {
        logError('PDO object not initialized in config.php');
        throw new Exception('Database connection failed - PDO not initialized');
    }
    
    // Test database connection
    $pdo->query('SELECT 1');
    
} catch (PDOException $e) {
    logError('Database connection test failed', ['error' => $e->getMessage()]);
    echo json_encode([
        'success' => false,
        'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้'
    ], JSON_UNESCAPED_UNICODE);
    exit();
} catch (Exception $e) {
    logError('Config loading failed', ['error' => $e->getMessage()]);
    echo json_encode([
        'success' => false,
        'message' => 'ระบบไม่พร้อมใช้งาน: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Read JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate input
if (!$data || !isset($data['username']) || !isset($data['password'])) {
    logError('Invalid JSON input', ['input' => substr($input, 0, 100)]);
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
    // Check if admin table exists and has records
    $checkTable = $pdo->query("SELECT COUNT(*) as count FROM admin");
    $tableInfo = $checkTable->fetch();
    
    if ($tableInfo['count'] == 0) {
        logError('Admin table is empty');
        echo json_encode([
            'success' => false,
            'message' => 'ไม่พบข้อมูลผู้ดูแลระบบ'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    // Query admin
    $stmt = $pdo->prepare("
        SELECT admin_id, fullname, password, position, department, status 
        FROM admin 
        WHERE admin_id = ? AND status = 'active'
    ");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if (!$admin) {
        logError('Admin not found or inactive', ['username' => $username]);
        echo json_encode([
            'success' => false,
            'message' => 'รหัสพนักงานหรือรหัสผ่านไม่ถูกต้อง'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Check password hash format
    if (empty($admin['password']) || strlen($admin['password']) < 60) {
        logError('Invalid password hash format', [
            'username' => $username,
            'hash_length' => strlen($admin['password'])
        ]);
        echo json_encode([
            'success' => false,
            'message' => 'ข้อมูลรหัสผ่านไม่ถูกต้อง กรุณาติดต่อผู้ดูแลระบบ'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Verify password
    if (!password_verify($password, $admin['password'])) {
        logError('Password verification failed', [
            'username' => $username,
            'hash_starts_with' => substr($admin['password'], 0, 10)
        ]);
        echo json_encode([
            'success' => false,
            'message' => 'รหัสพนักงานหรือรหัสผ่านไม่ถูกต้อง'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Additional status check
    if ($admin['status'] !== 'active') {
        logError('Account not active', ['username' => $username, 'status' => $admin['status']]);
        echo json_encode([
            'success' => false,
            'message' => 'บัญชีผู้ใช้ถูกระงับ กรุณาติดต่อผู้ดูแลระบบ'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Success - create session
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $admin['admin_id'];
    $_SESSION['fullname'] = $admin['fullname'];
    $_SESSION['position'] = $admin['position'];
    $_SESSION['department'] = $admin['department'];
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();

    // Update last login time
    try {
        $update_stmt = $pdo->prepare("UPDATE admin SET updated_at = NOW() WHERE admin_id = ?");
        $update_stmt->execute([$admin['admin_id']]);
    } catch (PDOException $e) {
        logError('Failed to update last login time', ['error' => $e->getMessage()]);
        // Don't fail login for this
    }

    // Log successful login
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
    logError('Database error during authentication', [
        'error' => $e->getMessage(),
        'code' => $e->getCode(),
        'username' => $username ?? 'unknown'
    ]);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในระบบฐานข้อมูล'
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    logError('General error during authentication', [
        'error' => $e->getMessage(),
        'username' => $username ?? 'unknown'
    ]);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดที่ไม่คาดคิด'
    ], JSON_UNESCAPED_UNICODE);
}
?>