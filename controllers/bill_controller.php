<?php
// bill_controller.php - BillController class for handling orders and bills

class BillController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get orders count for testing
     */
    public function getOrdersCount()
    {
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM Orders");
            $result = $stmt->fetch();
            return $result['count'];
        } catch (Exception $e) {
            error_log("Get orders count error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Create new order
     */
    public function createOrder($user_id, $total_amount, $total_novat, $shipping_fee, $status = 'pending', $note = '', $items = [])
    {
        try {
            // Start transaction
            $this->pdo->beginTransaction();

            // Generate order ID
            $order_id = $this->generateOrderId();

            // Insert main order record
            $stmt = $this->pdo->prepare("
                INSERT INTO Orders (order_id, user_id, total_amount, total_novat, shipping_fee, status, note, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $result = $stmt->execute([
                $order_id,
                $user_id, 
                $total_amount,
                $total_novat,
                $shipping_fee,
                $status,
                $note
            ]);

            if (!$result) {
                throw new Exception('Failed to create order');
            }

            // Log order creation in StockLog
            foreach ($items as $item) {
                $this->logStockChange(
                    $item['product_id'] ?? '',
                    $user_id,
                    'out',
                    $item['quantity'] ?? 0,
                    $item['quantity_before'] ?? 0,
                    ($item['quantity_before'] ?? 0) - ($item['quantity'] ?? 0),
                    'order',
                    $order_id
                );
            }

            // Commit transaction
            $this->pdo->commit();

            return $order_id;
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->pdo->rollback();
            error_log("Create order error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get single order by ID
     */
    public function getOrder($order_id, $user_id = null)
    {
        try {
            $sql = "
                SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone
                FROM Orders o 
                LEFT JOIN Users u ON o.user_id = u.user_id 
                WHERE o.order_id = ?
            ";
            
            $params = [$order_id];
            
            // If user_id is provided, add user restriction
            if ($user_id) {
                $sql .= " AND o.user_id = ?";
                $params[] = $user_id;
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($order) {
                // Get order items from StockLog
                $order['items'] = $this->getOrderItems($order_id);
            }

            return $order;
        } catch (Exception $e) {
            error_log("Get order error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all orders with pagination and filtering
     */
    public function getOrders($page = 1, $limit = 10, $status = '', $search = '')
    {
        try {
            $offset = ($page - 1) * $limit;
            
            $sql = "
                SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone
                FROM Orders o 
                LEFT JOIN Users u ON o.user_id = u.user_id 
                WHERE 1=1
            ";
            
            $params = [];
            
            // Add status filter
            if (!empty($status)) {
                $sql .= " AND o.status = ?";
                $params[] = $status;
            }
            
            // Add search filter
            if (!empty($search)) {
                $sql .= " AND (o.order_id LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
                $search_term = "%{$search}%";
                $params[] = $search_term;
                $params[] = $search_term;
                $params[] = $search_term;
            }
            
            // Get total count for pagination
            $count_sql = str_replace("SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone", "SELECT COUNT(*)", $sql);
            $count_stmt = $this->pdo->prepare($count_sql);
            $count_stmt->execute($params);
            $total = $count_stmt->fetchColumn();
            
            // Add ordering and pagination
            $sql .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get items for each order
            foreach ($orders as &$order) {
                $order['items'] = $this->getOrderItems($order['order_id']);
            }

            return [
                'orders' => $orders,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'total_pages' => ceil($total / $limit)
                ]
            ];
        } catch (Exception $e) {
            error_log("Get orders error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get orders for specific user
     */
    public function getUserOrders($user_id, $page = 1, $limit = 10, $status = '')
    {
        try {
            $offset = ($page - 1) * $limit;
            
            $sql = "
                SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone
                FROM Orders o 
                LEFT JOIN Users u ON o.user_id = u.user_id 
                WHERE o.user_id = ?
            ";
            
            $params = [$user_id];
            
            // Add status filter
            if (!empty($status)) {
                $sql .= " AND o.status = ?";
                $params[] = $status;
            }
            
            // Get total count
            $count_sql = str_replace("SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone", "SELECT COUNT(*)", $sql);
            $count_stmt = $this->pdo->prepare($count_sql);
            $count_stmt->execute($params);
            $total = $count_stmt->fetchColumn();
            
            // Add ordering and pagination
            $sql .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get items for each order
            foreach ($orders as &$order) {
                $order['items'] = $this->getOrderItems($order['order_id']);
            }

            return [
                'orders' => $orders,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'total_pages' => ceil($total / $limit)
                ]
            ];
        } catch (Exception $e) {
            error_log("Get user orders error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update order status
     */
    public function updateOrderStatus($order_id, $status, $admin_id = null, $note = '')
    {
        try {
            // Start transaction
            $this->pdo->beginTransaction();

            // Update order status
            $stmt = $this->pdo->prepare("
                UPDATE Orders 
                SET status = ?, updated_at = NOW() 
                WHERE order_id = ?
            ");
            
            $result = $stmt->execute([$status, $order_id]);

            if (!$result) {
                throw new Exception('Failed to update order status');
            }

            // Log status change in StockLog
            $this->logStockChange(
                '', // No specific product
                $admin_id ?: 'system',
                'adjust',
                0, // No quantity change
                0,
                0,
                'status_update',
                $order_id,
                $admin_id ?: null,
                "Status changed to: {$status}. " . ($note ? "Note: {$note}" : '')
            );

            // Commit transaction
            $this->pdo->commit();

            return true;
        } catch (Exception $e) {
            $this->pdo->rollback();
            error_log("Update order status error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cancel order
     */
    public function cancelOrder($order_id, $user_id, $reason = '')
    {
        try {
            // Check if order can be cancelled
            $order = $this->getOrder($order_id, $user_id);
            if (!$order) {
                return false;
            }

            if (in_array($order['status'], ['shipped', 'delivered', 'cancelled'])) {
                return false; // Cannot cancel already processed orders
            }

            // Start transaction
            $this->pdo->beginTransaction();

            // Update order status to cancelled
            $stmt = $this->pdo->prepare("
                UPDATE Orders 
                SET status = 'cancelled', updated_at = NOW() 
                WHERE order_id = ? AND user_id = ?
            ");
            
            $result = $stmt->execute([$order_id, $user_id]);

            if (!$result) {
                throw new Exception('Failed to cancel order');
            }

            // Restore stock by logging reverse transactions
            $items = $this->getOrderItems($order_id);
            foreach ($items as $item) {
                $this->logStockChange(
                    $item['product_id'] ?? '',
                    $user_id,
                    'in', // Restore stock
                    abs($item['quantity_change'] ?? 0),
                    $item['quantity_after'] ?? 0,
                    ($item['quantity_after'] ?? 0) + abs($item['quantity_change'] ?? 0),
                    'cancel',
                    $order_id,
                    null,
                    "Order cancelled. " . ($reason ? "Reason: {$reason}" : '')
                );
            }

            // Commit transaction
            $this->pdo->commit();

            return true;
        } catch (Exception $e) {
            $this->pdo->rollback();
            error_log("Cancel order error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get order statistics
     */
    public function getOrderStats($start_date = '', $end_date = '', $user_id = '')
    {
        try {
            $sql = "
                SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_orders,
                    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
                    SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
                    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
                    SUM(total_amount) as total_revenue,
                    AVG(total_amount) as average_order_value,
                    SUM(CASE WHEN status IN ('delivered', 'shipped') THEN total_amount ELSE 0 END) as completed_revenue
                FROM Orders 
                WHERE 1=1
            ";
            
            $params = [];
            
            // Add date filters
            if (!empty($start_date)) {
                $sql .= " AND DATE(created_at) >= ?";
                $params[] = $start_date;
            }
            
            if (!empty($end_date)) {
                $sql .= " AND DATE(created_at) <= ?";
                $params[] = $end_date;
            }
            
            // Add user filter
            if (!empty($user_id)) {
                $sql .= " AND user_id = ?";
                $params[] = $user_id;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get daily sales data for chart
            $daily_sql = "
                SELECT 
                    DATE(created_at) as order_date,
                    COUNT(*) as daily_orders,
                    SUM(total_amount) as daily_revenue
                FROM Orders 
                WHERE 1=1
            ";
            
            $daily_params = [];
            
            if (!empty($start_date)) {
                $daily_sql .= " AND DATE(created_at) >= ?";
                $daily_params[] = $start_date;
            } else {
                $daily_sql .= " AND DATE(created_at) >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            }
            
            if (!empty($end_date)) {
                $daily_sql .= " AND DATE(created_at) <= ?";
                $daily_params[] = $end_date;
            }
            
            if (!empty($user_id)) {
                $daily_sql .= " AND user_id = ?";
                $daily_params[] = $user_id;
            }
            
            $daily_sql .= " GROUP BY DATE(created_at) ORDER BY order_date";
            
            $daily_stmt = $this->pdo->prepare($daily_sql);
            $daily_stmt->execute($daily_params);
            $daily_stats = $daily_stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'summary' => $stats,
                'daily_data' => $daily_stats
            ];
        } catch (Exception $e) {
            error_log("Get order stats error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get order items from StockLog
     */
    private function getOrderItems($order_id)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    product_id,
                    ABS(quantity_change) as quantity,
                    quantity_before,
                    quantity_after,
                    reference_type,
                    reference_id,
                    note
                FROM StockLog 
                WHERE reference_type = 'order' AND reference_id = ?
                ORDER BY created_at
            ");
            
            $stmt->execute([$order_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get order items error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Log stock changes in StockLog table
     */
    private function logStockChange($product_id, $user_id, $change_type, $quantity_change, $quantity_before, $quantity_after, $reference_type, $reference_id, $admin_id = null, $note = '')
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO StockLog 
                (product_id, user_id, change_type, quantity_change, quantity_before, quantity_after, reference_type, reference_id, admin_id, note, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            return $stmt->execute([
                $product_id,
                $user_id,
                $change_type,
                ($change_type === 'out') ? -abs($quantity_change) : abs($quantity_change),
                $quantity_before,
                $quantity_after,
                $reference_type,
                $reference_id,
                $admin_id,
                $note
            ]);
        } catch (Exception $e) {
            error_log("Log stock change error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate unique order ID
     */
    private function generateOrderId()
    {
        $prefix = 'ORD';
        $date = date('Ymd');
        
        // Get the last order number for today
        $stmt = $this->pdo->prepare("
            SELECT order_id 
            FROM Orders 
            WHERE order_id LIKE ? 
            ORDER BY order_id DESC 
            LIMIT 1
        ");
        
        $stmt->execute(["{$prefix}{$date}%"]);
        $last_order = $stmt->fetch();
        
        if ($last_order) {
            // Extract the number part and increment
            $last_number = intval(substr($last_order['order_id'], -4));
            $new_number = $last_number + 1;
        } else {
            $new_number = 1;
        }
        
        // Format with leading zeros
        $order_number = str_pad($new_number, 4, '0', STR_PAD_LEFT);
        
        return "{$prefix}{$date}{$order_number}";
    }

    /**
     * Get order by tracking number (for public tracking)
     */
    public function getOrderByTrackingNumber($tracking_number)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    order_id,
                    status,
                    total_amount,
                    created_at,
                    updated_at
                FROM Orders 
                WHERE order_id = ?
            ");
            
            $stmt->execute([$tracking_number]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($order) {
                // Don't include sensitive information for public tracking
                $order['items_count'] = count($this->getOrderItems($order['order_id']));
            }

            return $order;
        } catch (Exception $e) {
            error_log("Get order by tracking error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get order status history
     */
    public function getOrderStatusHistory($order_id)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    change_type,
                    note,
                    admin_id,
                    created_at
                FROM StockLog 
                WHERE reference_type IN ('order', 'status_update', 'cancel') 
                AND reference_id = ?
                ORDER BY created_at ASC
            ");
            
            $stmt->execute([$order_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get order status history error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if order exists and belongs to user
     */
    public function verifyOrderOwnership($order_id, $user_id)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count 
                FROM Orders 
                WHERE order_id = ? AND user_id = ?
            ");
            
            $stmt->execute([$order_id, $user_id]);
            $result = $stmt->fetch();
            
            return $result['count'] > 0;
        } catch (Exception $e) {
            error_log("Verify order ownership error: " . $e->getMessage());
            return false;
        }
    }
}
?>