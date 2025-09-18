<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สถานะในเสร็จ</title>
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

        .github-icon {
            width: 32px;
            height: 32px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
            font-weight: bold;
        }

        .admin-text {
            font-size: 0.9rem;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-icons {
            display: flex;
            gap: 0.5rem;
        }

        .user-icon {
            width: 32px;
            height: 32px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .user-icon:hover {
            background: rgba(255,255,255,0.3);
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .main-title {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 1.5rem;
            color: #333;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #555;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #d32f2f;
        }

        .form-input:disabled {
            background: #f5f5f5;
            color: #999;
        }

        .order-details {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 12px;
            margin-top: 1rem;
        }

        .detail-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e1e5e9;
        }

        .detail-row:last-child {
            border-bottom: none;
            font-weight: bold;
            color: #d32f2f;
        }

        .detail-label {
            font-weight: 500;
            color: #555;
        }

        .detail-value {
            color: #333;
            text-align: right;
        }

        .product-list {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 12px;
            margin-top: 1rem;
        }

        .product-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid #e1e5e9;
        }

        .product-item:last-child {
            border-bottom: none;
        }

        .product-image {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
            transition: transform 0.3s ease;
        }

        .product-image img:hover {
            transform: scale(1.05);
        }

        .product-image-placeholder {
            width: 100%;
            height: 100%;
            background: #e1e5e9;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 1.5rem;
        }

        .product-info {
            flex: 1;
        }

        .product-name {
            font-weight: bold;
            margin-bottom: 0.25rem;
            color: #333;
        }

        .product-code {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.25rem;
        }

        .product-price {
            color: #d32f2f;
            font-weight: bold;
        }

        .product-quantity {
            text-align: right;
            font-weight: bold;
            color: #333;
        }

        .submit-btn {
            width: 100%;
            background: #1a237e;
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            background: #303f9f;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 35, 126, 0.3);
        }

        .status-timeline {
            margin-top: 2rem;
        }

        .timeline-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid #e1e5e9;
        }

        .timeline-item:last-child {
            border-bottom: none;
        }

        .timeline-icon {
            width: 50px;
            height: 50px;
            background: #e1e5e9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .timeline-icon.active {
            background: #d32f2f;
            color: white;
        }

        .timeline-content {
            flex: 1;
        }

        .timeline-title {
            font-weight: bold;
            margin-bottom: 0.25rem;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .section {
            animation: fadeInUp 0.6s ease;
        }

        .section:nth-child(2) { animation-delay: 0.1s; }
        .section:nth-child(3) { animation-delay: 0.2s; }
        .section:nth-child(4) { animation-delay: 0.3s; }
        .section:nth-child(5) { animation-delay: 0.4s; }

        /* Loading and Error States */
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

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .section {
                padding: 1.5rem;
            }

            .product-item {
                flex-direction: column;
                text-align: center;
            }

            .product-quantity {
                text-align: center;
            }

            .detail-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.25rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include("header.php");?>
    
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>
    
    <div class="container">
        <div class="main-title">
            สถานะใบเสร็จ
        </div>

        <div class="section">
            <div class="section-title">สถานะสินค้า</div>
            
            <div class="status-timeline">
                <div class="timeline-item">
                    <div class="timeline-icon">⏳</div>
                    <div class="timeline-content">
                        <div class="timeline-title">รอตรวจสอบการการชำระเงิน</div>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-icon">📦</div>
                    <div class="timeline-content">
                        <div class="timeline-title">รอจัดส่ง</div>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-icon">🚚</div>
                    <div class="timeline-content">
                        <div class="timeline-title">กำลังจัดส่ง</div>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-icon">✅</div>
                    <div class="timeline-content">
                        <div class="timeline-title">จัดส่งแล้ว</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">รายละเอียดคำสั่งซื้อ</div>
            
            <div class="order-details">
                <div class="detail-row">
                    <span class="detail-label">หมายเลขคำสั่งซื้อ:</span>
                    <span class="detail-value">#ORD202509050004</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">วันที่สั่งซื้อ:</span>
                    <span class="detail-value">5 กันยายน 2568 เวลา 15:32</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">ชื่อผู้สั่ง:</span>
                    <span class="detail-value">Pooh ZAZA</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">ช่องทางการชำระ:</span>
                    <span class="detail-value">โอนเงินผ่านธนาคาร</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">เบอร์โทรศัพท์:</span>
                    <span class="detail-value">0999999999</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">ยอดรวมทั้งหมด:</span>
                    <span class="detail-value">8329 บาท</span>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">รายการสินค้า</div>
            
            <div class="product-list">
                <div class="product-item">
                    <div class="product-image">
                        <div class="product-image-placeholder">🖼️</div>
                    </div>
                    <div class="product-info">
                        <div class="product-name">รองเท้าผ้าใบ Nike</div>
                        <div class="product-code">รหัสสินค้า: NK001</div>
                        <div class="product-price">฿ 3,500</div>
                    </div>
                    <div class="product-quantity">จำนวน: 1</div>
                </div>

                <div class="product-item">
                    <div class="product-image">
                        <div class="product-image-placeholder">🖼️</div>
                    </div>
                    <div class="product-info">
                        <div class="product-name">เสื้อยืดแฟชั่น</div>
                        <div class="product-code">รหัสสินค้า: TS002</div>
                        <div class="product-price">฿ 890</div>
                    </div>
                    <div class="product-quantity">จำนวน: 2</div>
                </div>

                <div class="product-item">
                    <div class="product-image">
                        <div class="product-image-placeholder">🖼️</div>
                    </div>
                    <div class="product-info">
                        <div class="product-name">กระเป๋าสะพายหลัง</div>
                        <div class="product-code">รหัสสินค้า: BG003</div>
                        <div class="product-price">฿ 2,400</div>
                    </div>
                    <div class="product-quantity">จำนวน: 1</div>
                </div>

                <div class="detail-row" style="margin-top: 1rem; padding-top: 1rem; border-top: 2px solid #d32f2f;">
                    <span class="detail-label">ยอดรวมทั้งสิ้น:</span>
                    <span class="detail-value">฿ 8,329</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include("footer.php");?>

    <script>
        let currentUserId = null;
        let currentOrderId = null;

        // API Configuration
        const API_BASE_URL = './';
        const API_ENDPOINTS = {
            ORDER: API_BASE_URL + 'controllers/order_api.php'
        };

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            initializePage();
        });

        function initializePage() {
            // Check if user is logged in
            currentUserId = getCookie('user_id');
            if (!currentUserId) {
                window.location.href = 'login.php';
                return;
            }

            // Get order ID from URL parameter
            const urlParams = new URLSearchParams(window.location.search);
            currentOrderId = urlParams.get('order_id');
            
            if (!currentOrderId) {
                showError('ไม่พบหมายเลขคำสั่งซื้อ');
                return;
            }

            // Load order data
            loadOrderDetails();
        }

        async function loadOrderDetails() {
            try {
                showLoading();

                const response = await fetch(`${API_ENDPOINTS.ORDER}?action=get_order_details&order_id=${currentOrderId}&user_id=${currentUserId}`, {
                    method: "GET"
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                console.log("Order details response:", data);

                if (data.success && data.data) {
                    displayOrderDetails(data.data);
                } else {
                    showError(data.message || 'ไม่สามารถโหลดข้อมูลคำสั่งซื้อได้');
                }
            } catch (error) {
                console.error("Error loading order details:", error);
                showError('เกิดข้อผิดพลาดในการโหลดข้อมูลคำสั่งซื้อ');
            } finally {
                hideLoading();
            }
        }

        function displayOrderDetails(order) {
            // Update order details
            updateOrderDetails(order);
            
            // Update status timeline
            updateStatusTimeline(order.status.status_code);
            
            // Update product list
            updateProductList(order.order_items);
            
            // Update page title
            document.title = `รายละเอียดคำสั่งซื้อ ${order.order_id}`;
        }

        function updateOrderDetails(order) {
            const orderDate = new Date(order.created_at).toLocaleDateString('th-TH', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

            const statusText = getStatusText(order.status.status_code);

            // Update order details section
            const detailsContainer = document.querySelector('.order-details');
            if (detailsContainer) {
                detailsContainer.innerHTML = `
                    <div class="detail-row">
                        <span class="detail-label">หมายเลขคำสั่งซื้อ:</span>
                        <span class="detail-value">${order.order_id}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">วันที่สั่งซื้อ:</span>
                        <span class="detail-value">${orderDate}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">ชื่อผู้สั่ง:</span>
                        <span class="detail-value">${order.customer_info.name || '-'}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">สถานะ:</span>
                        <span class="detail-value">${statusText}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">เบอร์โทรศัพท์:</span>
                        <span class="detail-value">${order.customer_info.phone || '-'}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">ยอดรวมทั้งหมด:</span>
                        <span class="detail-value">${formatCurrency(order.total_amount)}</span>
                    </div>
                `;
            }
        }

        function updateStatusTimeline(statusCode) {
            const timelineItems = document.querySelectorAll('.timeline-item');
            
            // Reset all timeline items
            timelineItems.forEach(item => {
                const icon = item.querySelector('.timeline-icon');
                icon.classList.remove('active');
            });

            // Set active status based on order status - updated to match new status mapping
            const statusMap = {
                'pending_payment': 0, // รอตรวจสอบการการชำระเงิน
                'awaiting_shipment': 1, // รอจัดส่ง  
                'in_transit': 2, // กำลังจัดส่ง
                'delivered': 3, // จัดส่งแล้ว
                'cancelled': -1 // ยกเลิก
            };

            const activeIndex = statusMap[statusCode];
            if (activeIndex >= 0 && timelineItems[activeIndex]) {
                timelineItems[activeIndex].querySelector('.timeline-icon').classList.add('active');
            }
        }

        function updateProductList(orderItems) {
            const productList = document.querySelector('.product-list');
            if (!productList) return;

            if (!orderItems || orderItems.length === 0) {
                productList.innerHTML = '<div class="no-data">ไม่พบรายการสินค้า</div>';
                return;
            }

            let html = '';
            let totalAmount = 0;

            orderItems.forEach(item => {
                const lineTotal = item.quantity * item.price_each;
                totalAmount += lineTotal;

                const productImage = item.product_image ? 
                    `<img src="${item.product_image}" alt="${item.product_name || 'สินค้า'}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                     <div class="product-image-placeholder" style="display: none;">🖼️</div>` :
                    `<div class="product-image-placeholder">🖼️</div>`;

                html += `
                    <div class="product-item">
                        <div class="product-image">
                            ${productImage}
                        </div>
                        <div class="product-info">
                            <div class="product-name">${item.product_name || 'ไม่ระบุชื่อสินค้า'}</div>
                            <div class="product-code">รหัสสินค้า: ${item.product_id}</div>
                            <div class="product-price">${formatCurrency(item.price_each)}</div>
                        </div>
                        <div class="product-quantity">จำนวน: ${item.quantity}</div>
                    </div>
                `;
            });

            // Add total row
            html += `
                <div class="detail-row" style="margin-top: 1rem; padding-top: 1rem; border-top: 2px solid #d32f2f;">
                    <span class="detail-label">ยอดรวมทั้งสิ้น:</span>
                    <span class="detail-value">${formatCurrency(totalAmount)}</span>
                </div>
            `;

            productList.innerHTML = html;
        }

        function getStatusText(statusCode) {
            // Updated status mapping to match new requirements
            const statusMap = {
                'pending_payment': 'รอตรวจสอบการการชำระเงิน',
                'awaiting_shipment': 'รอจัดส่ง',
                'in_transit': 'กำลังจัดส่ง',
                'delivered': 'จัดส่งแล้ว',
                'cancelled': 'ยกเลิก'
            };
            return statusMap[statusCode] || statusCode;
        }

        function formatCurrency(amount) {
            return new Intl.NumberFormat('th-TH', {
                style: 'currency',
                currency: 'THB',
                minimumFractionDigits: 2
            }).format(amount);
        }

        function getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
            return null;
        }

        function showLoading() {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) {
                overlay.classList.add('active');
            }
        }

        function hideLoading() {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) {
                overlay.classList.remove('active');
            }
        }

        function showError(message) {
            hideLoading();
            
            // Create error message element
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.textContent = message;
            
            // Insert error message after main title
            const mainTitle = document.querySelector('.main-title');
            if (mainTitle) {
                mainTitle.insertAdjacentElement('afterend', errorDiv);
            }
            
            // Hide all sections
            const sections = document.querySelectorAll('.section');
            sections.forEach(section => {
                section.style.display = 'none';
            });
        }

        // Animation เมื่อโหลดหน้า
        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('.section');
            sections.forEach((section, index) => {
                section.style.animationDelay = `${index * 0.1}s`;
            });
        });

        // เพิ่มเอฟเฟกต์ hover สำหรับ timeline items
        document.querySelectorAll('.timeline-item').forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.style.backgroundColor = '#f8f9fa';
                this.style.transform = 'translateX(10px)';
                this.style.transition = 'all 0.3s ease';
            });

            item.addEventListener('mouseleave', function() {
                this.style.backgroundColor = 'transparent';
                this.style.transform = 'translateX(0)';
            });
        });

        // เพิ่มเอฟเฟกต์ hover สำหรับ product items
        document.querySelectorAll('.product-item').forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.style.backgroundColor = '#ffffff';
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 5px 15px rgba(0,0,0,0.1)';
                this.style.transition = 'all 0.3s ease';
                this.style.borderRadius = '8px';
                this.style.margin = '0 -0.5rem';
                this.style.padding = '1rem 1.5rem';
            });

            item.addEventListener('mouseleave', function() {
                this.style.backgroundColor = 'transparent';
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
                this.style.borderRadius = '0';
                this.style.margin = '0';
                this.style.padding = '1rem 0';
            });
        });
    </script>
</body>
</html>