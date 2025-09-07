<?php
// bill_api.php - Bill/Order API handler
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
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

// Clean any previous output
ob_clean();

try {
    // Include required files
    if (!file_exists('config.php')) {
        throw new Exception('Configuration file not found');
    }
    require_once 'config.php';
    
    if (!file_exists('bill_controller.php')) {
        throw new Exception('BillController file not found');
    }
    require_once 'bill_controller.php';

    // Test database connection first
    if (!isset($pdo) || !$pdo) {
        throw new Exception('Database connection failed');
    }

    $billController = new BillController($pdo);

    // Handle different actions
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'test':
            handleTest($billController, $pdo);
            break;
        case 'create_order':
            handleCreateOrder($billController);
            break;
        case 'get_order':
            handleGetOrder($billController);
            break;
        case 'get_orders':
            handleGetOrders($billController);
            break;
        case 'get_user_orders':
            handleGetUserOrders($billController);
            break;
        case 'update_order_status':
            handleUpdateOrderStatus($billController);
            break;
        case 'cancel_order':
            handleCancelOrder($billController);
            break;
        case 'get_order_stats':
            handleGetOrderStats($billController);
            break;
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'Invalid action: ' . $action,
                'code' => 'INVALID_ACTION',
                'available_actions' => [
                    'test', 'create_order', 'get_order', 'get_orders', 
                    'get_user_orders', 'update_order_status', 'cancel_order', 'get_order_stats'
                ]
            ]);
            break;
    }

} catch (Exception $e) {
    error_log("Bill API Error: " . $e->getMessage());
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
 * Handle API test
 */
function handleTest($billController, $pdo)
{
    try {
        // Test database connection
        $stmt = $pdo->query("SELECT 1");
        $db_test = $stmt ? true : false;
        
        // Test if Orders table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'Orders'");
        $orders_table_exists = $stmt->rowCount() > 0;
        
        // Test if StockLog table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'StockLog'");
        $stocklog_table_exists = $stmt->rowCount() > 0;
        
        $response = [
            'success' => true,
            'message' => 'Bill API ทำงานได้ปกติ',
            'data' => [
                'database_connected' => $db_test,
                'orders_table_exists' => $orders_table_exists,
                'stocklog_table_exists' => $stocklog_table_exists,
                'server_time' => date('Y-m-d H:i:s'),
                'php_version' => PHP_VERSION,
                'available_actions' => [
                    'test', 'create_order', 'get_order', 'get_orders', 
                    'get_user_orders', 'update_order_status', 'cancel_order', 'get_order_stats'
                ]
            ]
        ];
        
        // Get additional info if tables exist
        if ($orders_table_exists) {
            try {
                $orders_count = $billController->getOrdersCount();
                $response['data']['orders_count'] = $orders_count;
                
                // Get table structure
                $stmt = $pdo->query("DESCRIBE Orders");
                $orders_structure = $stmt->fetchAll();
                $response['data']['orders_table_structure'] = array_column($orders_structure, 'Field');
                
            } catch (Exception $e) {
                $response['data']['orders_error'] = $e->getMessage();
            }
        }
        
        if ($stocklog_table_exists) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM StockLog");
                $stocklog_count = $stmt->fetch()['count'];
                $response['data']['stocklog_count'] = $stocklog_count;
                
            } catch (Exception $e) {
                $response['data']['stocklog_error'] = $e->getMessage();
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
 * Handle create new order
 */
function handleCreateOrder($billController)
{
    try {
        // Get POST data
        $user_id = $_POST['user_id'] ?? '';
        $total_amount = $_POST['total_amount'] ?? 0;
        $total_novat = $_POST['total_novat'] ?? 0;
        $shipping_fee = $_POST['shipping_fee'] ?? 0;
        $status = $_POST['status'] ?? 'pending';
        $note = $_POST['note'] ?? '';
        $items = json_decode($_POST['items'] ?? '[]', true);

        // Validation
        if (empty($user_id)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'ไม่พบข้อมูลผู้ใช้งาน',
                'code' => 'MISSING_USER_ID'
            ]);
            return;
        }

        if (empty($items) || !is_array($items)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'ไม่พบรายการสินค้า',
                'code' => 'MISSING_ITEMS'
            ]);
            return;
        }

        if ($total_amount <= 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'ยอดเงินไม่ถูกต้อง',
                'code' => 'INVALID_AMOUNT'
            ]);
            return;
        }

        // Create order
        $order_id = $billController->createOrder($user_id, $total_amount, $total_novat, $shipping_fee, $status, $note, $items);

        if ($order_id) {
            echo json_encode([
                'success' => true,
                'message' => 'สร้างคำสั่งซื้อสำเร็จ',
                'data' => [
                    'order_id' => $order_id
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'ไม่สามารถสร้างคำสั่งซื้อได้',
                'code' => 'CREATE_FAILED'
            ]);
        }
    } catch (Exception $e) {
        error_log("Create order error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'เกิดข้อผิดพลาดในการสร้างคำสั่งซื้อ',
            'code' => 'SERVER_ERROR',
            'debug' => [
                'error' => $e->getMessage()
            ]
        ]);
    }
}

/**
 * Handle get single order
 */
function handleGetOrder($billController)
{
    try {
        $order_id = $_GET['order_id'] ?? '';
        $user_id = $_GET['user_id'] ?? $_COOKIE['user_id'] ?? '';

        if (empty($order_id)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'ไม่พบหมายเลขคำสั่งซื้อ',
                'code' => 'MISSING_ORDER_ID'
            ]);
            return;
        }

        $order = $billController->getOrder($order_id, $user_id);

        if ($order) {
            echo json_encode([
                'success' => true,
                'data' => $order
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false, 
                'message' => 'ไม่พบคำสั่งซื้อ',
                'code' => 'ORDER_NOT_FOUND'
            ]);
        }
    } catch (Exception $e) {
        error_log("Get order error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'เกิดข้อผิดพลาด',
            'code' => 'SERVER_ERROR'
        ]);
    }
}

/**
 * Handle get all orders (admin)
 */
function handleGetOrders($billController)
{
    try {
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 10;
        $status = $_GET['status'] ?? '';
        $search = $_GET['search'] ?? '';

        $orders = $billController->getOrders($page, $limit, $status, $search);

        echo json_encode([
            'success' => true,
            'data' => $orders
        ]);
    } catch (Exception $e) {
        error_log("Get orders error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'เกิดข้อผิดพลาด',
            'code' => 'SERVER_ERROR'
        ]);
    }
}

/**
 * Handle get user orders
 */
function handleGetUserOrders($billController)
{
    try {
        $user_id = $_GET['user_id'] ?? $_COOKIE['user_id'] ?? '';
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 10;
        $status = $_GET['status'] ?? '';

        if (empty($user_id)) {
            http_response_code(401);
            echo json_encode([
                'success' => false, 
                'message' => 'ไม่ได้รับอนุญาต',
                'code' => 'NOT_AUTHENTICATED'
            ]);
            return;
        }

        $orders = $billController->getUserOrders($user_id, $page, $limit, $status);

        echo json_encode([
            'success' => true,
            'data' => $orders
        ]);
    } catch (Exception $e) {
        error_log("Get user orders error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'เกิดข้อผิดพลาด',
            'code' => 'SERVER_ERROR'
        ]);
    }
}

/**
 * Handle update order status
 */
function handleUpdateOrderStatus($billController)
{
    try {
        $order_id = $_POST['order_id'] ?? '';
        $status = $_POST['status'] ?? '';
        $admin_id = $_POST['admin_id'] ?? $_COOKIE['user_id'] ?? '';
        $note = $_POST['note'] ?? '';

        if (empty($order_id) || empty($status)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'ข้อมูลไม่ครบถ้วน',
                'code' => 'MISSING_DATA'
            ]);
            return;
        }

        $valid_statuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
        if (!in_array($status, $valid_statuses)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'สถานะไม่ถูกต้อง',
                'code' => 'INVALID_STATUS'
            ]);
            return;
        }

        $result = $billController->updateOrderStatus($order_id, $status, $admin_id, $note);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'อัปเดตสถานะสำเร็จ'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'ไม่สามารถอัปเดตสถานะได้',
                'code' => 'UPDATE_FAILED'
            ]);
        }
    } catch (Exception $e) {
        error_log("Update order status error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'เกิดข้อผิดพลาด',
            'code' => 'SERVER_ERROR'
        ]);
    }
}

/**
 * Handle cancel order
 */
function handleCancelOrder($billController)
{
    try {
        $order_id = $_POST['order_id'] ?? '';
        $user_id = $_POST['user_id'] ?? $_COOKIE['user_id'] ?? '';
        $reason = $_POST['reason'] ?? '';

        if (empty($order_id) || empty($user_id)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'ข้อมูลไม่ครบถ้วน',
                'code' => 'MISSING_DATA'
            ]);
            return;
        }

        $result = $billController->cancelOrder($order_id, $user_id, $reason);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'ยกเลิกคำสั่งซื้อสำเร็จ'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'ไม่สามารถยกเลิกคำสั่งซื้อได้',
                'code' => 'CANCEL_FAILED'
            ]);
        }
    } catch (Exception $e) {
        error_log("Cancel order error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'เกิดข้อผิดพลาด',
            'code' => 'SERVER_ERROR'
        ]);
    }
}

/**
 * Handle get order statistics
 */
function handleGetOrderStats($billController)
{
    try {
        $start_date = $_GET['start_date'] ?? '';
        $end_date = $_GET['end_date'] ?? '';
        $user_id = $_GET['user_id'] ?? '';

        $stats = $billController->getOrderStats($start_date, $end_date, $user_id);

        echo json_encode([
            'success' => true,
            'data' => $stats
        ]);
    } catch (Exception $e) {
        error_log("Get order stats error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'เกิดข้อผิดพลาด',
            'code' => 'SERVER_ERROR'
        ]);
    }
}

// Flush output buffer and end
ob_end_flush();
?>