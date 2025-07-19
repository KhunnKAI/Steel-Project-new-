<?php
// ตั้งค่าข้อมูลสำหรับการเชื่อมต่อ
$host = 'localhost:3307';        // หรือ 127.0.0.1
$username = 'user';         // ชื่อผู้ใช้ MySQL (เช่น root)
$password = '12345678';     // รหัสผ่าน MySQL
$database = 'SteelShop';    // ชื่อฐานข้อมูล

// สร้างการเชื่อมต่อ
$conn = new mysqli($host, $username, $password, $database);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("การเชื่อมต่อล้มเหลว: " . $conn->connect_error);
}

echo "เชื่อมต่อฐานข้อมูลสำเร็จ!";
?>
