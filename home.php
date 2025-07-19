<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ช้างเหล็กไทย - สินค้า</title>
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
            background: #f8f9fa;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #1e3a5f 0%, #2c5477 100%);
            color: white;
            padding: 60px 0;
            text-align: center;
        }

        .hero-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .logo-page {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            margin: 0 auto 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }

        .hero-title {
            font-size: 42px;
            font-weight: bold;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .hero-subtitle {
            font-size: 16px;
            opacity: 0.9;
        }

        /* Main Content */
        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .video-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        /* Responsive YouTube Video Container */
        .youtube-container {
            position: relative;
            width: 100%;
            height: 0;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            margin-bottom: 20px;
        }
        
        .youtube-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 8px;
        }
        
        /* Fixed size YouTube video */
        .youtube-fixed {
            width: 100%;
            max-width: 800px;
            height: 450px;
            display: block;
            margin: 0 auto 20px;
            border-radius: 8px;
        }
        
        /* Small YouTube video */
        .youtube-small {
            width: 100%;
            max-width: 480px;
            height: 270px;
            display: block;
            margin: 0 auto 20px;
            border-radius: 8px;
        }
        
        .content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .how-to {
            background: #e8f4f8;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }
        
        .code-block {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            margin: 10px 0;
            overflow-x: auto;
        }
        
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .grid-item {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        /* Products Section */
        .products-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .products-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .products-title {
            font-size: 24px;
            font-weight: 600;
            color: #333;
        }

        .search-container {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .search-input {
            padding: 8px 12px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 14px;
            width: 200px;
        }

        .search-input:focus {
            outline: none;
            border-color: #2c5477;
        }

        .search-btn {
            padding: 8px 12px;
            background: #2c5477;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        .search-btn:hover {
            background: #1e3a5f;
        }

        .filter-btn {
            padding: 8px 12px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        .filter-btn:hover {
            background: #5a6268;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .product-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
        }

        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .product-image {
            width: 100%;
            height: 150px;
            background: #f8f9fa;
            border-radius: 6px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 14px;
        }

        .product-name {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .product-price {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 10px;
        }

        .product-btn {
            width: 100%;
            padding: 8px;
            background: #2c5477;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        .product-btn:hover {
            background: #1e3a5f;
        }

        @media (max-width: 768px) {
            .header-container {
                padding: 0 15px;
            }

            .nav-links {
                display: none;
            }

            .hero-title {
                font-size: 32px;
            }

            .main-content {
                padding: 20px 15px;
            }

            .products-header {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }

            .search-container {
                flex-direction: column;
                gap: 10px;
            }

            .search-input {
                width: 100%;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include("header.php");?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-container">
            <div class="logo-page">
                <img src="image/logo.png" width="300px">
            </div>
            <h1 class="hero-title">ช้างเหล็กไทย</h1>
            <p class="hero-subtitle">เหล็กคุณภาพ แกร่งทุกงาน มั่นใจช้างเหล็กไทย</p>
        </div>
    </section>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Video Section -->
        <section class="video-section">
            <div class="youtube-container">
                <iframe 
                    src="https://www.youtube.com/embed/dQw4w9WgXcQ" 
                    title="YouTube video player" 
                    frameborder="0" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                    allowfullscreen>
                </iframe>
            </div>
        </section>

        <!-- Products Section -->
        <section class="products-section">
            <div class="products-header">
                <h2 class="products-title">สินค้า</h2>
                <div class="search-container">
                    <input type="text" class="search-input" placeholder="ค้นหาสินค้า...">
                    <button class="search-btn">🔍</button>
                    <button class="filter-btn">ตัวกรอง</button>
                </div>
            </div>
            
            <div class="products-grid">
                <div class="product-card">
                    <div class="product-image">ช้างเหล็ก รุ่น A</div>
                    <div class="product-name">ช้างเหล็ก รุ่น A</div>
                    <div class="product-price">฿2,500</div>
                    <button class="product-btn">เพิ่มในตะกร้า</button>
                </div>
                
                <div class="product-card">
                    <div class="product-image">ช้างเหล็ก รุ่น B</div>
                    <div class="product-name">ช้างเหล็ก รุ่น B</div>
                    <div class="product-price">฿3,200</div>
                    <button class="product-btn">เพิ่มในตะกร้า</button>
                </div>
                
                <div class="product-card">
                    <div class="product-image">ช้างเหล็ก รุ่น C</div>
                    <div class="product-name">ช้างเหล็ก รุ่น C</div>
                    <div class="product-price">฿2,800</div>
                    <button class="product-btn">เพิ่มในตะกร้า</button>
                </div>
                
                <div class="product-card">
                    <div class="product-image">ช้างเหล็ก รุ่น D</div>
                    <div class="product-name">ช้างเหล็ก รุ่น D</div>
                    <div class="product-price">฿4,100</div>
                    <button class="product-btn">เพิ่มในตะกร้า</button>
                </div>
                
                <div class="product-card">
                    <div class="product-image">ช้างเหล็ก รุ่น E</div>
                    <div class="product-name">ช้างเหล็ก รุ่น E</div>
                    <div class="product-price">฿3,600</div>
                    <button class="product-btn">เพิ่มในตะกร้า</button>
                </div>
                
                <div class="product-card">
                    <div class="product-image">ช้างเหล็ก รุ่น F</div>
                    <div class="product-name">ช้างเหล็ก รุ่น F</div>
                    <div class="product-price">฿2,900</div>
                    <button class="product-btn">เพิ่มในตะกร้า</button>
                </div>
            </div>
        </section>
    </main>

    <script>
        function playVideo() {
            alert('เปิดวิดีโอ "วิธีการสั่งซื้อ"');
        }

        // Search functionality
        document.querySelector('.search-btn').addEventListener('click', function() {
            const searchTerm = document.querySelector('.search-input').value;
            if (searchTerm) {
                alert(`ค้นหา: ${searchTerm}`);
            }
        });

        // Filter functionality
        document.querySelector('.filter-btn').addEventListener('click', function() {
            alert('เปิดตัวกรองสินค้า');
        });

        // Add to cart functionality
        document.querySelectorAll('.product-btn').forEach(button => {
            button.addEventListener('click', function() {
                const productName = this.parentElement.querySelector('.product-name').textContent;
                alert(`เพิ่ม "${productName}" ลงในตะกร้าแล้ว`);
            });
        });
    </script>

    <!-- Footer -->
    <?php include("footer.php");?>
</body>
</html>