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
                            <input type="text" id="subdistrict" name="subdistrict" required>
                        </div>
                        <div class="form-group">
                            <label>อำเภอ/เขต <span class="required">*จำเป็น</span></label>
                            <input type="text" id="district" name="district" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>จังหวัด <span class="required">*จำเป็น</span></label>
                            <input type="text" id="province" name="province" required>
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

    <script>
    // Global variables
    let currentUserId = null;
    let currentCustomerData = null;
    let isEditingAddress = false;

    // API Configuration
    const API_BASE_URL = '';
    const API_ENDPOINTS = {
        CUSTOMER: 'controllers/customer_api.php',
        ADDRESS: 'controllers/address_api.php'
    };

    // Initialize page
    document.addEventListener('DOMContentLoaded', function() {
        initializePage();
        setupEventListeners();
    });

    function initializePage() {
        // Check if user is logged in
        currentUserId = getCookie('user_id');
        if (!currentUserId) {
            window.location.href = 'login.html';
            return;
        }

        // Load user data
        loadCustomerData();
        loadAddressData();
    }

    function setupEventListeners() {
        // Address modal
        const addAddressBtn = document.getElementById('addAddressBtn');
        if (addAddressBtn) {
            addAddressBtn.addEventListener('click', openAddressModal);
        }

        // Password strength checking
        const currentPasswordField = document.getElementById('currentPassword');
        const newPasswordField = document.getElementById('newPassword');
        const confirmPasswordField = document.getElementById('confirmPassword');

        // Current password validation
        if (currentPasswordField) {
            currentPasswordField.addEventListener('input', function() {
                const matchDiv = document.getElementById('passwordMatch');
                if (matchDiv.textContent) {
                    // Reset password match display when current password changes
                    matchDiv.textContent = '';
                    matchDiv.className = 'password-strength';
                }
            });
        }

        // New password strength checking
        if (newPasswordField) {
            newPasswordField.addEventListener('input', function() {
                const password = this.value;
                const strengthResult = checkPasswordStrength(password);
                const strengthDiv = document.getElementById('passwordStrength');

                if (password) {
                    strengthDiv.textContent = strengthResult.text;
                    strengthDiv.className = 'password-strength ' + strengthResult.className;

                    // Also check match if confirm password has value
                    const confirmPassword = confirmPasswordField.value;
                    if (confirmPassword) {
                        checkPasswordMatch(password, confirmPassword);
                    }
                } else {
                    strengthDiv.textContent = '';
                    strengthDiv.className = 'password-strength';
                    // Clear match display if new password is empty
                    const matchDiv = document.getElementById('passwordMatch');
                    matchDiv.textContent = '';
                    matchDiv.className = 'password-strength';
                }
            });
        }

        // Confirm password matching
        if (confirmPasswordField) {
            confirmPasswordField.addEventListener('input', function() {
                const password = newPasswordField.value;
                const confirmPassword = this.value;
                checkPasswordMatch(password, confirmPassword);
            });
        }

        // Form validation
        const postalCodeField = document.getElementById('postalCode');
        if (postalCodeField) {
            postalCodeField.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        }

        const phoneFields = ['recipientPhone', 'editPhone'];
        phoneFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 10) {
                        value = value.substring(0, 10);
                    }
                    e.target.value = value;
                });
            }
        });
    }

    // เพิ่มฟังก์ชันช่วยเหลือสำหรับตรวจสอบความตรงกันของรหัสผ่าน
    function checkPasswordMatch(password, confirmPassword) {
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
    }

    // อัปเดตฟังก์ชัน openPasswordModal()
    function openPasswordModal() {
        document.getElementById('passwordModal').style.display = 'block';
        document.getElementById('passwordForm').reset();

        // Clear all password feedback
        document.getElementById('passwordStrength').textContent = '';
        document.getElementById('passwordMatch').textContent = '';
        document.getElementById('passwordStrength').className = 'password-strength';
        document.getElementById('passwordMatch').className = 'password-strength';

        hideMessages();

        // Focus on current password field
        setTimeout(() => {
            document.getElementById('currentPassword').focus();
        }, 100);
    }

    // API Helper Functions
    function showLoading() {
        document.getElementById('loadingOverlay').classList.add('active');
    }

    function hideLoading() {
        document.getElementById('loadingOverlay').classList.remove('active');
    }

    function showError(message) {
        const errorDiv = document.getElementById('errorMessage');
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
        errorDiv.scrollIntoView({
            behavior: 'smooth'
        });

        setTimeout(() => {
            errorDiv.style.display = 'none';
        }, 5000);
    }

    function showSuccess(message) {
        const successDiv = document.getElementById('successMessage');
        successDiv.textContent = message;
        successDiv.style.display = 'block';
        successDiv.scrollIntoView({
            behavior: 'smooth'
        });

        setTimeout(() => {
            successDiv.style.display = 'none';
        }, 3000);
    }

    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    }

    // Customer Data Functions
    async function loadCustomerData() {
        try {
            showLoading();

            const formData = new FormData();
            formData.append('action', 'get');
            formData.append('customer_id', currentUserId);

            const response = await fetch(`${API_ENDPOINTS.CUSTOMER}?action=get&customer_id=${currentUserId}`, {
                method: 'GET'
            });

            const result = await response.json();

            if (result.success && result.data) {
                currentCustomerData = result.data;
                displayCustomerData(result.data);
            } else {
                showError(result.message || 'ไม่สามารถโหลดข้อมูลลูกค้าได้');
            }
        } catch (error) {
            console.error('Error loading customer data:', error);
            showError('เกิดข้อผิดพลาดในการโหลดข้อมูล');
        } finally {
            hideLoading();
        }
    }

    function displayCustomerData(data) {
        document.getElementById('displayName').textContent = data.name || '-';
        document.getElementById('displayPhone').textContent = data.phone || '-';
        document.getElementById('displayEmail').textContent = data.email || '-';

        // Enable buttons
        document.getElementById('editBtn').disabled = false;
        document.getElementById('passwordBtn').disabled = false;
    }

    async function updateCustomerData(name, phone, email) {
        try {
            showLoading();

            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('customer_id', currentUserId);
            formData.append('name', name);
            formData.append('phone', phone);
            formData.append('email', email);

            const response = await fetch(API_ENDPOINTS.CUSTOMER, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                // Update display
                document.getElementById('displayName').textContent = name;
                document.getElementById('displayPhone').textContent = phone;
                document.getElementById('displayEmail').textContent = email;

                showSuccess('✓ แก้ไขข้อมูลเรียบร้อยแล้ว');
                return true;
            } else {
                showError(result.message || 'ไม่สามารถแก้ไขข้อมูลได้');
                return false;
            }
        } catch (error) {
            console.error('Error updating customer data:', error);
            showError('เกิดข้อผิดพลาดในการแก้ไขข้อมูล');
            return false;
        } finally {
            hideLoading();
        }
    }

    // Address Data Functions
    async function loadAddressData() {
        try {
            const formData = new FormData();
            formData.append('action', 'get_by_user');
            formData.append('user_id', currentUserId);

            const response = await fetch(`${API_ENDPOINTS.ADDRESS}?action=get_by_user&user_id=${currentUserId}`, {
                method: 'GET'
            });

            const result = await response.json();

            if (result.success) {
                displayAddresses(result.data || []);
            } else {
                console.error('Error loading addresses:', result.message);
                displayAddresses([]);
            }
        } catch (error) {
            console.error('Error loading address data:', error);
            displayAddresses([]);
        }
    }

    function displayAddresses(addresses) {
        const container = document.getElementById('addressContainer');

        if (!addresses || addresses.length === 0) {
            container.innerHTML = '<div class="no-addresses">ยังไม่มีที่อยู่จัดส่ง</div>';
            return;
        }

        let html = '';
        addresses.forEach((address, index) => {
            const isSelected = index === 0; // First address is default
            html += `
                <div class="address-item ${isSelected ? 'selected' : ''}" data-id="${address.address_id}">
                    <div class="address-content">
                        <div class="address-details">
                            <div class="address-name">${address.recipient_name}</div>
                            <div class="address-info">
                                ${address.phone}<br>
                                ${address.address_line}<br>
                                แขวง${address.subdistrict} เขต${address.district} ${address.province} ${address.postal_code}
                            </div>
                        </div>
                        <div class="address-actions">
                            <button class="btn-default" onclick="setDefaultAddress(this)" ${isSelected ? 'style="background: #6c757d;" disabled' : ''}>
                                ${isSelected ? 'เลือกแล้ว' : 'เลือก'}
                            </button>
                            <button class="btn-edit-address" onclick="editAddress('${address.address_id}')">แก้ไข</button>
                            <button class="btn-delete" onclick="deleteAddress('${address.address_id}')">ลบ</button>
                        </div>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
    }

    async function saveAddress() {
        const form = document.getElementById('addressForm');
        const addressId = document.getElementById('addressId').value;
        const isEditing = !!addressId;

        // Validate form
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        try {
            showLoading();

            const formData = new FormData();
            formData.append('action', isEditing ? 'update' : 'create');
            formData.append('user_id', currentUserId);
            formData.append('recipient_name', document.getElementById('recipientName').value);
            formData.append('phone', document.getElementById('recipientPhone').value);
            formData.append('address_line', document.getElementById('addressLine').value);
            formData.append('subdistrict', document.getElementById('subdistrict').value);
            formData.append('district', document.getElementById('district').value);
            formData.append('province', document.getElementById('province').value);
            formData.append('postal_code', document.getElementById('postalCode').value);

            if (isEditing) {
                formData.append('address_id', addressId);
            }

            const response = await fetch(API_ENDPOINTS.ADDRESS, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showSuccess(isEditing ? '✓ แก้ไขที่อยู่เรียบร้อยแล้ว' : '✓ เพิ่มที่อยู่เรียบร้อยแล้ว');
                closeAddressModal();
                loadAddressData(); // Reload addresses
            } else {
                showError(result.message || 'ไม่สามารถบันทึกที่อยู่ได้');
            }
        } catch (error) {
            console.error('Error saving address:', error);
            showError('เกิดข้อผิดพลาดในการบันทึกที่อยู่');
        } finally {
            hideLoading();
        }
    }

    async function deleteAddress(addressId) {
        if (!confirm('คุณต้องการลบที่อยู่นี้หรือไม่?')) {
            return;
        }

        try {
            showLoading();

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('address_id', addressId);
            formData.append('user_id', currentUserId);

            const response = await fetch(API_ENDPOINTS.ADDRESS, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showSuccess('✓ ลบที่อยู่เรียบร้อยแล้ว');
                loadAddressData(); // Reload addresses
            } else {
                showError(result.message || 'ไม่สามารถลบที่อยู่ได้');
            }
        } catch (error) {
            console.error('Error deleting address:', error);
            showError('เกิดข้อผิดพลาดในการลบที่อยู่');
        } finally {
            hideLoading();
        }
    }

    // Modal Functions
    function openEditModal() {
        if (!currentCustomerData) return;

        document.getElementById('editName').value = currentCustomerData.name || '';
        document.getElementById('editPhone').value = currentCustomerData.phone || '';
        document.getElementById('editEmail').value = currentCustomerData.email || '';

        document.getElementById('editModal').style.display = 'block';
        hideMessages();
    }

    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    function openPasswordModal() {
        document.getElementById('passwordModal').style.display = 'block';
        document.getElementById('passwordForm').reset();
        document.getElementById('passwordStrength').textContent = '';
        document.getElementById('passwordMatch').textContent = '';
        hideMessages();
    }

    function closePasswordModal() {
        document.getElementById('passwordModal').style.display = 'none';
    }

    function openAddressModal() {
        document.getElementById('addressModalTitle').textContent = 'เพิ่มที่อยู่ใหม่';
        document.getElementById('saveAddressBtn').textContent = 'บันทึกที่อยู่';
        document.getElementById('addressForm').reset();
        document.getElementById('addressId').value = '';
        document.getElementById('addressModal').classList.add('active');
        document.body.style.overflow = 'hidden';
        isEditingAddress = false;
        hideMessages();
    }

    function closeAddressModal() {
        document.getElementById('addressModal').classList.remove('active');
        document.body.style.overflow = 'auto';
        document.getElementById('addressForm').reset();
    }

    async function editAddress(addressId) {
        try {
            showLoading();

            // ใช้ GET request สำหรับดึงข้อมูลที่อยู่
            const response = await fetch(`${API_ENDPOINTS.ADDRESS}?action=get&address_id=${addressId}`, {
                method: 'GET'
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            if (result.success && result.data) {
                const address = result.data;

                // เปลี่ยนหัวข้อและปุ่มของ modal
                document.getElementById('addressModalTitle').textContent = 'แก้ไขที่อยู่';
                document.getElementById('saveAddressBtn').textContent = 'บันทึกการแก้ไข';
                
                // ใส่ข้อมูลเดิมในฟอร์ม
                document.getElementById('addressId').value = address.address_id;
                document.getElementById('recipientName').value = address.recipient_name || '';
                document.getElementById('recipientPhone').value = address.phone || '';
                document.getElementById('addressLine').value = address.address_line || '';
                document.getElementById('subdistrict').value = address.subdistrict || '';
                document.getElementById('district').value = address.district || '';
                document.getElementById('province').value = address.province || '';
                document.getElementById('postalCode').value = address.postal_code || '';

                // เปิด modal
                document.getElementById('addressModal').classList.add('active');
                document.body.style.overflow = 'hidden';
                isEditingAddress = true;
                hideMessages();

                // Focus ที่ช่องแรก
                setTimeout(() => {
                    document.getElementById('recipientName').focus();
                }, 100);

            } else {
                showError(result.message || 'ไม่สามารถโหลดข้อมูลที่อยู่ได้');
            }
        } catch (error) {
            console.error('Error loading address for edit:', error);
            showError('เกิดข้อผิดพลาดในการโหลดข้อมูลที่อยู่: ' + error.message);
        } finally {
            hideLoading();
        }
    }

    function setDefaultAddress(button) {
        // Remove selected from all addresses
        document.querySelectorAll('.address-item').forEach(item => {
            item.classList.remove('selected');
            const btn = item.querySelector('.btn-default');
            btn.textContent = 'เลือก';
            btn.style.background = '#28a745';
            btn.disabled = false;
        });

        // Set selected address
        const addressItem = button.closest('.address-item');
        addressItem.classList.add('selected');
        button.textContent = 'เลือกแล้ว';
        button.style.background = '#6c757d';
        button.disabled = true;

        showSuccess('✓ เลือกที่อยู่เรียบร้อยแล้ว');
    }

    // Form submission functions
    async function confirmEdit() {
        const name = document.getElementById('editName').value.trim();
        const phone = document.getElementById('editPhone').value.trim();
        const email = document.getElementById('editEmail').value.trim();

        // Validation
        if (!name || !phone || !email) {
            alert('กรุณากรอกข้อมูลให้ครบถ้วน');
            return;
        }

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            alert('กรุณากรอกอีเมลในรูปแบบที่ถูกต้อง');
            return;
        }

        const phoneRegex = /^[0-9]{10}$/;
        if (!phoneRegex.test(phone.replace(/-/g, ''))) {
            alert('กรุณากรอกเบอร์โทรศัพท์ที่ถูกต้อง (10 หลัก)');
            return;
        }

        const success = await updateCustomerData(name, phone, email);
        if (success) {
            closeEditModal();
        }
    }

    /**
     * Handle change password - แก้ไขการส่งข้อมูล
     */
    async function confirmPasswordChange() {
        const currentPassword = document.getElementById('currentPassword').value;
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        // Validation
        if (!currentPassword.trim()) {
            alert('กรุณากรอกรหัสผ่านเดิม');
            return;
        }

        if (!newPassword.trim() || !confirmPassword.trim()) {
            alert('กรุณากรอกรหัสผ่านใหม่ให้ครบถ้วน');
            return;
        }

        if (newPassword.length < 8) {
            alert('รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร');
            return;
        }

        if (newPassword === currentPassword) {
            alert('รหัสผ่านใหม่ต้องแตกต่างจากรหัสผ่านเดิม');
            return;
        }

        if (newPassword !== confirmPassword) {
            alert('รหัสผ่านใหม่ไม่ตรงกัน กรุณาตรวจสอบอีกครั้ง');
            return;
        }

        // Check password strength
        const strengthResult = checkPasswordStrength(newPassword);
        if (strengthResult.strength < 3) {
            if (!confirm('รหัสผ่านของคุณยังไม่แข็งแกร่งมาก ต้องการดำเนินการต่อหรือไม่?')) {
                return;
            }
        }

        try {
            showLoading();

            // แก้ไข: ส่ง customer_id ใน FormData และใช้ POST method
            const formData = new FormData();
            formData.append('customer_id', currentUserId);
            formData.append('current_password', currentPassword);
            formData.append('new_password', newPassword);

            console.log('Sending password change request:', {
                action: 'change_password',
                customer_id: currentUserId,
                has_current_password: !!currentPassword,
                has_new_password: !!newPassword
            });

            const response = await fetch(`${API_ENDPOINTS.CUSTOMER}?action=change_password&customer_id=${currentUserId}`, {
                method: 'POST',
                body: formData
            });

            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);

            // ตรวจสอบว่า response เป็น JSON หรือไม่
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const textResponse = await response.text();
                console.error('Non-JSON response:', textResponse);
                throw new Error('เซิร์ฟเวอร์ตอบกลับไม่ใช่ JSON: ' + textResponse.substring(0, 200));
            }

            const result = await response.json();
            console.log('Password change result:', result);

            if (result.success) {
                showSuccess('✓ เปลี่ยนรหัสผ่านเรียบร้อยแล้ว');
                closePasswordModal();

                // Clear form
                document.getElementById('passwordForm').reset();
                document.getElementById('passwordStrength').textContent = '';
                document.getElementById('passwordMatch').textContent = '';

                // Optional: Show logout prompt
                setTimeout(() => {
                    if (confirm(
                            'รหัสผ่านได้รับการเปลี่ยนแปลงแล้ว ต้องการออกจากระบบเพื่อเข้าสู่ระบบใหม่หรือไม่?'
                            )) {
                        // Clear cookies and redirect to login
                        document.cookie = 'user_id=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
                        window.location.href = 'login.html';
                    }
                }, 2000);

            } else {
                showError(result.message || 'ไม่สามารถเปลี่ยนรหัสผ่านได้');

                // Clear password fields if current password is wrong
                if (result.code === 'INVALID_CURRENT_PASSWORD') {
                    document.getElementById('currentPassword').value = '';
                    document.getElementById('currentPassword').focus();
                }
            }
        } catch (error) {
            console.error('Error changing password:', error);
            showError('เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน: ' + error.message);
        } finally {
            hideLoading();
        }
    }

    // Close modals when clicking outside
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
        if (event.target === addressModal) {
            closeAddressModal();
        }
    }

    // Close modals when pressing ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeEditModal();
            closePasswordModal();
            if (document.getElementById('addressModal').classList.contains('active')) {
                closeAddressModal();
            }
        }
    });

    //Hide error and success messages
    function hideMessages() {
        const successDiv = document.getElementById('successMessage');
        const errorDiv = document.getElementById('errorMessage');

        if (successDiv) successDiv.style.display = 'none';
        if (errorDiv) errorDiv.style.display = 'none';
    }

    /**
     * Check password strength
     */
    function checkPasswordStrength(password) {
        let strength = 0;
        let text = '';
        let className = '';

        if (password.length >= 8) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;

        switch (strength) {
            case 0:
            case 1:
                text = 'รหัสผ่านแข็งแกร่งน้อยมาก';
                className = 'strength-weak';
                break;
            case 2:
            case 3:
                text = 'รหัสผ่านแข็งแกร่งปานกลาง';
                className = 'strength-medium';
                break;
            case 4:
            case 5:
                text = 'รหัสผ่านแข็งแกร่ง';
                className = 'strength-strong';
                break;
        }

        return {
            strength,
            text,
            className
        };
    }

    /**
     * Toggle password visibility
     */
    function togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        const toggleBtn = field.nextElementSibling;

        if (field && toggleBtn) {
            if (field.type === 'password') {
                field.type = 'text';
                toggleBtn.textContent = 'ซ่อน';
            } else {
                field.type = 'password';
                toggleBtn.textContent = 'แสดง';
            }
        }
    }

    /**
     * Validate password strength requirements
     */
    function validatePassword(password) {
        // รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร และมีตัวพิมพ์เล็ก พิมพ์ใหญ่ และตัวเลข
        if (password.length < 8) return false;
        if (!/[a-z]/.test(password)) return false;
        if (!/[A-Z]/.test(password)) return false;
        if (!/[0-9]/.test(password)) return false;

        return true;
    }

    // แก้ไขฟังก์ชัน openPasswordModal ที่มีอยู่แล้ว
    function openPasswordModal() {
        document.getElementById('passwordModal').style.display = 'block';
        document.getElementById('passwordForm').reset();

        // Clear all password feedback
        const strengthDiv = document.getElementById('passwordStrength');
        const matchDiv = document.getElementById('passwordMatch');

        if (strengthDiv) {
            strengthDiv.textContent = '';
            strengthDiv.className = 'password-strength';
        }

        if (matchDiv) {
            matchDiv.textContent = '';
            matchDiv.className = 'password-strength';
        }

        hideMessages();

        // Focus on current password field
        setTimeout(() => {
            const currentPasswordField = document.getElementById('currentPassword');
            if (currentPasswordField) {
                currentPasswordField.focus();
            }
        }, 100);
    }
    </script>
</body>

</html>