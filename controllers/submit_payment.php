<?php
session_start();
require_once './config.php';
require_once './shipping_calculator.php';
require_once '../admin/controllers/stock_logger.php'; // Include our stock logger

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "success" => false, 
        "message" => "Method not allowed"
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

try {
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        throw new Exception("กรุณาเข้าสู่ระบบก่อน");
    }

    // Validate required fields
    $requiredFields = ['fullName', 'email', 'phone', 'addressId', 'cartTotal'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("ข้อมูล {$field} ไม่ครบถ้วน");
        }
    }

    // Validate file upload
    if (empty($_FILES['paymentSlip']['name'])) {
        throw new Exception("กรุณาอัพโหลดสลิปการโอนเงิน");
    }

    // Get form data
    $fullName = trim($_POST['fullName']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $note = trim($_POST['note'] ?? '');
    $addressId = $_POST['addressId'];
    $total = floatval($_POST['cartTotal']);
    $subtotal = floatval($_POST['cartSubtotal']);
    $tax = floatval($_POST['cartTax'] ?? 0);
    $shipping = floatval($_POST['cartShipping'] ?? 0);
    $weight = floatval($_POST['cartWeight'] ?? 0);

    // Parse cart items
    $cartItemsJson = $_POST['cartItemsJson'] ?? '[]';
    $cartItems = json_decode($cartItemsJson, true);
    
    if (empty($cartItems)) {
        throw new Exception("ไม่พบรายการสินค้าในตะกร้า");
    }

    // Initialize stock logger
    $stockLogger = new StockLogger($pdo);

    // Validate stock availability
    foreach ($cartItems as $item) {
        $stmt = $pdo->prepare("SELECT stock, name FROM Product WHERE product_id = ?");
        $stmt->execute([$item['product_id']]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            throw new Exception("ไม่พบสินค้า ID: " . $item['product_id']);
        }
        
        if ($product['stock'] < intval($item['quantity'])) {
            throw new Exception("สินค้า '{$product['name']}' มีสต็อกไม่เพียงพอ (เหลือ {$product['stock']} ชิ้น ต้องการ {$item['quantity']} ชิ้น)");
        }
    }

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Generate unique order ID
        $order_id = 'ORD' . date('Ymd') . strtoupper(uniqid());

        // Insert Order
        $stmt = $pdo->prepare("
            INSERT INTO `Orders` (
                order_id, user_id, total_amount, total_novat, shipping_fee, 
                status, note, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $status = "status01"; // pending_payment status
        $stmt->execute([
            $order_id, 
            $user_id, 
            $total, 
            $subtotal, 
            $shipping, 
            $status, 
            $note
        ]);

        // Insert Order Items
        $itemCounter = 1;
        foreach ($cartItems as $item) {
            $microtime = microtime(true);
            $microseconds = sprintf("%06d", ($microtime - floor($microtime)) * 1000000);
            $order_item_id = 'ITM' . date('Ymd') . '_' . $microseconds . '_' . sprintf('%03d', $itemCounter);
            
            $lot = $item['lot'] ?? null;
            if (empty($lot) && !empty($item['product_id'])) {
                $lotStmt = $pdo->prepare("SELECT lot FROM Product WHERE product_id = ?");
                $lotStmt->execute([$item['product_id']]);
                $productData = $lotStmt->fetch(PDO::FETCH_ASSOC);
                $lot = $productData['lot'] ?? null;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO OrderItem (
                    order_item_id, order_id, product_id, quantity, 
                    price_each, weight_each, lot
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $order_item_id,
                $order_id,
                $item['product_id'],
                intval($item['quantity']),
                floatval($item['price']),
                floatval($item['weight'] ?? 0),
                $lot
            ]);
            
            $itemCounter++;
        }

        // UPDATE STOCK with proper logging
        foreach ($cartItems as $item) {
            $quantity = intval($item['quantity']);
            $product_id = $item['product_id'];
            
            // Use stock logger to update stock
            $stock_result = $stockLogger->updateProductStock(
                $product_id,
                'out', // Stock going out
                $quantity,
                'order', // Reference type
                $order_id, // Reference ID
                $user_id, // User ID
                null, // Admin ID (null for customer orders)
                "Stock deducted for order: {$order_id}"
            );
            
            if (!$stock_result['success']) {
                throw new Exception("ไม่สามารถอัพเดทสต็อกได้: " . $stock_result['error']);
            }
            
            error_log("Stock logged - Product: {$product_id}, Before: {$stock_result['quantity_before']}, After: {$stock_result['quantity_after']}");
        }

        // Handle file upload
        $uploadDir = __DIR__ . "/uploads/payment_slips/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileExtension = strtolower(pathinfo($_FILES['paymentSlip']['name'], PATHINFO_EXTENSION));
        $filename = $order_id . "_" . time() . "." . $fileExtension;
        $uploadPath = $uploadDir . $filename;

        if (!move_uploaded_file($_FILES['paymentSlip']['tmp_name'], $uploadPath)) {
            throw new Exception("ไม่สามารถบันทึกไฟล์ได้");
        }

        // Insert Payment record
        $microtime = microtime(true);
        $microseconds = sprintf("%06d", ($microtime - floor($microtime)) * 1000000);
        $payment_id = 'PAY' . date('Ymd') . '_' . $microseconds;
        
        $stmt = $pdo->prepare("
            INSERT INTO Payment (
                payment_id, order_id, slip_image, created_at, updated_at
            ) VALUES (?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([$payment_id, $order_id, $filename]);

        // Clear cart
        $stmt = $pdo->prepare("DELETE FROM Cart WHERE user_id = ?");
        $stmt->execute([$user_id]);

        $pdo->commit();

        echo json_encode([
            "success" => true, 
            "message" => "คำสั่งซื้อสำเร็จ",
            "order_id" => $order_id,
            "payment_id" => $payment_id,
            "redirect" => "home.php"
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        $pdo->rollBack();
        
        if (isset($uploadPath) && file_exists($uploadPath)) {
            unlink($uploadPath);
        }
        
        throw $e;
    }

} catch (Exception $e) {
    error_log("Submit payment error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        "success" => false, 
        "message" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>