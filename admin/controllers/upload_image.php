<?php
// ========================
// UPLOAD IMAGE - API ENDPOINT
// ========================
// อัปโหลดและบันทึกรูปภาพสินค้า

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once 'config.php';

// ========================
// CONFIGURATION
// ========================
$UPLOAD_DIR = 'uploads/products/';
$MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
$ALLOWED_TYPES = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

// ========================
// FUNCTION: ส่ง JSON Error
// ========================
function send_json_error($message, $code = 500) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ========================
// FUNCTION: ส่ง JSON Success
// ========================
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

// ========================
// FUNCTION: ได้รับ Base URL
// ========================
function getImageBaseURL() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $script_path = dirname($_SERVER['SCRIPT_NAME']);
    return $protocol . $host . $script_path . '/';
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        send_json_error('อนุญาตเฉพาะ POST method เท่านั้น', 405);
    }

    // ตรวจสอบฟิลด์ที่จำเป็น
    if (!isset($_FILES['image'])) {
        send_json_error('ไม่พบไฟล์รูปภาพ');
    }
    if (!isset($_POST['product_id']) || empty($_POST['product_id'])) {
        send_json_error('รหัสสินค้าเป็นสิ่งจำเป็น');
    }

    $file = $_FILES['image'];
    $product_id = trim($_POST['product_id']);
    $is_main = isset($_POST['is_main']) ? filter_var($_POST['is_main'], FILTER_VALIDATE_BOOLEAN) : false;

    // ========================
    // VALIDATE FILE UPLOAD
    // ========================
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'ไฟล์ใหญ่เกินไป (ขีดจำกัดของเซิร์ฟเวอร์)',
            UPLOAD_ERR_FORM_SIZE => 'ไฟล์ใหญ่เกินไป (ขีดจำกัดของฟอร์ม)',
            UPLOAD_ERR_PARTIAL => 'ไฟล์อัปโหลดไม่สมบูรณ์',
            UPLOAD_ERR_NO_FILE => 'ไม่มีไฟล์ที่อัปโหลด',
            UPLOAD_ERR_NO_TMP_DIR => 'ไม่มีโฟลเดอร์ชั่วคราว',
            UPLOAD_ERR_CANT_WRITE => 'ไม่สามารถเขียนลงในดิสก์ได้',
            UPLOAD_ERR_EXTENSION => 'ส่วนขยายบล็อกการอัปโหลด'
        ];
        $error_message = $error_messages[$file['error']] ?? 'ข้อผิดพลาดการอัปโหลดที่ไม่รู้จัก';
        send_json_error($error_message);
    }

    // ตรวจสอบสินค้า
    $check_stmt = $pdo->prepare("SELECT product_id FROM Product WHERE product_id = ?");
    $check_stmt->execute([$product_id]);

    if ($check_stmt->rowCount() === 0) {
        send_json_error('ไม่พบสินค้า', 404);
    }

    // ตรวจสอบประเภทไฟล์
    $file_info = new finfo(FILEINFO_MIME_TYPE);
    $detected_type = $file_info->file($file['tmp_name']);

    if (!in_array($file['type'], $ALLOWED_TYPES) && !in_array($detected_type, $ALLOWED_TYPES)) {
        send_json_error('ประเภทไฟล์ไม่อนุญาต ประเภทที่อนุญาต: JPG, PNG, GIF, WebP');
    }

    // ตรวจสอบส่วนขยายไฟล์
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, $ALLOWED_EXTENSIONS)) {
        send_json_error('ส่วนขยายไฟล์ไม่อนุญาต');
    }

    // ตรวจสอบขนาดไฟล์
    if ($file['size'] > $MAX_FILE_SIZE) {
        send_json_error('ไฟล์ใหญ่เกินไป ขนาดสูงสุด: ' . ($MAX_FILE_SIZE / 1024 / 1024) . 'MB');
    }

    // ตรวจสอบไฟล์ว่าง
    if ($file['size'] === 0) {
        send_json_error('ไม่อนุญาตไฟล์ว่าง');
    }

    // ตรวจสอบว่าเป็นรูปภาพ
    $image_info = getimagesize($file['tmp_name']);
    if ($image_info === false) {
        send_json_error('ไฟล์ไม่ใช่รูปภาพที่ถูกต้อง');
    }

    // สร้างชื่อไฟล์ที่ปลอดภัย
    $safe_filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $file_extension;
    $safe_filename = basename($safe_filename);
    $file_path = $UPLOAD_DIR . $safe_filename;

    // สร้างโฟลเดอร์ถ้ายังไม่มี
    if (!file_exists($UPLOAD_DIR)) {
        if (!mkdir($UPLOAD_DIR, 0755, true)) {
            send_json_error('ไม่สามารถสร้างโฟลเดอร์อัปโหลดได้');
        }
    }

    // ย้ายไฟล์
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        send_json_error('ไม่สามารถย้ายไฟล์ที่อัปโหลดได้');
    }

    // สร้าง URL เต็ม
    $base_url = getImageBaseURL();
    $image_url = $base_url . $file_path;

    // สร้าง productimage_id
    $productimage_id = 'IMG' . date('YmdHis') . bin2hex(random_bytes(4));

    // เริ่มต้น Transaction
    $pdo->beginTransaction();

    try {
        // ========== ตั้งค่าเป็นรูปภาพหลัก ==========
        $should_be_main = $is_main;

        // ตรวจสอบว่ามีรูปภาพหลักแล้วหรือไม่
        $check_main = $pdo->prepare("SELECT COUNT(*) as count FROM ProductImage WHERE product_id = ?");
        $check_main->execute([$product_id]);
        $main_count = $check_main->fetch()['count'];

        if ($main_count === 0) {
            $should_be_main = true;
        }

        if ($should_be_main) {
            $update_main = $pdo->prepare("
                UPDATE ProductImage
                SET is_main = 0, updated_at = NOW()
                WHERE product_id = ? AND is_main = 1
            ");
            $update_main->execute([$product_id]);
        }

        // ========== บันทึกรูปภาพ ==========
        $is_main_int = $should_be_main ? 1 : 0;

        $insert_stmt = $pdo->prepare("
            INSERT INTO ProductImage (productimage_id, product_id, image_url, is_main, created_at, updated_at)
            VALUES (?, ?, ?, ?, NOW(), NOW())
        ");

        if (!$insert_stmt->execute([$productimage_id, $product_id, $image_url, $is_main_int])) {
            throw new Exception('ไม่สามารถบันทึกรูปภาพได้');
        }

        // ========== อัปเดตสินค้า ==========
        if ($should_be_main) {
            $update_product = $pdo->prepare("
                UPDATE Product
                SET productimage_id = ?, updated_at = NOW()
                WHERE product_id = ?
            ");
            $update_product->execute([$productimage_id, $product_id]);
        }

        // ========== นับจำนวนรูปภาพทั้งหมด ==========
        $count_stmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM ProductImage
            WHERE product_id = ?
        ");
        $count_stmt->execute([$product_id]);
        $total_images = $count_stmt->fetch()['total'];

        // ปิด Transaction
        $pdo->commit();

        // ส่ง Response
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
        $pdo->rollback();

        if (file_exists($file_path)) {
            unlink($file_path);
        }

        throw $e;
    }

} catch (Exception $e) {
    error_log("Error in upload_image.php: " . $e->getMessage());
    send_json_error('อัปโหลดล้มเหลว: ' . $e->getMessage());
}
?>