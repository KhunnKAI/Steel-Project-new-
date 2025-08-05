<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ชำระเงิน</title>
    <link href="header.css" rel="stylesheet">
    <link href="footer.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }

        .main-content {
            max-width: 800px;
            margin: 30px auto;
            padding: 0 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .payment-section {
            background: white;
            padding: 30px;
            border-bottom: 1px solid #e0e0e0;
        }

        .payment-title {
            font-size: 24px;
            margin-bottom: 30;
            color: #333;
        }

        .content {
            padding: 40px;
        }

        .section {
            margin-bottom: 40px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 500;
            color: #333;
            margin-bottom: 20px;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            flex: 1;
        }

        .form-group.small {
            flex: 0 0 150px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #666;
            font-weight: 400;
        }

        .required {
            color: #d32f2f;
            font-size: 12px;
            margin-left: 4px;
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"],
        select,
        textarea {
            width: 100%;
            height: 48px;
            padding: 12px 16px;
            border: 1px solid #d0d0d0;
            border-radius: 4px;
            font-size: 14px;
            background: #f8f8f8;
            transition: all 0.2s;
        }

        textarea {
            height: 120px;
            resize: vertical;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #666;
            background: white;
        }

        .address-group {
            margin-bottom: 20px;
        }

        .address-row {
            display: flex;
            gap: 20px;
        }

        .transfer-info {
            margin-bottom: 30px;
        }

        
        .bank-detail {
            margin-bottom: 8px;
            padding: 5px 0;
        }

        .bank-detail strong {
            display: inline-block;
            width: 100px;
            color: #666;
        }

        .bank-value {
            color: #333;
            font-weight: 500;
        }

        .qr-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .qr-section h3 {
            color: #333;
            text-align: left;
            margin-bottom: 15px;
            font-weight: normal;
        }

        .qr-container {
            background: #f0f0f0;
            border: 2px dashed #ccc;
            border-radius: 10px;
            padding: 30px;
            margin: 15px 0;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .qr-container:hover {
            background: #e8e8e8;
            border-color: #999;
        }

        .qr-code-display {
            width: 150px;
            height: 150px;
            margin: 0 auto 15px;
            background: white;
            border: 2px solid #333;
            border-radius: 8px;
            display: none;
            position: relative;
            overflow: hidden;
        }

        .qr-code-display.active {
            display: block;
        }

        .qr-pattern {
            width: 100%;
            height: 100%;
            background: 
                /* Corner detection patterns */
                radial-gradient(circle at 20% 20%, #000 15%, transparent 20%),
                radial-gradient(circle at 80% 20%, #000 15%, transparent 20%),
                radial-gradient(circle at 20% 80%, #000 15%, transparent 20%),
                /* Data pattern */
                repeating-linear-gradient(45deg, #000 0px, #000 2px, transparent 2px, transparent 6px),
                repeating-linear-gradient(-45deg, #000 0px, #000 1px, transparent 1px, transparent 4px);
            background-size: 
                40px 40px,
                40px 40px,
                40px 40px,
                8px 8px,
                6px 6px;
        }

        .qr-placeholder {
            color: #999;
            font-size: 16px;
        }

        .generate-qr-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .generate-qr-btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }

        .order-summary {
            background: #f8f8f8;
            padding: 30px;
            border-radius: 4px;
            margin-bottom: 30px;
        }

        .order-summary h3 {
            font-size: 18px;
            font-weight: 500;
            color: #333;
            margin-bottom: 20px;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 16px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-info h4 {
            font-size: 14px;
            font-weight: 500;
            color: #333;
            margin-bottom: 4px;
        }

        .item-info p {
            font-size: 12px;
            color: #666;
        }

        .item-price {
            font-size: 14px;
            font-weight: 500;
            color: #333;
        }

        .total-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .total-final {
            font-weight: 500;
            font-size: 16px;
            color: #333;
            border-top: 1px solid #e0e0e0;
            padding-top: 12px;
            margin-top: 12px;
        }

        .submit-btn {
            width: 100%;
            height: 56px;
            background: #d32f2f;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .submit-btn:hover {
            background: #b71c1c;
        }

        .full-width {
            width: 100%;
        }

        @media (max-width: 768px) {
            .content {
                padding: 20px;
            }
            
            .form-row,
            .address-row {
                flex-direction: column;
                gap: 15px;
            }
            
            .payment-methods {
                flex-direction: column;
            }
            
            .payment-option label {
                height: 80px;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include("header.php");?>

    <main class="main-content">
        <section class="payment-section">
            <h1 class="payment-title">ชำระเงิน</h1>
        </section>

        <div class="content">
            <form id="paymentForm">
                <div class="section">
                    <div class="section-title">ข้อมูลลูกค้า</div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>ชื่อ <span class="required">*จำเป็น</span></label>
                            <input type="text" id="firstName" name="firstName" required>
                        </div>
                        <div class="form-group">
                            <label>นามสกุล <span class="required">*จำเป็น</span></label>
                            <input type="text" id="lastName" name="lastName" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>บริษัท/ร้านค้า</label>
                            <input type="text" id="company" name="company">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>อีเมล <span class="required">*จำเป็น</span></label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label>เบอร์โทรศัพท์ <span class="required">*จำเป็น</span></label>
                            <input type="tel" id="phone" name="phone" required>
                        </div>
                    </div>
                </div>

                <div class="section">
                    <div class="section-title">ที่อยู่จัดส่ง</div>
                    
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label>ที่อยู่ <span class="required">*จำเป็น</span></label>
                            <textarea id="address" name="address" required placeholder="บ้านเลขที่ ซอย ถนน"></textarea>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>ตำบล/แขวง <span class="required">*จำเป็น</span></label>
                            <input type="text" id="district" name="district" required>
                        </div>
                        <div class="form-group">
                            <label>อำเภอ/เขต <span class="required">*จำเป็น</span></label>
                            <input type="text" id="city" name="city" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>จังหวัด <span class="required">*จำเป็น</span></label>
                            <input type="text" id="province" name="province" required>
                        </div>
                        <div class="form-group small">
                            <label>รหัสไปรษณีย์ <span class="required">*จำเป็น</span></label>
                            <input type="text" id="zipcode" name="zipcode" required maxlength="5">
                        </div>
                    </div>
                </div>

                <div class="section">
                    <div class="section-title">วิธีการชำระเงิน</div>
                    
            <div class="bank-detail">
                <strong>ธนาคาร:</strong>
                <span class="bank-value">กรุงไทย</span>
            </div>
            <div class="bank-detail">
                <strong>ชื่อบัญชี:</strong>
                <span class="bank-value">ปวิชญา อุดมสิทธิพัฒนา</span>
            </div>
            <div class="bank-detail">
                <strong>เลขที่บัญชี:</strong>
                <span class="bank-value">111-1-11111-1</span>
            </div>
        </div>

        <div class="qr-section">
            <h3>หรือสแกนคิวอาร์โค้ด</h3>
            <img src="image/qr.jpg" width="500px">
        </div>

                <div class="order-summary">
                    <h3>สรุปคำสั่งซื้อ</h3>
                    
                    <div class="order-item">
                        <div class="item-info">
                            <h4>เหล็กเส้น DB16</h4>
                            <p>จำนวน: 50 เส้น × 100 บาท</p>
                        </div>
                        <div class="item-price">5,000 ฿</div>
                    </div>

                    <div class="order-item">
                        <div class="item-info">
                            <h4>เหล็กแผ่น 3mm</h4>
                            <p>จำนวน: 10 แผ่น × 250 บาท</p>
                        </div>
                        <div class="item-price">2,500 ฿</div>
                    </div>

                    <div class="order-item">
                        <div class="item-info">
                            <h4>ท่อเหล็ก 2 นิ้ว</h4>
                            <p>จำนวน: 20 เส้น × 150 บาท</p>
                        </div>
                        <div class="item-price">3,000 ฿</div>
                    </div>

                    <div class="total-section">
                        <div class="total-row">
                            <span>ราคาสินค้า</span>
                            <span>10,500 ฿</span>
                        </div>
                        <div class="total-row">
                            <span>ค่าจัดส่ง</span>
                            <span>500 ฿</span>
                        </div>
                        <div class="total-row">
                            <span>ภาษี (7%)</span>
                            <span>770 ฿</span>
                        </div>
                        <div class="total-row total-final">
                            <span>รวมทั้งสิ้น</span>
                            <span>11,770 ฿</span>
                        </div>
                    </div>
                </div>

                <button type="submit" class="submit-btn" onclick="processPayment()">
                    ยืนยันการสั่งซื้อ
                </button>
            </form>
        </div>
    </main>

    <!-- Footer -->
    <?php include("footer.php");?>

    <script>
        function processPayment() {
            event.preventDefault();
            
            const form = document.getElementById('paymentForm');
            const formData = new FormData(form);
            
            // ตรวจสอบว่ากรอกข้อมูลครบถ้วน
            const requiredFields = ['firstName', 'lastName', 'email', 'phone', 'address', 'district', 'city', 'province', 'zipcode'];
            let isValid = true;
            
            requiredFields.forEach(field => {
                const input = document.getElementById(field);
                if (!input.value.trim()) {
                    input.style.borderColor = '#d32f2f';
                    isValid = false;
                } else {
                    input.style.borderColor = '#d0d0d0';
                }
            });
            
            if (!isValid) {
                alert('กรุณากรอกข้อมูลให้ครบถ้วน');
                return;
            }
            
            // จำลองการประมวลผลการชำระเงิน
            const paymentMethod = document.querySelector('input[name="payment"]:checked').value;
            const paymentText = {
                'bank-transfer': 'โอนเงิน',
                'credit-card': 'บัตรเครดิต',
                'cash': 'เงินสด'
            };
            
            alert(`ขอบคุณสำหรับการสั่งซื้อ!\n\nข้อมูลการสั่งซื้อ:\nลูกค้า: ${formData.get('firstName')} ${formData.get('lastName')}\nยอดรวม: 11,770 บาท\nวิธีชำระเงิน: ${paymentText[paymentMethod]}\n\nเราจะติดต่อกลับภายใน 24 ชั่วโมง`);
        }

        // เพิ่มการตรวจสอบรหัสไปรษณีย์
        document.getElementById('zipcode').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 5) {
                value = value.substring(0, 5);
            }
            e.target.value = value;
        });

        // เพิ่มการตรวจสอบเบอร์โทรศัพท์
        document.getElementById('phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 10) {
                value = value.substring(0, 10);
            }
            e.target.value = value;
        });
    </script>
</body>
</html>