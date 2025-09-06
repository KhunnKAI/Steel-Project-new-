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
    
    <div class="container">
        <div class="main-title">
            สถานะใบเสร็จ
        </div>

        <div class="section">
            <div class="section-title">สถานะสินค้า</div>
            
            <div class="status-timeline">
                <div class="timeline-item">
                    <div class="timeline-icon active">✓</div>
                    <div class="timeline-content">
                        <div class="timeline-title">คุณได้สร้างรายการสั่งซื้อแล้ว</div>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-icon">⏳</div>
                    <div class="timeline-content">
                        <div class="timeline-title">รอการชำระเงิน</div>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-icon">📦</div>
                    <div class="timeline-content">
                        <div class="timeline-title">ร้านยืนยันยอดเงินแล้ว</div>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-icon">🚚</div>
                    <div class="timeline-content">
                        <div class="timeline-title">รอจัดส่ง</div>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-icon">✅</div>
                    <div class="timeline-content">
                        <div class="timeline-title">เสร็จเรียบร้อย</div>
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