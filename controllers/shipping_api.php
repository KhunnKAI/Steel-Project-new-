<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

try {
    // Include required files
    if (!file_exists(__DIR__ . '/config.php')) {
        throw new Exception('Configuration file not found');
    }
    require_once __DIR__ . '/config.php';
    
    if (!file_exists(__DIR__ . '/shipping_calculator.php')) {
        throw new Exception('ShippingCalculator file not found');
    }
    require_once __DIR__ . '/shipping_calculator.php';

    // Start session
    session_start();

    // Get user ID
    function getUserId() {
        return $_SESSION['user_id'] ?? $_COOKIE['user_id'] ?? null;
    }

    function isAuthenticated() {
        $userId = getUserId();
        return !empty($userId);
    }

    if (!isAuthenticated()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'กรุณาล็อกอินก่อนใช้งาน'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $userId = getUserId();

    // Get database connection
    if (!isset($pdo) || !$pdo) {
        throw new Exception('Database connection failed');
    }

    // Initialize shipping calculator
    $shippingCalculator = new ShippingCalculator($pdo);

    // Handle different actions
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'calculate':
            handleCalculateShipping($shippingCalculator, $pdo, $userId);
            break;
        case 'get_zones':
            handleGetZones($shippingCalculator);
            break;
        case 'get_rates_by_zone':
            handleGetRatesByZone($shippingCalculator);
            break;
        case 'recalculate_cart':
            handleRecalculateCart($shippingCalculator, $pdo, $userId);
            break;
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action: ' . $action,
                'available_actions' => ['calculate', 'get_zones', 'get_rates_by_zone', 'recalculate_cart']
            ], JSON_UNESCAPED_UNICODE);
            break;
    }

} catch (Exception $e) {
    error_log("Shipping API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดของระบบ'
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Calculate shipping for specific weight and province
 */
function handleCalculateShipping($shippingCalculator, $pdo, $userId) {
    try {
        $provinceId = $_POST['province_id'] ?? $_GET['province_id'] ?? '';
        $weight = floatval($_POST['weight'] ?? $_GET['weight'] ?? 0);

        if (empty($provinceId)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'กรุณาระบุจังหวัด'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($weight <= 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'น้ำหนักต้องมากกว่า 0'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $result = $shippingCalculator->calculateShippingCost($weight, $provinceId);

        // Handle weight exceeded case specifically
        if (!$result['success'] && isset($result['weight_exceeded']) && $result['weight_exceeded']) {
            http_response_code(422); // Unprocessable Entity
            echo json_encode([
                'success' => false,
                'message' => $result['error'],
                'weight_validation' => $result['weight_validation'],
                'weight_exceeded' => true
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'data' => [
                    'shipping_cost' => $result['shipping_cost'],
                    'total_weight' => $result['total_weight'],
                    'province_info' => $result['province_info'],
                    'shipping_rate_info' => $result['shipping_rate_info'],
                    'weight_validation' => $result['weight_validation']
                ]
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'success' => false,
                'message' => $result['error']
            ], JSON_UNESCAPED_UNICODE);
        }

    } catch (Exception $e) {
        error_log("Calculate shipping error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการคำนวดค่าส่ง'
        ], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * Get all shipping zones
 */
function handleGetZones($shippingCalculator) {
    try {
        $zones = $shippingCalculator->getShippingZones();
        
        echo json_encode([
            'success' => true,
            'data' => $zones,
            'count' => count($zones)
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        error_log("Get zones error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลโซนการจัดส่ง'
        ], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * Get shipping rates by zone
 */
function handleGetRatesByZone($shippingCalculator) {
    try {
        $zoneId = $_GET['zone_id'] ?? '';
        
        if (empty($zoneId)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'กรุณาระบุ zone_id'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        $rates = $shippingCalculator->getShippingRatesByZone($zoneId);
        
        echo json_encode([
            'success' => true,
            'data' => $rates,
            'count' => count($rates)
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        error_log("Get rates by zone error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลอัตราค่าส่ง'
        ], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * Recalculate entire cart with consistent weight validation
 */
function handleRecalculateCart($shippingCalculator, $pdo, $userId) {
    try {
        $provinceId = $_POST['province_id'] ?? '';
        
        if (empty($provinceId)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'กรุณาระบุจังหวัด'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Get current cart items with complete weight data
        $stmt = $pdo->prepare("
            SELECT 
                c.product_id,
                c.quantity,
                p.price,
                COALESCE(p.weight, 0) as weight,
                COALESCE(p.weight_unit, 'kg') as weight_unit,
                p.name as product_name
            FROM Cart c
            INNER JOIN product p ON c.product_id = p.product_id
            WHERE c.user_id = :user_id
        ");
        $stmt->execute([':user_id' => $userId]);
        $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($cartItems)) {
            echo json_encode([
                'success' => true,
                'data' => [
                    'subtotal' => 0,
                    'total_items' => 0,
                    'total_weight' => 0,
                    'shipping' => ['cost' => 0],
                    'tax' => ['amount' => 0],
                    'grand_total' => 0,
                    'weight_validation' => ['success' => true, 'weight' => 0, 'limit' => ShippingCalculator::MAX_WEIGHT_LIMIT],
                    'can_order' => true
                ]
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Use ShippingCalculator for consistent calculation
        $result = $shippingCalculator->calculateOrderTotal($cartItems, $provinceId);

        // Handle weight exceeded case with proper error response
        if (!$result['success'] && isset($result['weight_validation']) && !$result['weight_validation']['success']) {
            http_response_code(422); // Unprocessable Entity
            echo json_encode([
                'success' => false,
                'message' => $result['message'] ?? $result['weight_validation']['error'],
                'data' => [
                    'subtotal' => $result['subtotal'],
                    'total_items' => $result['total_items'], 
                    'total_weight' => $result['total_weight'],
                    'shipping' => $result['shipping'],
                    'tax' => $result['tax'],
                    'grand_total' => $result['grand_total'],
                    'weight_validation' => $result['weight_validation'],
                    'can_order' => false,
                    'province_id' => $provinceId
                ],
                'weight_validation' => $result['weight_validation'],
                'weight_exceeded' => true
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'data' => [
                    'subtotal' => $result['subtotal'],
                    'total_items' => $result['total_items'],
                    'total_weight' => $result['total_weight'],
                    'shipping' => $result['shipping'],
                    'tax' => $result['tax'],
                    'grand_total' => $result['grand_total'],
                    'weight_validation' => $result['weight_validation'],
                    'can_order' => $result['can_order'],
                    'province_id' => $provinceId
                ]
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $result['error'] ?? 'ไม่สามารถคำนวดตะกร้าใหม่ได้'
            ], JSON_UNESCAPED_UNICODE);
        }

    } catch (Exception $e) {
        error_log("Recalculate cart error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการคำนวดตะกร้าใหม่'
        ], JSON_UNESCAPED_UNICODE);
    }
}
?>