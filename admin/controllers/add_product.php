<?php
// ========================
// ADD PRODUCT - API ENDPOINT
// ========================
// เพิ่มสินค้าใหม่พร้อมสต็อกเริ่มต้น

require_once 'config.php';
require_once 'stock_logger.php';

requireLogin();

header('Content-Type: application/json; charset=utf-8');

// ========================
// FUNCTION: ส่ง JSON Error
// ========================
function send_json_error($message, $code = 500) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

// ========================
// FUNCTION: ส่ง JSON Success
// ========================
function send_json_success($message, $data = null) {
    $response = ['success' => true, 'message' => $message];
    if ($data !== null) {
        $response = array_merge($response, $data);
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}

// ========================
// FUNCTION: ได้รับค่า Default
// ========================
function getVal($arr, $key, $default = null) {
    if (!isset($arr[$key])) return $default;
    $v = $arr[$key];
    return ($v === '' || $v === null) ? $default : $v;
}

try {
    // อ่านข้อมูลจาก JSON หรือ POST
    $input = [];
    $raw_input = file_get_contents('php://input');
    if (!empty($raw_input)) {
        $json_input = json_decode($raw_input, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json_input)) {
            $input = $json_input;
        }
    }

    if (empty($input) && !empty($_POST)) {
        $input = $_POST;
    }

    if (empty($input)) {
        send_json_error('ไม่พบข้อมูลอินพุต');
    }

    // เริ่มต้น Stock Logger
    $stockLogger = new StockLogger($pdo);

    $pdo->beginTransaction();

    // ========================
    // สร้าง Product ID
    // ========================
    $product_id = getVal($input, 'product_id');
    if (!$product_id) {
        $prefix = 'S' . date('ym');
        $sql_last = "SELECT product_id FROM Product WHERE product_id LIKE ? ORDER BY product_id DESC LIMIT 1";
        $stmt_last = $pdo->prepare($sql_last);
        $stmt_last->execute([$prefix . '%']);

        $next_number = 1;
        if ($row_last = $stmt_last->fetch()) {
            $last_num = (int)substr($row_last['product_id'], strlen($prefix));
            $next_number = $last_num + 1;
        }
        $product_id = $prefix . str_pad($next_number, 4, '0', STR_PAD_LEFT);
    }

    // ========================
    // ตรวจสอบและได้รับข้อมูล
    // ========================
    $name = getVal($input, 'name', '');
    if (empty(trim($name))) {
        throw new Exception('ชื่อสินค้าเป็นสิ่งจำเป็น');
    }

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

    // ประมวลผลวันที่
    $received_time = getVal($input, 'received_time', date('H:i:s'));
    
    // ตรวจสอบรูปแบบเวลา HH:mm:ss
    if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $received_time)) {
        $received_time = date('H:i:s');
    }
    
    $received_date = date('Y-m-d') . ' ' . $received_time;

    // ตรวจสอบ Category
    $allowed_categories = ['ot', 'rb', 'sp', 'ss', 'wm'];
    $category_id = getVal($input, 'category_id', 'ot');
    if (!in_array($category_id, $allowed_categories)) {
        throw new Exception('Category ไม่ถูกต้อง: ' . $category_id);
    }

    $supplier_id = getVal($input, 'supplier_id');

    // ========================
    // INSERT PRODUCT
    // ========================
    $sql = "
        INSERT INTO Product (
            product_id, name, description, width, length, height, weight,
            width_unit, length_unit, height_unit, weight_unit,
            lot, stock, price, received_date,
            category_id, supplier_id, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $product_id, $name, $description,
        $width, $length, $height, $weight,
        $width_unit, $length_unit, $height_unit, $weight_unit,
        $lot, $stock, $price, $received_date,
        $category_id, $supplier_id
    ]);

    // ========================
    // LOG INITIAL STOCK
    // ========================
    if ($stock > 0) {
        $admin_id = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 'system';

        $stock_result = $stockLogger->logStockChange(
            $product_id,
            'in',
            $stock,
            'receive',
            null,
            null,
            $admin_id,
            "Initial stock when creating product: {$name}",
            0
        );

        if (!$stock_result['success']) {
            throw new Exception("ไม่สามารถบันทึกสต็อกได้: " . $stock_result['error']);
        }
    }

    // ========================
    // HANDLE PRODUCT IMAGE
    // ========================
    $productimage_id = getVal($input, 'productimage_id');
    if ($productimage_id) {
        $image_url = getVal($input, 'image_url', '');
        $is_main = getVal($input, 'is_main') ? 1 : 0;

        $sql_img = "
            INSERT INTO ProductImage (productimage_id, product_id, image_url, is_main, created_at, updated_at)
            VALUES (?, ?, ?, ?, NOW(), NOW())
        ";
        $stmt_img = $pdo->prepare($sql_img);
        $stmt_img->execute([$productimage_id, $product_id, $image_url, $is_main]);

        if ($is_main) {
            $sql_upd = "UPDATE Product SET productimage_id = ?, updated_at = NOW() WHERE product_id = ?";
            $stmt_upd = $pdo->prepare($sql_upd);
            $stmt_upd->execute([$productimage_id, $product_id]);
        }
    }

    $pdo->commit();

    send_json_success('เพิ่มสินค้าเสร็จสิ้น', [
        'product_id' => $product_id,
        'productimage_id' => $productimage_id,
        'initial_stock' => $stock
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error in add_product.php: " . $e->getMessage());
    send_json_error($e->getMessage());
}
?>