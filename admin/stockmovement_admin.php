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
    <title>บันทึกการเคลื่อนไหวสต็อค - ระบบจัดการร้านค้า</title>
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
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-weight: 500;
            font-size: 14px;
        }

        .filters-section {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            padding: 25px 30px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            position: relative;
        }

        .filters-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #990000, #cc0000, #ff6b6b);
            border-radius: 20px 20px 0 0;
        }

        .filters-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .filters-header h3 {
            color: #333;
            font-size: 18px;
            font-weight: 600;
            margin: 0;
        }

        .filters-header i {
            color: #990000;
            font-size: 20px;
        }

        .filters-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: end;
        }

        .filter-group {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            font-size: 13px;
            color: #555;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-group select,
        .filter-group input {
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            position: relative;
        }

        .filter-group select:hover,
        .filter-group input:hover {
            border-color: #990000;
            box-shadow: 0 4px 12px rgba(153, 0, 0, 0.15);
            transform: translateY(-1px);
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #990000;
            box-shadow: 0 0 0 4px rgba(153, 0, 0, 0.1), 0 4px 12px rgba(153, 0, 0, 0.15);
            transform: translateY(-1px);
        }

        .reset-all-btn {
            padding: 12px 20px;
            background: linear-gradient(45deg, #dc3545, #c82333);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            width: 100%;
            justify-content: center;
        }

        .reset-all-btn:hover {
            background: linear-gradient(45deg, #c82333, #bd2130);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
        }

        .table-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .table-header {
            padding: 20px 30px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            background: #f8f9fa;
        }

        .table-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }

        .search-container {
            position: relative;
            flex: 1;
            max-width: 400px;
        }

        .search-container input {
            width: 100%;
            padding: 12px 45px 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .search-container input:focus {
            outline: none;
            border-color: #990000;
            box-shadow: 0 0 0 3px rgba(153, 0, 0, 0.1);
        }

        .search-container i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #f0f0f0;
            white-space: nowrap;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
            color: #555;
        }

        tr:hover {
            background: rgba(153, 0, 0, 0.02);
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            padding: 20px;
        }

        .pagination button {
            padding: 8px 12px;
            border: 2px solid #e9ecef;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .pagination button:hover,
        .pagination button.active {
            background: #990000;
            color: white;
            border-color: #990000;
        }

        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                text-align: center;
            }

            .header-title h1 {
                font-size: 1.5rem;
            }

            .container {
                padding: 1rem;
            }

            .filters-row {
                grid-template-columns: 1fr;
            }

            .table-header {
                flex-direction: column;
                align-items: stretch;
            }

            .search-container {
                max-width: none;
            }

            .pagination {
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <!-- Header Section -->
    <div class="header-section">
        <div class="header-content">
            <div class="header-title">
                <i class="fas fa-exchange-alt"></i>
                <h1>บันทึกการเคลื่อนไหวสต็อค</h1>
            </div>
            <a href="products_admin.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                กลับสู่หน้าจัดการสินค้า
            </a>
        </div>
    </div>

    <div class="container">
        <!-- Statistics Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-number" id="totalMovements">0</div>
                <div class="stat-label">การเคลื่อนไหวทั้งหมด</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="todayMovements">0</div>
                <div class="stat-label">การเคลื่อนไหววันนี้</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="receivedToday">0</div>
                <div class="stat-label">รับเข้าวันนี้</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="dispatchedToday">0</div>
                <div class="stat-label">เบิกออกวันนี้</div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="filters-section">
            <div class="filters-header">
                <i class="fas fa-filter"></i>
                <h3>ตัวกรองข้อมูล</h3>
            </div>
            <div class="filters-row">
                <div class="filter-group">
                    <label><i class="fas fa-tags"></i> ประเภทการเคลื่อนไหว</label>
                    <select id="movementTypeFilter">
                        <option value="">ทั้งหมด</option>
                        <option value="in">รับเข้า</option>
                        <option value="out">เบิกออก</option>
                        <option value="adjust">ปรับปรุง</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-calendar-alt"></i> วันที่เริ่มต้น</label>
                    <input type="date" id="startDateFilter">
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-calendar-check"></i> วันที่สิ้นสุด</label>
                    <input type="date" id="endDateFilter">
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-user"></i> ผู้ดำเนินการ</label>
                    <input type="text" id="userFilter" placeholder="ชื่อผู้ดำเนินการ">
                </div>
                <div class="filter-group">
                    <button class="reset-all-btn" onclick="resetAllFilters()">
                        <i class="fas fa-undo"></i>
                        รีเซ็ตทั้งหมด
                    </button>
                </div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="table-container">
            <div class="table-header">
                <h3 class="table-title">
                    <i class="fas fa-list"></i> 
                    รายการการเคลื่อนไหวสต็อค
                </h3>
                <div class="search-container">
                    <input type="text" id="searchInput" placeholder="ค้นหาตามรหัสสินค้า, ชื่อสินค้า หรือหมายเหตุ...">
                    <i class="fas fa-search"></i>
                </div>
            </div>

            <div class="table-wrapper">
                <table id="movementsTable">
                    <thead>
                        <tr>
                            <th>วันที่/เวลา</th>
                            <th>ประเภท</th>
                            <th>รหัสสินค้า</th>
                            <th>ชื่อสินค้า</th>
                            <th>ล็อต</th>
                            <th>จำนวนที่เปลี่ยน</th>
                            <th>สต็อคก่อน</th>
                            <th>สต็อคหลัง</th>
                            <th>ผู้ดำเนินการ</th>
                            <th>หมายเหตุ</th>
                        </tr>
                    </thead>
                    <tbody id="movementsTableBody">
                        <!-- Movement rows will be inserted here -->
                    </tbody>
                </table>
            </div>

            <div class="pagination" id="pagination">
                <!-- Pagination buttons will be inserted here -->
            </div>
        </div>
    </div>

    <script src="stockmovement_admin.js"></script>
</body>
</html>