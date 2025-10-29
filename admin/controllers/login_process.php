<?php
// ========================
// LOGIN PROCESS
// ========================
// ตรวจสอบและทำการเข้าสู่ระบบของผู้ใช้งาน

error_reporting(0);
ini_set('display_errors', 0);
session_start();

header('Content-Type: application/json; charset=utf-8');

// ========================
// FUNCTION: บันทึกข้อผิดพลาด
// ========================
function logError($message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = empty($context) ? '' : ' Context: ' . json_encode($context);
    error_log("[$timestamp] LOGIN ERROR: $message$contextStr");
}

try {
    // ตรวจสอบไฟล์ config
    if (!file_exists('config.php')) {
        logError('Config file not found');
        throw new Exception('Config file not found');
    }

    require_once 'config.php';

    if (!isset($pdo)) {
        logError('PDO object not initialized');
        throw new Exception('Database connection failed');
    }

    // ทดสอบการเชื่อมต่อ
    $pdo->query('SELECT 1');

} catch (PDOException $e) {
    logError('Database connection failed', ['error' => $e->getMessage()]);
    echo json_encode([
        'success' => false,
        'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้'
    ], JSON_UNESCAPED_UNICODE);
    exit();
} catch (Exception $e) {
    logError('Config loading failed', ['error' => $e->getMessage()]);
    echo json_encode([
        'success' => false,
        'message' => 'ระบบมีข้อผิดพลาด'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// ตรวจสอบ Method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// ========================
// FUNCTION: ตรวจสอบและตรวจสอบข้อมูลเข้า
// ========================
function validateInput($data) {
    if (!$data || !isset($data['username']) || !isset($data['password'])) {
        return ['valid' => false, 'message' => 'ข้อมูลไม่สมบูรณ์'];
    }

    $username = trim($data['username']);
    $password = trim($data['password']);

    if (empty($username)) {
        return ['valid' => false, 'message' => 'กรุณากรอกรหัสผู้ใช้งาน'];
    }

    if (empty($password)) {
        return ['valid' => false, 'message' => 'กรุณากรอกรหัสผ่าน'];
    }

    return ['valid' => true, 'username' => $username, 'password' => $password];
}

// อ่านข้อมูล JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// ตรวจสอบข้อมูล
$validation = validateInput($data);
if (!$validation['valid']) {
    echo json_encode([
        'success' => false,
        'message' => $validation['message']
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$username = $validation['username'];
$password = $validation['password'];

try {
    // ========================
    // FUNCTION: ตรวจสอบและค้นหา Admin
    // ========================
    function findAdmin($pdo, $username) {
        $checkTable = $pdo->query("SELECT COUNT(*) as count FROM Admin");
        $tableInfo = $checkTable->fetch();

        if ($tableInfo['count'] == 0) {
            logError('Admin table is empty');
            throw new Exception('ไม่พบข้อมูลผู้ดูแลระบบ');
        }

        $stmt = $pdo->prepare("
            SELECT admin_id, fullname, password, position, department, status
            FROM Admin
            WHERE admin_id = ? AND status = 'active'
        ");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }

    // ค้นหา Admin
    $admin = findAdmin($pdo, $username);

    if (!$admin) {
        logError('Admin not found or inactive', ['username' => $username]);
        echo json_encode([
            'success' => false,
            'message' => 'รหัสผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // ========================
    // FUNCTION: ตรวจสอบรหัสผ่าน
    // ========================
    function verifyPassword($password, $hashPassword, $username) {
        if (empty($hashPassword) || strlen($hashPassword) < 60) {
            logError('Invalid password hash', ['username' => $username, 'hash_length' => strlen($hashPassword)]);
            throw new Exception('ข้อมูลรหัสผ่านไม่ถูกต้อง');
        }

        if (!password_verify($password, $hashPassword)) {
            logError('Password verification failed', ['username' => $username]);
            throw new Exception('รหัสผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง');
        }

        return true;
    }

    // ตรวจสอบรหัสผ่าน
    verifyPassword($password, $admin['password'], $username);

    // ========================
    // FUNCTION: สร้างเซสชั่น
    // ========================
    function createSession($admin) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['fullname'] = $admin['fullname'];
        $_SESSION['position'] = $admin['position'];
        $_SESSION['department'] = $admin['department'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
    }

    // สร้างเซสชั่น
    createSession($admin);

    // ========================
    // FUNCTION: อัปเดตเวลา Login
    // ========================
    function updateLastLogin($pdo, $adminId) {
        try {
            $stmt = $pdo->prepare("UPDATE Admin SET updated_at = NOW() WHERE admin_id = ?");
            $stmt->execute([$adminId]);
        } catch (PDOException $e) {
            logError('Failed to update last login', ['error' => $e->getMessage()]);
        }
    }

    // อัปเดตเวลา login
    updateLastLogin($pdo, $admin['admin_id']);

    error_log("Successful login: " . $username . " at " . date('Y-m-d H:i:s'));

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
    logError('Database error', ['error' => $e->getMessage(), 'username' => $username]);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในระบบฐานข้อมูล'
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    logError('General error', ['error' => $e->getMessage(), 'username' => $username]);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>