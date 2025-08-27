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

    <script>
    // Global variables
    let allProducts = [];
    let filteredProducts = [];
    let currentSort = 'price_asc';

    // API configuration
    const API_ENDPOINTS = [
        'admin/controllers/get_product.php',
        '/admin/controllers/get_product.php',
        './admin/controllers/get_product.php',
        'http://localhost/newproject/admin/controllers/get_product.php'
    ];

    // Initialize page
    document.addEventListener('DOMContentLoaded', function() {
        loadProducts();
        setupEventListeners();
    });

    // Setup event listeners
    function setupEventListeners() {
        document.getElementById('search-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });

        const categoryCheckboxes = document.querySelectorAll('.category-item input[type="checkbox"]');
        categoryCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', applyFilters);
        });

        document.getElementById('min-price').addEventListener('change', applyFilters);
        document.getElementById('max-price').addEventListener('change', applyFilters);
    }

    // Load products with multiple endpoint attempts
    async function loadProducts() {
        showLoading();

        for (let i = 0; i < API_ENDPOINTS.length; i++) {
            const endpoint = API_ENDPOINTS[i];

            try {
                const response = await fetch(endpoint, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const text = await response.text();
                let result;
                
                try {
                    result = JSON.parse(text);
                } catch (parseError) {
                    continue;
                }

                if (result && result.success) {
                    allProducts = result.data || [];
                    filteredProducts = [...allProducts];
                    displayProducts();
                    return; // Success - exit the loop
                } else {
                    throw new Error(result.message || 'API returned success: false');
                }

            } catch (error) {
                if (i === API_ENDPOINTS.length - 1) {
                    showError(`ไม่สามารถเชื่อมต่อ API ได้:<br>${error.message}`);
                }
            }
        }
    }

    // Display products
    function displayProducts() {
        const grid = document.getElementById('products-grid');

        if (filteredProducts.length === 0) {
            grid.innerHTML = '<div class="no-products">ไม่พบสินค้าที่ตรงตามเงื่อนไข</div>';
            return;
        }

        let html = '';
        filteredProducts.forEach((product) => {
            html += createProductCard(product);
        });

        grid.innerHTML = html;
    }

    // Create product card HTML with navigation link
    function createProductCard(product) {
        const imageUrl = getMainImageUrl(product);
        const dimensions = formatDimensions(product);
        const price = formatPrice(product.price);
        const stockStatus = getStockStatus(product.stock);
        const productDetailUrl = `product.php?id=${product.product_id}`;

        return `
                <div class="product-card" onclick="navigateToProduct('${product.product_id}')" data-product-id="${product.product_id}">
                    <div class="product-image">
                        ${imageUrl ? 
                            `<img src="${imageUrl}" alt="${product.name}" 
                                  onerror="this.parentNode.innerHTML='<div class=\\'steel-bars\\'><div class=\\'steel-bar\\'></div><div class=\\'steel-bar\\'></div><div class=\\'steel-bar\\'></div><div class=\\'steel-bar\\'></div><div class=\\'steel-bar\\'></div></div>'">` :
                            `<div class="steel-bars">
                                <div class="steel-bar"></div>
                                <div class="steel-bar"></div>
                                <div class="steel-bar"></div>
                                <div class="steel-bar"></div>
                                <div class="steel-bar"></div>
                            </div>`
                        }
                    </div>
                    <div class="product-info">
                        <div class="product-title">${product.name || 'ไม่ระบุชื่อ'}</div>
                        <div class="product-specs">${dimensions}</div>
                        ${product.lot ? `<div class="product-specs">Lot: ${product.lot}</div>` : ''}
                        <div class="product-price">${price}</div>
                        <div class="product-stock">${stockStatus}</div>
                        <div class="action-buttons">
                            <button class="add-to-cart-btn" 
                                    ${(product.stock || 0) <= 0 ? 'disabled' : ''} 
                                    onclick="event.stopPropagation(); addToCart('${product.product_id || ''}')">
                                ${(product.stock || 0) <= 0 ? 'สินค้าหมด' : 'ใส่ตะกร้า'}
                            </button>
                            <a href="${productDetailUrl}" class="view-detail-btn" onclick="event.stopPropagation();">
                                ดูรายละเอียด
                            </a>
                        </div>
                    </div>
                </div>
            `;
    }

    // Navigate to product detail page
    function navigateToProduct(productId) {
        if (productId) {
            const detailUrl = `product.php?id=${productId}`;
            window.location.href = detailUrl;
        }
    }

    // Get main image URL
    function getMainImageUrl(product) {
        if (product.images && Array.isArray(product.images) && product.images.length > 0) {
            const mainImage = product.images.find(img => img.is_main === 1 || img.is_main === '1');
            let selectedImageUrl = mainImage ? mainImage.image_url : product.images[0].image_url;

            if (selectedImageUrl) {
                selectedImageUrl = selectedImageUrl.replace('/steelproject/', '/newproject/');
            }

            return selectedImageUrl;
        }

        return null;
    }

    // Format dimensions
    function formatDimensions(product) {
        let dimensions = [];

        if (product.width) {
            dimensions.push(`กว้าง ${product.width} ${product.width_unit || 'mm'}`);
        }
        if (product.length) {
            dimensions.push(`ยาว ${product.length} ${product.length_unit || 'mm'}`);
        }
        if (product.height) {
            dimensions.push(`สูง ${product.height} ${product.height_unit || 'mm'}`);
        }
        if (product.weight) {
            dimensions.push(`หนัก ${product.weight} ${product.weight_unit || 'kg'}`);
        }

        return dimensions.length > 0 ? dimensions.join(', ') : 'ไม่ระบุขนาด';
    }

    // Format price
    function formatPrice(price) {
        if (!price || price == 0) {
            return 'ราคาสอบถาม';
        }
        return parseFloat(price).toLocaleString('th-TH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }) + ' บาท';
    }

    // Get stock status
    function getStockStatus(stock) {
        if (!stock || stock <= 0) {
            return 'สินค้าหมด';
        }
        return `คงเหลือ ${stock} ชิ้น`;
    }

    // Show loading
    function showLoading() {
        document.getElementById('products-grid').innerHTML = '<div class="loading">กำลังโหลดข้อมูลสินค้า...</div>';
    }

    // Show error
    function showError(message) {
        document.getElementById('products-grid').innerHTML = `<div class="error">${message}</div>`;
    }

    // Perform search
    function performSearch() {
        applyFilters();
    }

    // Apply filters
    function applyFilters() {
        const searchTerm = document.getElementById('search-input').value.toLowerCase().trim();
        const minPrice = parseFloat(document.getElementById('min-price').value) || 0;
        const maxPrice = parseFloat(document.getElementById('max-price').value) || Infinity;

        const selectedCategories = [];
        const categoryCheckboxes = document.querySelectorAll('.category-item input[type="checkbox"]:checked');
        categoryCheckboxes.forEach(checkbox => {
            selectedCategories.push(checkbox.value);
        });

        filteredProducts = allProducts.filter(product => {
            if (searchTerm && !(product.name || '').toLowerCase().includes(searchTerm)) {
                return false;
            }

            const productPrice = parseFloat(product.price) || 0;
            if (productPrice < minPrice || productPrice > maxPrice) {
                return false;
            }

            if (selectedCategories.length > 0 && !selectedCategories.includes(product.category_id)) {
                return false;
            }

            return true;
        });

        sortProducts();
        displayProducts();
    }

    // Toggle sort
    function toggleSort() {
        const sortOptions = ['price_asc', 'price_desc', 'name_asc', 'name_desc'];
        const currentIndex = sortOptions.indexOf(currentSort);
        const nextIndex = (currentIndex + 1) % sortOptions.length;
        currentSort = sortOptions[nextIndex];

        const sortText = {
            'price_asc': 'ราคา: ต่ำ-สูง',
            'price_desc': 'ราคา: สูง-ต่ำ',
            'name_asc': 'ชื่อ: A-Z',
            'name_desc': 'ชื่อ: Z-A'
        };
        document.getElementById('sort-text').textContent = sortText[currentSort];

        sortProducts();
        displayProducts();
    }

    // Sort products
    function sortProducts() {
        filteredProducts.sort((a, b) => {
            switch (currentSort) {
                case 'price_asc':
                    return (parseFloat(a.price) || 0) - (parseFloat(b.price) || 0);
                case 'price_desc':
                    return (parseFloat(b.price) || 0) - (parseFloat(a.price) || 0);
                case 'name_asc':
                    return (a.name || '').localeCompare(b.name || '', 'th');
                case 'name_desc':
                    return (b.name || '').localeCompare(a.name || '', 'th');
                default:
                    return 0;
            }
        });
    }

    // Add to cart function
    function addToCart(productId) {
        const button = event.target;
        const originalText = button.textContent;

        button.textContent = 'เพิ่มแล้ว ✓';
        button.style.backgroundColor = '#27ae60';
        button.disabled = true;

        setTimeout(() => {
            button.textContent = originalText;
            button.style.backgroundColor = '#2c3e50';
            button.disabled = false;
        }, 2000);
    }
    </script>
</body>

</html>