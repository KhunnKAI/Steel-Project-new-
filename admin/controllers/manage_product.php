<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, DELETE, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Database configuration
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'teststeel';

function send_json_error($message, $code = 500) {
    http_response_code($code);
    echo json_encode(['success' => false, 'status' => 'error', 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function send_json_success($message, $data = null) {
    $response = [
        'success' => true,
        'status' => 'success',
        'message' => $message
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}

try {
    $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    if ($conn->connect_error) {
        send_json_error('Connection failed: ' . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');

    $input = [];
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST') {
        $raw_input = file_get_contents('php://input');
        if (!empty($raw_input)) {
            $json_data = json_decode($raw_input, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($json_data)) {
                $input = $json_data;
            } else {
                parse_str($raw_input, $parsed_data);
                if (!empty($parsed_data)) $input = $parsed_data;
            }
        }
        if (empty($input) && !empty($_POST)) $input = $_POST;
    }

    if (isset($input['_method'])) $method = strtoupper($input['_method']);

    function getVal($arr, $key, $default = null) {
        return isset($arr[$key]) && $arr[$key] !== '' ? $arr[$key] : $default;
    }

    switch ($method) {
        case 'PUT':
            $product_id = getVal($input, 'product_id');
            if (!$product_id) send_json_error('product_id is required for update');

            $check_stmt = $conn->prepare("SELECT product_id FROM Product WHERE product_id = ?");
            if (!$check_stmt) send_json_error('Prepare check failed: ' . $conn->error);
            $check_stmt->bind_param('s', $product_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            if ($result->num_rows === 0) {
                $check_stmt->close();
                send_json_error('Product not found', 404);
            }
            $check_stmt->close();

            // Collect data
            $name = getVal($input, 'name');
            if (!$name || trim($name) === '') send_json_error('Product name is required');
            $description = getVal($input, 'description');
            $width = is_numeric(getVal($input, 'width')) ? (float)$input['width'] : null;
            $length = is_numeric(getVal($input, 'length')) ? (float)$input['length'] : null;
            $height = is_numeric(getVal($input, 'height')) ? (float)$input['height'] : null;
            $weight = is_numeric(getVal($input, 'weight')) ? (float)$input['weight'] : null;
            $width_unit = getVal($input, 'width_unit', 'mm');
            $length_unit = getVal($input, 'length_unit', 'mm');
            $height_unit = getVal($input, 'height_unit', 'mm');
            $weight_unit = getVal($input, 'weight_unit', 'kg');
            $lot = getVal($input, 'lot', '');
            $stock = is_numeric(getVal($input, 'stock')) ? (int)$input['stock'] : null;
            $price = is_numeric(getVal($input, 'price')) ? (float)$input['price'] : null;

            $received_date = getVal($input, 'received_date');
            if ($received_date) {
                $ts = strtotime($received_date);
                if ($ts === false) send_json_error('Invalid received_date format');
                $received_date = date('Y-m-d H:i:s', $ts);
            }

            $productimage_id = getVal($input, 'productimage_id');

            $allowed_categories = ['ot', 'rb', 'sp', 'ss', 'wm'];
            $category_id = getVal($input, 'category_id', 'ot');
            if (!in_array($category_id, $allowed_categories)) send_json_error('Invalid category_id: ต้องเป็น ot, rb, sp, ss, wm');

            $supplier_id = getVal($input, 'supplier_id');

            // Update SQL
            $update_sql = "UPDATE Product SET 
                name=?, description=?, width=?, length=?, height=?, weight=?,
                width_unit=?, length_unit=?, height_unit=?, weight_unit=?,
                lot=?, stock=?, price=?, received_date=?,
                productimage_id=?, category_id=?, supplier_id=?,
                updated_at=NOW()
                WHERE product_id=?";
            $update_stmt = $conn->prepare($update_sql);
            if (!$update_stmt) send_json_error('Prepare update failed: ' . $conn->error);

            $update_stmt->bind_param(
                'ssddddsssssissssss', // 18 params
                $name, $description, $width, $length, $height, $weight,
                $width_unit, $length_unit, $height_unit, $weight_unit,
                $lot, $stock, $price, $received_date,
                $productimage_id, $category_id, $supplier_id, $product_id
            );

            if (!$update_stmt->execute()) {
                $update_stmt->close();
                send_json_error('Execute update failed: ' . $update_stmt->error);
            }
            $affected_rows = $update_stmt->affected_rows;
            $update_stmt->close();
            send_json_success("แก้ไขสินค้าสำเร็จ แถวที่ได้รับผลกระทบ: {$affected_rows}", ['product_id'=>$product_id]);
            break;

        case 'DELETE':
            $product_id = getVal($input, 'product_id');
            if (!$product_id) send_json_error('product_id is required for delete');

            $check_stmt = $conn->prepare("SELECT product_id FROM Product WHERE product_id = ?");
            if (!$check_stmt) send_json_error('Prepare check failed: ' . $conn->error);
            $check_stmt->bind_param('s', $product_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            if ($result->num_rows === 0) {
                $check_stmt->close();
                send_json_error('Product not found', 404);
            }
            $check_stmt->close();

            $delete_images_stmt = $conn->prepare("DELETE FROM ProductImage WHERE product_id=?");
            if ($delete_images_stmt) {
                $delete_images_stmt->bind_param('s', $product_id);
                $delete_images_stmt->execute();
                $images_deleted = $delete_images_stmt->affected_rows;
                $delete_images_stmt->close();
            }

            $delete_stmt = $conn->prepare("DELETE FROM Product WHERE product_id=?");
            if (!$delete_stmt) send_json_error('Prepare delete failed: ' . $conn->error);
            $delete_stmt->bind_param('s', $product_id);
            if (!$delete_stmt->execute()) {
                $delete_stmt->close();
                send_json_error('Execute delete failed: ' . $delete_stmt->error);
            }
            $affected_rows = $delete_stmt->affected_rows;
            $delete_stmt->close();
            if ($affected_rows === 0) send_json_error('Product not found or already deleted', 404);

            send_json_success("ลบสินค้าสำเร็จ แถวที่ได้รับผลกระทบ: {$affected_rows}", [
                'product_id'=>$product_id,
                'images_deleted'=>isset($images_deleted)?$images_deleted:0
            ]);
            break;

        case 'GET':
            $product_id = getVal($input, 'product_id') ?: getVal($_GET, 'product_id');
            if ($product_id) {
                $stmt = $conn->prepare("
                    SELECT p.*, c.name AS category_name, s.name AS supplier_name
                    FROM Product p
                    LEFT JOIN Category c ON p.category_id=c.category_id
                    LEFT JOIN Supplier s ON p.supplier_id=s.supplier_id
                    WHERE p.product_id=?
                ");
                if (!$stmt) send_json_error('Prepare failed: ' . $conn->error);
                $stmt->bind_param('s', $product_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows === 0) {
                    $stmt->close();
                    send_json_error('Product not found', 404);
                }
                $product = $result->fetch_assoc();
                $stmt->close();

                $img_stmt = $conn->prepare("SELECT productimage_id,image_url,is_main FROM ProductImage WHERE product_id=? ORDER BY is_main DESC, created_at ASC");
                if ($img_stmt) {
                    $img_stmt->bind_param('s', $product_id);
                    $img_stmt->execute();
                    $img_result = $img_stmt->get_result();
                    $images = [];
                    while ($img = $img_result->fetch_assoc()) $images[]=$img;
                    $img_stmt->close();
                    $product['images']=$images;
                }

                send_json_success('Product found', $product);
            } else {
                $limit = isset($_GET['limit'])?(int)$_GET['limit']:100;
                $offset = isset($_GET['offset'])?(int)$_GET['offset']:0;
                $category = getVal($_GET,'category_id');
                $sql = "SELECT p.*, c.name AS category_name, s.name AS supplier_name
                        FROM Product p
                        LEFT JOIN Category c ON p.category_id=c.category_id
                        LEFT JOIN Supplier s ON p.supplier_id=s.supplier_id";
                $params=[]; $types='';
                if ($category) { $sql.=" WHERE p.category_id=?"; $params[]=$category; $types.='s'; }
                $sql.=" ORDER BY p.created_at DESC LIMIT ? OFFSET ?"; $params[]=$limit; $params[]=$offset; $types.='ii';
                $stmt=$conn->prepare($sql); if(!$stmt) send_json_error('Prepare failed: '.$conn->error);
                if(!empty($params)) $stmt->bind_param($types,...$params);
                $stmt->execute(); $result=$stmt->get_result(); $products=[];
                while($product=$result->fetch_assoc()){
                    $img_stmt=$conn->prepare("SELECT productimage_id,image_url,is_main FROM ProductImage WHERE product_id=? ORDER BY is_main DESC, created_at ASC");
                    if($img_stmt){ $img_stmt->bind_param('s',$product['product_id']); $img_stmt->execute(); $img_result=$img_stmt->get_result(); $images=[];
                        while($img=$img_result->fetch_assoc()) $images[]=$img; $img_stmt->close(); $product['images']=$images; } else { $product['images']=[]; }
                    $products[]=$product;
                }
                $stmt->close();
                send_json_success('Products found',['products'=>$products,'count'=>count($products),'limit'=>$limit,'offset'=>$offset]);
            }
            break;

        default:
            send_json_error('Method not allowed: '.$method,405);
            break;
    }

    $conn->close();

} catch(Exception $e) {
    error_log("Error in manage_product.php: ".$e->getMessage());
    send_json_error('Internal server error: '.$e->getMessage());
}
?>
