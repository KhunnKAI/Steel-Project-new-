<?php
header('Content-Type: application/json; charset=utf-8');

// -------------------------
// เปิดแสดง error สำหรับ debug
// -------------------------
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';
session_start();

// -------------------------
// ตรวจสอบการล็อกอิน
// -------------------------
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'กรุณาล็อกอินก่อนใช้งานตะกร้า'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $pdo = $database->getConnection();
    if (!$pdo) {
        throw new Exception("ไม่สามารถเชื่อมต่อฐานข้อมูลได้");
    }

    // -------------------------
    // ดึงข้อมูลลูกค้า
    // -------------------------
    $stmtUser = $pdo->prepare("
        SELECT user_id, name, email, phone
        FROM users
        WHERE user_id = :user_id
    ");
    $stmtUser->bindParam(':user_id', $userId, PDO::PARAM_STR);
    $stmtUser->execute();
    $customer = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'ไม่พบข้อมูลลูกค้า'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // -------------------------
    // ดึงที่อยู่ default ของลูกค้า
    // -------------------------
    $stmtAddress = $pdo->prepare("
        SELECT address_id, recipient_name, phone, address_line, subdistrict, district, province, postal_code
        FROM addresses
        WHERE user_id = :user_id
    ");
    $stmtAddress->bindParam(':user_id', $userId, PDO::PARAM_STR);
    $stmtAddress->execute();
    $defaultAddress = $stmtAddress->fetch(PDO::FETCH_ASSOC);
    if (!$defaultAddress) $defaultAddress = null;

    // -------------------------
    // ดึงรายการสินค้าในตะกร้า
    // -------------------------
    $stmt = $pdo->prepare("
        SELECT 
            c.product_id,
            c.quantity,
            p.name,
            p.price,
            pi.image_url AS image
        FROM cart c
        INNER JOIN product p ON c.product_id = p.product_id
        LEFT JOIN productimage pi 
            ON p.product_id = pi.product_id AND pi.is_main = 1
        WHERE c.user_id = :user_id
        ORDER BY c.product_id DESC
    ");
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
    $stmt->execute();
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cartItems)) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'ไม่มีสินค้าในตะกร้าของคุณ'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // -------------------------
    // คำนวณยอดรวม
    // -------------------------
    $totalAmount = 0;
    $totalItems = 0;

    foreach ($cartItems as &$item) {
        $item['price'] = floatval($item['price']);
        $item['quantity'] = intval($item['quantity']);
        $itemTotal = $item['price'] * $item['quantity'];
        $item['itemTotal'] = round($itemTotal, 2);

        $totalAmount += $itemTotal;
        $totalItems += $item['quantity'];
    }

    $totalShipping = $totalAmount >= 1000 ? 0 : 500;
    $taxRate = 0.07;
    $taxAmount = round($totalAmount * $taxRate, 2);
    $grandTotal = round($totalAmount + $totalShipping + $taxAmount, 2);

    // -------------------------
    // ส่ง JSON กลับ
    // -------------------------
    echo json_encode([
        'success' => true,
        'customer' => $customer,
        'address' => $defaultAddress,
        'cart' => [
            'items' => $cartItems,
            'totalItems' => $totalItems,
            'subTotal' => round($totalAmount, 2),
            'shipping' => $totalShipping,
            'taxRate' => $taxRate,
            'taxAmount' => $taxAmount,
            'grandTotal' => $grandTotal
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    // แสดงข้อความ error จริง ๆ สำหรับ debug
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดทั่วไป: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
