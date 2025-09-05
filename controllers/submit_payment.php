<?php
// Add debugging at the very start
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("POST data: " . print_r($_POST, true));
error_log("FILES data: " . print_r($_FILES, true));

session_start();
require_once './config.php';
require_once './shipping_calculator.php'; // Include shipping calculator for validation

header('Content-Type: application/json');

// Method validation
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Method not POST. Actual method: " . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo json_encode([
        "success" => false, 
        "message" => "Method not allowed. Received: " . $_SERVER['REQUEST_METHOD']
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

    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
    $fileType = $_FILES['paymentSlip']['type'];
    if (!in_array($fileType, $allowedTypes)) {
        throw new Exception("ประเภทไฟล์ไม่ถูกต้อง อนุญาตเฉพาะ JPG, PNG, PDF");
    }

    // Validate file size (5MB max)
    if ($_FILES['paymentSlip']['size'] > 5 * 1024 * 1024) {
        throw new Exception("ขนาดไฟล์ใหญ่เกินไป (สูงสุด 5MB)");
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

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("รูปแบบอีเมลไม่ถูกต้อง");
    }

    // Validate totals
    if ($total <= 0) {
        throw new Exception("ยอดรวมไม่ถูกต้อง");
    }

    // Parse and validate cart items
    $cartItemsJson = $_POST['cartItemsJson'] ?? '[]';
    $cartItems = json_decode($cartItemsJson, true);
    
    if (empty($cartItems)) {
        throw new Exception("ไม่พบรายการสินค้าในตะกร้า");
    }

    // Initialize shipping calculator for validation
    $shippingCalculator = new ShippingCalculator($pdo);
    
    // Validate weight before processing order
    if ($weight > 0) {
        $weightValidation = $shippingCalculator->validateWeightLimit($weight);
        if (!$weightValidation['success']) {
            throw new Exception("ไม่สามารถสั่งซื้อได้ เนื่องจากน้ำหนักเกินขีดจำกัด: " . $weightValidation['error']);
        }
    }

    // Validate address exists and belongs to user
    $stmtAddress = $pdo->prepare("
        SELECT address_id, province_id 
        FROM Addresses 
        WHERE address_id = ? AND user_id = ?
    ");
    $stmtAddress->execute([$addressId, $user_id]);
    $addressData = $stmtAddress->fetch(PDO::FETCH_ASSOC);
    
    if (!$addressData) {
        throw new Exception("ไม่พบข้อมูลที่อยู่จัดส่ง");
    }

    // Validate stock availability before processing
    foreach ($cartItems as $item) {
        $stmt = $pdo->prepare("SELECT stock, name FROM Product WHERE product_id = ?");
        $stmt->execute([$item['product_id']]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            throw new Exception("ไม่พบสินค้า ID: " . $item['product_id']);
        }
        
        if ($product['stock'] < intval($item['quantity'])) {
            throw new Exception("สินค้า '{$product['product_name']}' มีสต็อกไม่เพียงพอ (เหลือ {$product['stock']} ชิ้น ต้องการ {$item['quantity']} ชิ้น)");
        }
    }

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Generate unique order ID
        $order_id = 'ORD' . date('Ymd') . strtoupper(uniqid());

        // Fixed Insert Order - removed the extra parameter
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
            // Removed $addressId from here since it's not in the column list
        ]);

        // Insert Order Items with consistent ID generation
        $itemCounter = 1;
        foreach ($cartItems as $item) {
            // Generate unique order item ID
            $microtime = microtime(true);
            $microseconds = sprintf("%06d", ($microtime - floor($microtime)) * 1000000);
            $order_item_id = 'ITM' . date('Ymd') . '_' . $microseconds . '_' . sprintf('%03d', $itemCounter);
            
            // Get lot from cart item or fetch from Product table
            $lot = $item['lot'] ?? null;
            if (empty($lot) && !empty($item['product_id'])) {
                $lotStmt = $pdo->prepare("SELECT lot FROM Product WHERE product_id = ?");
                $lotStmt->execute([$item['product_id']]);
                $productData = $lotStmt->fetch(PDO::FETCH_ASSOC);
                $lot = $productData['lot'] ?? null;
                
                if (empty($lot)) {
                    error_log("No lot found for product_id: " . $item['product_id']);
                }
            }
            
            // Insert order item
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

        // UPDATE STOCK - Deduct quantities from Product table
        foreach ($cartItems as $item) {
            $quantity = intval($item['quantity']);
            $product_id = $item['product_id'];
            
            // Update stock with validation to prevent negative stock
            $stmt = $pdo->prepare("
                UPDATE Product 
                SET stock = stock - ?, 
                    updated_at = NOW()
                WHERE product_id = ? AND stock >= ?
            ");
            
            $stmt->execute([$quantity, $product_id, $quantity]);
            
            // Check if the update affected any rows (i.e., stock was sufficient)
            if ($stmt->rowCount() === 0) {
                // Get current stock for error message
                $checkStmt = $pdo->prepare("SELECT stock, product_name FROM Product WHERE product_id = ?");
                $checkStmt->execute([$product_id]);
                $currentProduct = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                throw new Exception("สินค้า '{$currentProduct['product_name']}' มีสต็อกไม่เพียงพอ (เหลือ {$currentProduct['stock']} ชิ้น)");
            }
            
            // Log stock deduction
            error_log("Stock deducted: Product ID {$product_id}, Quantity: {$quantity}");
        }

        // Handle file upload with secure filename
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

        // Insert Payment record with consistent ID generation
        $microtime = microtime(true);
        $microseconds = sprintf("%06d", ($microtime - floor($microtime)) * 1000000);
        $payment_id = 'PAY' . date('Ymd') . '_' . $microseconds;
        
        $stmt = $pdo->prepare("
            INSERT INTO Payment (
                payment_id, order_id, slip_image, created_at, updated_at
            ) VALUES (?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([$payment_id, $order_id, $filename]);

        // Update customer contact info if provided
        if (!empty($phone)) {
            $stmt = $pdo->prepare("
                UPDATE Users SET 
                    phone = ?,
                    updated_at = NOW()
                WHERE user_id = ?
            ");
            $stmt->execute([$phone, $user_id]);
        }

        // Clear cart after successful order
        $stmt = $pdo->prepare("DELETE FROM Cart WHERE user_id = ?");
        $stmt->execute([$user_id]);

        // Log successful order
        error_log("Order created successfully: {$order_id} for user: {$user_id}, Total: {$total}, Weight: {$weight}kg");

        // Commit transaction
        $pdo->commit();

        // Success response
        echo json_encode([
            "success" => true, 
            "message" => "คำสั่งซื้อสำเร็จ",
            "order_id" => $order_id,
            "payment_id" => $payment_id,
            "redirect" => "home.php"
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        $pdo->rollBack();
        
        // Clean up uploaded file if transaction failed
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