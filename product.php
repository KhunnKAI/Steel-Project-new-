<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สินค้า - ช่างเหล็กไทย</title>
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

        .main-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }

        .breadcrumb {
            margin-bottom: 20px;
            font-size: 14px;
            color: #666;
            padding: 10px 0;
        }

        .breadcrumb a {
            color: #2c3e50;
            text-decoration: none;
            font-weight: 500;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .product-container {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }

        .product-images {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .main-image {
            width: 100%;
            height: 400px;
            border: 2px solid #ddd;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f9f9f9;
            overflow: hidden;
        }

        .main-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
        }

        .thumbnail-images {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .thumbnail {
            width: 80px;
            height: 80px;
            border: 2px solid #ddd;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .thumbnail:hover {
            border-color: #3498db;
            transform: scale(1.05);
        }

        .thumbnail.active {
            border-color: #2980b9;
            box-shadow: 0 0 10px rgba(52, 152, 219, 0.3);
        }

        .thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 3px;
        }

        .product-info {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .product-title {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
            line-height: 1.2;
        }

        .product-specs {
            font-size: 16px;
            color: #666;
        }

        .product-description {
            font-size: 12px;
            color: #666;
            margin-bottom: 20px;
        }

        
        .product-stock {
            font-size: 14px;
            color: #666;
        }

        .product-price {
            font-size: 28px;
            font-weight: bold;
            color: #c41e3a;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            flex: 1;
            transition: all 0.3s;
        }

        .btn-primary {
            background-color: #2c3e50;
            color: white;
        }

        .btn-primary:hover {
            background-color: #1a252f;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .btn-secondary {
            background-color: #bdc3c7;
            color: #2c3e50;
        }

        .btn-secondary:hover {
            background-color: #95a5a6;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .btn-contact {
            background-color: #27ae60;
            color: white;
        }

        .btn-contact:hover {
            background-color: #229954;
        }

        /* Loading Animation */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #2c3e50;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        /* Error/Success Messages */
        .message {
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .message.error {
            background-color: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .message.success {
            background-color: #efe;
            color: #363;
            border: 1px solid #cfc;
        }

        /* Placeholder styles */
        .product-image-placeholder, .thumbnail-placeholder {
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 14px;
            text-align: center;
        }

        .product-image-placeholder {
            width: 100%;
            height: 100%;
            min-height: 400px;
            border-radius: 8px;
            font-size: 18px;
        }

        .thumbnail-placeholder {
            width: 100%;
            height: 100%;
            min-height: 80px;
            border-radius: 3px;
            font-size: 24px;
        }

        @media (max-width: 768px) {
            .product-container {
                grid-template-columns: 1fr;
                gap: 30px;
                padding: 20px;
            }
            
            .product-title {
                font-size: 24px;
            }
            
            .product-specs {
                font-size: 16px;
            }
            
            .product-price {
                font-size: 24px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .main-image {
                height: 300px;
            }

            .thumbnail-images {
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .main-container {
                padding: 0 10px;
            }

            .product-container {
                padding: 15px;
            }

            .product-title {
                font-size: 20px;
            }

            .main-image {
                height: 250px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include("header.php");?>

    <div class="main-container">
        <div class="breadcrumb">
            <a href="allproduct.php">กลับไปหน้าสินค้า</a>
        </div>

        <div class="product-container">
            <div class="product-images">
                <div class="main-image">
                    <!-- JavaScript will populate this -->
                </div>
                <div class="thumbnail-images">
                    <!-- JavaScript will populate this -->
                </div>
            </div>

            <div class="product-info">
                <h1 class="product-title">กำลังโหลด...</h1>
                <div class="product-specs">กำลังโหลดข้อมูล...</div>
                <div class="product-description">กำลังโหลดคำอธิบาย...</div>
                <div class="product-stock"></div>
                <div class="product-price">฿0.00 บาท/เส้น</div>
                <div class="action-buttons">
                    <button class="btn btn-secondary">เพิ่มใส่ตะกร้า</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include("footer.php");?>

    <script src="cart.js"></script>
    <script src="product.js"></script>

</body>
</html>