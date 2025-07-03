<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ช้างเหล็กไทย - สินค้า</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            line-height: 1.6;
        }

        /* Header Styles */
        .header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 12px 0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .home-icon {
            width: 24px;
            height: 24px;
            background: white;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .home-icon:hover {
            background: #f8f9fa;
        }

        .nav-links {
            display: flex;
            gap: 20px;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.3s ease;
        }

        .nav-links a:hover {
            opacity: 0.8;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-icon, .cart-icon, .menu-icon {
            width: 32px;
            height: 32px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .user-icon:hover, .cart-icon:hover, .menu-icon:hover {
            background: rgba(255,255,255,0.3);
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

        .logo {
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

        .elephant {
            width: 50px;
            height: 50px;
            background: #2c5477;
            position: relative;
        }

        .elephant::before {
            content: '';
            position: absolute;
            top: -8px;
            right: -12px;
            width: 20px;
            height: 20px;
            background: #dc3545;
            border-radius: 50%;
            box-shadow: 
                8px 0 0 #dc3545,
                4px -6px 0 #dc3545,
                12px -6px 0 #dc3545,
                -2px -4px 0 #dc3545;
        }

        .elephant-shape {
            clip-path: polygon(
                20% 10%, 35% 0%, 50% 10%, 65% 0%, 80% 15%,
                90% 30%, 85% 50%, 75% 70%, 60% 85%, 40% 90%,
                25% 85%, 15% 70%, 10% 50%, 15% 30%
            );
            width: 100%;
            height: 100%;
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

        /* Video Section */
        .video-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .video-title {
            font-size: 24px;
            font-weight: 600;
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        .video-container {
            position: relative;
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            aspect-ratio: 16/9;
            background: #e9ecef;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .video-container:hover {
            background: #dee2e6;
        }

        .play-button {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #333;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: transform 0.3s ease;
        }

        .play-button:hover {
            transform: scale(1.1);
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
    <header class="header">
        <div class="header-container">
            <div class="header-left">
                <div class="home-icon">
                    <img src="image/logo.png" width="70px">
                </div>
                <nav class="nav-links">
                    <a href="http://localhost/NewProject/home.php#">หน้าแรก</a>
                    <a href="#">สินค้า</a>
                    <a href="#">เกี่ยวกับเรา</a>
                    <a href="#">ติดต่อ</a>
                </nav>
            </div>
            <div class="header-right">
                <div class="user-icon">👤</div>
                <div class="cart-icon">🛒</div>
                <div class="menu-icon">☰</div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-container">
            <div class="logo">
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
            <h2 class="video-title">วิธีการสั่งซื้อ</h2>
            <div class="video-container" onclick="playVideo()">
                <div class="play-button">▶</div>
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
</body>
</html>