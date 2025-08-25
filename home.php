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
            font-family: 'Inter';
            background: #f8f9fa;
        }

        /* Hero Section */
        .hero {
            background: #051A37;
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
            max-width: 1400px;
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

        /* Products Section Layout */
        .products-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        /* Top Search Bar */
        .search-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            gap: 20px;
            flex-wrap: wrap;
        }

        .search-left {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
        }

        .search-input {
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            width: 300px;
            max-width: 100%;
        }

        .search-input:focus {
            outline: none;
            border-color: #051A37;
        }

        .search-btn {
            padding: 12px 20px;
            background: #051A37;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
        }

        .search-btn:hover {
            background: #051A37;
        }

        .sort-dropdown {
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            background: white;
            cursor: pointer;
            min-width: 180px;
        }

        .sort-dropdown:focus {
            outline: none;
            border-color: #051A37;
        }

        /* Main Products Layout */
        .products-layout {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 30px;
        }

        /* Filter Sidebar */
        .filter-sidebar {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .filter-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            border-bottom: 2px solid #051A37;
            padding-bottom: 10px;
        }

        .filter-group {
            margin-bottom: 25px;
        }

        .filter-group-title {
            font-size: 16px;
            font-weight: 600;
            color: #495057;
            margin-bottom: 12px;
        }

        .filter-option {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            cursor: pointer;
        }

        .filter-option input[type="checkbox"] {
            margin-right: 8px;
            transform: scale(1.2);
        }

        .filter-option label {
            cursor: pointer;
            color: #495057;
            font-size: 14px;
        }

        .price-range {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 10px;
        }

        .price-input {
            width: 80px;
            padding: 8px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-size: 14px;
        }

        .filter-apply-btn {
            width: 100%;
            padding: 10px;
            background: #051A37;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 15px;
        }

        .filter-apply-btn:hover {
            background: #051A37;
        }

        .clear-filters {
            width: 100%;
            padding: 8px;
            background: transparent;
            color: #6c757d;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 8px;
        }

        .clear-filters:hover {
            background: #f8f9fa;
        }

        /* Products Grid */
        .products-container {
            min-height: 400px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .product-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.15);
            border-color: #051A37;
        }

         .product-image {
            width: 100%;
            aspect-ratio: 1 / 1; /* สี่เหลี่ยมจัตุรัสที่แท้จริง */
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            border-radius: 12px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 14px;
            font-weight: 600;
            position: relative;
            overflow: hidden;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover; /* ทำให้รูปเต็มกรอบโดยไม่บิดเบี้ยว */
            border-radius: 10px;
        }

        .product-category {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #051A37;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .product-name {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .product-description {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 12px;
            line-height: 1.4;
        }

        .product-price {
            font-size: 20px;
            font-weight: 700;
            color: #051A37;
            margin-bottom: 15px;
        }

        .product-btn {
            width: 100%;
            padding: 12px;
            background: #051A37;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }

        .product-btn:hover {
            background: #051A37;
        }

        .no-products {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .no-products h3 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        /* Results count */
        .results-count {
            color: #6c757d;
            margin-bottom: 20px;
            font-size: 14px;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .products-layout {
                grid-template-columns: 1fr;
            }

            .filter-sidebar {
                position: static;
                margin-bottom: 20px;
            }
        }

        @media (max-width: 768px) {
            .search-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .search-left {
                flex-direction: column;
                gap: 10px;
            }

            .search-input {
                width: 100%;
            }

            .sort-dropdown {
                width: 100%;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
                gap: 20px;
            }

            .price-range {
                flex-direction: column;
                align-items: stretch;
                gap: 8px;
            }

            .price-input {
                width: 100%;
            }
        }
    </style>
</head>
<body>
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
            <!-- Top Search Bar -->
            <div class="search-bar">
                <div class="search-left">
                    <input type="text" class="search-input" placeholder="ค้นหาสินค้า เช่น เหล็กเส้น ข้ออ้อย..." id="searchInput">
                    <button class="search-btn" onclick="searchProducts()">🔍 ค้นหา</button>
                </div>
                <select class="sort-dropdown" id="sortSelect" onchange="sortProducts()">
                    <option value="latest">ล่าสุด</option>
                    <option value="price-high">ราคา: มาก - น้อย</option>
                    <option value="price-low">ราคา: น้อย - มาก</option>
                    <option value="name-az">ชื่อ: A - Z</option>
                </select>
            </div>

            <!-- Products Layout -->
            <div class="products-layout">
                <!-- Filter Sidebar -->
                <aside class="filter-sidebar">
                    <h3 class="filter-title">ตัวกรอง</h3>
                    
                    <!-- Price Range Filter -->
                    <div class="filter-group">
                        <h4 class="filter-group-title">ช่วงราคา</h4>
                        <div class="price-range">
                            <input type="number" class="price-input" placeholder="ต่ำสุด" id="minPrice">
                            <span>-</span>
                            <input type="number" class="price-input" placeholder="สูงสุด" id="maxPrice">
                        </div>
                        <button class="filter-apply-btn" onclick="applyPriceFilter()">ใช้ตัวกรอง</button>
                    </div>

                    <!-- Category Filter -->
                    <div class="filter-group">
                        <h4 class="filter-group-title">ประเภทสินค้า</h4>
                        <div class="filter-option">
                            <input type="checkbox" id="steel-bar" value="เหล็กเส้น" onchange="filterByCategory()">
                            <label for="steel-bar">เหล็กเส้น</label>
                        </div>
                        <div class="filter-option">
                            <input type="checkbox" id="steel-sheet" value="เหล็กแผ่น" onchange="filterByCategory()">
                            <label for="steel-sheet">เหล็กแผ่น</label>
                        </div>
                        <div class="filter-option">
                            <input type="checkbox" id="steel-shape" value="เหล็กรูปพรรณ" onchange="filterByCategory()">
                            <label for="steel-shape">เหล็กรูปพรรณ</label>
                        </div>
                        <div class="filter-option">
                            <input type="checkbox" id="steel-mesh" value="เหล็กตะแกรง/ตาข่าย" onchange="filterByCategory()">
                            <label for="steel-mesh">เหล็กตะแกรง/ตาข่าย</label>
                        </div>
                        <div class="filter-option">
                            <input type="checkbox" id="others" value="อื่นๆ" onchange="filterByCategory()">
                            <label for="others">อื่นๆ</label>
                        </div>
                    </div>

                    <button class="clear-filters" onclick="clearAllFilters()">ล้างตัวกรอง</button>
                </aside>

                <!-- Products Container -->
                <div class="products-container">
                    <div class="results-count" id="resultsCount">แสดงสินค้าทั้งหมด 12 รายการ</div>
                    
                    <div class="products-grid" id="productsGrid">
                        <!-- Products will be populated by JavaScript -->
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include("footer.php");?>

    <script src="home.js"></script>
    
</body>
</html>