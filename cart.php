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
            font-family: Arial, sans-serif;
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
            background: #2c3e50;
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
            color: #2c3e50;
            cursor: pointer;
            font-size: 20px;
            padding: 10px;
        }

        .delete-btn:hover {
            color: #2c3e50;
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
            background: #c0392b;
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
<body>
    <!-- Header -->
    <?php include("header.php");?>

    <main class="main-content">
        <section class="cart-section">
            <h1 class="cart-title">ตะกร้าสินค้า</h1>
            
            <div class="cart-item">
                <div class="item-image">ภาพสินค้า</div>
                <div class="item-details">
                    <div class="item-name">ยี่ห้องเก่า</div>
                    <div class="item-desc">รายละเอียด</div>
                    <div class="item-price">฿199</div>
                </div>
                <div class="quantity-controls">
                    <button class="qty-btn" onclick="decreaseQty(1)">-</button>
                    <input type="number" value="1" min="1" class="qty-input" id="qty1" onchange="updateTotal(1)">
                    <button class="qty-btn" onclick="increaseQty(1)">+</button>
                </div>
                <div class="item-total" id="total1">฿199</div>
                <button class="delete-btn" onclick="removeItem(this)">ลบ</button>
            </div>

            <div class="cart-item">
                <div class="item-image">ภาพสินค้า</div>
                <div class="item-details">
                    <div class="item-name">ยี่ห้องเก่า</div>
                    <div class="item-desc">รายละเอียด</div>
                    <div class="item-price">฿199</div>
                </div>
                <div class="quantity-controls">
                    <button class="qty-btn" onclick="decreaseQty(2)">-</button>
                    <input type="number" value="2" min="1" class="qty-input" id="qty2" onchange="updateTotal(2)">
                    <button class="qty-btn" onclick="increaseQty(2)">+</button>
                </div>
                <div class="item-total" id="total2">฿398</div>
                <button class="delete-btn" onclick="removeItem(this)">ลบ</button>
            </div>
        </section>

        <aside class="summary-section">
            <h2 class="summary-title">สรุปยอด</h2>
            
            <div class="summary-row">
                <span>3 รายการ</span>
                <span id="subtotal">฿597</span>
            </div>
            
            <div class="summary-row">
                <span>ค่าจัดส่ง</span>
                <span>฿40</span>
            </div>
            
            <div class="summary-row discount">
                <span>ยอดรวม</span>
                <span id="discount-total">฿637</span>
            </div>
            
            <div class="summary-row total">
                <span>ชำระเงิน</span>
                <span id="final-total">฿637</span>
            </div>
            
            <button class="checkout-btn">ชำระเงิน</button>
        </aside>
    </main>

    <!-- Footer -->
    <?php include("footer.php");?>

    <script src="cart.js"></script>
</body>
</html>