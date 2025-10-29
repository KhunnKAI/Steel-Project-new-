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
    
    // *** แก้ไข: บังคับให้ period เป็น '7days' เสมอ เพื่อจำกัดข้อมูลทั้งหมด ***
    $period = '7days'; 
    // $period = isset($_GET['period']) ? $_GET['period'] : '7days'; // โค้ดเดิมถูกยกเลิก
    
    // กำหนดช่วงวันที่ตาม period
    $dateCondition = getDateCondition($period);
    
    switch($type) {
        case 'overview':
            // ข้อมูลภาพรวม (บางส่วนไม่ขึ้นกับช่วงเวลา, แต่ยอดขาย/คำสั่งซื้อใช้ $dateCondition)
            $data = getDashboardOverview($pdo, $dateCondition);
            break;
        case 'sales':
            // ข้อมูลยอดขาย (ใช้เงื่อนไขวันที่ 7 วัน)
            $data = getSalesData($pdo, $dateCondition, $period);
            break;
        case 'orders':
            // ข้อมูลคำสั่งซื้อ (ใช้เงื่อนไขวันที่ 7 วัน)
            $data = getOrdersData($pdo, $dateCondition);
            break;
        case 'recent_activity':
            // กิจกรรมล่าสุด (ใช้เงื่อนไขวันที่ 7 วัน)
            $data = getRecentActivity($pdo, $dateCondition);
            break;
        case 'recent_orders':
            // คำสั่งซื้อล่าสุด (ดึง 10 รายการล่าสุด ไม่ขึ้นกับช่วงเวลา - เก็บไว้ตามเดิม)
            $data = getRecentOrders($pdo);
            break;
        case 'top_products':
            // สินค้ายอดนิยม (ใช้เงื่อนไขวันที่ 7 วัน)
            $data = getTopProducts($pdo, $dateCondition);
            break;
        case 'stock':
            // ข้อมูลสต็อก (ไม่ขึ้นกับช่วงเวลา)
            $data = getStockData($pdo, $dateCondition);
            break;
        default:
            throw new Exception('Invalid data type requested');
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s'),
        'period_used' => $period // แสดงช่วงเวลาที่ใช้ (7days)
    ]);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// *** แก้ไข: ฟังก์ชันสร้าง date condition ให้ส่งคืนเงื่อนไข 7 วันเท่านั้น ***
function getDateCondition($period) {
    // บังคับให้ใช้เงื่อนไข 7 วันเสมอ โดยไม่สนใจค่า $period ที่ส่งเข้ามา
    return "DATE(o.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
}

// ฟังก์ชันดึงข้อมูลภาพรวมแดชบอร์ด (ไม่มีการเปลี่ยนแปลง)
function getDashboardOverview($pdo, $dateCondition) {
    // ยอดขายรวมตามช่วงเวลาที่กำหนด (ตอนนี้ถูกจำกัดที่ 7 วัน)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(o.total_amount), 0) as total_sales 
        FROM Orders o
        JOIN Status s ON o.status = s.status_id
        WHERE s.status_code IN ('awaiting_shipment', 'in_transit', 'delivered')
        AND $dateCondition
    ");
    $stmt->execute();
    $totalSales = $stmt->fetch()['total_sales'];
    
    // จำนวนคำสั่งซื้อตามช่วงเวลาที่กำหนด (ตอนนี้ถูกจำกัดที่ 7 วัน)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_orders 
        FROM Orders o
        WHERE o.status NOT IN ('status05')
        AND $dateCondition 
    ");
    $stmt->execute();
    $totalOrders = $stmt->fetch()['total_orders'];
    
    // จำนวนสินค้าทั้งหมด (ไม่ขึ้นกับช่วงเวลา)
    $stmt = $pdo->query("SELECT COUNT(*) as total_products FROM Product");
    $totalProducts = $stmt->fetch()['total_products'];
    
    // จำนวนผู้ใช้ทั้งหมด (ไม่ขึ้นกับช่วงเวลา)
    $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM Users");
    $totalUsers = $stmt->fetch()['total_users'];
    
    // สินค้าที่มีสต็อกต่ำ (น้อยกว่า 10) - ไม่ขึ้นกับช่วงเวลา
    $stmt = $pdo->query("SELECT COUNT(*) as low_stock_count FROM Product WHERE stock < 10");
    $lowStockCount = $stmt->fetch()['low_stock_count'];
    
    // คำสั่งซื้อรอดำเนินการ - ไม่ขึ้นกับช่วงเวลา (สถานะปัจจุบัน)
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

// ฟังก์ชันดึงข้อมูลยอดขาย (ไม่มีการเปลี่ยนแปลงการทำงาน แต่ใช้ $dateCondition 7 วัน)
function getSalesData($pdo, $dateCondition, $period) {
    error_log("Checking sales data with date condition: $dateCondition");
    
    // ปรับปรุง query ให้ใช้ $dateCondition แทนการกำหนด 7 วันตายตัว
    $stmt = $pdo->prepare("
        SELECT 
            DATE(o.created_at) as sale_date,
            COALESCE(SUM(CASE 
                WHEN s.status_code IN ('delivered', 'awaiting_shipment', 'in_transit') 
                THEN o.total_amount 
                ELSE 0 
            END), 0) as daily_sales,
            COUNT(o.order_id) as daily_orders,
            COUNT(CASE 
                WHEN s.status_code IN ('delivered', 'awaiting_shipment', 'in_transit') 
                THEN 1 
            END) as completed_orders
        FROM Orders o
        JOIN Status s ON o.status = s.status_id
        WHERE $dateCondition 
        GROUP BY DATE(o.created_at)
        ORDER BY sale_date ASC
    ");
    $stmt->execute();
    $dailySales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Raw sales data: " . json_encode($dailySales));
    
    // การเติมวันที่ขาดหายไปจะทำได้ง่ายสำหรับ 7 วันเท่านั้น 
    // เนื่องจาก $period ถูกบังคับเป็น '7days' เสมอ ส่วนนี้จึงทำงานได้อย่างถูกต้อง
    if ($period === '7days') {
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
        $dailySales = $salesData;
    }

    // แปลงค่าให้เป็นชนิดที่ถูกต้อง (ส่วนนี้ถูกเรียกใช้เฉพาะเมื่อ $period ไม่ใช่ 7days ซึ่งตอนนี้จะไม่เกิดขึ้น)
    if ($period !== '7days') {
        foreach ($dailySales as &$sale) {
            $sale['sales'] = floatval($sale['daily_sales']);
            unset($sale['daily_sales']);
            $sale['orders'] = intval($sale['daily_orders']);
            unset($sale['daily_orders']);
            $sale['completed_orders'] = intval($sale['completed_orders']);
            $sale['day_name'] = date('l', strtotime($sale['sale_date']));
            $sale['date'] = $sale['sale_date'];
            unset($sale['sale_date']);
        }
    }
    
    error_log("Final sales data: " . json_encode($dailySales));
    return $dailySales;
}

// ฟังก์ชันดึงข้อมูลคำสั่งซื้อ (ไม่มีการเปลี่ยนแปลง)
function getOrdersData($pdo, $dateCondition) {
    // สถิติคำสั่งซื้อตามสถานะ - ใช้ $dateCondition ในการ JOIN (ตอนนี้ถูกจำกัดที่ 7 วัน)
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
    
    // คำสั่งซื้อล่าสุด (ยังคงดึง 10 รายการล่าสุด ไม่ขึ้นกับช่วงเวลา - เก็บไว้ตามเดิม)
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

// Recent orders function (ไม่มีการเปลี่ยนแปลง)
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

// ฟังก์ชันดึงกิจกรรมล่าสุด (ไม่มีการเปลี่ยนแปลงการทำงาน แต่ใช้ $dateCondition 7 วัน)
function getRecentActivity($pdo, $dateCondition) {
    $activities = [];
    
    // คำสั่งซื้อใหม่ - ใช้ $dateCondition (ตอนนี้ถูกจำกัดที่ 7 วัน)
    $stmt = $pdo->prepare("
        SELECT 
            'new_order' as activity_type,
            CONCAT('คำสั่งซื้อใหม่: ', o.order_id, ' จาก ', COALESCE(u.name, 'ลูกค้า')) as description,
            o.created_at as activity_time,
            o.total_amount as amount
        FROM Orders o
        LEFT JOIN Users u ON o.user_id = u.user_id
        WHERE $dateCondition 
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
    
    // การชำระเงิน/สถานะเปลี่ยนเป็นจัดส่งสำเร็จ (ตอนนี้ถูกจำกัดที่ 7 วัน)
    // NOTE: $dateCondition ถูกสร้างจาก o.created_at เราจึงเปลี่ยนเป็น o.updated_at สำหรับการอัปเดต
    $updateDateCondition = str_replace('o.created_at', 'o.updated_at', $dateCondition);

    $stmt = $pdo->prepare("
        SELECT 
            'payment' as activity_type,
            CONCAT('จัดส่งสำเร็จ: คำสั่งซื้อ ', o.order_id) as description,
            o.updated_at as activity_time,
            o.total_amount as amount
        FROM Orders o
        JOIN Status s ON o.status = s.status_id
        WHERE s.status_code = 'delivered'
        AND $updateDateCondition
        ORDER BY o.updated_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
    
    // สินค้าใหม่ (ตอนนี้ถูกจำกัดที่ 7 วัน)
    $productDateCondition = str_replace('o.created_at', 'created_at', $dateCondition);
    
    $stmt = $pdo->prepare("
        SELECT 
            'new_product' as activity_type,
            CONCAT('เพิ่มสินค้าใหม่: ', name) as description,
            created_at as activity_time,
            price as amount
        FROM Product
        WHERE $productDateCondition
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

// ฟังก์ชันดึงสินค้ายอดนิยม (ไม่มีการเปลี่ยนแปลงการทำงาน แต่ใช้ $dateCondition 7 วัน)
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
        AND s.status_code IN ('delivered', 'awaiting_shipment', 'in_transit')
        GROUP BY p.product_id, p.name, p.price, p.stock, c.name
        ORDER BY total_sold DESC
        LIMIT 10
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ฟังก์ชันดึงข้อมูลสต็อก (ไม่มีการเปลี่ยนแปลง)
function getStockData($pdo, $dateCondition) {
    // สินค้าที่มีสต็อกต่ำ (ไม่ขึ้นกับช่วงเวลา)
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
    
    // สถิติสต็อกตามหมวดหมู่ (ไม่ขึ้นกับช่วงเวลา)
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

?>