<?php
class ShippingCalculator {
    private $pdo;
    const MAX_WEIGHT_LIMIT = 1000; // 1000kg limit
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Validate weight limit before any calculations
     * @param float $totalWeight - Total weight in kg
     * @return array - Validation result
     */
    public function validateWeightLimit($totalWeight) {
        if ($totalWeight > self::MAX_WEIGHT_LIMIT) {
            return [
                'success' => false,
                'error' => "น้ำหนักรวม {$totalWeight} กก. เกินขีดจำกัด " . self::MAX_WEIGHT_LIMIT . " กก. ไม่สามารถสั่งซื้อได้",
                'weight' => $totalWeight,
                'limit' => self::MAX_WEIGHT_LIMIT,
                'code' => 'WEIGHT_LIMIT_EXCEEDED'
            ];
        }
        
        return [
            'success' => true,
            'weight' => $totalWeight,
            'limit' => self::MAX_WEIGHT_LIMIT
        ];
    }
    
    /**
     * Calculate shipping cost with weight validation
     * @param float $totalWeight - Total weight in kg
     * @param string $provinceId - Province ID
     * @return array - Contains shipping cost and validation status
     */
    public function calculateShippingCost($totalWeight, $provinceId) {
        try {
            // Check weight limit first
            $weightValidation = $this->validateWeightLimit($totalWeight);
            if (!$weightValidation['success']) {
                return [
                    'success' => false,
                    'shipping_cost' => null,
                    'weight_exceeded' => true,
                    'weight_validation' => $weightValidation,
                    'error' => 'ไม่สามารถคำนวดค่าส่งได้ เนื่องจากน้ำหนักเกินขีดจำกัด'
                ];
            }
            
            // Debug logging
            error_log("Calculating shipping for Province: {$provinceId}, Weight: {$totalWeight}kg");
            
            // Get shipping zone for the province
            $stmt = $this->pdo->prepare("
                SELECT p.province_id, p.name as province_name, p.zone_id,
                    sz.name as zone_name, sz.description as zone_description
                FROM Province p
                LEFT JOIN ShippingZone sz ON p.zone_id = sz.zone_id
                WHERE p.province_id = ?
            ");
            $stmt->execute([$provinceId]);
            $provinceInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$provinceInfo) {
                throw new Exception("Province not found: " . $provinceId);
            }
            
            $zoneId = $provinceInfo['zone_id'];
            error_log("Found zone_id: " . ($zoneId ?? 'NULL') . " for province: " . $provinceInfo['province_name']);
            
            if (empty($zoneId)) {
                throw new Exception("No shipping zone assigned for province: " . $provinceInfo['province_name']);
            }
            
            // Get shipping rate for the zone and weight
            $stmt = $this->pdo->prepare("
                SELECT rate_id, zone_id, min_weight, max_weight, price
                FROM ShippingRate
                WHERE zone_id = ? 
                AND min_weight <= ?
                AND (max_weight >= ? OR max_weight IS NULL)
                ORDER BY min_weight DESC
                LIMIT 1
            ");
            $stmt->execute([$zoneId, $totalWeight, $totalWeight]);
            $shippingRate = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$shippingRate) {
                // If no rate found, try to get the highest weight range rate for this zone
                $stmt = $this->pdo->prepare("
                    SELECT rate_id, zone_id, min_weight, max_weight, price
                    FROM ShippingRate
                    WHERE zone_id = ?
                    ORDER BY max_weight DESC
                    LIMIT 1
                ");
                $stmt->execute([$zoneId]);
                $shippingRate = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$shippingRate) {
                    throw new Exception("No shipping rate found for zone: {$zoneId}");
                }
                
                error_log("Using fallback rate for zone {$zoneId}: " . json_encode($shippingRate));
            } else {
                error_log("Found shipping rate: " . json_encode($shippingRate));
            }
            
            return [
                'success' => true,
                'shipping_cost' => floatval($shippingRate['price']),
                'province_info' => $provinceInfo,
                'shipping_rate_info' => $shippingRate,
                'total_weight' => $totalWeight,
                'weight_validation' => $weightValidation
            ];
            
        } catch (PDOException $e) {
            error_log("PDO Error in shipping calculation: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage(),
                'shipping_cost' => 0
            ];
        } catch (Exception $e) {
            error_log("General error in shipping calculation: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'shipping_cost' => 0
            ];
        }
    }
    
    /**
     * Calculate complete order totals with consistent weight validation
     * @param array $cartItems - Cart items with weight data
     * @param string $provinceId - Destination province
     * @return array - Complete order calculation with validation
     */
    public function calculateOrderTotal($cartItems, $provinceId) {
        try {
            // Calculate subtotal and total items
            $subtotal = 0;
            $totalItems = 0;
            
            if (!empty($cartItems)) {
                foreach ($cartItems as $item) {
                    $price = floatval($item['price'] ?? 0);
                    $quantity = intval($item['quantity'] ?? 0);
                    $subtotal += $price * $quantity;
                    $totalItems += $quantity;
                }
            }
            
            // Calculate total weight consistently
            $totalWeight = $this->calculateTotalWeight($cartItems);
            
            // Validate weight limit BEFORE any shipping calculations
            $weightValidation = $this->validateWeightLimit($totalWeight);
            
            // Calculate tax (7% on subtotal only)
            $taxRate = 0.07;
            $taxAmount = round($subtotal * $taxRate, 2);
            
            // If weight exceeds limit, return error response but include all calculations
            if (!$weightValidation['success']) {
                return [
                    'success' => false,
                    'subtotal' => round($subtotal, 2),
                    'total_items' => $totalItems,
                    'total_weight' => $totalWeight,
                    'shipping' => [
                        'cost' => null,
                        'method' => 'weight_exceeded',
                        'note' => 'ไม่สามารถคำนวดค่าส่งได้ เนื่องจากน้ำหนักเกินขีดจำกัด',
                        'weight_exceeded' => true,
                        'province_id' => $provinceId
                    ],
                    'tax' => [
                        'rate' => $taxRate,
                        'amount' => $taxAmount
                    ],
                    'grand_total' => round($subtotal + $taxAmount, 2), // No shipping cost
                    'weight_validation' => $weightValidation,
                    'can_order' => false,
                    'message' => "น้ำหนักสินค้ารวม {$totalWeight} กก. เกินขีดจำกัดการจัดส่ง " . self::MAX_WEIGHT_LIMIT . " กก."
                ];
            }
            
            // Calculate shipping (weight is within limit)
            $shippingResult = $this->calculateShippingCost($totalWeight, $provinceId);
            
            if (!$shippingResult['success']) {
                // Use fallback shipping for calculation errors (not weight exceeded)
                $shippingCost = $this->getFallbackShipping($subtotal, $totalWeight);
                error_log("Using fallback shipping: " . ($shippingResult['error'] ?? 'Unknown error'));
                
                $shippingInfo = [
                    'cost' => $shippingCost,
                    'method' => 'fallback',
                    'note' => 'ใช้อัตราค่าส่งสำรอง เนื่องจาก: ' . ($shippingResult['error'] ?? 'ข้อผิดพลาดไม่ทราบสาเหตุ'),
                    'province_id' => $provinceId
                ];
            } else {
                $shippingCost = $shippingResult['shipping_cost'];
                $shippingInfo = [
                    'cost' => $shippingCost,
                    'method' => 'calculated',
                    'province_info' => $shippingResult['province_info'],
                    'rate_info' => $shippingResult['shipping_rate_info'],
                    'province_id' => $provinceId
                ];
            }
            
            // Calculate grand total
            $grandTotal = round($subtotal + $shippingCost + $taxAmount, 2);
            
            return [
                'success' => true,
                'subtotal' => round($subtotal, 2),
                'total_items' => $totalItems,
                'total_weight' => $totalWeight,
                'shipping' => $shippingInfo,
                'tax' => [
                    'rate' => $taxRate,
                    'amount' => $taxAmount
                ],
                'grand_total' => $grandTotal,
                'weight_validation' => $weightValidation,
                'can_order' => true,
                'province_id' => $provinceId
            ];
            
        } catch (Exception $e) {
            error_log("Order total calculation error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Calculate total weight from cart items (improved and consistent)
     */
    public function calculateTotalWeight($cartItems) {
        if (empty($cartItems)) {
            return 0;
        }
        
        $totalWeight = 0;
        
        // Check if cart items already have weight information
        $firstItem = $cartItems[0];
        if (isset($firstItem['weight']) && isset($firstItem['quantity'])) {
            // Weight data is already in cart items - use it directly
            foreach ($cartItems as $item) {
                $weight = floatval($item['weight'] ?? 0);
                $quantity = intval($item['quantity'] ?? 0);
                $unit = strtolower($item['weight_unit'] ?? 'kg');
                
                // Convert weight to kg if necessary
                $weight = $this->convertWeightToKg($weight, $unit);
                $totalWeight += $weight * $quantity;
            }
        } else {
            // Need to fetch weight from database
            $productIds = array_column($cartItems, 'product_id');
            if (empty($productIds)) {
                return 0;
            }
            
            $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
            
            $stmt = $this->pdo->prepare("
                SELECT product_id, 
                       COALESCE(weight, 0) as weight, 
                       COALESCE(weight_unit, 'kg') as weight_unit
                FROM Product
                WHERE product_id IN ($placeholders)
            ");
            $stmt->execute($productIds);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Create lookup array for products
            $productWeights = [];
            foreach ($products as $product) {
                $weight = floatval($product['weight'] ?? 0);
                $unit = strtolower($product['weight_unit'] ?? 'kg');
                $productWeights[$product['product_id']] = $this->convertWeightToKg($weight, $unit);
            }
            
            // Calculate total weight from cart quantities
            foreach ($cartItems as $item) {
                $productId = $item['product_id'];
                $quantity = intval($item['quantity'] ?? 0);
                $weight = $productWeights[$productId] ?? 0;
                
                $totalWeight += $weight * $quantity;
            }
        }
        
        return round($totalWeight, 2);
    }
    
    /**
     * Convert weight to kg from various units
     * @param float $weight
     * @param string $unit
     * @return float - Weight in kg
     */
    private function convertWeightToKg($weight, $unit) {
        $unit = strtolower(trim($unit));
        
        switch ($unit) {
            case 'g':
            case 'gram':
            case 'grams':
                return $weight / 1000;
            case 'mg':
            case 'milligram':
            case 'milligrams':
                return $weight / 1000000;
            case 'lb':
            case 'pound':
            case 'pounds':
                return $weight * 0.453592;
            case 'oz':
            case 'ounce':
            case 'ounces':
                return $weight * 0.0283495;
            case 'kg':
            case 'kilogram':
            case 'kilograms':
            default:
                return $weight;
        }
    }
    
    /**
     * Get fallback shipping cost - only for weights within limit
     */
    private function getFallbackShipping($subtotal, $totalWeight) {
        // Don't calculate fallback if weight exceeds limit
        if ($totalWeight > self::MAX_WEIGHT_LIMIT) {
            return 0;
        }
        
        // Free shipping for orders over 1000 THB
        if ($subtotal >= 1000) {
            return 0;
        }
        
        // Weight-based fallback shipping
        if ($totalWeight <= 1) {
            return 50; // Up to 1kg
        } elseif ($totalWeight <= 5) {
            return 80; // 1-5kg
        } elseif ($totalWeight <= 10) {
            return 120; // 5-10kg
        } else {
            return 150 + (ceil($totalWeight - 10) * 15); // Over 10kg, add 15 THB per kg
        }
    }
    
    /**
     * Get all shipping zones
     */
    public function getShippingZones() {
        try {
            $stmt = $this->pdo->query("
                SELECT zone_id, name, description 
                FROM ShippingZone 
                ORDER BY zone_id
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting shipping zones: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get shipping rates for a specific zone
     */
    public function getShippingRatesByZone($zoneId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT rate_id, zone_id, min_weight, max_weight, price
                FROM ShippingRate
                WHERE zone_id = ?
                ORDER BY min_weight ASC
            ");
            $stmt->execute([$zoneId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting shipping rates for zone {$zoneId}: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get provinces by zone
     */
    public function getProvincesByZone($zoneId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT province_id, name
                FROM Province
                WHERE zone_id = ?
                ORDER BY name
            ");
            $stmt->execute([$zoneId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting provinces for zone {$zoneId}: " . $e->getMessage());
            return [];
        }
    }
}
?>