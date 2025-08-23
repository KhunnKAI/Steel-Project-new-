<?php
// UserController.php - User management controller with custom user_id format
class UserController
{
    private $pdo;
    private $table = 'Users';

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        
        // Test connection on initialization
        if (!$pdo) {
            throw new Exception('Database connection is null');
        }
    }

    /**
     * Generate next user ID in format user001, user002, etc.
     */
    private function generateNextUserId()
    {
        try {
            // หาหมายเลข user_id ที่ใหญ่ที่สุด
            $stmt = $this->pdo->prepare("
                SELECT user_id FROM {$this->table} 
                WHERE user_id LIKE 'user%' 
                ORDER BY CAST(SUBSTRING(user_id, 5) AS UNSIGNED) DESC 
                LIMIT 1
            ");
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && $result['user_id']) {
                // ดึงตัวเลขจาก user_id ที่มีอยู่
                $last_number = intval(substr($result['user_id'], 4));
                $next_number = $last_number + 1;
            } else {
                // ถ้าไม่มีข้อมูลเลย เริ่มจาก 1
                $next_number = 1;
            }
            
            // สร้าง user_id ใหม่ในรูปแบบ user001, user002
            $new_user_id = 'user' . str_pad($next_number, 3, '0', STR_PAD_LEFT);
            
            error_log("UserController: Generated new user_id: " . $new_user_id);
            
            return $new_user_id;
            
        } catch (PDOException $e) {
            error_log("Generate user ID error: " . $e->getMessage());
            // Fallback: สร้างจาก timestamp
            return 'user' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        }
    }

    /**
     * Check if user_id already exists
     */
    private function userIdExists($user_id)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->table} WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Check user ID exists error: " . $e->getMessage());
            return true; // ถ้าเกิดข้อผิดพลาด ให้ถือว่ามีอยู่แล้วเพื่อความปลอดภัย
        }
    }

    /**
     * Authenticate user login
     */
    public function login($email, $password)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE email = :email");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Update last login time
                $this->updateLastLogin($user['user_id']);
                return $user;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create new user - แก้ไขให้สร้าง user_id แบบ user001, user002
     */
    public function create($name, $email, $phone, $password)
    {
        try {
            // Log creation attempt
            error_log("UserController: Attempting to create user - Email: " . $email);
            
            // Check if table exists first
            $stmt = $this->pdo->query("SHOW TABLES LIKE '{$this->table}'");
            if ($stmt->rowCount() == 0) {
                error_log("UserController: Table {$this->table} does not exist");
                throw new Exception("Table {$this->table} does not exist");
            }
            
            // เริ่ม transaction เพื่อป้องกันการสร้าง user_id ซ้ำ
            $this->pdo->beginTransaction();
            
            // สร้าง user_id ใหม่
            $max_attempts = 10;
            $attempt = 0;
            
            do {
                $new_user_id = $this->generateNextUserId();
                $attempt++;
                
                if ($attempt >= $max_attempts) {
                    throw new Exception("Cannot generate unique user_id after {$max_attempts} attempts");
                }
                
            } while ($this->userIdExists($new_user_id));
            
            error_log("UserController: Generated unique user_id: " . $new_user_id);
            
            // Hash the password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            error_log("UserController: Password hashed successfully");
            
            // Prepare SQL statement - รวม user_id ที่สร้างขึ้น
            $sql = "INSERT INTO {$this->table} (user_id, name, email, phone, password_hash, created_at, updated_at) 
                    VALUES (:user_id, :name, :email, :phone, :password_hash, NOW(), NOW())";
            
            error_log("UserController: Preparing SQL: " . $sql);
            
            $stmt = $this->pdo->prepare($sql);
            
            // Bind parameters with validation
            $stmt->bindValue(':user_id', $new_user_id, PDO::PARAM_STR);
            $stmt->bindValue(':name', $name, PDO::PARAM_STR);
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $stmt->bindValue(':phone', $phone, PDO::PARAM_STR);
            $stmt->bindValue(':password_hash', $password_hash, PDO::PARAM_STR);
            
            error_log("UserController: Parameters bound successfully");
            
            // Execute the statement
            $result = $stmt->execute();
            
            if ($result) {
                // Commit transaction
                $this->pdo->commit();
                
                error_log("UserController: User created successfully with ID: " . $new_user_id);
                
                // คืนค่า array ที่มี user_id ที่สร้างใหม่
                return [
                    'success' => true,
                    'user_id' => $new_user_id,
                    'message' => 'User created successfully'
                ];
            } else {
                // Rollback transaction
                $this->pdo->rollback();
                error_log("UserController: Execute returned false");
                return [
                    'success' => false,
                    'message' => 'Failed to create user'
                ];
            }
            
        } catch (PDOException $e) {
            // Rollback transaction on error
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollback();
            }
            
            error_log("UserController create PDO error: " . $e->getMessage());
            error_log("UserController create PDO code: " . $e->getCode());
            error_log("UserController create SQL State: " . $e->errorInfo[0] ?? 'unknown');
            
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        } catch (Exception $e) {
            // Rollback transaction on error
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollback();
            }
            
            error_log("UserController create general error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get user by ID
     */
    public function getById($user_id)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_STR); // เปลี่ยนเป็น STR
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get user by ID error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user by email
     */
    public function getByEmail($email)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE email = :email");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get user by email error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all users
     */
    public function getAll()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT user_id, name, email, phone, created_at, updated_at 
                FROM {$this->table} 
                ORDER BY CAST(SUBSTRING(user_id, 5) AS UNSIGNED) DESC
            ");
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get all users error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update user information
     */
    public function update($user_id, $name, $email, $phone)
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE {$this->table} 
                SET name = :name, email = :email, phone = :phone, updated_at = NOW() 
                WHERE user_id = :user_id
            ");
            
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_STR); // เปลี่ยนเป็น STR
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Update user error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update user password
     */
    public function updatePassword($user_id, $new_password)
    {
        try {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            $stmt = $this->pdo->prepare("
                UPDATE {$this->table} 
                SET password_hash = :password_hash, updated_at = NOW() 
                WHERE user_id = :user_id
            ");
            
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_STR); // เปลี่ยนเป็น STR
            $stmt->bindParam(':password_hash', $password_hash, PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Update password error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete user
     */
    public function delete($user_id)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_STR); // เปลี่ยนเป็น STR
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Delete user error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if email exists - แก้ไขและเพิ่ม debug
     */
    public function emailExists($email, $exclude_user_id = null)
    {
        try {
            error_log("UserController: Checking email exists for: " . $email);
            
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE email = :email";
            $params = [':email' => $email];
            
            if ($exclude_user_id) {
                $sql .= " AND user_id != :user_id";
                $params[':user_id'] = $exclude_user_id;
            }
            
            error_log("UserController: Email check SQL: " . $sql);
            error_log("UserController: Email check params: " . print_r($params, true));
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $exists = ($result && $result['count'] > 0);
            
            error_log("UserController: Email exists result: " . ($exists ? 'true' : 'false'));
            
            return $exists;
        } catch (PDOException $e) {
            error_log("UserController email exists PDO error: " . $e->getMessage());
            error_log("UserController email exists SQL: " . ($sql ?? 'N/A'));
            error_log("UserController email exists Params: " . print_r($params ?? [], true));
            
            // Return false to allow signup attempt (safer than blocking)
            return false;
        }
    }

    /**
     * Update last login timestamp
     */
    private function updateLastLogin($user_id)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE {$this->table} SET updated_at = NOW() WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_STR); // เปลี่ยนเป็น STR
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Update last login error: " . $e->getMessage());
        }
    }

    /**
     * Get user count
     */
    public function getCount()
    {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->table}");
            $stmt->execute();
            
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Get user count error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Debug method - ตรวจสอบ table structure
     */
    public function debugTableStructure()
    {
        try {
            // Check if table exists
            $stmt = $this->pdo->query("SHOW TABLES LIKE '{$this->table}'");
            $table_exists = $stmt->rowCount() > 0;
            
            if (!$table_exists) {
                return [
                    'table_exists' => false,
                    'error' => 'Table Users does not exist'
                ];
            }
            
            // Get table structure
            $stmt = $this->pdo->query("DESCRIBE {$this->table}");
            $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'table_exists' => true,
                'structure' => $structure
            ];
        } catch (PDOException $e) {
            return [
                'table_exists' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Debug method - ใช้สำหรับ debug ปัญหา
     */
    public function debugEmailCheck($email)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT email, user_id FROM {$this->table} WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Debug email check for: " . $email);
            error_log("Debug email check results: " . print_r($result, true));
            
            return $result;
        } catch (PDOException $e) {
            error_log("Debug email check error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Debug method - แสดง user_id ที่มีอยู่ทั้งหมด
     */
    public function debugUserIds()
    {
        try {
            $stmt = $this->pdo->prepare("SELECT user_id FROM {$this->table} ORDER BY user_id");
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            error_log("Debug: All existing user_ids: " . print_r($result, true));
            
            return $result;
        } catch (PDOException $e) {
            error_log("Debug user IDs error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Test method - ทดสอบการสร้าง user_id
     */
    public function testGenerateUserId()
    {
        return $this->generateNextUserId();
    }
}
?>