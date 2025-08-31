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
    
    // Include ShippingCalculator class
    if (!file_exists(__DIR__ . '/shipping_calculator.php')) {
        throw new Exception('ShippingCalculator file not found');
    }
    require_once __DIR__ . '/shipping_calculator.php';
    
    // Start session
    session_start();

    // Get user ID (same pattern as address_api.php)
    function getUserId() {
        return $_SESSION['user_id'] ?? $_COOKIE['user_id'] ?? null;
    }
    
    function isAuthenticated() {
        $userId = getUserId();
        return !empty($userId);
    }

    // Check authentication
    if (!isAuthenticated()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'กรุณาล็อกอินก่อนใช้งานตะกร้า'
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

    // -------------------------
    // Get customer data
    // -------------------------
    $stmtUser = $pdo->prepare("
        SELECT user_id, name, email, phone
        FROM users
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
        FROM addresses a
        LEFT JOIN Province p ON a.province_id = p.province_id
        WHERE a.user_id = :user_id
        ORDER BY a.is_main DESC, a.created_at DESC
        LIMIT 1
    ");
    $stmtAddress->bindParam(':user_id', $userId, PDO::PARAM_STR);
    $stmtAddress->execute();
    $defaultAddress = $stmtAddress->fetch(PDO::FETCH_ASSOC);

    // -------------------------
    // Get cart items with product details and ALL images
    // -------------------------
    $cartItems = [];
    
    // Check if product table exists and get its structure
    $productCheck = $pdo->query("SHOW TABLES LIKE 'product'");
    if ($productCheck && $productCheck->rowCount() > 0) {
        // Check what columns exist in product table
        $productColumns = $pdo->query("DESCRIBE product");
        $columns = $productColumns->fetchAll(PDO::FETCH_COLUMN);
        
        $hasStock = in_array('stock', $columns);
        $hasWeight = in_array('weight', $columns);
        $hasWeightUnit = in_array('weight_unit', $columns);
        
        // Build cart query - แก้ไขการ JOIN รูปภาพ
        $cartQuery = "
            SELECT 
                c.product_id,
                c.quantity,
                p.name,
                p.price";
        
        // Use correct stock column name based on your schema
        if ($hasStock) {
            $cartQuery .= ", p.stock as stock_quantity";
        } else {
            $cartQuery .= ", 999 as stock_quantity";
        }
        
        if ($hasWeight) {
            $cartQuery .= ", p.weight";
        } else {
            $cartQuery .= ", 0 as weight";
        }
        
        if ($hasWeightUnit) {
            $cartQuery .= ", p.weight_unit";
        } else {
            $cartQuery .= ", 'kg' as weight_unit";
        }
        
        // แก้ไข: ใช้ productimage_id จากตาราง product เพื่อ JOIN กับ ProductImage
        $cartQuery .= "
            FROM cart c
            INNER JOIN product p ON c.product_id = p.product_id
            WHERE c.user_id = :user_id
            ORDER BY c.created_at DESC";
        
        try {
            $stmt = $pdo->prepare($cartQuery);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
            $stmt->execute();
            $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // ดึงรูปภาพแยกต่างหาก เพื่อให้ได้รูปภาพครบทุกรายการ
            if (!empty($cartItems)) {
                $productIds = array_column($cartItems, 'product_id');
                $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
                
                // ดึงรูปภาพทั้งหมดของสินค้าในตะกร้า
                $imageQuery = "
                    SELECT DISTINCT
                        p.product_id,
                        CASE 
                            WHEN pi.image_url IS NOT NULL AND pi.image_url != '' THEN pi.image_url
                            ELSE 'admin/controllers/uploads/products/default-product.jpg'
                        END as image_url,
                        COALESCE(pi.is_main, 0) as is_main
                    FROM product p
                    LEFT JOIN productimage pi ON p.product_id = pi.product_id
                    WHERE p.product_id IN ($placeholders)
                ";
                
                // ตรวจสอบว่ามี ProductImage table หรือไม่
                $imageCheck = $pdo->query("SHOW TABLES LIKE 'productimage'");
                if ($imageCheck && $imageCheck->rowCount() > 0) {
                    try {
                        $imageStmt = $pdo->prepare($imageQuery);
                        $imageStmt->execute($productIds);
                        $productImages = $imageStmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        // สร้าง lookup array สำหรับรูปภาพ
                        $imageMap = [];
                        foreach ($productImages as $img) {
                            if (!isset($imageMap[$img['product_id']])) {
                                $imageMap[$img['product_id']] = [];
                            }
                            $imageMap[$img['product_id']][] = [
                                'url' => $img['image_url'],
                                'is_main' => (bool)$img['is_main']
                            ];
                        }
                        
                        // เพิ่มรูปภาพให้กับรายการสินค้าในตะกร้า
                        foreach ($cartItems as &$item) {
                            $productId = $item['product_id'];
                            
                            if (isset($imageMap[$productId]) && !empty($imageMap[$productId])) {
                                // หาภาพหลักก่อน
                                $mainImage = null;
                                $allImages = $imageMap[$productId];
                                
                                foreach ($allImages as $img) {
                                    if ($img['is_main']) {
                                        $mainImage = $img['url'];
                                        break;
                                    }
                                }
                                
                                // ถ้าไม่มีภาพหลัก ใช้ภาพแรก
                                if (!$mainImage && !empty($allImages)) {
                                    $mainImage = $allImages[0]['url'];
                                }
                                
                                $item['image'] = $mainImage ?: 'admin/controllers/uploads/products/default-product.jpg';
                                $item['images'] = $allImages; // รูปภาพทั้งหมด
                            } else {
                                $item['image'] = 'admin/controllers/uploads/products/default-product.jpg';
                                $item['images'] = [[
                                    'url' => 'admin/controllers/uploads/products/default-product.jpg',
                                    'is_main' => true
                                ]];
                            }
                        }
                        
                    } catch (PDOException $e) {
                        error_log("Image query failed: " . $e->getMessage());
                        // ถ้าดึงรูปไม่ได้ ให้ใส่รูป default
                        foreach ($cartItems as &$item) {
                            $item['image'] = 'admin/controllers/uploads/products/default-product.jpg';
                            $item['images'] = [[
                                'url' => 'admin/controllers/uploads/products/default-product.jpg',
                                'is_main' => true
                            ]];
                        }
                    }
                } else {
                    // ไม่มี ProductImage table
                    foreach ($cartItems as &$item) {
                        $item['image'] = 'admin/controllers/uploads/products/default-product.jpg';
                        $item['images'] = [[
                            'url' => 'admin/controllers/uploads/products/default-product.jpg',
                            'is_main' => true
                        ]];
                    }
                }
            }
            
        } catch (PDOException $e) {
            error_log("Cart query failed: " . $e->getMessage());
            $cartItems = [];
        }
    }

    // -------------------------
    // Process cart items and calculate totals
    // -------------------------
    $totalAmount = 0;
    $totalItems = 0;
    $outOfStockItems = [];

    // Process cart items first (handle stock adjustments)
    foreach ($cartItems as $index => &$item) {
        $item['price'] = floatval($item['price'] ?? 0);
        $item['quantity'] = intval($item['quantity'] ?? 0);
        $item['stock_quantity'] = intval($item['stock_quantity'] ?? 999);
        $item['weight'] = floatval($item['weight'] ?? 0);
        $item['weight_unit'] = $item['weight_unit'] ?? 'kg';
        
        // Check stock availability
        if ($item['stock_quantity'] < $item['quantity']) {
            $outOfStockItems[] = $item['name'];
            $item['quantity'] = max(0, $item['stock_quantity']);
            
            // Update cart in database
            if ($item['quantity'] > 0) {
                $updateStmt = $pdo->prepare("UPDATE cart SET quantity = :quantity WHERE user_id = :user_id AND product_id = :product_id");
                $updateStmt->execute([
                    ':quantity' => $item['quantity'],
                    ':user_id' => $userId,
                    ':product_id' => $item['product_id']
                ]);
            } else {
                // Remove from cart if no stock
                $deleteStmt = $pdo->prepare("DELETE FROM cart WHERE user_id = :user_id AND product_id = :product_id");
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
        
        // Clean up response (remove sensitive data)
        unset($item['stock_quantity']);
    }

    // Reindex array after removing items
    $cartItems = array_values($cartItems);

    // -------------------------
    // Calculate shipping and totals (SINGLE CALCULATION)
    // -------------------------
    $shippingCost = 0;
    $shippingInfo = null;
    $totalWeight = 0;

    if ($defaultAddress && !empty($defaultAddress['province_id']) && !empty($cartItems)) {
        // Calculate order total with shipping using ShippingCalculator
        $orderCalculation = $shippingCalculator->calculateOrderTotal($cartItems, $defaultAddress['province_id']);
        
        if ($orderCalculation['success']) {
            $shippingCost = $orderCalculation['shipping']['cost'];
            $shippingInfo = $orderCalculation['shipping'];
            $totalWeight = $orderCalculation['total_weight'];
            
            error_log("Shipping calculation successful: Cost={$shippingCost}, Weight={$totalWeight}kg");
        } else {
            error_log("Shipping calculation error: " . $orderCalculation['error']);
            // Fallback to default shipping
            $totalWeight = $shippingCalculator->calculateTotalWeight($cartItems);
            $shippingCost = $totalAmount >= 1000 ? 0 : 50;
        }
    } else {
        // No address or empty cart - use fallback
        $totalWeight = !empty($cartItems) ? $shippingCalculator->calculateTotalWeight($cartItems) : 0;
        $shippingCost = $totalAmount >= 1000 ? 0 : 50;
        error_log("Using fallback shipping: No address or empty cart. Weight={$totalWeight}kg, Cost={$shippingCost}");
    }

    // Calculate tax (7% on subtotal only)
    $taxRate = 0.07;
    $taxAmount = round($totalAmount * $taxRate, 2);
    
    // Calculate grand total
    $grandTotal = round($totalAmount + $shippingCost + $taxAmount, 2);

    // -------------------------
    // Return response with complete image data
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
            'grandTotal' => $grandTotal
        ]
    ];

    if (!empty($outOfStockItems)) {
        $response['warnings'] = [
            'out_of_stock' => 'สินค้าบางรายการมี stock ไม่เพียงพอ: ' . implode(', ', $outOfStockItems)
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