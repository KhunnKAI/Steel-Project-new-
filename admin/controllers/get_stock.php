<?php
// ========================
// HEADERS & CORS SETUP
// ========================

// FUNCTION: ตั้งค่า headers สำหรับ API
require_once 'config.php';
require_once 'stock_logger.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// ========================
// REQUEST PARAMETERS
// ========================

// FUNCTION: รับพารามิเตอร์จาก GET/POST
$action = $_GET['action'] ?? 'get_movements';
$product_id = $_GET['product_id'] ?? '';
$change_type = $_GET['change_type'] ?? '';
$reference_type = $_GET['reference_type'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$search = $_GET['search'] ?? '';
$user_filter = $_GET['user_filter'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$limit = (int)($_GET['limit'] ?? 10);

// FUNCTION: คำนวณ offset สำหรับ pagination
$offset = ($page - 1) * $limit;

// ========================
// INITIALIZE LOGGER
// ========================

// FUNCTION: สร้าง StockLogger instance
$stockLogger = new StockLogger($pdo);

// ========================
// HANDLE REQUESTS
// ========================

try {
    // FUNCTION: ตรวจสอบ action และเรียกใช้ฟังก์ชันที่เหมาะสม
    switch ($action) {
        case 'get_movements':
            $movements = getStockMovements($pdo, $product_id, $change_type, $reference_type, $start_date, $end_date, $search, $user_filter, $limit, $offset);
            echo json_encode($movements);
            break;
            
        case 'get_stats':
            $stats = getStockStats($pdo);
            echo json_encode($stats);
            break;
            
        case 'get_products':
            $products = getProductsForSelect($pdo);
            echo json_encode($products);
            break;
            
        case 'get_history':
            if (empty($product_id)) {
                throw new Exception('Product ID is required for history');
            }
            $history = $stockLogger->getStockHistory($product_id, $limit);
            echo json_encode($history);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action parameter'
            ]);
    }
} catch (Exception $e) {
    // ========================
    // ERROR HANDLING
    // ========================

    // FUNCTION: จัดการข้อผิดพลาดสำหรับ GET requests
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ]);
}

// ========================
// HELPER FUNCTIONS
// ========================

/**
 * FUNCTION: ดึงข้อมูลการเคลื่อนไหวของสต็อก
 * 
 * @param PDO $pdo
 * @param string $product_id
 * @param string $change_type (in, out, adjust)
 * @param string $reference_type (order, cancel, receive, manual)
 * @param string $start_date
 * @param string $end_date
 * @param string $search
 * @param string $user_filter
 * @param int $limit
 * @param int $offset
 */
function getStockMovements($pdo, $product_id, $change_type, $reference_type, $start_date, $end_date, $search, $user_filter, $limit, $offset) {
    // FUNCTION: สร้างเงื่อนไข WHERE clause
    $where_conditions = [];
    $params = [];
    
    if (!empty($product_id)) {
        $where_conditions[] = "sl.product_id = :product_id";
        $params[':product_id'] = $product_id;
    }
    
    if (!empty($change_type)) {
        $where_conditions[] = "sl.change_type = :change_type";
        $params[':change_type'] = $change_type;
    }
    
    if (!empty($reference_type)) {
        $where_conditions[] = "sl.reference_type = :reference_type";
        $params[':reference_type'] = $reference_type;
    }
    
    if (!empty($start_date)) {
        $where_conditions[] = "DATE(sl.created_at) >= :start_date";
        $params[':start_date'] = $start_date;
    }
    
    if (!empty($end_date)) {
        $where_conditions[] = "DATE(sl.created_at) <= :end_date";
        $params[':end_date'] = $end_date;
    }
    
    if (!empty($search)) {
        $where_conditions[] = "(p.product_id LIKE :search OR p.name LIKE :search OR sl.note LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }
    
    if (!empty($user_filter)) {
        $where_conditions[] = "(u.name LIKE :user_filter OR a.fullname LIKE :user_filter)";
        $params[':user_filter'] = '%' . $user_filter . '%';
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // FUNCTION: ดึงข้อมูลการเคลื่อนไหว
    $sql = "
        SELECT 
            sl.log_id,
            sl.product_id,
            p.name as product_name,
            p.lot as product_lot,
            sl.user_id,
            u.name as user_name,
            sl.admin_id,
            a.fullname as admin_name,
            sl.change_type,
            sl.quantity_change,
            sl.quantity_before,
            sl.quantity_after,
            sl.reference_type,
            sl.reference_id,
            sl.created_at,
            sl.note,
            CASE 
                WHEN sl.change_type = 'in' THEN 'รับเข้า'
                WHEN sl.change_type = 'out' THEN 'เบิกออก'
                WHEN sl.change_type = 'adjust' THEN 'ปรับปรุง'
                ELSE sl.change_type
            END as change_type_text,
            CASE 
                WHEN sl.reference_type = 'order' THEN 'การสั่งซื้อ'
                WHEN sl.reference_type = 'cancel' THEN 'ยกเลิกคำสั่งซื้อ'
                WHEN sl.reference_type = 'receive' THEN 'รับสินค้าเข้า'
                WHEN sl.reference_type = 'manual' THEN 'ปรับปรุงแมนนวล'
                ELSE sl.reference_type
            END as reference_type_text
        FROM StockLog sl
        INNER JOIN Product p ON sl.product_id = p.product_id
        LEFT JOIN Users u ON sl.user_id = u.user_id
        LEFT JOIN Admin a ON sl.admin_id = a.admin_id
        $where_clause
        ORDER BY sl.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $movements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // FUNCTION: นับจำนวนรายการทั้งหมด
    $count_sql = "
        SELECT COUNT(*) as total
        FROM StockLog sl
        INNER JOIN Product p ON sl.product_id = p.product_id
        LEFT JOIN Users u ON sl.user_id = u.user_id
        LEFT JOIN Admin a ON sl.admin_id = a.admin_id
        $where_clause
    ";
    
    $count_stmt = $pdo->prepare($count_sql);
    foreach ($params as $key => $value) {
        if ($key !== ':limit' && $key !== ':offset') {
            $count_stmt->bindValue($key, $value);
        }
    }
    $count_stmt->execute();
    $total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    return [
        'success' => true,
        'data' => $movements,
        'pagination' => [
            'current_page' => (int)($offset / $limit) + 1,
            'per_page' => $limit,
            'total_records' => (int)$total_records,
            'total_pages' => ceil($total_records / $limit)
        ]
    ];
}

/**
 * FUNCTION: ดึงสถิติการเคลื่อนไหวของสต็อก
 */
function getStockStats($pdo) {
    $today = date('Y-m-d');
    
    // FUNCTION: นับการเคลื่อนไหวทั้งหมด
    $total_sql = "SELECT COUNT(*) as total FROM StockLog";
    $total_stmt = $pdo->query($total_sql);
    $total_movements = $total_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // FUNCTION: นับการเคลื่อนไหววันนี้
    $today_sql = "SELECT COUNT(*) as total FROM StockLog WHERE DATE(created_at) = :today";
    $today_stmt = $pdo->prepare($today_sql);
    $today_stmt->execute([':today' => $today]);
    $today_movements = $today_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // FUNCTION: นับการรับเข้าวันนี้
    $receive_sql = "SELECT 
                        COUNT(*) as count,
                        SUM(ABS(quantity_change)) as total_qty
                    FROM StockLog 
                    WHERE DATE(created_at) = :today 
                    AND change_type = 'in'";
    $receive_stmt = $pdo->prepare($receive_sql);
    $receive_stmt->execute([':today' => $today]);
    $received_data = $receive_stmt->fetch(PDO::FETCH_ASSOC);
    
    // FUNCTION: นับการเบิกออกวันนี้
    $dispatch_sql = "SELECT 
                        COUNT(*) as count,
                        SUM(ABS(quantity_change)) as total_qty
                    FROM StockLog 
                    WHERE DATE(created_at) = :today 
                    AND change_type = 'out'";
    $dispatch_stmt = $pdo->prepare($dispatch_sql);
    $dispatch_stmt->execute([':today' => $today]);
    $dispatched_data = $dispatch_stmt->fetch(PDO::FETCH_ASSOC);
    
    // FUNCTION: นับการปรับปรุงวันนี้
    $adjust_sql = "SELECT COUNT(*) as total FROM StockLog WHERE DATE(created_at) = :today AND change_type = 'adjust'";
    $adjust_stmt = $pdo->prepare($adjust_sql);
    $adjust_stmt->execute([':today' => $today]);
    $adjusted_today = $adjust_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // FUNCTION: นับสินค้าที่มีสต็อกต่ำ
    $low_stock_sql = "SELECT COUNT(*) as total FROM Product WHERE stock < 10 AND stock > 0";
    $low_stock_stmt = $pdo->query($low_stock_sql);
    $low_stock_count = $low_stock_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // FUNCTION: นับสินค้าหมดสต็อก
    $out_of_stock_sql = "SELECT COUNT(*) as total FROM Product WHERE stock = 0";
    $out_of_stock_stmt = $pdo->query($out_of_stock_sql);
    $out_of_stock_count = $out_of_stock_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    return [
        'success' => true,
        'data' => [
            'total_movements' => (int)$total_movements,
            'today_movements' => (int)$today_movements,
            'received_today' => (int)($received_data['count'] ?? 0),
            'received_qty_today' => (int)($received_data['total_qty'] ?? 0),
            'dispatched_today' => (int)($dispatched_data['count'] ?? 0),
            'dispatched_qty_today' => (int)($dispatched_data['total_qty'] ?? 0),
            'adjusted_today' => (int)$adjusted_today,
            'low_stock_products' => (int)$low_stock_count,
            'out_of_stock_products' => (int)$out_of_stock_count
        ]
    ];
}

/**
 * FUNCTION: ดึงรายการสินค้าสำหรับ dropdown
 */
function getProductsForSelect($pdo) {
    $sql = "
        SELECT 
            product_id,
            name,
            lot,
            stock,
            CONCAT(name, ' (', lot, ') - คงเหลือ: ', stock) as display_name
        FROM Product 
        ORDER BY name ASC
    ";
    
    $stmt = $pdo->query($sql);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'success' => true,
        'data' => $products
    ];
}

// ========================
// POST REQUEST HANDLING
// ========================

// FUNCTION: จัดการ POST requests สำหรับบันทึกข้อมูล
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($input && isset($input['action'])) {
        try {
            switch ($input['action']) {
                case 'save_movement':
                    // FUNCTION: บันทึกการเคลื่อนไหวสต็อกใหม่
                    $result = recordStockMovement($pdo, $input);
                    echo json_encode($result);
                    break;
                    
                case 'add_initial_stock':
                    // FUNCTION: เพิ่มสต็อกเริ่มต้น
                    if (empty($input['product_id']) || empty($input['initial_stock'])) {
                        throw new Exception('ข้อมูลไม่ครบถ้วน');
                    }
                    
                    $stockLogger = new StockLogger($pdo);
                    $result = $stockLogger->addInitialStock(
                        $input['product_id'],
                        (int)$input['initial_stock'],
                        $input['admin_id'] ?? null,
                        $input['note'] ?? 'เพิ่มสต็อกเริ่มต้น'
                    );
                    
                    echo json_encode($result);
                    break;
                    
                default:
                    throw new Exception('Invalid action');
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการบันทึก: ' . $e->getMessage()
            ]);
        }
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ข้อมูลที่ส่งมาไม่ถูกต้อง'
        ]);
    }
}

// ========================
// CORS PREFLIGHT HANDLING
// ========================

// FUNCTION: จัดการ OPTIONS request (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * FUNCTION: บันทึกการเคลื่อนไหวสต็อกใหม่
 */
function recordStockMovement($pdo, $data) {
    $stockLogger = new StockLogger($pdo);
    
    $pdo->beginTransaction();
    
    try {
        // FUNCTION: ตรวจสอบข้อมูลที่จำเป็น
        if (empty($data['product_id']) || empty($data['change_type']) || empty($data['quantity_change'])) {
            throw new Exception('ข้อมูลไม่ครบถ้วน: product_id, change_type, quantity_change จำเป็น');
        }
        
        // FUNCTION: แปลง quantity_change เป็น int
        $quantity_change = (int)$data['quantity_change'];
        if ($quantity_change <= 0) {
            throw new Exception('จำนวนต้องมากกว่า 0');
        }
        
        // FUNCTION: ใช้ StockLogger อัพเดตสต็อก
        $result = $stockLogger->updateProductStock(
            $data['product_id'],
            $data['change_type'],
            $quantity_change,
            $data['reference_type'] ?? 'manual',
            $data['reference_id'] ?? null,
            $data['user_id'] ?? null,
            $data['admin_id'] ?? null,
            $data['note'] ?? null
        );
        
        if (!$result['success']) {
            throw new Exception($result['error']);
        }
        
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'บันทึกการเคลื่อนไหวสต็อกสำเร็จ',
            'log_id' => $result['log_id'],
            'quantity_before' => $result['quantity_before'],
            'quantity_after' => $result['quantity_after']
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

?>