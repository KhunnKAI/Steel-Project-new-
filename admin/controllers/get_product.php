<?php
// ========================
// GET PRODUCT - API ENDPOINT
// ========================
// ดึงข้อมูลสินค้าพร้อมรูปภาพ

require_once 'config.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');

error_reporting(0);
ini_set('display_errors', 0);

// ========================
// FUNCTION: ส่ง JSON Error
// ========================
function send_json_error($message, $code = 500) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ========================
// FUNCTION: แปลง Full URL เป็น Relative Path
// ========================
function get_relative_path($full_url) {
    if (empty($full_url) || !is_string($full_url)) {
        return $full_url;
    }

    $parts = parse_url($full_url);

    if (isset($parts['path'])) {
        return $parts['path'];
    }

    return $full_url;
}

// ========================
// GET SINGLE PRODUCT
// ========================
if (!empty($_GET['product_id'])) {
    $product_id = $_GET['product_id'];

    try {
        // ดึงข้อมูลสินค้า
        $sql_product = "
            SELECT p.product_id, p.name, p.description,
                   p.width, p.length, p.height, p.weight,
                   p.width_unit, p.length_unit, p.height_unit, p.weight_unit,
                   p.lot, p.stock, p.price, p.received_date,
                   p.category_id, c.name AS category_name,
                   p.supplier_id, s.name AS supplier_name,
                   p.created_at, p.updated_at
            FROM Product p
            LEFT JOIN Category c ON p.category_id = c.category_id
            LEFT JOIN Supplier s ON p.supplier_id = s.supplier_id
            WHERE p.product_id = ?
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql_product);
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            echo json_encode(['success' => true, 'data' => null], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // ดึงรูปภาพทั้งหมด
        $images = [];
        $sql_images = "
            SELECT productimage_id, image_url, is_main, created_at, updated_at
            FROM ProductImage
            WHERE product_id = ?
            ORDER BY is_main DESC, created_at ASC
        ";
        $stmt_img = $pdo->prepare($sql_images);
        $stmt_img->execute([$product_id]);

        while ($img = $stmt_img->fetch(PDO::FETCH_ASSOC)) {
            $img['image_url'] = get_relative_path($img['image_url']);
            $images[] = $img;
        }

        $product['images'] = $images;

        echo json_encode(['success' => true, 'data' => $product], JSON_UNESCAPED_UNICODE);

    } catch (PDOException $e) {
        send_json_error('Database error: ' . $e->getMessage());
    }

    exit;
}

// ========================
// GET ALL PRODUCTS
// ========================
try {
    $sql = "
        SELECT p.product_id, p.name, p.description,
               p.width, p.length, p.height, p.weight,
               p.width_unit, p.length_unit, p.height_unit, p.weight_unit,
               p.lot, p.stock, p.price, p.received_date,
               p.category_id, COALESCE(c.name, 'ไม่ระบุ') AS category_name,
               p.supplier_id, COALESCE(s.name, 'ไม่ระบุ') AS supplier_name,
               p.created_at, p.updated_at
        FROM Product p
        LEFT JOIN Category c ON p.category_id = c.category_id
        LEFT JOIN Supplier s ON p.supplier_id = s.supplier_id
        ORDER BY p.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($products)) {
        echo json_encode(['success' => true, 'data' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Eager Loading: ดึงรูปภาพทั้งหมด
    $product_ids = array_column($products, 'product_id');
    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';

    $sql_images = "
        SELECT product_id, productimage_id, image_url, is_main, created_at, updated_at
        FROM ProductImage
        WHERE product_id IN ({$placeholders})
        ORDER BY product_id, is_main DESC, created_at ASC
    ";

    $stmt_img = $pdo->prepare($sql_images);
    $stmt_img->execute($product_ids);

    // จัดกลุ่มรูปภาพตาม product_id
    $images_map = [];
    while ($img = $stmt_img->fetch(PDO::FETCH_ASSOC)) {
        $img['image_url'] = get_relative_path($img['image_url']);

        if (!isset($images_map[$img['product_id']])) {
            $images_map[$img['product_id']] = [];
        }
        $images_map[$img['product_id']][] = $img;
    }

    // ผนวกรูปภาพเข้ากับสินค้า
    $rows = [];
    foreach ($products as $product) {
        $product_id = $product['product_id'];
        $product['images'] = $images_map[$product_id] ?? [];
        $rows[] = $product;
    }

    echo json_encode(['success' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    send_json_error('Database error: ' . $e->getMessage());
}
?>