<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตะกร้าสินค้า</title>
    <link href="header.css" rel="stylesheet">
    <link href="footer.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter';
            background-color: #f5f5f5;
        }

        .main-content {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }

        .cart-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .cart-title {
            font-size: 24px;
            margin-bottom: 30px;
            color: #333;
        }

        .cart-item {
            display: flex;
            align-items: center;
            padding: 20px;
            border: 1px solid #eee;
            border-radius: 10px;
            margin-bottom: 15px;
            background: #fafafa;
        }

        .item-image {
            width: 80px;
            height: 80px;
            background: #ddd;
            border-radius: 8px;
            margin-right: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #888;
            font-size: 12px;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .item-desc {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .item-price {
            color: #27ae60;
            font-weight: bold;
            font-size: 16px;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0 20px;
        }

        .qty-btn {
            width: 35px;
            height: 35px;
            border: none;
            background: #2c3e50;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qty-btn:hover {
            background: #34495e;
        }

        .qty-input {
            width: 50px;
            text-align: center;
            border: 1px solid #ddd;
            padding: 8px;
            border-radius: 5px;
        }

        .item-total {
            color: #27ae60;
            font-weight: bold;
            font-size: 18px;
            margin: 0 20px;
        }

        .delete-btn {
            background: none;
            border: none;
            color: #e74c3c;
            cursor: pointer;
            font-size: 16px;
            padding: 10px;
        }

        .delete-btn:hover {
            color: #c0392b;
        }

        .summary-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: fit-content;
        }

        .summary-title {
            font-size: 20px;
            margin-bottom: 20px;
            color: #333;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
        }

        .summary-row.total {
            border-top: 2px solid #eee;
            padding-top: 20px;
            font-size: 18px;
            font-weight: bold;
        }

        .discount {
            color: #27ae60;
        }

        .checkout-btn {
            width: 100%;
            background: #c0392b;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
        }

        .checkout-btn:hover {
            background: #a93226;
        }

        /* Loading state */
        .loading-cart {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 2s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
                padding: 0 15px;
            }
            
            .cart-item {
                flex-direction: column;
                text-align: center;
            }
            
            .item-image {
                margin-right: 0;
                margin-bottom: 15px;
            }
            
            .quantity-controls {
                margin: 15px 0;
            }
        }
    </style>
</head>
<!-- ... HTML ก่อนหน้า ... -->

<body>
    <!-- Header -->
    <?php include("header.php");?>

    <main class="main-content">
        <section class="cart-section">
            <h1 class="cart-title">ตะกร้าสินค้า</h1>
            <!-- Loading state จนกว่า CartManager จะพร้อม -->
            <div class="loading-cart" id="cartLoading">
                <div class="spinner"></div>
                <p>กำลังโหลดตะกร้าสินค้า...</p>
            </div>
        </section>

        <aside class="summary-section" style="display: none;">
            <!-- Summary content will be loaded by CartManager -->
        </aside>
    </main>

    <!-- Footer -->
    <?php include("footer.php");?>

    <!-- ส่ง session user_id ไป JS -->
    <script>
        window.SESSION_USER_ID = '<?php echo $_SESSION["user_id"] ?? ""; ?>';
    </script>

    <script src="cart.js"></script>
</body>
</html>
