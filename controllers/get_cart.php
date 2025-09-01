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
    // Get cart items with complete product details
    // -------------------------
    $cartItems = [];
    
    // Check if product table exists and get its structure
    $productCheck = $pdo->query("SHOW TABLES LIKE 'Product'");
    if ($productCheck && $productCheck->rowCount() > 0) {
        // Check what columns exist in product table
        $productColumns = $pdo->query("DESCRIBE Product");
        $columns = $productColumns->fetchAll(PDO::FETCH_COLUMN);
        
        $hasStock = in_array('stock', $columns);
        $hasWeight = in_array('weight', $columns);
        $hasWeightUnit = in_array('weight_unit', $columns);
        
        // Build cart query with all necessary fields
        $cartQuery = "
            SELECT 
                c.product_id,
                c.quantity,
                p.name,
                p.price";
        
        // Include stock information
        if ($hasStock) {
            $cartQuery .= ", COALESCE(p.stock, 999) as stock_quantity";
        } else {
            $cartQuery .= ", 999 as stock_quantity";
        }
        
        // Include weight information (critical for shipping calculations)
        if ($hasWeight) {
            $cartQuery .= ", COALESCE(p.weight, 0) as weight";
        } else {
            $cartQuery .= ", 0 as weight";
        }
        
        if ($hasWeightUnit) {
            $cartQuery .= ", COALESCE(p.weight_unit, 'kg') as weight_unit";
        } else {
            $cartQuery .= ", 'kg' as weight_unit";
        }
        
        $cartQuery .= "
            FROM Cart c
            INNER JOIN Product p ON c.product_id = p.product_id
            WHERE c.user_id = :user_id
            ORDER BY c.created_at DESC";
        
        try {
            $stmt = $pdo->prepare($cartQuery);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
            $stmt->execute();
            $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get product images separately
            if (!empty($cartItems)) {
                $productIds = array_column($cartItems, 'product_id');
                $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
                
                // Check if ProductImage table exists
                $imageCheck = $pdo->query("SHOW TABLES LIKE 'Productimage'");
                if ($imageCheck && $imageCheck->rowCount() > 0) {
                    try {
                        $imageQuery = "
                            SELECT DISTINCT
                                p.product_id,
                                CASE 
                                    WHEN pi.image_url IS NOT NULL AND pi.image_url != '' THEN pi.image_url
                                    ELSE 'admin/controllers/uploads/products/default-product.jpg'
                                END as image_url,
                                COALESCE(pi.is_main, 0) as is_main
                            FROM Product p
                            LEFT JOIN Productimage pi ON p.product_id = pi.product_id
                            WHERE p.product_id IN ($placeholders)
                        ";
                        
                        $imageStmt = $pdo->prepare($imageQuery);
                        $imageStmt->execute($productIds);
                        $productImages = $imageStmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        // Create image lookup array
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
                        
                        // Add images to cart items
                        foreach ($cartItems as &$item) {
                            $productId = $item['product_id'];
                            
                            if (isset($imageMap[$productId]) && !empty($imageMap[$productId])) {
                                // Find main image
                                $mainImage = null;
                                $allImages = $imageMap[$productId];
                                
                                foreach ($allImages as $img) {
                                    if ($img['is_main']) {
                                        $mainImage = $img['url'];
                                        break;
                                    }
                                }
                                
                                // Use first image if no main image
                                if (!$mainImage && !empty($allImages)) {
                                    $mainImage = $allImages[0]['url'];
                                }
                                
                                $item['image'] = $mainImage ?: 'admin/controllers/uploads/products/default-product.jpg';
                                $item['images'] = $allImages;
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
                        // Add default images if query fails
                        foreach ($cartItems as &$item) {
                            $item['image'] = 'admin/controllers/uploads/products/default-product.jpg';
                            $item['images'] = [[
                                'url' => 'admin/controllers/uploads/products/default-product.jpg',
                                'is_main' => true
                            ]];
                        }
                    }
                } else {
                    // No ProductImage table - use default images
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
    // Process cart items and handle stock validation
    // -------------------------
    $totalAmount = 0;
    $totalItems = 0;
    $outOfStockItems = [];

    foreach ($cartItems as $index => &$item) {
        // Ensure proper data types
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
                $updateStmt = $pdo->prepare("UPDATE Cart SET quantity = :quantity WHERE user_id = :user_id AND product_id = :product_id");
                $updateStmt->execute([
                    ':quantity' => $item['quantity'],
                    ':user_id' => $userId,
                    ':product_id' => $item['product_id']
                ]);
            } else {
                // Remove from cart if no stock
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
        
        // Clean up response (remove sensitive data)
        unset($item['stock_quantity']);
    }

    // Reindex array after removing items
    $cartItems = array_values($cartItems);

    // -------------------------
    // Calculate shipping and totals using ShippingCalculator
    // -------------------------
    $shippingCost = 0;
    $shippingInfo = null;
    $totalWeight = 0;
    $canOrder = true;
    $weightValidation = null;

    if ($defaultAddress && !empty($defaultAddress['province_id']) && !empty($cartItems)) {
        // Use ShippingCalculator for consistent calculation
        $orderCalculation = $shippingCalculator->calculateOrderTotal($cartItems, $defaultAddress['province_id']);
        
        if ($orderCalculation['success']) {
            $shippingCost = $orderCalculation['shipping']['cost'];
            $shippingInfo = $orderCalculation['shipping'];
            $totalWeight = $orderCalculation['total_weight'];
            $weightValidation = $orderCalculation['weight_validation'];
            $canOrder = $orderCalculation['can_order'];
            
            error_log("Shipping calculation successful: Cost={$shippingCost}, Weight={$totalWeight}kg, CanOrder=" . ($canOrder ? 'true' : 'false'));
        } else {
            // Handle weight exceeded or other calculation errors
            $totalWeight = $shippingCalculator->calculateTotalWeight($cartItems);
            $weightValidation = $shippingCalculator->validateWeightLimit($totalWeight);
            
            if (!$weightValidation['success']) {
                // Weight exceeded - cannot order
                $shippingCost = 0;
                $canOrder = false;
                $shippingInfo = [
                    'cost' => 0,
                    'method' => 'weight_exceeded',
                    'note' => 'ไม่สามารถคำนวดค่าส่งได้ เนื่องจากน้ำหนักเกินขีดจำกัด',
                    'weight_exceeded' => true
                ];
                error_log("Weight exceeded: {$totalWeight}kg > " . ShippingCalculator::MAX_WEIGHT_LIMIT . "kg");
            } else {
                // Other error - use fallback shipping
                $shippingCost = $totalAmount >= 1000 ? 0 : 50;
                $canOrder = true;
                $shippingInfo = [
                    'cost' => $shippingCost,
                    'method' => 'fallback',
                    'note' => 'ใช้อัตราค่าส่งสำรอง: ' . ($orderCalculation['error'] ?? 'ข้อผิดพลาดไม่ทราบสาเหตุ')
                ];
                error_log("Shipping calculation error, using fallback: " . ($orderCalculation['error'] ?? 'Unknown error'));
            }
        }
    } else {
        // No address or empty cart
        if (!empty($cartItems)) {
            $totalWeight = $shippingCalculator->calculateTotalWeight($cartItems);
            $weightValidation = $shippingCalculator->validateWeightLimit($totalWeight);
            
            if (!$weightValidation['success']) {
                $canOrder = false;
                $shippingCost = 0;
            } else {
                $canOrder = true;
                $shippingCost = $totalAmount >= 1000 ? 0 : 50;
            }
        } else {
            $totalWeight = 0;
            $canOrder = true;
            $shippingCost = 0;
            $weightValidation = ['success' => true, 'weight' => 0, 'limit' => ShippingCalculator::MAX_WEIGHT_LIMIT];
        }
        
        error_log("Using fallback shipping: No address or empty cart. Weight={$totalWeight}kg, Cost={$shippingCost}");
    }

    // Calculate tax (7% on subtotal only)
    $taxRate = 0.07;
    $taxAmount = round($totalAmount * $taxRate, 2);
    
    // Calculate grand total
    $grandTotal = round($totalAmount + $shippingCost + $taxAmount, 2);

    // -------------------------
    // Build complete response
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

    // Add weight validation info if available
    if ($weightValidation) {
        $response['cart']['weightValidation'] = $weightValidation;
    }

    // Add warnings for out of stock items
    if (!empty($outOfStockItems)) {
        $response['warnings'] = [
            'out_of_stock' => 'สินค้าบางรายการมี stock ไม่เพียงพอ: ' . implode(', ', $outOfStockItems)
        ];
    }

    // Add weight exceeded warning
    if (!$canOrder && $weightValidation && !$weightValidation['success']) {
        $response['warnings'] = ($response['warnings'] ?? []) + [
            'weight_exceeded' => $weightValidation['error']
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