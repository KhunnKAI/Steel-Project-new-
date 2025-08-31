<?php
// Add debugging at the very start
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("POST data: " . print_r($_POST, true));
error_log("FILES data: " . print_r($_FILES, true));

session_start();
require_once './config.php'; // Adjust path as needed

header('Content-Type: application/json');

// More detailed method checking
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Method not POST. Actual method: " . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo json_encode([
        "success" => false, 
        "message" => "Method not allowed. Received: " . $_SERVER['REQUEST_METHOD']
    ]);
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

    // รับค่าจาก Form
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

    // Parse cart items
    $cartItemsJson = $_POST['cartItemsJson'] ?? '[]';
    $cartItems = json_decode($cartItemsJson, true);
    
    if (empty($cartItems)) {
        throw new Exception("ไม่พบรายการสินค้าในตะกร้า");
    }

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Generate unique order ID
        $order_id = 'ORD' . date('Ymd') . strtoupper(uniqid());

        // Insert Order - Use correct table name from your schema
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

        // Insert Order Items with improved unique ID generation and lot from Products table
        $itemCounter = 1;
        foreach ($cartItems as $item) {
            // Generate more unique ID with microseconds and counter
            $microtime = microtime(true);
            $microseconds = sprintf("%06d", ($microtime - floor($microtime)) * 1000000);
            $order_item_id = 'ITM' . date('Ymd') . '_' . $microseconds . '_' . sprintf('%03d', $itemCounter);
            
            // Get lot from cart item first, if empty then fetch from Products table
            $lot = $item['lot'] ?? null;
            if (empty($lot) && !empty($item['product_id'])) {
                // Fetch lot from Products table
                $lotStmt = $pdo->prepare("SELECT lot FROM Product WHERE product_id = ?");
                $lotStmt->execute([$item['product_id']]);
                $productData = $lotStmt->fetch(PDO::FETCH_ASSOC);
                $lot = $productData['lot'] ?? null;
                
                // Log if no lot found in database
                if (empty($lot)) {
                    error_log("No lot found for product_id: " . $item['product_id']);
                }
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
                $lot  // Use lot from Products table
            ]);
            
            $itemCounter++;
        }

        // Handle file upload
        $uploadDir = __DIR__ . "/uploads/payment_slips/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileExtension = pathinfo($_FILES['paymentSlip']['name'], PATHINFO_EXTENSION);
        $filename = $order_id . "_" . time() . "." . $fileExtension;
        $uploadPath = $uploadDir . $filename;

        if (!move_uploaded_file($_FILES['paymentSlip']['tmp_name'], $uploadPath)) {
            throw new Exception("ไม่สามารถบันทึกไฟล์ได้");
        }

        // Insert Payment record with improved unique ID
        $microtime = microtime(true);
        $microseconds = sprintf("%06d", ($microtime - floor($microtime)) * 1000000);
        $payment_id = 'PAY' . date('Ymd') . '_' . $microseconds;
        
        $stmt = $pdo->prepare("
            INSERT INTO Payment (
                payment_id, order_id, slip_image, created_at, updated_at
            ) VALUES (?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([$payment_id, $order_id, $filename]);

        // Update customer contact info (removed company field)
        $stmt = $pdo->prepare("
            UPDATE Users SET 
                phone = COALESCE(NULLIF(?, ''), phone),
                updated_at = NOW()
            WHERE user_id = ?
        ");
        $stmt->execute([$phone, $user_id]);

        // Clear cart after successful order
        $stmt = $pdo->prepare("DELETE FROM Cart WHERE user_id = ?");
        $stmt->execute([$user_id]);

        // Commit transaction
        $pdo->commit();

        // Success response
        echo json_encode([
            "success" => true, 
            "message" => "คำสั่งซื้อสำเร็จ",
            "order_id" => $order_id,
            "redirect" => "home.php"
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        
        // Clean up uploaded file if transaction failed
        if (isset($uploadPath) && file_exists($uploadPath)) {
            unlink($uploadPath);
        }
        
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "success" => false, 
        "message" => $e->getMessage()
    ]);
}
?>