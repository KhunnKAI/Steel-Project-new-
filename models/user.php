<?php
class User {
    private $conn;
    private $table_name = "Users";

    public $user_id;
    public $name;
    public $email;
    public $phone;
    public $password_hash;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // ตรวจสอบว่าอีเมลมีอยู่ในระบบหรือไม่
    public function emailExists() {
        $query = "SELECT user_id, name, email, phone, password_hash FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->email);
        $stmt->execute();

        $num = $stmt->rowCount();
        if($num > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->user_id = $row['user_id'];
            $this->name = $row['name'];
            $this->email = $row['email'];
            $this->phone = $row['phone'];
            $this->password_hash = $row['password_hash'];
            return true;
        }
        return false;
    }

    // สร้างผู้ใช้ใหม่
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                SET name=:name, email=:email, phone=:phone, password_hash=:password_hash, 
                    created_at=:created_at, updated_at=:updated_at";

        $stmt = $this->conn->prepare($query);

        // ทำความสะอาดข้อมูล
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->password_hash = htmlspecialchars(strip_tags($this->password_hash));

        // เข้ารหัสรหัสผ่าน
        $this->password_hash = password_hash($this->password_hash, PASSWORD_DEFAULT);

        // กำหนดเวลาปัจจุบัน
        $this->created_at = date('Y-m-d H:i:s');
        $this->updated_at = date('Y-m-d H:i:s');

        // bind ค่า
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":password_hash", $this->password_hash);
        $stmt->bindParam(":created_at", $this->created_at);
        $stmt->bindParam(":updated_at", $this->updated_at);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // ตรวจสอบรหัสผ่าน
    public function verifyPassword($password) {
        return password_verify($password, $this->password_hash);
    }
}
?>