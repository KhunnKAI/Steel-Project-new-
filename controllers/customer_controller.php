<?php
/**
 * CustomerController - Handle customer database operations
 */
class CustomerController
{
    private $pdo;
    private $table = 'Users'; // เปลี่ยนชื่อตารางตามที่คุณใช้จริง

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get all customers with pagination
     */
    public function getAll($limit = 20, $offset = 0)
    {
        try {
            $sql = "SELECT user_id, name, email, phone, created_at, updated_at 
                    FROM {$this->table} 
                    ORDER BY created_at DESC 
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get all customers error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get customer by ID
     */
    public function getById($customer_id)
    {
        try {
            $sql = "SELECT user_id, name, email, phone, password_hash, created_at, updated_at 
                    FROM {$this->table} 
                    WHERE user_id = :customer_id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get customer by ID error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create new customer
     */
    public function create($name, $email, $phone, $password)
    {
        try {
            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO {$this->table} (name, email, phone, password_hash) 
                    VALUES (:name, :email, :phone, :password_hash)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':password_hash', $password_hash);
            
            if ($stmt->execute()) {
                return $this->pdo->lastInsertId();
            }
            return false;
        } catch (Exception $e) {
            error_log("Create customer error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update customer
     */
    public function update($customer_id, $name, $email, $phone)
    {
        try {
            $sql = "UPDATE {$this->table} 
                    SET name = :name, email = :email, phone = :phone, updated_at = CURRENT_TIMESTAMP 
                    WHERE user_id = :customer_id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':phone', $phone);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Update customer error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete customer
     */
    public function delete($customer_id)
    {
        try {
            $sql = "DELETE FROM {$this->table} WHERE user_id = :customer_id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Delete customer error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if email exists
     */
    public function emailExists($email, $exclude_id = null)
    {
        try {
            $sql = "SELECT user_id FROM {$this->table} WHERE email = :email";
            
            if ($exclude_id) {
                $sql .= " AND user_id != :exclude_id";
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':email', $email);
            
            if ($exclude_id) {
                $stmt->bindParam(':exclude_id', $exclude_id, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log("Check email exists error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get total count of customers
     */
    public function getCount()
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table}";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
        } catch (Exception $e) {
            error_log("Get customer count error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Search customers
     */
    public function search($query, $field = 'all', $limit = 20, $offset = 0)
    {
        try {
            $search_query = "%{$query}%";
            
            // Build WHERE clause based on field
            switch ($field) {
                case 'name':
                    $where = "name LIKE :query";
                    break;
                case 'email':
                    $where = "email LIKE :query";
                    break;
                case 'phone':
                    $where = "phone LIKE :query";
                    break;
                default: // 'all'
                    $where = "(name LIKE :query OR email LIKE :query OR phone LIKE :query)";
                    break;
            }
            
            $sql = "SELECT user_id, name, email, phone, created_at, updated_at 
                    FROM {$this->table} 
                    WHERE {$where} 
                    ORDER BY created_at DESC 
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':query', $search_query);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Search customers error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get search results count
     */
    public function getSearchCount($query, $field = 'all')
    {
        try {
            $search_query = "%{$query}%";
            
            // Build WHERE clause based on field
            switch ($field) {
                case 'name':
                    $where = "name LIKE :query";
                    break;
                case 'email':
                    $where = "email LIKE :query";
                    break;
                case 'phone':
                    $where = "phone LIKE :query";
                    break;
                default: // 'all'
                    $where = "(name LIKE :query OR email LIKE :query OR phone LIKE :query)";
                    break;
            }
            
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE {$where}";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':query', $search_query);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
        } catch (Exception $e) {
            error_log("Get search count error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Update customer password
     */
    public function updatePassword($customer_id, $new_password)
    {
        try {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            $sql = "UPDATE {$this->table} 
                    SET password_hash = :password_hash, updated_at = CURRENT_TIMESTAMP 
                    WHERE user_id = :customer_id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
            $stmt->bindParam(':password_hash', $password_hash);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Update customer password error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Customer login validation
     */
    public function login($email, $password)
    {
        try {
            $sql = "SELECT user_id, name, email, phone, password_hash, created_at 
                    FROM {$this->table} 
                    WHERE email = :email";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($customer && password_verify($password, $customer['password_hash'])) {
                // Remove password hash from return data
                unset($customer['password_hash']);
                return $customer;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Customer login error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get customers by date range
     */
    public function getByDateRange($start_date, $end_date, $limit = 50, $offset = 0)
    {
        try {
            $sql = "SELECT user_id, name, email, phone, created_at, updated_at 
                    FROM {$this->table} 
                    WHERE DATE(created_at) BETWEEN :start_date AND :end_date 
                    ORDER BY created_at DESC 
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get customers by date range error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get customer statistics
     */
    public function getStatistics()
    {
        try {
            $stats = [];
            
            // Total customers
            $sql = "SELECT COUNT(*) as total FROM {$this->table}";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['total_customers'] = $result['total'] ?? 0;
            
            // New customers this month
            $sql = "SELECT COUNT(*) as new_this_month 
                    FROM {$this->table} 
                    WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
                    AND YEAR(created_at) = YEAR(CURRENT_DATE())";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['new_this_month'] = $result['new_this_month'] ?? 0;
            
            // New customers today
            $sql = "SELECT COUNT(*) as new_today 
                    FROM {$this->table} 
                    WHERE DATE(created_at) = CURRENT_DATE()";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['new_today'] = $result['new_today'] ?? 0;
            
            // Recently updated (last 7 days)
            $sql = "SELECT COUNT(*) as updated_recently 
                    FROM {$this->table} 
                    WHERE updated_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['updated_recently'] = $result['updated_recently'] ?? 0;
            
            return $stats;
        } catch (Exception $e) {
            error_log("Get customer statistics error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Bulk delete customers
     */
    public function bulkDelete($customer_ids)
    {
        try {
            if (empty($customer_ids) || !is_array($customer_ids)) {
                return false;
            }
            
            $placeholders = str_repeat('?,', count($customer_ids) - 1) . '?';
            $sql = "DELETE FROM {$this->table} WHERE user_id IN ({$placeholders})";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($customer_ids);
        } catch (Exception $e) {
            error_log("Bulk delete customers error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Export customers to CSV format
     */
    public function exportToArray($start_date = null, $end_date = null)
    {
        try {
            $sql = "SELECT user_id, name, email, phone, created_at, updated_at 
                    FROM {$this->table}";
            
            if ($start_date && $end_date) {
                $sql .= " WHERE DATE(created_at) BETWEEN :start_date AND :end_date";
            }
            
            $sql .= " ORDER BY created_at DESC";
            
            $stmt = $this->pdo->prepare($sql);
            
            if ($start_date && $end_date) {
                $stmt->bindParam(':start_date', $start_date);
                $stmt->bindParam(':end_date', $end_date);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Export customers error: " . $e->getMessage());
            return [];
        }
    }
}
?>