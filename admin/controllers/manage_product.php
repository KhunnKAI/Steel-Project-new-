<?php
require_once 'config.php';

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

// ฟังก์ชันส่ง error
function send_json_error($message, $status_code = 400) {
    http_response_code($status_code);
    echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function send_json_success($message, $data = null) {
    $response = ['success' => true, 'status' => 'success', 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!$data) $data = [];

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST' && isset($data['_method'])) {
    $method = strtoupper($data['_method']);
}

try {
    // ------------------ GET ------------------
    if ($method === 'GET') {
        $sql = "SELECT p.*, c.name as category_name, s.name as supplier_name 
                FROM Product p 
                LEFT JOIN Category c ON p.category_id = c.category_id 
                LEFT JOIN Supplier s ON p.supplier_id = s.supplier_id 
                ORDER BY p.created_at DESC";
        $stmt = $pdo->query($sql);

        $products = [];
        while ($row = $stmt->fetch()) {
            $images_sql = "SELECT productimage_id, image_url, is_main, created_at, updated_at 
                           FROM ProductImage 
                           WHERE product_id = ? 
                           ORDER BY is_main DESC, created_at ASC";
            $img_stmt = $pdo->prepare($images_sql);
            $img_stmt->execute([$row['product_id']]);
            $images = $img_stmt->fetchAll();
            
            $row['images'] = $images;
            $products[] = $row;
        }

        echo json_encode(['success' => true, 'data' => $products], JSON_UNESCAPED_UNICODE);

    // ------------------ POST ------------------
    } elseif ($method === 'POST' && !isset($data['_method'])) {
        if (empty($data['name'])) {
            send_json_error("ชื่อสินค้าจำเป็นต้องระบุ");
        }

        if (empty($data['product_id'])) {
            $prefix = 'S' . date('ym');
            $sql_last = "SELECT product_id FROM Product WHERE product_id LIKE ? ORDER BY product_id DESC LIMIT 1";
            $stmt_last = $pdo->prepare($sql_last);
            $like_prefix = $prefix . '%';
            $stmt_last->execute([$like_prefix]);
            $result_last = $stmt_last->fetch();
            
            $next_number = 1;
            if ($result_last) {
                $last_num = (int)substr($result_last['product_id'], strlen($prefix));
                $next_number = $last_num + 1;
            }
            $data['product_id'] = $prefix . str_pad($next_number, 4, '0', STR_PAD_LEFT);
        }

        // เตรียมตัวแปรสำหรับ bind_param
        $product_id = $data['product_id'];
        $name = $data['name'];
        $description = $data['description'] ?? null;
        $width = $data['width'] ?? null;
        $length = $data['length'] ?? null;
        $height = $data['height'] ?? null;
        $weight = $data['weight'] ?? null;
        $width_unit = $data['width_unit'] ?? 'mm';
        $length_unit = $data['length_unit'] ?? 'mm';
        $height_unit = $data['height_unit'] ?? 'mm';
        $weight_unit = $data['weight_unit'] ?? 'kg';
        $lot = $data['lot'] ?? '';
        $stock = $data['stock'] ?? 0;
        $price = $data['price'] ?? 0;
        $received_date = !empty($data['received_date']) ? $data['received_date'] : null;
        $category_id = $data['category_id'] ?? 'ot';
        $supplier_id = $data['supplier_id'] ?? null;

        $sql = "INSERT INTO Product (
            product_id, name, description, width, length, height, weight, 
            width_unit, length_unit, height_unit, weight_unit, lot, stock, price, received_date, 
            category_id, supplier_id, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

        $stmt = $pdo->prepare($sql);
        if (!$stmt) send_json_error("Prepare failed", 500);

        $params = [
            $product_id, $name, $description, $width, $length, $height, $weight,
            $width_unit, $length_unit, $height_unit, $weight_unit,
            $lot, $stock, $price, $received_date, $category_id, $supplier_id
        ];

        if ($stmt->execute($params)) {
            send_json_success('เพิ่มสินค้าสำเร็จ', ['product_id' => $product_id]);
        } else {
            send_json_error("Insert failed", 500);
        }

    // ------------------ PUT ------------------
    } elseif ($method === 'PUT') {
        if (empty($data['product_id'])) {
            send_json_error("กรุณาระบุรหัสสินค้า (product_id)");
        }

        $check_stmt = $pdo->prepare("SELECT product_id FROM Product WHERE product_id = ?");
        $check_stmt->execute([$data['product_id']]);

        if ($check_stmt->rowCount() === 0) {
            send_json_error("ไม่พบสินค้าที่ต้องการแก้ไข", 404);
        }

        $product_id = $data['product_id'];
        $name = $data['name'];
        $description = $data['description'] ?? null;
        $width = $data['width'] ?? null;
        $length = $data['length'] ?? null;
        $height = $data['height'] ?? null;
        $weight = $data['weight'] ?? null;
        $width_unit = $data['width_unit'] ?? 'mm';
        $length_unit = $data['length_unit'] ?? 'mm';
        $height_unit = $data['height_unit'] ?? 'mm';
        $weight_unit = $data['weight_unit'] ?? 'kg';
        $lot = $data['lot'] ?? '';
        $stock = $data['stock'] ?? 0;
        $price = $data['price'] ?? 0;
        $received_date = !empty($data['received_date']) ? $data['received_date'] : null;
        $category_id = $data['category_id'] ?? 'ot';
        $supplier_id = $data['supplier_id'] ?? null;

        $update_sql = "UPDATE Product SET 
            name=?, description=?, width=?, length=?, height=?, weight=?,
            width_unit=?, length_unit=?, height_unit=?, weight_unit=?,
            lot=?, stock=?, price=?, received_date=?,
            category_id=?, supplier_id=?,
            updated_at=NOW()
            WHERE product_id=?";

        $update_stmt = $pdo->prepare($update_sql);
        if (!$update_stmt) send_json_error("Prepare update failed", 500);

        $params = [
            $name, $description, $width, $length, $height, $weight,
            $width_unit, $length_unit, $height_unit, $weight_unit,
            $lot, $stock, $price, $received_date, $category_id, $supplier_id,
            $product_id
        ];

        if ($update_stmt->execute($params)) {
            $affected_rows = $update_stmt->rowCount();
            if ($affected_rows > 0) {
                send_json_success("แก้ไขสินค้าสำเร็จ", ['product_id' => $product_id, 'affected_rows' => $affected_rows]);
            } else {
                send_json_success("ไม่มีการเปลี่ยนแปลงข้อมูล", ['product_id' => $product_id]);
            }
        } else {
            send_json_error("Update failed", 500);
        }

    // ------------------ DELETE ------------------
    } elseif ($method === 'DELETE') {
        if (empty($data['product_id'])) send_json_error("กรุณาระบุรหัสสินค้า (product_id)");

        $pdo->beginTransaction();
        try {
            $delete_images_stmt = $pdo->prepare("DELETE FROM ProductImage WHERE product_id=?");
            $delete_images_stmt->execute([$data['product_id']]);

            $delete_stmt = $pdo->prepare("DELETE FROM Product WHERE product_id=?");
            if ($delete_stmt->execute([$data['product_id']])) {
                if ($delete_stmt->rowCount() > 0) {
                    $pdo->commit();
                    send_json_success('ลบสินค้าสำเร็จ');
                } else {
                    $pdo->rollback();
                    send_json_error("ไม่พบสินค้าที่ต้องการลบ", 404);
                }
            } else {
                $pdo->rollback();
                throw new Exception("Delete failed");
            }
        } catch (Exception $e) {
            $pdo->rollback();
            send_json_error("Delete operation failed: " . $e->getMessage(), 500);
        }

    } else {
        send_json_error("Method not allowed: " . $method, 405);
    }

} catch (Exception $e) {
    send_json_error("Unexpected error: " . $e->getMessage(), 500);
}
?>