<?php
// ========================
// STOCK LOGGER CLASS
// ========================

/**
 * CLASS: StockLogger - Centralized stock logging system
 * 
 * บริหารจัดการการบันทึกและอัพเดตสต็อกสินค้าอย่างเป็นศูนย์กลาง
 */
class StockLogger {
    private $pdo;
    
    // ========================
    // CONSTRUCTOR
    // ========================

    // FUNCTION: สร้าง constructor
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // ========================
    // LOGGING
    // ========================

    /**
     * FUNCTION: บันทึกการเปลี่ยนแปลงสต็อก
     * 
     * @param string $product_id
     * @param string $change_type (in, out, adjust)
     * @param int $quantity_change
     * @param string $reference_type
     * @param string|null $reference_id
     * @param string|null $user_id
     * @param string|null $admin_id
     * @param string|null $note
     * @param int|null $override_quantity_before
     */
    public function logStockChange($product_id, $change_type, $quantity_change, $reference_type, $reference_id = null, $user_id = null, $admin_id = null, $note = null, $override_quantity_before = null) {
        try {
            // FUNCTION: ดึงสต็อกปัจจุบัน
            if ($override_quantity_before !== null) {
                $quantity_before = $override_quantity_before;
            } else {
                $stmt = $this->pdo->prepare("SELECT stock FROM Product WHERE product_id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$product) {
                    throw new Exception("Product not found: " . $product_id);
                }
                
                $quantity_before = $product['stock'];
            }
            
            // FUNCTION: คำนวณสต็อกใหม่ตามประเภทการเปลี่ยนแปลง
            switch ($change_type) {
                case 'in':
                    $quantity_after = $quantity_before + abs($quantity_change);
                    $actual_quantity_change = abs($quantity_change);
                    break;
                case 'out':
                    $quantity_after = $quantity_before - abs($quantity_change);
                    $actual_quantity_change = -abs($quantity_change);
                    break;
                case 'adjust':
                    $quantity_after = $quantity_before + $quantity_change;
                    $actual_quantity_change = $quantity_change;
                    break;
                default:
                    throw new Exception("Invalid change_type: " . $change_type);
            }
            
            // FUNCTION: ตรวจสอบสต็อกไม่เป็นลบ
            if ($quantity_after < 0) {
                throw new Exception("Insufficient stock. Current: {$quantity_before}, Requested: {$quantity_change}");
            }
            
            // FUNCTION: บันทึกลง StockLog
            $sql = "INSERT INTO StockLog (
                product_id, user_id, change_type, quantity_change, 
                quantity_before, quantity_after, reference_type, 
                reference_id, admin_id, created_at, note
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $product_id,
                $user_id,
                $change_type,
                $actual_quantity_change,
                $quantity_before,
                $quantity_after,
                $reference_type,
                $reference_id,
                $admin_id,
                $note
            ]);
            
            if (!$result) {
                throw new Exception("Failed to insert stock log");
            }
            
            return [
                'success' => true,
                'log_id' => $this->pdo->lastInsertId(),
                'quantity_before' => $quantity_before,
                'quantity_after' => $quantity_after
            ];
            
        } catch (Exception $e) {
            error_log("Stock logging error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // ========================
    // UPDATE STOCK
    // ========================

    /**
     * FUNCTION: อัพเดตสต็อกสินค้ากับการบันทึก
     */
    public function updateProductStock($product_id, $change_type, $quantity_change, $reference_type, $reference_id = null, $user_id = null, $admin_id = null, $note = null) {
        try {
            // FUNCTION: บันทึกการเปลี่ยนแปลงก่อน
            $log_result = $this->logStockChange(
                $product_id, $change_type, $quantity_change, 
                $reference_type, $reference_id, $user_id, $admin_id, $note
            );
            
            if (!$log_result['success']) {
                throw new Exception($log_result['error']);
            }
            
            // FUNCTION: อัพเดตสต็อกในตาราง Product
            $stmt = $this->pdo->prepare("UPDATE Product SET stock = ?, updated_at = NOW() WHERE product_id = ?");
            $update_result = $stmt->execute([$log_result['quantity_after'], $product_id]);
            
            if (!$update_result) {
                throw new Exception("Failed to update product stock");
            }
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("No product found to update: " . $product_id);
            }
            
            return [
                'success' => true,
                'log_id' => $log_result['log_id'],
                'quantity_before' => $log_result['quantity_before'],
                'quantity_after' => $log_result['quantity_after']
            ];
            
        } catch (Exception $e) {
            error_log("Stock update error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // ========================
    // INITIAL STOCK
    // ========================

    /**
     * FUNCTION: เพิ่มสต็อกเริ่มต้นสำหรับสินค้าใหม่
     */
    public function addInitialStock($product_id, $initial_stock, $admin_id = null, $note = null) {
        try {
            // FUNCTION: ตรวจสอบสต็อกเริ่มต้น
            if ($initial_stock <= 0) {
                return ['success' => true, 'message' => 'No initial stock to add'];
            }
            
            // FUNCTION: บันทึกการเปลี่ยนแปลงด้วยสต็อกเริ่มต้น = 0
            $log_result = $this->logStockChange(
                $product_id,
                'in',
                $initial_stock,
                'receive',
                null,
                null,
                $admin_id,
                $note ?: "Initial stock for new product",
                0
            );
            
            if (!$log_result['success']) {
                throw new Exception($log_result['error']);
            }
            
            // FUNCTION: อัพเดตสต็อกสินค้า
            $stmt = $this->pdo->prepare("UPDATE Product SET stock = ?, updated_at = NOW() WHERE product_id = ?");
            $update_result = $stmt->execute([$log_result['quantity_after'], $product_id]);
            
            if (!$update_result) {
                throw new Exception("Failed to update product stock");
            }
            
            return [
                'success' => true,
                'log_id' => $log_result['log_id'],
                'quantity_before' => 0,
                'quantity_after' => $initial_stock
            ];
            
        } catch (Exception $e) {
            error_log("Add initial stock error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // ========================
    // HISTORY & RETRIEVAL
    // ========================

    /**
     * FUNCTION: ดึงประวัติสต็อกของสินค้า
     */
    public function getStockHistory($product_id, $limit = 50) {
        try {
            $sql = "SELECT 
                        sl.*,
                        u.name as user_name,
                        a.fullname as admin_name
                    FROM StockLog sl
                    LEFT JOIN Users u ON sl.user_id = u.user_id
                    LEFT JOIN Admin a ON sl.admin_id = a.admin_id
                    WHERE sl.product_id = ?
                    ORDER BY sl.created_at DESC
                    LIMIT ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$product_id, $limit]);
            
            return [
                'success' => true,
                'history' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
            
        } catch (Exception $e) {
            error_log("Stock history error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * FUNCTION: ดึงสรุปการเคลื่อนไหวสต็อก
     */
    public function getStockMovements($filters = [], $limit = 100) {
        try {
            // FUNCTION: สร้างเงื่อนไข WHERE
            $where_conditions = [];
            $params = [];
            
            if (!empty($filters['product_id'])) {
                $where_conditions[] = "sl.product_id = ?";
                $params[] = $filters['product_id'];
            }
            
            if (!empty($filters['change_type'])) {
                $where_conditions[] = "sl.change_type = ?";
                $params[] = $filters['change_type'];
            }
            
            if (!empty($filters['reference_type'])) {
                $where_conditions[] = "sl.reference_type = ?";
                $params[] = $filters['reference_type'];
            }
            
            if (!empty($filters['date_from'])) {
                $where_conditions[] = "DATE(sl.created_at) >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $where_conditions[] = "DATE(sl.created_at) <= ?";
                $params[] = $filters['date_to'];
            }
            
            $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
            
            // FUNCTION: ดึงการเคลื่อนไหว
            $sql = "SELECT 
                        sl.*,
                        p.name as product_name,
                        u.name as user_name,
                        a.fullname as admin_name
                    FROM StockLog sl
                    LEFT JOIN Product p ON sl.product_id = p.product_id
                    LEFT JOIN Users u ON sl.user_id = u.user_id
                    LEFT JOIN Admin a ON sl.admin_id = a.admin_id
                    {$where_clause}
                    ORDER BY sl.created_at DESC
                    LIMIT ?";
            
            $params[] = $limit;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return [
                'success' => true,
                'movements' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
            
        } catch (Exception $e) {
            error_log("Stock movements error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

?>