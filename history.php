<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>คำสั่งซื้อของฉัน</title>
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

    /* Loading Spinner */
    .loading {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid #f3f3f3;
        border-top: 3px solid #d32f2f;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 2000;
        display: none;
    }

    .loading-overlay.active {
        display: flex;
    }

    .loading-spinner {
        width: 50px;
        height: 50px;
        border: 5px solid #f3f3f3;
        border-top: 5px solid #d32f2f;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    /* Error Message */
    .error-message {
        display: none;
        background-color: #f8d7da;
        color: #721c24;
        padding: 15px;
        border: 1px solid #f5c6cb;
        border-radius: 5px;
        margin-bottom: 20px;
    }

    /* Main Content */
    .container {
        max-width: 1200px;
        margin: 30px auto;
        padding: 0 20px;
    }

    /* Page Header */
    .page-header {
        background-color: white;
        border-radius: 10px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        text-align: center;
    }

    .page-title {
        font-size: 28px;
        color: #d32f2f;
        margin-bottom: 10px;
        font-weight: bold;
    }

    .page-subtitle {
        color: #666;
        font-size: 16px;
    }

    /* Filter Section */
    .filter-section {
        background-color: white;
        border-radius: 10px;
        padding: 20px 30px;
        margin-bottom: 30px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .filter-row {
        display: flex;
        gap: 20px;
        align-items: flex-end;
        flex-wrap: wrap;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .filter-group label {
        font-size: 14px;
        font-weight: bold;
        color: #333;
    }

    .filter-group select,
    .filter-group input {
        padding: 10px 12px;
        border: 2px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
        min-width: 150px;
    }

    .filter-group select:focus,
    .filter-group input:focus {
        outline: none;
        border-color: #d32f2f;
    }

    .filter-buttons {
        display: flex;
        gap: 10px;
        align-items: flex-end;
    }

    .btn-filter {
        background-color: #d32f2f;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        font-weight: bold;
        transition: background-color 0.3s;
        height: 42px;
    }

    .btn-filter:hover {
        background-color: #b71c1c;
    }

    .btn-clear {
        background-color: #6c757d;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        transition: background-color 0.3s;
        height: 42px;
    }

    .btn-clear:hover {
        background-color: #5a6268;
    }

    /* Order Section */
    .order-section {
        background-color: white;
        border-radius: 10px;
        padding: 30px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .order-summary {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #f0f0f0;
    }

    .order-count {
        font-size: 18px;
        color: #333;
        font-weight: bold;
    }

    .order-sort {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .order-sort select {
        padding: 8px 12px;
        border: 2px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
    }

    /* Order Item */
    .order-item {
        border: 2px solid #ddd;
        border-radius: 10px;
        padding: 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        transition: all 0.3s ease;
        background: white;
    }

    .order-item:hover {
        border-color: #d32f2f;
        box-shadow: 0 4px 15px rgba(211, 47, 47, 0.1);
        transform: translateY(-2px);
    }

    .order-details {
        flex: 1;
    }

    .order-id {
        font-weight: bold;
        margin-bottom: 12px;
        color: #333;
        font-size: 16px;
    }

    .order-date {
        color: #d32f2f;
        font-size: 14px;
        margin-bottom: 12px;
        font-weight: 500;
    }

    .order-amount {
        color: #666;
        margin-bottom: 8px;
        font-size: 14px;
    }

    .order-total {
        font-weight: bold;
        color: #333;
        font-size: 16px;
    }

    .order-actions {
        display: flex;
        flex-direction: column;
        gap: 10px;
        align-items: flex-end;
    }

    .btn-view {
        background-color: #1e3a5f;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        text-decoration: none;
        font-size: 14px;
        font-weight: bold;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        min-width: 120px;
    }

    .btn-view:hover {
        background-color: #2c4e73;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(30, 58, 95, 0.3);
    }

    .btn-reorder {
        background-color: #28a745;
        color: white;
        padding: 8px 16px;
        border: none;
        border-radius: 6px;
        font-size: 12px;
        cursor: pointer;
        transition: background-color 0.3s;
        min-width: 120px;
    }

    .btn-reorder:hover {
        background-color: #218838;
    }

    .btn-cancel {
        background-color: #dc3545;
        color: white;
        padding: 8px 16px;
        border: none;
        border-radius: 6px;
        font-size: 12px;
        cursor: pointer;
        transition: background-color 0.3s;
        min-width: 120px;
    }

    .btn-cancel:hover {
        background-color: #c82333;
    }

    /* Status Badges */
    .status-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
        text-align: center;
        min-width: 100px;
        margin-left: 10px;
    }

    .status-pending {
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeaa7;
    }

    .status-awaiting {
        background-color: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }

    .status-transit {
        background-color: #cce5ff;
        color: #004085;
        border: 1px solid #b3d7ff;
    }

    .status-delivered {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .status-cancelled {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .status-default {
        background-color: #e2e3e5;
        color: #383d41;
        border: 1px solid #d6d8db;
    }

    /* No orders message */
    .no-orders {
        text-align: center;
        padding: 60px 20px;
        color: #666;
        font-style: italic;
        background-color: #f8f9fa;
        border-radius: 10px;
        border: 2px dashed #dee2e6;
    }

    .no-orders-icon {
        font-size: 48px;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    .no-orders-text {
        font-size: 18px;
        margin-bottom: 10px;
    }

    .no-orders-subtext {
        font-size: 14px;
        color: #999;
    }

    /* Order items container */
    .order-items-container {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    /* Pagination */
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-top: 30px;
        gap: 10px;
    }

    .pagination button {
        padding: 10px 15px;
        border: 2px solid #ddd;
        background: white;
        color: #333;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s;
    }

    .pagination button:hover:not(:disabled) {
        background: #d32f2f;
        color: white;
        border-color: #d32f2f;
    }

    .pagination button.active {
        background: #d32f2f;
        color: white;
        border-color: #d32f2f;
    }

    .pagination button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Statistics Cards */
    .stats-section {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        text-align: center;
        transition: transform 0.3s;
    }

    .stat-card:hover {
        transform: translateY(-5px);
    }

    .stat-number {
        font-size: 24px;
        font-weight: bold;
        color: #d32f2f;
        margin-bottom: 5px;
    }

    .stat-label {
        font-size: 14px;
        color: #666;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .order-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 20px;
        }

        .order-actions {
            align-items: flex-start;
            flex-direction: row;
            gap: 10px;
            width: 100%;
        }

        .filter-row {
            flex-direction: column;
            align-items: stretch;
        }

        .filter-group {
            width: 100%;
        }

        .filter-group select,
        .filter-group input {
            min-width: 100%;
        }

        .filter-buttons {
            width: 100%;
            justify-content: center;
            margin-top: 15px;
        }

        .order-summary {
            flex-direction: column;
            gap: 15px;
            text-align: center;
        }

        .stats-section {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    /* Success Message */
    .success-message {
        display: none;
        background-color: #d4edda;
        color: #155724;
        padding: 15px;
        border: 1px solid #c3e6cb;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    </style>
</head>

<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Header -->
    <?php include("header.php"); ?>

    <!-- Main Content -->
    <div class="container">
        <!-- Success Message -->
        <div id="successMessage" class="success-message">
            ดำเนินการเรียบร้อยแล้ว
        </div>

        <!-- Error Message -->
        <div id="errorMessage" class="error-message">
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">คำสั่งซื้อของฉัน</h1>
            <p class="page-subtitle">ตรวจสอบสถานะและประวัติการสั่งซื้อของคุณ</p>
        </div>

        <!-- Statistics Section -->
        <div class="stats-section">
            <div class="stat-card">
                <div class="stat-number" id="totalOrders">0</div>
                <div class="stat-label">คำสั่งซื้อทั้งหมด</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="pendingOrders">0</div>
                <div class="stat-label">รอดำเนินการ</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="completedOrders">0</div>
                <div class="stat-label">เสร็จสิ้น</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="totalSpent">0</div>
                <div class="stat-label">ยอดรวมการสั่งซื้อ</div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="statusFilter">สถานะ</label>
                    <select id="statusFilter">
                        <option value="">ทุกสถานะ</option>
                        <option value="pending_payment">รอการชำระเงิน</option>
                        <option value="awaiting_shipment">รอจัดส่ง</option>
                        <option value="in_transit">กำลังจัดส่ง</option>
                        <option value="delivered">จัดส่งแล้ว</option>
                        <option value="cancelled">ยกเลิก</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="dateFrom">วันที่เริ่มดำเนินการ</label>
                    <input type="date" id="dateFrom">
                </div>
                <div class="filter-group">
                    <label for="dateTo">วันที่สิ้นสุดดำเนินการ</label>
                    <input type="date" id="dateTo">
                </div>
                <div class="filter-buttons">
                    <button class="btn-filter" onclick="applyFilters()">กรองข้อมูล</button>
                    <button class="btn-clear" onclick="clearFilters()">ล้างตัวกรอง</button>
                </div>
            </div>
        </div>

        <!-- Order Section -->
        <div class="order-section">
            <div class="order-summary">
                <div class="order-count" id="orderCount">กำลังโหลดคำสั่งซื้อ...</div>
                <div class="order-sort">
                    <label for="sortOrder">เรียงตาม:</label>
                    <select id="sortOrder" onchange="sortOrders()">
                        <option value="newest">ล่าสุด</option>
                        <option value="oldest">เก่าสุด</option>
                        <option value="amount_high">ราคาสูงสุด</option>
                        <option value="amount_low">ราคาต่ำสุด</option>
                    </select>
                </div>
            </div>

            <div class="order-items-container" id="orderContainer">
                <div class="no-orders">
                    <div class="no-orders-icon">📦</div>
                    <div class="no-orders-text">กำลังโหลดคำสั่งซื้อ...</div>
                </div>
            </div>

            <!-- Pagination -->
            <div class="pagination" id="pagination" style="display: none;">
                <button onclick="changePage('prev')" id="prevBtn">ก่อนหน้า</button>
                <span id="pageInfo">หน้า 1 จาก 1</span>
                <button onclick="changePage('next')" id="nextBtn">ถัดไป</button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include("footer.php"); ?>

</body>

<script src="history.js"></script>

</html>