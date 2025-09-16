<?php
// auth_api.php - Authentication API handler for Users table with Universal Email Support

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

ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

ob_clean();

try {
    if (!file_exists('config.php')) {
        throw new Exception('Configuration file not found');
    }
    require_once 'config.php';
    
    if (!file_exists('user_controller.php')) {
        throw new Exception('UserController file not found');
    }
    require_once 'user_controller.php';

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
        case 'test_email':
            handleTestEmail();
            break;
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'Invalid action: ' . $action,
                'code' => 'INVALID_ACTION',
                'available_actions' => ['test', 'login', 'signup', 'logout', 'profile', 'update_profile', 'change_password', 'forgot_password', 'reset_password', 'test_email']
            ]);
            break;
    }

} catch (Exception $e) {
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
 * Enhanced Universal Email Sending Function
 * Can send to ANY email address, not just the sender's email
 */
function sendUniversalEmail($toEmail, $toName, $subject, $htmlMessage, $textMessage = '')
{
    global $smtp_config;

    // Clean the text message if not provided
    if (empty($textMessage)) {
        $textMessage = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlMessage));
    }

    // Try PHPMailer SMTP first if enabled
    if (!empty($smtp_config['enabled'])) {
        // Find Composer autoloader
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
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);

                // Server settings
                $mail->isSMTP();
                $mail->Host = $smtp_config['host'];
                $mail->SMTPAuth = true;
                $mail->Username = $smtp_config['username'];
                $mail->Password = $smtp_config['password'];
                
                // Set encryption
                if (!empty($smtp_config['encryption'])) {
                    if (strtolower($smtp_config['encryption']) === 'tls') {
                        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    } elseif (strtolower($smtp_config['encryption']) === 'ssl') {
                        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                    }
                }
                
                $mail->Port = (int)$smtp_config['port'];
                $mail->CharSet = 'UTF-8';

                // Set sender (always uses the configured Gmail account)
                $fromEmail = $smtp_config['from_email'];
                $fromName = $smtp_config['from_name'];
                $mail->setFrom($fromEmail, $fromName);
                
                // Set reply-to if configured
                if (!empty($smtp_config['reply_to_email'])) {
                    $mail->addReplyTo(
                        $smtp_config['reply_to_email'], 
                        $smtp_config['reply_to_name'] ?? ''
                    );
                }

                // Recipients - THIS IS THE KEY: Can send to ANY email address
                $mail->addAddress($toEmail, $toName);

                // Content
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $htmlMessage;
                $mail->AltBody = $textMessage;

                // Additional headers for better deliverability
                $mail->addCustomHeader('X-Mailer', 'ช้างเหล็กไทย System v1.0');
                $mail->addCustomHeader('X-Priority', '3');

                $mail->send();
                
                // Log successful send
                error_log("Email sent successfully to: {$toEmail} via SMTP");
                return true;

            } catch (Exception $e) {
                error_log('PHPMailer SMTP failed: ' . $e->getMessage());
                // Continue to fallback methods
            }
        } else {
            error_log('PHPMailer class not found, falling back to mail()');
        }
    }

    // Fallback to PHP mail() function
    try {
        $fromEmail = $smtp_config['from_email'] ?? 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $fromName = $smtp_config['from_name'] ?? 'ช้างเหล็กไทย';
        $replyTo = $smtp_config['reply_to_email'] ?? $fromEmail;
        
        // Prepare headers
        $headers = [];
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-type: text/html; charset=UTF-8";
        $headers[] = "From: {$fromName} <{$fromEmail}>";
        $headers[] = "Reply-To: {$replyTo}";
        $headers[] = "Return-Path: {$fromEmail}";
        $headers[] = "X-Mailer: PHP/" . phpversion();
        $headers[] = "X-Priority: 3";

        $headerString = implode("\r\n", $headers);

        // Send email using mail()
        $result = mail($toEmail, $subject, $htmlMessage, $headerString);
        
        if ($result) {
            error_log("Email sent successfully to: {$toEmail} via mail()");
            return true;
        } else {
            error_log("mail() function failed for: {$toEmail}");
            return false;
        }

    } catch (Exception $e) {
        error_log('mail() fallback failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Render email template with variables
 */
function renderEmailTemplate($templateName, $variables = [])
{
    global $email_templates;
    
    if (!isset($email_templates[$templateName])) {
        throw new Exception("Email template '{$templateName}' not found");
    }
    
    $template = $email_templates[$templateName];
    
    // Replace variables in both HTML and text templates
    $htmlContent = $template['html_template'];
    $textContent = $template['text_template'];
    
    foreach ($variables as $key => $value) {
        $placeholder = '{{' . strtoupper($key) . '}}';
        $htmlContent = str_replace($placeholder, htmlspecialchars($value), $htmlContent);
        $textContent = str_replace($placeholder, $value, $textContent);
    }
    
    return [
        'subject' => $template['subject'],
        'html' => $htmlContent,
        'text' => $textContent
    ];
}

/**
 * Handle test email sending to any email address
 */
function handleTestEmail()
{
    try {
        $testEmail = $_POST['test_email'] ?? $_GET['test_email'] ?? '';
        
        if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'กรุณากรอกอีเมลที่ถูกต้องสำหรับทดสอบ',
                'code' => 'INVALID_EMAIL'
            ]);
            return;
        }

        global $smtp_config;
        
        $subject = 'ทดสอบการส่งอีเมล - ช้างเหล็กไทย';
        $html = '<h2>ทดสอบระบบส่งอีเมล</h2>
                 <p>สวัสดี!</p>
                 <p>หากคุณได้รับอีเมลนี้ แสดงว่าระบบส่งอีเมลของ <strong>ช้างเหล็กไทย</strong> ทำงานได้ปกติ</p>
                 <p>สามารถส่งอีเมลไปยังที่อยู่อีเมลใดก็ได้แล้ว!</p>
                 <hr>
                 <p><small>เวลาทดสอบ: ' . date('Y-m-d H:i:s') . '</small></p>';
        
        $text = "ทดสอบระบบส่งอีเมล - ช้างเหล็กไทย\n\nสวัสดี!\n\nหากคุณได้รับอีเมลนี้ แสดงว่าระบบส่งอีเมลทำงานได้ปกติ\nสามารถส่งอีเมลไปยังที่อยู่อีเมลใดก็ได้แล้ว!\n\nเวลาทดสอบ: " . date('Y-m-d H:i:s');

        $result = sendUniversalEmail($testEmail, 'Test User', $subject, $html, $text);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => "ส่งอีเมลทดสอบไปยัง {$testEmail} สำเร็จ!"
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'ไม่สามารถส่งอีเมลทดสอบได้',
                'code' => 'SEND_FAILED'
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Test email error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการส่งอีเมลทดสอบ',
            'code' => 'SERVER_ERROR'
        ]);
    }
}

/**
 * Enhanced forgot password with universal email support
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

        // Find user by email
        $user = $userController->getByEmail($email);

        // Always respond success to prevent user enumeration
        $genericResponse = function () use ($email) {
            echo json_encode([
                'success' => true,
                'message' => 'หากอีเมล ' . $email . ' อยู่ในระบบ เราได้ส่งลิงก์รีเซ็ตรหัสผ่านให้แล้ว'
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

        // Build reset URL
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $resetUrl = $scheme . $host . '/steelproject/reset_password.php?token=' . urlencode($token);

        global $smtp_config;

        // Render email using template
        $emailContent = renderEmailTemplate('reset_password', [
            'user_name' => $user['name'],
            'reset_url' => $resetUrl,
            'reply_to_email' => $smtp_config['reply_to_email'] ?? $smtp_config['from_email']
        ]);

        // Send email to the user's actual email address (can be any email provider)
        $result = sendUniversalEmail(
            $email,                    // Can be Gmail, Yahoo, Hotmail, corporate email, etc.
            $user['name'], 
            $emailContent['subject'], 
            $emailContent['html'], 
            $emailContent['text']
        );

        if ($result) {
            error_log("Password reset email sent to: {$email}");
        } else {
            error_log("Failed to send password reset email to: {$email}");
        }

        $genericResponse();
        
    } catch (Exception $e) {
        error_log('Forgot password error: ' . $e->getMessage());
        echo json_encode([
            'success' => true,
            'message' => 'หากอีเมลนี้อยู่ในระบบ เราได้ส่งลิงก์รีเซ็ตรหัสผ่านให้แล้ว'
        ]);
    }
}

// [Keep all other existing functions unchanged: handleTest, handleLogin, handleSignup, handleLogout, 
//  handleGetProfile, handleUpdateProfile, handleChangePassword, handleResetPassword, validatePassword, isAuthenticated]

function handleTest($userController, $pdo)
{
    try {
        $stmt = $pdo->query("SELECT 1");
        $db_test = $stmt ? true : false;
        
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
                'email_system' => 'Universal Email Support Active',
                'available_actions' => ['test', 'login', 'signup', 'logout', 'profile', 'update_profile', 'change_password', 'forgot_password', 'reset_password', 'test_email']
            ]
        ];
        
        if ($table_exists) {
            try {
                $users_count = $userController->getCount();
                $response['data']['users_count'] = $users_count;
                
                $stmt = $pdo->query("DESCRIBE Users");
                $table_structure = $stmt->fetchAll();
                $response['data']['table_structure'] = array_column($table_structure, 'Field');
                
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

function handleSignup($userController)
{
    try {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirmPassword'] ?? '';

        error_log("Signup attempt - Email: " . $email . ", Name: " . $name);

        if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($confirmPassword)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'กรุณากรอกข้อมูลให้ครบทุกช่อง',
                'code' => 'MISSING_FIELDS'
            ]);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'รูปแบบอีเมลไม่ถูกต้อง',
                'code' => 'INVALID_EMAIL'
            ]);
            return;
        }

        if ($password !== $confirmPassword) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'การยืนยันรหัสผ่านไม่ตรงกัน',
                'code' => 'PASSWORD_MISMATCH'
            ]);
            return;
        }

        if (!validatePassword($password)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร ประกอบด้วยตัวอักษรพิมพ์เล็ก พิมพ์ใหญ่ และตัวเลข',
                'code' => 'WEAK_PASSWORD'
            ]);
            return;
        }

        if (!preg_match('/^[0-9+\-\s()]{8,20}$/', $phone)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง',
                'code' => 'INVALID_PHONE'
            ]);
            return;
        }

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

function handleLogin($userController)
{
    try {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'กรุณากรอกอีเมลและรหัสผ่าน',
                'code' => 'MISSING_CREDENTIALS'
            ]);
            return;
        }

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
            if (session_status() === PHP_SESSION_NONE) {
                session_name('user_session');
                session_start();
            }

            $expire_time = time() + (7 * 24 * 60 * 60); // 7 days

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

function handleLogout()
{
    try {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = array();

        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }

        session_destroy();

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

        if (empty($name) || empty($email) || empty($phone)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'กรุณากรอกชื่อ อีเมล และเบอร์โทรศัพท์',
                'code' => 'MISSING_FIELDS'
            ]);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'รูปแบบอีเมลไม่ถูกต้อง',
                'code' => 'INVALID_EMAIL'
            ]);
            return;
        }

        if ($userController->emailExists($email, $user_id)) {
            http_response_code(409);
            echo json_encode([
                'success' => false, 
                'message' => 'อีเมลนี้ถูกใช้งานแล้ว',
                'code' => 'EMAIL_EXISTS'
            ]);
            return;
        }

        $result = $userController->update($user_id, $name, $email, $phone);

        if ($result) {
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

        if (!validatePassword($new_password)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร ประกอบด้วยตัวอักษรพิมพ์เล็ก พิมพ์ใหญ่ และตัวเลข',
                'code' => 'WEAK_PASSWORD'
            ]);
            return;
        }

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

function validatePassword($password)
{
    return strlen($password) >= 8 &&
        preg_match('/[A-Z]/', $password) &&
        preg_match('/[a-z]/', $password) &&
        preg_match('/[0-9]/', $password);
}

function isAuthenticated()
{
    return isset($_COOKIE['user_id']) && !empty($_COOKIE['user_id']);
}

ob_end_flush();
?>