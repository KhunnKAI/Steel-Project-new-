<?php
require_once 'config.php';
requireLogin(); // ต้อง login ก่อนใช้งาน

header('Content-Type: application/json; charset=utf-8');

// Debug mode (ปิดใน production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// ฟังก์ชันส่ง JSON error
function send_json_error($message, $code = 500) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ถ้ามี product_id => ดึงสินค้าตัวเดียวพร้อมรูปภาพทั้งหมด
if (!empty($_GET['product_id'])) {
    $product_id = $_GET['product_id'];
    
    try {
        // Query หลัก
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
        $product = $stmt->fetch();
        
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
        
        while ($img = $stmt_img->fetch()) {
            $images[] = $img;
        }
        
        $product['images'] = $images;
        
        echo json_encode(['success' => true, 'data' => $product], JSON_UNESCAPED_UNICODE);
        
    } catch (PDOException $e) {
        send_json_error('Database error: ' . $e->getMessage());
    }
    
    exit;
}

// ถ้าไม่ระบุ product_id => ดึงสินค้าทั้งหมด
try {
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

    $rows = [];
    while ($r = $stmt->fetch()) {
        // ดึงรูป
        $images = [];
        $sql_images = "SELECT productimage_id, image_url, is_main, created_at, updated_at
                       FROM ProductImage 
                       WHERE product_id = ?
                       ORDER BY is_main DESC, created_at ASC";
        $stmt_img = $pdo->prepare($sql_images);
        $stmt_img->execute([$r['product_id']]);
        
        while ($img = $stmt_img->fetch()) {
            $images[] = $img;
        }

        $r['images'] = $images;
        $rows[] = $r;
    }

    echo json_encode(['success' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    send_json_error('Database error: ' . $e->getMessage());
}
?>