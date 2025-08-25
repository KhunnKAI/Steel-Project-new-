<?php
// customer_api.php - Customer Management API handler
// Prevent any output before headers
ob_start();

// Set proper JSON headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Error handling - DON'T display errors in production
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

// Clean any previous output
ob_clean();

try {
    // Include required files with better error handling
    if (!file_exists('config.php')) {
        throw new Exception('Configuration file not found');
    }
    require_once 'config.php';
    
    if (!file_exists('customer_controller.php')) {
        throw new Exception('CustomerController file not found');
    }
    require_once 'customer_controller.php';

    // Test database connection first
    if (!isset($pdo) || !$pdo) {
        throw new Exception('Database connection failed');
    }

    $customerController = new CustomerController($pdo);

    // Handle different actions
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'test':
            handleTest($customerController, $pdo);
            break;
        case 'list':
            handleGetAllCustomers($customerController);
            break;
        case 'get':
            handleGetCustomer($customerController);
            break;
        case 'create':
            handleCreateCustomer($customerController);
            break;
        case 'update':
            handleUpdateCustomer($customerController);
            break;
        case 'delete':
            handleDeleteCustomer($customerController);
            break;
        case 'search':
            handleSearchCustomers($customerController);
            break;
        case 'change_password':
            handleChangePassword($customerController);
            break;
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'Invalid action: ' . $action,
                'code' => 'INVALID_ACTION',
                'available_actions' => ['test', 'list', 'get', 'create', 'update', 'delete', 'search', 'change_password']
            ]);
            break;
    }

} catch (Exception $e) {
    // Log the full error for debugging
    error_log("Customer API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดของเซิร์ฟเวอร์',
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
function handleTest($customerController, $pdo)
{
    try {
        // Test database connection with a simple query
        $stmt = $pdo->query("SELECT 1");
        $db_test = $stmt ? true : false;
        
        // Test if Users table exists (ใช้ Users แทน customers)
        $stmt = $pdo->query("SHOW TABLES LIKE 'Users'");
        $table_exists = $stmt->rowCount() > 0;
        
        $response = [
            'success' => true,
            'message' => 'Customer API ทำงานได้ปกติ',
            'data' => [
                'database_connected' => $db_test,
                'table_exists' => $table_exists,
                'server_time' => date('Y-m-d H:i:s'),
                'php_version' => PHP_VERSION,
                'available_actions' => ['test', 'list', 'get', 'create', 'update', 'delete', 'search', 'change_password']
            ]
        ];
        
        // Only get additional info if table exists
        if ($table_exists) {
            try {
                $customers_count = $customerController->getCount();
                $response['data']['customers_count'] = $customers_count;
                
                // Get table structure for Users table
                $stmt = $pdo->query("DESCRIBE Users");
                $table_structure = $stmt->fetchAll();
                $response['data']['table_structure'] = array_column($table_structure, 'Field');
                
                // Get sample customer (without password)
                $stmt = $pdo->query("SELECT user_id, name, email, phone, created_at FROM Users LIMIT 1");
                $sample_customer = $stmt->fetch();
                $response['data']['sample_customer'] = $sample_customer;
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
            'message' => 'การทดสอบล้มเหลว: ' . $e->getMessage(),
            'code' => 'TEST_ERROR'
        ]);
    }
}

/**
 * Handle get all customers with pagination
 */
function handleGetAllCustomers($customerController)
{
    try {
        $page = intval($_GET['page'] ?? 1);
        $limit = intval($_GET['limit'] ?? 20);
        $offset = ($page - 1) * $limit;

        // Log request
        error_log("Getting customers - Page: $page, Limit: $limit");

        $customers = $customerController->getAll($limit, $offset);
        $totalCount = $customerController->getCount();
        $totalPages = ceil($totalCount / $limit);

        echo json_encode([
            'success' => true,
            'data' => $customers,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total_records' => $totalCount,
                'total_pages' => $totalPages,
                'has_more' => $page < $totalPages
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get all customers error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'ไม่สามารถดึงข้อมูลลูกค้าได้',
            'code' => 'FETCH_ERROR'
        ]);
    }
}

/**
 * Handle get single customer
 */
function handleGetCustomer($customerController)
{
    try {
        $customer_id = $_GET['customer_id'] ?? $_GET['id'] ?? '';

        if (empty($customer_id)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'กรุณาระบุรหัสลูกค้า',
                'code' => 'MISSING_CUSTOMER_ID'
            ]);
            return;
        }

        error_log("Getting customer with ID: " . $customer_id);

        $customer = $customerController->getById($customer_id);

        if ($customer) {
            // Remove sensitive data
            unset($customer['password_hash']);
            
            echo json_encode([
                'success' => true,
                'data' => $customer
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'ไม่พบลูกค้า',
                'code' => 'CUSTOMER_NOT_FOUND'
            ]);
        }
    } catch (Exception $e) {
        error_log("Get customer error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'ไม่สามารถดึงข้อมูลลูกค้าได้',
            'code' => 'FETCH_ERROR'
        ]);
    }
}

/**
 * Handle create new customer
 */
function handleCreateCustomer($customerController)
{
    try {
        // Capture input data
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';

        // Log creation attempt (without sensitive data)
        error_log("Creating customer - Email: " . $email . ", Name: " . $name);

        // Validation
        if (empty($name) || empty($email) || empty($phone) || empty($password)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'กรุณากรอกข้อมูลให้ครบทุกช่อง',
                'code' => 'MISSING_FIELDS',
                'required_fields' => ['name', 'email', 'phone', 'password']
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
        $emailExists = $customerController->emailExists($email);
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

        // Create customer
        error_log("Attempting to create customer with email: " . $email);
        $result = $customerController->create($name, $email, $phone, $password);

        if ($result) {
            error_log("Customer created successfully for email: " . $email);
            echo json_encode([
                'success' => true,
                'message' => 'สร้างข้อมูลลูกค้าสำเร็จ',
                'data' => [
                    'customer_id' => $result,
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone
                ]
            ]);
        } else {
            error_log("Failed to create customer for email: " . $email);
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'ไม่สามารถสร้างข้อมูลลูกค้าได้ กรุณาลองใหม่อีกครั้ง',
                'code' => 'CREATE_FAILED'
            ]);
        }
    } catch (Exception $e) {
        error_log("Create customer error: " . $e->getMessage());
        error_log("Create customer stack trace: " . $e->getTraceAsString());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'เกิดข้อผิดพลาดในการสร้างข้อมูลลูกค้า',
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
 * Handle update customer
 */
function handleUpdateCustomer($customerController)
{
    try {
        $customer_id = $_POST['customer_id'] ?? $_POST['id'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (empty($customer_id)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'กรุณาระบุรหัสลูกค้า',
                'code' => 'MISSING_CUSTOMER_ID'
            ]);
            return;
        }

        // Log update attempt
        error_log("Updating customer ID: " . $customer_id);

        // Check if customer exists
        $existingCustomer = $customerController->getById($customer_id);
        if (!$existingCustomer) {
            http_response_code(404);
            echo json_encode([
                'success' => false, 
                'message' => 'ไม่พบลูกค้า',
                'code' => 'CUSTOMER_NOT_FOUND'
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

        // Check if email exists for other customers
        if ($customerController->emailExists($email, $customer_id)) {
            http_response_code(409);
            echo json_encode([
                'success' => false, 
                'message' => 'อีเมลนี้ถูกใช้งานแล้วโดยลูกค้าคนอื่น',
                'code' => 'EMAIL_EXISTS'
            ]);
            return;
        }

        // Update customer
        $result = $customerController->update($customer_id, $name, $email, $phone);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'อัปเดตข้อมูลลูกค้าสำเร็จ',
                'data' => [
                    'customer_id' => $customer_id,
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'ไม่สามารถอัปเดตข้อมูลลูกค้าได้',
                'code' => 'UPDATE_FAILED'
            ]);
        }
    } catch (Exception $e) {
        error_log("Update customer error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'เกิดข้อผิดพลาดในการอัปเดตข้อมูลลูกค้า',
            'code' => 'SERVER_ERROR'
        ]);
    }
}

/**
 * Handle delete customer
 */
function handleDeleteCustomer($customerController)
{
    try {
        $customer_id = $_POST['customer_id'] ?? $_GET['customer_id'] ?? $_POST['id'] ?? $_GET['id'] ?? '';

        if (empty($customer_id)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'กรุณาระบุรหัสลูกค้า',
                'code' => 'MISSING_CUSTOMER_ID'
            ]);
            return;
        }

        // Log delete attempt
        error_log("Deleting customer ID: " . $customer_id);

        // Check if customer exists
        $existingCustomer = $customerController->getById($customer_id);
        if (!$existingCustomer) {
            http_response_code(404);
            echo json_encode([
                'success' => false, 
                'message' => 'ไม่พบลูกค้า',
                'code' => 'CUSTOMER_NOT_FOUND'
            ]);
            return;
        }

        // Delete customer
        $result = $customerController->delete($customer_id);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'ลบข้อมูลลูกค้าสำเร็จ',
                'data' => [
                    'deleted_customer_id' => $customer_id
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'ไม่สามารถลบข้อมูลลูกค้าได้',
                'code' => 'DELETE_FAILED'
            ]);
        }
    } catch (Exception $e) {
        error_log("Delete customer error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'เกิดข้อผิดพลาดในการลบข้อมูลลูกค้า',
            'code' => 'SERVER_ERROR'
        ]);
    }
}

/**
 * Handle change password - แก้ไขวิธีรับพารามิเตอร์
 */
function handleChangePassword($customerController)
{
    try {
        // แก้ไขการรับพารามิเตอร์ - ใช้ POST สำหรับ customer_id ด้วย
        $customer_id = $_POST['customer_id'] ?? $_GET['customer_id'] ?? '';
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';

        // Log ข้อมูลที่ได้รับ (ไม่ log รหัสผ่าน)
        error_log("Change password request - Customer ID: " . $customer_id);
        error_log("POST data keys: " . implode(', ', array_keys($_POST)));
        error_log("GET data keys: " . implode(', ', array_keys($_GET)));

        // Validation
        if (empty($customer_id)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'กรุณาระบุรหัสลูกค้า',
                'code' => 'MISSING_CUSTOMER_ID',
                'debug' => [
                    'post_customer_id' => $_POST['customer_id'] ?? 'not found',
                    'get_customer_id' => $_GET['customer_id'] ?? 'not found'
                ]
            ]);
            return;
        }

        if (empty($current_password) || empty($new_password)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'กรุณากรอกรหัสผ่านเดิมและรหัสผ่านใหม่',
                'code' => 'MISSING_PASSWORDS',
                'debug' => [
                    'has_current_password' => !empty($current_password),
                    'has_new_password' => !empty($new_password)
                ]
            ]);
            return;
        }

        // Validate new password strength
        if (!validatePassword($new_password)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 8 ตัวอักษร ประกอบด้วยตัวอักษรพิมพ์เล็ก พิมพ์ใหญ่ และตัวเลข',
                'code' => 'WEAK_PASSWORD'
            ]);
            return;
        }

        // Log password change attempt
        error_log("Password change attempt for customer ID: " . $customer_id);

        // Check if customer exists and verify current password
        $customer = $customerController->getById($customer_id);
        if (!$customer) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'ไม่พบลูกค้า',
                'code' => 'CUSTOMER_NOT_FOUND'
            ]);
            return;
        }

        // Verify current password
        if (!password_verify($current_password, $customer['password_hash'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'รหัสผ่านเดิมไม่ถูกต้อง',
                'code' => 'INVALID_CURRENT_PASSWORD'
            ]);
            return;
        }

        // Check if new password is different from current
        if (password_verify($new_password, $customer['password_hash'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'รหัสผ่านใหม่ต้องแตกต่างจากรหัสผ่านเดิม',
                'code' => 'SAME_PASSWORD'
            ]);
            return;
        }

        // Update password
        $result = $customerController->updatePassword($customer_id, $new_password);

        if ($result) {
            error_log("Password changed successfully for customer ID: " . $customer_id);
            echo json_encode([
                'success' => true,
                'message' => 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว',
                'data' => [
                    'customer_id' => $customer_id,
                    'changed_at' => date('Y-m-d H:i:s')
                ]
            ]);
        } else {
            error_log("Failed to change password for customer ID: " . $customer_id);
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'ไม่สามารถเปลี่ยนรหัสผ่านได้ กรุณาลองใหม่อีกครั้ง',
                'code' => 'CHANGE_PASSWORD_FAILED'
            ]);
        }
    } catch (Exception $e) {
        error_log("Change password error: " . $e->getMessage());
        error_log("Change password stack trace: " . $e->getTraceAsString());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน',
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
 * Handle search customers
 */
function handleSearchCustomers($customerController)
{
    try {
        $query = trim($_GET['query'] ?? $_POST['query'] ?? '');
        $field = $_GET['field'] ?? $_POST['field'] ?? 'all';
        $page = intval($_GET['page'] ?? 1);
        $limit = intval($_GET['limit'] ?? 20);
        $offset = ($page - 1) * $limit;

        if (empty($query)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'กรุณาระบุคำค้นหา',
                'code' => 'MISSING_QUERY'
            ]);
            return;
        }

        $customers = $customerController->search($query, $field, $limit, $offset);
        $totalCount = $customerController->getSearchCount($query, $field);
        $totalPages = ceil($totalCount / $limit);

        echo json_encode([
            'success' => true,
            'data' => $customers,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total_records' => $totalCount,
                'total_pages' => $totalPages,
                'has_more' => $page < $totalPages
            ],
            'search' => [
                'query' => $query,
                'field' => $field
            ]
        ]);
    } catch (Exception $e) {
        error_log("Search customers error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'ไม่สามารถค้นหาลูกค้าได้',
            'code' => 'SEARCH_ERROR'
        ]);
    }
}

/**
 * Password validation function
 */
function validatePassword($password) 
{
    // รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร และมีตัวพิมพ์เล็ก พิมพ์ใหญ่ และตัวเลข
    if (strlen($password) < 8) return false;
    if (!preg_match('/[a-z]/', $password)) return false;
    if (!preg_match('/[A-Z]/', $password)) return false;
    if (!preg_match('/[0-9]/', $password)) return false;
    
    return true;
}

// Flush output
ob_end_flush();
?>