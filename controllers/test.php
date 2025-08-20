<?php
// test_files.php - ตรวจสอบไฟล์ที่จำเป็น
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>File Check</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .exists { color: green; }
        .missing { color: red; }
        .error { color: orange; }
    </style>
</head>
<body>
    <h2>ตรวจสอบไฟล์ที่จำเป็น</h2>
    
    <?php
    $required_files = [
        'config.php',
        'UserController.php',
        'auth_api.php'
    ];
    
    echo "<ul>";
    foreach ($required_files as $file) {
        if (file_exists($file)) {
            echo "<li class='exists'>✓ {$file} - มีอยู่</li>";
        } else {
            echo "<li class='missing'>✗ {$file} - ไม่พบไฟล์</li>";
        }
    }
    echo "</ul>";
    
    // Test database connection
    echo "<h3>ตรวจสอบการเชื่อมต่อฐานข้อมูล</h3>";
    
    if (file_exists('config.php')) {
        try {
            require_once 'config.php';
            
            // Test Database class
            if (class_exists('Database')) {
                echo "<p class='exists'>✓ พบ Database class</p>";
                
                $database = new Database();
                $conn = $database->getConnection();
                
                if ($conn) {
                    echo "<p class='exists'>✓ เชื่อมต่อฐานข้อมูล SteelShop สำเร็จ</p>";
                    
                    // Check if users table exists
                    try {
                        $stmt = $conn->query("SHOW TABLES LIKE 'Users'");
                        if ($stmt->rowCount() > 0) {
                            echo "<p class='exists'>✓ ตาราง Users มีอยู่แล้ว</p>";
                            
                            // Count users
                            $stmt = $conn->query("SELECT COUNT(*) as count FROM Users");
                            $result = $stmt->fetch();
                            echo "<p class='exists'>📊 จำนวนผู้ใช้งานในระบบ: " . $result['count'] . " คน</p>";
                        } else {
                            echo "<p class='missing'>✗ ไม่พบตาราง Users - กรุณารัน SQL สร้างตาราง</p>";
                        }
                    } catch (Exception $e) {
                        echo "<p class='error'>⚠ ตรวจสอบตารางไม่ได้: " . $e->getMessage() . "</p>";
                    }
                    
                } else {
                    echo "<p class='missing'>✗ ไม่สามารถเชื่อมต่อฐานข้อมูลได้</p>";
                }
            } else {
                echo "<p class='missing'>✗ ไม่พบ Database class</p>";
            }
            
            // Test global $pdo variable
            if (isset($pdo)) {
                echo "<p class='exists'>✓ ตัวแปร \$pdo พร้อมใช้งาน</p>";
                
                // Test UserController
                if (file_exists('UserController.php')) {
                    require_once 'UserController.php';
                    try {
                        $userController = new UserController($pdo);
                        echo "<p class='exists'>✓ UserController ทำงานได้</p>";
                    } catch (Exception $e) {
                        echo "<p class='error'>⚠ UserController Error: " . $e->getMessage() . "</p>";
                    }
                }
            } else {
                echo "<p class='missing'>✗ ไม่พบตัวแปร \$pdo</p>";
            }
            
        } catch (Exception $e) {
            echo "<p class='error'>⚠ Database Error: " . $e->getMessage() . "</p>";
        }
    }
    
    // Test API endpoint
    echo "<h3>ทดสอบ API Endpoint</h3>";
    echo "<button onclick='testAPI()'>ทดสอบ API</button>";
    echo "<div id='apiResult'></div>";
    ?>
    
    <script>
    function testAPI() {
        const resultDiv = document.getElementById('apiResult');
        resultDiv.innerHTML = 'กำลังทดสอบ...';
        
        fetch('auth_api.php?action=test')
            .then(response => response.text())
            .then(data => {
                resultDiv.innerHTML = '<pre>' + data + '</pre>';
            })
            .catch(error => {
                resultDiv.innerHTML = '<p style="color:red">Error: ' + error.message + '</p>';
            });
    }
    </script>
</body>
</html>