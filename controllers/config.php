<?php
// config.php - Database configuration
class Database {
    private $host = "26.94.44.21:3307";
    private $db_name = "SteelShop";
    private $username = "user";
    private $password = "12345678";
    public $conn;

    // private $host = "localhost";
    // private $db_name = "teststeel";
    // private $username = "root";
    // private $password = "";
    // public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                                $this->username, $this->password);
            $this->conn->exec("set names utf8mb4");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            die("Database connection failed. Please check your configuration.");
        }
        return $this->conn;
    }
}

// Create global PDO connection for backward compatibility
try {
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    error_log("Database initialization error: " . $e->getMessage());
    die("Database initialization failed.");
}

// SMTP configuration for sending emails to ANY email address
// This Gmail account will be used as the sender, but can send to any recipient
$smtp_config = [
    'enabled' => true, // เปิดใช้งาน SMTP
    'host' => 'smtp.gmail.com', // Gmail SMTP server
    'port' => 587, // TLS port for Gmail
    'username' => 'prawitchaya.game@gmail.com', // Gmail account ที่ใช้ส่ง (sender)
    'password' => 'fvci trvk mrno khdl', // App password ของ Gmail account
    'encryption' => 'tls', // Use TLS encryption
    'from_email' => 'prawitchaya.game@gmail.com', // Sender email (แสดงเป็นผู้ส่ง)
    'from_name' => 'ช้างเหล็กไทย', // Sender name
    'reply_to_email' => 'support@changelekthia.com', // Email ที่ให้ reply กลับ (อาจเป็น email อื่น)
    'reply_to_name' => 'ฝ่ายสนับสนุน ช้างเหล็กไทย'
];

// Email templates configuration
$email_templates = [
    'reset_password' => [
        'subject' => 'รีเซ็ตรหัสผ่าน - ช้างเหล็กไทย',
        'html_template' => '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9;">
                <div style="background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <div style="text-align: center; margin-bottom: 30px;">
                        <h1 style="color: #2c5477; margin: 0;">ช้างเหล็กไทย</h1>
                        <p style="color: #666; margin: 5px 0;">เหล็กคุณภาพ แกร่งทุกงาน</p>
                    </div>
                    
                    <h2 style="color: #333;">รีเซ็ตรหัสผ่าน</h2>
                    <p>สวัสดีคุณ <strong>{{USER_NAME}}</strong></p>
                    
                    <p>เราได้รับคำขอรีเซ็ตรหัสผ่านสำหรับบัญชีของคุณ กรุณาคลิกปุ่มด้านล่างเพื่อรีเซ็ตรหัสผ่าน:</p>
                    
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="{{RESET_URL}}" style="background-color: #dc3545; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;">
                            รีเซ็ตรหัสผ่าน
                        </a>
                    </div>
                    
                    <p style="color: #666; font-size: 14px;">หรือคัดลอกลิงก์ด้านล่างไปวางในเว็บเบราว์เซอร์:</p>
                    <p style="background-color: #f8f9fa; padding: 10px; border-radius: 5px; word-break: break-all; font-size: 14px;">
                        {{RESET_URL}}
                    </p>
                    
                    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                        <p style="color: #999; font-size: 12px;">
                            <strong>หมายเหตุ:</strong><br>
                            • ลิงก์นี้จะหมดอายุใน 1 ชั่วโมง<br>
                            • หากคุณไม่ได้ร้องขอการรีเซ็ตรหัสผ่าน กรุณาเพิกเฉยต่ออีเมลนี้<br>
                            • อย่าแชร์ลิงก์นี้กับใครเพื่อความปลอดภัยของบัญชี
                        </p>
                    </div>
                    
                    <div style="text-align: center; margin-top: 30px; color: #999; font-size: 12px;">
                        <p>อีเมลนี้ส่งจากระบบอัตโนมัติ กรุณาอย่าตอบกลับ</p>
                        <p>หากมีปัญหา กรุณาติดต่อ: {{REPLY_TO_EMAIL}}</p>
                    </div>
                </div>
            </div>
        ',
        'text_template' => '
รีเซ็ตรหัสผ่าน - ช้างเหล็กไทย

สวัสดีคุณ {{USER_NAME}}

เราได้รับคำขอรีเซ็ตรหัสผ่านสำหรับบัญชีของคุณ
กรุณาเข้าลิงก์ด้านล่างเพื่อรีเซ็ตรหัสผ่าน:

{{RESET_URL}}

หมายเหตุ:
- ลิงก์นี้จะหมดอายุใน 1 ชั่วโมง
- หากคุณไม่ได้ร้องขอ กรุณาเพิกเฉยต่ออีเมลนี้
- หากมีปัญหา กรุณาติดต่อ: {{REPLY_TO_EMAIL}}

ช้างเหล็กไทย - เหล็กคุณภาพ แกร่งทุกงาน
        '
    ]
];
?>