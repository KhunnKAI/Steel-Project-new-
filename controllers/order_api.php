<?php
// order_api.php - Order API for profile page
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

// Error handling
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

    // Test database connection
    if (!isset($pdo) || !$pdo) {
        throw new Exception('Database connection failed');
    }

    // Handle different actions
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'get_user_orders':
            handleGetUserOrders($pdo);
            break;
        case 'get_order_details':
            handleGetOrderDetails($pdo);
            break;
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'Invalid action: ' . $action,
                'code' => 'INVALID_ACTION',
                'available_actions' => [
                    'get_user_orders', 'get_order_details'
                ]
            ]);
            break;
    }

} catch (Exception $e) {
    error_log("Order API Error: " . $e->getMessage());
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
 * Handle get user orders for profile page
 */
function handleGetUserOrders($pdo)
{
    try {
        $user_id = $_GET['user_id'] ?? $_COOKIE['user_id'] ?? '';
        $limit = isset($_GET['limit']) ? max(1, min(50, intval($_GET['limit']))) : 10;
        $offset = isset($_GET['offset']) ? max(0, intval($_GET['offset'])) : 0;

        if (empty($user_id)) {
            http_response_code(401);
            echo json_encode([
                'success' => false, 
                'message' => 'ไม่ได้รับอนุญาต',
                'code' => 'NOT_AUTHENTICATED'
            ]);
            return;
        }

        // Query to get orders with status information
        $sql = "SELECT 
                    o.order_id,
                    o.user_id,
                    o.total_amount,
                    o.total_novat,
                    o.shipping_fee,
                    o.status AS status_id,
                    s.status_code,
                    s.description AS status_description,
                    o.note,
                    o.created_at,
                    o.updated_at,
                    -- Payment info
                    p.payment_id,
                    p.slip_image,
                    p.admin_id AS verified_by_admin,
                    p.created_at AS payment_created_at,
                    -- Address info
                    addr.address_id,
                    addr.recipient_name,
                    addr.phone AS address_phone,
                    addr.address_line,
                    addr.subdistrict,
                    addr.district,
                    prov.name AS province_name,
                    addr.postal_code
                FROM Orders o
                LEFT JOIN Status s ON o.status = s.status_id
                LEFT JOIN Payment p ON o.order_id = p.order_id
                LEFT JOIN Addresses addr ON o.user_id = addr.user_id AND addr.is_main = 1
                LEFT JOIN Province prov ON addr.province_id = prov.province_id
                WHERE o.user_id = ?
                ORDER BY o.created_at DESC 
                LIMIT ? OFFSET ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $limit, $offset]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get order items for each order
        if (!empty($orders)) {
            $order_ids = array_column($orders, 'order_id');
            $placeholders = str_repeat('?,', count($order_ids) - 1) . '?';

            $items_sql = "SELECT 
                            oi.order_item_id,
                            oi.order_id,
                            oi.product_id,
                            p.name AS product_name,
                            p.description AS product_description,
                            oi.quantity,
                            oi.price_each,
                            oi.weight_each,
                            oi.lot,
                            (oi.quantity * oi.price_each) as line_total,
                            pi.image_url AS product_image
                          FROM OrderItem oi
                          LEFT JOIN Product p ON oi.product_id = p.product_id
                          LEFT JOIN ProductImage pi ON p.product_id = pi.product_id AND pi.is_main = 1
                          WHERE oi.order_id IN ($placeholders)
                          ORDER BY oi.order_item_id";

            $items_stmt = $pdo->prepare($items_sql);
            $items_stmt->execute($order_ids);
            $order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

            // Group items by order_id
            $items_by_order = [];
            foreach ($order_items as $item) {
                $items_by_order[$item['order_id']][] = [
                    'order_item_id' => $item['order_item_id'],
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'product_description' => $item['product_description'],
                    'quantity' => intval($item['quantity']),
                    'price_each' => floatval($item['price_each']),
                    'weight_each' => floatval($item['weight_each']),
                    'line_total' => floatval($item['line_total']),
                    'lot' => $item['lot']
                ];
            }

            // Add items to orders
            foreach ($orders as &$order) {
                $order['order_items'] = $items_by_order[$order['order_id']] ?? [];
                
                // Calculate total quantity
                $total_quantity = 0;
                foreach ($order['order_items'] as $item) {
                    $total_quantity += $item['quantity'];
                }
                $order['total_quantity'] = $total_quantity;

                // Format status
                $order['status'] = [
                    'status_id' => $order['status_id'],
                    'status_code' => $order['status_code'],
                    'description' => $order['status_description']
                ];

                // Format payment info
                $order['payment_info'] = null;
                if ($order['payment_id']) {
                    $order['payment_info'] = [
                        'payment_id' => $order['payment_id'],
                        'slip_image' => $order['slip_image'] ? 'controllers/uploads/payment_slips/' . $order['slip_image'] : null,
                        'verified_by_admin' => $order['verified_by_admin'],
                        'payment_created_at' => $order['payment_created_at'],
                        'is_verified' => !empty($order['verified_by_admin'])
                    ];
                }

                // Format shipping address
                $order['shipping_address'] = null;
                if ($order['address_id']) {
                    $order['shipping_address'] = [
                        'address_id' => $order['address_id'],
                        'recipient_name' => $order['recipient_name'],
                        'phone' => $order['address_phone'],
                        'address_line' => $order['address_line'],
                        'subdistrict' => $order['subdistrict'],
                        'district' => $order['district'],
                        'province' => $order['province_name'],
                        'postal_code' => $order['postal_code']
                    ];
                }

                // Remove raw fields
                unset($order['status_id'], $order['status_code'], $order['status_description']);
                unset($order['payment_id'], $order['slip_image'], $order['verified_by_admin'], $order['payment_created_at']);
                unset($order['address_id'], $order['recipient_name'], $order['address_phone'], $order['address_line']);
                unset($order['subdistrict'], $order['district'], $order['province_name'], $order['postal_code']);
            }
        }

        // Get total count for pagination
        $count_sql = "SELECT COUNT(*) as total FROM Orders WHERE user_id = ?";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute([$user_id]);
        $total_count = $count_stmt->fetchColumn();

        echo json_encode([
            'success' => true,
            'data' => $orders,
            'pagination' => [
                'total' => intval($total_count),
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $total_count,
                'current_page' => floor($offset / $limit) + 1,
                'total_pages' => ceil($total_count / $limit)
            ]
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        error_log("Get user orders error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลคำสั่งซื้อ',
            'code' => 'SERVER_ERROR'
        ]);
    }
}

/**
 * Handle get order details
 */
function handleGetOrderDetails($pdo)
{
    try {
        $order_id = $_GET['order_id'] ?? '';
        $user_id = $_GET['user_id'] ?? $_COOKIE['user_id'] ?? '';

        if (empty($order_id) || empty($user_id)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'ข้อมูลไม่ครบถ้วน',
                'code' => 'MISSING_DATA'
            ]);
            return;
        }

        // Get order details with all related information
        $sql = "SELECT 
                    o.order_id,
                    o.user_id,
                    o.total_amount,
                    o.total_novat,
                    o.shipping_fee,
                    o.status AS status_id,
                    s.status_code,
                    s.description AS status_description,
                    o.note,
                    o.created_at,
                    o.updated_at,
                    -- Customer info
                    u.name AS customer_name,
                    u.email AS customer_email,
                    u.phone AS customer_phone,
                    -- Payment info
                    p.payment_id,
                    p.slip_image,
                    p.admin_id AS verified_by_admin,
                    p.created_at AS payment_created_at,
                    -- Address info
                    addr.address_id,
                    addr.recipient_name,
                    addr.phone AS address_phone,
                    addr.address_line,
                    addr.subdistrict,
                    addr.district,
                    prov.name AS province_name,
                    addr.postal_code
                FROM Orders o
                LEFT JOIN Users u ON o.user_id = u.user_id
                LEFT JOIN Status s ON o.status = s.status_id
                LEFT JOIN Payment p ON o.order_id = p.order_id
                LEFT JOIN Addresses addr ON o.user_id = addr.user_id AND addr.is_main = 1
                LEFT JOIN Province prov ON addr.province_id = prov.province_id
                WHERE o.order_id = ? AND o.user_id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$order_id, $user_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            http_response_code(404);
            echo json_encode([
                'success' => false, 
                'message' => 'ไม่พบคำสั่งซื้อ',
                'code' => 'ORDER_NOT_FOUND'
            ]);
            return;
        }

        // Get order items
        $items_sql = "SELECT 
                        oi.order_item_id,
                        oi.order_id,
                        oi.product_id,
                        p.name AS product_name,
                        p.description AS product_description,
                        oi.quantity,
                        oi.price_each,
                        oi.weight_each,
                        oi.lot,
                        (oi.quantity * oi.price_each) as line_total,
                        pi.image_url AS product_image
                      FROM OrderItem oi
                      LEFT JOIN Product p ON oi.product_id = p.product_id
                      LEFT JOIN ProductImage pi ON p.product_id = pi.product_id AND pi.is_main = 1
                      WHERE oi.order_id = ?
                      ORDER BY oi.order_item_id";

        $items_stmt = $pdo->prepare($items_sql);
        $items_stmt->execute([$order_id]);
        $order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format order items
        $order['order_items'] = [];
        foreach ($order_items as $item) {
                $order['order_items'][] = [
                    'order_item_id' => $item['order_item_id'],
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'product_description' => $item['product_description'],
                    'quantity' => intval($item['quantity']),
                    'price_each' => floatval($item['price_each']),
                    'weight_each' => floatval($item['weight_each']),
                    'line_total' => floatval($item['line_total']),
                    'lot' => $item['lot'],
                    'product_image' => $item['product_image']
                ];
        }

        // Calculate total quantity
        $total_quantity = 0;
        foreach ($order['order_items'] as $item) {
            $total_quantity += $item['quantity'];
        }
        $order['total_quantity'] = $total_quantity;

        // Format customer info
        $order['customer_info'] = [
            'name' => $order['customer_name'],
            'email' => $order['customer_email'],
            'phone' => $order['customer_phone']
        ];

        // Format status
        $order['status'] = [
            'status_id' => $order['status_id'],
            'status_code' => $order['status_code'],
            'description' => $order['status_description']
        ];

        // Format payment info
        $order['payment_info'] = null;
        if ($order['payment_id']) {
            $order['payment_info'] = [
                'payment_id' => $order['payment_id'],
                'slip_image' => $order['slip_image'] ? 'controllers/uploads/payment_slips/' . $order['slip_image'] : null,
                'verified_by_admin' => $order['verified_by_admin'],
                'payment_created_at' => $order['payment_created_at'],
                'is_verified' => !empty($order['verified_by_admin'])
            ];
        }

        // Format shipping address
        $order['shipping_address'] = null;
        if ($order['address_id']) {
            $order['shipping_address'] = [
                'address_id' => $order['address_id'],
                'recipient_name' => $order['recipient_name'],
                'phone' => $order['address_phone'],
                'address_line' => $order['address_line'],
                'subdistrict' => $order['subdistrict'],
                'district' => $order['district'],
                'province' => $order['province_name'],
                'postal_code' => $order['postal_code']
            ];
        }

        // Remove raw fields
        unset($order['customer_name'], $order['customer_email'], $order['customer_phone']);
        unset($order['status_id'], $order['status_code'], $order['status_description']);
        unset($order['payment_id'], $order['slip_image'], $order['verified_by_admin'], $order['payment_created_at']);
        unset($order['address_id'], $order['recipient_name'], $order['address_phone'], $order['address_line']);
        unset($order['subdistrict'], $order['district'], $order['province_name'], $order['postal_code']);

        echo json_encode([
            'success' => true,
            'data' => $order
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        error_log("Get order details error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลคำสั่งซื้อ',
            'code' => 'SERVER_ERROR'
        ]);
    }
}

// Flush output buffer and end
ob_end_flush();
?>
