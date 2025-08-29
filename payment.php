<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ชำระเงิน</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
        }

        /* Header */
        .header {
            background: #940606;
            padding: 20px 0;
            box-shadow: 0 4px 20px rgba(5, 26, 55, 0.3);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            padding: 0 20px;
        }

        .header-title {
            color: #fff;
            font-size: 28px;
            font-weight: 600;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        /* Toast Container */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
        }

        .toast {
            display: flex;
            align-items: center;
            padding: 16px 20px;
            margin-bottom: 12px;
            border-radius: 12px;
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.15);
            transform: translateX(450px);
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            font-weight: 500;
            position: relative;
            overflow: hidden;
            min-height: 64px;
        }

        .toast.show {
            transform: translateX(0);
            opacity: 1;
        }

        .toast.hide {
            transform: translateX(450px);
            opacity: 0;
        }

        .toast-success {
            background: linear-gradient(135deg, rgba(212, 237, 218, 0.95) 0%, rgba(195, 230, 203, 0.95) 100%);
            color: #155724;
            border: 1px solid rgba(40, 167, 69, 0.2);
        }

        .toast-error {
            background: linear-gradient(135deg, rgba(248, 215, 218, 0.95) 0%, rgba(245, 198, 203, 0.95) 100%);
            color: #721c24;
            border: 1px solid rgba(220, 53, 69, 0.2);
        }

        .toast-loading {
            background: linear-gradient(135deg, rgba(209, 236, 241, 0.95) 0%, rgba(190, 229, 235, 0.95) 100%);
            color: #0c5460;
            border: 1px solid rgba(23, 162, 184, 0.2);
        }

        .toast-content {
            flex: 1;
            line-height: 1.5;
        }

        .toast-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            padding: 4px;
            margin-left: 12px;
            border-radius: 50%;
            opacity: 0.7;
            transition: all 0.2s ease;
            color: currentColor;
        }

        .toast-close:hover {
            opacity: 1;
            background: rgba(0, 0, 0, 0.1);
        }

        /* Main Content */
        .main-content {
            max-width: 900px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .payment-container {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(5, 26, 55, 0.1);
            overflow: hidden;
        }

        .payment-header {
            background: #051A37;
            padding: 30px;
            text-align: center;
        }

        .payment-title {
            color: #fff;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .payment-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 16px;
        }

        .content {
            padding: 40px;
        }

        /* Sections */
        .section {
            margin-bottom: 40px;
            background: #f8f9fa;
            border-radius: 12px;
            padding: 30px;
            border-left: 4px solid #940606;
        }

        .section-title {
            font-size: 22px;
            font-weight: 600;
            color: #051A37;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
        }

        .section-title::before {
            content: '';
            width: 4px;
            height: 24px;
            background: #940606;
            margin-right: 12px;
            border-radius: 2px;
        }

        /* Form Styling */
        .form-row {
            display: grid;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-row.two-cols {
            grid-template-columns: 1fr 1fr;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #051A37;
            font-weight: 500;
        }

        .required {
            color: #940606;
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
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            background: #fff;
            transition: all 0.3s ease;
        }

        textarea {
            height: 120px;
            resize: vertical;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #940606;
            box-shadow: 0 0 0 3px rgba(148, 6, 6, 0.1);
        }

        /* Address Section */
        .address-section {
            background: #fff;
            border-radius: 12px;
            padding: 0;
            border: 2px solid #e9ecef;
            overflow: hidden;
        }

        .address-header {
            background: linear-gradient(135deg, #051A37 0%, #0a2448 100%);
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .address-title {
            color: #fff;
            font-size: 18px;
            font-weight: 600;
        }

        .add-address-btn {
            background: #940606;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .add-address-btn:hover {
            background: #b50707;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(148, 6, 6, 0.3);
        }

        .address-list {
            padding: 20px;
            min-height: 100px;
        }

        .address-item {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            background: #f8f9fa;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .address-item:hover {
            border-color: #940606;
            background: #fff;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(148, 6, 6, 0.15);
        }

        .address-item.selected {
            border-color: #940606;
            background: #fff;
            box-shadow: 0 6px 20px rgba(148, 6, 6, 0.2);
        }

        .address-name {
            font-weight: 600;
            color: #051A37;
            font-size: 16px;
            margin-bottom: 8px;
        }

        .address-info {
            color: #6c757d;
            line-height: 1.6;
            margin-bottom: 16px;
        }

        .address-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-default {
            background: #28a745;
            color: #fff;
        }

        .btn-edit {
            background: #ffc107;
            color: #212529;
        }

        .btn-delete {
            background: #dc3545;
            color: #fff;
        }

        /* Payment Method Section */
        .payment-method {
            color: #051A37;
            border-radius: 12px;
            padding: 30px;
        }

        .payment-method h3 {
            margin-bottom: 20px;
            font-size: 20px;
        }

        .bank-details {
            display: grid;
            gap: 12px;
            margin-bottom: 30px;
        }

        .bank-detail {
            display: flex;
            justify-content: space-between;
            padding: 12px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
        }

        .bank-label {
            font-weight: 500;
            opacity: 0.9;
        }

        .bank-value {
            font-weight: 600;
        }

        /* QR Code Section */
        .qr-section {
            text-align: center;
            margin-top: 30px;
        }

        .qr-title {
            color: #051A37;
            margin-bottom: 20px;
            font-size: 18px;
        }

        .qr-code {
            width: 280px;
            height: 280px;
            background: #fff;
            border-radius: 16px;
            margin: 0 auto;
            padding: 20px;
            border: 2px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            font-size: 14px;
        }

        /* File Upload */
        .file-upload-section {
            background: #fff;
            border: 3px dashed #940606;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .file-upload-section:hover,
        .file-upload-section.dragover {
            border-color: #b50707;
            background: #fafafa;
        }

        .file-upload-input {
            display: none;
        }

        .file-upload-button {
            display: inline-block;
            background: linear-gradient(135deg, #940606 0%, #b50707 100%);
            color: #fff;
            padding: 16px 32px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 16px;
        }

        .file-upload-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(148, 6, 6, 0.3);
        }

        .file-upload-text {
            color: #6c757d;
            font-size: 16px;
            margin-bottom: 8px;
        }

        .file-upload-hint {
            color: #adb5bd;
            font-size: 14px;
        }

        /* Uploaded Files */
        .uploaded-files {
            margin-top: 20px;
        }

        .uploaded-file-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fff;
            padding: 16px;
            border-radius: 8px;
            border: 2px solid #e9ecef;
            margin-bottom: 12px;
            transition: all 0.3s ease;
        }

        .file-info {
            display: flex;
            align-items: center;
            flex: 1;
        }

        .file-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #051A37 0%, #0a2448 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 12px;
            font-weight: bold;
            margin-right: 12px;
        }

        .file-details {
            flex: 1;
        }

        .file-name {
            font-weight: 500;
            color: #051A37;
            margin-bottom: 4px;
        }

        .file-size {
            color: #6c757d;
            font-size: 12px;
        }

        .file-actions {
            display: flex;
            gap: 8px;
        }

        .file-preview-btn {
            background: #28a745;
            color: #fff;
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
        }

        .file-remove-btn {
            background: #dc3545;
            color: #fff;
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
        }

        /* Order Summary - แก้ไขให้ตรงกับ JS */
        .customer-info {
            background: #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .customer-info h3 {
            color: #051A37;
            font-size: 18px;
            margin-bottom: 16px;
            font-weight: 600;
        }

        .customer-info p {
            margin-bottom: 8px;
            color: #495057;
            line-height: 1.5;
        }

        .cart-items {
            margin-bottom: 30px;
        }

        .cart-item {
            display: flex;
            align-items: flex-start;
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
            background: #fff;
            border-radius: 8px;
            margin-bottom: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .cart-item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 16px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
        }

        .cart-item-info {
            flex: 1;
        }

        .cart-item-name {
            font-size: 16px;
            font-weight: 600;
            color: #051A37;
            margin-bottom: 8px;
        }

        .cart-item-quantity {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .cart-item-price {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .cart-item-total {
            color: #940606;
            font-size: 16px;
            font-weight: 600;
        }

        .cart-summary {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 24px;
            border-left: 4px solid #051A37;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 16px;
            color: #495057;
        }

        .summary-row.total {
            font-weight: 700;
            font-size: 20px;
            color: #051A37;
            border-top: 2px solid #051A37;
            padding-top: 16px;
            margin-top: 16px;
        }

        .summary-row.total span:last-child {
            color: #940606;
        }

        /* Submit Button */
        .submit-btn {
            width: 100%;
            height: 60px;
            background: linear-gradient(135deg, #940606 0%, #b50707 100%);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 30px;
            box-shadow: 0 4px 20px rgba(148, 6, 6, 0.3);
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 32px rgba(148, 6, 6, 0.4);
        }

        .submit-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(5, 26, 55, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            background: #fff;
            border-radius: 16px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(5, 26, 55, 0.3);
        }

        .modal-header {
            background: linear-gradient(135deg, #051A37 0%, #0a2448 100%);
            padding: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            color: #fff;
            font-size: 20px;
            font-weight: 600;
        }

        .close-btn {
            background: none;
            border: none;
            color: #fff;
            font-size: 24px;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .close-btn:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .modal-body {
            padding: 30px;
        }

        .modal-footer {
            padding: 24px;
            border-top: 1px solid #e9ecef;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .btn-primary {
            background: linear-gradient(135deg, #940606 0%, #b50707 100%);
            color: #fff;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #6c757d;
            padding: 12px 24px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .form-row.two-cols {
                grid-template-columns: 1fr;
            }
            
            .content {
                padding: 24px;
            }
            
            .qr-code {
                width: 240px;
                height: 240px;
            }
            
            .cart-item {
                flex-direction: column;
            }
            
            .cart-item-image {
                width: 100%;
                height: 200px;
                margin-right: 0;
                margin-bottom: 16px;
            }
        }
    </style>
</head>
<body>
    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <img src="image/logo.png" width="100px">
            <h1 class="header-title">ระบบชำระเงิน</h1>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="payment-container">
            <div class="payment-header">
                <h1 class="payment-title">ชำระเงิน</h1>
                <p class="payment-subtitle">กรุณากรอกข้อมูลการชำระเงินให้ครบถ้วน</p>
            </div>

            <div class="content">
                <form id="paymentForm">
                    <!-- Customer Information -->
                    <div class="section">
                        <div class="section-title">ข้อมูลลูกค้า</div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>ชื่อ-นามสกุล <span class="required">*จำเป็น</span></label>
                                <input type="text" id="fullName" name="fullName" required placeholder="กรอกชื่อ-นามสกุล">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>บริษัท/ร้านค้า</label>
                                <input type="text" id="company" name="company" placeholder="ชื่อบริษัทหรือร้านค้า (ถ้ามี)">
                            </div>
                        </div>
                        <div class="form-row two-cols">
                            <div class="form-group">
                                <label>อีเมล <span class="required">*จำเป็น</span></label>
                                <input type="email" id="email" name="email" required placeholder="example@email.com">
                            </div>
                            <div class="form-group">
                                <label>เบอร์โทรศัพท์ <span class="required">*จำเป็น</span></label>
                                <input type="text" name="phone" id="phone" required placeholder="กรอกเบอร์โทรศัพท์">
                            </div>
                        </div>
                    </div>

                    <!-- Address Section -->
                    <div class="section">
                        <div class="section-title">ที่อยู่จัดส่ง</div>
                        <div class="address-section">
                            <div class="address-header">
                                <div class="address-title">เลือกที่อยู่จัดส่ง</div>
                                <button type="button" class="add-address-btn" id="addAddressBtn">+ เพิ่มที่อยู่</button>
                            </div>
                            <div class="address-list" id="addressList">
                                <p style="color: #6c757d; text-align: center; padding: 20px;">
                                    ยังไม่มีที่อยู่จัดส่ง กรุณาเพิ่มที่อยู่ใหม่
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="section">
                        <div class="section-title">วิธีการชำระเงิน</div>
                        <div class="payment-method">
                            <h3>โอนเงินผ่านธนาคาร</h3>
                            <div class="bank-details">
                                <div class="bank-detail">
                                    <span class="bank-label">ธนาคาร:</span>
                                    <span class="bank-value">กรุงไทย</span>
                                </div>
                                <div class="bank-detail">
                                    <span class="bank-label">ชื่อบัญชี:</span>
                                    <span class="bank-value">ปวิชกา อุดมสิทธิพัฒนา</span>
                                </div>
                                <div class="bank-detail">
                                    <span class="bank-label">เลขที่บัญชี:</span>
                                    <span class="bank-value">111-1-11111-1</span>
                                </div>
                            </div>
                            <div class="qr-section">
                                <h3 class="qr-title">หรือสแกนคิวอาร์โค้ด</h3>
                                <img src="image/qr.jpg" width="300px">
                            </div>
                        </div>
                    </div>

                    <!-- Payment Slip Upload -->
                    <div class="section">
                        <div class="section-title">แนบสลิปการโอนเงิน <span class="required">*จำเป็น</span></div>
                        <div class="file-upload-section" id="fileUploadSection">
                            <input type="file" id="slipUpload" class="file-upload-input" accept="image/*,.pdf" multiple>
                            <label for="slipUpload" class="file-upload-button">เลือกไฟล์</label>
                            <div class="file-upload-text">หรือลากไฟล์มาวางที่นี่</div>
                            <div class="file-upload-hint">รองรับไฟล์: JPG, PNG, PDF (ขนาดไม่เกิน 5MB)</div>
                        </div>
                        <div class="uploaded-files" id="uploadedFiles"></div>
                    </div>

                    <!-- Order Summary -->
                    <div class="section">
                        <div class="section-title">สรุปคำสั่งซื้อ</div>
                        <div id="orderSummary">
                            <p style="text-align: center; color: #6c757d;">กำลังโหลดข้อมูล...</p>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn">ยืนยันการสั่งซื้อ</button>
                </form>
            </div>
        </div>
    </main>

    <!-- Address Modal -->
    <div class="modal-overlay" id="addressModal">
        <div class="modal">
            <div class="modal-header">
                <h2 class="modal-title">เพิ่มที่อยู่ใหม่</h2>
                <button type="button" class="close-btn" id="closeModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addressForm">
                    <div class="form-group">
                        <label>ชื่อที่อยู่ <span class="required">*</span></label>
                        <input type="text" name="addressName" required placeholder="เช่น: บ้าน, ที่ทำงาน">
                    </div>
                    <div class="form-group">
                        <label>ที่อยู่เต็ม <span class="required">*</span></label>
                        <textarea name="fullAddress" required placeholder="กรอกที่อยู่เต็มรายละเอียด"></textarea>
                    </div>
                    <div class="form-row two-cols">
                        <div class="form-group">
                            <label>จังหวัด <span class="required">*</span></label>
                            <input type="text" name="province" required placeholder="จังหวัด">
                        </div>
                        <div class="form-group">
                            <label>รหัสไปรษณีย์ <span class="required">*</span></label>
                            <input type="text" name="zipCode" required placeholder="รหัสไปรษณีย์">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="cancelBtn">ยกเลิก</button>
                <button type="button" class="btn-primary" id="saveAddressBtn">บันทึกที่อยู่</button>
            </div>
        </div>
    </div>

    <script src="payment.js"></script>

</body>
</html>