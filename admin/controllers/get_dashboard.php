<?php
// ========================
// GET DASHBOARD DATA - API ENDPOINT
// ========================

error_reporting(0);
ini_set('display_errors', 0);

require_once 'config.php';
requireLogin();

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // ========================
    // GET REQUEST PARAMETERS
    // ========================
    $type = isset($_GET['type']) ? $_GET['type'] : 'overview';
    $period = '7days'; // บังคับให้เป็น 7 วันเสมอ

    // ========================
    // FUNCTION: สร้าง Date Condition
    // ========================
    function getDateCondition($period) {
        return "DATE(o.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    }

    $dateCondition = getDateCondition($period);

    // ========================
    // ROUTE REQUEST TYPE
    // ========================
    switch ($type) {
        case 'overview':
            $data = getDashboardOverview($pdo, $dateCondition);
            break;
        case 'sales':
            $data = getSalesData($pdo, $dateCondition, $period);
            break;
        case 'recent_activity':
            $data = getRecentActivity($pdo, $dateCondition);
            break;
        case 'recent_orders':
            $data = getRecentOrders($pdo);
            break;
        default:
            throw new Exception('Invalid data type requested');
    }

    echo json_encode([
        'success' => true,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s'),
        'period_used' => $period
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// ========================
// FUNCTION: ดึงข้อมูลภาพรวม
// ========================
function getDashboardOverview($pdo, $dateCondition) {
    try {
        // ยอดขายรวม (7 วัน)
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(o.total_amount), 0) as total_sales
            FROM Orders o
            JOIN Status s ON o.status = s.status_id
            WHERE s.status_code IN ('awaiting_shipment', 'in_transit', 'delivered')
            AND $dateCondition
        ");
        $stmt->execute();
        $totalSales = $stmt->fetch()['total_sales'];

        // จำนวนคำสั่งซื้อ (7 วัน)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total_orders
            FROM Orders o
            WHERE o.status NOT IN ('status05')
            AND $dateCondition
        ");
        $stmt->execute();
        $totalOrders = $stmt->fetch()['total_orders'];

        // จำนวนสินค้าทั้งหมด
        $stmt = $pdo->query("SELECT COUNT(*) as total_products FROM Product");
        $totalProducts = $stmt->fetch()['total_products'];

        // จำนวนผู้ใช้ทั้งหมด
        $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM Users");
        $totalUsers = $stmt->fetch()['total_users'];

        // สินค้าที่มีสต็อกต่ำ (< 10)
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
    } catch (Exception $e) {
        error_log('Error in getDashboardOverview: ' . $e->getMessage());
        return [];
    }
}

// ========================
// FUNCTION: ดึงข้อมูลยอดขาย
// ========================
function getSalesData($pdo, $dateCondition, $period) {
    try {
        // ดึงข้อมูลรายวัน
        $stmt = $pdo->prepare("
            SELECT
                DATE(o.created_at) as sale_date,
                COALESCE(SUM(CASE
                    WHEN s.status_code IN ('delivered', 'awaiting_shipment', 'in_transit')
                    THEN o.total_amount
                    ELSE 0
                END), 0) as daily_sales,
                COUNT(o.order_id) as daily_orders
            FROM Orders o
            JOIN Status s ON o.status = s.status_id
            WHERE $dateCondition
            GROUP BY DATE(o.created_at)
            ORDER BY sale_date ASC
        ");
        $stmt->execute();
        $dailySales = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // เติมวันที่ขาดหายไป
        $salesData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $found = false;

            foreach ($dailySales as $sale) {
                if ($sale['sale_date'] === $date) {
                    $salesData[] = [
                        'date' => $date,
                        'sales' => floatval($sale['daily_sales']),
                        'orders' => intval($sale['daily_orders'])
                    ];
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $salesData[] = [
                    'date' => $date,
                    'sales' => 0,
                    'orders' => 0
                ];
            }
        }

        return $salesData;
    } catch (Exception $e) {
        error_log('Error in getSalesData: ' . $e->getMessage());
        return [];
    }
}

// ========================
// FUNCTION: ดึงกิจกรรมล่าสุด
// ========================
function getRecentActivity($pdo, $dateCondition) {
    try {
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
            WHERE $dateCondition
            ORDER BY o.created_at DESC
            LIMIT 5
        ");
        $stmt->execute();
        $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));

        // จัดส่งสำเร็จ
        $updateDateCondition = str_replace('o.created_at', 'o.updated_at', $dateCondition);
        $stmt = $pdo->prepare("
            SELECT
                'delivery' as activity_type,
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

        // สินค้าใหม่
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
        usort($activities, function ($a, $b) {
            return strtotime($b['activity_time']) - strtotime($a['activity_time']);
        });

        return array_slice($activities, 0, 10);
    } catch (Exception $e) {
        error_log('Error in getRecentActivity: ' . $e->getMessage());
        return [];
    }
}

// ========================
// FUNCTION: ดึงคำสั่งซื้อล่าสุด
// ========================
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
    } catch (Exception $e) {
        error_log('Error in getRecentOrders: ' . $e->getMessage());
        return [];
    }
}
?>