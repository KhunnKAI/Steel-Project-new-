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
        case 'recent_orders':
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
            return "DATE(o.created_at) >= CURDATE()";
        case '7days':
            return "DATE(o.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        case '30days':
            return "DATE(o.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        case '3months':
            return "DATE(o.created_at) >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
        case '1year':
            return "DATE(o.created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
        default:
            return "DATE(o.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    }
}

// ฟังก์ชันดึงข้อมูลภาพรวมแดชบอร์ด
function getDashboardOverview($pdo, $dateCondition) {
    // ยอดขายรวม - Only count completed orders (delivered/shipped)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(o.total_amount), 0) as total_sales 
        FROM Orders o
        JOIN Status s ON o.status = s.status_id
        WHERE s.status_code IN ('awaiting_shipment', 'in_transit', 'delivered')
    ");

    $stmt->execute();
    $totalSales = $stmt->fetch()['total_sales'];
    
    // จำนวนคำสั่งซื้อทั้งหมด (รวมทุกสถานะ)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_orders 
        FROM Orders o
    ");
    $stmt->execute();
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
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as pending_orders 
        FROM Orders o
        JOIN Status s ON o.status = s.status_id
        WHERE s.status_code = 'pending_payment'
    ");
    $stmt->execute();
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

// ฟังก์ชันดึงข้อมูลยอดขาย - FIXED VERSION
function getSalesData($pdo, $dateCondition) {
    error_log("Checking sales data with date condition: $dateCondition");
    
    // Fixed query - separate sales (completed orders) and order counts (all orders)
    $stmt = $pdo->prepare("
        SELECT 
            DATE(o.created_at) as sale_date,
            COALESCE(SUM(CASE 
                WHEN s.status_code IN ('delivered', 'awaiting_shipment') 
                THEN o.total_amount 
                ELSE 0 
            END), 0) as daily_sales,
            COUNT(o.order_id) as daily_orders,
            COUNT(CASE 
                WHEN s.status_code IN ('delivered', 'awaiting_shipment') 
                THEN 1 
            END) as completed_orders
        FROM Orders o
        JOIN Status s ON o.status = s.status_id
        WHERE DATE(o.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(o.created_at)
        ORDER BY sale_date ASC
    ");
    $stmt->execute();
    $dailySales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Raw sales data: " . json_encode($dailySales));
    
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
                    'completed_orders' => intval($sale['completed_orders']),
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
                'completed_orders' => 0,
                'day_name' => date('l', strtotime($date))
            ];
        }
    }
    
    error_log("Final sales data: " . json_encode($salesData));
    return $salesData;
}

// ฟังก์ชันดึงข้อมูลคำสั่งซื้อ
function getOrdersData($pdo, $dateCondition) {
    // สถิติคำสั่งซื้อตามสถานะ
    $stmt = $pdo->prepare("
        SELECT 
            s.status_code,
            s.description,
            COUNT(o.order_id) as count
        FROM Status s
        LEFT JOIN Orders o ON s.status_id = o.status AND $dateCondition
        GROUP BY s.status_id, s.status_code, s.description
        ORDER BY s.status_id
    ");
    $stmt->execute();
    $ordersByStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // คำสั่งซื้อล่าสุด
    $stmt = $pdo->prepare("
        SELECT 
            o.order_id,
            o.total_amount,
            o.created_at,
            u.name as customer_name,
            s.description as status_desc
        FROM Orders o
        LEFT JOIN Users u ON o.user_id = u.user_id
        JOIN Status s ON o.status = s.status_id
        ORDER BY o.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'orders_by_status' => $ordersByStatus,
        'recent_orders' => $recentOrders
    ];
}

// Recent orders function - corrected
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
            JOIN Status s ON o.status = s.status_id
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
    $activities = [];
    
    // คำสั่งซื้อใหม่
    $stmt = $pdo->prepare("
        SELECT 
            'new_order' as activity_type,
            CONCAT('คำสั่งซื้อใหม่: ', o.order_id, ' จาก ', COALESCE(u.name, 'ลูกค้า')) as description,
            o.created_at as activity_time,
            o.total_amount as amount
        FROM Orders o
        LEFT JOIN Users u ON o.user_id = u.user_id
        WHERE DATE(o.created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
    
    // การชำระเงิน/สถานะเปลี่ยนเป็นจัดส่งสำเร็จ
    $stmt = $pdo->prepare("
        SELECT 
            'payment' as activity_type,
            CONCAT('จัดส่งสำเร็จ: คำสั่งซื้อ ', o.order_id) as description,
            o.updated_at as activity_time,
            o.total_amount as amount
        FROM Orders o
        JOIN Status s ON o.status = s.status_id
        WHERE s.status_code = 'delivered'
        AND DATE(o.updated_at) >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)
        ORDER BY o.updated_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
    
    // สินค้าใหม่
    $stmt = $pdo->prepare("
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
    $stmt->execute();
    $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
    
    // เรียงลำดับตามเวลา
    usort($activities, function($a, $b) {
        return strtotime($b['activity_time']) - strtotime($a['activity_time']);
    });
    
    return array_slice($activities, 0, 10);
}

// ฟังก์ชันดึงสินค้ายอดนิยม
function getTopProducts($pdo, $dateCondition) {
    $stmt = $pdo->prepare("
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
        JOIN Status s ON o.status = s.status_id
        LEFT JOIN Category c ON p.category_id = c.category_id
        WHERE $dateCondition 
        AND s.status_code IN ('delivered', 'awaiting_shipment')
        GROUP BY p.product_id, p.name, p.price, p.stock, c.name
        ORDER BY total_sold DESC
        LIMIT 10
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ฟังก์ชันดึงข้อมูลสต็อก
function getStockData($pdo) {
    // สินค้าที่มีสต็อกต่ำ
    $stmt = $pdo->prepare("
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
    $stmt->execute();
    $lowStockProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // สถิติสต็อกตามหมวดหมู่
    $stmt = $pdo->prepare("
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
    $stmt->execute();
    $stockByCategory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'low_stock_products' => $lowStockProducts,
        'stock_by_category' => $stockByCategory
    ];
}

// Additional function to debug order statuses
function getOrderStatusDebug($pdo) {
    try {
        // Get all orders with their statuses for debugging
        $stmt = $pdo->prepare("
            SELECT 
                o.order_id,
                o.total_amount,
                o.created_at,
                s.status_code,
                s.description as status_desc
            FROM Orders o
            JOIN Status s ON o.status = s.status_id
            WHERE DATE(o.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            ORDER BY o.created_at DESC
        ");
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Debug - All recent orders: " . json_encode($orders));
        
        return $orders;
    } catch(Exception $e) {
        error_log("Error in getOrderStatusDebug: " . $e->getMessage());
        return [];
    }
}
?>