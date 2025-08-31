<?php
// address_api.php - Address API handler for Addresses table
// Prevent any output before headers
ob_start();

// Set proper JSON headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE');
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
    
    if (!file_exists('address_controller.php')) {
        throw new Exception('AddressController file not found');
    }
    require_once 'address_controller.php';

    // Test database connection first
    if (!isset($pdo) || !$pdo) {
        throw new Exception('Database connection failed');
    }

    $addressController = new AddressController($pdo);

    // Handle different actions
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'test':
            handleTest($addressController, $pdo);
            break;
        case 'create':
            handleCreate($addressController);
            break;
        case 'get':
            handleGet($addressController);
            break;
        case 'get_by_user':
            handleGetByUser($addressController);
            break;
        case 'get_main':
            handleGetMain($addressController);
            break;
        case 'update':
            handleUpdate($addressController);
            break;
        case 'delete':
            handleDelete($addressController);
            break;
        case 'set_main':
            handleSetMain($addressController);
            break;
        case 'list':
            handleList($addressController);
            break;
        case 'get_provinces':
            handleGetProvinces($addressController);
            break;
        case 'get_provinces_by_zone':
            handleGetProvincesByZone($addressController);
            break;
        case 'statistics':
            handleStatistics($addressController);
            break;
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'Invalid action: ' . $action,
                'code' => 'INVALID_ACTION',
                'available_actions' => ['test', 'create', 'get', 'get_by_user', 'get_main', 'update', 'delete', 'set_main', 'list', 'get_provinces', 'get_provinces_by_zone', 'statistics']
            ]);
            break;
    }

} catch (Exception $e) {
    // Log the full error for debugging
    error_log("Address API Error: " . $e->getMessage());
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
function handleTest($addressController, $pdo)
{
    try {
        // Test database connection with a simple query
        $stmt = $pdo->query("SELECT 1");
        $db_test = $stmt ? true : false;
        
        // Test if Addresses table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'Addresses'");
        $addresses_table_exists = $stmt->rowCount() > 0;
        
        // Test if Province table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'Province'");
        $province_table_exists = $stmt->rowCount() > 0;
        
        $response = [
            'success' => true,
            'message' => 'Address API ทำงานได้ปกติ',
            'data' => [
                'database_connected' => $db_test,
                'addresses_table_exists' => $addresses_table_exists,
                'province_table_exists' => $province_table_exists,
                'server_time' => date('Y-m-d H:i:s'),
                'php_version' => PHP_VERSION,
                'available_actions' => ['test', 'create', 'get', 'get_by_user', 'get_main', 'update', 'delete', 'set_main', 'list', 'get_provinces', 'get_provinces_by_zone', 'statistics']
            ]
        ];
        
        // Only get additional info if tables exist
        if ($addresses_table_exists) {
            try {
                $addresses_count = $addressController->getCount();
                $response['data']['addresses_count'] = $addresses_count;
                
                // Get table structure
                $stmt = $pdo->query("DESCRIBE Addresses");
                $table_structure = $stmt->fetchAll();
                $response['data']['addresses_table_structure'] = array_column($table_structure, 'Field');
                
                // Get sample address
                $stmt = $pdo->query("SELECT * FROM Addresses LIMIT 1");
                $sample_address = $stmt->fetch();
                $response['data']['sample_address'] = $sample_address;
            } catch (Exception $e) {
                $response['data']['addresses_table_error'] = $e->getMessage();
            }
        }

        if ($province_table_exists) {
            try {
                $provinces = $addressController->getProvinces();
                $response['data']['provinces_count'] = count($provinces);
                $response['data']['sample_provinces'] = array_slice($provinces, 0, 5);
            } catch (Exception $e) {
                $response['data']['province_table_error'] = $e->getMessage();
            }
        }
        
        echo json_encode($response);
    } catch (Exception $e) {
        error_log("Address test error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Test failed: ' . $e->getMessage(),
            'code' => 'TEST_ERROR'
        ]);
    }
}

/**
 * Handle create new address
 */
function handleCreate($addressController)
{
    try {
        // Capture input data
        $user_id = $_POST['user_id'] ?? $_COOKIE['user_id'] ?? '';
        $recipient_name = trim($_POST['recipient_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address_line = trim($_POST['address_line'] ?? '');
        $subdistrict = trim($_POST['subdistrict'] ?? '');
        $district = trim($_POST['district'] ?? '');
        $province_id = trim($_POST['province_id'] ?? '');
        $postal_code = trim($_POST['postal_code'] ?? '');
        $is_main = isset($_POST['is_main']) ? (bool)$_POST['is_main'] : false;

        // Authentication check
        if (empty($user_id)) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'ไม่ได้รับอนุญาต',
                'code' => 'NOT_AUTHENTICATED'
            ]);
            return;
        }

        // Validation using controller method
        $validation_data = [
            'recipient_name' => $recipient_name,
            'phone' => $phone,
            'address_line' => $address_line,
            'subdistrict' => $subdistrict,
            'district' => $district,
            'province_id' => $province_id,
            'postal_code' => $postal_code
        ];

        $validation_errors = $addressController->validateAddressData($validation_data);
        
        if (!empty($validation_errors)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validation_errors,
                'code' => 'VALIDATION_ERROR'
            ]);
            return;
        }

        // Create address
        $result = $addressController->create($user_id, $recipient_name, $phone, $address_line, 
                                           $subdistrict, $district, $province_id, $postal_code, $is_main);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'เพิ่มที่อยู่สำเร็จ',
                'data' => ['address_id' => $result]
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'ไม่สามารถเพิ่มที่อยู่ได้ กรุณาลองใหม่อีกครั้ง',
                'code' => 'CREATE_FAILED'
            ]);
        }
    } catch (Exception $e) {
        error_log("Create address error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'เกิดข้อผิดพลาดในการเพิ่มที่อยู่',
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
 * Handle get address by ID
 */
function handleGet($addressController)
{
    try {
        $address_id = $_GET['address_id'] ?? '';

        if (empty($address_id)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'กรุณาระบุ address_id',
                'code' => 'MISSING_ADDRESS_ID'
            ]);
            return;
        }

        $address = $addressController->getById($address_id);

        if ($address) {
            echo json_encode([
                'success' => true,
                'data' => $address
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false, 
                'message' => 'ไม่พบที่อยู่',
                'code' => 'ADDRESS_NOT_FOUND'
            ]);
        }
    } catch (Exception $e) {
        error_log("Get address error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'เกิดข้อผิดพลาด',
            'code' => 'SERVER_ERROR'
        ]);
    }
}

/**
 * Handle get addresses by user ID
 */
function handleGetByUser($addressController)
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

        $addresses = $addressController->getByUserId($user_id);

        echo json_encode([
            'success' => true,
            'data' => $addresses,
            'count' => count($addresses)
        ]);
    } catch (Exception $e) {
        error_log("Get addresses by user error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'เกิดข้อผิดพลาด',
            'code' => 'SERVER_ERROR'
        ]);
    }
}

/**
 * Handle get main address by user ID
 */
function handleGetMain($addressController)
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

        $address = $addressController->getMainByUserId($user_id);

        if ($address) {
            echo json_encode([
                'success' => true,
                'data' => $address
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'data' => null,
                'message' => 'ไม่พบที่อยู่หลัก'
            ]);
        }
    } catch (Exception $e) {
        error_log("Get main address error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'เกิดข้อผิดพลาด',
            'code' => 'SERVER_ERROR'
        ]);
    }
}

/**
 * Handle update address
 */
function handleUpdate($addressController)
{
    try {
        $address_id = $_POST['address_id'] ?? '';
        $user_id = $_POST['user_id'] ?? $_COOKIE['user_id'] ?? '';
        $recipient_name = trim($_POST['recipient_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address_line = trim($_POST['address_line'] ?? '');
        $subdistrict = trim($_POST['subdistrict'] ?? '');
        $district = trim($_POST['district'] ?? '');
        $province_id = trim($_POST['province_id'] ?? '');
        $postal_code = trim($_POST['postal_code'] ?? '');
        $is_main = isset($_POST['is_main']) ? (bool)$_POST['is_main'] : false;

        // Authentication check
        if (empty($user_id)) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'ไม่ได้รับอนุญาต',
                'code' => 'NOT_AUTHENTICATED'
            ]);
            return;
        }

        if (empty($address_id)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'กรุณาระบุ address_id',
                'code' => 'MISSING_ADDRESS_ID'
            ]);
            return;
        }

        // Validation using controller method
        $validation_data = [
            'recipient_name' => $recipient_name,
            'phone' => $phone,
            'address_line' => $address_line,
            'subdistrict' => $subdistrict,
            'district' => $district,
            'province_id' => $province_id,
            'postal_code' => $postal_code
        ];

        $validation_errors = $addressController->validateAddressData($validation_data);
        
        if (!empty($validation_errors)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validation_errors,
                'code' => 'VALIDATION_ERROR'
            ]);
            return;
        }

        // Check if address belongs to user
        $existing_address = $addressController->getById($address_id);
        if (!$existing_address || $existing_address['user_id'] != $user_id) {
            http_response_code(403);
            echo json_encode([
                'success' => false, 
                'message' => 'คุณไม่มีสิทธิ์แก้ไขที่อยู่นี้',
                'code' => 'FORBIDDEN'
            ]);
            return;
        }

        // Update address
        $result = $addressController->update($address_id, $recipient_name, $phone, $address_line, 
                                           $subdistrict, $district, $province_id, $postal_code, $is_main);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'อัปเดตที่อยู่สำเร็จ'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'ไม่สามารถอัปเดตที่อยู่ได้',
                'code' => 'UPDATE_FAILED'
            ]);
        }
    } catch (Exception $e) {
        error_log("Update address error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'เกิดข้อผิดพลาด',
            'code' => 'SERVER_ERROR'
        ]);
    }
}

/**
 * Handle delete address
 */
function handleDelete($addressController)
{
    try {
        $address_id = $_POST['address_id'] ?? $_GET['address_id'] ?? '';
        $user_id = $_POST['user_id'] ?? $_COOKIE['user_id'] ?? '';

        // Authentication check
        if (empty($user_id)) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'ไม่ได้รับอนุญาต',
                'code' => 'NOT_AUTHENTICATED'
            ]);
            return;
        }

        if (empty($address_id)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'กรุณาระบุ address_id',
                'code' => 'MISSING_ADDRESS_ID'
            ]);
            return;
        }

        // Check if address belongs to user
        $existing_address = $addressController->getById($address_id);
        if (!$existing_address || $existing_address['user_id'] != $user_id) {
            http_response_code(403);
            echo json_encode([
                'success' => false, 
                'message' => 'คุณไม่มีสิทธิ์ลบที่อยู่นี้',
                'code' => 'FORBIDDEN'
            ]);
            return;
        }

        // Delete address
        $result = $addressController->delete($address_id);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'ลบที่อยู่สำเร็จ'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'ไม่สามารถลบที่อยู่ได้',
                'code' => 'DELETE_FAILED'
            ]);
        }
    } catch (Exception $e) {
        error_log("Delete address error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'เกิดข้อผิดพลาด',
            'code' => 'SERVER_ERROR'
        ]);
    }
}

/**
 * Handle set address as main
 */
function handleSetMain($addressController)
{
    try {
        $address_id = $_POST['address_id'] ?? '';
        $user_id = $_POST['user_id'] ?? $_COOKIE['user_id'] ?? '';

        // Authentication check
        if (empty($user_id)) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'ไม่ได้รับอนุญาต',
                'code' => 'NOT_AUTHENTICATED'
            ]);
            return;
        }

        if (empty($address_id)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'กรุณาระบุ address_id',
                'code' => 'MISSING_ADDRESS_ID'
            ]);
            return;
        }

        // Check if address belongs to user
        $existing_address = $addressController->getById($address_id);
        if (!$existing_address || $existing_address['user_id'] != $user_id) {
            http_response_code(403);
            echo json_encode([
                'success' => false, 
                'message' => 'คุณไม่มีสิทธิ์แก้ไขที่อยู่นี้',
                'code' => 'FORBIDDEN'
            ]);
            return;
        }

        // Set as main address
        $result = $addressController->setAsMain($address_id, $user_id);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'ตั้งเป็นที่อยู่หลักสำเร็จ'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'ไม่สามารถตั้งเป็นที่อยู่หลักได้',
                'code' => 'SET_MAIN_FAILED'
            ]);
        }
    } catch (Exception $e) {
        error_log("Set main address error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'เกิดข้อผิดพลาด',
            'code' => 'SERVER_ERROR'
        ]);
    }
}

/**
 * Handle list addresses with pagination and search
 */
function handleList($addressController)
{
    try {
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = max(1, min(100, intval($_GET['limit'] ?? 10)));
        $search = trim($_GET['search'] ?? '');
        $user_id = $_GET['user_id'] ?? '';

        $offset = ($page - 1) * $limit;

        $addresses = $addressController->getList($limit, $offset, $search, $user_id);
        $total = $addressController->getCount($search, $user_id);

        echo json_encode([
            'success' => true,
            'data' => $addresses,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'total_pages' => ceil($total / $limit),
                'has_next' => $page * $limit < $total,
                'has_prev' => $page > 1
            ]
        ]);
    } catch (Exception $e) {
        error_log("List addresses error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'เกิดข้อผิดพลาด',
            'code' => 'SERVER_ERROR'
        ]);
    }
}

/**
 * Handle get provinces
 */
function handleGetProvinces($addressController)
{
    try {
        $provinces = $addressController->getProvinces();

        echo json_encode([
            'success' => true,
            'data' => $provinces,
            'count' => count($provinces)
        ]);
    } catch (Exception $e) {
        error_log("Get provinces error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'เกิดข้อผิดพลาด',
            'code' => 'SERVER_ERROR'
        ]);
    }
}

/**
 * Handle get provinces by zone
 */
function handleGetProvincesByZone($addressController)
{
    try {
        $zone_id = $_GET['zone_id'] ?? '';

        if (empty($zone_id)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'กรุณาระบุ zone_id',
                'code' => 'MISSING_ZONE_ID'
            ]);
            return;
        }

        $provinces = $addressController->getProvincesByZone($zone_id);

        echo json_encode([
            'success' => true,
            'data' => $provinces,
            'count' => count($provinces)
        ]);
    } catch (Exception $e) {
        error_log("Get provinces by zone error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'เกิดข้อผิดพลาด',
            'code' => 'SERVER_ERROR'
        ]);
    }
}

/**
 * Handle get address statistics
 */
function handleStatistics($addressController)
{
    try {
        $statistics = $addressController->getStatistics();

        echo json_encode([
            'success' => true,
            'data' => $statistics
        ]);
    } catch (Exception $e) {
        error_log("Get statistics error: " . $e->getMessage());
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