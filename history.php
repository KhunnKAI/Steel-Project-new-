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
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
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
        align-items: center;
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
        margin-top: 10px;
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
                    <label for="dateFrom">วันที่เริ่มต้น</label>
                    <input type="date" id="dateFrom">
                </div>
                <div class="filter-group">
                    <label for="dateTo">วันที่สิ้นสุด</label>
                    <input type="date" id="dateTo">
                </div>
            </div>
            <div class="filter-buttons">
                <button class="btn-filter" onclick="applyFilters()">กรองข้อมูล</button>
                <button class="btn-clear" onclick="clearFilters()">ล้างตัวกรอง</button>
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

    <script>
        let currentUserId = null;
        let allOrders = [];
        let filteredOrders = [];
        let currentPage = 1;
        const ordersPerPage = 10;

        // API Configuration
        const API_BASE_URL = './';
        const API_ENDPOINTS = {
            ORDER: API_BASE_URL + 'controllers/order_api.php'
        };

        // Initialize page
        document.addEventListener('DOMContentLoaded', function () {
            initializePage();
        });

        function initializePage() {
            currentUserId = getCookie('user_id');
            if (!currentUserId) {
                window.location.href = 'login.php';
                return;
            }

            loadOrderData();
        }

        // API Helper Functions
        function showLoading() {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) overlay.classList.add('active');
        }

        function hideLoading() {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) overlay.classList.remove('active');
        }

        function showError(message) {
            const errorDiv = document.getElementById('errorMessage');
            if (errorDiv) {
                const displayMessage = Array.isArray(message) ? message.join('\n') : message;
                errorDiv.innerHTML = displayMessage.replace(/\n/g, '<br>');
                errorDiv.style.display = 'block';
                errorDiv.scrollIntoView({ behavior: 'smooth' });

                setTimeout(() => {
                    errorDiv.style.display = 'none';
                }, 8000);
            }
        }

        function showSuccess(message) {
            const successDiv = document.getElementById('successMessage');
            if (successDiv) {
                successDiv.textContent = message;
                successDiv.style.display = 'block';
                successDiv.scrollIntoView({ behavior: 'smooth' });

                setTimeout(() => {
                    successDiv.style.display = 'none';
                }, 3000);
            }
        }

        function getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
            return null;
        }

        // Order Data Functions
        async function loadOrderData() {
            try {
                showLoading();

                const response = await fetch(`${API_ENDPOINTS.ORDER}?action=get_user_orders&user_id=${currentUserId}`, {
                    method: "GET"
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();

                if (data.success) {
                    allOrders = data.data || [];
                    filteredOrders = [...allOrders];
                    updateStatistics();
                    displayOrders();
                } else {
                    showError(data.message || 'ไม่สามารถโหลดข้อมูลคำสั่งซื้อได้');
                    displayOrders([]);
                }
            } catch (error) {
                console.error("Error loading orders:", error);
                showError('เกิดข้อผิดพลาดในการโหลดข้อมูลคำสั่งซื้อ');
                displayOrders([]);
            } finally {
                hideLoading();
            }
        }

        function updateStatistics() {
            const totalOrders = allOrders.length;
            const pendingOrders = allOrders.filter(order => 
                ['pending_payment', 'awaiting_shipment', 'in_transit'].includes(order.status.status_code)
            ).length;
            const completedOrders = allOrders.filter(order => 
                order.status.status_code === 'delivered'
            ).length;
            const totalSpent = allOrders
                .filter(order => order.status.status_code !== 'cancelled')
                .reduce((sum, order) => sum + parseFloat(order.total_amount || 0), 0);

            document.getElementById('totalOrders').textContent = totalOrders;
            document.getElementById('pendingOrders').textContent = pendingOrders;
            document.getElementById('completedOrders').textContent = completedOrders;
            document.getElementById('totalSpent').textContent = formatCurrency(totalSpent);
        }

        function displayOrders() {
            const container = document.getElementById('orderContainer');
            const countElement = document.getElementById('orderCount');
            
            if (!container) return;

            // Update order count
            if (countElement) {
                const totalCount = filteredOrders.length;
                countElement.textContent = `พบคำสั่งซื้อ ${totalCount} รายการ`;
            }

            if (!filteredOrders || filteredOrders.length === 0) {
                container.innerHTML = `
                    <div class="no-orders">
                        <div class="no-orders-icon">📦</div>
                        <div class="no-orders-text">ไม่พบคำสั่งซื้อ</div>
                        <div class="no-orders-subtext">คุณยังไม่มีประวัติการสั่งซื้อสินค้า</div>
                    </div>
                `;
                document.getElementById('pagination').style.display = 'none';
                return;
            }

            // Calculate pagination
            const totalPages = Math.ceil(filteredOrders.length / ordersPerPage);
            const startIndex = (currentPage - 1) * ordersPerPage;
            const endIndex = startIndex + ordersPerPage;
            const currentOrders = filteredOrders.slice(startIndex, endIndex);

            let html = '';
            currentOrders.forEach((order) => {
                const orderDate = new Date(order.created_at).toLocaleDateString('th-TH', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit'
                });

                const statusText = getStatusText(order.status.status_code);
                const statusClass = getStatusClass(order.status.status_code);

                // Action buttons based on status
                let actionButtons = `<a href="bill.php?order_id=${order.order_id}" class="btn-view">ดูรายละเอียด</a>`;
                
                if (order.status.status_code === 'pending_payment') {
                    actionButtons += `<button class="btn-cancel" onclick="cancelOrder('${order.order_id}')">ยกเลิกคำสั่งซื้อ</button>`;
                } else if (order.status.status_code === 'delivered') {
                    actionButtons += `<button class="btn-reorder" onclick="reorder('${order.order_id}')">สั่งซื้ออีกครั้ง</button>`;
                }

                html += `
                    <div class="order-item">
                        <div class="order-details">
                            <div class="order-id">เลขคำสั่งซื้อ ${order.order_id}</div>
                            <div class="order-date">
                                วันที่สั่งซื้อ ${orderDate}
                                <span class="status-badge ${statusClass}">${statusText}</span>
                            </div>
                            <div class="order-amount">จำนวนสินค้า ${order.total_quantity || 0} รายการ</div>
                            <div class="order-total">ยอดรวม ${formatCurrency(order.total_amount)}</div>
                        </div>
                        <div class="order-actions">
                            ${actionButtons}
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;

            // Update pagination
            updatePagination(totalPages);
        }

        function updatePagination(totalPages) {
            const pagination = document.getElementById('pagination');
            const pageInfo = document.getElementById('pageInfo');
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');

            if (totalPages <= 1) {
                pagination.style.display = 'none';
                return;
            }

            pagination.style.display = 'flex';
            pageInfo.textContent = `หน้า ${currentPage} จาก ${totalPages}`;
            prevBtn.disabled = currentPage <= 1;
            nextBtn.disabled = currentPage >= totalPages;
        }

        function changePage(direction) {
            const totalPages = Math.ceil(filteredOrders.length / ordersPerPage);
            
            if (direction === 'prev' && currentPage > 1) {
                currentPage--;
            } else if (direction === 'next' && currentPage < totalPages) {
                currentPage++;
            }

            displayOrders();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Filter and Sort Functions
        function applyFilters() {
            const statusFilter = document.getElementById('statusFilter').value;
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;

            filteredOrders = allOrders.filter(order => {
                // Status filter
                if (statusFilter && order.status.status_code !== statusFilter) {
                    return false;
                }

                // Date filter
                const orderDate = new Date(order.created_at).toISOString().split('T')[0];
                if (dateFrom && orderDate < dateFrom) {
                    return false;
                }
                if (dateTo && orderDate > dateTo) {
                    return false;
                }

                return true;
            });

            currentPage = 1;
            displayOrders();
            showSuccess('ใช้ตัวกรองข้อมูลแล้ว');
        }

        function clearFilters() {
            document.getElementById('statusFilter').value = '';
            document.getElementById('dateFrom').value = '';
            document.getElementById('dateTo').value = '';
            
            filteredOrders = [...allOrders];
            currentPage = 1;
            displayOrders();
            showSuccess('ล้างตัวกรองแล้ว');
        }

        function sortOrders() {
            const sortValue = document.getElementById('sortOrder').value;

            filteredOrders.sort((a, b) => {
                switch (sortValue) {
                    case 'newest':
                        return new Date(b.created_at) - new Date(a.created_at);
                    case 'oldest':
                        return new Date(a.created_at) - new Date(b.created_at);
                    case 'amount_high':
                        return parseFloat(b.total_amount) - parseFloat(a.total_amount);
                    case 'amount_low':
                        return parseFloat(a.total_amount) - parseFloat(b.total_amount);
                    default:
                        return new Date(b.created_at) - new Date(a.created_at);
                }
            });

            currentPage = 1;
            displayOrders();
        }

        // Order Actions
        async function cancelOrder(orderId) {
            if (!confirm('คุณต้องการยกเลิกคำสั่งซื้อนี้หรือไม่?')) {
                return;
            }

            try {
                showLoading();

                const formData = new FormData();
                formData.append('action', 'cancel_order');
                formData.append('order_id', orderId);
                formData.append('user_id', currentUserId);

                const response = await fetch(API_ENDPOINTS.ORDER, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showSuccess('ยกเลิกคำสั่งซื้อเรียบร้อยแล้ว');
                    await loadOrderData(); // Reload orders
                } else {
                    showError(result.message || 'ไม่สามารถยกเลิกคำสั่งซื้อได้');
                }
            } catch (error) {
                console.error('Error cancelling order:', error);
                showError('เกิดข้อผิดพลาดในการยกเลิกคำสั่งซื้อ');
            } finally {
                hideLoading();
            }
        }

        async function reorder(orderId) {
            if (!confirm('คุณต้องการสั่งซื้อสินค้าเหล่านี้อีกครั้งหรือไม่?')) {
                return;
            }

            try {
                showLoading();

                // Get order details first
                const response = await fetch(`${API_ENDPOINTS.ORDER}?action=get_order_details&order_id=${orderId}`);
                const result = await response.json();

                if (result.success && result.data.order_items) {
                    // Add items to cart
                    const cart = JSON.parse(localStorage.getItem('shopping_cart') || '{}');
                    
                    result.data.order_items.forEach(item => {
                        const productId = item.product_id.toString();
                        if (cart[productId]) {
                            cart[productId].quantity += parseInt(item.quantity);
                        } else {
                            cart[productId] = {
                                product_id: item.product_id,
                                name: item.product_name,
                                price: parseFloat(item.price),
                                quantity: parseInt(item.quantity),
                                image: item.product_image || ''
                            };
                        }
                    });

                    localStorage.setItem('shopping_cart', JSON.stringify(cart));
                    
                    // Update cart badge if function exists
                    if (typeof window.updateCartBadge === 'function') {
                        window.updateCartBadge();
                    }

                    showSuccess('เพิ่มสินค้าในตะกร้าแล้ว');
                    
                    // Ask if user wants to go to cart
                    setTimeout(() => {
                        if (confirm('ต้องการไปที่ตะกร้าสินค้าเพื่อทำการสั่งซื้อหรือไม่?')) {
                            window.location.href = 'cart.php';
                        }
                    }, 1500);

                } else {
                    showError('ไม่สามารถโหลดข้อมูลสินค้าได้');
                }
            } catch (error) {
                console.error('Error reordering:', error);
                showError('เกิดข้อผิดพลาดในการสั่งซื้ออีกครั้ง');
            } finally {
                hideLoading();
            }
        }

        // Utility Functions
        function getStatusText(statusCode) {
            const statusMap = {
                'pending_payment': 'รอการชำระเงิน',
                'awaiting_shipment': 'รอจัดส่ง',
                'in_transit': 'กำลังจัดส่ง',
                'delivered': 'จัดส่งแล้ว',
                'cancelled': 'ยกเลิก'
            };
            return statusMap[statusCode] || statusCode;
        }

        function getStatusClass(statusCode) {
            const classMap = {
                'pending_payment': 'status-pending',
                'awaiting_shipment': 'status-awaiting',
                'in_transit': 'status-transit',
                'delivered': 'status-delivered',
                'cancelled': 'status-cancelled'
            };
            return classMap[statusCode] || 'status-default';
        }

        function formatCurrency(amount) {
            return new Intl.NumberFormat('th-TH', {
                style: 'currency',
                currency: 'THB',
                minimumFractionDigits: 2
            }).format(amount);
        }

        // Set current date as default for date filters
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date();
            const thirtyDaysAgo = new Date();
            thirtyDaysAgo.setDate(today.getDate() - 30);

            // Set default date range to last 30 days
            document.getElementById('dateFrom').value = thirtyDaysAgo.toISOString().split('T')[0];
            document.getElementById('dateTo').value = today.toISOString().split('T')[0];
        });

        // Export orders (optional feature)
        function exportOrders() {
            if (filteredOrders.length === 0) {
                showError('ไม่มีข้อมูลคำสั่งซื้อที่จะส่งออก');
                return;
            }

            const csvContent = "data:text/csv;charset=utf-8," + 
                "เลขคำสั่งซื้อ,วันที่สั่งซื้อ,สถานะ,จำนวนสินค้า,ยอดรวม\n" +
                filteredOrders.map(order => {
                    const date = new Date(order.created_at).toLocaleDateString('th-TH');
                    const status = getStatusText(order.status.status_code);
                    return `${order.order_id},"${date}","${status}",${order.total_quantity || 0},${order.total_amount}`;
                }).join("\n");

            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", `orders_${new Date().toISOString().split('T')[0]}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            showSuccess('ส่งออกข้อมูลคำสั่งซื้อแล้ว');
        }
    </script>
</body>
</html>