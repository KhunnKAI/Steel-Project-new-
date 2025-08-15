<?php
header('Content-Type: application/json; charset=utf-8');

// เปิดการแสดง error (ปิดใน production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'teststeel';

// ฟังก์ชันส่ง JSON error
function send_json_error($message, $code = 500) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    send_json_error('Connection failed: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

// อ่าน input (รองรับทั้ง form-data, x-www-form-urlencoded, JSON)
$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $input = $json;
    }
}

// helper get value
function getVal($arr, $key) {
    if (!isset($arr[$key])) return null;
    $v = $arr[$key];
    if ($v === '') return null;
    return $v;
}

$product_id = getVal($input, 'product_id');

// ✅ ถ้าไม่มี product_id ให้เจนเป็น S-ym0000
if (!$product_id) {
    $prefix = 'S' . date('ym');
    $sql_last = "SELECT product_id FROM Product 
                 WHERE product_id LIKE ? 
                 ORDER BY product_id DESC LIMIT 1";
    $like_prefix = $prefix . '%';
    $stmt_last = $conn->prepare($sql_last);
    if (!$stmt_last) {
        send_json_error('Prepare failed (get last id): ' . $conn->error);
    }
    $stmt_last->bind_param('s', $like_prefix);
    $stmt_last->execute();
    $result_last = $stmt_last->get_result();

    $next_number = 1;
    if ($row_last = $result_last->fetch_assoc()) {
        $last_num = (int)substr($row_last['product_id'], strlen($prefix));
        $next_number = $last_num + 1;
    }
    $stmt_last->close();

    $product_id = $prefix . str_pad($next_number, 4, '0', STR_PAD_LEFT);
}

$name = getVal($input, 'name') ?? '';
$description = getVal($input, 'description');
$width = is_numeric(getVal($input, 'width')) ? (float)getVal($input, 'width') : null;
$length = is_numeric(getVal($input, 'length')) ? (float)getVal($input, 'length') : null;
$height = is_numeric(getVal($input, 'height')) ? (float)getVal($input, 'height') : null;
$weight = is_numeric(getVal($input, 'weight')) ? (float)getVal($input, 'weight') : null;
$width_unit = getVal($input, 'width_unit');
$length_unit = getVal($input, 'length_unit');
$height_unit = getVal($input, 'height_unit');
$weight_unit = getVal($input, 'weight_unit');
$lot = getVal($input, 'lot') ?? ''; // ✅ กัน NULL
$stock = is_numeric(getVal($input, 'stock')) ? (int)getVal($input, 'stock') : 0; // ✅ กัน NULL
$price = is_numeric(getVal($input, 'price')) ? (float)getVal($input, 'price') : 0; // ✅ กัน NULL

$received_date = getVal($input, 'received_date');
if ($received_date) {
    $ts = strtotime($received_date);
    if ($ts !== false) {
        $received_date = date('Y-m-d H:i:s', $ts);
    } else {
        $received_date = null;
    }
}

$productimage_id = getVal($input, 'productimage_id');

// ✅ เช็ค category_id ให้ตรงกับค่าที่อนุญาต
$allowed_categories = ['ot', 'rb', 'sp', 'ss', 'wm'];
$category_id = getVal($input, 'category_id');
if (!$category_id) {
    $category_id = 'ot'; // default
} elseif (!in_array($category_id, $allowed_categories)) {
    send_json_error('Invalid category_id: ต้องเป็น ot, rb, sp, ss, wm');
}

$supplier_id = getVal($input, 'supplier_id');

// SQL Insert
$sql = "INSERT INTO Product (
    product_id, name, description,
    width, length, height, weight,
    width_unit, length_unit, height_unit, weight_unit,
    lot, stock, price, received_date,
    productimage_id, category_id, supplier_id,
    created_at, updated_at
) VALUES (
    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    send_json_error('Prepare failed: ' . $conn->error);
}

$types = 'sssddddsssssidssss';
$stmt->bind_param(
    $types,
    $product_id, $name, $description,
    $width, $length, $height, $weight,
    $width_unit, $length_unit, $height_unit, $weight_unit,
    $lot, $stock, $price, $received_date,
    $productimage_id, $category_id, $supplier_id
);

if (!$stmt->execute()) {
    send_json_error('Execute failed: ' . $stmt->error);
}

echo json_encode([
    'success' => true,
    'message' => 'Product inserted',
    'product_id' => $product_id
], JSON_UNESCAPED_UNICODE);

$stmt->close();
$conn->close();
?>