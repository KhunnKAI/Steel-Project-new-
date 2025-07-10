<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรไฟล์ผู้ใช้</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }

        .header {
            background-color: #c41e3a;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            width: 40px;
            height: 40px;
            background-color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #c41e3a;
            font-weight: bold;
            font-size: 18px;
        }

        .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-size: 14px;
        }

        .nav-icons {
            display: flex;
            gap: 10px;
        }

        .nav-icon {
            width: 35px;
            height: 35px;
            background-color: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .main-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .profile-section {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            background-color: #ddd;
            border-radius: 50%;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: #666;
        }

        .profile-name {
            font-size: 18px;
            font-weight: bold;
            color: #c41e3a;
            margin-bottom: 20px;
        }

        .profile-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            min-width: 120px;
        }

        .btn-primary {
            background-color: #c41e3a;
            color: white;
        }

        .btn-secondary {
            background-color: #2c3e50;
            color: white;
        }

        .profile-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 30px;
        }

        .info-group h3 {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }

        .info-group p {
            color: #666;
            font-size: 14px;
            line-height: 1.4;
        }

        .address-section {
            grid-column: 1 / -1;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-top: 20px;
        }

        .address-info {
            flex: 1;
        }

        .edit-address-btn {
            background-color: #c41e3a;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin-left: 20px;
        }

        .orders-section {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .orders-header {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 30px;
            color: #333;
        }

        .order-card {
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .order-id {
            font-weight: bold;
            font-size: 16px;
        }

        .order-date {
            color: #666;
            font-size: 14px;
        }

        .order-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-info {
            flex: 1;
        }

        .order-quantity {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }

        .order-total {
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }

        .order-status {
            background-color: #e0e0e0;
            color: #666;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            border: none;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .profile-info {
                grid-template-columns: 1fr;
            }
            
            .address-section {
                flex-direction: column;
                gap: 15px;
            }
            
            .edit-address-btn {
                margin-left: 0;
                align-self: flex-start;
            }
            
            .profile-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">🐘</div>
        <nav class="nav-links">
            <a href="#">หน้าหลัก</a>
            <a href="#">ติดต่อเรา</a>
        </nav>
        <div class="nav-icons">
            <div class="nav-icon">🛒</div>
            <div class="nav-icon">🔔</div>
            <div class="nav-icon">👤</div>
        </div>
    </header>

    <div class="main-container">
        <div class="profile-section">
            <div class="profile-header">
                <div class="profile-avatar">👤</div>
                <div class="profile-name">แก้ไขข้อมูล</div>
                <div class="profile-buttons">
                    <a href="#" class="btn btn-primary">แก้ไขข้อมูล</a>
                    <a href="#" class="btn btn-secondary">เปลี่ยนรหัสผ่าน</a>
                </div>
            </div>

            <div class="profile-info">
                <div class="info-group">
                    <h3>ชื่อ-สกุล</h3>
                    <p>เจษฎา วิระสุทธิ์</p>
                </div>
                <div class="info-group">
                    <h3>เบอร์โทรศัพท์</h3>
                    <p>0910000000</p>
                </div>
                <div class="info-group">
                    <h3>อีเมล</h3>
                    <p>abc123@gmail.com</p>
                </div>
                <div class="address-section">
                    <div class="address-info">
                        <h3>ที่อยู่</h3>
                        <p>123/45 หมู่บ้านสมุทร ซอยสมุทรอง<br>
                        101/1 ถนนสมุทร แขวงบางนา<br>
                        เขตพระโขนง<br>
                        จังหวัดกรุงเทพมหานคร 10260</p>
                    </div>
                    <button class="edit-address-btn">แก้ไขที่อยู่</button>
                </div>
            </div>
        </div>

        <div class="orders-section">
            <h2 class="orders-header">ประวัติคำสั่งซื้อ</h2>
            <div class="order-card">
                <div class="order-header">
                    <div class="order-id">เลขที่คำสั่งซื้อ steel123456</div>
                    <div class="order-date">วันที่สั่งซื้อ 7/9/2025</div>
                </div>
                <div class="order-details">
                    <div class="order-info">
                        <div class="order-quantity">จำนวนสินค้า 10</div>
                        <div class="order-total">ยอดรวม 1000.00</div>
                    </div>
                    <button class="order-status">ติดตาม</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>