<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// ----------------------------------------
// Error & Exception Handling
// ----------------------------------------
error_reporting(E_ALL);
ini_set('display_errors', 0);

$log_file = __DIR__ . '/error_log_get_admin.log';

set_error_handler(function ($severity, $message, $file, $line) use ($log_file) {
    $log = "[" . date('Y-m-d H:i:s') . "] ERROR: $message in $file on line $line\n";
    error_log($log, 3, $log_file);
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function ($e) use ($log_file) {
    $log = "[" . date('Y-m-d H:i:s') . "] EXCEPTION: " . $e->getMessage() . " in " . $e->getFile() . " line " . $e->getLine() . "\n";
    error_log($log, 3, $log_file);

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
});

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ----------------------------------------
// Database configuration
// ----------------------------------------
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'teststeel';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --------------------------
    // Query Parameters
    // --------------------------
    $admin_id   = $_GET['admin_id']   ?? null;
    $status     = $_GET['status']     ?? null;
    $department = $_GET['department'] ?? null;
    $position   = $_GET['position']   ?? null;
    $search     = $_GET['search']     ?? null;
    $page       = max(1, (int)($_GET['page'] ?? 1));
    $limit      = max(1, (int)($_GET['limit'] ?? 10));
    $offset     = ($page - 1) * $limit;

    // --------------------------
    // Base SQL
    // --------------------------
    $sql = "SELECT admin_id, fullname, position, department, phone, status, created_at, updated_at 
            FROM admin WHERE 1=1";
    $params = [];

    if ($admin_id) {
        $sql .= " AND admin_id = :admin_id";
        $params[':admin_id'] = $admin_id;
    }
    if ($status) {
        $sql .= " AND status = :status";
        $params[':status'] = $status;
    }
    if ($department) {
        $sql .= " AND department = :department";
        $params[':department'] = $department;
    }
    if ($position) {
        $sql .= " AND position = :position";
        $params[':position'] = $position;
    }
    if ($search) {
        $sql .= " AND (fullname LIKE :search OR admin_id LIKE :search OR phone LIKE :search)";
        $params[':search'] = "%$search%";
    }

    // --------------------------
    // Count total
    // --------------------------
    $count_sql = str_replace(
        "SELECT admin_id, fullname, position, department, phone, status, created_at, updated_at FROM admin",
        "SELECT COUNT(*) as total FROM admin",
        $sql
    );
    $count_stmt = $pdo->prepare($count_sql);
    foreach ($params as $k => $v) {
        $count_stmt->bindValue($k, $v);
    }
    $count_stmt->execute();
    $count_row = $count_stmt->fetch(PDO::FETCH_ASSOC);
    $total_records = $count_row && isset($count_row['total']) ? (int)$count_row['total'] : 0;
    $total_pages   = $limit > 0 ? ceil($total_records / $limit) : 1;

    // --------------------------
    // Main Query
    // --------------------------
    $sql .= " ORDER BY created_at DESC";
    if (!$admin_id) {
        $sql .= " LIMIT :limit OFFSET :offset";
    }

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    if (!$admin_id) {
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    }
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --------------------------
    // Format data
    // --------------------------
    foreach ($admins as &$admin) {
        $admin['created_at_formatted'] = date('d/m/Y H:i', strtotime($admin['created_at']));
        $admin['updated_at_formatted'] = $admin['updated_at'] ? date('d/m/Y H:i', strtotime($admin['updated_at'])) : null;

        $dept_thai = [
            'management' => 'บริหาร',
            'sales'      => 'ขาย',
            'warehouse'  => 'คลังสินค้า',
            'logistics'  => 'ขนส่ง',
            'accounting' => 'บัญชี',
            'it'         => 'เทคโนโลยีสารสนเทศ'
        ];
        $position_thai = [
            'manager'    => 'ผู้จัดการ',
            'sales'      => 'พนักงานขาย',
            'warehouse'  => 'พนักงานคลัง',
            'shipping'   => 'พนักงานขนส่ง',
            'accounting' => 'พนักงานบัญชี',
            'super'      => 'ผู้ดูแลระบบ'
        ];

        $admin['department_thai'] = $dept_thai[$admin['department']] ?? $admin['department'];
        $admin['position_thai']   = $position_thai[$admin['position']] ?? $admin['position'];
        $admin['status_thai']     = $admin['status'] === 'active' ? 'ใช้งานอยู่' : 'ไม่ได้ใช้งาน';
    }

    // --------------------------
    // Response
    // --------------------------
    $response = [
        'success' => true,
        'data' => $admin_id ? ($admins[0] ?? null) : $admins,
        'pagination' => [
            'current_page'     => $page,
            'total_pages'      => $total_pages,
            'total_records'    => $total_records,
            'records_per_page' => $limit
        ],
        'summary' => [
            'total'        => $total_records,
            'active'       => 0,
            'inactive'     => 0,
            'by_department'=> [],
            'by_position'  => []
        ]
    ];

    // summary เฉพาะตอนที่ไม่มี filter
    if (!$admin_id && empty($search) && empty($status) && empty($department) && empty($position)) {
        $summary_stmt = $pdo->query("
            SELECT status, department, position, COUNT(*) as count
            FROM admin 
            GROUP BY status, department, position
        ");
        $summary_data = $summary_stmt->fetchAll(PDO::FETCH_ASSOC);

        $dept_counts = [];
        $position_counts = [];

        foreach ($summary_data as $row) {
            if ($row['status'] === 'active') {
                $response['summary']['active'] += $row['count'];
            } else {
                $response['summary']['inactive'] += $row['count'];
            }
            $dept_counts[$row['department']] = ($dept_counts[$row['department']] ?? 0) + $row['count'];
            $position_counts[$row['position']] = ($position_counts[$row['position']] ?? 0) + $row['count'];
        }

        $response['summary']['by_department'] = $dept_counts;
        $response['summary']['by_position']   = $position_counts;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] PDO ERROR: " . $e->getMessage() . "\n", 3, $log_file);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

?>
