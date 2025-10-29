<?php
// Remove session_start() from here since config.php handles it properly
require_once 'controllers/config.php';

// Require login to access this page
requireLogin();

// Get current admin information
$current_admin = getCurrentAdmin();
if (!$current_admin) {
    // If admin not found in database, logout
    header("Location: controllers/logout.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการคำสั่งซื้อ - ระบบจัดการร้านค้า</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="sidebar_admin.css">
    
    <style>
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
            border: none;
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
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .role-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #990000;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            color: white;
        }

        .print-btn {
            padding: 12px 24px;
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 14px;
            white-space: nowrap;
        }

        .print-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
            background: linear-gradient(45deg, #0056b3, #004085);
        }

        .search-filter {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

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
            font-family: 'Inter';
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

        .filters-section {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }

        .filters-row {
            display: flex;
            gap: 20px;
            align-items: end;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-group label {
            font-size: 14px;
            color: #666;
            font-weight: 500;
        }

        .filter-group select,
        .filter-group input {
            padding: 8px 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            min-width: 150px;
            font-family: 'Inter';
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #990000;
        }

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
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .stat-card.pending-payment::before { background: linear-gradient(90deg, #ff6b35, #f7931e); }
        .stat-card.awaiting-shipment::before { background: linear-gradient(90deg, #2196F3, #03DAC6); }
        .stat-card.in-transit::before { background: linear-gradient(90deg, #ff9800, #ffc107); }
        .stat-card.delivered::before { background: linear-gradient(90deg, #4CAF50, #8BC34A); }
        .stat-card.cancelled::before { background: linear-gradient(90deg, #dc3545, #c82333); }

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

        .table-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .loading-container {
            display: none;
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }

        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #990000;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 8px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        td {
            color: #555;
            font-size: 14px;
        }

        .order-id {
            font-family: 'Inter';
            font-weight: 600;
            color: #007bff;
            font-size: 13px;
            background: #f0f8ff;
            padding: 3px 6px;
            border-radius: 4px;
            display: inline-block;
        }

        .customer-info {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .customer-name {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .customer-phone {
            font-size: 12px;
            color: #666;
        }

        .order-items {
            font-size: 12px;
            color: #666;
            max-width: 200px;
        }

        .item-count {
            background: #e9ecef;
            color: #495057;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
            margin-left: 5px;
        }

        .price-info {
            font-size: 15px;
            font-weight: 700;
            color: #333;
        }

        .status-badge {
            padding: 6px 10px;
            border-radius: 15px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
            min-width: fit-content;
        }

        /* Status badge styles */
        .status-pending-payment { 
            background: #fff3cd; 
            color: #856404;
        }
        .status-pending-payment::before {
            content: '💳';
            font-size: 10px;
        }

        .status-awaiting-shipment { 
            background: #e3f2fd; 
            color: #1976d2;
        }
        .status-awaiting-shipment::before {
            content: '📦';
            font-size: 10px;
        }

        .status-in-transit { 
            background: #fff3e0; 
            color: #f57c00;
        }
        .status-in-transit::before {
            content: '🚛';
            font-size: 10px;
        }

        .status-delivered { 
            background: #d4edda; 
            color: #155724;
        }
        .status-delivered::before {
            content: '✅';
            font-size: 10px;
        }

        .status-cancelled { 
            background: #f8d7da; 
            color: #721c24;
        }
        .status-cancelled::before {
            content: '❌';
            font-size: 10px;
        }

        .status-unknown {
            background: #e9ecef;
            color: #6c757d;
        }

        .date-info {
            font-size: 13px;
            color: #666;
        }

        .actions {
            display: flex;
            gap: 4px;
            align-items: center;
            justify-content: flex-start;
            flex-wrap: wrap;
            width: 180px;
            min-width: 180px;
        }

        .actions .btn {
            padding: 6px 8px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 10px;
            font-weight: 500;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 3px;
            text-decoration: none;
            white-space: nowrap;
            min-height: 28px;
            line-height: 1;
            font-family: 'Inter';
        }

        .actions .btn:hover:not(.btn-disabled) {
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(0,0,0,0.12);
        }

        .actions .btn i {
            font-size: 10px;
        }

        .btn-view {
            background: #17a2b8;
            color: white;
            flex: 1;
            min-width: 50px;
            justify-content: center;
        }

        .btn-view:hover {
            background: #138496;
        }

        .btn-approve {
            background: #28a745;
            color: white;
            flex: 1;
            min-width: 55px;
            justify-content: center;
        }

        .btn-approve:hover:not(.btn-disabled) {
            background: #218838;
        }

        .btn-ship {
            background: #007bff;
            color: white;
            flex: 1;
            min-width: 55px;
            justify-content: center;
        }

        .btn-ship:hover:not(.btn-disabled) {
            background: #0056b3;
        }

        .btn-cancel {
            background: #dc3545;
            color: white;
            flex: 1;
            min-width: 50px;
            justify-content: center;
        }

        .btn-cancel:hover:not(.btn-disabled) {
            background: #c82333;
        }

        .btn-reject {
            background: #fd7e14;
            color: white;
            flex: 1;
            min-width: 55px;
            justify-content: center;
        }

        .btn-reject:hover:not(.btn-disabled) {
            background: #e8680f;
        }

        /* Disabled button styles */
        .btn-disabled {
            opacity: 0.5 !important;
            cursor: not-allowed !important;
            background: #6c757d !important;
            color: white !important;
            pointer-events: none;
            position: relative;
        }

        .btn-disabled:hover {
            transform: none !important;
            box-shadow: none !important;
            background: #6c757d !important;
        }

        .btn-disabled::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.1);
            border-radius: inherit;
        }

        .btn-primary {
            background: #990000;
            color: white;
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: 'Inter';
        }

        .btn-primary:hover {
            background: #770000;
            transform: translateY(-1px);
        }

        .btn-success {
            background: #28a745;
            color: white;
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: 'Inter';
        }

        .btn-success:hover {
            background: #218838;
            transform: translateY(-1px);
        }

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
            font-family: 'Inter';
        }

        .pagination button:hover:not(:disabled),
        .pagination button.active {
            background: #990000;
            color: white;
            border-color: #990000;
        }

        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination-dots {
            padding: 8px 4px;
            color: #666;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            overflow-y: auto;
        }

        .modal-content {
            position: relative;
            background: white;
            margin: 50px auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 800px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
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
            font-size: 24px;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
            transition: color 0.3s ease;
        }

        .close-btn:hover {
            color: #990000;
        }

        .order-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .detail-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
        }

        .detail-section h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            align-items: flex-start;
        }

        .detail-label {
            font-weight: 600;
            color: #666;
            min-width: 100px;
        }

        .detail-value {
            color: #333;
            flex: 1;
            text-align: right;
            word-break: break-word;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .items-table th,
        .items-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .items-table th {
            background: #f8f9fa;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }

        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Inter';
            resize: vertical;
            min-height: 100px;
        }

        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #990000;
        }

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
            transition: all 0.3s ease;
            font-family: 'Inter';
        }

        .save-btn {
            background: #990000;
            color: white;
        }

        .save-btn:hover {
            background: #770000;
        }

        .cancel-form-btn {
            background: #6c757d;
            color: white;
        }

        .cancel-form-btn:hover {
            background: #545b62;
        }

        .payment-section {
            background: #fff9c4;
            border: 2px solid #f57f17;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }

        .payment-section h3 {
            color: #e65100;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .bank-info {
            background: #fff3e0;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #ff9800;
        }

        .bank-info h4 {
            color: #e65100;
            font-size: 16px;
            margin-bottom: 8px;
        }

        .bank-name {
            color: #333;
            font-size: 18px;
            font-weight: 600;
        }

        .slip-preview {
            text-align: center;
            margin: 20px 0;
        }

        .slip-preview h4 {
            color: #333;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .slip-image {
            max-width: 100%;
            max-height: 400px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .slip-image:hover {
            transform: scale(1.02);
        }

        .no-slip-message {
            color: #999;
            font-style: italic;
            padding: 40px;
            border: 2px dashed #ddd;
            border-radius: 8px;
            background: #f9f9f9;
        }

        .verification-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
        }

        .verification-actions button {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: 'Inter';
        }

        .approve-payment-btn {
            background: linear-gradient(45deg, #4caf50, #66bb6a);
            color: white;
        }

        .approve-payment-btn:hover:not(.btn-disabled) {
            background: linear-gradient(45deg, #388e3c, #4caf50);
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.4);
            transform: translateY(-1px);
        }

        .reject-payment-btn {
            background: linear-gradient(45deg, #f44336, #e57373);
            color: white;
        }

        .reject-payment-btn:hover:not(.btn-disabled) {
            background: linear-gradient(45deg, #d32f2f, #f44336);
            box-shadow: 0 4px 12px rgba(244, 67, 54, 0.4);
            transform: translateY(-1px);
        }

        /* Enhanced verification actions for payment section */
        .verification-actions .btn-disabled {
            min-width: 140px;
            justify-content: center;
            opacity: 0.4;
            background: #dee2e6 !important;
            color: #6c757d !important;
            border: 1px solid #ced4da;
            cursor: not-allowed;
        }

        .verification-actions .btn-disabled i {
            color: #adb5bd;
        }

        .lightbox {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 3000;
            cursor: pointer;
        }

        .lightbox img {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            max-width: 90%;
            max-height: 90%;
            border-radius: 10px;
        }

        .lightbox .close {
            position: absolute;
            top: 20px;
            right: 40px;
            color: white;
            font-size: 35px;
            font-weight: bold;
            cursor: pointer;
            transition: opacity 0.3s ease;
        }

        .lightbox .close:hover {
            opacity: 0.7;
        }

        .sidebar {
            background: #940606;
            color: white;
            width: 260px;
            min-width: 260px;
            height: 100%;
            position: fixed;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
        }

        .sidebar.collapsed {
            width: 0;
            min-width: 0;
            overflow: hidden;
        }

        .logo {
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid #940606;
            font-size: 18px;
            font-weight: 600;
            margin-top: 10px;
        }

        nav ul {
            list-style: none;
            padding: 20px 0;
        }

        nav li {
            margin: 5px 0;
            transition: all 0.3s ease;
        }

        nav li a {
            display: flex;
            align-items: center;
            padding: 15px 25px;
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        nav li a i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }

        nav li.active a,
        nav li a:hover {
            background: #051A37;
        }

        .results-info {
            text-align: center; 
            margin-top: 10px; 
            color: #666;
            font-size: 14px;
        }

        .btn-filter-search,
        .btn-filter-reset {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-family: 'Inter';
            font-size: 14px;
            min-height: 36px;
        }

        .btn-filter-search {
            background: linear-gradient(45deg, #990000, #cc0000);
            color: white;
            border: 2px solid #990000;
        }

        .btn-filter-search:hover {
            background: linear-gradient(45deg, #770000, #990000);
            box-shadow: 0 4px 12px rgba(153, 0, 0, 0.3);
            transform: translateY(-2px);
        }

        .btn-filter-search:active {
            transform: translateY(0);
            box-shadow: 0 2px 6px rgba(153, 0, 0, 0.2);
        }

        .btn-filter-reset {
            background: linear-gradient(45deg, #6c757d, #868e96);
            color: white;
            border: 2px solid #6c757d;
        }

        .btn-filter-reset:hover {
            background: linear-gradient(45deg, #5a6268, #6c757d);
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
            transform: translateY(-2px);
        }

        .btn-filter-reset:active {
            transform: translateY(0);
            box-shadow: 0 2px 6px rgba(108, 117, 125, 0.2);
        }

        /* Responsive adjustments */
        @media screen and (max-width: 768px) {
            .filters-row {
                flex-direction: column;
                gap: 10px;
            }
            
            .filter-group {
                width: 100%;
            }
            
            .filter-group select,
            .filter-group input {
                width: 100%;
            }
            
            .btn-filter-search,
            .btn-filter-reset {
                width: 100%;
                justify-content: center;
            }
        }

        /* Notification styles */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            z-index: 3000;
            max-width: 350px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            font-family: 'Inter';
            cursor: pointer;
        }

        .notification.success {
            background: #28a745;
        }

        .notification.error {
            background: #dc3545;
        }

        .notification.info {
            background: #17a2b8;
        }

        .notification.warning {
            background: #ffc107;
            color: #856404;
        }

        /* Notes Modal Styles */
        .notes-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 2100;
        }

        .notes-modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .notes-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }

        .notes-modal-header h3 {
            color: #333;
            font-size: 20px;
            margin: 0;
        }

        .notes-modal-message {
            color: #666;
            margin-bottom: 20px;
            font-size: 14px;
        }

        /* Responsive Design */
        @media screen and (max-width: 1200px) {
            .actions {
                width: auto;
                min-width: auto;
            }
            
            .actions .btn {
                min-width: 35px;
                padding: 4px 6px;
            }
        }

        @media screen and (max-width: 768px) {
            .main-content {
                margin-left: 0 !important;
                padding: 80px 15px 30px 15px !important;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }
            
            .search-container input[type="text"] {
                width: 100%;
            }
            
            .order-details {
                grid-template-columns: 1fr;
            }
        }

        /* Print Styles */
        @media print {
            .sidebar,
            .navbar-toggle,
            .search-filter,
            .filters-section,
            .pagination,
            .actions {
                display: none !important;
            }

            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
            }

            .header {
                box-shadow: none !important;
            }
        }
    </style>
</head>

<body>
    <div class="navbar-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </div>

    <div class="container">
        <?php include 'sidebar_admin.php'; ?>

        <main class="main-content">
            <div class="header">
                <div>
                    <h1>
                        <i class="fas fa-shopping-cart" aria-hidden="true"></i> 
                        จัดการคำสั่งซื้อ
                        <div class="role-indicator">
                            <i class="fas fa-user-shield"></i>
                            <?php echo htmlspecialchars($current_admin['fullname'] . ' (' . $current_admin['position'] . ')'); ?>
                        </div>
                    </h1>
                </div>
                <button class="print-btn" onclick="window.location.href='printorders_admin.php'">
                        <i class="fas fa-print"></i> พิมพ์รายละเอียดคำสั่งซื้อ
                    </button>
                <div class="search-filter">
                    <div class="search-container">
                        <input type="text" id="searchInput" placeholder="ค้นหารหัสคำสั่งซื้อ, ชื่อลูกค้า..." autocomplete="off">
                        <i class="fas fa-search" aria-hidden="true"></i>
                    </div>
                </div>
            </div>

            <!-- Loading Indicator -->
            <div class="loading-container" id="loadingIndicator" aria-live="polite">
                <div class="loading-spinner" role="status" aria-label="Loading"></div>
                <div>กำลังโหลดข้อมูล...</div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-row">
                <div class="stat-card pending-payment">
                    <div class="stat-number" id="pendingPaymentOrders">0</div>
                    <div class="stat-label">รอตรวจสอบการการชำระเงิน</div>
                </div>
                <div class="stat-card awaiting-shipment">
                    <div class="stat-number" id="awaitingShipmentOrders">0</div>
                    <div class="stat-label">รอจัดส่ง</div>
                </div>
                <div class="stat-card in-transit">
                    <div class="stat-number" id="inTransitOrders">0</div>
                    <div class="stat-label">กำลังจัดส่ง</div>
                </div>
                <div class="stat-card delivered">
                    <div class="stat-number" id="deliveredOrders">0</div>
                    <div class="stat-label">จัดส่งแล้ว</div>
                </div>
                <div class="stat-card cancelled">
                    <div class="stat-number" id="cancelledOrders">0</div>
                    <div class="stat-label">ยกเลิก</div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="filters-section">
                <div class="filters-row">
                    <div class="filter-group">
                        <label for="statusFilter">สถานะ</label>
                        <select id="statusFilter">
                            <option value="">ทั้งหมด</option>
                            <option value="pending_payment">รอการชำระเงิน</option>
                            <option value="awaiting_shipment">รอจัดส่ง</option>
                            <option value="in_transit">กำลังจัดส่ง</option>
                            <option value="delivered">จัดส่งแล้ว</option>
                            <option value="cancelled">ยกเลิก</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="dateFromFilter">ตั้งแต่วันที่</label>
                        <input type="date" id="dateFromFilter">
                    </div>
                    <div class="filter-group">
                        <label for="dateToFilter">ถึงวันที่</label>
                        <input type="date" id="dateToFilter">
                    </div>
                    <div style="display: flex; gap: 10px; align-items: flex-end;">
                        <button id="searchBtn" class="btn-filter-search" type="button">
                            <i class="fas fa-search"></i> ค้นหา
                        </button>
                        <button id="resetBtn" class="btn-filter-reset" type="button">
                            <i class="fas fa-redo"></i> รีเซ็ต
                        </button>
                    </div>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="table-container">
                <table id="ordersTable">
                    <thead>
                        <tr>
                            <th style="width: 120px;">รหัสคำสั่งซื้อ</th>
                            <th style="width: 160px;">ลูกค้า</th>
                            <th style="width: 220px;">รายการสินค้า</th>
                            <th style="width: 100px;">ยอดรวม</th>
                            <th style="width: 120px;">สถานะ</th>
                            <th style="width: 100px;">วันที่สั่งซื้อ</th>
                            <th style="width: 180px;">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody id="ordersTableBody">
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #666;">
                                กำลังโหลดข้อมูล...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="pagination" id="pagination" role="navigation" aria-label="Pagination">
                <!-- Pagination buttons will be inserted here -->
            </div>

            <!-- Results Info -->
            <div id="resultsInfo" class="results-info" aria-live="polite"></div>
        </main>
    </div>

    <!-- Order Details Modal -->
    <div id="orderDetailsModal" class="modal" role="dialog" aria-labelledby="orderDetailsTitle" aria-hidden="true">
        <div class="modal-content" role="document">
            <div class="modal-header">
                <h2 id="orderDetailsTitle">รายละเอียดคำสั่งซื้อ</h2>
                <button class="close-btn" onclick="closeOrderDetailsModal()" type="button" aria-label="Close">&times;</button>
            </div>
            
            <div class="order-details">
                <div class="detail-section">
                    <h3><i class="fas fa-user" aria-hidden="true"></i> ข้อมูลลูกค้า</h3>
                    <div class="detail-row">
                        <span class="detail-label">ชื่อ:</span>
                        <span class="detail-value" id="detailCustomerName">-</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">เบอร์โทร:</span>
                        <span class="detail-value" id="detailCustomerPhone">-</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">อีเมล:</span>
                        <span class="detail-value" id="detailCustomerEmail">-</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">ที่อยู่:</span>
                        <span class="detail-value" id="detailCustomerAddress">-</span>
                    </div>
                </div>
                
                <div class="detail-section">
                    <h3><i class="fas fa-shopping-cart" aria-hidden="true"></i> ข้อมูลคำสั่งซื้อ</h3>
                    <div class="detail-row">
                        <span class="detail-label">รหัสคำสั่งซื้อ:</span>
                        <span class="detail-value" id="detailOrderId">-</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">วันที่สั่งซื้อ:</span>
                        <span class="detail-value" id="detailOrderDate">-</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">สถานะ:</span>
                        <span class="detail-value" id="detailOrderStatus">-</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">หมายเหตุ:</span>
                        <span class="detail-value" id="detailOrderNotes">-</span>
                    </div>
                </div>
            </div>

            <!-- Payment Verification Section -->
            <div id="paymentSection" class="payment-section" style="display: none;">
                <h3>
                    <i class="fas fa-credit-card" aria-hidden="true"></i>
                    การตรวจสอบสลิปโอนเงิน
                </h3>
            
                <div class="slip-preview">
                    <h4>รูปภาพสลิปการโอนเงิน</h4>
                    <img id="paymentSlip" class="slip-image" src="" alt="Payment Slip" onclick="openLightbox(this.src)" style="display: none;">
                    <div id="noSlipMessage" class="no-slip-message" style="display: none;">
                        <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                        ลูกค้ายังไม่ได้แนบรูปสลิป
                    </div>
                </div>

                <div class="verification-actions">
                    <!-- Actions will be populated by JavaScript based on permissions -->
                </div>
            </div>

            <!-- Order Items Section -->
            <div class="detail-section">
                <h3><i class="fas fa-list" aria-hidden="true"></i> รายการสินค้า</h3>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>สินค้า</th>
                            <th>จำนวน</th>
                            <th>ราคาต่อหน่วย</th>
                            <th>รวม</th>
                        </tr>
                    </thead>
                    <tbody id="orderItemsList">
                        <tr>
                            <td colspan="4" style="text-align: center; color: #666;">ไม่มีรายการสินค้า</td>
                        </tr>
                    </tbody>
                </table>
                
                <div style="margin-top: 20px; text-align: right;">
                    <div style="font-size: 18px; font-weight: 700; color: #333;" id="detailOrderTotal">
                        ยอดรวมทั้งหมด: ฿0
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notes Modal -->
    <div id="notesModal" class="modal notes-modal" role="dialog" aria-labelledby="notesModalTitle" aria-hidden="true">
        <div class="notes-modal-content" role="document">
            <div class="notes-modal-header">
                <h3 id="notesModalTitle">กรอกหมายเหตุ</h3>
                <button class="close-btn" onclick="closeNotesModal()" type="button" aria-label="Close">&times;</button>
            </div>
            
            <div class="notes-modal-message" id="notesModalMessage">
                กรุณาระบุเหตุผล
            </div>
            
            <div class="form-group">
                <label for="notesTextarea">หมายเหตุ:</label>
                <textarea id="notesTextarea"></textarea>
            </div>
            
            <div class="form-actions">
                <button type="button" class="save-btn" onclick="submitNotes()">ยืนยัน</button>
                <button type="button" class="cancel-form-btn" onclick="cancelNotes()">ยกเลิก</button>
            </div>
        </div>
    </div>

    <!-- Image Lightbox -->
    <div id="lightbox" class="lightbox" onclick="closeLightbox()" role="dialog" aria-labelledby="lightboxTitle" aria-hidden="true">
        <span class="close" onclick="closeLightbox()" aria-label="Close">&times;</span>
        <img id="lightboxImage" src="" alt="Payment Slip" loading="lazy">
    </div>

    <!-- JavaScript -->
    <script src="sidebar_admin.js"></script>
    <script src="orders_admin.js?v=<?php echo time(); ?>"></script>
</body>
</html>