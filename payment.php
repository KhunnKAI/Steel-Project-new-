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
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f5f5f5;
    }

    .main-content {
        max-width: 800px;
        margin: 30px auto;
        padding: 0 20px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        text-align: left;
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
        text-align: left;
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

    .address-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .address-title {
        font-weight: bold;
        color: #333;
        font-size: 18px;
    }

    .address-item {
        border: 2px solid #e0e0e0;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 15px;
        background: #fafafa;
        transition: all 0.3s ease;
    }

    .address-item:hover {
        border-color: #d32f2f;
        background: #fff;
        box-shadow: 0 4px 12px rgba(211, 47, 47, 0.1);
    }

    .address-item.selected {
        border-color: #d32f2f;
        background: #fff5f5;
        box-shadow: 0 4px 12px rgba(211, 47, 47, 0.1);
    }

    .address-content {
        display: flex;
        flex-direction: column;
    }

    .address-details {
        color: #555;
        line-height: 1.8;
        font-size: 14px;
        margin-bottom: 15px;
    }

    .address-name {
        font-weight: bold;
        margin-bottom: 5px;
        color: #333;
    }

    .address-info {
        color: #666;
    }

    .address-actions {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
    }

    .btn-default {
        background: #28a745;
        color: white;
        padding: 8px 16px;
        border: none;
        border-radius: 20px;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.3s;
    }

    .btn-default:hover {
        background: #218838;
        transform: translateY(-1px);
    }

    .btn-edit-address {
        background: #ffc107;
        color: #333;
        padding: 8px 16px;
        border: none;
        border-radius: 20px;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.3s;
    }

    .btn-edit-address:hover {
        background: #e0a800;
        transform: translateY(-1px);
    }

    .btn-delete {
        background: #dc3545;
        color: white;
        padding: 8px 16px;
        border: none;
        border-radius: 20px;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.3s;
    }

    .btn-delete:hover {
        background: #c82333;
        transform: translateY(-1px);
    }

    .add-address-btn {
        background: #1e3a5f;
        color: white;
        border: none;
        padding: 10px 15px;
        border-radius: 5px;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s;
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

    .success-message {
        display: none;
        background-color: #d4edda;
        color: #155724;
        padding: 15px;
        border: 1px solid #c3e6cb;
        border-radius: 5px;
        margin-bottom: 20px;
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

        .address-content {
            flex-direction: column;
            gap: 10px;
        }

        .address-details {
            margin-right: 0;
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
            <!-- Success Message -->
            <div id="successMessage" class="success-message">
                ✓ เพิ่มที่อยู่เรียบร้อยแล้ว
            </div>

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

                <!-- Address Section -->
                <div class="section">
                    <div class="address-section">
                        <div class="address-header">
                            <div class="address-title">ที่อยู่จัดส่ง</div>
                            <button type="button" class="add-address-btn" id="addAddressBtn">+ เพิ่มที่อยู่</button>
                        </div>

                        <div class="address-item selected" data-id="1">
                            <div class="address-content">
                                <div class="address-details">
                                    <div class="address-name">บางลาง ปริญญา วันบาร</div>
                                    <div class="address-info">
                                        011-111-1111<br>
                                        155/88 หมู่ 5 ซอย สีกาน 29 ถนน สีกาน<br>
                                        แขวง คืนนักติ เขต คืนติดก กรุงเทพมหานคร 88888
                                    </div>
                                </div>
                                <div class="address-actions">
                                    <button class="btn-default" onclick="setDefaultAddress(this)">เลือก</button>
                                    <button class="btn-edit-address" onclick="editAddress(this)">แก้ไข</button>
                                    <button class="btn-delete" onclick="deleteAddress(this)">ลบ</button>
                                </div>
                            </div>
                        </div>

                        <div class="address-item" data-id="2">
                            <div class="address-content">
                                <div class="address-details">
                                    <div class="address-name">บายนท์ บานา</div>
                                    <div class="address-info">
                                        022-222-2222<br>
                                        245/4 หมู่ 8 ซอย ซองสราร์ 3 ถนน สันท์<br>
                                        แขวง สสัน เขต คำอลีซอง กรุงเทพมหานคร 33333
                                    </div>
                                </div>
                                <div class="address-actions">
                                    <button class="btn-default" onclick="setDefaultAddress(this)">เลือก</button>
                                    <button class="btn-edit-address" onclick="editAddress(this)">แก้ไข</button>
                                    <button class="btn-delete" onclick="deleteAddress(this)">ลบ</button>
                                </div>
                            </div>
                        </div>

                        <div class="address-item" data-id="3">
                            <div class="address-content">
                                <div class="address-details">
                                    <div class="address-name">บายปฏา กีน</div>
                                    <div class="address-info">
                                        033-333-3333<br>
                                        1 หมู่ 7 ซอย บานา 34 ถนน บานา<br>
                                        แขวง ยักลา เขต ยักกำ กรุงเทพมหานคร 55555
                                    </div>
                                </div>
                                <div class="address-actions">
                                    <button class="btn-default" onclick="setDefaultAddress(this)">เลือก</button>
                                    <button class="btn-edit-address" onclick="editAddress(this)">แก้ไข</button>
                                    <button class="btn-delete" onclick="deleteAddress(this)">ลบ</button>
                                </div>
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
                        <div class="form-group">
                            <label>เบอร์โทรศัพท์ <span class="required">*จำเป็น</span></label>
                            <input type="tel" id="newPhone" name="newPhone" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group full-width">
                            <label>ที่อยู่ <span class="required">*จำเป็น</span></label>
                            <textarea id="newAddress" name="newAddress" required
                                placeholder="บ้านเลขที่ ซอย ถนน"></textarea>
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
        item.addEventListener('click', function(e) {
            // ป้องกันการเลือกที่อยู่เมื่อคลิกปุ่ม
            if (e.target.classList.contains('btn-default') ||
                e.target.classList.contains('btn-edit-address') ||
                e.target.classList.contains('btn-delete')) {
                return;
            }

            document.querySelectorAll('.address-item').forEach(addr => {
                addr.classList.remove('selected');
            });
            this.classList.add('selected');
        });
    });

    // Address action functions
    function setDefaultAddress(button) {
        const addressItem = button.closest('.address-item');

        // ลบการเลือกจากที่อยู่อื่น ๆ
        document.querySelectorAll('.address-item').forEach(item => {
            item.classList.remove('selected');
        });

        // เลือกที่อยู่นี้
        addressItem.classList.add('selected');

        // แสดงข้อความสำเร็จ
        showSuccessMessage('ตั้งเป็นที่อยู่หลักเรียบร้อยแล้ว');
    }

    function editAddress(button) {
        const addressItem = button.closest('.address-item');
        const addressName = addressItem.querySelector('.address-name').textContent;
        const addressInfo = addressItem.querySelector('.address-info').innerHTML;

        // เปิด modal และ populate ข้อมูล
        openAddressModal();

        // ในการใช้งานจริง คุณสามารถ populate ข้อมูลใน form ได้
        console.log('แก้ไขที่อยู่:', addressName, addressInfo);

        // เปลี่ยนชื่อปุ่มในกรณีแก้ไข
        document.getElementById('saveAddressBtn').textContent = 'อัปเดตที่อยู่';
        document.querySelector('.modal-title').textContent = 'แก้ไขที่อยู่';
    }

    function deleteAddress(button) {
        const addressItem = button.closest('.address-item');
        const addressName = addressItem.querySelector('.address-name').textContent;

        if (confirm(`คุณต้องการลบที่อยู่ของ ${addressName} หรือไม่?`)) {
            // ตรวจสอบว่ามีที่อยู่เหลืออย่างน้อย 1 ที่อยู่
            const allAddresses = document.querySelectorAll('.address-item');
            if (allAddresses.length <= 1) {
                alert('ต้องมีที่อยู่อย่างน้อย 1 ที่อยู่');
                return;
            }

            // หากเป็นที่อยู่ที่เลือกอยู่ ให้เลือกที่อยู่แรก
            if (addressItem.classList.contains('selected')) {
                const firstAddress = document.querySelector('.address-item:not([data-removing])');
                if (firstAddress && firstAddress !== addressItem) {
                    firstAddress.classList.add('selected');
                }
            }

            addressItem.remove();
            showSuccessMessage('ลบที่อยู่เรียบร้อยแล้ว');
        }
    }

    function showSuccessMessage(message) {
        const successMessage = document.getElementById('successMessage');
        successMessage.textContent = message;
        successMessage.style.display = 'block';

        // เลื่อนไปที่ข้อความสำเร็จ
        successMessage.scrollIntoView({
            behavior: 'smooth'
        });

        // ซ่อนข้อความสำเร็จหลังจาก 3 วินาที
        setTimeout(() => {
            successMessage.style.display = 'none';
        }, 3000);
    }

    // Modal functionality
    const modal = document.getElementById('addressModal');
    const addAddressBtn = document.getElementById('addAddressBtn');
    const closeModal = document.getElementById('closeModal');
    const cancelBtn = document.getElementById('cancelBtn');
    const saveAddressBtn = document.getElementById('saveAddressBtn');

    // Open modal
    function openAddressModal() {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';

        // รีเซ็ตชื่อปุ่มและหัวข้อ
        document.getElementById('saveAddressBtn').textContent = 'บันทึกที่อยู่';
        document.querySelector('.modal-title').textContent = 'เพิ่มที่อยู่ใหม่';
    }

    addAddressBtn.addEventListener('click', function() {
        openAddressModal();
        document.getElementById('successMessage').style.display = 'none';
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
        const requiredFields = ['newFirstName', 'newLastName', 'newPhone', 'newAddress', 'newDistrict',
            'newCity', 'newProvince', 'newZipcode'
        ];
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
        const fullAddress =
            `${formData.get('newPhone')}<br>${formData.get('newAddress')}<br>แขวง ${formData.get('newDistrict')} เขต ${formData.get('newCity')} ${formData.get('newProvince')} ${formData.get('newZipcode')}`;

        newAddressItem.innerHTML = `
                <div class="address-content">
                    <div class="address-details">
                        <div class="address-name">${fullName}</div>
                        <div class="address-info">
                            ${fullAddress}
                        </div>
                    </div>
                    <div class="address-actions">
                        <button class="btn-default" onclick="setDefaultAddress(this)">ตั้งเป็นหลัก</button>
                        <button class="btn-edit-address" onclick="editAddress(this)">แก้ไข</button>
                        <button class="btn-delete" onclick="deleteAddress(this)">ลบ</button>
                    </div>
                </div>
            `;

        // Add click event to new address item
        newAddressItem.addEventListener('click', function(e) {
            // ป้องกันการเลือกที่อยู่เมื่อคลิกปุ่ม
            if (e.target.classList.contains('btn-default') ||
                e.target.classList.contains('btn-edit-address') ||
                e.target.classList.contains('btn-delete')) {
                return;
            }

            document.querySelectorAll('.address-item').forEach(addr => {
                addr.classList.remove('selected');
            });
            this.classList.add('selected');
        });

        addressSection.appendChild(newAddressItem);
        addressCounter++;

        // Close modal and show success message
        closeAddressModal();
        showSuccessMessage('เพิ่มที่อยู่เรียบร้อยแล้ว');
    });

     // ฟังก์ชันสำหรับการเลือกที่อยู่เป็นค่าเริ่มต้น
    function setDefaultAddress(button) {
        // ลบสถานะ selected จากที่อยู่ทั้งหมด
        const allAddressItems = document.querySelectorAll('.address-item');
        allAddressItems.forEach(item => {
            item.classList.remove('selected');
            // เปลี่ยนปุ่มทั้งหมดกลับเป็น "เลือก"
            const selectBtn = item.querySelector('.btn-default');
            if (selectBtn) {
                selectBtn.textContent = 'เลือก';
                selectBtn.style.background = '#28a745';
            }
        });

        // เพิ่มสถานะ selected ให้กับที่อยู่ที่เลือก
        const selectedAddressItem = button.closest('.address-item');
        selectedAddressItem.classList.add('selected');

        // เปลี่ยนปุ่มเป็น "เลือกแล้ว"
        button.textContent = 'เลือกแล้ว';
        button.style.background = '#6c757d'; // สีเทา

        // แสดงข้อความยืนยัน
        showSuccessMessage('✓ เลือกที่อยู่เรียบร้อยแล้ว');
    }

    // ฟังก์ชันสำหรับแก้ไขที่อยู่
    function editAddress(button) {
        const addressItem = button.closest('.address-item');
        const addressDetails = addressItem.querySelector('.address-details');

        // ดึงข้อมูลปัจจุบัน
        const currentName = addressDetails.querySelector('.address-name').textContent;
        const currentInfo = addressDetails.querySelector('.address-info').innerHTML;

        // เปิด Modal สำหรับแก้ไข (คุณสามารถปรับแต่งได้ตามต้องการ)
        alert(`แก้ไขที่อยู่: ${currentName}\n${currentInfo.replace(/<br>/g, '\n')}`);
    }

    // ฟังก์ชันสำหรับลบที่อยู่
    function deleteAddress(button) {
        const addressItem = button.closest('.address-item');
        const addressName = addressItem.querySelector('.address-name').textContent;

        if (confirm(`คุณต้องการลบที่อยู่ของ ${addressName} หรือไม่?`)) {
            // ตรวจสอบว่าเป็นที่อยู่ที่เลือกอยู่หรือไม่
            const isSelected = addressItem.classList.contains('selected');

            // ลบที่อยู่
            addressItem.remove();

            // ถ้าที่อยู่ที่ลบเป็นที่อยู่ที่เลือกอยู่ ให้เลือกที่อยู่แรกเป็นค่าเริ่มต้น
            if (isSelected) {
                const remainingAddresses = document.querySelectorAll('.address-item');
                if (remainingAddresses.length > 0) {
                    const firstAddress = remainingAddresses[0];
                    firstAddress.classList.add('selected');
                    const firstSelectBtn = firstAddress.querySelector('.btn-default');
                    if (firstSelectBtn) {
                        firstSelectBtn.textContent = 'เลือกแล้ว';
                        firstSelectBtn.style.background = '#6c757d';
                    }
                }
            }

            showSuccessMessage('✓ ลบที่อยู่เรียบร้อยแล้ว');
        }
    }

    // ฟังก์ชันแสดงข้อความสำเร็จ
    function showSuccessMessage(message) {
        const successMessage = document.getElementById('successMessage');
        successMessage.style.display = 'block';
        successMessage.textContent = message;

        // เลื่อนไปที่ข้อความสำเร็จ
        successMessage.scrollIntoView({
            behavior: 'smooth'
        });

        // ซ่อนข้อความสำเร็จหลังจาก 3 วินาที
        setTimeout(() => {
            successMessage.style.display = 'none';
        }, 3000);
    }

    // เมื่อโหลดหน้าเสร็จ ให้กำหนดที่อยู่แรกเป็นค่าเริ่มต้น
    document.addEventListener('DOMContentLoaded', function() {
        // ตรวจสอบว่ามีที่อยู่ที่มีคลาส selected อยู่แล้วหรือไม่
        const selectedAddress = document.querySelector('.address-item.selected');
        if (selectedAddress) {
            const selectBtn = selectedAddress.querySelector('.btn-default');
            if (selectBtn && selectBtn.textContent === 'เลือก') {
                selectBtn.textContent = 'เลือกแล้ว';
                selectBtn.style.background = '#6c757d';
            }
        }

        // เพิ่ม Event Listener สำหรับปุ่มเพิ่มที่อยู่
        const addAddressBtn = document.getElementById('addAddressBtn');
        if (addAddressBtn) {
            addAddressBtn.addEventListener('click', openAddressModal);
        }
    });

    // ปรับปรุงฟังก์ชัน confirmAddAddress เพื่อเพิ่มที่อยู่ใหม่
    function confirmAddAddress() {
        const form = document.getElementById('addressForm');
        if (!form) return;

        const newAddress = document.getElementById('newAddress').value.trim();
        const newDistrict = document.getElementById('newDistrict').value.trim();
        const newCity = document.getElementById('newCity').value.trim();
        const newProvince = document.getElementById('newProvince').value.trim();
        const newZipcode = document.getElementById('newZipcode').value.trim();

        // ตรวจสอบข้อมูลที่จำเป็น
        if (!newAddress || !newDistrict || !newCity || !newProvince || !newZipcode) {
            alert('กรุณากรอกข้อมูลให้ครบถ้วน');
            return;
        }

        // สร้างที่อยู่ใหม่
        const addressSection = document.querySelector('.address-section');
        const addressContainer = addressSection.querySelector('.address-item').parentNode;

        // สร้าง ID ใหม่
        const existingAddresses = document.querySelectorAll('.address-item');
        const newId = existingAddresses.length + 1;

        // สร้าง HTML สำหรับที่อยู่ใหม่
        const newAddressHTML = `
        <div class="address-item" data-id="${newId}">
            <div class="address-content">
                <div class="address-details">
                    <div class="address-name">ที่อยู่ใหม่</div>
                    <div class="address-info">
                        ${newAddress}<br>
                        แขวง ${newDistrict} เขต ${newCity} ${newProvince} ${newZipcode}
                    </div>
                </div>
                <div class="address-actions">
                    <button class="btn-default" onclick="setDefaultAddress(this)">เลือก</button>
                    <button class="btn-edit-address" onclick="editAddress(this)">แก้ไข</button>
                    <button class="btn-delete" onclick="deleteAddress(this)">ลบ</button>
                </div>
            </div>
        </div>
    `;

        // เพิ่มที่อยู่ใหม่
        addressContainer.insertAdjacentHTML('beforeend', newAddressHTML);

        // แสดงข้อความสำเร็จ
        showSuccessMessage('✓ เพิ่มที่อยู่เรียบร้อยแล้ว');

        // ปิด Modal
        closeAddressModal();
    }

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