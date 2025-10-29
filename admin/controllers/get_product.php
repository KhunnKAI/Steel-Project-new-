<?php
require_once 'config.php';
requireLogin(); // ต้อง login ก่อนใช้งาน

header('Content-Type: application/json; charset=utf-8');

// Debug mode (ปิดใน production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // ควรกำหนดเป็น 0 ใน Production

// ฟังก์ชันส่ง JSON error
function send_json_error($message, $code = 500) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * แปลง Full URL เป็น Relative Path
 * เช่น "https://example.com/uploads/products/image.jpg" จะกลายเป็น "/uploads/products/image.jpg"
 * @param string $full_url URL เต็ม
 * @return string Relative Path หรือ URL เดิมหากตัดไม่ได้
 */
function get_relative_path($full_url) {
    // ถ้า URL ว่างหรือไม่ใช่ string ให้ส่งค่าเดิมกลับ
    if (empty($full_url) || !is_string($full_url)) {
        return $full_url;
    }
    
    // ใช้ parse_url เพื่อแยกส่วนประกอบ
    $parts = parse_url($full_url);
    
    // ตรวจสอบว่ามีส่วน path หรือไม่
    if (isset($parts['path'])) {
        return $parts['path'];
    }
    
    // ถ้าไม่มีส่วน path หรือ URL ไม่สมบูรณ์ (เช่น แค่ /uploads/...) ให้ส่งค่าเดิมกลับ
    return $full_url; 
}


// ถ้ามี product_id => ดึงสินค้าตัวเดียวพร้อมรูปภาพทั้งหมด
if (!empty($_GET['product_id'])) {
    $product_id = $_GET['product_id'];
    
    try {
        // Query หลัก (เหมือนเดิม)
        $sql_product = "SELECT p.product_id, p.name, p.description,
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
                         LIMIT 1";
        
        $stmt = $pdo->prepare($sql_product);
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            echo json_encode(['success' => true, 'data' => null], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // ดึงรูปภาพทั้งหมดของสินค้านี้
        $images = [];
        $sql_images = "SELECT productimage_id, image_url, is_main, created_at, updated_at
                         FROM ProductImage 
                         WHERE product_id = ?
                         ORDER BY is_main DESC, created_at ASC";
        $stmt_img = $pdo->prepare($sql_images);
        $stmt_img->execute([$product_id]);
        
        while ($img = $stmt_img->fetch(PDO::FETCH_ASSOC)) {
            // *** การปรับปรุง: ใช้ฟังก์ชัน get_relative_path ที่นี่ ***
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

// ถ้าไม่ระบุ product_id => ดึงสินค้าทั้งหมด (ปรับปรุงโดยใช้ Eager Loading)
try {
    // 1. ดึงสินค้าหลักทั้งหมด (เหมือนเดิม)
    $sql = "SELECT p.product_id, p.name, p.description,
                   p.width, p.length, p.height, p.weight,
                   p.width_unit, p.length_unit, p.height_unit, p.weight_unit,
                   p.lot, p.stock, p.price, p.received_date,
                   p.category_id, COALESCE(c.name, 'ไม่ระบุ') AS category_name,
                   p.supplier_id, COALESCE(s.name, 'ไม่ระบุ') AS supplier_name,
                   p.created_at, p.updated_at
             FROM Product p
             LEFT JOIN Category c ON p.category_id = c.category_id
             LEFT JOIN Supplier s ON p.supplier_id = s.supplier_id
             ORDER BY p.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($products)) {
        echo json_encode(['success' => true, 'data' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2. ดึงรูปภาพทั้งหมดที่เกี่ยวข้องกับสินค้าเหล่านี้ (Eager Loading) (เหมือนเดิม)
    $product_ids = array_column($products, 'product_id');
    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';

    $sql_images = "SELECT product_id, productimage_id, image_url, is_main, created_at, updated_at
                     FROM ProductImage 
                     WHERE product_id IN ({$placeholders})
                     ORDER BY product_id, is_main DESC, created_at ASC";
    
    $stmt_img = $pdo->prepare($sql_images);
    $stmt_img->execute($product_ids);
    
    // จัดกลุ่มรูปภาพตาม product_id
    $images_map = [];
    while ($img = $stmt_img->fetch(PDO::FETCH_ASSOC)) {
        // *** การปรับปรุง: ใช้ฟังก์ชัน get_relative_path ที่นี่ ***
        $img['image_url'] = get_relative_path($img['image_url']); 
        
        if (!isset($images_map[$img['product_id']])) {
            $images_map[$img['product_id']] = [];
        }
        $images_map[$img['product_id']][] = $img;
    }

    // 3. ผนวกรูปภาพเข้ากับสินค้าหลัก (เหมือนเดิม)
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