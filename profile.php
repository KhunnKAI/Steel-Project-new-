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
        position: relative;
    }

    .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .btn-edit {
        background-color: #d32f2f;
        color: white;
    }

    .btn-edit:hover:not(:disabled) {
        background-color: #b71c1c;
    }

    .btn-password {
        background-color: #ccc;
        color: #666;
    }

    .btn-password:hover:not(:disabled) {
        background-color: #bbbbbbff;
    }

    .btn-address {
        background-color: #1e3a5f;
        color: white;
    }

    .btn-address:hover:not(:disabled) {
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

    /* address section*/
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

    .btn-default:hover:not(:disabled) {
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

    .btn-edit-address:hover:not(:disabled) {
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

    .btn-delete:hover:not(:disabled) {
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

    .add-address-btn:hover:not(:disabled) {
        background: #2c4e73;
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
        position: relative;
    }

    .btn-primary:hover:not(:disabled) {
        background: #2c4e73;
    }

    .btn-primary:disabled {
        opacity: 0.6;
        cursor: not-allowed;
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

    .btn-secondary:hover:not(:disabled) {
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

    .btn-confirm:hover:not(:disabled) {
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

    .btn-cancel:hover:not(:disabled) {
        background-color: #5a6268;
    }

    .no-addresses {
        text-align: center;
        padding: 40px;
        color: #666;
        font-style: italic;
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
    </style>
</head>

<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Header -->
    <?php include("header.php");?>

    <!-- Main Content -->
    <div class="container">
        <!-- Success Message -->
        <div id="successMessage" class="success-message">
            ✓ แก้ไขข้อมูลเรียบร้อยแล้ว
        </div>

        <!-- Error Message -->
        <div id="errorMessage" class="error-message">
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
                        <div class="profile-value" id="displayName">กำลังโหลด...</div>
                    </div>
                    <div class="profile-item">
                        <div class="profile-label">เบอร์โทรศัพท์</div>
                        <div class="profile-value" id="displayPhone">กำลังโหลด...</div>
                    </div>
                    <div class="profile-item">
                        <div class="profile-label">อีเมล</div>
                        <div class="profile-value" id="displayEmail">กำลังโหลด...</div>
                    </div>
                </div>
                <div class="profile-right">
                    <button class="btn btn-edit" id="editBtn" onclick="openEditModal()">แก้ไขข้อมูล</button>
                    <br><br>
                    <button class="btn btn-password" id="passwordBtn"
                        onclick="openPasswordModal()">เปลี่ยนรหัสผ่าน</button>
                </div>
            </div>

            <!-- Address Section -->
            <div class="section">
                <div class="address-section">
                    <div class="address-header">
                        <div class="address-title">ที่อยู่จัดส่ง</div>
                        <button type="button" class="add-address-btn" id="addAddressBtn">+ เพิ่มที่อยู่</button>
                    </div>

                    <div id="addressContainer">
                        <div class="no-addresses">กำลังโหลดที่อยู่...</div>
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

    <!-- Add Address Modal -->
    <div class="modal-overlay" id="addressModal">
        <div class="modal">
            <div class="modal-header">
                <h2 class="modal-title" id="addressModalTitle">เพิ่มที่อยู่ใหม่</h2>
                <button type="button" class="close-btn" onclick="closeAddressModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addressForm">
                    <input type="hidden" id="addressId" name="addressId">

                    <div class="form-row">
                        <div class="form-group">
                            <label>ชื่อผู้รับ <span class="required">*จำเป็น</span></label>
                            <input type="text" id="recipientName" name="recipientName" required>
                        </div>
                        <div class="form-group">
                            <label>เบอร์โทรศัพท์ <span class="required">*จำเป็น</span></label>
                            <input type="tel" id="recipientPhone" name="recipientPhone" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group full-width">
                            <label>ที่อยู่ <span class="required">*จำเป็น</span></label>
                            <textarea id="addressLine" name="addressLine" required
                                placeholder="บ้านเลขที่ ซอย ถนน"></textarea>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>ตำบล/แขวง <span class="required">*จำเป็น</span></label>
                            <input type="text" id="subdistrict" name="subdistrict" placeholder="ตำบลหรือแขวง" required>
                        </div>
                        <div class="form-group">
                            <label>อำเภอ/เขต <span class="required">*จำเป็น</span></label>
                            <input type="text" id="district" name="district" placeholder="อำเภอหรือเขต" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                             <label for="province">จังหวัด <span class="required">*จำเป็น</span></label>
                            <select id="province" name="province" required>
                                <option value="">เลือกจังหวัด</option>
                            </select>
                        </div>
                        <div class="form-group small">
                            <label>รหัสไปรษณีย์ <span class="required">*จำเป็น</span></label>
                            <input type="text" id="postalCode" name="postalCode" required maxlength="5">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeAddressModal()">ยกเลิก</button>
                <button type="button" class="btn-primary" id="saveAddressBtn"
                    onclick="saveAddress()">บันทึกที่อยู่</button>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div id="passwordModal" class="old-modal">
        <div class="old-modal-content">
            <div class="old-modal-header">
                <span class="old-modal-title">เปลี่ยนรหัสผ่าน</span>
                <span class="close" onclick="closePasswordModal()">&times;</span>
            </div>
            <div class="old-modal-body">
                <form id="passwordForm">
                    <div class="form-group">
                        <label class="form-label" for="currentPassword">รหัสผ่านเดิม <span
                                style="color: red;">*</span></label>
                        <div class="password-field">
                            <input type="password" class="form-input" id="currentPassword"
                                placeholder="กรุณากรอกรหัสผ่านเดิม" required>
                            <span class="show-password" onclick="togglePassword('currentPassword')">แสดง</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="newPassword">รหัสผ่านใหม่ <span
                                style="color: red;">*</span></label>
                        <div class="password-field">
                            <input type="password" class="form-input" id="newPassword"
                                placeholder="กรุณากรอกรหัสผ่านใหม่" required>
                            <span class="show-password" onclick="togglePassword('newPassword')">แสดง</span>
                        </div>
                        <div id="passwordStrength" class="password-strength"></div>
                        <small style="color: #666; font-size: 12px;">
                            รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร ประกอบด้วยตัวพิมพ์เล็ก พิมพ์ใหญ่ และตัวเลข
                        </small>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="confirmPassword">ยืนยันรหัสผ่านใหม่ <span
                                style="color: red;">*</span></label>
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
                <button type="button" class="btn-confirm" id="confirmPasswordBtn"
                    onclick="confirmPasswordChange()">ยืนยันข้อมูล</button>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
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
                        <input type="text" class="form-input" id="editName" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="editPhone">เบอร์โทรศัพท์</label>
                        <input type="tel" class="form-input" id="editPhone" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="editEmail">อีเมล</label>
                        <input type="email" class="form-input" id="editEmail" required>
                    </div>
                </form>
            </div>
            <div class="old-modal-footer">
                <button type="button" class="btn-cancel" onclick="closeEditModal()">ยกเลิก</button>
                <button type="button" class="btn-confirm" id="confirmEditBtn"
                    onclick="confirmEdit()">ยืนยันข้อมูล</button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include("footer.php");?>

    
</body>

<script src="profile.js"></script>

</html>