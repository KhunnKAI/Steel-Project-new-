<!-- ========================
     ADMIN MANAGEMENT SYSTEM - PHP & HTML
     ======================== -->

<?php
// ========================
// SECURITY & AUTHENTICATION
// ========================

// FUNCTION: ตรวจสอบการเข้าสู่ระบบ (ต้องลงทะเบียนก่อนเข้าหน้านี้)
require_once 'controllers/config.php';
requireLogin();

// FUNCTION: ดึงข้อมูลผู้ดูแลปัจจุบัน
$current_admin = getCurrentAdmin();
if (!$current_admin) {
    header("Location: controllers/logout.php");
    exit();
}

// ========================
// AUTHORIZATION CHECK
// ========================

// FUNCTION: ตรวจสอบสิทธิ์การเข้าถึง (เฉพาะ manager และ super admin)
$allowed_roles = ['manager', 'super'];

if (!isset($current_admin['position']) || !in_array($current_admin['position'], $allowed_roles)) {
    $_SESSION['error'] = "คุณไม่มีสิทธิ์เข้าถึงหน้านี้";
    header("Location: accessdenied_admin.html");
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <!-- ========================
         META & DOCUMENT INFO
         ======================== -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ดูแล - ช้างเหล็กไทย</title>
    <link rel="icon" type="image/png" href="image\logo_cropped.png">
    
    <!-- ========================
         EXTERNAL RESOURCES
         ======================== -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="sidebar_admin.css">
    
    <style>
        /* ========================
           GLOBAL STYLES
           ======================== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter';
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        /* ========================
           NAVBAR & TOGGLE
           ======================== */
        .navbar-toggle {
            position: fixed;
            top: 20px;
            left: 20px;
            font-size: 24px;
            cursor: pointer;
            z-index: 1001;
            color: #333;
            background: white;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: none;
            transition: all 0.3s ease;
        }

        .navbar-toggle:hover {
            background: #f8f9fa;
            transform: scale(1.05);
        }

        /* ========================
           LAYOUT STRUCTURE
           ======================== */
        .container {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            padding: 30px;
            margin-left: 260px;
            transition: all 0.3s ease;
            min-height: 100vh;
        }

        /* ========================
           HEADER SECTION
           ======================== */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .header h1 {
            font-size: 28px;
            color: #333;
            font-weight: 600;
        }

        .search-add {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        /* ========================
           BUTTON STYLES
           ======================== */
        .add-btn {
            padding: 12px 24px;
            background: linear-gradient(45deg, #990000, #cc0000);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-family: 'Inter';
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .add-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(153, 0, 0, 0.3);
        }

        /* ========================
           FILTERS SECTION
           ======================== */
        .filters-section {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }

        .filters-row {
            display: flex;
            gap: 15px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
            min-width: 180px;
        }

        .filter-group label {
            font-size: 13px;
            color: #666;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-group select {
            padding: 10px 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Inter';
            transition: all 0.3s ease;
        }

        .filter-group select:focus {
            outline: none;
            border-color: #990000;
            box-shadow: 0 0 0 3px rgba(153, 0, 0, 0.1);
        }

        .search-container {
            position: relative;
            flex: 1;
            min-width: 250px;
        }

        .search-container input {
            width: 100%;
            padding: 10px 40px 10px 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Inter';
            transition: all 0.3s ease;
        }

        .search-container input:focus {
            outline: none;
            border-color: #990000;
            box-shadow: 0 0 0 3px rgba(153, 0, 0, 0.1);
        }

        .search-container i {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            pointer-events: none;
        }

        .button-group {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        .btn-filter-search,
        .btn-filter-reset {
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-family: 'Inter';
            font-size: 13px;
            min-height: 40px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .btn-filter-search {
            background: linear-gradient(45deg, #dc3545, #c82333);
            color: white;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }

        .btn-filter-search:hover {
            background: linear-gradient(45deg, #c82333, #bd2130);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
        }

        .btn-filter-reset {
            background: linear-gradient(45deg, #6c757d, #868e96);
            color: white;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }

        .btn-filter-reset:hover {
            background: linear-gradient(45deg, #5a6268, #6c757d);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
        }

        /* ========================
           STATISTICS CARDS
           ======================== */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .stat-card.total::before { background: linear-gradient(90deg, #007bff, #0056b3); }
        .stat-card.active::before { background: linear-gradient(90deg, #28a745, #20c997); }
        .stat-card.inactive::before { background: linear-gradient(90deg, #dc3545, #c82333); }

        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        /* ========================
           TABLE STYLES
           ======================== */
        .table-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            color: #555;
        }

        /* ========================
           STAFF INFO DISPLAY
           ======================== */
        .staff-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .staff-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 16px;
        }

        .staff-details {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .staff-name {
            font-weight: 600;
            color: #333;
            font-size: 16px;
        }

        .staff-phone {
            font-size: 13px;
            color: #666;
        }

        .staff-department {
            font-size: 11px;
            color: #888;
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 4px;
            display: inline-block;
            width: fit-content;
        }

        /* ========================
           BADGE STYLES
           ======================== */
        .role-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }

        .role-manager { background: #e7e3ff; color: #6f42c1; }
        .role-sales { background: #fff3cd; color: #856404; }
        .role-warehouse { background: #cce5ff; color: #004085; }
        .role-shipping { background: #d4edda; color: #155724; }
        .role-accounting { background: #f8d7da; color: #721c24; }
        .role-super { background: #ffe7f2; color: #e83e8c; }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }

        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }

        /* ========================
           ACTION BUTTONS
           ======================== */
        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .actions button {
            padding: 8px 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            font-family: 'Inter';
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .actions button:hover {
            transform: translateY(-2px);
        }

        .view-btn { background: linear-gradient(45deg, #17a2b8, #20c997); color: white; }
        .view-btn:hover { box-shadow: 0 4px 12px rgba(23, 162, 184, 0.4); }

        .edit-btn { background: linear-gradient(45deg, #ffc107, #ffb300); color: white; }
        .edit-btn:hover { box-shadow: 0 4px 12px rgba(255, 193, 7, 0.4); }

        .toggle-btn { background: linear-gradient(45deg, #6c757d, #5a6268); color: white; }
        .toggle-btn:hover { box-shadow: 0 4px 12px rgba(108, 117, 125, 0.4); }

        .delete-btn { background: linear-gradient(45deg, #dc3545, #c82333); color: white; }
        .delete-btn:hover { box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4); }

        /* ========================
           PAGINATION
           ======================== */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            padding: 20px;
        }

        .pagination button {
            padding: 8px 12px;
            border: 2px solid #e9ecef;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            font-family: 'Inter';
            transition: all 0.3s ease;
        }

        .pagination button:hover,
        .pagination button.active {
            background: #990000;
            color: white;
            border-color: #990000;
        }

        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* ========================
           MODAL STYLES
           ======================== */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }

        .modal-header h2 {
            color: #333;
            font-size: 20px;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
            transition: all 0.2s ease;
        }

        .close-btn:hover {
            color: #333;
            transform: rotate(90deg);
        }

        /* ========================
           FORM STYLES
           ======================== */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Inter';
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #990000;
            box-shadow: 0 0 0 3px rgba(153, 0, 0, 0.1);
        }

        .form-group input[type="checkbox"] {
            width: auto;
            margin-right: 8px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }

        /* ========================
           AUTO-GENERATED FIELD
           ======================== */
        .auto-generated {
            background-color: #f8f9fa !important;
            cursor: not-allowed;
        }

        .auto-generated-label {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #6c757d;
        }

        .auto-generated-label i {
            color: #28a745;
            font-size: 12px;
        }

        /* ========================
           PASSWORD FIELD STYLES
           ======================== */
        .password-field {
            position: relative;
        }

        .password-controls {
            display: flex;
            gap: 8px;
            align-items: stretch;
        }

        .password-input-wrapper {
            flex: 1;
            position: relative;
        }

        .password-input-wrapper input {
            padding-right: 45px;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .password-toggle:hover {
            color: #333;
            background: #f0f0f0;
        }

        .generate-password-btn {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 12px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            font-family: 'Inter';
            transition: all 0.3s ease;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .generate-password-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(40, 167, 69, 0.3);
        }

        /* ========================
           PASSWORD DISPLAY
           ======================== */
        .password-display {
            background: #e8f5e8;
            border: 2px solid #28a745;
            border-radius: 8px;
            padding: 12px;
            margin-top: 8px;
            font-family: 'Inter';
            font-size: 16px;
            font-weight: bold;
            color: #155724;
            text-align: center;
            letter-spacing: 2px;
            position: relative;
        }

        .password-display .copy-btn {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: #28a745;
            color: white;
            border: none;
            padding: 6px 8px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 11px;
            transition: all 0.2s ease;
        }

        .password-display .copy-btn:hover {
            background: #20a047;
        }

        /* ========================
           PASSWORD STRENGTH
           ======================== */
        .password-strength {
            margin-top: 8px;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
        }

        .password-strength.weak {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .password-strength.medium {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }

        .password-strength.strong {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        /* ========================
           FORM ACTIONS
           ======================== */
        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .form-actions button {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-family: 'Inter';
            transition: all 0.3s ease;
        }

        .save-btn {
            background: #990000;
            color: white;
        }

        .save-btn:hover {
            box-shadow: 0 4px 12px rgba(153, 0, 0, 0.3);
        }

        .cancel-form-btn {
            background: #6c757d;
            color: white;
        }

        .cancel-form-btn:hover {
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
        }

        /* ========================
           ANIMATIONS
           ======================== */
        @keyframes passwordGenerated {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .password-generated {
            animation: passwordGenerated 0.3s ease;
        }

        /* ========================
           RESPONSIVE DESIGN
           ======================== */
        @media screen and (max-width: 1200px) {
            .filters-row {
                gap: 12px;
            }

            .filter-group {
                min-width: 150px;
            }
        }

        @media screen and (max-width: 768px) {
            .navbar-toggle {
                display: block;
            }

            .main-content {
                padding: 80px 20px 20px;
                margin-left: 0;
            }

            .header {
                flex-direction: column;
                gap: 15px;
            }

            .filters-section {
                padding: 15px 20px;
            }

            .filters-row {
                flex-direction: column;
                gap: 10px;
            }

            .filter-group {
                width: 100%;
                min-width: unset;
            }

            .search-container {
                width: 100%;
                min-width: unset;
            }

            .button-group {
                width: 100%;
                flex-direction: row;
                gap: 10px;
            }

            .btn-filter-search,
            .btn-filter-reset {
                flex: 1;
                justify-content: center;
            }

            .stats-row {
                grid-template-columns: 1fr;
            }

            .staff-info {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }

            table {
                font-size: 14px;
            }

            th, td {
                padding: 10px 8px;
            }

            .actions {
                flex-direction: column;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .password-controls {
                flex-direction: column;
            }

            .form-actions {
                flex-direction: column;
            }

            .form-actions button {
                width: 100%;
            }
        }

        @media screen and (max-width: 480px) {
            .header h1 {
                font-size: 20px;
            }

            .actions button {
                font-size: 11px;
                padding: 6px 8px;
            }

            .modal-content {
                width: 95%;
                padding: 20px;
            }

            .modal-header h2 {
                font-size: 18px;
            }

            .stat-number {
                font-size: 20px;
            }
        }
    </style>
</head>

<body>
    <!-- ========================
         NAVIGATION TOGGLE
         ======================== -->
    <div class="navbar-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </div>

    <!-- ========================
         MAIN CONTAINER
         ======================== -->
    <div class="container">
        <?php include 'sidebar_admin.php'; ?>

        <main class="main-content">
            <!-- ========================
                 PAGE HEADER
                 ======================== -->
            <div class="header">
                <h1><i class="fas fa-users-cog"></i> จัดการผู้ดูแลและพนักงาน</h1>
                <div class="search-add">
                    <button class="add-btn" onclick="openAddModal()">
                        <i class="fas fa-user-plus"></i> เพิ่มพนักงาน
                    </button>
                </div>
            </div>

            <!-- ========================
                 STATISTICS CARDS
                 ======================== -->
            <div class="stats-row">
                <div class="stat-card total">
                    <div class="stat-number" id="totalStaff">0</div>
                    <div class="stat-label">พนักงานทั้งหมด</div>
                </div>
                <div class="stat-card active">
                    <div class="stat-number" id="activeStaff">0</div>
                    <div class="stat-label">ใช้งานอยู่</div>
                </div>
                <div class="stat-card inactive">
                    <div class="stat-number" id="inactiveStaff">0</div>
                    <div class="stat-label">ไม่ได้ใช้งาน</div>
                </div>
            </div>

            <!-- ========================
                 FILTER SECTION
                 ======================== -->
            <div class="filters-section">
                <div class="filters-row">
                    <div class="filter-group">
                        <label>บทบาท</label>
                        <select id="roleFilter">
                            <option value="">ทั้งหมด</option>
                            <option value="manager">ผู้จัดการ</option>
                            <option value="sales">พนักงานขาย</option>
                            <option value="warehouse">พนักงานคลัง</option>
                            <option value="shipping">พนักงานขนส่ง</option>
                            <option value="accounting">พนักงานบัญชี</option>
                            <option value="super">ผู้ดูแลระบบ</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>แผนก</label>
                        <select id="departmentFilter">
                            <option value="">ทั้งหมด</option>
                            <option value="management">บริหาร</option>
                            <option value="sales">ขาย</option>
                            <option value="warehouse">คลังสินค้า</option>
                            <option value="logistics">ขนส่ง</option>
                            <option value="accounting">บัญชี</option>
                            <option value="it">เทคโนโลยีสารสนเทศ</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>สถานะ</label>
                        <select id="statusFilter">
                            <option value="">ทั้งหมด</option>
                            <option value="active">ใช้งานอยู่</option>
                            <option value="inactive">ไม่ได้ใช้งาน</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>เรียงลำดับ</label>
                        <select id="sortFilter">
                            <option value="name">ชื่อ</option>
                            <option value="role">บทบาท</option>
                            <option value="department">แผนก</option>
                            <option value="created">วันที่สร้าง</option>
                        </select>
                    </div>

                    <div class="filter-group" style="flex: 1; min-width: 250px;">
                        <label>ค้นหา</label>
                        <div class="search-container">
                            <input type="text" id="searchInput" placeholder="ค้นหาชื่อพนักงาน, บทบาท, รหัส...">
                            <i class="fas fa-search"></i>
                        </div>
                    </div>

                    <div class="button-group">
                        <button id="searchBtn" class="btn-filter-search" type="button" title="ค้นหาข้อมูล">
                            <i class="fas fa-search"></i>
                            <span>ค้นหา</span>
                        </button>
                        <button id="resetBtn" class="btn-filter-reset" type="button" title="รีเซ็ตทั้งหมด">
                            <i class="fas fa-redo"></i>
                            <span>รีเซ็ต</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ========================
                 STAFF TABLE
                 ======================== -->
            <div class="table-container">
                <table id="staffTable">
                    <thead>
                        <tr>
                            <th>พนักงาน</th>
                            <th>บทบาท</th>
                            <th>แผนก</th>
                            <th>สถานะ</th>
                            <th>การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody id="staffTableBody">
                        <!-- FUNCTION: แทรกแถว Staff ด้วย JavaScript -->
                    </tbody>
                </table>
            </div>

            <!-- ========================
                 PAGINATION
                 ======================== -->
            <div class="pagination" id="pagination">
                <!-- FUNCTION: สร้างปุ่มการแบ่งหน้า ด้วย JavaScript -->
            </div>
        </main>
    </div>

    <!-- ========================
         MODAL: ADD/EDIT STAFF
         ======================== -->
    <div id="staffModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">เพิ่มพนักงานใหม่</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            
            <form id="staffForm">
                <!-- FUNCTION: ฟอร์มเพิ่ม/แก้ไข ข้อมูลพนักงาน -->
                
                <div class="form-row">
                    <div class="form-group">
                        <label>ชื่อ-นามสกุล *</label>
                        <input type="text" id="staffName" required>
                    </div>
                    <div class="form-group">
                        <label>เบอร์โทรศัพท์ *</label>
                        <input type="tel" id="staffPhone" required placeholder="08X-XXX-XXXX">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="auto-generated-label">
                            รหัสพนักงาน *
                            <i class="fas fa-magic" title="สร้างอัตโนมัติ"></i>
                        </label>
                        <input type="text" id="staffCode" readonly class="auto-generated" placeholder="สร้างอัตโนมัติ">
                    </div>
                    <div class="form-group">
                        <label>บทบาท *</label>
                        <select id="staffRole" required onchange="updateDepartmentAndPermissions()">
                            <option value="">เลือกบทบาท</option>
                            <option value="manager">ผู้จัดการ</option>
                            <option value="sales">พนักงานขาย</option>
                            <option value="warehouse">พนักงานคลัง</option>
                            <option value="shipping">พนักงานขนส่ง</option>
                            <option value="accounting">พนักงานบัญชี</option>
                            <option value="super">ผู้ดูแลระบบ (สิทธิ์เต็ม)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>แผนก *</label>
                    <select id="staffDepartment" required onchange="generateStaffCode()">
                        <option value="">เลือกแผนก</option>
                        <option value="management">บริหาร</option>
                        <option value="sales">ขาย</option>
                        <option value="warehouse">คลังสินค้า</option>
                        <option value="logistics">ขนส่ง</option>
                        <option value="accounting">บัญชี</option>
                        <option value="it">เทคโนโลยีสารสนเทศ</option>
                    </select>
                </div>
                
                <div class="form-group password-field" id="passwordGroup">
                    <label>รหัสผ่าน *</label>
                    <div class="password-controls">
                        <div class="password-input-wrapper">
                            <input type="password" id="staffPassword" required>
                            <button type="button" class="password-toggle" onclick="togglePasswordVisibility('staffPassword', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <button type="button" class="generate-password-btn" onclick="generateRandomPassword()">
                            <i class="fas fa-dice"></i> สุ่มรหัส
                        </button>
                    </div>
                    <div id="generatedPasswordDisplay" style="display: none;">
                        <div class="password-display">
                            <span id="generatedPasswordText"></span>
                            <button type="button" class="copy-btn" onclick="copyPassword()">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    <div id="passwordStrength" class="password-strength" style="display: none;"></div>
                </div>

                <div class="form-group" id="passwordConfirmGroup">
                    <label>ยืนยันรหัสผ่าน *</label>
                    <div class="password-input-wrapper">
                        <input type="password" id="staffPasswordConfirm" required>
                        <button type="button" class="password-toggle" onclick="togglePasswordVisibility('staffPasswordConfirm', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="staffActive" checked>
                        <label for="staffActive">เปิดใช้งานทันที</label>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="cancel-form-btn" onclick="closeModal()">ยกเลิก</button>
                    <button type="submit" class="save-btn">บันทึก</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================
         MODAL: STAFF DETAILS
         ======================== -->
    <div id="staffDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="staffDetailsTitle">รายละเอียดพนักงาน</h2>
                <button class="close-btn" onclick="closeStaffDetailsModal()">&times;</button>
            </div>
            
            <!-- FUNCTION: แสดงข้อมูลส่วนตัวของพนักงาน -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                    <h3 style="color: #333; margin-bottom: 15px; font-size: 14px; font-weight: 600;">
                        <i class="fas fa-user"></i> ข้อมูลส่วนตัว
                    </h3>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span style="font-weight: 600; color: #666;">ชื่อ:</span>
                        <span style="color: #333;" id="detailName"></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span style="font-weight: 600; color: #666;">เบอร์โทร:</span>
                        <span style="color: #333;" id="detailPhone"></span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="font-weight: 600; color: #666;">รหัสพนักงาน:</span>
                        <span style="color: #333;" id="detailCode"></span>
                    </div>
                </div>
                
                <!-- FUNCTION: แสดงข้อมูลงานของพนักงาน -->
                <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                    <h3 style="color: #333; margin-bottom: 15px; font-size: 14px; font-weight: 600;">
                        <i class="fas fa-briefcase"></i> ข้อมูลงาน
                    </h3>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span style="font-weight: 600; color: #666;">บทบาท:</span>
                        <span id="detailRole"></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span style="font-weight: 600; color: #666;">แผนก:</span>
                        <span style="color: #333;" id="detailDepartment"></span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="font-weight: 600; color: #666;">สถานะ:</span>
                        <span id="detailStatus"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================
         SCRIPTS
         ======================== -->
    <script src="admins_admin.js"></script>

</body>
</html>