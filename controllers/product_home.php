<?php
require_once 'config.php'; // ใช้การตั้งค่าจาก config.php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

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

// ฟังก์ชันส่ง JSON success
function send_json_success($data, $message = 'Success') {
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
try {
    if (!isset($pdo) || $pdo === null) {
        if (isset($database_error)) {
            send_json_error('Database connection failed: ' . $database_error);
        } else {
            $database = new Database();
            $pdo = $database->getConnection();
        }
    }
    
    // ทดสอบการเชื่อมต่อ
    $pdo->query("SELECT 1");
    
} catch (Exception $e) {
    send_json_error('Database connection failed: ' . $e->getMessage());
}

// รับพารามิเตอร์จาก URL
$product_id = isset($_GET['product_id']) ? $_GET['product_id'] : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 0;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$category_id = isset($_GET['category_id']) ? $_GET['category_id'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'latest';

// ถ้ามี product_id => ดึงสินค้าตัวเดียวพร้อมรูปภาพทั้งหมด
if (!empty($product_id)) {
    try {
        $sql_product = "SELECT p.product_id, p.name, p.description,
                               p.width, p.length, p.height, p.weight,
                               p.width_unit, p.length_unit, p.height_unit, p.weight_unit,
                               p.lot, p.stock, p.price, p.received_date,
                               p.category_id, COALESCE(c.name, 'ไม่ระบุ') AS category_name,
                               p.supplier_id, COALESCE(s.name, 'ไม่ระบุ') AS supplier_name,
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
            send_json_success(null, 'Product not found');
        }
        
        // ดึงรูปภาพทั้งหมดของสินค้านี้
        $sql_images = "SELECT productimage_id, image_url, is_main, created_at, updated_at
               FROM ProductImage 
               WHERE product_id = ?
               ORDER BY is_main DESC, created_at ASC";

        $stmt_img = $pdo->prepare($sql_images);
        $stmt_img->execute([$product_id]);
        $images = $stmt_img->fetchAll();

        // แปลงเป็น relative path ใช้ backslash
        foreach ($images as &$img) {
            if (!empty($img['image_url'])) {
                $relative_path = str_replace('http://localhost/steelproject/', '', $img['image_url']);
                $img['image_url'] = str_replace('/', '\\', $relative_path);
            }
        }
        unset($img);

        $product['images'] = $images;
        send_json_success($product, 'Product retrieved successfully');
        
    } catch (PDOException $e) {
        send_json_error('Database query error: ' . $e->getMessage());
    }
}

// ตรวจสอบว่าตารางมีอยู่จริงหรือไม่
try {
    $tables_check = $pdo->query("SHOW TABLES LIKE 'Product'")->fetchAll();
    if (empty($tables_check)) {
        send_json_error('Table "Product" does not exist in database "teststeel"');
    }
} catch (PDOException $e) {
    send_json_error('Cannot check database tables: ' . $e->getMessage());
}

// สร้าง WHERE clause สำหรับการค้นหาและกรอง
$where_conditions = [];
$params = [];

// กรองตามหมวดหมู่
if (!empty($category_id)) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_id;
}

// ค้นหาตามชื่อ คำอธิบาย หรือหมวดหมู่
if (!empty($search)) {
    $search_term = "%{$search}%";
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ? OR s.name LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

// สร้าง WHERE clause
$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(' AND ', $where_conditions);
}

// สร้าง ORDER BY clause
$order_clause = '';
switch ($sort) {
    case 'price-high':
        $order_clause = "ORDER BY p.price DESC";
        break;
    case 'price-low':
        $order_clause = "ORDER BY p.price ASC";
        break;
    case 'name-az':
        $order_clause = "ORDER BY p.name ASC";
        break;
    case 'latest':
    default:
        $order_clause = "ORDER BY p.created_at DESC";
        break;
}

// สร้าง LIMIT clause
$limit_clause = '';
if ($limit > 0) {
    $limit_clause = "LIMIT {$offset}, {$limit}";
}

// Query หลักสำหรับดึงสินค้าทั้งหมด
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
        {$where_clause}
        {$order_clause}
        {$limit_clause}";

try {
    // เตรียมและ execute query
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    
    $rows = [];
    foreach ($products as $product) {
        // ดึงรูปภาพของแต่ละสินค้า
        $sql_images = "SELECT productimage_id, image_url, is_main, created_at, updated_at
                       FROM ProductImage 
                       WHERE product_id = ?
                       ORDER BY is_main DESC, created_at ASC";
        
        $stmt_img = $pdo->prepare($sql_images);
        $stmt_img->execute([$product['product_id']]);
        $images = $stmt_img->fetchAll();

        // แปลงข้อมูลให้เป็นตัวเลขที่เหมาะสม
        $product['price'] = floatval($product['price']);
        $product['stock'] = intval($product['stock']);
        $product['width'] = $product['width'] ? floatval($product['width']) : null;
        $product['length'] = $product['length'] ? floatval($product['length']) : null;
        $product['height'] = $product['height'] ? floatval($product['height']) : null;
        $product['weight'] = $product['weight'] ? floatval($product['weight']) : null;

        $product['images'] = $images;
        $rows[] = $product;
    }

    // ถ้าต้องการข้อมูลเพิ่มเติม เช่น จำนวนทั้งหมด
    $total_count = 0;
    if ($limit > 0) {
        // นับจำนวนทั้งหมดสำหรับ pagination
        $count_sql = "SELECT COUNT(*) as total
                      FROM Product p
                      LEFT JOIN Category c ON p.category_id = c.category_id
                      LEFT JOIN Supplier s ON p.supplier_id = s.supplier_id
                      {$where_clause}";
        
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute($params);
        $count_result = $count_stmt->fetch();
        $total_count = intval($count_result['total']);
    }

    $response_data = $rows;

    // เพิ่มข้อมูล pagination ถ้ามี limit
    if ($limit > 0) {
        $response_data = [
            'products' => $rows,
            'pagination' => [
                'total' => $total_count,
                'limit' => $limit,
                'offset' => $offset,
                'current_page' => floor($offset / $limit) + 1,
                'total_pages' => ceil($total_count / $limit)
            ]
        ];
    }

    send_json_success($response_data, 'Products retrieved successfully');

} catch (PDOException $e) {
    send_json_error('Database query error: ' . $e->getMessage() . ' | SQL: ' . $sql);
}
?>