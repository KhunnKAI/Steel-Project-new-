<?php
header('Content-Type: application/json; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', 0);

$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'teststeel';

function send_json_error($message, $code = 500) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function send_json_success($message, $data = null) {
    $response = ['success' => true, 'message' => $message];
    if ($data !== null) {
        $response = array_merge($response, $data);
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) send_json_error('Connection failed: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

// FIXED: Handle input properly
$input = [];

// Check for JSON input first
$raw_input = file_get_contents('php://input');
if (!empty($raw_input)) {
    $json_input = json_decode($raw_input, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($json_input)) {
        $input = $json_input;
    }
}

// Fallback to POST data if no valid JSON
if (empty($input) && !empty($_POST)) {
    $input = $_POST;
}

// If still empty, send error
if (empty($input)) {
    send_json_error('No input data received');
}

function getVal($arr, $key, $default = null) {
    if (!isset($arr[$key])) return $default;
    $v = $arr[$key];
    return ($v === '' || $v === null) ? $default : $v;
}

try {
    $conn->begin_transaction();

    // Generate product_id if not provided
    $product_id = getVal($input, 'product_id');
    if (!$product_id) {
        $prefix = 'S' . date('ym');
        $sql_last = "SELECT product_id FROM Product WHERE product_id LIKE ? ORDER BY product_id DESC LIMIT 1";
        $stmt_last = $conn->prepare($sql_last);
        if (!$stmt_last) throw new Exception('Prepare failed (get last id): ' . $conn->error);
        $like_prefix = $prefix . '%';
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

    // Validate required fields
    $name = getVal($input, 'name', '');
    if (empty(trim($name))) {
        throw new Exception('ชื่อสินค้าจำเป็นต้องระบุ');
    }

    // Product fields with proper type conversion and defaults
    $description = getVal($input, 'description');
    $width = is_numeric(getVal($input, 'width')) ? (float)getVal($input, 'width') : null;
    $length = is_numeric(getVal($input, 'length')) ? (float)getVal($input, 'length') : null;
    $height = is_numeric(getVal($input, 'height')) ? (float)getVal($input, 'height') : null;
    $weight = is_numeric(getVal($input, 'weight')) ? (float)getVal($input, 'weight') : null;
    $width_unit = getVal($input, 'width_unit', 'mm');
    $length_unit = getVal($input, 'length_unit', 'mm');
    $height_unit = getVal($input, 'height_unit', 'mm');
    $weight_unit = getVal($input, 'weight_unit', 'kg');
    $lot = getVal($input, 'lot', '');
    $stock = is_numeric(getVal($input, 'stock')) ? (int)getVal($input, 'stock') : 0;
    $price = is_numeric(getVal($input, 'price')) ? (float)getVal($input, 'price') : 0;
    
    // Handle received_date properly
    $received_date = getVal($input, 'received_date');
    if ($received_date) {
        $ts = strtotime($received_date);
        $received_date = ($ts !== false) ? date('Y-m-d H:i:s', $ts) : null;
    } else {
        $received_date = null;
    }

    // Validate category
    $allowed_categories = ['ot', 'rb', 'sp', 'ss', 'wm'];
    $category_id = getVal($input, 'category_id', 'ot');
    if (!in_array($category_id, $allowed_categories)) {
        throw new Exception('Invalid category_id: ' . $category_id);
    }

    $supplier_id = getVal($input, 'supplier_id');

    // Insert Product (ไม่ต้องใส่ productimage_id ตอนสร้าง)
    $sql = "INSERT INTO Product (
        product_id, name, description, width, length, height, weight,
        width_unit, length_unit, height_unit, weight_unit,
        lot, stock, price, received_date,
        category_id, supplier_id, created_at, updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception('Prepare failed: ' . $conn->error);
    
    $stmt->bind_param(
        'sssddddsssssidsss',
        $product_id, $name, $description,
        $width, $length, $height, $weight,
        $width_unit, $length_unit, $height_unit, $weight_unit,
        $lot, $stock, $price, $received_date,
        $category_id, $supplier_id
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }
    $stmt->close();

    // Handle ProductImage if provided
    $productimage_id = getVal($input, 'productimage_id');
    if ($productimage_id) {
        $image_url = getVal($input, 'image_url', '');
        $is_main = getVal($input, 'is_main') ? 1 : 0;

        $sql_img = "INSERT INTO ProductImage (productimage_id, product_id, image_url, is_main, created_at, updated_at)
                    VALUES (?, ?, ?, ?, NOW(), NOW())";
        $stmt_img = $conn->prepare($sql_img);
        if (!$stmt_img) throw new Exception('Prepare ProductImage failed: ' . $conn->error);
        
        $stmt_img->bind_param('sssi', $productimage_id, $product_id, $image_url, $is_main);
        if (!$stmt_img->execute()) {
            throw new Exception('Execute ProductImage failed: ' . $stmt_img->error);
        }
        $stmt_img->close();

        // Update Product to reference the main image
        if ($is_main) {
            $sql_upd = "UPDATE Product SET productimage_id = ?, updated_at = NOW() WHERE product_id = ?";
            $stmt_upd = $conn->prepare($sql_upd);
            if (!$stmt_upd) throw new Exception('Prepare update Product failed: ' . $conn->error);
            
            $stmt_upd->bind_param('ss', $productimage_id, $product_id);
            if (!$stmt_upd->execute()) {
                throw new Exception('Execute update Product failed: ' . $stmt_upd->error);
            }
            $stmt_upd->close();
        }
    }

    $conn->commit();

    send_json_success('เพิ่มสินค้าสำเร็จ', [
        'product_id' => $product_id,
        'productimage_id' => $productimage_id
    ]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Error in add_product.php: " . $e->getMessage());
    send_json_error($e->getMessage());
}

$conn->close();
?>