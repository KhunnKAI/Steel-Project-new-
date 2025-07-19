<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เหล็กเส้นกลม RBxx</title>
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
        }

        .breadcrumb a {
            color: #666;
            text-decoration: none;
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
            height: 300px;
            border: 2px solid #ddd;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f9f9f9;
        }

        .rebar-image {
            width: 200px;
            height: 150px;
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 50%, #2c3e50 100%);
            border-radius: 10px;
            position: relative;
            overflow: hidden;
        }

        .rebar-image::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 20px;
            right: 20px;
            bottom: 20px;
            background: repeating-linear-gradient(
                90deg,
                #2c3e50 0px,
                #2c3e50 8px,
                #34495e 8px,
                #34495e 12px
            );
            border-radius: 5px;
        }

        .rebar-image::after {
            content: '';
            position: absolute;
            top: 30px;
            left: 30px;
            right: 30px;
            bottom: 30px;
            background: repeating-linear-gradient(
                0deg,
                #2c3e50 0px,
                #2c3e50 3px,
                #34495e 3px,
                #34495e 6px
            );
            border-radius: 3px;
        }

        .thumbnail-images {
            display: flex;
            gap: 10px;
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
            transition: border-color 0.3s;
        }

        .thumbnail:hover {
            border-color: #c41e3a;
        }

        .thumbnail.active {
            border-color: #c41e3a;
        }

        .thumbnail-rebar {
            width: 50px;
            height: 30px;
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 50%, #2c3e50 100%);
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
        }

        .product-specs {
            font-size: 18px;
            color: #666;
            margin-bottom: 10px;
        }

        .product-description {
            font-size: 16px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .product-code {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }

        .product-price {
            font-size: 28px;
            font-weight: bold;
            color: #c41e3a;
            margin-bottom: 30px;
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
        }

        .btn-secondary {
            background-color: #bdc3c7;
            color: #2c3e50;
        }

        .btn-secondary:hover {
            background-color: #95a5a6;
        }

        @media (max-width: 768px) {
            .product-container {
                grid-template-columns: 1fr;
                gap: 30px;
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
            < <a href="#">กลับไปหน้าหลัก</a>
        </div>

        <div class="product-container">
            <div class="product-images">
                <div class="main-image">
                    <div class="rebar-image"></div>
                </div>
                <div class="thumbnail-images">
                    <div class="thumbnail active">
                        <div class="thumbnail-rebar"></div>
                    </div>
                    <div class="thumbnail">
                        <div class="thumbnail-rebar"></div>
                    </div>
                    <div class="thumbnail">
                        <div class="thumbnail-rebar"></div>
                    </div>
                </div>
            </div>

            <div class="product-info">
                <h1 class="product-title">เหล็กเส้นกลม RBxx</h1>
                <div class="product-specs">00 มม. x 00 ม. 0.00 กก.</div>
                <div class="product-description">
                    เหล็กเส้นกลมสมรรถนะสูง มอก. ทนทานต่อแรงดึงและแรงกดที่สูงใส่ไว้
                </div>
                <div class="product-code">
                    <strong>รหัสสินค้า:</strong> 00
                </div>
                <div class="product-price">000.00 บาท/เส้น</div>
                <div class="action-buttons">
                    <button class="btn btn-primary">ซื้อ</button>
                    <button class="btn btn-secondary">ใส่ในตะกร้า</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Thumbnail click functionality
        const thumbnails = document.querySelectorAll('.thumbnail');
        
        thumbnails.forEach(thumbnail => {
            thumbnail.addEventListener('click', function() {
                // Remove active class from all thumbnails
                thumbnails.forEach(t => t.classList.remove('active'));
                // Add active class to clicked thumbnail
                this.classList.add('active');
            });
        });
    </script>

    <!-- Footer -->
    <?php include("footer.php");?>
</body>
</html>