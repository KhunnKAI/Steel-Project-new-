<?php
// ========================
// PRODUCTS ADMIN PAGE
// ========================

// ========================
// SECURITY & AUTHENTICATION
// ========================

// FUNCTION: โหลดไฟล์กำหนดค่าและตรวจสอบการเข้าสู่ระบบ
require_once 'controllers/config.php';

// FUNCTION: บังคับให้ผู้ใช้เข้าสู่ระบบ
requireLogin();

// FUNCTION: ดึงข้อมูลผู้ดูแลระบบปัจจุบัน
$current_admin = getCurrentAdmin();
if (!$current_admin) {
    header("Location: controllers/logout.php");
    exit();
}

// FUNCTION: ตรวจสอบสิทธิ์การเข้าถึง
$allowed_roles = ['manager', 'super', 'sales', 'warehouse'];

if (!isset($current_admin['position']) || !in_array($current_admin['position'], $allowed_roles)) {
    $_SESSION['error'] = "คุณไม่มีสิทธิ์เข้าถึงหน้านี้";
    header("Location: accessdenied_admin.html");
    exit();
}

?>

<!DOCTYPE html>
<html lang="th">
    
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสินค้า - ช้างเหล็กไทย</title>
    <link rel="icon" type="image/png" href="image\logo_cropped.png">
    
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="sidebar_admin.css">

    <style>
        /* ======================== */
        /* BASE STYLES              */
        /* ======================== */
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

        /* ======================== */
        /* LAYOUT                   */
        /* ======================== */
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
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: none;
            transition: all 0.3s ease;
        }

        .navbar-toggle:hover {
            background: #f8f9fa;
            transform: scale(1.05);
        }

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

        /* ======================== */
        /* HEADER                   */
        /* ======================== */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .header h1 {
            font-size: 28px;
            color: #333;
            font-weight: 600;
        }

        .add-stockmovement {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        /* ======================== */
        /* SEARCH                   */
        /* ======================== */
        .search-container {
            position: relative;
        }

        .search-container input[type="text"] {
            padding: 12px 45px 12px 15px;
            width: 350px;
            border-radius: 10px;
            border: 2px solid #e9ecef;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .search-container input[type="text"]:focus {
            outline: none;
            border-color: #990000;
            box-shadow: 0 0 0 3px rgba(153, 0, 0, 0.1);
        }

        .search-container i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        /* ======================== */
        /* BUTTONS                  */
        /* ======================== */
        .add-btn,
        .stockmovement-btn,
        .reset-all-btn,
        .search-filter-btn {
            padding: 12px 24px;
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .add-btn {
            background: linear-gradient(45deg, #990000, #cc0000);
        }

        .add-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(153, 0, 0, 0.3);
        }

        .stockmovement-btn {
            background: linear-gradient(45deg, #007bff, #0056b3);
            text-decoration: none;
            white-space: nowrap;
        }

        .stockmovement-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
            background: linear-gradient(45deg, #0056b3, #004085);
        }

        .reset-all-btn {
            background: linear-gradient(45deg, #6c757d, #868e96);
            width: 100%;
            justify-content: center;
            box-shadow: 0 6px 20px rgba(198, 198, 198, 0.4);
        }

        .reset-all-btn:hover {
            background: linear-gradient(45deg, #5a6268, #6c757d);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(198, 198, 198, 0.4);
        }

        .search-filter-btn {
            background: linear-gradient(45deg, #dc3545, #c82333);
            flex: 1;
            justify-content: center;
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
        }

        .search-filter-btn:hover {
            background: linear-gradient(45deg, #c82333, #bd2130);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
        }

        /* ======================== */
        /* STATISTICS CARDS         */
        /* ======================== */
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
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            text-align: center;
        }

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

        /* ======================== */
        /* FILTERS SECTION          */
        /* ======================== */
        .filters-section {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            padding: 25px 30px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            position: relative;
        }

        .filters-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #990000, #cc0000, #ff6b6b);
            border-radius: 20px 20px 0 0;
        }

        .filters-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .filters-header h3 {
            color: #333;
            font-size: 18px;
            font-weight: 600;
            margin: 0;
        }

        .filters-header i {
            color: #990000;
            font-size: 20px;
        }

        .filters-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            align-items: end;
        }

        .filter-group {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            font-size: 13px;
            color: #555;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-group select,
        .filter-group input[type="date"] {
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .filter-group select:hover,
        .filter-group input[type="date"]:hover {
            border-color: #990000;
            box-shadow: 0 4px 12px rgba(153, 0, 0, 0.15);
            transform: translateY(-1px);
        }

        .filter-group select:focus,
        .filter-group input[type="date"]:focus {
            outline: none;
            border-color: #990000;
            box-shadow: 0 0 0 4px rgba(153, 0, 0, 0.1), 0 4px 12px rgba(153, 0, 0, 0.15);
            transform: translateY(-1px);
        }

        .date-range-group {
            grid-column: span 2;
            display: flex;
            gap: 15px;
            align-items: end;
            background: rgba(153, 0, 0, 0.03);
            padding: 20px;
            border-radius: 15px;
            border: 2px dashed rgba(153, 0, 0, 0.1);
            position: relative;
        }

        .date-range-group::before {
            content: '📅';
            position: absolute;
            top: -10px;
            left: 20px;
            background: white;
            padding: 0 10px;
            font-size: 16px;
        }

        .date-input-wrapper {
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1;
        }

        .date-input-wrapper label {
            color: #990000;
            font-weight: 600;
        }

        .filter-all {
            grid-column: span 1;
            display: flex;
            gap: 10px;
            align-self: end;
        }

        .filter-count-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: linear-gradient(45deg, #990000, #cc0000);
            color: white;
            font-size: 10px;
            font-weight: 700;
            padding: 4px 8px;
            border-radius: 12px;
            min-width: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(153, 0, 0, 0.3);
            animation: bounceIn 0.5s;
        }

        @keyframes bounceIn {
            0% { opacity: 0; transform: scale(0.3); }
            50% { opacity: 1; transform: scale(1.05); }
            70% { transform: scale(0.9); }
            100% { opacity: 1; transform: scale(1); }
        }

        /* ======================== */
        /* TABLE STYLES             */
        /* ======================== */
        .table-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
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

        /* ======================== */
        /* PRODUCT INFO CELLS       */
        /* ======================== */
        .product-info {
            display: flex;
            gap: 15px;
            align-items: flex-start;
        }

        .product-image-cell {
            width: 80px;
            height: 80px;
            flex-shrink: 0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .product-image-cell img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .product-image-cell:hover img {
            transform: scale(1.05);
        }

        .no-image-placeholder {
            color: #999;
            font-size: 24px;
        }

        .multiple-images-indicator {
            position: absolute;
            top: 4px;
            right: 4px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 8px;
            font-weight: 600;
        }

        .product-details {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .product-name {
            font-weight: 600;
            color: #333;
            font-size: 16px;
        }

        .product-description {
            font-size: 10px;
            color: #666;
        }

        /* ======================== */
        /* BADGES & STATUS          */
        /* ======================== */
        .product-code {
            font-family: 'Inter';
            font-weight: 600;
            color: #007bff;
            font-size: 10px;
            background: #f0f8ff;
            padding: 4px 8px;
            border-radius: 4px;
            display: inline-block;
        }

        .lot-badge {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            display: inline-block;
        }

        .category-badge {
            padding: 8px 16px;
            border-radius: 15px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .category-badge[data-category="rebar"] { background: #e74c3c; color: white; }
        .category-badge[data-category="steelplate"] { background: #34495e; color: white; }
        .category-badge[data-category="structuralsteel"] { background: #f39c12; color: white; }
        .category-badge[data-category="wiremesh"] { background: #27ae60; color: white; }
        .category-badge[data-category="steelproducts"] { background: #8e44ad; color: white; }

        /* ======================== */
        /* STOCK & PRICE INFO       */
        /* ======================== */
        .stock-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .stock-number {
            font-size: 16px;
            font-weight: 700;
            color: #333;
        }

        .stock-status {
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 12px;
            font-weight: 600;
        }

        .stock-status.high { background: #d4edda; color: #155724; }
        .stock-status.medium { background: #fff3cd; color: #856404; }
        .stock-status.low { background: #f8d7da; color: #721c24; }

        .price-info { font-weight: 500; color: #000000ff; }
        .date-info { font-size: 10px; color: #666; }
        .supplier-info { font-size: 10px; color: #333; font-weight: 500; max-width: 200px; word-wrap: break-word; }

        /* ======================== */
        /* ACTION BUTTONS           */
        /* ======================== */
        .actions {
            display: flex;
            gap: 8px;
        }

        .actions button {
            padding: 8px 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .actions button:hover {
            transform: translateY(-2px);
        }

        .view-btn { background: linear-gradient(45deg, #28a745, #20c997); color: white; }
        .view-btn:hover { box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4); }

        .edit-btn { background: linear-gradient(45deg, #ffc107, #ffb300); color: white; }
        .edit-btn:hover { box-shadow: 0 4px 12px rgba(255, 193, 7, 0.4); }

        .delete-btn { background: linear-gradient(45deg, #dc3545, #c82333); color: white; }
        .delete-btn:hover { box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4); }

        /* ======================== */
        /* PAGINATION               */
        /* ======================== */
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
            transition: all 0.3s ease;
        }

        .pagination button:hover,
        .pagination button.active {
            background: #990000;
            color: white;
            border-color: #990000;
        }

        /* ======================== */
        /* MODAL STYLES             */
        /* ======================== */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 30px;
            border-radius: 20px;
            width: 90%;
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .modal-header h2 {
            color: #333;
            font-size: 24px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #666;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .close-btn:hover {
            background: #f0f0f0;
            color: #333;
        }

        /* ======================== */
        /* FORM STYLES              */
        /* ======================== */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-grid-full {
            grid-column: 1 / -1;
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
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #990000;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .form-actions button {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .save-btn { background: #990000; color: white; }
        .cancel-btn { background: #6c757d; color: white; }

        /* ======================== */
        /* IMAGE UPLOAD SECTION     */
        /* ======================== */
        .images-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 20px;
            border: 2px dashed #dee2e6;
            transition: all 0.3s ease;
        }

        .images-section.drag-over {
            border-color: #990000;
            background: linear-gradient(135deg, #fff5f5 0%, #ffe6e6 100%);
            transform: scale(1.02);
        }

        .images-section h3 {
            color: #333;
            font-size: 18px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .drop-zone {
            border: 2px dashed #ced4da;
            border-radius: 12px;
            padding: 40px 20px;
            text-align: center;
            background: white;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .drop-zone.drag-over {
            border-color: #990000;
            background: linear-gradient(135deg, #fff5f5 0%, #ffe6e6 100%);
            transform: scale(1.02);
        }

        .drop-zone-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }

        .drop-zone-icon {
            font-size: 48px;
            color: #990000;
            opacity: 0.7;
            transition: all 0.3s ease;
        }

        .drop-zone.drag-over .drop-zone-icon {
            transform: scale(1.2);
            opacity: 1;
        }

        .drop-zone-text {
            font-size: 16px;
            color: #666;
            font-weight: 500;
        }

        .drop-zone-subtext {
            font-size: 14px;
            color: #999;
        }

        .file-input {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .upload-button {
            padding: 12px 24px;
            background: linear-gradient(45deg, #990000, #cc0000);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .upload-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(153, 0, 0, 0.3);
        }

        /* ======================== */
        /* IMAGE PREVIEW             */
        /* ======================== */
        .image-preview-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 15px;
            margin-top: 20px;
            max-height: 300px;
            overflow-y: auto;
            padding: 10px;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 12px;
        }

        .image-preview {
            position: relative;
            width: 120px;
            height: 120px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .image-preview:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .main-image-indicator {
            position: absolute;
            top: 8px;
            left: 8px;
            background: linear-gradient(45deg, #ffc107, #ffb300);
            color: white;
            font-size: 10px;
            padding: 4px 8px;
            border-radius: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .image-preview-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .image-preview:hover .image-preview-overlay {
            opacity: 1;
        }

        .preview-action-btn {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .preview-view-btn { background: #007bff; color: white; }
        .preview-delete-btn { background: #dc3545; color: white; }

        .preview-action-btn:hover {
            transform: scale(1.1);
        }

        /* ======================== */
        /* DIMENSIONS SECTION       */
        /* ======================== */
        .dimensions-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .dimensions-section h3 {
            color: #333;
            font-size: 16px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .dimension-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 10px;
            margin-bottom: 10px;
        }

        /* ======================== */
        /* PRODUCT VIEW MODAL       */
        /* ======================== */
        .product-view-modal {
            max-width: 1000px;
        }

        .product-view-content {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .product-view-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #990000;
        }

        .product-view-code {
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'Inter';
            font-size: 18px;
            font-weight: 700;
            color: #333;
        }

        .product-view-code i {
            color: #990000;
            font-size: 20px;
        }

        .product-view-main {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 25px;
            align-items: start;
        }

        .product-view-images {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
        }

        .main-image-container {
            width: 100%;
            height: 250px;
            border-radius: 8px;
            overflow: hidden;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 15px;
            border: 1px solid #e9ecef;
        }

        .main-image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            cursor: pointer;
        }

        .main-image-container .no-image-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            color: #999;
            font-size: 14px;
        }

        .main-image-container .no-image-placeholder i {
            font-size: 32px;
        }

        .thumbnail-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(50px, 1fr));
            gap: 8px;
            max-height: 150px;
            overflow-y: auto;
        }

        .thumbnail {
            width: 50px;
            height: 50px;
            border-radius: 6px;
            overflow: hidden;
            cursor: pointer;
            border: 2px solid #e9ecef;
            background: white;
        }

        .thumbnail.active {
            border-color: #990000;
        }

        .thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-view-details {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .product-view-details h3 {
            font-size: 24px;
            color: #333;
            font-weight: 700;
            line-height: 1.3;
            margin-bottom: 5px;
        }

        .product-description-text {
            font-size: 15px;
            color: #666;
            line-height: 1.5;
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            border-left: 3px solid #990000;
        }

        .product-view-specs {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }

        .spec-item {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 12px;
        }

        .spec-item label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            color: #666;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .spec-item label i {
            color: #990000;
            width: 16px;
            text-align: center;
        }

        .spec-item span {
            font-size: 16px;
            font-weight: 700;
            color: #333;
            display: block;
        }

        .lot-value {
            background: #007bff;
            color: white;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 10px !important;
            font-weight: 600 !important;
            display: inline-block !important;
        }

        .stock-value { color: #28a745; }
        .price-value { color: #990000; }
        .date-value { color: #6c757d; }
        .supplier-value { color: #333; font-weight: 600; }

        .product-view-dimensions {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            border: 1px solid #e9ecef;
        }

        .product-view-dimensions h4 {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 16px;
            color: #333;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .product-view-dimensions h4 i {
            color: #990000;
            font-size: 18px;
        }

        .dimensions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
        }

        .dimension-item {
            background: white;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            text-align: center;
        }

        .dimension-item .dimension-label {
            font-size: 11px;
            color: #666;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .dimension-item .dimension-value {
            font-size: 16px;
            font-weight: 700;
            color: #333;
        }

        .dimension-item .dimension-unit {
            font-size: 12px;
            color: #999;
            margin-left: 3px;
        }

        /* ======================== */
        /* IMAGE GALLERY MODAL      */
        /* ======================== */
        .image-gallery-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 3000;
        }

        .gallery-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            text-align: center;
        }

        .gallery-image {
            max-width: 100%;
            max-height: 70vh;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .gallery-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            color: white;
        }

        .gallery-nav-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            padding: 15px 20px;
            border-radius: 50px;
            cursor: pointer;
            font-size: 18px;
            transition: all 0.3s ease;
        }

        .gallery-nav-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        .gallery-nav-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .gallery-close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 24px;
            transition: all 0.3s ease;
        }

        .gallery-close-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        .gallery-info {
            color: white;
            font-size: 14px;
        }

        /* ======================== */
        /* RESPONSIVE DESIGN        */
        /* ======================== */
        @media screen and (max-width: 768px) {
            .navbar-toggle {
                display: block;
            }

            .main-content {
                padding: 80px 20px 20px;
                margin-left: 0;
            }

            .stats-row {
                grid-template-columns: 1fr;
            }

            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .search-container input[type="text"] {
                width: 100%;
            }

            table {
                font-size: 14px;
            }

            th,
            td {
                padding: 10px 8px;
            }
        }

        @media screen and (max-width: 480px) {
            th,
            td {
                padding: 8px 4px;
            }

            .stat-card {
                padding: 15px;
            }

            .stat-number {
                font-size: 18px;
            }
        }
    </style>
</head>

<body>
    <!-- ======================== -->
    <!-- NAVBAR TOGGLE BUTTON     -->
    <!-- ======================== -->
    <div class="navbar-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </div>

    <!-- ======================== -->
    <!-- MAIN CONTAINER           -->
    <!-- ======================== -->
    <div class="container">
        <!-- SIDEBAR -->
        <?php include 'sidebar_admin.php'; ?>

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <!-- HEADER -->
            <div class="header">
                <h1><i class="fas fa-box"></i> จัดการสินค้า</h1>
                <div class="add-stockmovement">
                    <button class="add-btn" onclick="openAddModal()">
                        <i class="fas fa-plus"></i> เพิ่มสินค้า
                    </button>
                    <button class="stockmovement-btn" onclick="window.location.href='stockmovement_admin.php'">
                        <i class="fas fa-exchange-alt"></i> บันทึกการเคลื่อนไหวสินค้า
                    </button>
                </div>
            </div>

            <!-- STATISTICS CARDS -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-number" id="totalProducts">0</div>
                    <div class="stat-label">สินค้าทั้งหมด</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="lowStockProducts">0</div>
                    <div class="stat-label">สินค้าใกล้หมด</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="filteredProducts">0</div>
                    <div class="stat-label">ผลการค้นหา</div>
                </div>
            </div>

            <!-- FILTERS SECTION -->
            <div class="filters-section">
                <div class="filters-header">
                    <i class="fas fa-filter"></i>
                    <h3>ตัวกรองข้อมูล</h3>
                    <div id="activeFiltersCount" class="filter-count-badge" style="display: none;">0</div>
                </div>

                <div class="filters-row">
                    <div class="filter-group">
                        <label>หมวดหมู่</label>
                        <select id="categoryFilter">
                            <option value="">ทั้งหมด</option>
                            <option value="rebar">เหล็กเส้น</option>
                            <option value="steelplate">เหล็กแผ่น</option>
                            <option value="structuralsteel">เหล็กรูปพรรณ</option>
                            <option value="wiremesh">เหล็กตะแกรง/ตาข่าย</option>
                            <option value="steelproducts">อื่นๆ</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>สถานะสต็อก</label>
                        <select id="stockFilter">
                            <option value="">ทั้งหมด</option>
                            <option value="high">สต็อกเพียงพอ</option>
                            <option value="medium">สต็อกปานกลาง</option>
                            <option value="low">สต็อกต่ำ</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>เรียงตาม</label>
                        <select id="sortFilter">
                            <option value="name">ชื่อสินค้า</option>
                            <option value="stock">จำนวนเหลือ</option>
                            <option value="lot">ล็อต</option>
                            <option value="receivedDate_desc">วันที่รับเข้า (ใหม่-เก่า)</option>
                            <option value="receivedDate_asc">วันที่รับเข้า (เก่า-ใหม่)</option>
                            <option value="supplier">ซัพพลายเออร์</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>วันที่เริ่มต้น</label>
                        <input type="date" id="startDateFilter">
                    </div>
                    <div class="filter-group">
                        <label>วันที่สิ้นสุด</label>
                        <input type="date" id="endDateFilter">
                    </div>

                    <div class="filter-all">
                        <div class="search-container">
                            <input type="text" id="searchInput" placeholder="ค้นหาชื่อสินค้า, ล็อต หรือรหัสสินค้า...">
                        </div>
                        <button class="reset-all-btn" onclick="resetAllFilters()">
                            <i class="fas fa-undo"></i> รีเซ็ต
                        </button>
                        <button class="search-filter-btn" onclick="applyFilters()">
                                <i class="fas fa-search"></i> ค้นหา
                        </button>
                    </div>
                </div>
            </div>

            <!-- PRODUCTS TABLE -->
            <div class="table-container">
                <table id="productsTable">
                    <thead>
                        <tr>
                            <th>รหัสสินค้า</th>
                            <th>ข้อมูลสินค้า</th>
                            <th>หมวดหมู่</th>
                            <th>ล็อต</th>
                            <th>จำนวนเหลือ</th>
                            <th>ราคา</th>
                            <th>วันที่รับเข้า</th>
                            <th>ซัพพลายเออร์</th>
                            <th>การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody id="productsTableBody">
                        <!-- Products inserted here -->
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <div class="pagination" id="pagination">
                <!-- Pagination buttons inserted here -->
            </div>
        </main>
    </div>

    <!-- ======================== -->
    <!-- MODALS                   -->
    <!-- ======================== -->

    <!-- ADD/EDIT PRODUCT MODAL -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle"><i class="fas fa-plus-circle"></i> เพิ่มสินค้าใหม่</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form id="productForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label>หมวดหมู่ *</label>
                        <select id="productCategory" required>
                            <option value="">เลือกหมวดหมู่</option>
                            <option value="rebar">เหล็กเส้น</option>
                            <option value="steelplate">เหล็กแผ่น</option>
                            <option value="structuralsteel">เหล็กรูปพรรณ</option>
                            <option value="wiremesh">เหล็กตะแกรง/ตาข่าย</option>
                            <option value="steelproducts">อื่นๆ</option>
                        </select>
                    </div>
                </div>

                <div class="form-group form-grid-full">
                    <label>ชื่อสินค้า *</label>
                    <input type="text" id="productName" required>
                </div>

                <div class="form-group">
                    <label>คำอธิบาย</label>
                    <textarea id="productDescription" rows="3"></textarea>
                </div>

                <!-- IMAGE UPLOAD SECTION -->
                <div class="images-section" id="imagesSection">
                    <h3><i class="fas fa-images"></i> รูปภาพสินค้า</h3>
                    <div class="drop-zone" id="dropZone">
                        <input type="file" id="imageInput" class="file-input" multiple accept="image/*">
                        <div class="drop-zone-content">
                            <i class="fas fa-cloud-upload-alt drop-zone-icon"></i>
                            <div class="drop-zone-text">ลากและวางรูปภาพที่นี่</div>
                            <div class="drop-zone-subtext">หรือคลิกเพื่อเลือกไฟล์</div>
                            <button type="button" class="upload-button" onclick="document.getElementById('imageInput').click()">
                                <i class="fas fa-plus"></i> เลือกรูปภาพ
                            </button>
                        </div>
                    </div>
                    <div class="image-preview-container" id="imagePreviewContainer"></div>
                </div>

                <!-- DIMENSIONS SECTION -->
                <div class="dimensions-section">
                    <h3><i class="fas fa-ruler-combined"></i> ข้อมูลขนาดและน้ำหนัก</h3>
                    <div class="dimension-row">
                        <div class="form-group">
                            <label>ความกว้าง</label>
                            <input type="number" id="productWidth" step="0.01" min="0" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label>หน่วย</label>
                            <select id="widthUnit">
                                <option value="mm">มม.</option>
                                <option value="cm">ซม.</option>
                                <option value="m">ม.</option>
                                <option value="inch">นิ้ว</option>
                            </select>
                        </div>
                    </div>

                    <div class="dimension-row">
                        <div class="form-group">
                            <label>ความยาว</label>
                            <input type="number" id="productLength" step="0.01" min="0" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label>หน่วย</label>
                            <select id="lengthUnit">
                                <option value="mm">มม.</option>
                                <option value="cm">ซม.</option>
                                <option value="m">ม.</option>
                                <option value="inch">นิ้ว</option>
                            </select>
                        </div>
                    </div>

                    <div class="dimension-row">
                        <div class="form-group">
                            <label>ส่วนสูง/ความหนา</label>
                            <input type="number" id="productHeight" step="0.01" min="0" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label>หน่วย</label>
                            <select id="heightUnit">
                                <option value="mm">มม.</option>
                                <option value="cm">ซม.</option>
                                <option value="m">ม.</option>
                                <option value="inch">นิ้ว</option>
                            </select>
                        </div>
                    </div>

                    <div class="dimension-row">
                        <div class="form-group">
                            <label>น้ำหนัก</label>
                            <input type="number" id="productWeight" step="0.01" min="0" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label>หน่วย</label>
                            <select id="weightUnit">
                                <option value="kg">กก.</option>
                                <option value="g">กรัม</option>
                                <option value="ton">ตัน</option>
                                <option value="lb">ปอนด์</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>ล็อต *</label>
                        <input type="text" id="productLot" required>
                    </div>
                    <div class="form-group">
                        <label>จำนวนเหลือ *</label>
                        <input type="number" id="productStock" required min="0">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>ราคาต่อหน่วย (บาท)</label>
                        <input type="number" id="productPrice" step="0.01" min="0">
                    </div>
                    <div class="form-group">
                        <label>วันที่รับเข้า *</label>
                        <input type="date" id="productReceivedDate" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>ซัพพลายเออร์ *</label>
                    <select id="productSupplier" required>
                        <option value="">เลือกซัพพลายเออร์</option>
                        <option value="บจก. โอเชี่ยนซัพพลายเออร์ จำกัด (Ocean Supplier)">บจก. โอเชี่ยนซัพพลายเออร์ จำกัด</option>
                        <option value="Metallic Corporation Limited (MCC / Metallic Steel Center)">Metallic Corporation Limited</option>
                        <option value="Millcon Steel (MILL)">Millcon Steel (MILL)</option>
                        <option value="Navasiam Steel Co., Ltd.">Navasiam Steel Co., Ltd.</option>
                        <option value="กิจไพบูลย์ เมททอล">กิจไพบูลย์ เมททอล</option>
                        <option value="Chuephaibul Steel (เชื้อไพบูลย์ สตีล)">Chuephaibul Steel</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="button" class="cancel-btn" onclick="closeModal()">ยกเลิก</button>
                    <button type="submit" class="save-btn">บันทึก</button>
                </div>
            </form>
        </div>
    </div>

    <!-- PRODUCT VIEW MODAL -->
    <div id="productViewModal" class="modal">
        <div class="modal-content product-view-modal">
            <div class="modal-header">
                <h2><i class="fas fa-eye"></i> รายละเอียดสินค้า</h2>
                <button class="close-btn" onclick="closeViewModal()">&times;</button>
            </div>

            <div class="product-view-content">
                <div class="product-view-header">
                    <div class="product-view-code">
                        <i class="fas fa-barcode"></i>
                        <span id="viewProductCode">-</span>
                    </div>
                    <span id="viewProductCategory" class="category-badge">-</span>
                </div>

                <div class="product-view-main">
                    <div class="product-view-images">
                        <div class="main-image-container" id="viewMainImageContainer">
                            <div class="no-image-placeholder">
                                <i class="fas fa-image"></i>
                                <span>ไม่มีรูปภาพ</span>
                            </div>
                        </div>
                        <div class="thumbnail-gallery" id="viewThumbnailGallery"></div>
                    </div>

                    <div class="product-view-details">
                        <h3 id="viewProductName">-</h3>
                        <p id="viewProductDescription" class="product-description-text">-</p>

                        <div class="product-view-specs">
                            <div class="spec-item">
                                <label><i class="fas fa-layer-group"></i> ลัก:</label>
                                <span id="viewProductLot" class="lot-value">-</span>
                            </div>
                            <div class="spec-item">
                                <label><i class="fas fa-warehouse"></i> จำนวนเหลือ:</label>
                                <span id="viewProductStock" class="stock-value">-</span>
                            </div>
                            <div class="spec-item">
                                <label><i class="fas fa-tag"></i> ราคา:</label>
                                <span id="viewProductPrice" class="price-value">-</span>
                            </div>
                            <div class="spec-item">
                                <label><i class="fas fa-calendar-alt"></i> วันที่รับเข้า:</label>
                                <span id="viewProductDate" class="date-value">-</span>
                            </div>
                            <div class="spec-item">
                                <label><i class="fas fa-truck"></i> ซัพพลายเออร์:</label>
                                <span id="viewProductSupplier" class="supplier-value">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="product-view-dimensions">
                    <h4><i class="fas fa-ruler-combined"></i> ข้อมูลขนาดและน้ำหนัก</h4>
                    <div class="dimensions-grid" id="viewDimensionsGrid"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- IMAGE GALLERY MODAL -->
    <div id="imageGalleryModal" class="image-gallery-modal">
        <div class="gallery-content">
            <button class="gallery-close-btn" onclick="closeImageGallery()">&times;</button>
            <img id="galleryImage" class="gallery-image" src="" alt="">
            <div class="gallery-controls">
                <button class="gallery-nav-btn" id="prevImageBtn" onclick="navigateImage(-1)">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <div class="gallery-info">
                    <span id="currentImageIndex">1</span> / <span id="totalImages">1</span>
                </div>
                <button class="gallery-nav-btn" id="nextImageBtn" onclick="navigateImage(1)">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- ======================== -->
    <!-- SCRIPTS                  -->
    <!-- ======================== -->
    <script src="sidebar_admin.js"></script>
    <script src="products_admin.js"></script>

</body>

</html>