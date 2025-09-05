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

// ใช้ config.php แทน
require_once 'config.php';

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

// สร้าง mysqli connection จาก PDO config
$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    send_json_error("Connection failed: " . $conn->connect_error, 500);
}
$conn->set_charset('utf8mb4');

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
        $result = $conn->query($sql);

        if (!$result) {
            send_json_error("Query failed: " . $conn->error, 500);
        }

        $products = [];
        while ($row = $result->fetch_assoc()) {
            $images_sql = "SELECT productimage_id, image_url, is_main, created_at, updated_at 
                           FROM ProductImage 
                           WHERE product_id = ? 
                           ORDER BY is_main DESC, created_at ASC";
            $img_stmt = $conn->prepare($images_sql);
            if ($img_stmt) {
                $img_stmt->bind_param('s', $row['product_id']);
                $img_stmt->execute();
                $img_result = $img_stmt->get_result();
                
                $images = [];
                while ($img = $img_result->fetch_assoc()) {
                    $images[] = $img;
                }
                $img_stmt->close();
                $row['images'] = $images;
            } else {
                $row['images'] = [];
            }
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
            $stmt_last = $conn->prepare($sql_last);
            if ($stmt_last) {
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
                $data['product_id'] = $prefix . str_pad($next_number, 4, '0', STR_PAD_LEFT);
            } else {
                send_json_error("Failed to generate product ID: " . $conn->error, 500);
            }
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

        $stmt = $conn->prepare($sql);
        if (!$stmt) send_json_error("Prepare failed: " . $conn->error, 500);

        $stmt->bind_param(
            "sssddddssssssdsss",
            $product_id,
            $name,
            $description,
            $width,
            $length,
            $height,
            $weight,
            $width_unit,
            $length_unit,
            $height_unit,
            $weight_unit,
            $lot,
            $stock,
            $price,
            $received_date,
            $category_id,
            $supplier_id
        );

        if ($stmt->execute()) {
            send_json_success('เพิ่มสินค้าสำเร็จ', ['product_id' => $product_id]);
        } else {
            send_json_error("Insert failed: " . $stmt->error, 500);
        }
        $stmt->close();

    // ------------------ PUT ------------------
    } elseif ($method === 'PUT') {
        if (empty($data['product_id'])) {
            send_json_error("กรุณาระบุรหัสสินค้า (product_id)");
        }

        $check_stmt = $conn->prepare("SELECT product_id FROM Product WHERE product_id = ?");
        if (!$check_stmt) send_json_error("Prepare check failed: " . $conn->error, 500);

        $check_stmt->bind_param('s', $data['product_id']);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows === 0) {
            $check_stmt->close();
            send_json_error("ไม่พบสินค้าที่ต้องการแก้ไข", 404);
        }
        $check_stmt->close();

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

        $update_stmt = $conn->prepare($update_sql);
        if (!$update_stmt) send_json_error("Prepare update failed: " . $conn->error, 500);

        $update_stmt->bind_param(
            "ssddddssssssdssss", // แก้เป็น 17 ตัวอักษร
            $name,
            $description,
            $width,
            $length,
            $height,
            $weight,
            $width_unit,
            $length_unit,
            $height_unit,
            $weight_unit,
            $lot,
            $stock,
            $price,
            $received_date,
            $category_id,
            $supplier_id,
            $product_id
        );


        if ($update_stmt->execute()) {
            if ($update_stmt->affected_rows > 0) {
                send_json_success("แก้ไขสินค้าสำเร็จ", ['product_id' => $product_id, 'affected_rows' => $update_stmt->affected_rows]);
            } else {
                send_json_success("ไม่มีการเปลี่ยนแปลงข้อมูล", ['product_id' => $product_id]);
            }
        } else {
            send_json_error("Update failed: " . $update_stmt->error, 500);
        }
        $update_stmt->close();

    // ------------------ DELETE ------------------
    } elseif ($method === 'DELETE') {
        if (empty($data['product_id'])) send_json_error("กรุณาระบุรหัสสินค้า (product_id)");

        $conn->begin_transaction();
        try {
            $delete_images_sql = "DELETE FROM ProductImage WHERE product_id=?";
            $delete_images_stmt = $conn->prepare($delete_images_sql);
            if ($delete_images_stmt) {
                $delete_images_stmt->bind_param("s", $data['product_id']);
                $delete_images_stmt->execute();
                $delete_images_stmt->close();
            }

            $delete_sql = "DELETE FROM Product WHERE product_id=?";
            $delete_stmt = $conn->prepare($delete_sql);
            if (!$delete_stmt) throw new Exception("Prepare delete failed: " . $conn->error);

            $delete_stmt->bind_param("s", $data['product_id']);
            if ($delete_stmt->execute()) {
                if ($delete_stmt->affected_rows > 0) {
                    $conn->commit();
                    send_json_success('ลบสินค้าสำเร็จ');
                } else {
                    $conn->rollback();
                    send_json_error("ไม่พบสินค้าที่ต้องการลบ", 404);
                }
            } else {
                $conn->rollback();
                throw new Exception("Delete failed: " . $delete_stmt->error);
            }
            $delete_stmt->close();
        } catch (Exception $e) {
            $conn->rollback();
            send_json_error("Delete operation failed: " . $e->getMessage(), 500);
        }

    } else {
        send_json_error("Method not allowed: " . $method, 405);
    }

} catch (Exception $e) {
    send_json_error("Unexpected error: " . $e->getMessage(), 500);
} catch (Error $e) {
    send_json_error("Fatal error: " . $e->getMessage(), 500);
}

$conn->close();
?>