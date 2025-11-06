<?php
// ========================
// SECURITY & CONFIGURATION
// ========================

// FUNCTION: โหลดการตั้งค่าและตรวจสอบการเข้าสู่ระบบ
require_once 'config.php';
requireLogin();

// FUNCTION: ตั้งค่า Header เป็น JSON
header('Content-Type: application/json; charset=utf-8');

// ========================
// MAIN HANDLER
// ========================

// FUNCTION: ประมวลผลคำขอรายงาน (Route report_type ไปยังฟังก์ชันที่เหมาะสม)
try {
    $report_type = $_GET['type'] ?? '';
    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date = $_GET['end_date'] ?? date('Y-m-d');
    $category = $_GET['category'] ?? 'all';
    
    switch ($report_type) {
        case 'sales_summary':
            echo json_encode(getSalesSummary($pdo, $start_date, $end_date, $category));
            break;
            
        case 'sales_by_product':
            echo json_encode(getSalesByProduct($pdo, $start_date, $end_date, $category));
            break;
            
        case 'top_products':
            echo json_encode(getTopProducts($pdo, $start_date, $end_date, $category));
            break;
            
        case 'stock_summary':
            echo json_encode(getStockSummary($pdo, $category));
            break;
            
        case 'stock_movement':
            echo json_encode(getStockMovement($pdo, $start_date, $end_date));
            break;
            
        case 'reorder_point':
            echo json_encode(getReorderPoint($pdo, $category));
            break;
            
        case 'stock_value':
            echo json_encode(getStockValue($pdo, $category));
            break;
            
        case 'shipping_summary':
            echo json_encode(getShippingSummary($pdo, $start_date, $end_date));
            break;
            
        case 'shipping_by_zone':
            echo json_encode(getShippingByZone($pdo, $start_date, $end_date));
            break;
            
        case 'customer_summary':
            echo json_encode(getCustomerSummary($pdo, $start_date, $end_date));
            break;
            
        case 'top_customers':
            $limit = intval($_GET['limit'] ?? 10);
            echo json_encode(getTopCustomers($pdo, $start_date, $end_date, $limit));
            break;
            
        default:
            echo json_encode(['error' => 'Invalid report type']);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

// ========================
// SALES REPORTS
// ========================

// FUNCTION: ดึงข้อมูลสรุปการขาย (ยอดขาย, ค่าเฉลี่ย, ลูกค้าใหม่, อัตราการเติบโต)
function getSalesSummary($pdo, $start_date, $end_date, $category = 'all') {
    $date_end_param = $end_date . ' 23:59:59';
    $params = [$start_date, $date_end_param];

    // --- Query: ยอดขายและค่าเฉลี่ย (งวดปัจจุบัน) ---
    if ($category !== 'all') {
        $sales_sql = "
            SELECT
                COALESCE(SUM(sub.total_amount), 0) as total_sales,
                COALESCE(SUM(sub.total_novat), 0) as total_sales_no_vat,
                COALESCE(SUM(sub.shipping_fee), 0) as total_shipping,
                COALESCE(AVG(sub.total_amount), 0) as avg_order_value,
                COUNT(DISTINCT sub.user_id) as unique_customers
            FROM (
                SELECT 
                    o.order_id, 
                    o.total_amount, 
                    o.total_novat, 
                    o.shipping_fee, 
                    o.user_id 
                FROM Orders o
                INNER JOIN OrderItem oi ON o.order_id = oi.order_id
                INNER JOIN Product p ON oi.product_id = p.product_id
                WHERE o.created_at BETWEEN ? AND ? 
                AND o.status NOT IN ('status05','status01')
                AND p.category_id = ?
                GROUP BY o.order_id 
            ) sub
        ";
        $sales_params = [$start_date, $date_end_param, $category];
    } else {
        $sales_sql = "
            SELECT 
                COALESCE(SUM(o.total_amount), 0) as total_sales,
                COALESCE(SUM(o.total_novat), 0) as total_sales_no_vat,
                COALESCE(SUM(o.shipping_fee), 0) as total_shipping,
                COALESCE(AVG(o.total_amount), 0) as avg_order_value,
                COUNT(DISTINCT o.user_id) as unique_customers
            FROM Orders o
            WHERE o.created_at BETWEEN ? AND ? 
            AND o.status NOT IN ('status05','status01')
        ";
        $sales_params = $params;
    }

    $sales_stmt = $pdo->prepare($sales_sql);
    $sales_stmt->execute($sales_params);
    $sales_result = $sales_stmt->fetch(PDO::FETCH_ASSOC);
    
    // --- Query: จำนวนคำสั่งซื้อทั้งหมด (งวดปัจจุบัน) ---
    if ($category !== 'all') {
        $orders_sql = "
            SELECT COUNT(DISTINCT o.order_id) as total_orders
            FROM Orders o
            INNER JOIN OrderItem oi ON o.order_id = oi.order_id
            INNER JOIN Product p ON oi.product_id = p.product_id
            WHERE o.created_at BETWEEN ? AND ? 
            AND o.status NOT IN ('status05')
            AND p.category_id = ?
        ";
        $orders_params = [$start_date, $date_end_param, $category];
    } else {
        $orders_sql = "
            SELECT COUNT(DISTINCT o.order_id) as total_orders
            FROM Orders o
            WHERE o.created_at BETWEEN ? AND ? 
            AND o.status NOT IN ('status05')
        ";
        $orders_params = $params;
    }
    
    $orders_stmt = $pdo->prepare($orders_sql);
    $orders_stmt->execute($orders_params);
    $orders_result = $orders_stmt->fetch(PDO::FETCH_ASSOC);

    // --- คำนวณ: อัตราการเติบโต (Growth Rate) ---
    $date1 = new DateTime($start_date);
    $date2 = new DateTime($end_date);
    $interval = $date1->diff($date2);
    $days = $interval->days;
    
    $prev_end_date = date('Y-m-d', strtotime($start_date . ' -1 day'));
    $prev_start_date = date('Y-m-d', strtotime($prev_end_date . " -$days days"));
    
    // --- Query: ยอดขายช่วงอ่านหน้า (ระยะเวลาเดียวกัน) ---
    if ($category !== 'all') {
        $prev_sales_sql = "
            SELECT COALESCE(SUM(sub.total_amount), 0) as prev_total_sales
            FROM (
                SELECT o.order_id, o.total_amount
                FROM Orders o
                INNER JOIN OrderItem oi ON o.order_id = oi.order_id
                INNER JOIN Product p ON oi.product_id = p.product_id
                WHERE o.created_at BETWEEN ? AND ?
                AND o.status NOT IN ('status05','status01')
                AND p.category_id = ?
                GROUP BY o.order_id
            ) sub
        ";
        $prev_params = [$prev_start_date, $prev_end_date . ' 23:59:59', $category];
    } else {
        $prev_sales_sql = "
            SELECT COALESCE(SUM(o.total_amount), 0) as prev_total_sales
            FROM Orders o
            WHERE o.created_at BETWEEN ? AND ?
            AND o.status NOT IN ('status05','status01')
        ";
        $prev_params = [$prev_start_date, $prev_end_date . ' 23:59:59'];
    }
    
    $prev_stmt = $pdo->prepare($prev_sales_sql);
    $prev_stmt->execute($prev_params);
    $prev_result = $prev_stmt->fetch(PDO::FETCH_ASSOC);
    
    // --- คำนวณ: Growth Rate ---
    $current_sales = $sales_result['total_sales'] ?? 0;
    $previous_sales = $prev_result['prev_total_sales'] ?? 0;
    
    $growth_rate = 0;
    if ($previous_sales > 0) {
        $growth_rate = round((($current_sales - $previous_sales) / $previous_sales) * 100, 2);
    } elseif ($current_sales > 0) {
        $growth_rate = 100;
    }

    // --- รวมและส่งคืนผลลัพธ์ ---
    if ($sales_result && $orders_result) {
        return array_merge($orders_result, $sales_result, ['growth_rate' => $growth_rate]);
    }

    return [
        'total_orders' => 0,
        'total_sales' => 0,
        'total_sales_no_vat' => 0,
        'total_shipping' => 0,
        'avg_order_value' => 0,
        'unique_customers' => 0,
        'growth_rate' => 0
    ];
}

// FUNCTION: ดึงข้อมูลการขายตามสินค้า (ปริมาณ, ยอดขาย, ราคาเฉลี่ย)
function getSalesByProduct($pdo, $start_date, $end_date, $category = 'all') {
    $sql = "
        SELECT 
            p.product_id,
            p.name as product_name,
            p.category_id,
            c.name as category_name,
            SUM(oi.quantity) as total_quantity,
            COALESCE(SUM(oi.quantity * oi.price_each), 0) as total_sales,
            COUNT(DISTINCT oi.order_id) as order_count,
            COALESCE(AVG(oi.price_each), 0) as avg_price
        FROM OrderItem oi
        INNER JOIN Orders o ON oi.order_id = o.order_id
        INNER JOIN Product p ON oi.product_id = p.product_id
        LEFT JOIN Category c ON p.category_id = c.category_id
        WHERE o.created_at BETWEEN ? AND ? 
        AND o.status NOT IN ('status05','status01')
    ";
    
    $params = [$start_date, $end_date . ' 23:59:59'];
    
    if ($category !== 'all') {
        $sql .= " AND p.category_id = ?";
        $params[] = $category;
    }
    
    $sql .= "
        GROUP BY p.product_id, p.name, p.category_id, c.name
        ORDER BY total_sales DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// FUNCTION: ดึงข้อมูล 20 สินค้าขายดี (ปริมาณขาย, รายได้, ความถี่การสั่งซื้อ)
function getTopProducts($pdo, $start_date, $end_date, $category = 'all') {
    $sql = "
        SELECT 
            p.product_id,
            p.name as product_name,
            p.category_id,
            c.name as category_name,
            SUM(oi.quantity) as total_sold,
            COALESCE(SUM(oi.quantity * oi.price_each), 0) as total_revenue,
            COUNT(DISTINCT oi.order_id) as order_frequency,
            p.stock as current_stock
        FROM OrderItem oi
        INNER JOIN Orders o ON oi.order_id = o.order_id
        INNER JOIN Product p ON oi.product_id = p.product_id
        LEFT JOIN Category c ON p.category_id = c.category_id
        WHERE o.created_at BETWEEN ? AND ? 
        AND o.status NOT IN ('status05')
    ";
    
    $params = [$start_date, $end_date . ' 23:59:59'];
    
    if ($category !== 'all') {
        $sql .= " AND p.category_id = ?";
        $params[] = $category;
    }
    
    $sql .= "
        GROUP BY p.product_id, p.name, p.category_id, c.name, p.stock
        ORDER BY total_sold DESC
        LIMIT 20
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// ========================
// STOCK REPORTS
// ========================

// FUNCTION: ดึงข้อมูลสรุปสต็อก (ปริมาณ, ราคา, วันที่รับเข้า, วันที่ยังคงมี)
function getStockSummary($pdo, $category = 'all') {
    $sql = "
        SELECT 
            p.product_id,
            p.name as product_name,
            p.category_id,
            c.name as category_name,
            s.name as supplier_name,
            p.stock as current_stock,
            p.price,
            (p.stock * p.price) as stock_value,
            p.lot,
            p.received_date,
            DATEDIFF(NOW(), p.received_date) as days_in_stock
        FROM Product p
        LEFT JOIN Category c ON p.category_id = c.category_id
        LEFT JOIN Supplier s ON p.supplier_id = s.supplier_id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($category !== 'all') {
        $sql .= " AND p.category_id = ?";
        $params[] = $category;
    }
    
    $sql .= " ORDER BY stock_value DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// FUNCTION: ดึงข้อมูลการเคลื่อนไหวสต็อก (ปรับปรุง, ลดลง, หมายเหตุ)
function getStockMovement($pdo, $start_date, $end_date) {
    $sql = "
        SELECT 
            sl.log_id,
            sl.product_id,
            p.name as product_name,
            sl.change_type,
            sl.quantity_change,
            sl.quantity_before,
            sl.quantity_after,
            sl.reference_type,
            sl.reference_id,
            COALESCE(a.fullname, u.name) as admin_name,
            sl.created_at,
            sl.note
        FROM StockLog sl
        LEFT JOIN Product p ON sl.product_id = p.product_id
        LEFT JOIN Admin a ON sl.admin_id = a.admin_id
        LEFT JOIN Users u ON sl.user_id = u.user_id
        WHERE sl.created_at BETWEEN ? AND ?
        ORDER BY sl.created_at DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$start_date, $end_date . ' 23:59:59']);
    return $stmt->fetchAll();
}

// FUNCTION: ดึงข้อมูลจุดสั่งซื้อใหม่ (สต็อกคงเหลือ, การขายเฉลี่ยรายวัน, สถานะเร่งด่วน)
function getReorderPoint($pdo, $category = 'all') {
    $sql = "
        SELECT 
            p.product_id,
            p.name as product_name,
            p.category_id,
            c.name as category_name,
            p.stock as current_stock,
            p.price,
            COALESCE(sales_data.avg_daily_sales, 0) as avg_daily_sales,
            CASE 
                WHEN COALESCE(sales_data.avg_daily_sales, 0) > 0 
                THEN ROUND(p.stock / sales_data.avg_daily_sales, 1)
                ELSE 999 
            END as days_stock_remaining,
            CASE 
                WHEN p.stock <= 10 OR 
                     (COALESCE(sales_data.avg_daily_sales, 0) > 0 AND p.stock / sales_data.avg_daily_sales <= 7)
                THEN 'URGENT'
                WHEN p.stock <= 20 OR 
                     (COALESCE(sales_data.avg_daily_sales, 0) > 0 AND p.stock / sales_data.avg_daily_sales <= 14)
                THEN 'LOW'
                ELSE 'OK'
            END as stock_status
        FROM Product p
        LEFT JOIN Category c ON p.category_id = c.category_id
        LEFT JOIN (
            SELECT 
                daily_sales.product_id,
                AVG(daily_sales.daily_quantity) as avg_daily_sales
            FROM (
                SELECT 
                    oi.product_id,
                    DATE(o.created_at) as sale_date,
                    SUM(oi.quantity) as daily_quantity
                FROM OrderItem oi
                INNER JOIN Orders o ON oi.order_id = o.order_id
                WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                AND o.status NOT IN ('status05')
                GROUP BY oi.product_id, DATE(o.created_at)
            ) daily_sales
            GROUP BY daily_sales.product_id
        ) sales_data ON p.product_id = sales_data.product_id
        WHERE p.stock <= 50 OR 
              (COALESCE(sales_data.avg_daily_sales, 0) > 0 AND p.stock / sales_data.avg_daily_sales <= 30)
    ";
    
    $params = [];
    
    if ($category !== 'all') {
        $sql .= " AND p.category_id = ?";
        $params[] = $category;
    }
    
    $sql .= "
        ORDER BY 
            CASE 
                WHEN p.stock <= 10 OR 
                     (COALESCE(sales_data.avg_daily_sales, 0) > 0 AND p.stock / sales_data.avg_daily_sales <= 7)
                THEN 1
                WHEN p.stock <= 20 OR 
                     (COALESCE(sales_data.avg_daily_sales, 0) > 0 AND p.stock / sales_data.avg_daily_sales <= 14)
                THEN 2
                ELSE 3
            END,
            days_stock_remaining ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// FUNCTION: ดึงข้อมูลมูลค่าสต็อกทั้งหมด (สรุป)
function getStockValue($pdo, $category = 'all') {
    $sql = "
        SELECT 
            SUM(p.stock * p.price) as total_value
        FROM Product p
        WHERE p.stock > 0
    ";
    
    $params = [];
    
    if ($category !== 'all') {
        $sql .= " AND p.category_id = ?";
        $params[] = $category;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    
    return $result ?: ['total_value' => 0];
}

// ========================
// SHIPPING REPORTS
// ========================

// FUNCTION: ดึงข้อมูลสรุปการจัดส่ง (จำนวนคำสั่งซื้อ, ค่าส่ง)
function getShippingSummary($pdo, $start_date, $end_date) {
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN s.status_code = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
            SUM(CASE WHEN s.status_code IN ('awaiting_shipment', 'pending') THEN 1 ELSE 0 END) as pending_orders,
            COALESCE(SUM(o.shipping_fee), 0) as total_shipping_fee,
            COALESCE(AVG(o.shipping_fee), 0) as avg_shipping_fee
        FROM Orders o
        INNER JOIN Status s ON o.status = s.status_id
        WHERE o.created_at BETWEEN ? AND ?
    ");
    $stmt->execute([$start_date, $end_date . ' 23:59:59']);
    $result = $stmt->fetch();
    
    return $result ?: [
        'delivered_orders' => 0,
        'pending_orders' => 0,
        'total_shipping_fee' => 0,
        'avg_shipping_fee' => 0
    ];
}

// FUNCTION: ดึงข้อมูลการจัดส่งตามเขตพื้นที่ (ยอดขาย, จำนวน, ค่าเฉลี่ย)
function getShippingByZone($pdo, $start_date, $end_date) {
    $stmt = $pdo->prepare("
        SELECT 
            sz.zone_id,
            sz.name as zone_name,
            sz.description as zone_description,
            COUNT(o.order_id) as total_orders,
            SUM(CASE WHEN s.status_code = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
            COALESCE(SUM(o.shipping_fee), 0) as total_shipping_fee,
            COALESCE(AVG(o.shipping_fee), 0) as avg_shipping_fee
        FROM Orders o
        INNER JOIN Users u ON o.user_id = u.user_id
        INNER JOIN Addresses a ON u.user_id = a.user_id AND a.is_main = 1
        INNER JOIN Province pr ON a.province_id = pr.province_id
        INNER JOIN ShippingZone sz ON pr.zone_id = sz.zone_id
        INNER JOIN Status s ON o.status = s.status_id
        WHERE o.created_at BETWEEN ? AND ?
        AND o.status NOT IN ('status05')
        GROUP BY sz.zone_id, sz.name, sz.description
        ORDER BY total_shipping_fee DESC
    ");
    $stmt->execute([$start_date, $end_date . ' 23:59:59']);
    return $stmt->fetchAll();
}

// ========================
// CUSTOMER REPORTS
// ========================

// FUNCTION: ดึงข้อมูลสรุปลูกค้า (ลูกค้าใหม่, ลูกค้ากลับมา, ค่าเฉลี่ย)
function getCustomerSummary($pdo, $start_date, $end_date) {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT CASE WHEN u.created_at BETWEEN ? AND ? THEN u.user_id END) as new_customers,
            COUNT(DISTINCT CASE WHEN u.created_at < ? AND EXISTS(
                SELECT 1 FROM Orders o2 WHERE o2.user_id = u.user_id 
                AND o2.created_at BETWEEN ? AND ?
                AND o2.status NOT IN ('status05')
            ) THEN u.user_id END) as returning_customers,
            COALESCE(AVG(o.total_amount), 0) as avg_order_value,
            COALESCE(COUNT(DISTINCT o.order_id) / NULLIF(COUNT(DISTINCT u.user_id), 0), 0) as avg_orders_per_customer
        FROM Users u
        LEFT JOIN Orders o ON u.user_id = o.user_id 
            AND o.created_at BETWEEN ? AND ?
            AND o.status NOT IN ('status05')
    ");
    $stmt->execute([
        $start_date, $end_date . ' 23:59:59',
        $start_date,
        $start_date, $end_date . ' 23:59:59',
        $start_date, $end_date . ' 23:59:59'
    ]);
    $result = $stmt->fetch();
    
    return $result ?: [
        'new_customers' => 0,
        'returning_customers' => 0,
        'avg_order_value' => 0,
        'avg_orders_per_customer' => 0
    ];
}

// FUNCTION: ดึงข้อมูล 10 ลูกค้าซื้อมากที่สุด (จำนวน, ยอด, คำสั่งซื้อสุดท้าย)
function getTopCustomers($pdo, $start_date, $end_date, $limit = 10) {
    $stmt = $pdo->prepare("
        SELECT 
            u.user_id,
            COALESCE(u.name, u.email, 'Unknown') as customer_name,
            u.email,
            u.phone,
            COUNT(o.order_id) as total_orders,
            COALESCE(SUM(o.total_amount), 0) as total_spent,
            COALESCE(AVG(o.total_amount), 0) as avg_order_value,
            MAX(o.created_at) as last_order_date,
            MIN(o.created_at) as first_order_date
        FROM Users u
        INNER JOIN Orders o ON u.user_id = o.user_id
        WHERE o.created_at BETWEEN ? AND ?
        AND o.status NOT IN ('status05')
        GROUP BY u.user_id, u.name, u.email, u.phone
        ORDER BY total_spent DESC
        LIMIT ?
    ");
    
    $stmt->bindValue(1, $start_date);
    $stmt->bindValue(2, $end_date . ' 23:59:59');
    $stmt->bindValue(3, (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

?>