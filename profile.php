<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรไฟล์ผู้ใช้</title>
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

    /* Main Content */
    .container {
        max-width: 800px;
        margin: 30px auto;
        padding: 0 20px;
    }

    /* Profile Section */
    .profile-section {
        background-color: white;
        border-radius: 10px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .profile-header {
        display: flex;
        align-items: center;
        margin-bottom: 30px;
    }

    .profile-avatar {
        width: 80px;
        height: 80px;
        background-color: #ccc;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 30px;
    }

    .profile-avatar::before {
        content: "👤";
        font-size: 40px;
        color: #666;
    }

    .profile-info h2 {
        color: #d32f2f;
        margin-bottom: 10px;
        font-size: 24px;
    }

    .profile-details {
        display: flex;
        gap: 100px;
    }

    .profile-left,
    .profile-right {
        flex: 1;
    }

    .profile-item {
        margin-bottom: 15px;
        line-height: 1.6;
    }

    .profile-label {
        font-weight: bold;
        color: #333;
        margin-bottom: 5px;
    }

    .profile-value {
        color: #666;
    }

    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        font-size: 14px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        text-align: center;
        transition: background-color 0.3s;
    }

    .btn-edit {
        background-color: #d32f2f;
        color: white;
    }

    .btn-edit:hover {
        background-color: #b71c1c;
    }

    .btn-password {
        background-color: #ccc;
        color: #666;
    }

    .btn-password:hover {
        background-color: #bbbbbbff;
    }

    .btn-address {
        background-color: #1e3a5f;
        color: white;
    }

    .btn-address:hover {
        background-color: #2c4e73;
    }

    /* Order History Section */
    .order-section {
        background-color: white;
        border-radius: 10px;
        padding: 30px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .order-title {
        font-size: 24px;
        margin-bottom: 20px;
        color: #333;
    }

    .order-item {
        border: 2px solid #ddd;
        border-radius: 8px;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .order-details {
        flex: 1;
    }

    .order-id {
        font-weight: bold;
        margin-bottom: 10px;
        color: #333;
    }

    .order-date {
        color: #d32f2f;
        font-size: 14px;
        margin-bottom: 10px;
    }

    .order-amount {
        color: #666;
        margin-bottom: 5px;
    }

    .order-total {
        font-weight: bold;
        color: #333;
    }

    .order-actions {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .btn-view {
        background-color: #ccc;
        color: #666;
        padding: 8px 16px;
        font-size: 12px;
    }

    .address-section {
        margin-top: 30px;
        padding-top: 30px;
        border-top: 1px solid #eee;
    }

    .address-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .address-title {
        font-weight: bold;
        color: #333;
    }

    .address-detail {
        color: #666;
        line-height: 1.6;
    }

    /* Modal Styles - Updated to match payment page */
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

    /* Form Styles - Updated to match payment page */
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

    .form-group.full-width {
        width: 100%;
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
    input[type="password"],
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

    .btn-primary {
        background: #1e3a5f;
        color: white;
        padding: 12px 24px;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-primary:hover {
        background: #2c4e73;
    }

    .btn-secondary {
        background: #f8f9fa;
        color: #666;
        border: 1px solid #dee2e6;
        padding: 12px 24px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-secondary:hover {
        background: #e9ecef;
        color: #333;
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

    .password-strength {
        font-size: 12px;
        margin-top: 5px;
    }

    .strength-weak {
        color: #dc3545;
    }

    .strength-medium {
        color: #ffc107;
    }

    .strength-strong {
        color: #28a745;
    }

    .show-password {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #666;
        user-select: none;
        background: #f8f9fa;
        border: 1px solid #ddd;
        border-radius: 3px;
        padding: 4px 8px;
        font-size: 12px;
        transition: all 0.3s;
    }

    .show-password:hover {
        background: #e9ecef;
        border-color: #ccc;
    }

    .password-field {
        position: relative;
    }

    /* Old modal styles for edit profile and password modals */
    .old-modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.4);
        backdrop-filter: blur(5px);
    }

    .old-modal-content {
        background-color: #fefefe;
        margin: 10% auto;
        padding: 0;
        border-radius: 10px;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        animation: modalShow 0.3s ease-out;
    }

    @keyframes modalShow {
        from {
            opacity: 0;
            transform: translateY(-50px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .old-modal-header {
        background-color: #d32f2f;
        color: white;
        padding: 20px 30px;
        border-radius: 10px 10px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .old-modal-title {
        font-size: 20px;
        font-weight: bold;
    }

    .close {
        color: white;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        line-height: 1;
    }

    .close:hover {
        opacity: 0.7;
    }

    .old-modal-body {
        padding: 30px;
    }

    .form-label {
        display: block;
        font-weight: bold;
        color: #333;
        margin-bottom: 8px;
    }

    .form-input {
        width: 100%;
        padding: 12px;
        border: 2px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
        transition: border-color 0.3s;
    }

    .form-input:focus {
        outline: none;
        border-color: #d32f2f;
        box-shadow: 0 0 0 3px rgba(211, 47, 47, 0.1);
    }

    .old-modal-footer {
        padding: 20px 30px;
        border-top: 1px solid #eee;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .btn-confirm {
        background-color: #d32f2f;
        color: white;
        padding: 10px 25px;
        border: none;
        border-radius: 5px;
        font-size: 14px;
        cursor: pointer;
        transition: background-color 0.3s;
    }

    .btn-confirm:hover {
        background-color: #b71c1c;
    }

    .btn-cancel {
        background-color: #6c757d;
        color: white;
        padding: 10px 25px;
        border: none;
        border-radius: 5px;
        font-size: 14px;
        cursor: pointer;
        transition: background-color 0.3s;
    }

    .btn-cancel:hover {
        background-color: #5a6268;
    }

    @media (max-width: 768px) {
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

    .address-section {
        margin-top: 30px;
        padding-top: 30px;
        border-top: 1px solid #eee;
    // ตรวจสอบรหัสผ่านแบบ Real-time

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

    .address-content {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    .address-detail {
        color: #555;
        line-height: 1.8;
        font-size: 14px;
        flex: 1;
        margin-right: 20px;
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
    
    </style>
</head>

<body>
    <!-- Header -->
    <?php include("header.php");?>

    <!-- Main Content -->
    <div class="container">
        <!-- Success Message -->
        <div id="successMessage" class="success-message">
            ✓ แก้ไขข้อมูลเรียบร้อยแล้ว
        </div>

        <!-- Profile Section -->
        <div class="profile-section">
            <div class="profile-header">
                <div class="profile-avatar"></div>
                <div class="profile-info">
                    <h2>ข้อมูลลูกค้า</h2>
                </div>
            </div>

            <div class="profile-details">
                <div class="profile-left">
                    <div class="profile-item">
                        <div class="profile-label">ชื่อ-สกุล</div>
                        <div class="profile-value" id="displayName">เดช วีระเทพ</div>
                    </div>
                    <div class="profile-item">
                        <div class="profile-label">เบอร์โทรศัพท์</div>
                        <div class="profile-value" id="displayPhone">0910000000</div>
                    </div>
                    <div class="profile-item">
                        <div class="profile-label">อีเมล</div>
                        <div class="profile-value" id="displayEmail">abc123@gmail.com</div>
                    </div>
                </div>
                <div class="profile-right">
                    <a href="#" class="btn btn-edit" onclick="openEditModal()">แก้ไขข้อมูล</a>
                    <br><br>
                    <a href="#" class="btn btn-password" onclick="openPasswordModal()">เปลี่ยนรหัสผ่าน</a>
                </div>
            </div>

            <!-- Address Section -->
            <div class="address-section">
                <div class="address-header">
                    <div class="address-title">ที่อยู่</div>
                    <a href="#" class="btn btn-address" onclick="openAddressModal()">+เพิ่มที่อยู่</a>
                </div>
                <div class="address-item">
                    <div class="address-detail">
                        ตำบลเดก อำเภอเดก จังหวัดเดก 62000<br>
                        โทร: 0999999999
                    </div>
                    <div class="address-actions">
                        <button class="btn btn-default" onclick="setDefaultAddress()">ตั้งเป็นหลัก</button>
                        <button class="btn btn-edit-address" onclick="editAddress()">แก้ไข</button>
                        <button class="btn btn-delete" onclick="deleteAddress()">ลบ</button>
                    </div>
                </div>

                <div class="address-item">
                    <div class="address-detail">
                        ตำบลเดก อำเภอเดก จังหวัดเดก 62000<br>
                        โทร: 0999999999
                    </div>
                    <div class="address-actions">
                        <button class="btn btn-default" onclick="setDefaultAddress()">ตั้งเป็นหลัก</button>
                        <button class="btn btn-edit-address" onclick="editAddress()">แก้ไข</button>
                        <button class="btn btn-delete" onclick="deleteAddress()">ลบ</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order History Section -->
        <div class="order-section">
            <h2 class="order-title">ประวัติคำสั่งซื้อ</h2>

            <div class="order-item">
                <div class="order-details">
                    <div class="order-id">เลขคำสั่งซื้อ steel123456</div>
                    <div class="order-date">วันที่สั่งซื้อ 7/9/2025<br>สถานะ : จัดส่งแล้ว</div>
                    <div class="order-amount">จำนวนที่สั่งซื้อ 10</div>
                    <div class="order-total">ยอดรวม 1000.00</div>
                </div>
                <div class="order-actions">
                    <a href="#" class="btn btn-view">ดูรายละเอียด</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Address Modal - Updated to match payment page style -->
    <div class="modal-overlay" id="addressModal">
        <div class="modal">
            <div class="modal-header">
                <h2 class="modal-title">เพิ่มที่อยู่ใหม่</h2>
                <button type="button" class="close-btn" onclick="closeAddressModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addressForm">
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
                <button type="button" class="btn-secondary" onclick="closeAddressModal()">ยกเลิก</button>
                <button type="button" class="btn-primary" onclick="confirmAddAddress()">บันทึกที่อยู่</button>
            </div>
        </div>
    </div>

    <!-- Change Password Modal - Keep original style -->
    <div id="passwordModal" class="old-modal">
        <div class="old-modal-content">
            <div class="old-modal-header">
                <span class="old-modal-title">เปลี่ยนรหัสผ่าน</span>
                <span class="close" onclick="closePasswordModal()">&times;</span>
            </div>
            <div class="old-modal-body">
                <form id="passwordForm">
                    <div class="form-group">
                        <label class="form-label" for="newPassword">รหัสผ่านใหม่</label>
                        <div class="password-field">
                            <input type="password" class="form-input" id="newPassword"
                                placeholder="กรุณากรอกรหัสผ่านใหม่" required>
                            <span class="show-password" onclick="togglePassword('newPassword')">แสดง</span>
                        </div>
                        <div id="passwordStrength" class="password-strength"></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="confirmPassword">ยืนยันรหัสผ่านใหม่</label>
                        <div class="password-field">
                            <input type="password" class="form-input" id="confirmPassword"
                                placeholder="กรุณายืนยันรหัสผ่านใหม่" required>
                            <span class="show-password" onclick="togglePassword('confirmPassword')">แสดง</span>
                        </div>
                        <div id="passwordMatch" class="password-strength"></div>
                    </div>
                </form>
            </div>
            <div class="old-modal-footer">
                <button type="button" class="btn-cancel" onclick="closePasswordModal()">ยกเลิก</button>
                <button type="button" class="btn-confirm" onclick="confirmPasswordChange()">ยืนยันข้อมูล</button>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal - Keep original style -->
    <div id="editModal" class="old-modal">
        <div class="old-modal-content">
            <div class="old-modal-header">
                <span class="old-modal-title">แก้ไขข้อมูลส่วนตัว</span>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <div class="old-modal-body">
                <form id="editForm">
                    <div class="form-group">
                        <label class="form-label" for="editName">ชื่อ-นามสกุล</label>
                        <input type="text" class="form-input" id="editName" value="เดช วีระเทพ" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="editPhone">เบอร์โทรศัพท์</label>
                        <input type="tel" class="form-input" id="editPhone" value="0910000000" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="editEmail">อีเมล</label>
                        <input type="email" class="form-input" id="editEmail" value="abc123@gmail.com" required>
                    </div>
                </form>
            </div>
            <div class="old-modal-footer">
                <button type="button" class="btn-cancel" onclick="closeEditModal()">ยกเลิก</button>
                <button type="button" class="btn-confirm" onclick="confirmEdit()">ยืนยันข้อมูล</button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include("footer.php");?>

    <script>
    // เปิด Modal
    function openEditModal() {
        const modal = document.getElementById('editModal');
        modal.style.display = 'block';

        // ซ่อนข้อความสำเร็จ
        document.getElementById('successMessage').style.display = 'none';
    }

    // ปิด Modal
    function closeEditModal() {
        const modal = document.getElementById('editModal');
        modal.style.display = 'none';
    }

    // เปิด Modal เปลี่ยนรหัสผ่าน
    function openPasswordModal() {
        const modal = document.getElementById('passwordModal');
        modal.style.display = 'block';

        // ซ่อนข้อความสำเร็จ และ รีเซ็ตฟอร์ม
        document.getElementById('successMessage').style.display = 'none';
        document.getElementById('passwordForm').reset();
        document.getElementById('passwordStrength').textContent = '';
        document.getElementById('passwordMatch').textContent = '';
    }

    // ปิด Modal เปลี่ยนรหัสผ่าน
    function closePasswordModal() {
        const modal = document.getElementById('passwordModal');
        modal.style.display = 'none';
    }

    // เปิด Modal เพิ่มที่อยู่ - Updated to use new modal style
    function openAddressModal() {
        const modal = document.getElementById('addressModal');
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';

        // ซ่อนข้อความสำเร็จ และ รีเซ็ตฟอร์ม
        document.getElementById('successMessage').style.display = 'none';
        document.getElementById('addressForm').reset();
    }

    // ปิด Modal เพิ่มที่อยู่ - Updated to use new modal style
    function closeAddressModal() {
        const modal = document.getElementById('addressModal');
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';
        document.getElementById('addressForm').reset();
    }

    // แสดง/ซ่อนรหัสผ่าน
    function togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        const button = field.nextElementSibling;

        if (field.getAttribute('type') === 'password') {
            field.setAttribute('type', 'text');
            button.textContent = 'ซ่อน';
        } else {
            field.setAttribute('type', 'password');
            button.textContent = 'แสดง';
        }
    }

    // ตรวจสอบความแข็งแกร่งของรหัสผ่าน
    function checkPasswordStrength(password) {
        let strength = 0;
        let text = '';
        let className = '';

        if (password.length >= 8) strength++;
        if (password.match(/[a-z]/)) strength++;
        if (password.match(/[A-Z]/)) strength++;
        if (password.match(/[0-9]/)) strength++;
        if (password.match(/[^a-zA-Z0-9]/)) strength++;

        switch (strength) {
            case 0:
            case 1:
            case 2:
                text = 'รหัสผ่านอ่อน';
                className = 'strength-weak';
                break;
            case 3:
                text = 'รหัสผ่านปานกลาง';
                className = 'strength-medium';
                break;
            case 4:
            case 5:
                text = 'รหัสผ่านแข็งแกร่ง';
                className = 'strength-strong';
                break;
        }

        return {
            text,
            className,
            strength
        };
    }

    // ยืนยันการเปลี่ยนรหัสผ่าน
    function confirmPasswordChange() {
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        // ตรวจสอบข้อมูล
        if (!newPassword.trim() || !confirmPassword.trim()) {
            alert('กรุณากรอกรหัสผ่านให้ครบถ้วน');
            return;
        }

        // ตรวจสอบความยาวรหัสผ่าน
        if (newPassword.length < 8) {
            alert('รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร');
            return;
        }

        // ตรวจสอบการยืนยันรหัสผ่าน
        if (newPassword !== confirmPassword) {
            alert('รหัสผ่านไม่ตรงกัน กรุณาตรวจสอบอีกครั้ง');
            return;
        }

        // แสดงข้อความสำเร็จ
        const successMessage = document.getElementById('successMessage');
        successMessage.style.display = 'block';
        successMessage.textContent = '✓ เปลี่ยนรหัสผ่านเรียบร้อยแล้ว';

        // เลื่อนไปที่ข้อความสำเร็จ
        successMessage.scrollIntoView({
            behavior: 'smooth'
        });

        // ปิด Modal
        closePasswordModal();

        // ซ่อนข้อความสำเร็จหลังจาก 3 วินาที
        setTimeout(() => {
            successMessage.style.display = 'none';
        }, 3000);
    }

    // ยืนยันการเพิ่มที่อยู่ - Updated function
    function confirmAddAddress() {
        const form = document.getElementById('addressForm');
        const formData = new FormData(form);

        // Check required fields
        const requiredFields = ['newFirstName', 'newLastName', 'newPhone', 'newAddress', 'newDistrict', 'newCity',
            'newProvince', 'newZipcode'
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

        const fullName = `${formData.get('newFirstName')} ${formData.get('newLastName')}`;
        const fullAddress =
            `${formData.get('newAddress')}<br>แขวง ${formData.get('newDistrict')} เขต ${formData.get('newCity')} ${formData.get('newProvince')} ${formData.get('newZipcode')}`;

        // อัปเดตข้อมูลที่อยู่ในหน้า
        const addressDisplay = document.querySelector('.address-detail');
        addressDisplay.innerHTML = `
                ${formData.get('newAddress')}<br>
                แขวง${formData.get('newDistrict')} เขต${formData.get('newCity')}<br>
                จังหวัด${formData.get('newProvince')} ${formData.get('newZipcode')}
            `;

        // แสดงข้อความสำเร็จ
        const successMessage = document.getElementById('successMessage');
        successMessage.style.display = 'block';
        successMessage.textContent = '✓ เพิ่มที่อยู่เรียบร้อยแล้ว';

        // เลื่อนไปที่ข้อความสำเร็จ
        successMessage.scrollIntoView({
            behavior: 'smooth'
        });

        // ปิด Modal
        closeAddressModal();

        // ซ่อนข้อความสำเร็จหลังจาก 3 วินาที
        setTimeout(() => {
            successMessage.style.display = 'none';
        }, 3000);
    }

    // ยืนยันการแก้ไข
    function confirmEdit() {
        const name = document.getElementById('editName').value;
        const phone = document.getElementById('editPhone').value;
        const email = document.getElementById('editEmail').value;

        // ตรวจสอบข้อมูล
        if (!name.trim() || !phone.trim() || !email.trim()) {
            alert('กรุณากรอกข้อมูลให้ครบถ้วน');
            return;
        }

        // ตรวจสอบรูปแบบอีเมล
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            alert('กรุณากรอกอีเมลในรูปแบบที่ถูกต้อง');
            return;
        }

        // ตรวจสอบเบอร์โทรศัพท์
        const phoneRegex = /^[0-9]{10}$/;
        if (!phoneRegex.test(phone.replace(/-/g, ''))) {
            alert('กรุณากรอกเบอร์โทรศัพท์ที่ถูกต้อง (10 หลัก)');
            return;
        }

        // อัปเดตข้อมูลในหน้า
        document.getElementById('displayName').textContent = name;
        document.getElementById('displayPhone').textContent = phone;
        document.getElementById('displayEmail').textContent = email;

        // แสดงข้อความสำเร็จ
        const successMessage = document.getElementById('successMessage');
        successMessage.style.display = 'block';

        // เลื่อนไปที่ข้อความสำเร็จ
        successMessage.scrollIntoView({
            behavior: 'smooth'
        });

        // ปิด Modal
        closeEditModal();

        // ซ่อนข้อความสำเร็จหลังจาก 3 วินาที
        setTimeout(() => {
            successMessage.style.display = 'none';
        }, 3000);
    }

    // ปิด Modal เมื่อคลิกนอก Modal
    window.onclick = function(event) {
        const editModal = document.getElementById('editModal');
        const passwordModal = document.getElementById('passwordModal');
        const addressModal = document.getElementById('addressModal');

        if (event.target === editModal) {
            closeEditModal();
        }
        if (event.target === passwordModal) {
            closePasswordModal();
        }
        // Updated for new address modal
        if (event.target === addressModal) {
            closeAddressModal();
        }
    }

    // ปิด Modal เมื่อกด ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeEditModal();
            closePasswordModal();

            // Updated for new address modal
            if (document.getElementById('addressModal').classList.contains('active')) {
                closeAddressModal();
            }
        }
    });

    // ตรวจสอบรหัสผ่านแบบ Real-time
    document.addEventListener('DOMContentLoaded', function() {
        const newPasswordField = document.getElementById('newPassword');
        const confirmPasswordField = document.getElementById('confirmPassword');

        if (newPasswordField) {
            newPasswordField.addEventListener('input', function() {
                const password = this.value;
                const strengthResult = checkPasswordStrength(password);
                const strengthDiv = document.getElementById('passwordStrength');

                if (password) {
                    strengthDiv.textContent = strengthResult.text;
                    strengthDiv.className = 'password-strength ' + strengthResult.className;
                } else {
                    strengthDiv.textContent = '';
                    strengthDiv.className = 'password-strength';
                }
            });
        }

        if (confirmPasswordField) {
            confirmPasswordField.addEventListener('input', function() {
                const password = newPasswordField.value;
                const confirmPassword = this.value;
                const matchDiv = document.getElementById('passwordMatch');

                if (confirmPassword) {
                    if (password === confirmPassword) {
                        matchDiv.textContent = '✓ รหัสผ่านตรงกัน';
                        matchDiv.className = 'password-strength strength-strong';
                    } else {
                        matchDiv.textContent = '✗ รหัสผ่านไม่ตรงกัน';
                        matchDiv.className = 'password-strength strength-weak';
                    }
                } else {
                    matchDiv.textContent = '';
                    matchDiv.className = 'password-strength';
                }
            });
        }

        // Form validation for new address modal
        const zipcodeField = document.getElementById('newZipcode');
        if (zipcodeField) {
            zipcodeField.addEventListener('input', function(e) {
                // ลบตัวอักษรที่ไม่ใช่ตัวเลข
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        }

        // Form validation for phone number
        const newPhoneField = document.getElementById('newPhone');
        if (newPhoneField) {
            newPhoneField.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 10) {
                    value = value.substring(0, 10);
                }
                e.target.value = value;
            });
        }
    });
    </script>
</body>

</html>