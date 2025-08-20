<?php
// auth_api.php - Authentication API handler for Users table
// Prevent any output before headers
ob_start();

// Set proper JSON headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Error handling - DON'T display errors in production
ini_set('display_errors', 0); // Changed from 1 to 0
error_reporting(E_ALL);
ini_set('log_errors', 1); // Make sure errors are logged

// Clean any previous output
ob_clean();

try {
    // Include required files with better error handling
    if (!file_exists('config.php')) {
        throw new Exception('Configuration file not found');
    }
    require_once 'config.php';
    
    if (!file_exists('UserController.php')) {
        throw new Exception('UserController file not found');
    }
    require_once 'UserController.php';

    // Test database connection first
    if (!isset($pdo) || !$pdo) {
        throw new Exception('Database connection failed');
    }

    $userController = new UserController($pdo);

    // Handle different actions
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'test':
            handleTest($userController, $pdo);
            break;
        case 'login':
            handleLogin($userController);
            break;
        case 'signup':
            handleSignup($userController);
            break;
        case 'logout':
            handleLogout();
            break;
        case 'profile':
            handleGetProfile($userController);
            break;
        case 'update_profile':
            handleUpdateProfile($userController);
            break;
        case 'change_password':
            handleChangePassword($userController);
            break;
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'Invalid action: ' . $action,
                'code' => 'INVALID_ACTION',
                'available_actions' => ['test', 'login', 'signup', 'logout', 'profile', 'update_profile', 'change_password']
            ]);
            break;
    }

} catch (Exception $e) {
    // Log the full error for debugging
    error_log("API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred',
        'code' => 'SERVER_ERROR',
        'debug' => [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}

/**
 * Handle API test - Enhanced version
 */
function handleTest($userController, $pdo)
{
    try {
        // Test database connection with a simple query
        $stmt = $pdo->query("SELECT 1");
        $db_test = $stmt ? true : false;
        
        // Test if Users table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'Users'");
        $table_exists = $stmt->rowCount() > 0;
        
        $response = [
            'success' => true,
            'message' => 'API ทำงานได้ปกติ',
            'data' => [
                'database_connected' => $db_test,
                'table_exists' => $table_exists,
                'server_time' => date('Y-m-d H:i:s'),
                'php_version' => PHP_VERSION,
                'available_actions' => ['test', 'login', 'signup', 'logout', 'profile', 'update_profile', 'change_password']
            ]
        ];
        
        // Only get additional info if table exists
        if ($table_exists) {
            try {
                $users_count = $userController->getCount();
                $response['data']['users_count'] = $users_count;
                
                // Get table structure
                $stmt = $pdo->query("DESCRIBE Users");
                $table_structure = $stmt->fetchAll();
                $response['data']['table_structure'] = array_column($table_structure, 'Field');
                
                // Get sample user (without password)
                $stmt = $pdo->query("SELECT user_id, name, email, phone, created_at FROM Users LIMIT 1");
                $sample_user = $stmt->fetch();
                $response['data']['sample_user'] = $sample_user;
            } catch (Exception $e) {
                $response['data']['table_error'] = $e->getMessage();
            }
        }
        
        echo json_encode($response);
    } catch (Exception $e) {
        error_log("Test error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Test failed: ' . $e->getMessage(),
            'code' => 'TEST_ERROR'
        ]);
    }
}

/**
 * Handle user signup - Enhanced with better debugging
 */
function handleSignup($userController)
{
    try {
        // Capture input data
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirmPassword'] ?? '';

        // Log signup attempt (without sensitive data)
        error_log("Signup attempt - Email: " . $email . ", Name: " . $name);

        // Validation
        if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($confirmPassword)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'กรุณากรอกข้อมูลให้ครบทุกช่อง',
                'code' => 'MISSING_FIELDS'
            ]);
            return;
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'รูปแบบอีเมลไม่ถูกต้อง',
                'code' => 'INVALID_EMAIL'
            ]);
            return;
        }

        // Check password confirmation
        if ($password !== $confirmPassword) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'การยืนยันรหัสผ่านไม่ตรงกัน',
                'code' => 'PASSWORD_MISMATCH'
            ]);
            return;
        }

        // Validate password strength
        if (!validatePassword($password)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร ประกอบด้วยตัวอักษรพิมพ์เล็ก พิมพ์ใหญ่ และตัวเลข',
                'code' => 'WEAK_PASSWORD'
            ]);
            return;
        }

        // Validate phone number
        if (!preg_match('/^[0-9+\-\s()]{8,20}$/', $phone)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง',
                'code' => 'INVALID_PHONE'
            ]);
            return;
        }

        // Check if email already exists
        error_log("Checking if email exists: " . $email);
        $emailExists = $userController->emailExists($email);
        error_log("Email exists result: " . ($emailExists ? 'true' : 'false'));

        if ($emailExists) {
            http_response_code(409);
            echo json_encode([
                'success' => false, 
                'message' => 'อีเมลนี้ถูกใช้งานแล้ว',
                'code' => 'EMAIL_EXISTS'
            ]);
            return;
        }

        // Create user
        error_log("Attempting to create user with email: " . $email);
        $result = $userController->create($name, $email, $phone, $password);

        if ($result) {
            error_log("User created successfully for email: " . $email);
            echo json_encode([
                'success' => true,
                'message' => 'ลงทะเบียนสำเร็จ! กรุณาเข้าสู่ระบบ',
                'redirect' => 'login.php'
            ]);
        } else {
            error_log("Failed to create user for email: " . $email);
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'ไม่สามารถสร้างบัญชีได้ กรุณาลองใหม่อีกครั้ง',
                'code' => 'CREATE_FAILED'
            ]);
        }
    } catch (Exception $e) {
        error_log("Signup error: " . $e->getMessage());
        error_log("Signup stack trace: " . $e->getTraceAsString());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'เกิดข้อผิดพลาดในการสร้างบัญชี',
            'code' => 'SERVER_ERROR',
            'debug' => [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]
        ]);
    }
}

/**
 * Handle user login
 */
function handleLogin($userController)
{
    try {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validation
        if (empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'กรุณากรอกอีเมลและรหัสผ่าน',
                'code' => 'MISSING_CREDENTIALS'
            ]);
            return;
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'รูปแบบอีเมลไม่ถูกต้อง',
                'code' => 'INVALID_EMAIL'
            ]);
            return;
        }

        $user = $userController->login($email, $password);

        if ($user) {
            // Start session safely
            if (session_status() === PHP_SESSION_NONE) {
                session_name('user_session');
                session_start();
            }

            $expire_time = time() + (7 * 24 * 60 * 60); // 7 days

            // Set secure cookies
            $cookie_options = [
                'expires' => $expire_time,
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'httponly' => false,
                'samesite' => 'Strict'
            ];

            setcookie('user_id', $user['user_id'], $cookie_options);
            setcookie('email', $user['email'], $cookie_options);
            setcookie('name', $user['name'], $cookie_options);
            setcookie('phone', $user['phone'], $cookie_options);
            setcookie('login_time', time(), $cookie_options);

            // Store in session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['logged_in'] = true;

            echo json_encode([
                'success' => true,
                'message' => 'เข้าสู่ระบบสำเร็จ',
                'data' => [
                    'user_id' => $user['user_id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'phone' => $user['phone']
                ],
                'redirect' => 'index.php'
            ]);
        } else {
            http_response_code(401);
            echo json_encode([
                'success' => false, 
                'message' => 'อีเมลหรือรหัสผ่านไม่ถูกต้อง',
                'code' => 'INVALID_CREDENTIALS'
            ]);
        }
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'เกิดข้อผิดพลาดในการเข้าสู่ระบบ',
            'code' => 'SERVER_ERROR'
        ]);
    }
}

/**
 * Handle user logout
 */
function handleLogout()
{
    try {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Clear session data
        $_SESSION = array();

        // Delete session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }

        // Destroy session
        session_destroy();

        // Delete custom cookies
        $cookies_to_delete = ['user_session', 'user_id', 'email', 'name', 'phone', 'login_time'];
        foreach ($cookies_to_delete as $cookie) {
            if (isset($_COOKIE[$cookie])) {
                setcookie($cookie, '', time() - 3600, '/');
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'ออกจากระบบสำเร็จ',
            'redirect' => 'login.php'
        ]);
    } catch (Exception $e) {
        error_log("Logout error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'เกิดข้อผิดพลาดในการออกจากระบบ',
            'code' => 'SERVER_ERROR'
        ]);
    }
}

/**
 * Handle get user profile
 */
function handleGetProfile($userController)
{
    try {
        $user_id = $_GET['user_id'] ?? $_COOKIE['user_id'] ?? '';

        if (empty($user_id)) {
            http_response_code(401);
            echo json_encode([
                'success' => false, 
                'message' => 'ไม่ได้รับอนุญาต',
                'code' => 'NOT_AUTHENTICATED'
            ]);
            return;
        }

        $user = $userController->getById($user_id);

        if ($user) {
            // Remove sensitive data
            unset($user['password_hash']);
            
            echo json_encode([
                'success' => true,
                'data' => $user
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false, 
                'message' => 'ไม่พบผู้ใช้งาน',
                'code' => 'USER_NOT_FOUND'
            ]);
        }
    } catch (Exception $e) {
        error_log("Get profile error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'เกิดข้อผิดพลาด',
            'code' => 'SERVER_ERROR'
        ]);
    }
}

/**
 * Handle update user profile
 */
function handleUpdateProfile($userController)
{
    try {
        $user_id = $_POST['user_id'] ?? $_COOKIE['user_id'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (empty($user_id)) {
            http_response_code(401);
            echo json_encode([
                'success' => false, 
                'message' => 'ไม่ได้รับอนุญาต',
                'code' => 'NOT_AUTHENTICATED'
            ]);
            return;
        }

        // Validation
        if (empty($name) || empty($email) || empty($phone)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'กรุณากรอกชื่อ อีเมล และเบอร์โทรศัพท์',
                'code' => 'MISSING_FIELDS'
            ]);
            return;
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'รูปแบบอีเมลไม่ถูกต้อง',
                'code' => 'INVALID_EMAIL'
            ]);
            return;
        }

        // Check if email exists for other users
        if ($userController->emailExists($email, $user_id)) {
            http_response_code(409);
            echo json_encode([
                'success' => false, 
                'message' => 'อีเมลนี้ถูกใช้งานแล้ว',
                'code' => 'EMAIL_EXISTS'
            ]);
            return;
        }

        // Update profile
        $result = $userController->update($user_id, $name, $email, $phone);

        if ($result) {
            // Update cookies
            $expire_time = time() + (7 * 24 * 60 * 60);
            $cookie_options = [
                'expires' => $expire_time,
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'httponly' => false,
                'samesite' => 'Strict'
            ];

            setcookie('email', $email, $cookie_options);
            setcookie('name', $name, $cookie_options);
            setcookie('phone', $phone, $cookie_options);

            echo json_encode([
                'success' => true,
                'message' => 'อัปเดตข้อมูลสำเร็จ'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'ไม่สามารถอัปเดตข้อมูลได้',
                'code' => 'UPDATE_FAILED'
            ]);
        }
    } catch (Exception $e) {
        error_log("Update profile error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'เกิดข้อผิดพลาด',
            'code' => 'SERVER_ERROR'
        ]);
    }
}

/**
 * Handle change password
 */
function handleChangePassword($userController)
{
    try {
        $user_id = $_POST['user_id'] ?? $_COOKIE['user_id'] ?? '';
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($user_id)) {
            http_response_code(401);
            echo json_encode([
                'success' => false, 
                'message' => 'ไม่ได้รับอนุญาต',
                'code' => 'NOT_AUTHENTICATED'
            ]);
            return;
        }

        // Validation
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'กรุณากรอกข้อมูลให้ครบทุกช่อง',
                'code' => 'MISSING_FIELDS'
            ]);
            return;
        }

        if ($new_password !== $confirm_password) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'การยืนยันรหัสผ่านใหม่ไม่ตรงกัน',
                'code' => 'PASSWORD_MISMATCH'
            ]);
            return;
        }

        // Validate new password strength
        if (!validatePassword($new_password)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร ประกอบด้วยตัวอักษรพิมพ์เล็ก พิมพ์ใหญ่ และตัวเลข',
                'code' => 'WEAK_PASSWORD'
            ]);
            return;
        }

        // Verify current password
        $user = $userController->getById($user_id);
        if (!$user || !password_verify($current_password, $user['password_hash'])) {
            http_response_code(401);
            echo json_encode([
                'success' => false, 
                'message' => 'รหัสผ่านปัจจุบันไม่ถูกต้อง',
                'code' => 'INVALID_CURRENT_PASSWORD'
            ]);
            return;
        }

        // Update password
        $result = $userController->updatePassword($user_id, $new_password);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'เปลี่ยนรหัสผ่านสำเร็จ'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'ไม่สามารถเปลี่ยนรหัสผ่านได้',
                'code' => 'UPDATE_FAILED'
            ]);
        }
    } catch (Exception $e) {
        error_log("Change password error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'เกิดข้อผิดพลาด',
            'code' => 'SERVER_ERROR'
        ]);
    }
}

/**
 * Validate password strength
 */
function validatePassword($password)
{
    return strlen($password) >= 8 &&
        preg_match('/[A-Z]/', $password) &&
        preg_match('/[a-z]/', $password) &&
        preg_match('/[0-9]/', $password);
}

/**
 * Check if user is authenticated
 */
function isAuthenticated()
{
    return isset($_COOKIE['user_id']) && !empty($_COOKIE['user_id']);
}

// Flush output buffer and end
ob_end_flush();
?>