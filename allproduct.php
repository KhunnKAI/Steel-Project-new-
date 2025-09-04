<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการสินค้า - ช่างเหล็กไทย</title>
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
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        h1 {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 30px;
            color: #2c3e50;
        }

        .main-content {
            display: flex;
            gap: 20px;
        }

        /* Filter Sidebar */
        .sidebar {
            width: 200px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .filter-section {
            background: #d32f2f;
            color: white;
            padding: 10px;
            text-align: center;
            font-weight: bold;
            border-radius: 8px 8px 0 0;
        }

        .price-filter {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .price-inputs {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .price-inputs input {
            width: 60px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
            font-size: 14px;
        }

        .category-filter {
            padding: 15px;
        }

        .category-filter h3 {
            margin-bottom: 10px;
            color: #2c3e50;
            font-size: 16px;
        }

        .category-item {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }

        .category-item input[type="checkbox"] {
            margin-right: 8px;
            transform: scale(1.1);
        }

        .category-item label {
            cursor: pointer;
            font-size: 14px;
        }

        .filter-apply-btn {
            background: #2c3e50;
            color: white;
            padding: 10px;
            text-align: center;
            font-weight: bold;
            border-radius: 8px 8px 8px 8px;
            cursor: pointer;
            border: none;
            width: 70%;
        }

        .filter-apply-btn:hover {
            background: #34495e;
        }

        /* Search and Sort Bar */
        .search-sort-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .search-container {
            flex: 1;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
        }

        .search-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 18px;
        }

        .sort-dropdown {
            padding: 12px 16px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            min-width: 180px;
        }

        .sort-dropdown:focus {
            outline: none;
            border-color: #2c3e50;
        }

        /* Products Grid */
        .products-container {
            flex: 1;
        }

        .results-count {
            color: #6c757d;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .product-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            position: relative;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .product-image {
            width: 100%;
            height: 180px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid #eee;
            overflow: hidden;
            position: relative;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-category {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #2c3e50;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .steel-bars {
            display: flex;
            gap: 3px;
            transform: perspective(100px) rotateX(15deg);
        }

        .steel-bar {
            width: 8px;
            height: 80px;
            background: linear-gradient(to bottom, #666, #333, #666);
            border-radius: 2px;
            box-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .product-info {
            padding: 15px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .product-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #2c3e50;
            line-height: 1.3;
        }

        .product-specs {
            font-size: 12px;
            color: #666;
            margin-bottom: 10px;
        }

        .product-details {
            font-size: 12px;
            color: #666;
            margin-bottom: 10px;
        }

        .product-detail-line {
            margin-bottom: 3px;
        }

        .detail-label {
            font-weight: 600;
        }

        .detail-value {
            color: #777;
        }

        .product-price {
            font-size: 18px;
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 10px;
        }

        .product-stock {
            font-size: 12px;
            color: #777;
            margin-bottom: 10px;
        }

        .stock-out {
            color: #e74c3c;
            font-weight: 600;
        }

        .stock-low {
            color: #f39c12;
            font-weight: 600;
        }

        .stock-available {
            color: #27ae60;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            margin-top: auto;
        }

        .add-to-cart-btn {
            flex: 1;
            padding: 8px 12px;
            background: #2c3e50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 12px;
            transition: all 0.3s ease;
        }

        .add-to-cart-btn:hover {
            background: #34495e;
            transform: translateY(-1px);
        }

        .add-to-cart-btn:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
            transform: none;
        }

        .view-detail-btn {
            flex: 1;
            padding: 8px 12px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 12px;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
            display: inline-block;
        }

        .view-detail-btn:hover {
            background: #229954;
            transform: translateY(-1px);
            text-decoration: none;
            color: white;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .error {
            text-align: center;
            padding: 40px;
            color: #e74c3c;
            background: #fff;
            border-radius: 8px;
            margin: 20px 0;
        }

        .no-products {
            text-align: center;
            padding: 40px;
            color: #666;
            background: #fff;
            border-radius: 8px;
            grid-column: 1 / -1;
        }

        .no-products h3 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        /* Clear filters button */
        .clear-filters {
            width: 100%;
            padding: 8px;
            background: transparent;
            color:rgb(90, 95, 99);
            border: 1px solid #dee2e6;
            border-radius: 0px 0px 8px 8px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 8px;
        }

        .clear-filters:hover {
            background:rgb(199, 199, 199);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .main-content {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                position: static;
                margin-bottom: 20px;
            }
        }

        @media (max-width: 768px) {
            .search-sort-bar {
                flex-direction: column;
            }

            .sort-dropdown {
                width: 100%;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 15px;
            }

            .price-inputs {
                flex-direction: column;
                gap: 8px;
            }

            .price-inputs input {
                width: 100%;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

    <!-- Header -->
    <?php include("header.php");?>

    <div class="container">
        <h1>รายการสินค้าทั้งหมด</h1>
        
        <div class="main-content">
            <div class="sidebar">
                <div class="filter-section">
                    ค้นหาสินค้า
                </div>

                <div class="price-filter">
                    <div style="text-align: center; font-weight: bold; margin-bottom: 10px;">ราคา</div>
                    <div class="price-inputs">
                        <input type="number" id="minPrice" placeholder="0" min="0">
                        <span>ถึง</span>
                        <input type="number" id="maxPrice" placeholder="0" min="0">
                    </div>
                </div>

                <div class="category-filter">
                    <h3>ประเภท</h3>
                    <div class="category-item">
                        <input type="checkbox" id="steel-bar" value="เหล็กเส้น" onchange="filterByCategory()">
                        <label for="steel-bar">เหล็กเส้น</label>
                    </div>
                    <div class="category-item">
                        <input type="checkbox" id="steel-sheet" value="เหล็กแผ่น" onchange="filterByCategory()">
                        <label for="steel-sheet">เหล็กแผ่น</label>
                    </div>
                    <div class="category-item">
                        <input type="checkbox" id="steel-shape" value="เหล็กรูปพรรณ" onchange="filterByCategory()">
                        <label for="steel-shape">เหล็กรูปพรรณ</label>
                    </div>
                    <div class="category-item">
                        <input type="checkbox" id="steel-mesh" value="เหล็กตะแกรง/ตาข่าย" onchange="filterByCategory()">
                        <label for="steel-mesh">เหล็กตะแกรง/ตาข่าย</label>
                    </div>
                    <div class="category-item">
                        <input type="checkbox" id="others" value="อื่นๆ" onchange="filterByCategory()">
                        <label for="others">อื่นๆ</label>
                    </div>
                </div>

                <button class="filter-apply-btn" onclick="applyPriceFilter()">
                    ค้นหา
                </button>
                
                <button class="clear-filters" onclick="clearAllFilters()">ล้างตัวกรอง</button>
            </div>

            <div class="products-container">
                <div class="search-sort-bar">
                    <div class="search-container">
                        <input type="text" id="searchInput" class="search-input" placeholder="ค้นหาชื่อสินค้า...">
                        <button class="search-btn" onclick="searchProducts()">🔍</button>
                    </div>
                    <select class="sort-dropdown" id="sortSelect" onchange="sortProducts()">
                        <option value="latest">ล่าสุด</option>
                        <option value="price-high">ราคา: สูง-ต่ำ</option>
                        <option value="price-low">ราคา: ต่ำ-สูง</option>
                        <option value="name-az">ชื่อ: A-Z</option>
                    </select>
                </div>

                <div class="results-count" id="resultsCount">กำลังโหลดข้อมูลสินค้า...</div>
                
                <div class="products-grid" id="productsGrid">
                    <div class="loading">กำลังโหลดข้อมูลสินค้า...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include("footer.php");?>

    <script src="cart.js" defer></script>
    <script src="allproduct.js" defer></script>
</body>
</html>