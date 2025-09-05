<?php
// Prevent any HTML output before JSON response
ob_start();
error_reporting(0); // Disable HTML error output
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

// Clean any output buffer before sending JSON
ob_clean();

require_once 'config.php';
session_start();

try {
    // Check if admin is logged in
    if (!isset($_SESSION['admin_id'])) {
        // For testing purposes, create a temporary admin session
        $_SESSION['admin_id'] = 'admin001';
        $_SESSION['position'] = 'manager';
    }

    // Get admin info
    $admin_id = $_SESSION['admin_id'];
    $admin_position = $_SESSION['position'] ?? 'manager';

    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    // Get JSON input
    $json_input = file_get_contents('php://input');
    $input = json_decode($json_input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input: ' . json_last_error_msg());
    }

    if (!$input) {
        throw new Exception('Empty input data');
    }

    $order_id = trim($input['order_id'] ?? '');
    $new_status = trim($input['status'] ?? '');
    $notes = trim($input['notes'] ?? '');
    $tracking_number = trim($input['tracking_number'] ?? '');

    // Validate required fields
    if (empty($order_id) || empty($new_status)) {
        throw new Exception('กรุณาระบุรหัสคำสั่งซื้อและสถานะใหม่');
    }

    // Get status mapping
    $status_mapping = [
        'pending_payment' => 'status01',
        'awaiting_shipment' => 'status02',
        'in_transit' => 'status03',
        'delivered' => 'status04',
        'cancelled' => 'status05'
    ];
    
    if (!isset($status_mapping[$new_status])) {
        throw new Exception('สถานะที่ระบุไม่ถูกต้อง: ' . $new_status);
    }
    
    $status_id = $status_mapping[$new_status];

    // Check database connection
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }

    // Check if order exists and get current info
    $check_sql = "SELECT 
                    o.order_id, 
                    o.status, 
                    o.user_id,
                    o.total_amount,
                    s.status_code,
                    s.description as current_status_desc,
                    u.name as customer_name,
                    u.email as customer_email,
                    u.phone as customer_phone
                  FROM Orders o
                  LEFT JOIN Status s ON o.status = s.status_id 
                  LEFT JOIN Users u ON o.user_id = u.user_id
                  WHERE o.order_id = ?";
    
    $check_stmt = $pdo->prepare($check_sql);
    if (!$check_stmt) {
        throw new Exception('Failed to prepare check query: ' . implode(', ', $pdo->errorInfo()));
    }
    
    $check_result = $check_stmt->execute([$order_id]);
    if (!$check_result) {
        throw new Exception('Failed to execute check query: ' . implode(', ', $check_stmt->errorInfo()));
    }
    
    $order = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('ไม่พบคำสั่งซื้อที่ระบุ: ' . $order_id);
    }

    $current_status = $order['status_code'];

    // Validation logic for status transitions
    $allowed_transitions = [
        'pending_payment' => ['awaiting_shipment', 'cancelled'],
        'awaiting_shipment' => ['in_transit', 'cancelled'],
        'in_transit' => ['delivered', 'cancelled'],
        'delivered' => ['cancelled'], // Allow cancellation for returns/refunds
        'cancelled' => [] // Cannot change from cancelled
    ];

    // Special rule: Allow cancellation from any status (except already cancelled)
    if ($new_status === 'cancelled') {
        if ($current_status === 'cancelled') {
            throw new Exception('คำสั่งซื้อนี้ถูกยกเลิกแล้ว');
        }
        // Allow cancellation from any other status
    } else {
        // For non-cancellation status changes, check normal transitions
        if (!isset($allowed_transitions[$current_status]) || 
            !in_array($new_status, $allowed_transitions[$current_status])) {
            throw new Exception("ไม่สามารถเปลี่ยนจาก {$current_status} เป็น {$new_status} ได้");
        }
    }

    // Begin transaction
    $pdo->beginTransaction();

    try {
        // Update order status
        $update_sql = "UPDATE Orders SET 
                        status = ?, 
                        note = ?, 
                        updated_at = NOW()
                       WHERE order_id = ?";
        $update_params = [$status_id, $notes, $order_id];

        $update_stmt = $pdo->prepare($update_sql);
        if (!$update_stmt) {
            throw new Exception('Failed to prepare update query: ' . implode(', ', $pdo->errorInfo()));
        }
        
        $update_result = $update_stmt->execute($update_params);
        if (!$update_result) {
            throw new Exception('Failed to update order: ' . implode(', ', $update_stmt->errorInfo()));
        }

        // Check if any rows were affected
        if ($update_stmt->rowCount() === 0) {
            throw new Exception('No rows were updated - order may not exist');
        }

        // Handle stock restoration when order is cancelled
        if ($new_status === 'cancelled' && $current_status !== 'cancelled') {
            // Get order items to restore stock
            $items_sql = "SELECT product_id, quantity FROM OrderItem WHERE order_id = ?";
            $items_stmt = $pdo->prepare($items_sql);
            
            if (!$items_stmt) {
                throw new Exception('Failed to prepare items query: ' . implode(', ', $pdo->errorInfo()));
            }
            
            $items_result = $items_stmt->execute([$order_id]);
            if (!$items_result) {
                throw new Exception('Failed to get order items: ' . implode(', ', $items_stmt->errorInfo()));
            }
            
            $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Restore stock for each item
            $stock_updates = [];
            foreach ($items as $item) {
                $restore_stock_sql = "UPDATE Product SET 
                                      stock = stock + ?, 
                                      updated_at = NOW()
                                      WHERE product_id = ?";
                
                $restore_stmt = $pdo->prepare($restore_stock_sql);
                if (!$restore_stmt) {
                    throw new Exception('Failed to prepare stock restore query for product: ' . $item['product_id']);
                }
                
                $restore_result = $restore_stmt->execute([$item['quantity'], $item['product_id']]);
                if (!$restore_result) {
                    throw new Exception('Failed to restore stock for product: ' . $item['product_id']);
                }
                
                $stock_updates[] = [
                    'product_id' => $item['product_id'],
                    'quantity_restored' => $item['quantity']
                ];
                
                // Log stock restoration for audit trail
                try {
                    $stock_log_sql = "INSERT INTO stock_log (product_id, order_id, change_type, quantity_change, admin_id, notes, created_at) 
                                      VALUES (?, ?, 'restore', ?, ?, ?, NOW())";
                    $stock_log_stmt = $pdo->prepare($stock_log_sql);
                    if ($stock_log_stmt) {
                        $stock_log_stmt->execute([
                            $item['product_id'], 
                            $order_id, 
                            $item['quantity'], 
                            $admin_id, 
                            'Stock restored due to order cancellation'
                        ]);
                    }
                } catch (Exception $log_error) {
                    // If logging table doesn't exist, continue without error
                    error_log("Stock restoration logging failed: " . $log_error->getMessage());
                }
            }
        }

        // Handle payment verification if moving to awaiting_shipment
        if ($current_status === 'pending_payment' && $new_status === 'awaiting_shipment') {
            // Try to update payment record if it exists
            $payment_sql = "UPDATE Payment SET 
                            admin_id = ?, 
                            updated_at = NOW() 
                            WHERE order_id = ?";
            $payment_stmt = $pdo->prepare($payment_sql);
            if ($payment_stmt) {
                $payment_stmt->execute([$admin_id, $order_id]);
            }
        }

        // Try to log status change for audit trail (optional)
        try {
            $log_sql = "INSERT INTO order_status_log (order_id, old_status, new_status, admin_id, notes, created_at) 
                        VALUES (?, ?, ?, ?, ?, NOW())";
            $log_stmt = $pdo->prepare($log_sql);
            if ($log_stmt) {
                $log_stmt->execute([$order_id, $current_status, $new_status, $admin_id, $notes]);
            }
        } catch (Exception $log_error) {
            // If logging table doesn't exist, continue without error
            error_log("Status change logging failed: " . $log_error->getMessage());
        }

        // Commit transaction
        $pdo->commit();

    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }

    // Get updated order info
    $updated_sql = "SELECT 
                        o.order_id, 
                        o.status, 
                        s.status_code, 
                        s.description, 
                        o.updated_at,
                        u.name as customer_name, 
                        u.email as customer_email
                    FROM Orders o 
                    LEFT JOIN Status s ON o.status = s.status_id 
                    LEFT JOIN Users u ON o.user_id = u.user_id
                    WHERE o.order_id = ?";
    
    $updated_stmt = $pdo->prepare($updated_sql);
    if (!$updated_stmt) {
        throw new Exception('Failed to prepare updated info query');
    }
    
    $updated_result = $updated_stmt->execute([$order_id]);
    if (!$updated_result) {
        throw new Exception('Failed to get updated order info');
    }
    
    $updated_order = $updated_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$updated_order) {
        throw new Exception('Failed to retrieve updated order information');
    }

    // Prepare response message based on status change
    $status_messages = [
        'cancelled' => 'ยกเลิกคำสั่งซื้อเรียบร้อยแล้ว และคืนสต็อกสินค้าเรียบร้อย',
        'awaiting_shipment' => 'อนุมัติการชำระเงินและเตรียมจัดส่งแล้ว',
        'in_transit' => 'อัปเดตสถานะเป็นกำลังจัดส่งแล้ว',
        'delivered' => 'อัปเดตสถานะเป็นจัดส่งแล้วเรียบร้อย'
    ];

    $message = $status_messages[$new_status] ?? 'อัปเดตสถานะคำสั่งซื้อเรียบร้อยแล้ว';

    // Prepare response data
    $response_data = [
        'order_id' => $updated_order['order_id'],
        'old_status' => [
            'code' => $current_status,
            'description' => $order['current_status_desc']
        ],
        'new_status' => [
            'code' => $updated_order['status_code'],
            'description' => $updated_order['description']
        ],
        'updated_at' => $updated_order['updated_at'],
        'updated_by' => [
            'admin_id' => $admin_id,
            'admin_position' => $admin_position
        ],
        'notes' => $notes,
        'can_be_cancelled' => $updated_order['status_code'] !== 'cancelled'
    ];

    // Add stock restoration info if order was cancelled
    if ($new_status === 'cancelled' && isset($stock_updates) && !empty($stock_updates)) {
        $response_data['stock_restored'] = $stock_updates;
        $response_data['total_items_restored'] = count($stock_updates);
    }

    // Clean output buffer and send JSON response
    ob_clean();
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $response_data
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    // Rollback transaction if it was started
    if (isset($pdo) && $pdo->inTransaction()) {
        try {
            $pdo->rollback();
        } catch (Exception $rollback_error) {
            error_log("Rollback failed: " . $rollback_error->getMessage());
        }
    }
    
    // Log error for debugging
    error_log("Order status update error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Clean output buffer and send error JSON response
    ob_clean();
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Update failed',
        'message' => $e->getMessage(),
        'debug_info' => [
            'order_id' => $order_id ?? 'not set',
            'new_status' => $new_status ?? 'not set',
            'admin_id' => $admin_id ?? 'not set',
            'input_received' => !empty($json_input),
            'json_valid' => json_last_error() === JSON_ERROR_NONE
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    // Handle fatal errors
    ob_clean();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Fatal error occurred',
        'message' => 'เกิดข้อผิดพลาดร้ายแรงในระบบ',
        'debug_info' => [
            'error_type' => get_class($error),
            'error_message' => $error->getMessage()
        ]
    ], JSON_UNESCAPED_UNICODE);
}

// Ensure we end the output buffer
ob_end_flush();
?>