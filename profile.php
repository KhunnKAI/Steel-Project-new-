<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรไฟล์ผู้ใช้</title>
    <link href="header.css" rel="stylesheet">
    <link href="footer.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
        }

        /* Main Content */
        .container {
            max-width: 800px;
            margin: 30px auto;
            padding: 0 20px;
        }

        /* Profile Section */
        .profile-section {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            background-color: #ccc;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 30px;
        }

        .profile-avatar::before {
            content: "👤";
            font-size: 40px;
            color: #666;
        }

        .profile-info h2 {
            color: #d32f2f;
            margin-bottom: 10px;
            font-size: 24px;
        }

        .profile-details {
            display: flex;
            gap: 100px;
        }

        .profile-left, .profile-right {
            flex: 1;
        }

        .profile-item {
            margin-bottom: 15px;
            line-height: 1.6;
        }

        .profile-label {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .profile-value {
            color: #666;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-edit {
            background-color: #d32f2f;
            color: white;
        }

        .btn-password {
            background-color: #2c3e50;
            color: white;
        }

        .btn-address {
            background-color: #d32f2f;
            color: white;
        }

        /* Order History Section */
        .order-section {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .order-title {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
        }

        .order-item {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-details {
            flex: 1;
        }

        .order-id {
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }

        .order-date {
            color: #d32f2f;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .order-amount {
            color: #666;
            margin-bottom: 5px;
        }

        .order-total {
            font-weight: bold;
            color: #333;
        }

        .order-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .btn-view {
            background-color: #ccc;
            color: #666;
            padding: 8px 16px;
            font-size: 12px;
        }

        .address-section {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #eee;
        }

        .address-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .address-title {
            font-weight: bold;
            color: #333;
        }

        .address-detail {
            color: #666;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include("header.php");?>

    <!-- Main Content -->
    <div class="container">
        <!-- Profile Section -->
        <div class="profile-section">
            <div class="profile-header">
                <div class="profile-avatar"></div>
                <div class="profile-info">
                    <h2>แก้ไขรูป</h2>
                </div>
            </div>

            <div class="profile-details">
                <div class="profile-left">
                    <div class="profile-item">
                        <div class="profile-label">ชื่อ-สกุล</div>
                        <div class="profile-value">เดช วีระเทพ</div>
                    </div>
                    <div class="profile-item">
                        <div class="profile-label">เบอร์โทรศัพท์</div>
                        <div class="profile-value">0910000000</div>
                    </div>
                    <div class="profile-item">
                        <div class="profile-label">อีเมล</div>
                        <div class="profile-value">abc123@gmail.com</div>
                    </div>
                </div>
                <div class="profile-right">
                    <a href="#" class="btn btn-edit">แก้ไขข้อมูล</a>
                    <br><br>
                    <a href="#" class="btn btn-password">เปลี่ยนรหัสผ่าน</a>
                </div>
            </div>

            <!-- Address Section -->
            <div class="address-section">
                <div class="address-header">
                    <div class="address-title">ที่อยู่</div>
                    <a href="#" class="btn btn-address">แก้ไขที่อยู่</a>
                </div>
                <div class="address-detail">
                    123/45 หมู่บ้านสุขใจ ซอยสุขุมวิท<br>
                    101/1 ถนนสุขุมวิท แขวงบางจาก<br>
                    เขตพระโขนง<br>
                    จังหวัดกรุงเทพมหานคร 10260
                </div>
            </div>
        </div>

        <!-- Order History Section -->
        <div class="order-section">
            <h2 class="order-title">ประวัติคำสั่งซื้อ</h2>
            
            <div class="order-item">
                <div class="order-details">
                    <div class="order-id">เลขคำสั่งซื้อ steel123456</div>
                    <div class="order-date">วันที่สั่งซื้อ 7/9/2025<br>สถานะ : จัดส่งแล้ว</div>
                    <div class="order-amount">จำนวนที่สั่งซื้อ 10</div>
                    <div class="order-total">ยอดรวม 1000.00</div>
                </div>
                <div class="order-actions">
                    <a href="#" class="btn btn-view">ดูรายละเอียด</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include("footer.php");?>
</body>
</html>