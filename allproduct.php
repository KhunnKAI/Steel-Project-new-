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

    .sidebar {
        width: 200px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        height: fit-content;
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
    }

    .category-filter {
        padding: 15px;
    }

    .category-filter h3 {
        margin-bottom: 10px;
        color: #2c3e50;
    }

    .category-item {
        display: flex;
        align-items: center;
        margin-bottom: 8px;
    }

    .category-item input[type="checkbox"] {
        margin-right: 8px;
    }

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

    .sort-btn {
        padding: 12px 20px;
        border: 1px solid #ddd;
        background: white;
        border-radius: 6px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
        flex: 1;
    }

    .product-card {
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        cursor: pointer;
        position: relative;
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

    .product-image::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(44, 62, 80, 0);
        transition: background 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 16px;
    }

    .product-card:hover .product-image::after {
        background: rgba(44, 62, 80, 0.7);
        content: 'คลิกดูรายละเอียด';
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

    .action-buttons {
        display: flex;
        gap: 8px;
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

    @media (max-width: 768px) {
        .main-content {
            flex-direction: column;
        }

        .sidebar {
            width: 100%;
        }

        .products-grid {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
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
        <div class="main-content">
            <div class="sidebar">
                <div class="filter-section">
                    ค้นหาสินค้า
                </div>

                <div class="price-filter">
                    <div style="text-align: center; font-weight: bold; margin-bottom: 10px;">ราคา</div>
                    <div class="price-inputs">
                        <input type="number" id="min-price" placeholder="0" min="0">
                        <span>ถึง</span>
                        <input type="number" id="max-price" placeholder="0" min="0">
                    </div>
                </div>

                <div class="category-filter">
                    <h3>ประเภท</h3>
                    <div class="category-item">
                        <input type="checkbox" id="category-rd" value="rd">
                        <label for="category-rd">เหล็กเส้นกลม</label>
                    </div>
                    <div class="category-item">
                        <input type="checkbox" id="category-sq" value="sq">
                        <label for="category-sq">เหล็กเหนียมเหลี่ยม</label>
                    </div>
                    <div class="category-item">
                        <input type="checkbox" id="category-df" value="df">
                        <label for="category-df">เหล็กรูปพรรณ</label>
                    </div>
                    <div class="category-item">
                        <input type="checkbox" id="category-gz" value="gz">
                        <label for="category-gz">เหล็กชุบแป้งค์</label>
                    </div>
                    <div class="category-item">
                        <input type="checkbox" id="category-ot" value="ot">
                        <label for="category-ot">อื่นๆ</label>
                    </div>
                </div>

                <div style="background: #2c3e50; color: white; padding: 10px; text-align: center; font-weight: bold; border-radius: 0 0 8px 8px; cursor: pointer;"
                    onclick="applyFilters()">
                    ค้นหา
                </div>
            </div>

            <div style="flex: 1;">
                <div class="search-sort-bar">
                    <div class="search-container">
                        <input type="text" id="search-input" class="search-input" placeholder="ค้นหาชื่อสินค้า...">
                        <button class="search-btn" onclick="performSearch()">🔍</button>
                    </div>
                    <button class="sort-btn" onclick="toggleSort()">
                        <span id="sort-text">ราคา: ต่ำ-สูง</span>
                        ⇅
                    </button>
                </div>

                <div id="products-grid" class="products-grid">
                    <div class="loading">กำลังโหลดข้อมูลสินค้า...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include("footer.php");?>

    <script src="allproduct.js" defer></script>
    <script src="cart.js" defer></script>
</body>
</html>