<?php
// ========================
// MANAGE PRODUCT - API ENDPOINT
// ========================
// จัดการ CRUD สำหรับสินค้า (Create, Read, Update, Delete)

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, DELETE, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

error_reporting(0);
ini_set('display_errors', 0);

require_once 'config.php';
require_once 'stock_logger.php';

// ========================
// FUNCTION: ส่ง JSON Error
// ========================
function send_json_error($message, $status_code = 400) {
    http_response_code($status_code);
    echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

// ========================
// FUNCTION: ส่ง JSON Success
// ========================
function send_json_success($message, $data = null) {
    $response = ['success' => true, 'status' => 'success', 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stockLogger = new StockLogger($pdo);

    $raw_input = file_get_contents("php://input");
    $data = json_decode($raw_input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        $data = array_merge($data ?? [], $_POST ?? []);
    }

    $method = $_SERVER['REQUEST_METHOD'];
    if ($method === 'POST' && isset($data['_method'])) {
        $method = strtoupper($data['_method']);
    }

    // ========================
    // GET - ดึงสินค้าทั้งหมด
    // ========================
    if ($method === 'GET') {
        $sql = "
            SELECT p.*, c.name as category_name, s.name as supplier_name
            FROM Product p
            LEFT JOIN Category c ON p.category_id = c.category_id
            LEFT JOIN Supplier s ON p.supplier_id = s.supplier_id
            ORDER BY p.created_at DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $products = $stmt->fetchAll();

        foreach ($products as &$product) {
            $images_sql = "
                SELECT productimage_id, image_url, is_main, created_at, updated_at
                FROM ProductImage
                WHERE product_id = ?
                ORDER BY is_main DESC, created_at ASC
            ";
            $img_stmt = $pdo->prepare($images_sql);
            $img_stmt->execute([$product['product_id']]);
            $product['images'] = $img_stmt->fetchAll();
        }

        echo json_encode(['success' => true, 'data' => $products], JSON_UNESCAPED_UNICODE);

    // ========================
    // POST - เพิ่มสินค้าใหม่
    // ========================
    } elseif ($method === 'POST' && !isset($data['_method'])) {
        if (empty($data['name'])) {
            send_json_error("จำเป็นต้องกรอกชื่อสินค้า");
        }

        // สร้าง product_id อัตโนมัติ
        if (empty($data['product_id'])) {
            $prefix = 'S' . date('ym');
            $sql_last = "SELECT product_id FROM Product WHERE product_id LIKE ? ORDER BY product_id DESC LIMIT 1";
            $stmt_last = $pdo->prepare($sql_last);
            $stmt_last->execute([$prefix . '%']);
            $result_last = $stmt_last->fetch();

            $next_number = 1;
            if ($result_last) {
                $last_num = (int)substr($result_last['product_id'], strlen($prefix));
                $next_number = $last_num + 1;
            }
            $data['product_id'] = $prefix . str_pad($next_number, 4, '0', STR_PAD_LEFT);
        }

        $product_id = $data['product_id'];
        $name = $data['name'];
        $description = $data['description'] ?? null;
        $width = !empty($data['width']) ? (float)$data['width'] : null;
        $length = !empty($data['length']) ? (float)$data['length'] : null;
        $height = !empty($data['height']) ? (float)$data['height'] : null;
        $weight = !empty($data['weight']) ? (float)$data['weight'] : null;
        $width_unit = $data['width_unit'] ?? 'mm';
        $length_unit = $data['length_unit'] ?? 'mm';
        $height_unit = $data['height_unit'] ?? 'mm';
        $weight_unit = $data['weight_unit'] ?? 'kg';
        $lot = $data['lot'] ?? '';
        $initial_stock = !empty($data['stock']) ? (int)$data['stock'] : 0;
        $price = !empty($data['price']) ? (float)$data['price'] : 0;
        $received_date = !empty($data['received_date']) ? $data['received_date'] : null;
        $category_id = $data['category_id'] ?? 'ot';
        $supplier_id = $data['supplier_id'] ?? null;

        $pdo->beginTransaction();

        try {
            $sql = "
                INSERT INTO Product (
                    product_id, name, description, width, length, height, weight,
                    width_unit, length_unit, height_unit, weight_unit, lot, stock, price,
                    received_date, category_id, supplier_id, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, NOW(), NOW())
            ";

            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                $product_id, $name, $description, $width, $length, $height, $weight,
                $width_unit, $length_unit, $height_unit, $weight_unit, $lot,
                $price, $received_date, $category_id, $supplier_id
            ]);

            if (!$result) {
                throw new Exception("ไม่สามารถเพิ่มสินค้าได้");
            }

            // เพิ่มสต็อกเริ่มต้น
            if ($initial_stock > 0) {
                $admin_id = $_SESSION['admin_id'] ?? $data['admin_id'] ?? null;

                $stock_result = $stockLogger->addInitialStock(
                    $product_id,
                    $initial_stock,
                    $admin_id,
                    "สต็อกเริ่มต้นสำหรับสินค้าใหม่: " . $name
                );

                if (!$stock_result['success']) {
                    throw new Exception("ไม่สามารถเพิ่มสต็อกได้: " . $stock_result['error']);
                }
            }

            $pdo->commit();
            send_json_success('เพิ่มสินค้าเสร็จสิ้น', [
                'product_id' => $product_id,
                'initial_stock' => $initial_stock,
                'stock_logged' => $initial_stock > 0
            ]);

        } catch (Exception $e) {
            $pdo->rollBack();
            send_json_error("เพิ่มสินค้าล้มเหลว: " . $e->getMessage(), 500);
        }

    // ========================
    // PUT - อัปเดตสินค้า
    // ========================
    } elseif ($method === 'PUT') {
        if (empty($data['product_id'])) {
            send_json_error("จำเป็นต้องกรอกรหัสสินค้า");
        }

        $check_stmt = $pdo->prepare("SELECT product_id, stock FROM Product WHERE product_id = ?");
        $check_stmt->execute([$data['product_id']]);
        $current_product = $check_stmt->fetch();

        if (!$current_product) {
            send_json_error("ไม่พบสินค้า", 404);
        }

        $product_id = $data['product_id'];
        $name = $data['name'];
        $description = $data['description'] ?? null;
        $width = !empty($data['width']) ? (float)$data['width'] : null;
        $length = !empty($data['length']) ? (float)$data['length'] : null;
        $height = !empty($data['height']) ? (float)$data['height'] : null;
        $weight = !empty($data['weight']) ? (float)$data['weight'] : null;
        $width_unit = $data['width_unit'] ?? 'mm';
        $length_unit = $data['length_unit'] ?? 'mm';
        $height_unit = $data['height_unit'] ?? 'mm';
        $weight_unit = $data['weight_unit'] ?? 'kg';
        $lot = $data['lot'] ?? '';
        $new_stock = isset($data['stock']) ? (int)$data['stock'] : null;
        $price = !empty($data['price']) ? (float)$data['price'] : 0;
        $received_date = !empty($data['received_date']) ? $data['received_date'] : null;
        $category_id = $data['category_id'] ?? 'ot';
        $supplier_id = $data['supplier_id'] ?? null;

        $pdo->beginTransaction();

        try {
            $update_sql = "
                UPDATE Product SET
                    name=?, description=?, width=?, length=?, height=?, weight=?,
                    width_unit=?, length_unit=?, height_unit=?, weight_unit=?,
                    lot=?, price=?, received_date=?,
                    category_id=?, supplier_id=?,
                    updated_at=NOW()
                WHERE product_id=?
            ";

            $update_stmt = $pdo->prepare($update_sql);
            $result = $update_stmt->execute([
                $name, $description, $width, $length, $height, $weight,
                $width_unit, $length_unit, $height_unit, $weight_unit,
                $lot, $price, $received_date, $category_id, $supplier_id,
                $product_id
            ]);

            if (!$result) {
                throw new Exception("ไม่สามารถอัปเดตสินค้าได้");
            }

            // อัปเดตสต็อก
            $stock_updated = false;
            if ($new_stock !== null && $new_stock != $current_product['stock']) {
                $current_stock = (int)$current_product['stock'];
                $stock_difference = $new_stock - $current_stock;
                $admin_id = $_SESSION['admin_id'] ?? $data['admin_id'] ?? null;

                if ($stock_difference > 0) {
                    $stock_result = $stockLogger->updateProductStock(
                        $product_id, 'in', $stock_difference, 'manual', null, null, $admin_id,
                        "Manual stock adjustment (+{$stock_difference}) via product update"
                    );
                } else {
                    $stock_result = $stockLogger->updateProductStock(
                        $product_id, 'out', abs($stock_difference), 'manual', null, null, $admin_id,
                        "Manual stock adjustment ({$stock_difference}) via product update"
                    );
                }

                if (!$stock_result['success']) {
                    throw new Exception("ไม่สามารถอัปเดตสต็อคได้: " . $stock_result['error']);
                }
                $stock_updated = true;
            }

            $pdo->commit();
            send_json_success('อัปเดตสินค้าเสร็จสิ้น', [
                'product_id' => $product_id,
                'affected_rows' => $update_stmt->rowCount(),
                'stock_updated' => $stock_updated,
                'old_stock' => $current_product['stock'],
                'new_stock' => $new_stock
            ]);

        } catch (Exception $e) {
            $pdo->rollBack();
            send_json_error("อัปเดตสินค้าล้มเหลว: " . $e->getMessage(), 500);
        }

    // ========================
    // DELETE - ลบสินค้า
    // ========================
    } elseif ($method === 'DELETE') {
        $product_id = $data['product_id'] ?? $_POST['product_id'] ?? $_GET['product_id'] ?? null;

        if (empty($product_id)) {
            send_json_error("จำเป็นต้องกรอกรหัสสินค้า");
        }

        // ตรวจสอบการใช้งาน
        $check_usage = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM OrderItem oi
            INNER JOIN Orders o ON oi.order_id = o.order_id
            WHERE oi.product_id = ?
            AND o.status NOT IN ('status04', 'status05')
        ");
        $check_usage->execute([$product_id]);
        $usage_result = $check_usage->fetch();

        if ($usage_result['count'] > 0) {
            send_json_error("ไม่สามารถลบสินค้าได้ เนื่องจากสินค้ากำลังใช้งานในออเดอร์", 400);
        }

        $pdo->beginTransaction();
        try {
            $delete_images_sql = "DELETE FROM ProductImage WHERE product_id=?";
            $delete_images_stmt = $pdo->prepare($delete_images_sql);
            $delete_images_stmt->execute([$product_id]);

            $delete_stock_logs = $pdo->prepare("DELETE FROM StockLog WHERE product_id=?");
            $delete_stock_logs->execute([$product_id]);

            $delete_sql = "DELETE FROM Product WHERE product_id=?";
            $delete_stmt = $pdo->prepare($delete_sql);
            $result = $delete_stmt->execute([$product_id]);

            if (!$result) {
                throw new Exception("ไม่สามารถลบสินค้าได้");
            }

            if ($delete_stmt->rowCount() > 0) {
                $pdo->commit();
                send_json_success('ลบสินค้าเสร็จสิ้น', [
                    'product_id' => $product_id,
                    'deleted_images' => $delete_images_stmt->rowCount(),
                    'deleted_stock_logs' => $delete_stock_logs->rowCount()
                ]);
            } else {
                $pdo->rollback();
                send_json_error("ไม่พบสินค้า", 404);
            }

        } catch (Exception $e) {
            $pdo->rollback();
            send_json_error("ลบสินค้าล้มเหลว: " . $e->getMessage(), 500);
        }

    } else {
        send_json_error("ไม่อนุญาตให้ใช้ Method นี้: " . $method, 405);
    }

} catch (Exception $e) {
    error_log("Unexpected error: " . $e->getMessage());
    send_json_error("เกิดข้อผิดพลาด: " . $e->getMessage(), 500);
}
?>