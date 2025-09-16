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
    
    if (!file_exists('user_controller.php')) {
        throw new Exception('UserController file not found');
    }
    require_once 'user_controller.php';

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
        case 'forgot_password':
            handleForgotPassword($userController, $pdo);
            break;
        case 'reset_password':
            handleResetPassword($userController, $pdo);
            break;
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'Invalid action: ' . $action,
                'code' => 'INVALID_ACTION',
                'available_actions' => ['test', 'login', 'signup', 'logout', 'profile', 'update_profile', 'change_password', 'forgot_password', 'reset_password']
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
                'available_actions' => ['test', 'login', 'signup', 'logout', 'profile', 'update_profile', 'change_password', 'forgot_password', 'reset_password']
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
 * Send email using PHPMailer SMTP if configured; fallback to mail()
 */
function sendEmailSmart($toEmail, $toName, $subject, $htmlMessage, $textMessage)
{
    // Access SMTP config from config.php
    global $smtp_config;

    // Try PHPMailer if enabled and autoloadable
    if (!empty($smtp_config['enabled'])) {
        // Attempt to include Composer autoloader
        $autoloadPaths = [
            __DIR__ . '/../vendor/autoload.php',
            __DIR__ . '/vendor/autoload.php',
            dirname(__DIR__) . '/vendor/autoload.php'
        ];
        foreach ($autoloadPaths as $autoload) {
            if (file_exists($autoload)) {
                require_once $autoload;
                break;
            }
        }

        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            try {
                $mailer = new PHPMailer\PHPMailer\PHPMailer(true);

                $mailer->isSMTP();
                $mailer->Host = $smtp_config['host'] ?? '';
                $mailer->SMTPAuth = true;
                $mailer->Username = $smtp_config['username'] ?? '';
                $mailer->Password = $smtp_config['password'] ?? '';
                $encryption = $smtp_config['encryption'] ?? '';
                if ($encryption) {
                    $mailer->SMTPSecure = $encryption; // 'tls' or 'ssl'
                }
                $mailer->Port = (int)($smtp_config['port'] ?? 587);
                $mailer->CharSet = 'UTF-8';

                $fromEmail = $smtp_config['from_email'] ?? ('no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
                $fromName = $smtp_config['from_name'] ?? 'ระบบ';
                $mailer->setFrom($fromEmail, $fromName);
                if (!empty($smtp_config['reply_to_email'])) {
                    $mailer->addReplyTo($smtp_config['reply_to_email'], $smtp_config['reply_to_name'] ?? '');
                }

                $mailer->addAddress($toEmail, $toName);
                $mailer->isHTML(true);
                $mailer->Subject = $subject;
                $mailer->Body = $htmlMessage;
                $mailer->AltBody = $textMessage ?: strip_tags($htmlMessage);

                $mailer->send();
                return true;
            } catch (Exception $e) {
                error_log('PHPMailer send error: ' . $e->getMessage());
                // Fallback to mail()
            }
        }
    }

    // mail() fallback
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= 'From: ' . (($smtp_config['from_name'] ?? 'ระบบ') . ' <' . ($smtp_config['from_email'] ?? ('no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'))) . ">\r\n");
    return @mail($toEmail, $subject, $htmlMessage, $headers);
}

/**
 * Handle forgot password - generate token and send email
 */
function handleForgotPassword($userController, $pdo)
{
    try {
        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'กรุณากรอกอีเมลที่ถูกต้อง',
                'code' => 'INVALID_EMAIL'
            ]);
            return;
        }

        // Find user by email (do not reveal if not exists)
        $user = $userController->getByEmail($email);

        // Always respond success to prevent user enumeration
        $genericResponse = function () {
            echo json_encode([
                'success' => true,
                'message' => 'หากอีเมลนี้อยู่ในระบบ เราได้ส่งลิงก์รีเซ็ตรหัสผ่านให้แล้ว'
            ]);
        };

        if (!$user) {
            $genericResponse();
            return;
        }

        // Ensure reset table exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS PasswordResets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(50) NOT NULL,
            email VARCHAR(255) NOT NULL,
            token VARCHAR(128) NOT NULL,
            expires_at DATETIME NOT NULL,
            used TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX (email), INDEX (user_id), INDEX (token)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Generate secure token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 60 * 60); // 1 hour

        // Invalidate previous tokens for this user
        $stmt = $pdo->prepare("UPDATE PasswordResets SET used = 1 WHERE user_id = :user_id AND used = 0");
        $stmt->execute([':user_id' => $user['user_id']]);

        // Store token
        $stmt = $pdo->prepare("INSERT INTO PasswordResets (user_id, email, token, expires_at) VALUES (:user_id, :email, :token, :expires_at)");
        $stmt->execute([
            ':user_id' => $user['user_id'],
            ':email' => $email,
            ':token' => $token,
            ':expires_at' => $expiresAt
        ]);

        // Build reset URL (root-level reset_password.php)
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $resetUrl = $scheme . $host . '/steelproject/reset_password.php?token=' . urlencode($token);

        // Send email via PHPMailer SMTP (fallback to mail())
        $subject = 'รีเซ็ตรหัสผ่าน - ช้างเหล็กไทย';
        $html = '<p>สวัสดีคุณ ' . htmlspecialchars($user['name']) . '</p>' .
                '<p>กรุณาคลิกที่ลิงก์ด้านล่างเพื่อรีเซ็ตรหัสผ่านของคุณ:</p>' .
                '<p><a href="' . htmlspecialchars($resetUrl) . '">' . htmlspecialchars($resetUrl) . '</a></p>' .
                '<p>ลิงก์นี้จะหมดอายุใน 1 ชั่วโมง หากคุณไม่ได้ร้องขอ กรุณาเพิกเฉยอีเมลนี้</p>';
        $text = "สวัสดีคุณ {$user['name']}\r\n\r\nกรุณาเข้าลิงก์เพื่อรีเซ็ตรหัสผ่าน: {$resetUrl}\r\n\r\nลิงก์หมดอายุใน 1 ชั่วโมง";
        sendEmailSmart($email, $user['name'], $subject, $html, $text);

        $genericResponse();
    } catch (Exception $e) {
        error_log('Forgot password error: ' . $e->getMessage());
        // Always generic response
        echo json_encode([
            'success' => true,
            'message' => 'หากอีเมลนี้อยู่ในระบบ เราได้ส่งลิงก์รีเซ็ตรหัสผ่านให้แล้ว'
        ]);
    }
}

/**
 * Handle reset password - verify token and set new password
 */
function handleResetPassword($userController, $pdo)
{
    try {
        $token = $_POST['token'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($token) || empty($new_password) || empty($confirm_password)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'ข้อมูลไม่ครบถ้วน',
                'code' => 'MISSING_FIELDS'
            ]);
            return;
        }

        if ($new_password !== $confirm_password) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'การยืนยันรหัสผ่านไม่ตรงกัน',
                'code' => 'PASSWORD_MISMATCH'
            ]);
            return;
        }

        if (!validatePassword($new_password)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร ประกอบด้วยตัวอักษรพิมพ์เล็ก พิมพ์ใหญ่ และตัวเลข',
                'code' => 'WEAK_PASSWORD'
            ]);
            return;
        }

        // Validate token
        $stmt = $pdo->prepare("SELECT * FROM PasswordResets WHERE token = :token AND used = 0 LIMIT 1");
        $stmt->execute([':token' => $token]);
        $reset = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reset) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'โทเคนไม่ถูกต้อง',
                'code' => 'INVALID_TOKEN'
            ]);
            return;
        }

        if (strtotime($reset['expires_at']) < time()) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'โทเคนหมดอายุแล้ว',
                'code' => 'TOKEN_EXPIRED'
            ]);
            return;
        }

        // Update password
        $updated = $userController->updatePassword($reset['user_id'], $new_password);
        if (!$updated) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'ไม่สามารถอัปเดตรหัสผ่านได้',
                'code' => 'UPDATE_FAILED'
            ]);
            return;
        }

        // Mark token used
        $stmt = $pdo->prepare("UPDATE PasswordResets SET used = 1 WHERE id = :id");
        $stmt->execute([':id' => $reset['id']]);

        echo json_encode([
            'success' => true,
            'message' => 'รีเซ็ตรหัสผ่านสำเร็จ'
        ]);
    } catch (Exception $e) {
        error_log('Reset password error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาด',
            'code' => 'SERVER_ERROR'
        ]);
    }
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