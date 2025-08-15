<?php
header('Content-Type: application/json; charset=utf-8');

// เปิดการแสดง error สำหรับ debug (ปิดใน production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'teststeel';

// ฟังก์ชันส่ง JSON error และหยุดการทำงาน
function send_json_error($message, $code = 500) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    send_json_error('Connection failed: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

// ถ้ามี product_id => ดึงสินค้าตัวเดียวพร้อมรูปภาพทั้งหมด
if (!empty($_GET['product_id'])) {
    $product_id = $_GET['product_id'];
    
    // Query หลัก - ดึงข้อมูลสินค้า
    $sql_product = "SELECT p.*, c.name AS category_name, s.name AS supplier_name
                    FROM Product p
                    LEFT JOIN Category c ON p.category_id = c.category_id
                    LEFT JOIN Supplier s ON p.supplier_id = s.supplier_id
                    WHERE p.product_id = ?
                    LIMIT 1";
    
    $stmt = $conn->prepare($sql_product);
    if (!$stmt) {
        send_json_error('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('s', $product_id);
    if (!$stmt->execute()) {
        send_json_error('Execute failed: ' . $stmt->error);
    }
    $res = $stmt->get_result();
    $product = $res->fetch_assoc();
    $stmt->close();
    
    if (!$product) {
        echo json_encode(['success' => true, 'data' => null], JSON_UNESCAPED_UNICODE);
        $conn->close();
        exit;
    }
    
    // ดึงรูปภาพทั้งหมดของสินค้านี้ (ใช้ product_id)
    $images = [];
    $sql_images = "SELECT productimage_id, image_url, is_main 
                   FROM ProductImage 
                   WHERE product_id = ?
                   ORDER BY is_main DESC, created_at ASC";
    $stmt_img = $conn->prepare($sql_images);
    $stmt_img->bind_param('s', $product_id);
    $stmt_img->execute();
    $res_img = $stmt_img->get_result();
    
    while ($img = $res_img->fetch_assoc()) {
        $images[] = $img;
    }
    $stmt_img->close();
    
    // เพิ่มข้อมูลรูปภาพเข้าไปใน product
    $product['images'] = $images;
    
    echo json_encode(['success' => true, 'data' => $product], JSON_UNESCAPED_UNICODE);
    $conn->close();
    exit;
}

// ถ้าไม่ระบุ product_id => ดึงสินค้าทั้งหมด พร้อมรูปภาพ
$sql = "SELECT p.*, c.name AS category_name, s.name AS supplier_name
        FROM Product p
        LEFT JOIN Category c ON p.category_id = c.category_id
        LEFT JOIN Supplier s ON p.supplier_id = s.supplier_id
        ORDER BY p.created_at DESC";

$result = $conn->query($sql);
if (!$result) {
    send_json_error('Query failed: ' . $conn->error);
}

$rows = [];
while ($r = $result->fetch_assoc()) {
    // ดึงรูปของสินค้านี้
    $images = [];
    $sql_images = "SELECT productimage_id, image_url, is_main 
                   FROM ProductImage 
                   WHERE product_id = ?
                   ORDER BY is_main DESC, created_at ASC";
    $stmt_img = $conn->prepare($sql_images);
    $stmt_img->bind_param('s', $r['product_id']);
    $stmt_img->execute();
    $res_img = $stmt_img->get_result();
    while ($img = $res_img->fetch_assoc()) {
        $images[] = $img;
    }
    $stmt_img->close();

    $r['images'] = $images;
    $rows[] = $r;
}

echo json_encode(['success' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
$conn->close();
?>