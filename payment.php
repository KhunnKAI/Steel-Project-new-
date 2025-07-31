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
            margin: 0 auto;
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

        .payment-methods {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }

        .payment-option {
            flex: 1;
        }

        .payment-option input[type="radio"] {
            display: none;
        }

        .payment-option label {
            display: block;
            height: 120px;
            padding: 20px;
            border: 1px solid #d0d0d0;
            border-radius: 4px;
            background: #f8f8f8;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 400;
        }

        .payment-option input[type="radio"]:checked + label {
            border-color: #333;
            background: white;
        }

        .payment-icon {
            font-size: 32px;
            margin-bottom: 12px;
            display: block;
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
                            <select id="province" name="province" required>
                                <option value="">เลือกจังหวัด</option>
                                <option value="กรุงเทพมหานคร">กรุงเทพมหานคร</option>
                                <option value="นนทบุรี">นนทบุรี</option>
                                <option value="ปทุมธานี">ปทุมธานี</option>
                                <option value="สมุทรปราการ">สมุทรปราการ</option>
                                <option value="เชียงใหม่">เชียงใหม่</option>
                                <option value="ขอนแก่น">ขอนแก่น</option>
                                <option value="ชลบุรี">ชลบุรี</option>
                            </select>
                        </div>
                        <div class="form-group small">
                            <label>รหัสไปรษณีย์ <span class="required">*จำเป็น</span></label>
                            <input type="text" id="zipcode" name="zipcode" required maxlength="5">
                        </div>
                    </div>
                </div>

                <div class="section">
                    <div class="section-title">วิธีการชำระเงิน</div>
                    
                    <div class="payment-methods">
                        <div class="payment-option">
                            <input type="radio" id="bank-transfer" name="payment" value="bank-transfer" checked>
                            <label for="bank-transfer">
                                <span class="payment-icon">🏦</span>
                                โอนเงิน
                            </label>
                        </div>
                        <div class="payment-option">
                            <input type="radio" id="credit-card" name="payment" value="credit-card">
                            <label for="credit-card">
                                <span class="payment-icon">💳</span>
                                บัตรเครดิต
                            </label>
                        </div>
                        <div class="payment-option">
                            <input type="radio" id="cash" name="payment" value="cash">
                            <label for="cash">
                                <span class="payment-icon">💵</span>
                                เงินสด
                            </label>
                        </div>
                    </div>
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