<?php
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

// ใช้ config.php แทน
require_once 'config.php';

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

// FIXED: Get the base URL for images
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

    // สร้าง mysqli connection จาก config.php
    $conn = new mysqli($host, $username, $password, $db_name);
    if ($conn->connect_error) {
        send_json_error('Database connection failed: ' . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');

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
    $check_stmt = $conn->prepare("SELECT product_id, productimage_id FROM Product WHERE product_id = ?");
    if (!$check_stmt) {
        send_json_error('Database prepare failed: ' . $conn->error);
    }
    $check_stmt->bind_param('s', $product_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        $check_stmt->close();
        send_json_error('Product not found', 404);
    }

    $product_data = $result->fetch_assoc();
    $current_productimage_id = $product_data['productimage_id'];
    $check_stmt->close();

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

    // FIXED: Generate full URL for image access
    $base_url = getImageBaseURL();
    $image_url = $base_url . $file_path; // Full URL like: http://localhost/steelproject/admin/controllers/uploads/products/filename.jpg

    // Generate unique productimage_id
    $productimage_id = 'IMG' . date('YmdHis') . bin2hex(random_bytes(4));

    // Start transaction
    $conn->begin_transaction();

    try {
        // If this is set as main image, or if product has no images yet
        $should_be_main = $is_main;
        
        // If product has no images yet, make this the main image
        if (empty($current_productimage_id)) {
            $should_be_main = true;
        }
        
        $is_main_int = $should_be_main ? 1 : 0;

        // If setting as main, update existing main images
        if ($should_be_main) {
            $update_main_stmt = $conn->prepare("
                UPDATE ProductImage 
                SET is_main = 0, updated_at = NOW() 
                WHERE product_id = ? AND is_main = 1
            ");
            if ($update_main_stmt) {
                $update_main_stmt->bind_param('s', $product_id);
                $update_main_stmt->execute();
                $update_main_stmt->close();
            }
        }

        // Insert new image record
        $insert_stmt = $conn->prepare("
            INSERT INTO ProductImage (productimage_id, product_id, image_url, is_main, created_at, updated_at) 
            VALUES (?, ?, ?, ?, NOW(), NOW())
        ");
        if (!$insert_stmt) {
            throw new Exception('Failed to prepare insert statement: ' . $conn->error);
        }

        $insert_stmt->bind_param('sssi', $productimage_id, $product_id, $image_url, $is_main_int);
        if (!$insert_stmt->execute()) {
            throw new Exception('Failed to insert image record: ' . $insert_stmt->error);
        }
        $insert_stmt->close();

        // Update product's main image reference if this is the main image
        if ($should_be_main) {
            $update_product_stmt = $conn->prepare("
                UPDATE Product 
                SET productimage_id = ?, updated_at = NOW() 
                WHERE product_id = ?
            ");
            if ($update_product_stmt) {
                $update_product_stmt->bind_param('ss', $productimage_id, $product_id);
                $update_product_stmt->execute();
                $update_product_stmt->close();
            }
        }

        // Get total image count for this product
        $count_stmt = $conn->prepare("
            SELECT COUNT(*) as total 
            FROM ProductImage 
            WHERE product_id = ?
        ");
        $count_stmt->bind_param('s', $product_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $total_images = $count_result->fetch_assoc()['total'];
        $count_stmt->close();

        // Commit transaction
        $conn->commit();

        // Response data
        $response_data = [
            'productimage_id' => $productimage_id,
            'product_id' => $product_id,
            'image_url' => $image_url, // This now contains the full URL
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
        $conn->rollback();
        
        // Delete uploaded file if database operation failed
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        throw $e;
    }

    $conn->close();

} catch (Exception $e) {
    error_log("Error in upload_image.php: " . $e->getMessage());
    send_json_error('Upload failed: ' . $e->getMessage());
}
?>