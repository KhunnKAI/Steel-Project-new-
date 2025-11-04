<?php
// ========================
// SETUP & ERROR HANDLING
// ========================

ob_start();
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

// ========================
// INITIALIZATION
// ========================

require_once 'config.php';
require_once 'stock_logger.php';
session_start();

try {
    // ========================
    // AUTHENTICATION
    // ========================

    // FUNCTION: ตรวจสอบและตั้งค่า admin session
    if (!isset($_SESSION['admin_id'])) {
        $_SESSION['admin_id'] = 'admin001';
        $_SESSION['position'] = 'manager';
    }

    $admin_id = $_SESSION['admin_id'];
    $admin_position = $_SESSION['position'] ?? 'manager';

    // ========================
    // VALIDATE REQUEST
    // ========================

    // FUNCTION: ตรวจสอบวิธี HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    // ========================
    // PARSE INPUT
    // ========================

    // FUNCTION: แยกวิเคราะห์ JSON input
    $json_input = file_get_contents('php://input');
    $input = json_decode($json_input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }

    // ========================
    // VALIDATE INPUT DATA
    // ========================

    // FUNCTION: ตรวจสอบความถูกต้องของข้อมูลที่รับมา
    $order_id = trim($input['order_id'] ?? '');
    $new_status = trim($input['status'] ?? '');
    $notes = trim($input['notes'] ?? '');

    if (empty($order_id) || empty($new_status)) {
        throw new Exception('กรุณาระบุรหัสคำสั่งซื้อและสถานะใหม่');
    }

    // ========================
    // MAP STATUS
    // ========================

    // FUNCTION: แม็ป status code ไปยัง status ID
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

    // ========================
    // GET CURRENT ORDER
    // ========================

    // FUNCTION: ดึงข้อมูลคำสั่งซื้อปัจจุบัน
    $check_sql = "SELECT 
                    o.order_id, 
                    o.status, 
                    o.user_id,
                    s.status_code,
                    s.description as current_status_desc
                  FROM Orders o
                  LEFT JOIN Status s ON o.status = s.status_id 
                  WHERE o.order_id = ?";
    
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$order_id]);
    $order = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('ไม่พบคำสั่งซื้อที่ระบุ: ' . $order_id);
    }

    $current_status = $order['status_code'];

    // ========================
    // INITIALIZE STOCK LOGGER
    // ========================

    // FUNCTION: สร้าง instance ของ stock logger
    $stockLogger = new StockLogger($pdo);

    // ========================
    // DATABASE TRANSACTION
    // ========================

    // FUNCTION: เริ่มต้น transaction
    $pdo->beginTransaction();

    try {
        // ========================
        // UPDATE ORDER STATUS
        // ========================

        // FUNCTION: อัพเดตสถานะคำสั่งซื้อ
        $update_sql = "UPDATE Orders SET 
                        status = ?, 
                        note = ?, 
                        updated_at = NOW()
                       WHERE order_id = ?";
        
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute([$status_id, $notes, $order_id]);

        if ($update_stmt->rowCount() === 0) {
            throw new Exception('No rows were updated - order may not exist');
        }

        // ========================
        // HANDLE CANCELLATION
        // ========================

        // FUNCTION: คืนสต็อกเมื่อยกเลิกคำสั่งซื้อ
        if ($new_status === 'cancelled' && $current_status !== 'cancelled') {
            $items_sql = "SELECT oi.product_id, oi.quantity, p.name as product_name 
                         FROM OrderItem oi
                         JOIN Product p ON oi.product_id = p.product_id
                         WHERE oi.order_id = ?";
            $items_stmt = $pdo->prepare($items_sql);
            $items_stmt->execute([$order_id]);
            $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stock_updates = [];
            foreach ($items as $item) {
                // FUNCTION: ใช้ stock logger เพื่อคืนสต็อก
                $stock_result = $stockLogger->updateProductStock(
                    $item['product_id'],
                    'in',
                    $item['quantity'],
                    'cancel',
                    $order_id,
                    $order['user_id'],
                    $admin_id,
                    "Stock restored due to order cancellation by admin: {$admin_id}"
                );
                
                if (!$stock_result['success']) {
                    throw new Exception("ไม่สามารถคืนสต็อกได้สำหรับสินค้า: {$item['product_name']} - " . $stock_result['error']);
                }
                
                $stock_updates[] = [
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity_restored' => $item['quantity'],
                    'stock_before' => $stock_result['quantity_before'],
                    'stock_after' => $stock_result['quantity_after']
                ];
                
                error_log("Stock restored - Product: {$item['product_id']}, Quantity: {$item['quantity']}, Before: {$stock_result['quantity_before']}, After: {$stock_result['quantity_after']}");
            }
        }

        // FUNCTION: ยืนยัน transaction
        $pdo->commit();

        // ========================
        // PREPARE RESPONSE
        // ========================

        // FUNCTION: เตรียมข้อความตอบกลับตามสถานะ
        $status_messages = [
            'cancelled' => 'ยกเลิกคำสั่งซื้อเรียบร้อยแล้ว และคืนสต็อกสินค้าเรียบร้อย',
            'awaiting_shipment' => 'อนุมัติการชำระเงินและเตรียมจัดส่งแล้ว',
            'in_transit' => 'อัปเดตสถานะเป็นอำเนินจัดส่งแล้ว',
            'delivered' => 'อัปเดตสถานะเป็นจัดส่งแล้วเรียบร้อย'
        ];

        $message = $status_messages[$new_status] ?? 'อัปเดตสถานะคำสั่งซื้อเรียบร้อยแล้ว';

        $response_data = [
            'order_id' => $order_id,
            'old_status' => $current_status,
            'new_status' => $new_status,
            'updated_by' => $admin_id,
            'notes' => $notes
        ];

        if (isset($stock_updates) && !empty($stock_updates)) {
            $response_data['stock_restored'] = $stock_updates;
            $response_data['total_items_restored'] = count($stock_updates);
        }

        ob_clean();
        
        // ========================
        // SUCCESS RESPONSE
        // ========================

        // FUNCTION: ส่งคำตอบสำเร็จ
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $response_data
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        // FUNCTION: ยกเลิก transaction เมื่อเกิดข้อผิดพลาด
        $pdo->rollback();
        throw $e;
    }

} catch (Exception $e) {
    // ========================
    // ERROR HANDLING
    // ========================

    // FUNCTION: ยกเลิก transaction ถ้ายังดำเนินการอยู่
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    error_log("Order status update error: " . $e->getMessage());
    
    ob_clean();
    http_response_code(400);
    
    // FUNCTION: ส่งคำตอบข้อผิดพลาด
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

ob_end_flush();

?>