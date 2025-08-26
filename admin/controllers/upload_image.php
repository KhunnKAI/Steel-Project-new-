<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

function send_json_error($message, $code = 500) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function send_json_success($message, $url = null, $data = null) {
    $response = [
        'success' => true,
        'status' => 'success',
        'message' => $message
    ];
    if ($url !== null) {
        $response['url'] = $url;
    }
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}

// Configuration
$UPLOAD_DIR = 'uploads/products/';
$MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
$ALLOWED_TYPES = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

// Get the base URL for images
function getImageBaseURL() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $script_path = dirname($_SERVER['SCRIPT_NAME']);
    return $protocol . $host . $script_path . '/';
}

try {
    // Only allow POST method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        send_json_error('Only POST method is allowed', 405);
    }

    // Create upload directory if not exists
    if (!file_exists($UPLOAD_DIR)) {
        if (!mkdir($UPLOAD_DIR, 0755, true)) {
            send_json_error('Failed to create upload directory');
        }
    }

    // Validate required fields
    if (!isset($_FILES['image'])) {
        send_json_error('No image file provided');
    }
    if (!isset($_POST['product_id']) || empty($_POST['product_id'])) {
        send_json_error('Product ID is required');
    }

    $file = $_FILES['image'];
    $product_id = trim($_POST['product_id']);
    $is_main = isset($_POST['is_main']) ? filter_var($_POST['is_main'], FILTER_VALIDATE_BOOLEAN) : false;

    // Validate file upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File too large (server limit)',
            UPLOAD_ERR_FORM_SIZE => 'File too large (form limit)',
            UPLOAD_ERR_PARTIAL => 'File only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'No temporary directory',
            UPLOAD_ERR_CANT_WRITE => 'Cannot write to disk',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
        ];
        $error_message = $error_messages[$file['error']] ?? 'Unknown upload error';
        send_json_error($error_message);
    }

    // Check if product exists
    $check_stmt = $pdo->prepare("SELECT product_id FROM Product WHERE product_id = ?");
    $check_stmt->execute([$product_id]);

    if ($check_stmt->rowCount() === 0) {
        send_json_error('Product not found', 404);
    }

    // Validate file type
    $file_info = new finfo(FILEINFO_MIME_TYPE);
    $detected_type = $file_info->file($file['tmp_name']);
    
    if (!in_array($file['type'], $ALLOWED_TYPES) && !in_array($detected_type, $ALLOWED_TYPES)) {
        send_json_error('Invalid file type. Allowed: JPG, PNG, GIF, WebP');
    }

    // Validate file extension
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, $ALLOWED_EXTENSIONS)) {
        send_json_error('Invalid file extension');
    }

    // Validate file size
    if ($file['size'] > $MAX_FILE_SIZE) {
        send_json_error('File too large. Maximum size: ' . ($MAX_FILE_SIZE / 1024 / 1024) . 'MB');
    }

    // Additional security checks
    if ($file['size'] === 0) {
        send_json_error('Empty file not allowed');
    }

    // Check if file is actually an image
    $image_info = getimagesize($file['tmp_name']);
    if ($image_info === false) {
        send_json_error('File is not a valid image');
    }

    // Generate secure filename
    $safe_filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $file_extension;
    $safe_filename = basename($safe_filename); // extra security
    $file_path = $UPLOAD_DIR . $safe_filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        send_json_error('Failed to move uploaded file');
    }

    // Generate full URL for image access
    $base_url = getImageBaseURL();
    $image_url = $base_url . $file_path;

    // Generate unique productimage_id
    $productimage_id = 'IMG' . date('YmdHis') . bin2hex(random_bytes(4));

    // Start transaction
    $pdo->beginTransaction();

    try {
        // If this is set as main image, check if product has no images yet
        $should_be_main = $is_main;
        
        // Check if product has any images
        $count_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM ProductImage WHERE product_id = ?");
        $count_stmt->execute([$product_id]);
        $current_count = $count_stmt->fetch()['total'];
        
        // If product has no images yet, make this the main image
        if ($current_count == 0) {
            $should_be_main = true;
        }
        
        $is_main_int = $should_be_main ? 1 : 0;

        // If setting as main, update existing main images
        if ($should_be_main) {
            $update_main_stmt = $pdo->prepare("
                UPDATE ProductImage 
                SET is_main = 0, updated_at = NOW() 
                WHERE product_id = ? AND is_main = 1
            ");
            $update_main_stmt->execute([$product_id]);
        }

        // Insert new image record
        $insert_stmt = $pdo->prepare("
            INSERT INTO ProductImage (productimage_id, product_id, image_url, is_main, created_at, updated_at) 
            VALUES (?, ?, ?, ?, NOW(), NOW())
        ");
        $insert_stmt->execute([$productimage_id, $product_id, $image_url, $is_main_int]);

        // Update product's main image reference if this is the main image
        if ($should_be_main) {
            $update_product_stmt = $pdo->prepare("
                UPDATE Product 
                SET updated_at = NOW() 
                WHERE product_id = ?
            ");
            $update_product_stmt->execute([$product_id]);
        }

        // Get total image count for this product
        $count_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM ProductImage WHERE product_id = ?");
        $count_stmt->execute([$product_id]);
        $total_images = $count_stmt->fetch()['total'];

        // Commit transaction
        $pdo->commit();

        // Response data
        $response_data = [
            'productimage_id' => $productimage_id,
            'product_id' => $product_id,
            'image_url' => $image_url,
            'filename' => $safe_filename,
            'is_main' => (bool)$should_be_main,
            'file_size' => $file['size'],
            'file_type' => $detected_type,
            'dimensions' => [
                'width' => $image_info[0],
                'height' => $image_info[1]
            ],
            'total_images' => (int)$total_images
        ];

        $message = 'อัปโหลดรูปภาพสำเร็จ' . ($should_be_main ? ' (ตั้งเป็นรูปหลัก)' : '');
        send_json_success($message, $image_url, $response_data);

    } catch (Exception $e) {
        // Rollback transaction
        $pdo->rollback();
        
        // Delete uploaded file if database operation failed
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error in upload_image.php: " . $e->getMessage());
    send_json_error('Upload failed: ' . $e->getMessage());
}
?>