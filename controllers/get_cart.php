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

// Error handling
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

try {
    // Include config and shipping calculator
    if (!file_exists(__DIR__ . '/config.php')) {
        throw new Exception('Configuration file not found');
    }
    require_once __DIR__ . '/config.php';
    
    if (!file_exists(__DIR__ . '/shipping_calculator.php')) {
        throw new Exception('ShippingCalculator file not found');
    }
    require_once __DIR__ . '/shipping_calculator.php';
    
    session_start();

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
            'message' => 'กรุณาล็อกอินก่อนใช้งานตะกร้า'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $userId = getUserId();

    if (!isset($pdo) || !$pdo) {
        throw new Exception('Database connection failed');
    }

    $shippingCalculator = new ShippingCalculator($pdo);

    // -------------------------
    // Get customer data
    // -------------------------
    $stmtUser = $pdo->prepare("
        SELECT user_id, name, email, phone
        FROM Users
        WHERE user_id = :user_id
    ");
    $stmtUser->bindParam(':user_id', $userId, PDO::PARAM_STR);
    $stmtUser->execute();
    $customer = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'ไม่พบข้อมูลลูกค้า'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // -------------------------
    // Get default address
    // -------------------------
    $stmtAddress = $pdo->prepare("
        SELECT 
            a.address_id, 
            a.recipient_name, 
            a.phone, 
            a.address_line, 
            a.subdistrict, 
            a.district, 
            a.province_id,
            COALESCE(p.name, 'ไม่ระบุจังหวัด') as province,
            a.postal_code,
            a.is_main
        FROM Addresses a
        LEFT JOIN Province p ON a.province_id = p.province_id
        WHERE a.user_id = :user_id
        ORDER BY a.is_main DESC, a.created_at DESC
        LIMIT 1
    ");
    $stmtAddress->bindParam(':user_id', $userId, PDO::PARAM_STR);
    $stmtAddress->execute();
    $defaultAddress = $stmtAddress->fetch(PDO::FETCH_ASSOC);

    // -------------------------
    // Get cart items with product details
    // -------------------------
    $cartItems = [];
    
    $productCheck = $pdo->query("SHOW TABLES LIKE 'Product'");
    if ($productCheck && $productCheck->rowCount() > 0) {
        $productColumns = $pdo->query("DESCRIBE Product");
        $columns = $productColumns->fetchAll(PDO::FETCH_COLUMN);
        
        $hasStock = in_array('stock', $columns);
        $hasWeight = in_array('weight', $columns);
        $hasWeightUnit = in_array('weight_unit', $columns);
        
        $cartQuery = "
            SELECT 
                c.product_id,
                c.quantity,
                p.name,
                p.price";
        
        $cartQuery .= $hasStock ? ", COALESCE(p.stock, 999) as stock_quantity" : ", 999 as stock_quantity";
        $cartQuery .= $hasWeight ? ", COALESCE(p.weight, 0) as weight" : ", 0 as weight";
        $cartQuery .= $hasWeightUnit ? ", COALESCE(p.weight_unit, 'kg') as weight_unit" : ", 'kg' as weight_unit";
        
        $cartQuery .= "
            FROM Cart c
            INNER JOIN Product p ON c.product_id = p.product_id
            WHERE c.user_id = :user_id
            ORDER BY c.created_at DESC";
        
        $stmt = $pdo->prepare($cartQuery);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
        $stmt->execute();
        $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // -------------------------
        // Map product images to cart items
        // -------------------------
        if (!empty($cartItems)) {
            $productIds = array_column($cartItems, 'product_id');
            $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
            
            $imageCheck = $pdo->query("SHOW TABLES LIKE 'ProductImage'");
            if ($imageCheck && $imageCheck->rowCount() > 0) {
                $imageQuery = "
                    SELECT DISTINCT
                        p.product_id,
                        pi.image_url,
                        COALESCE(pi.is_main, 0) as is_main
                    FROM Product p
                    LEFT JOIN ProductImage pi ON p.product_id = pi.product_id
                    WHERE p.product_id IN ($placeholders)
                ";
                $imageStmt = $pdo->prepare($imageQuery);
                $imageStmt->execute($productIds);
                $productImages = $imageStmt->fetchAll(PDO::FETCH_ASSOC);

                $imageMap = [];
                foreach ($productImages as $img) {
                    if (!isset($imageMap[$img['product_id']])) {
                        $imageMap[$img['product_id']] = [];
                    }
                    $imageMap[$img['product_id']][] = [
                        'filename' => $img['image_url'],
                        'is_main' => (bool)$img['is_main']
                    ];
                }

                $baseUrl = 'http://localhost/steelproject/';
                foreach ($cartItems as &$item) {
                    $pid = $item['product_id'];
                    if (isset($imageMap[$pid]) && !empty($imageMap[$pid])) {
                        $allImages = $imageMap[$pid];
                        $mainImage = null;
                        foreach ($allImages as $img) {
                            if ($img['is_main']) {
                                $mainImage = $img['filename'];
                                break;
                            }
                        }
                        if (!$mainImage) $mainImage = $allImages[0]['filename'];

                        // ตัด base URL ออก แล้วใช้ path แบบเต็ม
                        $item['image'] = str_replace($baseUrl, '', $mainImage);
                        $item['images'] = array_map(function($img) use ($baseUrl) {
                            return [
                                'url' => str_replace($baseUrl, '', $img['filename']),
                                'is_main' => $img['is_main']
                            ];
                        }, $allImages);

                    } else {
                        $item['image'] = "admin/controllers/uploads/products/default.jpg";
                        $item['images'] = [[
                            'url' => "admin/controllers/uploads/products/default.jpg",
                            'is_main' => true
                        ]];
                    }
                }
            } else {
                foreach ($cartItems as &$item) {
                    $item['image'] = "admin/controllers/uploads/products/default.jpg";
                    $item['images'] = [[
                        'url' => "admin/controllers/uploads/products/default.jpg",
                        'is_main' => true
                    ]];
                }
            }
        }
    }

    // -------------------------
    // Process cart items, stock validation
    // -------------------------
    $totalAmount = 0;
    $totalItems = 0;
    $outOfStockItems = [];

    foreach ($cartItems as $index => &$item) {
        $item['price'] = floatval($item['price'] ?? 0);
        $item['quantity'] = intval($item['quantity'] ?? 0);
        $item['stock_quantity'] = intval($item['stock_quantity'] ?? 999);
        $item['weight'] = floatval($item['weight'] ?? 0);
        $item['weight_unit'] = $item['weight_unit'] ?? 'kg';
        
        if ($item['stock_quantity'] < $item['quantity']) {
            $outOfStockItems[] = $item['name'];
            $item['quantity'] = max(0, $item['stock_quantity']);
            
            if ($item['quantity'] > 0) {
                $updateStmt = $pdo->prepare("UPDATE Cart SET quantity = :quantity WHERE user_id = :user_id AND product_id = :product_id");
                $updateStmt->execute([
                    ':quantity' => $item['quantity'],
                    ':user_id' => $userId,
                    ':product_id' => $item['product_id']
                ]);
            } else {
                $deleteStmt = $pdo->prepare("DELETE FROM Cart WHERE user_id = :user_id AND product_id = :product_id");
                $deleteStmt->execute([
                    ':user_id' => $userId,
                    ':product_id' => $item['product_id']
                ]);
                unset($cartItems[$index]);
                continue;
            }
        }

        $itemTotal = $item['price'] * $item['quantity'];
        $item['itemTotal'] = round($itemTotal, 2);
        $item['available'] = $item['stock_quantity'] >= $item['quantity'];

        $totalAmount += $itemTotal;
        $totalItems += $item['quantity'];
        
        unset($item['stock_quantity']);
    }

    $cartItems = array_values($cartItems);

    // -------------------------
    // Shipping calculation
    // -------------------------
    $shippingCost = 0;
    $shippingInfo = null;
    $totalWeight = 0;
    $canOrder = true;
    $weightValidation = null;

    if ($defaultAddress && !empty($defaultAddress['province_id']) && !empty($cartItems)) {
        $orderCalculation = $shippingCalculator->calculateOrderTotal($cartItems, $defaultAddress['province_id']);
        if ($orderCalculation['success']) {
            $shippingCost = $orderCalculation['shipping']['cost'];
            $shippingInfo = $orderCalculation['shipping'];
            $totalWeight = $orderCalculation['total_weight'];
            $weightValidation = $orderCalculation['weight_validation'];
            $canOrder = $orderCalculation['can_order'];
        } else {
            $totalWeight = $shippingCalculator->calculateTotalWeight($cartItems);
            $weightValidation = $shippingCalculator->validateWeightLimit($totalWeight);
            if (!$weightValidation['success']) {
                $shippingCost = 0;
                $canOrder = false;
                $shippingInfo = [
                    'cost' => 0,
                    'method' => 'weight_exceeded',
                    'note' => 'ไม่สามารถคำนวดค่าส่งได้ เนื่องจากน้ำหนักเกินขีดจำกัด',
                    'weight_exceeded' => true
                ];
            } else {
                $shippingCost = $totalAmount >= 1000 ? 0 : 50;
                $canOrder = true;
                $shippingInfo = [
                    'cost' => $shippingCost,
                    'method' => 'fallback',
                    'note' => 'ใช้อัตราค่าส่งสำรอง'
                ];
            }
        }
    } else {
        if (!empty($cartItems)) {
            $totalWeight = $shippingCalculator->calculateTotalWeight($cartItems);
            $weightValidation = $shippingCalculator->validateWeightLimit($totalWeight);
            $canOrder = $weightValidation['success'];
            $shippingCost = $canOrder ? ($totalAmount >= 1000 ? 0 : 50) : 0;
        } else {
            $totalWeight = 0;
            $canOrder = true;
            $shippingCost = 0;
            $weightValidation = ['success' => true, 'weight' => 0, 'limit' => ShippingCalculator::MAX_WEIGHT_LIMIT];
        }
    }

    $taxRate = 0.07;
    $taxAmount = round($totalAmount * $taxRate, 2);
    $grandTotal = round($totalAmount + $shippingCost + $taxAmount, 2);

    // -------------------------
    // Build response
    // -------------------------
    $response = [
        'success' => true,
        'customer' => $customer,
        'address' => $defaultAddress,
        'cart' => [
            'items' => $cartItems,
            'totalItems' => $totalItems,
            'totalWeight' => $totalWeight,
            'subTotal' => round($totalAmount, 2),
            'shipping' => [
                'cost' => $shippingCost,
                'info' => $shippingInfo
            ],
            'taxRate' => $taxRate,
            'taxAmount' => $taxAmount,
            'grandTotal' => $grandTotal,
            'canOrder' => $canOrder
        ]
    ];

    if ($weightValidation) {
        $response['cart']['weightValidation'] = $weightValidation;
    }

    if (!empty($outOfStockItems)) {
        $response['warnings'] = [
            'out_of_stock' => 'สินค้าบางรายการมี stock ไม่เพียงพอ: ' . implode(', ', $outOfStockItems)
        ];
    }

    if (!$canOrder && $weightValidation && !$weightValidation['success']) {
        $response['warnings'] = ($response['warnings'] ?? []) + [
            'weight_exceeded' => $weightValidation['error'] ?? 'น้ำหนักเกินขีดจำกัด'
        ];
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    error_log("Cart API Database Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลตะกร้า กรุณาลองใหม่อีกครั้ง',
        'debug' => [
            'error' => $e->getMessage(),
            'code' => $e->getCode()
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("Cart API General Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดของระบบ กรุณาลองใหม่อีกครั้ง',
        'debug' => [
            'error' => $e->getMessage()
        ]
    ], JSON_UNESCAPED_UNICODE);
}
?>
