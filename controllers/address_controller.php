<?php
/**
 * AddressController class for managing Addresses table
 */
class AddressController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Create a new address
     * 
     * @param int $user_id
     * @param string $recipient_name
     * @param string $phone
     * @param string $address_line
     * @param string $subdistrict
     * @param string $district
     * @param string $province
     * @param string $postal_code
     * @return int|false Address ID on success, false on failure
     */
    public function create($user_id, $recipient_name, $phone, $address_line, $subdistrict, $district, $province, $postal_code)
    {
        try {
            $sql = "INSERT INTO Addresses (user_id, recipient_name, phone, address_line, subdistrict, district, province, postal_code) 
                    VALUES (:user_id, :recipient_name, :phone, :address_line, :subdistrict, :district, :province, :postal_code)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':recipient_name', $recipient_name, PDO::PARAM_STR);
            $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
            $stmt->bindParam(':address_line', $address_line, PDO::PARAM_STR);
            $stmt->bindParam(':subdistrict', $subdistrict, PDO::PARAM_STR);
            $stmt->bindParam(':district', $district, PDO::PARAM_STR);
            $stmt->bindParam(':province', $province, PDO::PARAM_STR);
            $stmt->bindParam(':postal_code', $postal_code, PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                return $this->pdo->lastInsertId();
            }
            return false;
        } catch (Exception $e) {
            error_log("AddressController create error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get address by ID
     * 
     * @param int $address_id
     * @return array|false Address data on success, false if not found
     */
    public function getById($address_id)
    {
        try {
            $sql = "SELECT * FROM Addresses WHERE address_id = :address_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':address_id', $address_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("AddressController getById error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get addresses by user ID
     * 
     * @param int $user_id
     * @return array Array of addresses
     */
    public function getByUserId($user_id)
    {
        try {
            $sql = "SELECT * FROM Addresses WHERE user_id = :user_id ORDER BY address_id DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("AddressController getByUserId error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update address
     * 
     * @param int $address_id
     * @param string $recipient_name
     * @param string $phone
     * @param string $address_line
     * @param string $subdistrict
     * @param string $district
     * @param string $province
     * @param string $postal_code
     * @return bool True on success, false on failure
     */
    public function update($address_id, $recipient_name, $phone, $address_line, $subdistrict, $district, $province, $postal_code)
    {
        try {
            $sql = "UPDATE Addresses SET 
                    recipient_name = :recipient_name,
                    phone = :phone,
                    address_line = :address_line,
                    subdistrict = :subdistrict,
                    district = :district,
                    province = :province,
                    postal_code = :postal_code
                    WHERE address_id = :address_id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':address_id', $address_id, PDO::PARAM_INT);
            $stmt->bindParam(':recipient_name', $recipient_name, PDO::PARAM_STR);
            $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
            $stmt->bindParam(':address_line', $address_line, PDO::PARAM_STR);
            $stmt->bindParam(':subdistrict', $subdistrict, PDO::PARAM_STR);
            $stmt->bindParam(':district', $district, PDO::PARAM_STR);
            $stmt->bindParam(':province', $province, PDO::PARAM_STR);
            $stmt->bindParam(':postal_code', $postal_code, PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("AddressController update error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete address
     * 
     * @param int $address_id
     * @return bool True on success, false on failure
     */
    public function delete($address_id)
    {
        try {
            $sql = "DELETE FROM Addresses WHERE address_id = :address_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':address_id', $address_id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("AddressController delete error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get addresses list with pagination and search
     * 
     * @param int $limit
     * @param int $offset
     * @param string $search
     * @param string $user_id
     * @return array Array of addresses
     */
    public function getList($limit = 10, $offset = 0, $search = '', $user_id = '')
    {
        try {
            $sql = "SELECT a.*, u.name as user_name, u.email as user_email 
                    FROM Addresses a 
                    LEFT JOIN Users u ON a.user_id = u.user_id 
                    WHERE 1=1";
            $params = [];

            // Add search condition
            if (!empty($search)) {
                $sql .= " AND (a.recipient_name LIKE :search OR a.phone LIKE :search OR 
                         a.address_line LIKE :search OR a.district LIKE :search OR 
                         a.province LIKE :search OR a.postal_code LIKE :search OR
                         u.name LIKE :search OR u.email LIKE :search)";
                $params[':search'] = "%{$search}%";
            }

            // Filter by user_id if provided
            if (!empty($user_id)) {
                $sql .= " AND a.user_id = :user_id";
                $params[':user_id'] = $user_id;
            }

            $sql .= " ORDER BY a.address_id DESC LIMIT :limit OFFSET :offset";

            $stmt = $this->pdo->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("AddressController getList error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get total count of addresses
     * 
     * @param string $search
     * @param string $user_id
     * @return int Total count
     */
    public function getCount($search = '', $user_id = '')
    {
        try {
            $sql = "SELECT COUNT(*) as total 
                    FROM Addresses a 
                    LEFT JOIN Users u ON a.user_id = u.user_id 
                    WHERE 1=1";
            $params = [];

            // Add search condition
            if (!empty($search)) {
                $sql .= " AND (a.recipient_name LIKE :search OR a.phone LIKE :search OR 
                         a.address_line LIKE :search OR a.district LIKE :search OR 
                         a.province LIKE :search OR a.postal_code LIKE :search OR
                         u.name LIKE :search OR u.email LIKE :search)";
                $params[':search'] = "%{$search}%";
            }

            // Filter by user_id if provided
            if (!empty($user_id)) {
                $sql .= " AND a.user_id = :user_id";
                $params[':user_id'] = $user_id;
            }

            $stmt = $this->pdo->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['total'];
        } catch (Exception $e) {
            error_log("AddressController getCount error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Check if address exists
     * 
     * @param int $address_id
     * @return bool True if exists, false otherwise
     */
    public function exists($address_id)
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM Addresses WHERE address_id = :address_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':address_id', $address_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (Exception $e) {
            error_log("AddressController exists error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get addresses by province
     * 
     * @param string $province
     * @return array Array of addresses
     */
    public function getByProvince($province)
    {
        try {
            $sql = "SELECT a.*, u.name as user_name, u.email as user_email 
                    FROM Addresses a 
                    LEFT JOIN Users u ON a.user_id = u.user_id 
                    WHERE a.province = :province 
                    ORDER BY a.district, a.subdistrict";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':province', $province, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("AddressController getByProvince error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get addresses by postal code
     * 
     * @param string $postal_code
     * @return array Array of addresses
     */
    public function getByPostalCode($postal_code)
    {
        try {
            $sql = "SELECT a.*, u.name as user_name, u.email as user_email 
                    FROM Addresses a 
                    LEFT JOIN Users u ON a.user_id = u.user_id 
                    WHERE a.postal_code = :postal_code 
                    ORDER BY a.district, a.subdistrict";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':postal_code', $postal_code, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("AddressController getByPostalCode error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Search addresses by recipient name or phone
     * 
     * @param string $search_term
     * @return array Array of addresses
     */
    public function searchByRecipient($search_term)
    {
        try {
            $sql = "SELECT a.*, u.name as user_name, u.email as user_email 
                    FROM Addresses a 
                    LEFT JOIN Users u ON a.user_id = u.user_id 
                    WHERE a.recipient_name LIKE :search OR a.phone LIKE :search 
                    ORDER BY a.recipient_name";
            
            $stmt = $this->pdo->prepare($sql);
            $search_param = "%{$search_term}%";
            $stmt->bindParam(':search', $search_param, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("AddressController searchByRecipient error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get address statistics
     * 
     * @return array Statistics data
     */
    public function getStatistics()
    {
        try {
            // Total addresses
            $total_stmt = $this->pdo->query("SELECT COUNT(*) as total FROM Addresses");
            $total = $total_stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Addresses by province
            $province_stmt = $this->pdo->query("
                SELECT province, COUNT(*) as count 
                FROM Addresses 
                GROUP BY province 
                ORDER BY count DESC 
                LIMIT 10
            ");
            $by_province = $province_stmt->fetchAll(PDO::FETCH_ASSOC);

            // Recent addresses
            $recent_stmt = $this->pdo->query("
                SELECT a.*, u.name as user_name 
                FROM Addresses a 
                LEFT JOIN Users u ON a.user_id = u.user_id 
                ORDER BY a.address_id DESC 
                LIMIT 5
            ");
            $recent = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'total_addresses' => $total,
                'addresses_by_province' => $by_province,
                'recent_addresses' => $recent
            ];
        } catch (Exception $e) {
            error_log("AddressController getStatistics error: " . $e->getMessage());
            return [
                'total_addresses' => 0,
                'addresses_by_province' => [],
                'recent_addresses' => []
            ];
        }
    }

    /**
     * Validate address data
     * 
     * @param array $data Address data to validate
     * @return array Validation errors (empty if valid)
     */
    public function validateAddressData($data)
    {
        $errors = [];

        // Required fields
        $required_fields = ['recipient_name', 'phone', 'address_line', 'subdistrict', 'district', 'province', 'postal_code'];
        
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                $errors[] = "กรุณากรอก " . $this->getFieldLabel($field);
            }
        }

        // Validate phone number
        if (!empty($data['phone']) && !preg_match('/^[0-9+\-\s()]{8,20}$/', $data['phone'])) {
            $errors[] = 'รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง';
        }

        // Validate postal code
        if (!empty($data['postal_code']) && !preg_match('/^[0-9]{5}$/', $data['postal_code'])) {
            $errors[] = 'รหัสไปรษณีย์ต้องเป็นตัวเลข 5 หลัก';
        }

        // Validate field lengths
        $length_limits = [
            'recipient_name' => 255,
            'phone' => 20,
            'address_line' => 300,
            'subdistrict' => 100,
            'district' => 100,
            'province' => 100,
            'postal_code' => 10
        ];

        foreach ($length_limits as $field => $max_length) {
            if (!empty($data[$field]) && strlen($data[$field]) > $max_length) {
                $errors[] = $this->getFieldLabel($field) . " ต้องไม่เกิน {$max_length} ตัวอักษร";
            }
        }

        return $errors;
    }

    /**
     * Get field label in Thai
     * 
     * @param string $field
     * @return string Thai label
     */
    private function getFieldLabel($field)
    {
        $labels = [
            'recipient_name' => 'ชื่อผู้รับ',
            'phone' => 'เบอร์โทรศัพท์',
            'address_line' => 'ที่อยู่',
            'subdistrict' => 'ตำบล/แขวง',
            'district' => 'อำเภอ/เขต',
            'province' => 'จังหวัด',
            'postal_code' => 'รหัสไปรษณีย์'
        ];

        return $labels[$field] ?? $field;
    }
}
?>