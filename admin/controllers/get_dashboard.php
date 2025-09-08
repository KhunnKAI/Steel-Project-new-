<?php
// Add error reporting at the top for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Add logging to see if file is being accessed
error_log("get_dashboard.php accessed at " . date('Y-m-d H:i:s'));

require_once 'config.php';

// ตรวจสอบการล็อกอิน
requireLogin();

// ตั้งค่า header สำหรับ JSON response
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // รับพารามิเตอร์จาก GET request
    $type = isset($_GET['type']) ? $_GET['type'] : 'overview';
    $period = isset($_GET['period']) ? $_GET['period'] : '7days';
    
    // กำหนดช่วงวันที่ตาม period
    $dateCondition = getDateCondition($period);
    
    switch($type) {
        case 'overview':
            $data = getDashboardOverview($pdo, $dateCondition);
            break;
        case 'sales':
            $data = getSalesData($pdo, $dateCondition);
            break;
        case 'orders':
            $data = getOrdersData($pdo, $dateCondition);
            break;
        case 'recent_activity':
            $data = getRecentActivity($pdo);
            break;
        case 'recent_orders':  // ADD THIS CASE
            $data = getRecentOrders($pdo);
            break;
        case 'top_products':
            $data = getTopProducts($pdo, $dateCondition);
            break;
        default:
            throw new Exception('Invalid data type requested');
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// ฟังก์ชันสร้าง date condition
function getDateCondition($period) {
    switch($period) {
        case '24hours':
            return "DATE(created_at) >= CURDATE()";
        case '7days':
            return "DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        case '30days':
            return "DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        case '3months':
            return "DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
        case '1year':
            return "DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
        default:
            return "DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    }
}

// ฟังก์ชันดึงข้อมูลภาพรวมแดชบอร์ด
function getDashboardOverview($pdo, $dateCondition) {
    // ยอดขายรวม
    $stmt = $pdo->query("
        SELECT COALESCE(SUM(total_amount), 0) as total_sales 
        FROM Orders 
        WHERE status IN ('status02', 'status03', 'status04') 
        AND $dateCondition
    ");
    $totalSales = $stmt->fetch()['total_sales'];
    
    // จำนวนคำสั่งซื้อ
    $stmt = $pdo->query("
        SELECT COUNT(*) as total_orders 
        FROM Orders 
        WHERE $dateCondition
    ");
    $totalOrders = $stmt->fetch()['total_orders'];
    
    // จำนวนสินค้าทั้งหมด
    $stmt = $pdo->query("SELECT COUNT(*) as total_products FROM Product");
    $totalProducts = $stmt->fetch()['total_products'];
    
    // จำนวนผู้ใช้ทั้งหมด
    $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM Users");
    $totalUsers = $stmt->fetch()['total_users'];
    
    // สินค้าที่มีสต็อกต่ำ (น้อยกว่า 10)
    $stmt = $pdo->query("SELECT COUNT(*) as low_stock_count FROM Product WHERE stock < 10");
    $lowStockCount = $stmt->fetch()['low_stock_count'];
    
    // คำสั่งซื้อรอดำเนินการ
    $stmt = $pdo->query("SELECT COUNT(*) as pending_orders FROM Orders WHERE status = 'status01'");
    $pendingOrders = $stmt->fetch()['pending_orders'];
    
    return [
        'total_sales' => floatval($totalSales),
        'total_orders' => intval($totalOrders),
        'total_products' => intval($totalProducts),
        'total_users' => intval($totalUsers),
        'low_stock_count' => intval($lowStockCount),
        'pending_orders' => intval($pendingOrders)
    ];
}

// ฟังก์ชันดึงข้อมูลยอดขาย
function getSalesData($pdo, $dateCondition) {
    // ยอดขายรายวัน 7 วันล่าสุด
    $stmt = $pdo->query("
        SELECT 
            DATE(created_at) as sale_date,
            COALESCE(SUM(total_amount), 0) as daily_sales,
            COUNT(*) as daily_orders
        FROM Orders 
        WHERE status IN ('status02', 'status03', 'status04')
        AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY sale_date ASC
    ");
    $dailySales = $stmt->fetchAll();
    
    // เติมวันที่ขาดหายไป
    $salesData = [];
    for($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $found = false;
        
        foreach($dailySales as $sale) {
            if($sale['sale_date'] === $date) {
                $salesData[] = [
                    'date' => $date,
                    'sales' => floatval($sale['daily_sales']),
                    'orders' => intval($sale['daily_orders']),
                    'day_name' => date('l', strtotime($date))
                ];
                $found = true;
                break;
            }
        }
        
        if(!$found) {
            $salesData[] = [
                'date' => $date,
                'sales' => 0,
                'orders' => 0,
                'day_name' => date('l', strtotime($date))
            ];
        }
    }
    
    return $salesData;
}

// ฟังก์ชันดึงข้อมูลคำสั่งซื้อ
function getOrdersData($pdo, $dateCondition) {
    // สถิติคำสั่งซื้อตามสถานะ
    $stmt = $pdo->query("
        SELECT 
            s.status_code,
            s.description,
            COUNT(o.order_id) as count
        FROM Status s
        LEFT JOIN Orders o ON s.status_id = o.status
        WHERE o.$dateCondition OR o.order_id IS NULL
        GROUP BY s.status_id, s.status_code, s.description
        ORDER BY s.status_id
    ");
    $ordersByStatus = $stmt->fetchAll();
    
    // คำสั่งซื้อล่าสุด
    $stmt = $pdo->query("
        SELECT 
            o.order_id,
            o.total_amount,
            o.created_at,
            u.name as customer_name,
            s.description as status_desc
        FROM Orders o
        JOIN Users u ON o.user_id = u.user_id
        JOIN Status s ON o.status = s.status_id
        ORDER BY o.created_at DESC
        LIMIT 10
    ");
    $recentOrders = $stmt->fetchAll();
    
    return [
        'orders_by_status' => $ordersByStatus,
        'recent_orders' => $recentOrders
    ];
}

// ADD THIS NEW FUNCTION FOR RECENT ORDERS
function getRecentOrders($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                o.order_id,
                o.total_amount,
                o.created_at,
                u.name as customer_name,
                s.status_code,
                s.description as status_desc
            FROM Orders o
            LEFT JOIN Users u ON o.user_id = u.user_id
            LEFT JOIN Status s ON o.status = s.status_id
            ORDER BY o.created_at DESC
            LIMIT 10
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e) {
        error_log("Error in getRecentOrders: " . $e->getMessage());
        return [];
    }
}

// ฟังก์ชันดึงกิจกรรมล่าสุด
function getRecentActivity($pdo) {
    // รวมกิจกรรมจากหลายตาราง
    $activities = [];
    
    // คำสั่งซื้อใหม่
    $stmt = $pdo->query("
        SELECT 
            'new_order' as activity_type,
            CONCAT('คำสั่งซื้อใหม่: ', o.order_id, ' จาก ', u.name) as description,
            o.created_at as activity_time,
            o.total_amount as amount
        FROM Orders o
        JOIN Users u ON o.user_id = u.user_id
        WHERE DATE(o.created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $activities = array_merge($activities, $stmt->fetchAll());
    
    // การชำระเงิน
    $stmt = $pdo->query("
        SELECT 
            'payment' as activity_type,
            CONCAT('ได้รับการชำระเงิน: ', p.payment_id, ' สำหรับคำสั่งซื้อ ', p.order_id) as description,
            p.created_at as activity_time,
            o.total_amount as amount
        FROM Payment p
        JOIN Orders o ON p.order_id = o.order_id
        WHERE DATE(p.created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)
        ORDER BY p.created_at DESC
        LIMIT 5
    ");
    $activities = array_merge($activities, $stmt->fetchAll());
    
    // สินค้าใหม่
    $stmt = $pdo->query("
        SELECT 
            'new_product' as activity_type,
            CONCAT('เพิ่มสินค้าใหม่: ', name) as description,
            created_at as activity_time,
            price as amount
        FROM Product
        WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 3 DAY)
        ORDER BY created_at DESC
        LIMIT 3
    ");
    $activities = array_merge($activities, $stmt->fetchAll());
    
    // เรียงลำดับตามเวลา
    usort($activities, function($a, $b) {
        return strtotime($b['activity_time']) - strtotime($a['activity_time']);
    });
    
    return array_slice($activities, 0, 10);
}

// ฟังก์ชันดึงสินค้ายอดนิยม
function getTopProducts($pdo, $dateCondition) {
    $stmt = $pdo->query("
        SELECT 
            p.product_id,
            p.name,
            p.price,
            p.stock,
            SUM(oi.quantity) as total_sold,
            SUM(oi.quantity * oi.price_each) as total_revenue,
            c.name as category_name
        FROM Product p
        JOIN OrderItem oi ON p.product_id = oi.product_id
        JOIN Orders o ON oi.order_id = o.order_id
        LEFT JOIN Category c ON p.category_id = c.category_id
        WHERE o.$dateCondition 
        AND o.status IN ('status02', 'status03', 'status04')
        GROUP BY p.product_id, p.name, p.price, p.stock, c.name
        ORDER BY total_sold DESC
        LIMIT 10
    ");
    
    return $stmt->fetchAll();
}

// ฟังก์ชันดึงข้อมูลสต็อก
function getStockData($pdo) {
    // สินค้าที่มีสต็อกต่ำ
    $stmt = $pdo->query("
        SELECT 
            p.product_id,
            p.name,
            p.stock,
            p.price,
            c.name as category_name
        FROM Product p
        LEFT JOIN Category c ON p.category_id = c.category_id
        WHERE p.stock < 10
        ORDER BY p.stock ASC
        LIMIT 10
    ");
    $lowStockProducts = $stmt->fetchAll();
    
    // สถิติสต็อกตามหมวดหมู่
    $stmt = $pdo->query("
        SELECT 
            c.name as category_name,
            COUNT(p.product_id) as product_count,
            SUM(p.stock) as total_stock,
            AVG(p.stock) as avg_stock
        FROM Category c
        LEFT JOIN Product p ON c.category_id = p.category_id
        GROUP BY c.category_id, c.name
        ORDER BY total_stock DESC
    ");
    $stockByCategory = $stmt->fetchAll();
    
    return [
        'low_stock_products' => $lowStockProducts,
        'stock_by_category' => $stockByCategory
    ];
}
?>