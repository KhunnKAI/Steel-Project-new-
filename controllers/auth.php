<?php
// controllers/AuthController.php
session_start();

class AuthController {
    private $db;
    private $user;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User($this->db);
    }

    // ฟังก์ชันลงทะเบียน
    public function register() {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // รับข้อมูลจากฟอร์ม
            $name = isset($_POST['name']) ? $_POST['name'] : '';
            $email = isset($_POST['email']) ? $_POST['email'] : '';
            $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

            // ตรวจสอบข้อมูล
            $errors = [];

            if(empty($name)) {
                $errors[] = "กรุณากรอกชื่อ-นามสกุล";
            }

            if(empty($email)) {
                $errors[] = "กรุณากรอกอีเมล";
            } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "รูปแบบอีเมลไม่ถูกต้อง";
            }

            if(empty($phone)) {
                $errors[] = "กรุณากรอกเบอร์โทรศัพท์";
            } elseif(!preg_match("/^[0-9]{10}$/", $phone)) {
                $errors[] = "เบอร์โทรศัพท์ต้องเป็นตัวเลข 10 หลัก";
            }

            if(empty($password)) {
                $errors[] = "กรุณากรอกรหัสผ่าน";
            } elseif(strlen($password) < 6) {
                $errors[] = "รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร";
            }

            if($password !== $confirm_password) {
                $errors[] = "รหัสผ่านไม่ตรงกัน";
            }

            // ตรวจสอบว่าอีเมลมีอยู่ในระบบหรือไม่
            $this->user->email = $email;
            if($this->user->emailExists()) {
                $errors[] = "อีเมลนี้มีผู้ใช้งานแล้ว";
            }

            if(empty($errors)) {
                // สร้างผู้ใช้ใหม่
                $this->user->name = $name;
                $this->user->email = $email;
                $this->user->phone = $phone;
                $this->user->password_hash = $password;

                if($this->user->create()) {
                    echo "<script>
                        alert('ลงทะเบียนสำเร็จ!');
                        window.location.href = 'login.php';
                    </script>";
                } else {
                    echo "<script>alert('เกิดข้อผิดพลาดในการลงทะเบียน');</script>";
                }
            } else {
                foreach($errors as $error) {
                    echo "<script>alert('$error');</script>";
                    break; // แสดงข้อผิดพลาดทีละข้อ
                }
            }
        }
    }

    // ฟังก์ชันเข้าสู่ระบบ
    public function login() {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // รับข้อมูลจากฟอร์ม
            $email = isset($_POST['email']) ? $_POST['email'] : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';

            // ตรวจสอบข้อมูล
            if(empty($email)) {
                echo "<script>alert('กรุณากรอกชื่อผู้ใช้หรืออีเมล');</script>";
                return;
            }

            if(empty($password)) {
                echo "<script>alert('กรุณากรอกรหัสผ่าน');</script>";
                return;
            }

            // ตรวจสอบผู้ใช้
            $this->user->email = $email;
            if($this->user->emailExists()) {
                if($this->user->verifyPassword($password)) {
                    // เข้าสู่ระบบสำเร็จ
                    $_SESSION['user_id'] = $this->user->user_id;
                    $_SESSION['name'] = $this->user->name;
                    $_SESSION['email'] = $this->user->email;
                    $_SESSION['phone'] = $this->user->phone;

                    echo "<script>
                        alert('เข้าสู่ระบบสำเร็จ!');
                        window.location.href = 'dashboard.php';
                    </script>";
                } else {
                    echo "<script>alert('รหัสผ่านไม่ถูกต้อง');</script>";
                }
            } else {
                echo "<script>alert('ไม่พบชื่อผู้ใช้นี้ในระบบ');</script>";
            }
        }
    }

    // ฟังก์ชันออกจากระบบ
    public function logout() {
        session_destroy();
        echo "<script>
            alert('ออกจากระบบสำเร็จ');
            window.location.href = 'login.php';
        </script>";
    }

    // ตรวจสอบการเข้าสู่ระบบ
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    // ส่งอีเมลรีเซ็ตรหัสผ่าน (จำลอง)
    public function forgotPassword() {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $email = isset($_POST['email']) ? $_POST['email'] : '';

            if(empty($email)) {
                echo "<script>alert('กรุณากรอกอีเมล');</script>";
                return;
            }

            if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo "<script>alert('รูปแบบอีเมลไม่ถูกต้อง');</script>";
                return;
            }

            // ตรวจสอบว่าอีเมลมีในระบบหรือไม่
            $this->user->email = $email;
            if($this->user->emailExists()) {
                // ในการใช้งานจริงควรส่งอีเมลที่มีลิงก์รีเซ็ตรหัสผ่าน
                echo "<script>alert('ส่งลิงก์รีเซ็ตรหัสผ่านไปยังอีเมลของคุณแล้ว');</script>";
            } else {
                echo "<script>alert('ไม่พบอีเมลนี้ในระบบ');</script>";
            }
        }
    }
}
?>