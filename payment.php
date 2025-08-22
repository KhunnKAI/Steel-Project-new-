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
            margin-bottom: 30px;
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

        .address-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .address-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #333;
        }

        .address-item {
            background: white;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            border: 2px solid #e9ecef;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .address-item.selected {
            border-color: #c41e3a;
            background-color: #fff5f5;
        }

        .address-item:hover {
            border-color: #c41e3a;
        }

        .address-name {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .address-details {
            font-size: 13px;
            color: #666;
            line-height: 1.4;
        }

        .add-address-btn {
            background: #1e3a5f;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            font-size: 12px;
            cursor: pointer;
            float: right;
            margin-top: -40px;
        }

        .add-address-btn:hover {
            background: #2c4e73;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-header {
            padding: 20px 25px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
            padding: 5px;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .close-btn:hover {
            background: #f0f0f0;
            color: #333;
        }

        .modal-body {
            padding: 25px;
        }

        .modal-footer {
            padding: 15px 25px;
            border-top: 1px solid #e0e0e0;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #1e3a5f;
            color: white;
        }

        .btn-primary:hover {
            background: #2c4e73;
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #666;
            border: 1px solid #dee2e6;
        }

        .btn-secondary:hover {
            background: #e9ecef;
            color: #333;
        }

        .full-width {
            width: 100%;
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

        @media (max-width: 768px) {
            .content {
                padding: 20px;
            }
            
            .form-row {
                flex-direction: column;
                gap: 15px;
            }
            
            .modal {
                width: 95%;
                margin: 10px;
            }
            
            .modal-body {
                padding: 20px;
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
                    <button type="button" class="add-address-btn" id="addAddressBtn">+ เพิ่มที่อยู่</button>
                    
                    <div class="address-section">
                        <div class="address-item selected" data-id="1">
                            <div class="address-name">บางลาง ปริญญา วันบาร</div>
                            <div class="address-details">
                                011-111-1111<br>
                                155/88 หมู่ 5 ซอย สีกาน 29 ถนน สีกาน<br>
                                แขวง คืนนักติ เขต คืนติดก กรุงเทพมหานคร 88888
                            </div>
                        </div>

                        <div class="address-item" data-id="2">
                            <div class="address-name">บายนท์ บานา</div>
                            <div class="address-details">
                                022-222-2222<br>
                                245/4 หมู่ 8 ซอย ซองสราร์ 3 ถนน สันท์<br>
                                แขวง สสัน เขต คำอลีซอง กรุงเทพมหานคร 33333
                            </div>
                        </div>

                        <div class="address-item" data-id="3">
                            <div class="address-name">บายปฏา กีน</div>
                            <div class="address-details">
                                033-333-3333<br>
                                1 หมู่ 7 ซอย บานา 34 ถนน บานา<br>
                                แขวง ยักลา เขต ยักกำ กรุงเทพมหานคร 55555
                            </div>
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

                <button type="submit" class="submit-btn">
                    ยืนยันการสั่งซื้อ
                </button>
            </form>
        </div>
    </main>

    <!-- Add Address Modal -->
    <div class="modal-overlay" id="addressModal">
        <div class="modal">
            <div class="modal-header">
                <h2 class="modal-title">เพิ่มที่อยู่ใหม่</h2>
                <button type="button" class="close-btn" id="closeModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addressForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label>ชื่อ <span class="required">*จำเป็น</span></label>
                            <input type="text" id="newFirstName" name="newFirstName" required>
                        </div>
                        <div class="form-group">
                            <label>นามสกุล <span class="required">*จำเป็น</span></label>
                            <input type="text" id="newLastName" name="newLastName" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group full-width">
                            <label>เบอร์โทรศัพท์ <span class="required">*จำเป็น</span></label>
                            <input type="tel" id="newPhone" name="newPhone" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group full-width">
                            <label>ที่อยู่ <span class="required">*จำเป็น</span></label>
                            <textarea id="newAddress" name="newAddress" required placeholder="บ้านเลขที่ ซอย ถนน"></textarea>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>ตำบล/แขวง <span class="required">*จำเป็น</span></label>
                            <input type="text" id="newDistrict" name="newDistrict" required>
                        </div>
                        <div class="form-group">
                            <label>อำเภอ/เขต <span class="required">*จำเป็น</span></label>
                            <input type="text" id="newCity" name="newCity" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>จังหวัด <span class="required">*จำเป็น</span></label>
                            <input type="text" id="newProvince" name="newProvince" required>
                        </div>
                        <div class="form-group small">
                            <label>รหัสไปรษณีย์ <span class="required">*จำเป็น</span></label>
                            <input type="text" id="newZipcode" name="newZipcode" required maxlength="5">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancelBtn">ยกเลิก</button>
                <button type="button" class="btn btn-primary" id="saveAddressBtn">บันทึกที่อยู่</button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include("footer.php");?>

    <script>
        let addressCounter = 4; // เริ่มนับจาก 4 เพราะมี 3 ที่อยู่เดิมแล้ว

        // Address selection functionality
        document.querySelectorAll('.address-item').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.address-item').forEach(addr => {
                    addr.classList.remove('selected');
                });
                this.classList.add('selected');
            });
        });

        // Modal functionality
        const modal = document.getElementById('addressModal');
        const addAddressBtn = document.getElementById('addAddressBtn');
        const closeModal = document.getElementById('closeModal');
        const cancelBtn = document.getElementById('cancelBtn');
        const saveAddressBtn = document.getElementById('saveAddressBtn');

        // Open modal
        addAddressBtn.addEventListener('click', function() {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        });

        // Close modal functions
        function closeAddressModal() {
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
            document.getElementById('addressForm').reset();
        }

        closeModal.addEventListener('click', closeAddressModal);
        cancelBtn.addEventListener('click', closeAddressModal);

        // Close modal when clicking outside
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeAddressModal();
            }
        });

        // Save new address
        saveAddressBtn.addEventListener('click', function() {
            const form = document.getElementById('addressForm');
            const formData = new FormData(form);
            
            // Check required fields
            const requiredFields = ['newFirstName', 'newLastName', 'newPhone', 'newAddress', 'newDistrict', 'newCity', 'newProvince', 'newZipcode'];
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

            // Create new address item
            const addressSection = document.querySelector('.address-section');
            const newAddressItem = document.createElement('div');
            newAddressItem.className = 'address-item';
            newAddressItem.setAttribute('data-id', addressCounter);
            
            const fullName = `${formData.get('newFirstName')} ${formData.get('newLastName')}`;
            const fullAddress = `${formData.get('newAddress')}<br>แขวง ${formData.get('newDistrict')} เขต ${formData.get('newCity')} ${formData.get('newProvince')} ${formData.get('newZipcode')}`;
            
            newAddressItem.innerHTML = `
                <div class="address-name">${fullName}</div>
                <div class="address-details">
                    ${formData.get('newPhone')}<br>
                    ${fullAddress}
                </div>
            `;
            
            // Add click event to new address item
            newAddressItem.addEventListener('click', function() {
                document.querySelectorAll('.address-item').forEach(addr => {
                    addr.classList.remove('selected');
                });
                this.classList.add('selected');
            });
            
            addressSection.appendChild(newAddressItem);
            addressCounter++;
            
            // Close modal and show success message
            closeAddressModal();
            alert('เพิ่มที่อยู่เรียบร้อยแล้ว');
        });

        // Form validation for postal code
        document.getElementById('newZipcode').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 5) {
                value = value.substring(0, 5);
            }
            e.target.value = value;
        });

        // Form validation for phone number
        document.getElementById('newPhone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 10) {
                value = value.substring(0, 10);
            }
            e.target.value = value;
        });

        // Main phone number validation
        document.getElementById('phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 10) {
                value = value.substring(0, 10);
            }
            e.target.value = value;
        });

        // Form submission
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const requiredFields = ['firstName', 'lastName', 'email', 'phone'];
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
            
            const selectedAddress = document.querySelector('.address-item.selected');
            if (!selectedAddress) {
                alert('กรุณาเลือกที่อยู่จัดส่ง');
                return;
            }
            
            alert('ขอบคุณสำหรับการสั่งซื้อ!\n\nเราจะติดต่อกลับภายใน 24 ชั่วโมง');
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.classList.contains('active')) {
                closeAddressModal();
            }
        });
    </script>
</body>
</html>