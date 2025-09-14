<?php
require_once 'controllers/config.php';
requireLogin();

// ดึงข้อมูล admin ปัจจุบัน
$admin = getCurrentAdmin();
if (!$admin) {
    header("Location: login_admin.html");
    exit();
}

// ดึงข้อมูลคำสั่งซื้อสำหรับการพิมพ์
$orders = [];
$order_details = [];
$selected_order_id = '';

// ถ้ามีการเลือกคำสั่งซื้อเพื่อพิมพ์
if (isset($_GET['order_id']) && !empty($_GET['order_id'])) {
    $selected_order_id = $_GET['order_id'];
    
    try {
        // ดึงข้อมูลคำสั่งซื้อและข้อมูลลูกค้า (เฉพาะ status 02)
        $stmt = $pdo->prepare("
            SELECT o.*, u.name as customer_name, u.email, u.phone as customer_phone,
                   s.status_code, s.description as status_description,
                   a.recipient_name, a.phone as shipping_phone, a.address_line,
                   a.subdistrict, a.district, p.name as province_name, a.postal_code
            FROM Orders o
            LEFT JOIN Users u ON o.user_id = u.user_id
            LEFT JOIN Status s ON o.status = s.status_id
            LEFT JOIN Addresses a ON u.user_id = a.user_id AND a.is_main = 1
            LEFT JOIN Province p ON a.province_id = p.province_id
            WHERE o.order_id = ? AND s.status_id = 'status02'
        ");
        $stmt->execute([$selected_order_id]);
        $order_details = $stmt->fetch();
        
        // ดึงรายการสินค้าในคำสั่งซื้อ
        if ($order_details) {
            $stmt = $pdo->prepare("
                SELECT oi.*, pr.name as product_name, pr.description, c.name as category_name
                FROM OrderItem oi
                LEFT JOIN Product pr ON oi.product_id = pr.product_id
                LEFT JOIN Category c ON pr.category_id = c.category_id
                WHERE oi.order_id = ?
                ORDER BY oi.order_item_id
            ");
            $stmt->execute([$selected_order_id]);
            $order_items = $stmt->fetchAll();
        }
    } catch(PDOException $e) {
        $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}

// ดึงรายการคำสั่งซื้อเฉพาะ status 02 สำหรับ dropdown
try {
    $stmt = $pdo->prepare("
        SELECT o.order_id, o.created_at, u.name as customer_name, o.total_amount,
               s.description as status_description
        FROM Orders o
        LEFT JOIN Users u ON o.user_id = u.user_id
        LEFT JOIN Status s ON o.status = s.status_id
        WHERE s.status_id = 'status02'
        ORDER BY o.created_at DESC
        LIMIT 100
    ");
    $stmt->execute();
    $orders = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>พิมพ์คำสั่งซื้อ - ระบบจัดการร้านค้า</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter';
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .header-section {
            background: linear-gradient(135deg, #990000, #cc0000);
            color: white;
            padding: 2rem 0;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-title h1 {
            font-size: 2rem;
            font-weight: 600;
        }

        .header-title i {
            font-size: 2.5rem;
            opacity: 0.9;
        }

        .back-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .alert {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .order-selection {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            padding: 25px 30px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            position: relative;
        }

        .order-selection::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #990000, #cc0000, #ff6b6b);
            border-radius: 20px 20px 0 0;
        }

        .order-selection h4 {
            color: #333;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .order-selection h4 i {
            color: #990000;
            font-size: 16px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 20px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-size: 13px;
            color: #555;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group select {
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .form-group select:hover {
            border-color: #990000;
            box-shadow: 0 4px 12px rgba(153, 0, 0, 0.15);
            transform: translateY(-1px);
        }

        .form-group select:focus {
            outline: none;
            border-color: #990000;
            box-shadow: 0 0 0 4px rgba(153, 0, 0, 0.1), 0 4px 12px rgba(153, 0, 0, 0.15);
            transform: translateY(-1px);
        }

        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            justify-content: center;
        }

        .btn-primary {
            background: linear-gradient(45deg, #990000, #cc0000);
            color: white;
            box-shadow: 0 4px 12px rgba(153, 0, 0, 0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(45deg, #cc0000, #dd0000);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(153, 0, 0, 0.4);
            color: white;
        }

        .btn-success {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }

        .btn-success:hover {
            background: linear-gradient(45deg, #20c997, #17a2b8);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
            color: white;
        }

        .btn-lg {
            padding: 15px 30px;
            font-size: 16px;
        }

        .print-section {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin: 20px 0;
            padding: 30px;
        }

        .company-header {
            text-align: center;
            border-bottom: 3px solid #990000;
            padding-bottom: 25px;
            margin-bottom: 30px;
            position: relative;
        }

        .company-header::after {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: linear-gradient(90deg, #cc0000, #ff6b6b);
        }

        .company-name {
            color: #990000;
            margin-bottom: 5px;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .company-info {
            font-size: 12px;
            color: #555;
            line-height: 1.6;
        }

        .company-info p {
            margin-bottom: 3px;
        }

        .order-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .info-section h5 {
            color: #495057;
            border-bottom: 2px solid #990000;
            padding-bottom: 8px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-section h5 i {
            color: #990000;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .items-table th {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid #dee2e6;
        }

        .items-table td {
            padding: 15px;
            border: 1px solid #dee2e6;
            vertical-align: middle;
            color: #555;
        }

        .items-table tr:hover {
            background: rgba(153, 0, 0, 0.02);
        }

        .total-section {
            margin-top: 20px;
            text-align: right;
        }

        .total-section table {
            border-collapse: collapse;
            width: 300px;
            margin-left: auto;
        }

        .total-section table td {
            padding: 8px 12px;
            border-bottom: 1px solid #dee2e6;
        }

        .total-section .table-dark td {
            background: #990000;
            color: white;
            font-weight: 600;
        }

        .print-buttons {
            text-align: center;
            margin: 20px 0;
            page-break-inside: avoid;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .bg-info {
            background: #17a2b8 !important;
            color: white;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                text-align: center;
            }

            .header-title h1 {
                font-size: 16px;
            }

            .container {
                padding: 1rem;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .order-info-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .print-buttons .btn {
                margin-bottom: 10px;
            }

            .company-name {
                font-size: 16px;
            }
        }

        @media print {
            body {
                background: white;
                font-size: 12px;
            }
            
            .header-section, 
            .order-selection, 
            .print-buttons, 
            .no-print,
            .status-notice {
                display: none !important;
            }
            
            .print-section {
                margin: 0;
                padding: 0;
                box-shadow: none;
                border-radius: 0;
            }
            
            .container {
                padding: 0;
                max-width: none;
            }

            .company-header {
                border-bottom: 2px solid #990000;
            }

            .company-header::after {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <div class="header-section">
        <div class="header-content">
            <div class="header-title">
                <i class="fas fa-print"></i>
                <h1>พิมพ์คำสั่งซื้อ</h1>
            </div>
            <a href="orders_admin.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                กลับไปรายการคำสั่งซื้อ
            </a>
        </div>
    </div>

    <div class="container">

        <?php if (isset($error)): ?>
        <div class="alert">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
        <div class="alert">
            <i class="fas fa-info-circle"></i>
            ไม่มีคำสั่งซื้อในระบบ
        </div>
        <?php else: ?>

        <!-- Order Selection Section -->
        <div class="order-selection no-print">
            <h4><i class="fas fa-search"></i>เลือกคำสั่งซื้อที่ต้องการพิมพ์</h4>
            <form method="GET" action="" class="form-row">
                <div class="form-group">
                    <label for="order_id">คำสั่งซื้อ</label>
                    <select name="order_id" id="order_id" required>
                        <option value="">เลือกคำสั่งซื้อ...</option>
                        <?php foreach ($orders as $order): ?>
                        <option value="<?php echo $order['order_id']; ?>" 
                                <?php echo ($selected_order_id === $order['order_id']) ? 'selected' : ''; ?>>
                            <?php echo $order['order_id']; ?> - 
                            <?php echo htmlspecialchars($order['customer_name']); ?> - 
                            ฿<?php echo number_format($order['total_amount'], 2); ?> - 
                            <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?> - 
                            <?php echo $order['status_description']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i>แสดงคำสั่งซื้อ
                </button>
                <?php if ($selected_order_id): ?>
                <button type="button" onclick="window.print()" class="btn btn-success">
                    <i class="fas fa-print"></i>พิมพ์
                </button>
                <?php endif; ?>
            </form>
        </div>

        <?php endif; ?>

        <!-- Print Section -->
        <?php if ($order_details): ?>
        <div class="print-section" id="printSection">
            <!-- Company Header -->
            <div class="company-header">
                <div>
                    <img src="image/logo_cropped.png" width="100px">
                </div>
                <h2 class="company-name">บริษัท ช้างเหล็กไทย จำกัด</h2>
                <div class="company-info">
                    <p> 99/9 หมู่ 4 ถ.บางนา-ตราด กม.12 ต.บางโฉลง อ.บางพลี จ.สมุทรปราการ 10540</p>
                    <p><strong>โทร:</strong> 02-123-4567 | <strong>มือถือ:</strong> 098-765-4321 | <strong>อีเมล:</strong> sales@changlekthai.co.th </p>
                </div>
            </div>

            <!-- Order Information -->
            <div class="order-info-grid">
                <div class="info-section">
                    <h5><i class="fas fa-file-invoice"></i>ข้อมูลคำสั่งซื้อ</h5>
                    <p><strong>เลขที่คำสั่งซื้อ:</strong> <?php echo $order_details['order_id']; ?></p>
                    <p><strong>วันที่สั่งซื้อ:</strong> <?php echo date('d/m/Y H:i:s', strtotime($order_details['created_at'])); ?></p>
                    <?php if ($order_details['note']): ?>
                    <p><strong>หมายเหตุ:</strong> <?php echo htmlspecialchars($order_details['note']); ?></p>
                    <?php endif; ?>
                </div>

                <div class="info-section">
                    <h5><i class="fas fa-user"></i>ข้อมูลลูกค้า</h5>
                    <p><strong>ชื่อลูกค้า:</strong> <?php echo htmlspecialchars($order_details['customer_name']); ?></p>
                    <p><strong>อีเมล:</strong> <?php echo htmlspecialchars($order_details['email']); ?></p>
                    <p><strong>เบอร์โทร:</strong> <?php echo htmlspecialchars($order_details['customer_phone']); ?></p>
                </div>
            </div>

            <!-- Shipping Address -->
            <?php if ($order_details['recipient_name']): ?>
            <div class="info-section" style="margin-bottom: 30px;">
                <h5><i class="fas fa-shipping-fast"></i>ที่อยู่จัดส่ง</h5>
                <p><strong>ผู้รับ:</strong> <?php echo htmlspecialchars($order_details['recipient_name']); ?></p>
                <p><strong>เบอร์โทร:</strong> <?php echo htmlspecialchars($order_details['shipping_phone']); ?></p>
                <p><strong>ที่อยู่:</strong> 
                    <?php echo htmlspecialchars($order_details['address_line']); ?><br>
                    <?php echo htmlspecialchars($order_details['subdistrict']); ?> 
                    <?php echo htmlspecialchars($order_details['district']); ?> 
                    จังหวัด<?php echo htmlspecialchars($order_details['province_name']); ?> 
                    <?php echo htmlspecialchars($order_details['postal_code']); ?>
                </p>
            </div>
            <?php endif; ?>

            <!-- Order Items -->
            <div class="items-section">
                <h5 style="color: #495057; border-bottom: 2px solid #990000; padding-bottom: 8px; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-list" style="color: #990000;"></i>รายการสินค้า
                </h5>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th width="5%">ที่</th>
                            <th width="35%">รายการสินค้า</th>
                            <th width="15%">ประเภท</th>
                            <th width="10%">ล็อต</th>
                            <th width="8%">จำนวน</th>
                            <th width="12%">ราคาต่อหน่วย</th>
                            <th width="15%">รวม</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $item_no = 1;
                        $total_items = 0;
                        foreach ($order_items as $item): 
                            $line_total = $item['quantity'] * $item['price_each'];
                            $total_items += $item['quantity'];
                        ?>
                        <tr>
                            <td style="text-align: center;"><?php echo $item_no++; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                <?php if ($item['description']): ?>
                                <br><small style="color: #666;"><?php echo htmlspecialchars($item['description']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['lot']); ?></td>
                            <td style="text-align: center;"><?php echo number_format($item['quantity']); ?></td>
                            <td style="text-align: right;">฿<?php echo number_format($item['price_each'], 2); ?></td>
                            <td style="text-align: right;">฿<?php echo number_format($line_total, 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Order Summary -->
            <div class="total-section">
                <table>
                    <tr>
                        <td><strong>จำนวนรายการทั้งหมด:</strong></td>
                        <td style="text-align: right;"><?php echo number_format($total_items); ?> รายการ</td>
                    </tr>
                    <tr>
                        <td><strong>ยอดรวมสินค้า (ไม่รวม VAT):</strong></td>
                        <td style="text-align: right;">฿<?php echo number_format($order_details['total_novat'], 2); ?></td>
                    </tr>
                    <tr>
                        <td><strong>ค่าจัดส่ง:</strong></td>
                        <td style="text-align: right;">฿<?php echo number_format($order_details['shipping_fee'], 2); ?></td>
                    </tr>
                    <tr>
                        <td><strong>VAT (7%):</strong></td>
                        <td style="text-align: right;">฿<?php echo number_format($order_details['total_amount'] - $order_details['total_novat'] - $order_details['shipping_fee'], 2); ?></td>
                    </tr>
                    <tr class="table-dark">
                        <td><strong>ยอดรวมทั้งสิ้น:</strong></td>
                        <td style="text-align: right;"><strong>฿<?php echo number_format($order_details['total_amount'], 2); ?></strong></td>
                    </tr>
                </table>
            </div>

            <!-- Footer -->
            <div style="text-align: center; margin-top: 50px; padding-top: 20px; border-top: 1px solid #dee2e6;">
                <p style="margin-bottom: 8px; color: #990000; font-weight: 600;"><small>ขอบคุณที่ใช้บริการ บริษัท ช้างเหล็กไทย จำกัด</small></p>
                <p style="margin-bottom: 0;"><small>พิมพ์เมื่อ: <?php echo date('d/m/Y H:i:s'); ?> โดย: <?php echo htmlspecialchars($admin['fullname']); ?></small></p>
            </div>
        </div>

        <?php elseif (isset($_GET['order_id']) && !empty($_GET['order_id']) && !$order_details): ?>
        <div class="alert">
            <i class="fas fa-exclamation-triangle"></i>
            ไม่พบคำสั่งซื้อที่เลือก
        </div>
        <?php endif; ?>

    </div>

    <script>
        // Auto-focus on order selection
        document.getElementById('order_id').focus();
        
        // Keyboard shortcut for printing
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                if (document.getElementById('printSection')) {
                    window.print();
                }
            }
        });
        
    </script>
</body>
</html>