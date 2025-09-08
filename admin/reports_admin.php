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
    <title>รายงาน - ระบบจัดการร้านค้า</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>


    
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
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

        /* Report Filter Section */
        .filter-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }

        .filter-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            font-weight: 500;
            color: #555;
            font-size: 14px;
        }

        .filter-group select,
        .filter-group input {
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: 'Prompt', Arial, sans-serif;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #990000;
            box-shadow: 0 0 0 3px rgba(153, 0, 0, 0.1);
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: 'Prompt', Arial, sans-serif;
        }

        .btn-primary {
            background: #990000;
            color: white;
        }

        .btn-primary:hover {
            background: #770000;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #545b62;
        }

        .btn-export {
            background: #28a745;
            color: white;
        }

        .btn-export:hover {
            background: #218838;
        }

        /* Report Tabs */
        .report-tabs {
            display: flex;
            background: white;
            border-radius: 15px;
            padding: 5px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            flex-wrap: wrap;
        }

        .tab-button {
            flex: 1;
            padding: 15px 20px;
            border: none;
            background: transparent;
            color: #666;
            font-weight: 500;
            cursor: pointer;
            border-radius: 10px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-width: 150px;
            font-family: 'Prompt', Arial, sans-serif;
        }

        .tab-button.active {
            background: #990000;
            color: white;
        }

        .tab-button:hover:not(.active) {
            background: #f5f5f5;
        }

        /* Report Content */
        .report-content {
            display: none;
        }

        .report-content.active {
            display: block;
        }

        .report-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Loading indicator */
        .loading {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
            background: rgba(255,255,255,0.95);
            color: #333;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        .loading-spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #990000;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Summary Cards */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .summary-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .summary-card.sales::before { background: linear-gradient(90deg, #4CAF50, #8BC34A); }
        .summary-card.orders::before { background: linear-gradient(90deg, #2196F3, #03DAC6); }
        .summary-card.products::before { background: linear-gradient(90deg, #FF9800, #FFC107); }
        .summary-card.warning::before { background: linear-gradient(90deg, #f44336, #ff5722); }

        .summary-value {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
        }

        .summary-label {
            color: #666;
            font-weight: 500;
            font-size: 14px;
        }

        /* Search box */
        .search-box {
            margin-bottom: 15px;
        }

        .search-box input {
            width: 100%;
            max-width: 300px;
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Prompt', Arial, sans-serif;
        }

        /* Data Table */
        .table-container {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            min-width: 800px;
        }

        .data-table th,
        .data-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }

        .data-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .data-table td {
            color: #555;
        }

        .data-table tr:hover {
            background: #f8f9fa;
        }

        .text-right {
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-in-stock { background: #d4edda; color: #155724; }
        .status-low-stock { background: #fff3cd; color: #856404; }
        .status-out-of-stock { background: #f8d7da; color: #721c24; }
        .status-critical { background: #f8d7da; color: #721c24; }

        .movement-in { color: #28a745; font-weight: 600; }
        .movement-out { color: #dc3545; font-weight: 600; }
        .movement-adjust { color: #6c757d; font-weight: 600; }

        .movement-positive { color: #28a745; }
        .movement-negative { color: #dc3545; }
        .movement-neutral { color: #6c757d; }

        .urgency-urgent { color: #dc3545; font-weight: bold; }
        .urgency-warning { color: #ffc107; font-weight: bold; }
        .urgency-normal { color: #28a745; }

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

            .filter-row {
                grid-template-columns: 1fr;
            }

            .summary-cards {
                grid-template-columns: 1fr;
            }

            .filter-buttons {
                justify-content: center;
                flex-wrap: wrap;
            }

            .report-tabs {
                flex-direction: column;
            }

            .data-table {
                font-size: 14px;
            }
            
            .data-table th,
            .data-table td {
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
            .data-table th,
            .data-table td {
                padding: 8px 4px;
            }
        }
    </style>
</head>

<body>
    <div class="navbar-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </div>

    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="logo">
                <div>
                    <img src="image/logo_cropped.png" width="100px" alt="Logo">
                </div>
                <h2>ระบบผู้ดูแล</h2>
            </div>
            
            <nav>
                <ul>
                    <li>
                        <a href="dashboard_admin.php">
                            <i class="fas fa-tachometer-alt"></i>
                            แดชบอร์ด
                        </a>
                    </li>
                    <li>
                        <a href="products_admin.php">
                            <i class="fas fa-box"></i>
                            จัดการสินค้า
                        </a>
                    </li>
                    <li>
                        <a href="orders_admin.php">
                            <i class="fas fa-shopping-cart"></i>
                            จัดการคำสั่งซื้อ
                        </a>
                    </li>
                    <li>
                        <a href="admins_admin.php">
                            <i class="fas fa-users-cog"></i>
                            จัดการผู้ดูแล
                        </a>
                    </li>
                    <li class="active">
                        <a href="reports_admin.php">
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
                <h1><i class="fas fa-chart-bar"></i> รายงาน</h1>
                <div class="user-info">
                    <div>
                        <div style="font-weight: 600;">สวัสดี, <?php echo htmlspecialchars($current_admin['fullname']); ?></div>
                        <div style="font-size: 14px; color: #666;"><?php echo htmlspecialchars($current_admin['position']); ?> - <?php echo htmlspecialchars($current_admin['department']); ?></div>
                        <div style="font-size: 14px; color: #666;" id="current-time"></div>
                    </div>
                    <div class="user-avatar"><?php echo mb_substr($current_admin['fullname'], 0, 1, 'UTF-8'); ?></div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <div class="filter-title">
                    <i class="fas fa-filter"></i>
                    ตัวกรองข้อมูล
                </div>
                <div class="filter-row">
                    <div class="filter-group">
                        <label>ประเภทรายงาน</label>
                        <select id="reportType">
                            <option value="sales">รายงานยอดขาย</option>
                            <option value="stock">รายงานสินค้าคงเหลือ</option>
                            <option value="movement">รายงานการเคลื่อนไหว</option>
                            <option value="shipping">รายงานการขนส่ง</option>
                            <option value="customer">รายงานลูกค้า</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>วันที่เริ่มต้น</label>
                        <input type="date" id="startDate">
                    </div>
                    <div class="filter-group">
                        <label>วันที่สิ้นสุด</label>
                        <input type="date" id="endDate">
                    </div>
                    <div class="filter-group">
                        <label>ประเภทสินค้า</label>
                        <select id="productCategory">
                            <option value="all">ทั้งหมด</option>
                            <option value="rb">เหล็กเส้น</option>
                            <option value="sp">เหล็กแผ่น</option>
                            <option value="ss">เหล็กรูปพรรณ</option>
                            <option value="wm">เหล็กตะแกรง/ตาข่าย</option>
                            <option value="ot">อื่นๆ</option>
                        </select>
                    </div>
                </div>
                <div class="filter-buttons">
                    <button class="btn btn-secondary" onclick="resetFilters()">
                        <i class="fas fa-undo"></i> รีเซ็ต
                    </button>
                    <button class="btn btn-primary" onclick="applyFilters()">
                        <i class="fas fa-search"></i> ค้นหา
                    </button>
                    <button class="btn btn-export" onclick="exportReport('excel')">
                        <i class="fas fa-file-excel"></i> ส่งออก Excel
                    </button>
                </div>
            </div>

            <!-- Report Tabs -->
            <div class="report-tabs">
                <button class="tab-button active" onclick="showReport('sales')">
                    <i class="fas fa-chart-line"></i>
                    รายงานยอดขาย
                </button>
                <button class="tab-button" onclick="showReport('stock')">
                    <i class="fas fa-boxes"></i>
                    รายงานสินค้าคงเหลือ
                </button>
                <button class="tab-button" onclick="showReport('movement')">
                    <i class="fas fa-exchange-alt"></i>
                    รายงานการเคลื่อนไหว
                </button>
                <button class="tab-button" onclick="showReport('shipping')">
                    <i class="fas fa-truck"></i>
                    รายงานการขนส่ง
                </button>
                <button class="tab-button" onclick="showReport('customer')">
                    <i class="fas fa-users"></i>
                    รายงานลูกค้า
                </button>
            </div>

            <!-- Loading Indicator -->
            <div class="loading" id="loading-indicator">
                <div class="loading-spinner"></div>
                <p>กำลังโหลดข้อมูล...</p>
            </div>

            <!-- Sales Report -->
            <div id="sales-report" class="report-content active">
                <div class="summary-cards">
                    <div class="summary-card sales">
                        <div class="summary-value" id="total-sales">฿0</div>
                        <div class="summary-label">ยอดขายรวม</div>
                    </div>
                    <div class="summary-card orders">
                        <div class="summary-value" id="total-orders">0</div>
                        <div class="summary-label">จำนวนคำสั่งซื้อ</div>
                    </div>
                    <div class="summary-card products">
                        <div class="summary-value" id="total-customers">0</div>
                        <div class="summary-label">จำนวนลูกค้า</div>
                    </div>
                    <div class="summary-card orders">
                        <div class="summary-value" id="avg-order">฿0</div>
                        <div class="summary-label">คำสั่งซื้อเฉลี่ย</div>
                    </div>
                    <div class="summary-card sales">
                        <div class="summary-value" id="growth-rate">0%</div>
                        <div class="summary-label">อัตราการเติบโต</div>
                    </div>
                </div>

                <div class="report-section">
                    <div class="section-title">
                        <i class="fas fa-table"></i>
                        รายละเอียดยอดขายตามสินค้า
                    </div>
                    <div class="search-box">
                        <input type="text" class="table-search" data-table="sales-table" placeholder="ค้นหาสินค้า...">
                    </div>
                    <div class="table-container">
                        <table class="data-table" id="sales-table">
                            <thead>
                                <tr>
                                    <th>รหัสสินค้า</th>
                                    <th>ชื่อสินค้า</th>
                                    <th>หมวดหมู่</th>
                                    <th>จำนวนที่ขาย</th>
                                    <th>ยอดขาย (บาท)</th>
                                    <th>ราคาเฉลี่ย</th>
                                    <th>จำนวนคำสั่งซื้อ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="7" class="text-center">กำลังโหลดข้อมูล...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="report-section">
                    <div class="section-title">
                        <i class="fas fa-trophy"></i>
                        สินค้าขายดี
                    </div>
                    <div class="table-container">
                        <table class="data-table" id="best-selling-table">
                            <thead>
                                <tr>
                                    <th>รหัสสินค้า</th>
                                    <th>ชื่อสินค้า</th>
                                    <th>จำนวนที่ขาย</th>
                                    <th>รายได้</th>
                                    <th>คงเหลือ</th>
                                    <th>สถานะ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center">กำลังโหลดข้อมูล...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Stock Report -->
            <div id="stock-report" class="report-content">
                <div class="summary-cards">
                    <div class="summary-card products">
                        <div class="summary-value" id="total-products">0</div>
                        <div class="summary-label">รายการสินค้าทั้งหมด</div>
                    </div>
                    <div class="summary-card warning">
                        <div class="summary-value" id="low-stock-count">0</div>
                        <div class="summary-label">สินค้าใกล้หมด</div>
                    </div>
                    <div class="summary-card warning">
                        <div class="summary-value" id="critical-stock-count">0</div>
                        <div class="summary-label">สินค้าวิกฤต</div>
                    </div>
                    <div class="summary-card sales">
                        <div class="summary-value" id="total-stock-value">฿0</div>
                        <div class="summary-label">มูลค่าสต็อครวม</div>
                    </div>
                </div>

                <div class="report-section">
                    <div class="section-title">
                        <i class="fas fa-table"></i>
                        รายละเอียดสินค้าคงเหลือ
                    </div>
                    <div class="search-box">
                        <input type="text" class="table-search" data-table="stock-table" placeholder="ค้นหาสินค้า...">
                    </div>
                    <div class="table-container">
                        <table class="data-table" id="stock-table">
                            <thead>
                                <tr>
                                    <th>รหัสสินค้า</th>
                                    <th>ชื่อสินค้า</th>
                                    <th>หมวดหมู่</th>
                                    <th>คงเหลือ</th>
                                    <th>ราคา</th>
                                    <th>สถานะ</th>
                                    <th>มูลค่า</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="7" class="text-center">กำลังโหลดข้อมูล...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="report-section">
                    <div class="section-title">
                        <i class="fas fa-exclamation-triangle"></i>
                        สินค้าที่ต้องสั่งซื้อเพิ่ม
                    </div>
                    <div class="table-container">
                        <table class="data-table" id="reorder-table">
                            <thead>
                                <tr>
                                    <th>รหัสสินค้า</th>
                                    <th>ชื่อสินค้า</th>
                                    <th>คงเหลือ</th>
                                    <th>ขายเฉลี่ย/เดือน</th>
                                    <th>คาดการณ์เหลือ</th>
                                    <th>สถานะ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center">กำลังโหลดข้อมูล...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Movement Report -->
            <div id="movement-report" class="report-content">
                <div class="summary-cards">
                    <div class="summary-card sales">
                        <div class="summary-value" id="incoming-stock">0</div>
                        <div class="summary-label">การรับเข้าสต็อก</div>
                    </div>
                    <div class="summary-card orders">
                        <div class="summary-value" id="outgoing-stock">0</div>
                        <div class="summary-label">การเบิกออก</div>
                    </div>
                    <div class="summary-card products">
                        <div class="summary-value" id="net-movement">0</div>
                        <div class="summary-label">ยอดคงเหลือสุทธิ</div>
                    </div>
                    <div class="summary-card warning">
                        <div class="summary-value" id="stock-adjustments">0</div>
                        <div class="summary-label">รายการปรับปรุง</div>
                    </div>
                </div>

                <div class="report-section">
                    <div class="section-title">
                        <i class="fas fa-table"></i>
                        รายละเอียดการเคลื่อนไหวสินค้า
                    </div>
                    <div class="search-box">
                        <input type="text" class="table-search" data-table="movement-table" placeholder="ค้นหาการเคลื่อนไหว...">
                    </div>
                    <div class="table-container">
                        <table class="data-table" id="movement-table">
                            <thead>
                                <tr>
                                    <th>วันที่</th>
                                    <th>รหัสสินค้า</th>
                                    <th>ชื่อสินค้า</th>
                                    <th>ประเภท</th>
                                    <th>จำนวน</th>
                                    <th>ก่อน</th>
                                    <th>หลัง</th>
                                    <th>อ้างอิง</th>
                                    <th>ผู้ดำเนินการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="9" class="text-center">กำลังโหลดข้อมูล...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Shipping Report -->
            <div id="shipping-report" class="report-content">
                <div class="summary-cards">
                    <div class="summary-card sales">
                        <div class="summary-value" id="shipped-orders">0</div>
                        <div class="summary-label">คำสั่งซื้อที่จัดส่งแล้ว</div>
                    </div>
                    <div class="summary-card warning">
                        <div class="summary-value" id="pending-orders">0</div>
                        <div class="summary-label">คำสั่งซื้อรอจัดส่ง</div>
                    </div>
                    <div class="summary-card orders">
                        <div class="summary-value" id="total-shipping-fee">฿0</div>
                        <div class="summary-label">ค่าจัดส่งรวม</div>
                    </div>
                    <div class="summary-card products">
                        <div class="summary-value" id="avg-shipping-fee">฿0</div>
                        <div class="summary-label">ค่าจัดส่งเฉลี่ย</div>
                    </div>
                </div>

                <div class="report-section">
                    <div class="section-title">
                        <i class="fas fa-table"></i>
                        รายงานการจัดส่งตามเขต
                    </div>
                    <div class="search-box">
                        <input type="text" class="table-search" data-table="shipping-table" placeholder="ค้นหาเขตจัดส่ง...">
                    </div>
                    <div class="table-container">
                        <table class="data-table" id="shipping-table">
                            <thead>
                                <tr>
                                    <th>เขตจัดส่ง</th>
                                    <th>คำสั่งซื้อทั้งหมด</th>
                                    <th>จัดส่งแล้ว</th>
                                    <th>อัตราความสำเร็จ</th>
                                    <th>ค่าจัดส่งรวม</th>
                                    <th>ค่าจัดส่งเฉลี่ย</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center">กำลังโหลดข้อมูล...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Customer Report -->
            <div id="customer-report" class="report-content">
                <div class="summary-cards">
                    <div class="summary-card sales">
                        <div class="summary-value" id="new-customers">0</div>
                        <div class="summary-label">ลูกค้าใหม่</div>
                    </div>
                    <div class="summary-card orders">
                        <div class="summary-value" id="returning-customers">0</div>
                        <div class="summary-label">ลูกค้าเก่า</div>
                    </div>
                    <div class="summary-card products">
                        <div class="summary-value" id="avg-order-value">฿0</div>
                        <div class="summary-label">คำสั่งซื้อเฉลี่ย</div>
                    </div>
                    <div class="summary-card warning">
                        <div class="summary-value" id="avg-orders-per-customer">0</div>
                        <div class="summary-label">คำสั่งซื้อต่อลูกค้า</div>
                    </div>
                </div>

                <div class="report-section">
                    <div class="section-title">
                        <i class="fas fa-table"></i>
                        ลูกค้าอันดับต้น
                    </div>
                    <div class="search-box">
                        <input type="text" class="table-search" data-table="customer-table" placeholder="ค้นหาลูกค้า...">
                    </div>
                    <div class="table-container">
                        <table class="data-table" id="customer-table">
                            <thead>
                                <tr>
                                    <th>ชื่อลูกค้า</th>
                                    <th>อีเมล</th>
                                    <th>จำนวนคำสั่งซื้อ</th>
                                    <th>ยอดซื้อรวม</th>
                                    <th>ค่าเฉลี่ยต่อคำสั่ง</th>
                                    <th>คำสั่งซื้อล่าสุด</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center">กำลังโหลดข้อมูล...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="reports_admin.js"></script>
</body>
</html>