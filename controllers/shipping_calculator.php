<?php
class ShippingCalculator {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Calculate shipping cost based on total weight and destination province
     * @param float $totalWeight - Total weight in kg
     * @param string $provinceId - Province ID (P01, P02, etc.)
     * @return array - Contains shipping cost and zone info
     */
    public function calculateShippingCost($totalWeight, $provinceId) {
        try {
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
            
            // Get shipping rate for the zone and weight - fixed parameter binding
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
                'total_weight' => $totalWeight
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
     * Calculate total weight from cart items
     * @param array $cartItems - Cart items with product_id and quantity
     * @return float - Total weight in kg
     */
    public function calculateTotalWeight($cartItems) {
        if (empty($cartItems)) {
            return 0;
        }
        
        $totalWeight = 0;
        
        // Check if cart items already have weight information
        $firstItem = $cartItems[0];
        if (isset($firstItem['weight']) && isset($firstItem['quantity'])) {
            // Weight data is already in cart items (from get_cart.php query)
            foreach ($cartItems as $item) {
                $weight = floatval($item['weight'] ?? 0);
                $quantity = intval($item['quantity'] ?? 0);
                $unit = strtolower($item['weight_unit'] ?? 'kg');
                
                // Convert weight to kg if necessary
                if ($unit === 'g' || $unit === 'gram' || $unit === 'grams') {
                    $weight = $weight / 1000; // Convert grams to kg
                } elseif ($unit === 'mg' || $unit === 'milligram' || $unit === 'milligrams') {
                    $weight = $weight / 1000000; // Convert milligrams to kg
                } elseif ($unit === 'lb' || $unit === 'pound' || $unit === 'pounds') {
                    $weight = $weight * 0.453592; // Convert pounds to kg
                }
                
                $totalWeight += $weight * $quantity;
            }
        } else {
            // Need to fetch weight from database (for shipping_api.php)
            $productIds = array_column($cartItems, 'product_id');
            if (empty($productIds)) {
                return 0;
            }
            
            $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
            
            $stmt = $this->pdo->prepare("
                SELECT product_id, weight, weight_unit
                FROM product
                WHERE product_id IN ($placeholders)
            ");
            $stmt->execute($productIds);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Create lookup array for products
            $productWeights = [];
            foreach ($products as $product) {
                $weight = floatval($product['weight'] ?? 0);
                
                // Convert weight to kg if necessary
                $unit = strtolower($product['weight_unit'] ?? 'kg');
                if ($unit === 'g' || $unit === 'gram' || $unit === 'grams') {
                    $weight = $weight / 1000; // Convert grams to kg
                } elseif ($unit === 'mg' || $unit === 'milligram' || $unit === 'milligrams') {
                    $weight = $weight / 1000000; // Convert milligrams to kg
                } elseif ($unit === 'lb' || $unit === 'pound' || $unit === 'pounds') {
                    $weight = $weight * 0.453592; // Convert pounds to kg
                }
                
                $productWeights[$product['product_id']] = $weight;
            }
            
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
     * Calculate complete order totals including shipping and tax
     * @param array $cartItems - Cart items
     * @param string $provinceId - Destination province
     * @return array - Complete order calculation
     */
    public function calculateOrderTotal($cartItems, $provinceId) {
        try {
            // Calculate subtotal from cart items
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
            
            // Calculate total weight
            $totalWeight = $this->calculateTotalWeight($cartItems);
            
            // Calculate shipping
            $shippingResult = $this->calculateShippingCost($totalWeight, $provinceId);
            
            if (!$shippingResult['success']) {
                // Fallback shipping calculation
                $shippingCost = $this->getFallbackShipping($subtotal, $totalWeight);
                error_log("Using fallback shipping: " . $shippingResult['error']);
                
                $shippingInfo = [
                    'cost' => $shippingCost,
                    'method' => 'fallback',
                    'note' => 'ใช้อัตราค่าส่งสำรอง เนื่องจาก: ' . $shippingResult['error']
                ];
            } else {
                $shippingCost = $shippingResult['shipping_cost'];
                $shippingInfo = [
                    'cost' => $shippingCost,
                    'method' => 'calculated',
                    'province_info' => $shippingResult['province_info'],
                    'rate_info' => $shippingResult['shipping_rate_info']
                ];
            }
            
            // Calculate tax (7% on subtotal only, not including shipping)
            $taxRate = 0.07;
            $taxAmount = round($subtotal * $taxRate, 2);
            
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
                'grand_total' => $grandTotal
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
     * Get fallback shipping cost
     * @param float $subtotal
     * @param float $totalWeight
     * @return float
     */
    private function getFallbackShipping($subtotal, $totalWeight) {
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
     * @return array
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
     * @param string $zoneId
     * @return array
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
     * @param string $zoneId
     * @return array
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