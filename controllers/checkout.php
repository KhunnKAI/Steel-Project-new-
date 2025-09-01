<?php
// ==========================
// Checkout PHP - Full Version
// ==========================

// เริ่ม session
session_start();

// ตั้งค่า header
header('Content-Type: application/json');
header('Content-Type: application/json; charset=utf-8');

// เปิด error reporting สำหรับ debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Debug session
error_log("=== CHECKOUT SESSION DEBUG ===");
error_log("Session ID: " . session_id());
error_log("Session data: " . print_r($_SESSION, true));
error_log("Cookies: " . print_r($_COOKIE, true));

// ตรวจสอบ user_id
$user_id = null;
if (!empty($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    error_log("Using session user_id: " . $user_id);
} elseif (!empty($_COOKIE['user_id'])) {
    $user_id = $_COOKIE['user_id'];
    $_SESSION['user_id'] = $user_id;
    error_log("Using cookie user_id: " . $user_id);
}

if (!$user_id) {
    echo json_encode([
        'success' => false,
        'message' => 'กรุณาล็อกอินก่อนสั่งซื้อ',
        'redirect' => 'login.php'
    ]);
    exit;
}

// ตรวจสอบ method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

// อ่านข้อมูลจาก request body
$input = json_decode(file_get_contents('php://input'), true);
error_log("Input data: " . print_r($input, true));

if (!$input || !isset($input['items']) || !is_array($input['items']) || count($input['items']) === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ไม่มีสินค้าในตะกร้า'
    ]);
    exit;
}

// โหลด config และเชื่อม DB
if (!file_exists(__DIR__ . '/config.php')) {
    echo json_encode([
        'success' => false,
        'message' => 'ไฟล์ config.php ไม่พบ'
    ]);
    exit;
}

require_once __DIR__ . '/config.php';

try {
    if (!isset($database)) throw new Exception('Database object not found');
    $pdo = $database->getConnection();
    if (!$pdo) throw new Exception('Database connection failed');

    $pdo->beginTransaction();

    // ดึง product_id จาก input
    $productIds = array_map(fn($item) => $item['product_id'], $input['items']);
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));

    // ตรวจสอบสินค้าที่มีอยู่
    $stmt = $pdo->prepare("SELECT product_id, name, price, stock FROM Product WHERE product_id IN ($placeholders)");
    $stmt->execute($productIds);
    $validProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$validProducts) throw new Exception('ไม่พบสินค้าที่ถูกต้องในฐานข้อมูล');

    // map product_id -> product
    $validProductsMap = [];
    foreach ($validProducts as $p) $validProductsMap[$p['product_id']] = $p;

    // กรอง valid items
    $validItems = array_filter($input['items'], fn($item) =>
        isset($validProductsMap[$item['product_id']]) &&
        isset($item['quantity']) &&
        intval($item['quantity']) > 0
    );

    if (!$validItems) throw new Exception('ไม่พบสินค้าที่ถูกต้องในคำสั่งซื้อ');

    // ลบ cart เดิม
    $deleteStmt = $pdo->prepare("DELETE FROM Cart WHERE user_id = :user_id");
    $deleteStmt->execute([':user_id' => $user_id]);

    // เตรียม insert
    $insertStmt = $pdo->prepare("
        INSERT INTO Cart (user_id, product_id, quantity, created_at, updated_at)
        VALUES (:user_id, :product_id, :quantity, NOW(), NOW())
    ");

    $insertedItems = [];
    $totalQuantity = 0;
    $totalAmount = 0;

    foreach ($validItems as $item) {
        $pid = $item['product_id'];
        $qty = intval($item['quantity']);
        $product = $validProductsMap[$pid];

        // ตรวจสอบ stock
        if ($qty > $product['stock']) {
            $qty = $product['stock']; // ถ้าเกิน stock ให้เท่ากับ stock
        }

        $insertStmt->execute([
            ':user_id' => $user_id,
            ':product_id' => $pid,
            ':quantity' => $qty
        ]);

        $insertedItems[] = [
            'product_id' => $pid,
            'name' => $product['name'],
            'quantity' => $qty,
            'price' => floatval($product['price']),
            'total' => floatval($product['price']) * $qty
        ];

        $totalQuantity += $qty;
        $totalAmount += floatval($product['price']) * $qty;
    }

    // บันทึก checkout summary ใน session
    $_SESSION['checkout_summary'] = [
        'user_id' => $user_id,
        'total_items' => count($insertedItems),
        'total_quantity' => $totalQuantity,
        'total_amount' => $totalAmount,
        'items' => $insertedItems,
        'checkout_time' => date('Y-m-d H:i:s')
    ];

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'user_id' => $user_id,
        'inserted_items' => count($insertedItems),
        'total_quantity' => $totalQuantity,
        'total_amount' => number_format($totalAmount, 2),
        'items' => $insertedItems,
        'message' => 'บันทึกข้อมูลการสั่งซื้อเรียบร้อยแล้ว'
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log("Checkout error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล',
        'error' => $e->getMessage()
    ]);
}
?>
