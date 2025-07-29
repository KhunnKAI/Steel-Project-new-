<?php
// register_debug.php - ไฟล์สำหรับ debug ปัญหา
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ตรวจสอบการเชื่อมต่อฐานข้อมูลก่อน
echo "<h3>1. ตรวจสอบการเชื่อมต่อฐานข้อมูล</h3>";

// config/database.php - แก้ไขให้ตรงกับของคุณ
class Database {
    private $host = "localhost:3307";
    private $db_name = "SteelShop";
    private $username = "user";
    private $password = "12345678";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                                $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "✅ เชื่อมต่อฐานข้อมูลสำเร็จ<br>";
        } catch(PDOException $exception) {
            echo "❌ เชื่อมต่อฐานข้อมูลล้มเหลว: " . $exception->getMessage() . "<br>";
            die();
        }
        return $this->conn;
    }
}

// ตรวจสอบตาราง users
echo "<h3>2. ตรวจสอบตาราง users</h3>";
$database = new Database();
$db = $database->getConnection();

try {
    $query = "DESCRIBE Users";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ โครงสร้างตาราง users:<br>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach($result as $row) {
        echo "<tr>";
        foreach($row as $value) {
            echo "<td>" . htmlspecialchars($value) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table><br>";
} catch(PDOException $e) {
    echo "❌ ไม่พบตาราง users หรือเกิดข้อผิดพลาด: " . $e->getMessage() . "<br>";
    echo "กรุณาสร้างตาราง users ด้วย SQL นี้:<br><br>";
    echo "<pre>
CREATE TABLE users (
    user_id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (user_id)
);
</pre>";
}

// User Model แบบ debug
class UserDebug {
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
        echo "<h4>🔍 ตรวจสอบอีเมลซ้ำ: " . $this->email . "</h4>";
        
        $query = "SELECT user_id, name, email, phone, password_hash FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->email);
        $stmt->execute();

        $num = $stmt->rowCount();
        if($num > 0) {
            echo "❌ อีเมลนี้มีผู้ใช้งานแล้ว<br>";
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->user_id = $row['user_id'];
            $this->name = $row['name'];
            $this->email = $row['email'];
            $this->phone = $row['phone'];
            $this->password_hash = $row['password_hash'];
            return true;
        } else {
            echo "✅ อีเมลไม่ซ้ำ สามารถใช้ได้<br>";
        }
        return false;
    }

    // สร้างผู้ใช้ใหม่
    public function create() {
        echo "<h4>📝 เริ่มสร้างผู้ใช้ใหม่</h4>";
        
        $query = "INSERT INTO " . $this->table_name . " 
                SET name=:name, email=:email, phone=:phone, password_hash=:password_hash, 
                    created_at=:created_at, updated_at=:updated_at";

        echo "SQL Query: " . $query . "<br>";

        try {
            $stmt = $this->conn->prepare($query);

            // ทำความสะอาดข้อมูล
            $this->name = htmlspecialchars(strip_tags($this->name));
            $this->email = htmlspecialchars(strip_tags($this->email));
            $this->phone = htmlspecialchars(strip_tags($this->phone));
            
            // เข้ารหัสรหัสผ่าน
            $plain_password = $this->password_hash;
            $this->password_hash = password_hash($this->password_hash, PASSWORD_DEFAULT);

            // กำหนดเวลาปัจจุบัน
            $this->created_at = date('Y-m-d H:i:s');
            $this->updated_at = date('Y-m-d H:i:s');

            echo "ข้อมูลที่จะบันทึก:<br>";
            echo "- ชื่อ: " . $this->name . "<br>";
            echo "- อีเมล: " . $this->email . "<br>";
            echo "- เบอร์: " . $this->phone . "<br>";
            echo "- รหัสผ่าน (plain): " . $plain_password . "<br>";
            echo "- รหัสผ่าน (hash): " . substr($this->password_hash, 0, 50) . "...<br>";
            echo "- สร้างเมื่อ: " . $this->created_at . "<br>";

            // bind ค่า
            $stmt->bindParam(":name", $this->name);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":phone", $this->phone);
            $stmt->bindParam(":password_hash", $this->password_hash);
            $stmt->bindParam(":created_at", $this->created_at);
            $stmt->bindParam(":updated_at", $this->updated_at);

            if($stmt->execute()) {
                echo "✅ บันทึกข้อมูลสำเร็จ! User ID: " . $this->conn->lastInsertId() . "<br>";
                return true;
            } else {
                echo "❌ บันทึกข้อมูลล้มเหลว<br>";
                print_r($stmt->errorInfo());
                return false;
            }
            
        } catch(PDOException $e) {
            echo "❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "<br>";
            return false;
        }
    }
}

// AuthController แบบ debug
class AuthControllerDebug {
    private $db;
    private $user;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new UserDebug($this->db);
    }

    public function register() {
        echo "<h3>3. ตรวจสอบการรับข้อมูลจากฟอร์ม</h3>";
        
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            echo "✅ รับ POST data แล้ว<br>";
            
            echo "ข้อมูลที่ได้รับ:<br>";
            echo "<pre>";
            print_r($_POST);
            echo "</pre>";

            // รับข้อมูลจากฟอร์ม
            $name = isset($_POST['name']) ? $_POST['name'] : '';
            $email = isset($_POST['email']) ? $_POST['email'] : '';
            $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

            echo "ข้อมูลที่แยกแล้ว:<br>";
            echo "- ชื่อ: '" . $name . "' (length: " . strlen($name) . ")<br>";
            echo "- อีเมล: '" . $email . "' (length: " . strlen($email) . ")<br>";
            echo "- เบอร์: '" . $phone . "' (length: " . strlen($phone) . ")<br>";
            echo "- รหัสผ่าน: '" . $password . "' (length: " . strlen($password) . ")<br>";
            echo "- ยืนยันรหัส: '" . $confirm_password . "' (length: " . strlen($confirm_password) . ")<br>";

            // ตรวจสอบข้อมูล
            echo "<h4>🔍 ตรวจสอบข้อมูล</h4>";
            $errors = [];

            if(empty($name)) {
                $errors[] = "กรุณากรอกชื่อ-นามสกุล";
                echo "❌ ชื่อว่าง<br>";
            } else {
                echo "✅ ชื่อถูกต้อง<br>";
            }

            if(empty($email)) {
                $errors[] = "กรุณากรอกอีเมล";
                echo "❌ อีเมลว่าง<br>";
            } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "รูปแบบอีเมลไม่ถูกต้อง";
                echo "❌ รูปแบบอีเมลไม่ถูกต้อง<br>";
            } else {
                echo "✅ อีเมลถูกต้อง<br>";
            }

            if(empty($phone)) {
                $errors[] = "กรุณากรอกเบอร์โทรศัพท์";
                echo "❌ เบอร์โทรว่าง<br>";
            } elseif(!preg_match("/^[0-9]{10}$/", $phone)) {
                $errors[] = "เบอร์โทรศัพท์ต้องเป็นตัวเลข 10 หลัก";
                echo "❌ เบอร์โทรไม่ถูกรูปแบบ (ต้อง 10 หลัก)<br>";
            } else {
                echo "✅ เบอร์โทรถูกต้อง<br>";
            }

            if(empty($password)) {
                $errors[] = "กรุณากรอกรหัสผ่าน";
                echo "❌ รหัสผ่านว่าง<br>";
            } elseif(strlen($password) < 6) {
                $errors[] = "รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร";
                echo "❌ รหัสผ่านสั้นเกินไป<br>";
            } else {
                echo "✅ รหัสผ่านถูกต้อง<br>";
            }

            if($password !== $confirm_password) {
                $errors[] = "รหัสผ่านไม่ตรงกัน";
                echo "❌ รหัสผ่านไม่ตรงกัน<br>";
            } else {
                echo "✅ รหัสผ่านตรงกัน<br>";
            }

            // ตรวจสอบว่าอีเมลมีอยู่ในระบบหรือไม่
            $this->user->email = $email;
            if($this->user->emailExists()) {
                $errors[] = "อีเมลนี้มีผู้ใช้งานแล้ว";
            }

            if(empty($errors)) {
                echo "<h4>✅ ผ่านการตรวจสอบแล้ว กำลังบันทึกข้อมูล...</h4>";
                
                // สร้างผู้ใช้ใหม่
                $this->user->name = $name;
                $this->user->email = $email;
                $this->user->phone = $phone;
                $this->user->password_hash = $password;

                if($this->user->create()) {
                    echo "<h3>🎉 ลงทะเบียนสำเร็จ!</h3>";
                    echo "<script>
                        setTimeout(function() {
                            if(confirm('ลงทะเบียนสำเร็จ! ต้องการไปหน้าเข้าสู่ระบบหรือไม่?')) {
                                window.location.href = 'login.php';
                            }
                        }, 2000);
                    </script>";
                } else {
                    echo "<h3>❌ เกิดข้อผิดพลาดในการลงทะเบียน</h3>";
                }
            } else {
                echo "<h4>❌ พบข้อผิดพลาด:</h4>";
                foreach($errors as $error) {
                    echo "- " . $error . "<br>";
                }
            }
        } else {
            echo "❌ ไม่ได้รับข้อมูล POST<br>";
        }
    }
}

// ประมวลผล
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $auth = new AuthControllerDebug();
    $auth->register();
} else {
    echo "กรุณากรอกข้อมูลในฟอร์มด้านล่าง<br><br>";
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Register</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .form-group { margin: 10px 0; }
        input { padding: 10px; width: 300px; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; }
        h3 { color: #333; border-bottom: 2px solid #007bff; }
        h4 { color: #666; }
    </style>
</head>
<body>
    <h2>🔧 Debug Register Form</h2>
    
    <form method="POST">
        <div class="form-group">
            <label>ชื่อ-นามสกุล:</label><br>
            <input type="text" name="name" placeholder="กรอกชื่อ-นามสกุล" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
        </div>
        
        <div class="form-group">
            <label>อีเมล:</label><br>
            <input type="email" name="email" placeholder="กรอกอีเมล" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>
        
        <div class="form-group">
            <label>เบอร์โทรศัพท์:</label><br>
            <input type="tel" name="phone" placeholder="กรอกเบอร์โทร 10 หลัก" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
        </div>
        
        <div class="form-group">
            <label>รหัสผ่าน:</label><br>
            <input type="password" name="password" placeholder="กรอกรหัสผ่าน (อย่างน้อย 6 ตัว)">
        </div>
        
        <div class="form-group">
            <label>ยืนยันรหัสผ่าน:</label><br>
            <input type="password" name="confirm_password" placeholder="กรอกรหัสผ่านอีกครั้ง">
        </div>
        
        <button type="submit">ทดสอบลงทะเบียน</button>
    </form>
    
    <hr>
    <p><strong>วิธีใช้:</strong></p>
    <ol>
        <li>แก้ไขข้อมูลการเชื่อมต่อฐานข้อมูลในโค้ดข้างบน</li>
        <li>กรอกข้อมูลในฟอร์มและกดส่ง</li>
        <li>ดูผลลัพธ์การ debug ด้านบนฟอร์ม</li>
        <li>หากพบปัญหาให้แก้ไขตามที่แจ้ง</li>
    </ol>
</body>
</html>