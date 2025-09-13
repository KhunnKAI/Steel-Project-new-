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
    <link rel="stylesheet" href="sidebar_admin.css">

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

        .header-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .period-selector {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .period-selector:hover {
            border-color: #007bff;
        }

        .refresh-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .refresh-btn:hover {
            background: #0056b3;
            transform: translateY(-1px);
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

        .bottom-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }

        .chart-container,
        .activity-panel,
        .recent-orders-panel {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .recent-orders-list {
            max-height: 450px;
            overflow-y: auto;
        }

        /* Table Styles for Recent Orders */
        .table-responsive {
            overflow-x: auto;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .orders-table thead {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }

        .orders-table th {
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #dee2e6;
        }

        .orders-table tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.2s ease;
        }

        .orders-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .orders-table tbody tr:last-child {
            border-bottom: none;
        }

        .orders-table td {
            padding: 15px 12px;
            vertical-align: middle;
        }

        .order-id-badge {
            display: inline-block;
            background: #e3f2fd;
            color: #1976d2;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 12px;
        }

        .customer-name {
            font-weight: 500;
            color: #333;
        }

        .order-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-weight: 500;
            font-size: 12px;
            text-align: center;
            min-width: 80px;
        }

        /* Status Colors */
        .order-status.status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .order-status.status-confirmed {
            background: #d1ecf1;
            color: #0c5460;
        }

        .order-status.status-paid {
            background: #d4edda;
            color: #155724;
        }

        .order-status.status-shipped {
            background: #cce7ff;
            color: #004085;
        }

        .order-status.status-delivered {
            background: #d1f2eb;
            color: #00695c;
        }

        .order-status.status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .order-status.status-unknown {
            background: #e2e3e5;
            color: #495057;
        }

        .amount-value {
            font-weight: 600;
            color: #4CAF50;
            font-size: 14px;
        }

        .time-ago {
            color: #6c757d;
            font-size: 13px;
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

        .activity-list, .top-products-list {
            max-height: 350px;
            overflow-y: auto;
        }

        .activity-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-content {
            flex: 1;
        }

        .activity-description {
            font-weight: 500;
            color: #333;
            margin-bottom: 5px;
        }

        .activity-amount {
            font-size: 14px;
            font-weight: 600;
            color: #4CAF50;
        }

        .activity-time {
            font-size: 12px;
            color: #999;
            white-space: nowrap;
            margin-left: 15px;
        }

        .product-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
            gap: 15px;
        }

        .product-item:last-child {
            border-bottom: none;
        }

        .product-rank {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #007bff;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
        }

        .product-info {
            flex: 1;
        }

        .product-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 3px;
        }

        .product-category {
            font-size: 12px;
            color: #666;
        }

        .product-stats {
            text-align: right;
        }

        .product-sold {
            font-size: 14px;
            color: #666;
        }

        .product-revenue {
            font-weight: 600;
            color: #4CAF50;
        }

        .loading-indicator {
            display: none;
            text-align: center;
            padding: 20px;
            color: #666;
        }

        .loading-indicator i {
            font-size: 24px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .error-message {
            display: none;
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
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

        .session-warning button {
            background: transparent;
            border: 1px solid white;
            color: white;
            padding: 5px 10px;
            margin-left: 10px;
            border-radius: 4px;
            cursor: pointer;
        }

        .session-warning button:hover {
            background: rgba(255, 255, 255, 0.2);
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

            .bottom-grid {
                grid-template-columns: 1fr;
            }

            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .header-controls {
                flex-wrap: wrap;
                justify-content: center;
            }

            /* Table responsiveness on mobile */
            .orders-table {
                font-size: 13px;
            }

            .orders-table th,
            .orders-table td {
                padding: 10px 8px;
            }

            .order-id-badge {
                font-size: 11px;
                padding: 3px 6px;
            }

            .order-status {
                font-size: 11px;
                padding: 3px 6px;
                min-width: 70px;
            }
        }

        @media screen and (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .stat-card {
                padding: 20px;
            }

            .stat-value {
                font-size: 24px;
            }

            /* Stack table cells on very small screens */
            .orders-table,
            .orders-table thead,
            .orders-table tbody,
            .orders-table th,
            .orders-table td,
            .orders-table tr {
                display: block;
            }

            .orders-table thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }

            .orders-table tr {
                border: 1px solid #ccc;
                margin-bottom: 10px;
                padding: 10px;
                border-radius: 8px;
                background: white;
            }

            .orders-table td {
                border: none;
                position: relative;
                padding: 8px 8px 8px 35%;
                text-align: right;
            }

            .orders-table td:before {
                content: attr(data-label);
                position: absolute;
                left: 6px;
                width: 30%;
                padding-right: 10px;
                white-space: nowrap;
                font-weight: 600;
                color: #495057;
                text-align: left;
            }
        }
    </style>
</head>

<body>

    <!-- Session timeout warning -->
    <div id="sessionWarning" class="session-warning">
        <i class="fas fa-clock"></i> เซสชันจะหมดอายุใน <span id="timeRemaining"></span> นาที
        <button onclick="resetSessionTimeout()">ขยายเวลา</button>
    </div>

    <div class="navbar-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </div>

    <div class="container">
        <?php include 'sidebar_admin.php'; ?>

        <main class="main-content">
            <div class="header">
                <h1><i class="fas fa-tachometer-alt"></i> แดชบอร์ด</h1>
                
                <div class="header-controls">
                    <select class="period-selector">
                        <option value="7days">7 วันที่ผ่านมา</option>
                        <option value="30days">30 วันที่ผ่านมา</option>
                        <option value="90days">90 วันที่ผ่านมา</option>
                        <option value="1year">1 ปีที่ผ่านมา</option>
                    </select>
                    
                    <button class="refresh-btn" id="refresh-dashboard">
                        <i class="fas fa-sync-alt"></i>
                        รีเฟรช
                    </button>
                </div>

                <div class="user-info">
                    <div>
                        <div style="font-weight: 600;">สวัสดี, <?php echo htmlspecialchars($current_admin['fullname']); ?></div>
                        <div style="font-size: 14px; color: #666;"><?php echo htmlspecialchars($current_admin['position']); ?> - <?php echo htmlspecialchars($current_admin['department']); ?></div>
                        <div style="font-size: 14px; color: #666;" id="current-time"></div>
                    </div>
                    <div class="user-avatar"><?php echo mb_substr($current_admin['fullname'], 0, 1, 'UTF-8'); ?></div>
                </div>
            </div>

            <div class="loading-indicator">
                <i class="fas fa-spinner"></i>
                <div>กำลังโหลดข้อมูล...</div>
            </div>

            <div class="dashboard-content">
                <div class="stats-grid">
                    <div class="stat-card sales">
                        <div class="stat-header">
                            <div>
                                <div class="stat-value" id="total-sales">฿0</div>
                                <div class="stat-label">ยอดขายทั้งหมด</div>
                            </div>
                            <div class="stat-icon sales">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card orders">
                        <div class="stat-header">
                            <div>
                                <div class="stat-value" id="total-orders">0</div>
                                <div class="stat-label">คำสั่งซื้อทั้งหมด</div>
                            </div>
                            <div class="stat-icon orders">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card products">
                        <div class="stat-header">
                            <div>
                                <div class="stat-value" id="total-products">0</div>
                                <div class="stat-label">สินค้าทั้งหมด</div>
                            </div>
                            <div class="stat-icon products">
                                <i class="fas fa-box"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card users">
                        <div class="stat-header">
                            <div>
                                <div class="stat-value" id="total-users">0</div>
                                <div class="stat-label">ลูกค้าทั้งหมด</div>
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
                            ยอดขายรายวัน
                        </div>
                        <canvas id="salesChart" class="chart-canvas"></canvas>
                    </div>

                    <div class="activity-panel">
                        <div class="panel-title">
                            <i class="fas fa-bell"></i>
                            กิจกรรมล่าสุด
                        </div>
                        <div class="activity-list" id="recent-activity-list">
                            <!-- Data will be loaded by JavaScript -->
                        </div>
                    </div>
                </div>

                <div class="bottom-grid">
                    <div class="recent-orders-panel">
                        <div class="panel-title">
                            <i class="fas fa-shopping-bag"></i>
                            คำสั่งซื้อล่าสุด
                        </div>
                        <div class="recent-orders-list" id="recent-orders-list">
                            <!-- Data will be loaded by JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="sidebar_admin.js"></script>
    <script src="dashboard_admin.js"></script>
</body>

</html>