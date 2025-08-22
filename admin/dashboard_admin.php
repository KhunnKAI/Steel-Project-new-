<?php
// Remove session_start() from here since config.php handles it properly
require_once 'controllers/config.php';

// Require login to access this page
requireLogin();

// Get current admin information
$current_admin = getCurrentAdmin();
if (!$current_admin) {
    // If admin not found in database, logout
    header("Location: controllers/logout.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ด - ระบบจัดการร้านค้า</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

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

        .navbar-toggle {
            position: fixed;
            top: 20px;
            left: 20px;
            font-size: 24px;
            cursor: pointer;
            z-index: 1001;
            color: #333;
            background: white;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: none;
            transition: all 0.3s ease;
        }

        .navbar-toggle:hover {
            background: #f8f9fa;
            transform: scale(1.05);
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            padding: 30px;
            margin-left: 260px;
            transition: all 0.3s ease;
            min-height: 100vh;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .header h1 {
            font-size: 28px;
            color: #333;
            font-weight: 600;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(45deg, #990000, #ff6b6b);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .logout-btn {
            background: #dc2626;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: #b91c1c;
            transform: translateY(-1px);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .stat-card.sales::before {
            background: linear-gradient(90deg, #4CAF50, #8BC34A);
        }

        .stat-card.orders::before {
            background: linear-gradient(90deg, #2196F3, #03DAC6);
        }

        .stat-card.products::before {
            background: linear-gradient(90deg, #FF9800, #FFC107);
        }

        .stat-card.users::before {
            background: linear-gradient(90deg, #9C27B0, #E91E63);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .stat-icon.sales {
            background: linear-gradient(45deg, #4CAF50, #8BC34A);
        }

        .stat-icon.orders {
            background: linear-gradient(45deg, #2196F3, #03DAC6);
        }

        .stat-icon.products {
            background: linear-gradient(45deg, #FF9800, #FFC107);
        }

        .stat-icon.users {
            background: linear-gradient(45deg, #9C27B0, #E91E63);
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-weight: 500;
        }

        .stat-change {
            font-size: 14px;
            font-weight: 600;
            margin-top: 10px;
        }

        .stat-change.positive {
            color: #4CAF50;
        }

        .stat-change.negative {
            color: #f44336;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-container,
        .activity-panel {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .panel-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-canvas {
            max-height: 300px;
        }

        .activity-list {
            max-height: 350px;
            overflow-y: auto;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: white;
        }

        .activity-icon.order {
            background: #2196F3;
        }

        .activity-icon.product {
            background: #FF9800;
        }

        .activity-icon.user {
            background: #9C27B0;
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 2px;
        }

        .activity-time {
            font-size: 12px;
            color: #999;
        }

        .recent-orders {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .orders-table th,
        .orders-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }

        .orders-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .orders-table td {
            color: #555;
        }

        .order-id {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: #007bff;
            font-size: 14px;
            background: #f0f8ff;
            padding: 4px 8px;
            border-radius: 4px;
            display: inline-block;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-processing {
            background: #e3f2fd;
            color: #1976d2;
        }

        .status-shipped {
            background: #e8f5e8;
            color: #2e7d32;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        /* Session timeout warning */
        .session-warning {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #f59e0b;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            z-index: 1002;
            display: none;
        }

        /* Sidebar Styles */
        .sidebar {
            background: #940606;
            color: white;
            width: 260px;
            min-width: 260px;
            height: 100%;
            position: fixed;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
        }

        .sidebar.collapsed {
            width: 0;
            min-width: 0;
            overflow: hidden;
        }

        .logo {
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid #940606;
            font-size: 18px;
            font-weight: 600;
            margin-top: 10px;
        }

        nav ul {
            list-style: none;
            padding: 20px 0;
        }

        nav li {
            margin: 5px 0;
            transition: all 0.3s ease;
        }

        nav li a {
            display: flex;
            align-items: center;
            padding: 15px 25px;
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        nav li a i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }

        nav li.active a,
        nav li a:hover {
            background: #051A37;
        }

        @media screen and (max-width: 768px) {
            .navbar-toggle {
                display: block;
            }

            .main-content {
                padding: 80px 20px 20px;
                margin-left: 0;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .orders-table {
                font-size: 14px;
            }

            .orders-table th,
            .orders-table td {
                padding: 10px 8px;
            }

            .sidebar {
                position: fixed;
                top: 0;
                left: -260px;
                height: 100vh;
                z-index: 1000;
            }

            .sidebar.show {
                left: 0;
            }
        }

        @media screen and (max-width: 480px) {

            .orders-table th,
            .orders-table td {
                padding: 8px 4px;
            }
        }
    </style>
</head>

<body>
    <!-- Session timeout warning -->
    <div id="sessionWarning" class="session-warning">
        <i class="fas fa-clock"></i> เซสชันจะหมดอายุใน <span id="timeRemaining"></span> นาที
    </div>

    <div class="navbar-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </div>

    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="logo">
                <div>
                    <img src="image/logo_cropped.png" width="100px">
                </div>
                <h2>ระบบผู้ดูแล</h2>
            </div>

            <nav>
                <ul>
                    <li class="active">
                        <a href="dashboard_admin.php" onclick="showSection('dashboard')">
                            <i class="fas fa-tachometer-alt"></i>
                            แดชบอร์ด
                        </a>
                    </li>
                    <li>
                        <a href="products_admin.php" onclick="showSection('products')">
                            <i class="fas fa-box"></i>
                            จัดการสินค้า
                        </a>
                    </li>
                    <li>
                        <a href="orders_admin.php" onclick="showSection('orders')">
                            <i class="fas fa-shopping-cart"></i>
                            จัดการคำสั่งซื้อ
                        </a>
                    </li>
                    <li>
                        <a href="admins_admin.php" onclick="showSection('admins')">
                            <i class="fas fa-users-cog"></i>
                            จัดการผู้ดูแล
                        </a>
                    </li>
                    <li>
                        <a href="reports_admin.php" onclick="showSection('reports')">
                            <i class="fas fa-chart-bar"></i>
                            รายงาน
                        </a>
                    </li>
                    <li>
                        <a href="#" onclick="handleLogout()">
                            <i class="fas fa-sign-out-alt"></i>
                            ออกจากระบบ
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1><i class="fas fa-tachometer-alt"></i> แดชบอร์ด</h1>
                <div class="user-info">
                    <div>
                        <div style="font-weight: 600;">สวัสดี, <?php echo htmlspecialchars($current_admin['fullname']); ?></div>
                        <div style="font-size: 14px; color: #666;"><?php echo htmlspecialchars($current_admin['position']); ?> - <?php echo htmlspecialchars($current_admin['department']); ?></div>
                        <div style="font-size: 14px; color: #666;" id="current-time"></div>
                    </div>
                    <div class="user-avatar"><?php echo mb_substr($current_admin['fullname'], 0, 1, 'UTF-8'); ?></div>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card sales">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value" id="sales-value">฿245,680</div>
                            <div class="stat-label">ยอดขายวันนี้</div>
                            <div class="stat-change positive">
                                <i class="fas fa-arrow-up"></i> +12.5%
                            </div>
                        </div>
                        <div class="stat-icon sales">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card orders">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value" id="orders-value">8,156</div>
                            <div class="stat-label">คำสั่งซื้อทั้งหมด</div>
                            <div class="stat-change positive">
                                <i class="fas fa-arrow-up"></i> +8.2%
                            </div>
                        </div>
                        <div class="stat-icon orders">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card products">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value" id="products-value">1,234</div>
                            <div class="stat-label">สินค้าทั้งหมด</div>
                            <div class="stat-change negative">
                                <i class="fas fa-arrow-down"></i> -2.1%
                            </div>
                        </div>
                        <div class="stat-icon products">
                            <i class="fas fa-box"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card users">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value" id="users-value">342</div>
                            <div class="stat-label">ลูกค้าทั้งหมด</div>
                            <div class="stat-change positive">
                                <i class="fas fa-arrow-up"></i> +5.7%
                            </div>
                        </div>
                        <div class="stat-icon users">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-grid">
                <div class="chart-container">
                    <div class="panel-title">
                        <i class="fas fa-chart-line"></i>
                        ยอดขายรายวัน (7 วันที่ผ่านมา)
                    </div>
                    <canvas id="salesChart" class="chart-canvas"></canvas>
                </div>

                <div class="activity-panel">
                    <div class="panel-title">
                        <i class="fas fa-bell"></i>
                        กิจกรรมล่าสุด
                    </div>
                    <div class="activity-list">
                        <div class="activity-item">
                            <div class="activity-icon order">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">คำสั่งซื้อใหม่ #ORD-202508-007</div>
                                <div class="activity-time">5 นาทีที่แล้ว</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon order">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">คำสั่งซื้อ #ORD-202508-003 สำเร็จ</div>
                                <div class="activity-time">15 นาทีที่แล้ว</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon product">
                                <i class="fas fa-box"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">เพิ่มสินค้าใหม่: เหล็กเส้นกลม SR24</div>
                                <div class="activity-time">1 ชั่วโมงที่แล้ว</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon order">
                                <i class="fas fa-truck"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">จัดส่งคำสั่งซื้อ #ORD-202508-001</div>
                                <div class="activity-time">2 ชั่วโมงที่แล้ว</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon user">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">ลูกค้าใหม่สมัครสมาชิก</div>
                                <div class="activity-time">3 ชั่วโมงที่แล้ว</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="recent-orders">
                <div class="panel-title">
                    <i class="fas fa-list"></i>
                    คำสั่งซื้อล่าสุด
                </div>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>รหัสคำสั่งซื้อ</th>
                            <th>ลูกค้า</th>
                            <th>ยอดรวม</th>
                            <th>สถานะ</th>
                            <th>วันที่</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="order-id">ORD-202508-007</span></td>
                            <td>นาย สมชาย ใจดี</td>
                            <td>฿13,400</td>
                            <td><span class="status-badge status-pending">รอดำเนินการ</span></td>
                            <td>05/08/2025</td>
                        </tr>
                        <tr>
                            <td><span class="order-id">ORD-202508-006</span></td>
                            <td>บริษัท ก่อสร้าง ABC จำกัด</td>
                            <td>฿84,500</td>
                            <td><span class="status-badge status-processing">กำลังดำเนินการ</span></td>
                            <td>01/08/2025</td>
                        </tr>
                        <tr>
                            <td><span class="order-id">ORD-202507-005</span></td>
                            <td>นาย อนุชา พัฒนา</td>
                            <td>฿4,600</td>
                            <td><span class="status-badge status-cancelled">ยกเลิก</span></td>
                            <td>27/07/2025</td>
                        </tr>
                        <tr>
                            <td><span class="order-id">ORD-202507-004</span></td>
                            <td>นาง วิภา สุขใส</td>
                            <td>฿13,850</td>
                            <td><span class="status-badge status-shipped">จัดส่งแล้ว</span></td>
                            <td>28/07/2025</td>
                        </tr>
                        <tr>
                            <td><span class="order-id">ORD-202507-003</span></td>
                            <td>นาย ประเสริฐ มั่งมี</td>
                            <td>฿4,500</td>
                            <td><span class="status-badge status-completed">สำเร็จ</span></td>
                            <td>29/07/2025</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        // Session management
        let sessionTimer;
        let warningShown = false;
        
        // Update current time
        function updateTime() {
            const now = new Date();
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            document.getElementById('current-time').textContent =
                now.toLocaleDateString('th-TH', options);
        }

        // Check session status
       async function checkSession() {
            try {
                const response = await fetch('controllers/check_session.php');
                
                if (!response.ok) {
                    console.error('Session check failed with status:', response.status);
                    return;
                }
                
                const responseText = await response.text();
                console.log('Session check raw response:', responseText); // Debug log
                
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON parsing error:', parseError);
                    console.error('Response was not valid JSON:', responseText);
                    
                    // If response contains HTML, it's likely a PHP error
                    if (responseText.includes('<') || responseText.includes('<!DOCTYPE')) {
                        console.error('Server returned HTML instead of JSON - likely a PHP error');
                        showAlert('เกิดข้อผิดพลาดในระบบ กรุณาติดต่อผู้ดูแลระบบ', 'error');
                    }
                    return;
                }
                
                if (!data.logged_in) {
                    window.location.href = 'login_admin.html?timeout=1';
                    return;
                }
                
                // Show warning when 15 minutes left
                const timeLeft = data.time_remaining;
                if (timeLeft <= 900 && timeLeft > 0 && !warningShown) { // 15 minutes
                    showSessionWarning(Math.ceil(timeLeft / 60));
                    warningShown = true;
                } else if (timeLeft > 900) {
                    warningShown = false;
                    hideSessionWarning();
                }
                
            } catch (error) {
                console.error('Error checking session:', error);
                // Don't show alert for network errors as they're common
                if (error.name !== 'TypeError' || !error.message.includes('fetch')) {
                    console.error('Unexpected session check error:', error);
                }
            }
        }

        // Function to show alerts (add this if it doesn't exist)
        function showAlert(message, type = 'error') {
            // Create alert if it doesn't exist
            let alertDiv = document.getElementById('sessionAlert');
            if (!alertDiv) {
                alertDiv = document.createElement('div');
                alertDiv.id = 'sessionAlert';
                alertDiv.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: #f59e0b;
                    color: white;
                    padding: 15px 20px;
                    border-radius: 8px;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
                    z-index: 1002;
                    max-width: 300px;
                `;
                document.body.appendChild(alertDiv);
            }
            
            if (type === 'error') {
                alertDiv.style.background = '#dc2626';
            } else if (type === 'success') {
                alertDiv.style.background = '#059669';
            }
            
            alertDiv.textContent = message;
            alertDiv.style.display = 'block';
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                alertDiv.style.display = 'none';
            }, 5000);
        }

        // Show session warning
        function showSessionWarning(minutes) {
            const warning = document.getElementById('sessionWarning');
            const timeSpan = document.getElementById('timeRemaining');
            timeSpan.textContent = minutes;
            warning.style.display = 'block';
            
            // Auto-hide after 10 seconds
            setTimeout(() => {
                hideSessionWarning();
            }, 10000);
        }

        // Hide session warning
        function hideSessionWarning() {
            const warning = document.getElementById('sessionWarning');
            warning.style.display = 'none';
        }

        // Handle logout
        async function handleLogout() {
            if (confirm('คุณต้องการออกจากระบบหรือไม่?')) {
                try {
                    const response = await fetch('controllers/logout.php', { // Fixed path
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    
                    const data = await response.json();
                    if (data.success) {
                        window.location.href = data.redirect;
                    }
                } catch (error) {
                    // Fallback to regular logout
                    window.location.href = 'controllers/logout.php'; // Fixed path
                }
            }
        }

        // Toggle sidebar
        function toggleSidebar() {
            const sidebar = document.getElementById("sidebar");
            const main = document.querySelector(".main-content");

            if (sidebar) {
                sidebar.classList.toggle("show");
                main.classList.toggle("overlay");
            }
        }

        // Show different sections
        function showSection(section) {
            if (section === 'products') {
                window.location.href = 'products_admin.php';
            } else if (section === 'orders') {
                window.location.href = 'orders_admin.php';
            } else if (section === 'admins') {
                window.location.href = 'admins_admin.php';
            } else if (section === 'reports') {
                window.location.href = 'reports_admin.php';
            } else if (section === 'dashboard') {
                if (window.innerWidth <= 768) {
                    const sidebar = document.getElementById("sidebar");
                    const main = document.querySelector(".main-content");
                    if (sidebar) {
                        sidebar.classList.remove("show");
                        main.classList.remove("overlay");
                    }
                }
            }
        }

        // Close sidebar when clicking outside (mobile only)
        document.addEventListener("click", function (e) {
            const sidebar = document.getElementById("sidebar");
            const toggle = document.querySelector(".navbar-toggle");
            const main = document.querySelector(".main-content");

            if (!sidebar) return;

            const clickedOutside = !sidebar.contains(e.target) && !toggle.contains(e.target);

            if (sidebar.classList.contains("show") && clickedOutside && window.innerWidth <= 768) {
                sidebar.classList.remove("show");
                main.classList.remove("overlay");
            }
        });

        // Animate numbers
        function animateValue(element, start, end, duration) {
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                const current = Math.floor(progress * (end - start) + start);

                if (element.id === 'sales-value') {
                    element.textContent = '฿' + current.toLocaleString();
                } else {
                    element.textContent = current.toLocaleString();
                }

                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };
            window.requestAnimationFrame(step);
        }

        // Initialize chart
        function initChart() {
            const ctx = document.getElementById('salesChart').getContext('2d');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['29/07', '30/07', '31/07', '01/08', '02/08', '03/08', '04/08'],
                    datasets: [{
                        label: 'ยอดขาย (บาท)',
                        data: [180000, 210000, 195000, 240000, 230000, 265000, 245680],
                        borderColor: '#990000',
                        backgroundColor: 'rgba(153, 0, 0, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#990000',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function (value) {
                                    return '฿' + value.toLocaleString();
                                }
                            }
                        }
                    },
                    elements: {
                        line: {
                            borderWidth: 3
                        }
                    }
                }
            });
        }

        // Export functions for global access
        window.toggleSidebar = toggleSidebar;
        window.showSection = showSection;
        window.handleLogout = handleLogout;

        // Initialize everything
        document.addEventListener('DOMContentLoaded', function () {
            updateTime();
            setInterval(updateTime, 60000); // Update every minute

            // Check session every 30 seconds
            checkSession();
            setInterval(checkSession, 30000);

            // Animate stats on load
            setTimeout(() => {
                animateValue(document.getElementById('sales-value'), 0, 245680, 2000);
                animateValue(document.getElementById('orders-value'), 0, 8156, 1500);
                animateValue(document.getElementById('products-value'), 0, 1234, 1800);
                animateValue(document.getElementById('users-value'), 0, 342, 1600);
            }, 500);

            // Initialize chart
            initChart();
        });

        // Handle page visibility change - pause session check when tab not active
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                // Tab became active, check session immediately
                checkSession();
            }
        });
    </script>
</body>

</html>