<?php
// ========================
// DASHBOARD ADMIN PAGE
// ========================

require_once 'controllers/config.php';
requireLogin();

$current_admin = getCurrentAdmin();
if (!$current_admin) {
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

        /* ========================
           HEADER
           ======================== */
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

        /* ========================
           STATISTICS CARDS
           ======================== */
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

        /* ========================
           CONTENT GRIDS
           ======================== */
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

        .activity-list,
        .recent-orders-list {
            max-height: 450px;
            overflow-y: auto;
        }

        /* ========================
           ACTIVITY ITEMS
           ======================== */
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

        /* ========================
           TABLE STYLES
           ======================== */
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

        /* ========================
           LOADING & ERROR STATES
           ======================== */
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

        /* ========================
           MOBILE RESPONSIVE
           ======================== */
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
                font-size: 13px;
            }

            .orders-table th,
            .orders-table td {
                padding: 10px 8px;
            }
        }

        @media screen and (max-width: 480px) {
            .stat-card {
                padding: 20px;
            }

            .stat-value {
                font-size: 24px;
            }
        }
    </style>
</head>

<body>
    <!-- Navbar Toggle Button -->
    <div class="navbar-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </div>

    <!-- Main Container -->
    <div class="container">
        <!-- Sidebar -->
        <?php include 'sidebar_admin.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
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

            <!-- Loading Indicator -->
            <div class="loading-indicator">
                <i class="fas fa-spinner"></i>
                <div>กำลังโหลดข้อมูล...</div>
            </div>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Statistics Cards -->
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

                <!-- Charts and Activity -->
                <div class="content-grid">
                    <!-- Sales Chart -->
                    <div class="chart-container">
                        <div class="panel-title">
                            <i class="fas fa-chart-line"></i>
                            ยอดขายรายวัน
                        </div>
                        <canvas id="salesChart" class="chart-canvas"></canvas>
                    </div>

                    <!-- Recent Activity -->
                    <div class="activity-panel">
                        <div class="panel-title">
                            <i class="fas fa-bell"></i>
                            กิจกรรมล่าสุด
                        </div>
                        <div class="activity-list" id="recent-activity-list"></div>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="bottom-grid">
                    <div class="recent-orders-panel">
                        <div class="panel-title">
                            <i class="fas fa-shopping-bag"></i>
                            คำสั่งซื้อล่าสุด
                        </div>
                        <div class="recent-orders-list" id="recent-orders-list"></div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Scripts -->
    <script src="sidebar_admin.js"></script>
    <script src="dashboard_admin.js"></script>
</body>

</html>