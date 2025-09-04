<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

require_once 'config.php';

try {
    // รับ filter parameters
    $status_filter = $_GET['status'] ?? '';
    $user_filter = $_GET['user_id'] ?? '';
    $order_filter = $_GET['order_id'] ?? '';
    $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 50;
    $offset = isset($_GET['offset']) ? max(0, intval($_GET['offset'])) : 0;
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';

    $params = [];

    // Query หลัก - ปรับปรุงให้เร็วขึ้น
    $sql = "SELECT 
                o.order_id,
                o.user_id,
                u.name AS customer_name,
                u.email AS customer_email,
                u.phone AS customer_phone,
                o.total_amount,
                o.total_novat,
                o.shipping_fee,
                o.status AS status_id,
                s.status_code,
                s.description AS status_description,
                o.note,
                o.created_at,
                o.updated_at,
                -- Payment info
                p.payment_id,
                p.slip_image,
                p.admin_id AS verified_by_admin,
                p.created_at AS payment_created_at,
                p.updated_at AS payment_updated_at,
                -- Admin info
                a.fullname AS admin_name,
                a.position AS admin_position,
                -- Address info
                addr.address_id,
                addr.recipient_name,
                addr.phone AS address_phone,
                addr.address_line,
                addr.subdistrict,
                addr.district,
                prov.name AS province_name,
                addr.postal_code
            FROM Orders o
            LEFT JOIN Users u ON o.user_id = u.user_id
            LEFT JOIN Status s ON o.status = s.status_id
            LEFT JOIN Payment p ON o.order_id = p.order_id
            LEFT JOIN Admin a ON p.admin_id = a.admin_id
            LEFT JOIN Addresses addr ON u.user_id = addr.user_id AND addr.is_main = 1
            LEFT JOIN Province prov ON addr.province_id = prov.province_id
            WHERE 1=1";

    // Filters - ปรับปรุงให้รองรับ status code
    if ($status_filter !== '') {
        // รองรับทั้ง status_code และ status_id
        if (strpos($status_filter, 'status') === 0) {
            // เป็น status_id (status01, status02, etc.)
            $sql .= " AND o.status = ?";
            $params[] = $status_filter;
        } else {
            // เป็น status_code (pending_payment, awaiting_shipment, etc.)
            $sql .= " AND s.status_code = ?";
            $params[] = $status_filter;
        }
    }
    if ($user_filter !== '') {
        $sql .= " AND o.user_id = ?";
        $params[] = $user_filter;
    }
    if ($order_filter !== '') {
        $sql .= " AND o.order_id = ?";
        $params[] = $order_filter;
    }
    if ($date_from !== '') {
        $sql .= " AND DATE(o.created_at) >= ?";
        $params[] = $date_from;
    }
    if ($date_to !== '') {
        $sql .= " AND DATE(o.created_at) <= ?";
        $params[] = $date_to;
    }

    $sql .= " ORDER BY o.created_at DESC LIMIT $limit OFFSET $offset";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $orders = [];
    while ($row = $stmt->fetch()) {
        $order_id = $row['order_id'];
        if (!isset($orders[$order_id])) {
            $orders[$order_id] = [
                'order_id' => $order_id,
                'user_id' => $row['user_id'],
                'customer_info' => [
                    'name' => $row['customer_name'],
                    'email' => $row['customer_email'],
                    'phone' => $row['customer_phone']
                ],
                'shipping_address' => null,
                'total_amount' => floatval($row['total_amount']),
                'total_novat' => floatval($row['total_novat']),
                'shipping_fee' => floatval($row['shipping_fee']),
                'status' => [
                    'status_id' => $row['status_id'],
                    'status_code' => $row['status_code'],
                    'description' => $row['status_description']
                ],
                'note' => $row['note'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
                'payment_info' => null,
                'order_items' => [],
                // เพิ่มข้อมูลสำหรับการจัดการ status
                'can_update_status' => $row['status_code'] !== 'delivered' && $row['status_code'] !== 'cancelled',
                'can_be_cancelled' => $row['status_code'] !== 'cancelled',
                'available_status_transitions' => [],
                'last_updated_by' => null
            ];

            // ข้อมูลที่อยู่จัดส่ง
            if ($row['address_id']) {
                $orders[$order_id]['shipping_address'] = [
                    'address_id' => $row['address_id'],
                    'recipient_name' => $row['recipient_name'],
                    'phone' => $row['address_phone'],
                    'address_line' => $row['address_line'],
                    'subdistrict' => $row['subdistrict'],
                    'district' => $row['district'],
                    'province' => $row['province_name'],
                    'postal_code' => $row['postal_code']
                ];
            }

            // ข้อมูลการชำระเงิน - ปรับปรุงให้สมบูรณ์ขึ้น
            if ($row['payment_id']) {
                $orders[$order_id]['payment_info'] = [
                    'payment_id' => $row['payment_id'],
                    'slip_image' => $row['slip_image'] ? '../controllers/uploads/payment_slips/' . $row['slip_image'] : null,
                    'verified_by_admin' => $row['verified_by_admin'],
                    'admin_name' => $row['admin_name'],
                    'admin_position' => $row['admin_position'],
                    'payment_created_at' => $row['payment_created_at'],
                    'payment_updated_at' => $row['payment_updated_at'],
                    'is_verified' => !empty($row['verified_by_admin'])
                ];
                
                // ข้อมูล admin ที่ verify
                if ($row['verified_by_admin']) {
                    $orders[$order_id]['last_updated_by'] = [
                        'admin_id' => $row['verified_by_admin'],
                        'admin_name' => $row['admin_name'],
                        'admin_position' => $row['admin_position']
                    ];
                }
            }

            // กำหนด available status transitions - อัปเดตให้รองรับการยกเลิกจากทุกสถานะ
            $orders[$order_id]['available_status_transitions'] = getAvailableStatusTransitions($row['status_code']);
        }
    }

    // ดึง order items
    if (!empty($orders)) {
        $order_ids = array_keys($orders);
        $placeholders = str_repeat('?,', count($order_ids) - 1) . '?';

        $items_sql = "SELECT 
                        oi.order_item_id,
                        oi.order_id,
                        oi.product_id,
                        p.name AS product_name,
                        p.description AS product_description,
                        oi.quantity,
                        oi.price_each,
                        oi.weight_each,
                        oi.lot,
                        p.width,
                        p.length,
                        p.height,
                        p.weight,
                        p.width_unit,
                        p.length_unit,
                        p.height_unit,
                        p.weight_unit,
                        (oi.quantity * oi.price_each) as line_total
                      FROM OrderItem oi
                      LEFT JOIN Product p ON oi.product_id = p.product_id
                      WHERE oi.order_id IN ($placeholders)
                      ORDER BY oi.order_item_id";

        $items_stmt = $pdo->prepare($items_sql);
        $items_stmt->execute($order_ids);

        while ($item = $items_stmt->fetch()) {
            $orders[$item['order_id']]['order_items'][] = [
                'order_item_id' => $item['order_item_id'],
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'product_description' => $item['product_description'],
                'quantity' => intval($item['quantity']),
                'price_each' => floatval($item['price_each']),
                'weight_each' => floatval($item['weight_each']),
                'line_total' => floatval($item['line_total']),
                'lot' => $item['lot'],
                'product_dimensions' => [
                    'width' => floatval($item['width']),
                    'length' => floatval($item['length']),
                    'height' => floatval($item['height']),
                    'weight' => floatval($item['weight']),
                    'width_unit' => $item['width_unit'],
                    'length_unit' => $item['length_unit'],
                    'height_unit' => $item['height_unit'],
                    'weight_unit' => $item['weight_unit']
                ]
            ];
        }
    }

    // Total count - ปรับปรุงให้ตรงกับ filter
    $count_sql = "SELECT COUNT(DISTINCT o.order_id) as total
                  FROM Orders o
                  LEFT JOIN Users u ON o.user_id = u.user_id
                  LEFT JOIN Status s ON o.status = s.status_id
                  WHERE 1=1";

    $count_params = [];
    if ($status_filter !== '') {
        if (strpos($status_filter, 'status') === 0) {
            $count_sql .= " AND o.status = ?";
            $count_params[] = $status_filter;
        } else {
            $count_sql .= " AND s.status_code = ?";
            $count_params[] = $status_filter;
        }
    }
    if ($user_filter !== '') {
        $count_sql .= " AND o.user_id = ?";
        $count_params[] = $user_filter;
    }
    if ($order_filter !== '') {
        $count_sql .= " AND o.order_id = ?";
        $count_params[] = $order_filter;
    }
    if ($date_from !== '') {
        $count_sql .= " AND DATE(o.created_at) >= ?";
        $count_params[] = $date_from;
    }
    if ($date_to !== '') {
        $count_sql .= " AND DATE(o.created_at) <= ?";
        $count_params[] = $date_to;
    }

    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($count_params);
    $total_count = $count_stmt->fetchColumn();

    // สรุปสถิติ - FIXED: Added s.status_id to GROUP BY and ORDER BY
    $stats_sql = "SELECT 
                    s.status_id,
                    s.status_code,
                    s.description,
                    COUNT(o.order_id) as count,
                    SUM(o.total_amount) as total_amount
                  FROM Orders o
                  LEFT JOIN Status s ON o.status = s.status_id
                  WHERE 1=1";
    
    if ($date_from !== '') {
        $stats_sql .= " AND DATE(o.created_at) >= '$date_from'";
    }
    if ($date_to !== '') {
        $stats_sql .= " AND DATE(o.created_at) <= '$date_to'";
    }
    
    $stats_sql .= " GROUP BY s.status_id, s.status_code, s.description ORDER BY s.status_id";
    
    $stats_stmt = $pdo->query($stats_sql);
    $statistics = $stats_stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => array_values($orders),
        'pagination' => [
            'total' => intval($total_count),
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $total_count,
            'current_page' => floor($offset / $limit) + 1,
            'total_pages' => ceil($total_count / $limit)
        ],
        'statistics' => $statistics,
        'filters_applied' => [
            'status' => $status_filter,
            'user_id' => $user_filter,
            'order_id' => $order_filter,
            'date_from' => $date_from,
            'date_to' => $date_to
        ],
        'status_definitions' => getStatusDefinitions()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

// Helper functions - อัปเดตให้รองรับการยกเลิกจากทุกสถานะ
function getAvailableStatusTransitions($current_status) {
    $transitions = [
        'pending_payment' => ['awaiting_shipment', 'cancelled'],
        'awaiting_shipment' => ['in_transit', 'cancelled'],
        'in_transit' => ['delivered', 'cancelled'],
        'delivered' => ['cancelled'], // อนุกาตให้ยกเลิกได้หลังจัดส่งแล้ว (สำหรับการคืนเงิน/รีเทิร์น)
        'cancelled' => [] // ไม่สามารถเปลี่ยนจากสถานะยกเลิกได้
    ];
    
    return $transitions[$current_status] ?? [];
}

function getStatusDefinitions() {
    return [
        'pending_payment' => [
            'code' => 'pending_payment',
            'id' => 'status01',
            'description' => 'รอการชำระเงิน',
            'color' => '#ffc107',
            'icon' => 'pending',
            'can_cancel' => true
        ],
        'awaiting_shipment' => [
            'code' => 'awaiting_shipment',
            'id' => 'status02',
            'description' => 'รอจัดส่ง',
            'color' => '#17a2b8',
            'icon' => 'package',
            'can_cancel' => true
        ],
        'in_transit' => [
            'code' => 'in_transit',
            'id' => 'status03',
            'description' => 'กำลังจัดส่ง',
            'color' => '#007bff',
            'icon' => 'truck',
            'can_cancel' => true
        ],
        'delivered' => [
            'code' => 'delivered',
            'id' => 'status04',
            'description' => 'จัดส่งแล้ว',
            'color' => '#28a745',
            'icon' => 'check',
            'can_cancel' => true // อนุกาตให้ยกเลิกได้ (สำหรับรีเทิร์น)
        ],
        'cancelled' => [
            'code' => 'cancelled',
            'id' => 'status05',
            'description' => 'ยกเลิก',
            'color' => '#dc3545',
            'icon' => 'x',
            'can_cancel' => false
        ]
    ];
}
?>