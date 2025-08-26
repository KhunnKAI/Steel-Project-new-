<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

// Enable error logging for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1); // Temporarily enable for debugging
ini_set('log_errors', 1);

// Function to send JSON error
function send_json_error($message, $code = 500) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message,
        'debug' => [
            'timestamp' => date('Y-m-d H:i:s'),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Function to send JSON success
function send_json_success($data, $message = 'Success') {
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data,
        'debug' => [
            'timestamp' => date('Y-m-d H:i:s'),
            'count' => is_array($data) ? count($data) : 1
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Check PDO connection
    if (!isset($pdo)) {
        send_json_error('Database connection not available');
    }

    // Test database connection
    $test_query = $pdo->query("SELECT 1");
    if (!$test_query) {
        send_json_error('Database connection test failed');
    }

    // Check if specific product_id is requested
    if (!empty($_GET['product_id'])) {
        $product_id = $_GET['product_id'];
        
        // Query for single product
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
        if (!$stmt) {
            send_json_error('Failed to prepare statement for single product');
        }

        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            send_json_success(null, 'Product not found');
        }
        
        // Get all images for this product
        $sql_images = "SELECT productimage_id, image_url, is_main, created_at, updated_at
                       FROM ProductImage 
                       WHERE product_id = ?
                       ORDER BY is_main DESC, created_at ASC";
        $stmt_img = $pdo->prepare($sql_images);
        if (!$stmt_img) {
            send_json_error('Failed to prepare image query');
        }
        
        $stmt_img->execute([$product_id]);
        $images = $stmt_img->fetchAll(PDO::FETCH_ASSOC);
        
        $product['images'] = $images;
        
        send_json_success($product, 'Single product retrieved successfully');
    }

    // Get all products
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

    $stmt = $pdo->query($sql);
    if (!$stmt) {
        send_json_error('Failed to execute main query');
    }

    $products = [];
    $product_count = 0;
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $product_count++;
        
        // Get images for each product
        $sql_images = "SELECT productimage_id, image_url, is_main, created_at, updated_at
                       FROM ProductImage 
                       WHERE product_id = ?
                       ORDER BY is_main DESC, created_at ASC";
        $stmt_img = $pdo->prepare($sql_images);
        if (!$stmt_img) {
            error_log("Failed to prepare image query for product: " . $row['product_id']);
            $row['images'] = []; // Set empty array if image query fails
        } else {
            $stmt_img->execute([$row['product_id']]);
            $images = $stmt_img->fetchAll(PDO::FETCH_ASSOC);
            $row['images'] = $images;
        }
        
        $products[] = $row;
    }

    // Return successful response with debug info
    send_json_success($products, "Successfully loaded {$product_count} products");

} catch (PDOException $e) {
    error_log("PDO Error in get_products.php: " . $e->getMessage());
    send_json_error('Database error: ' . $e->getMessage());
} catch (Exception $e) {
    error_log("General Error in get_products.php: " . $e->getMessage());
    send_json_error('เกิดข้อผิดพลาดในการโหลดข้อมูลสินค้า: ' . $e->getMessage());
}
?>