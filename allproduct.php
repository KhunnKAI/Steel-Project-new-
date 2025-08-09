<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการสินค้า</title>
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }

        .product-image {
            width: 100%;
            height: 180px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid #eee;
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
            box-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .product-info {
            padding: 15px;
        }

        .product-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #2c3e50;
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

        .add-to-cart-btn {
            width: 100%;
            padding: 10px;
            background: #2c3e50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s ease;
        }

        .add-to-cart-btn:hover {
            background: #34495e;
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
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include("header.php");?>

    <div class="container">
        <h1>สินค้า</h1>
        
        <div class="main-content">
            <div class="sidebar">
                <div class="filter-section">
                    ค้นหาสินค้า
                </div>
                
                <div class="price-filter">
                    <div style="text-align: center; font-weight: bold; margin-bottom: 10px;">ราคา</div>
                    <div class="price-inputs">
                        <input type="number" placeholder="0" min="0">
                        <span>ถึง</span>
                        <input type="number" placeholder="0" min="0">
                    </div>
                </div>
                
                <div class="category-filter">
                    <h3>ประเภท</h3>
                    <div class="category-item">
                        <input type="checkbox" id="steel-round">
                        <label for="steel-round">เหล็กเส้นกลม</label>
                    </div>
                    <div class="category-item">
                        <input type="checkbox" id="steel-square">
                        <label for="steel-square">เหล็กเหนียมเหลี่ยม</label>
                    </div>
                    <div class="category-item">
                        <input type="checkbox" id="steel-deformed">
                        <label for="steel-deformed">เหล็กรูปพรรณ</label>
                    </div>
                    <div class="category-item">
                        <input type="checkbox" id="steel-galvanized">
                        <label for="steel-galvanized">เหล็กชุบแป้งค์ตาม่า</label>
                    </div>
                    <div class="category-item">
                        <input type="checkbox" id="other">
                        <label for="other">อื่นๆ</label>
                    </div>
                </div>
                
                <div style="background: #2c3e50; color: white; padding: 10px; text-align: center; font-weight: bold; border-radius: 0 0 8px 8px;">
                    ค้นหา
                </div>
            </div>
            
            <div style="flex: 1;">
                <div class="search-sort-bar">
                    <div class="search-container">
                        <input type="text" class="search-input" placeholder="ค้นหา...">
                        <button class="search-btn">🔍</button>
                    </div>
                    <button class="sort-btn">
                        ⇅
                    </button>
                </div>
                
                <div class="products-grid">
                    <div class="product-card">
                        <div class="product-image">
                            <div class="steel-bars">
                                <div class="steel-bar"></div>
                                <div class="steel-bar"></div>
                                <div class="steel-bar"></div>
                                <div class="steel-bar"></div>
                                <div class="steel-bar"></div>
                            </div>
                        </div>
                        <div class="product-info">
                            <div class="product-title">เหล็กเส้นกลม RBxx</div>
                            <div class="product-specs">ขนาด: ø 10 น. x 00 ม. 0.00 กก.</div>
                            <div class="product-price">000.00 บาท/เส้น</div>
                            <button class="add-to-cart-btn">เพิ่มลงตะกร้า</button>
                        </div>
                    </div>
                    
                    <div class="product-card">
                        <div class="product-image">
                            <div class="steel-bars">
                                <div class="steel-bar"></div>
                                <div class="steel-bar"></div>
                                <div class="steel-bar"></div>
                                <div class="steel-bar"></div>
                                <div class="steel-bar"></div>
                            </div>
                        </div>
                        <div class="product-info">
                            <div class="product-title">เหล็กเส้นกลม RBxx</div>
                            <div class="product-specs">ขนาด: ø 10 น. x 00 ม. 0.00 กก.</div>
                            <div class="product-price">000.00 บาท/เส้น</div>
                            <button class="add-to-cart-btn">เพิ่มลงตะกร้า</button>
                        </div>
                    </div>
                    
                    <div class="product-card">
                        <div class="product-image">
                            <div class="steel-bars">
                                <div class="steel-bar"></div>
                                <div class="steel-bar"></div>
                                <div class="steel-bar"></div>
                                <div class="steel-bar"></div>
                                <div class="steel-bar"></div>
                            </div>
                        </div>
                        <div class="product-info">
                            <div class="product-title">เหล็กเส้นกลม RBxx</div>
                            <div class="product-specs">ขนาด: ø 10 น. x 00 ม. 0.00 กก.</div>
                            <div class="product-price">000.00 บาท/เส้น</div>
                            <button class="add-to-cart-btn">เพิ่มลงตะกร้า</button>
                        </div>
                    </div>
                    
                    <div class="product-card">
                        <div class="product-image">
                            <div class="steel-bars">
                                <div class="steel-bar"></div>
                                <div class="steel-bar"></div>
                                <div class="steel-bar"></div>
                                <div class="steel-bar"></div>
                                <div class="steel-bar"></div>
                            </div>
                        </div>
                        <div class="product-info">
                            <div class="product-title">เหล็กเส้นกลม RBxx</div>
                            <div class="product-specs">ขนาด: ø 10 น. x 00 ม. 0.00 กก.</div>
                            <div class="product-price">000.00 บาท/เส้น</div>
                            <button class="add-to-cart-btn">เพิ่มลงตะกร้า</button>
                        </div>
                    </div>
                    
                    <div class="product-card">
                        <div class="product-image">
                            <div class="steel-bars">
                                <div class="steel-bar"></div>
                                <div class="steel-bar"></div>
                                <div class="steel-bar"></div>
                                <div class="steel-bar"></div>
                                <div class="steel-bar"></div>
                            </div>
                        </div>
                        <div class="product-info">
                            <div class="product-title">เหล็กเส้นกลม RBxx</div>
                            <div class="product-specs">ขนาด: ø 10 น. x 00 ม. 0.00 กก.</div>
                            <div class="product-price">000.00 บาท/เส้น</div>
                            <button class="add-to-cart-btn">เพิ่มลงตะกร้า</button>
                        </div>
                    </div>
                    
                    <div class="product-card">
                        <div class="product-image">
                            <div class="steel-bars">
                                <div class="steel-bar"></div>
                                <div class="steel-bar"></div>
                                <div class="steel-bar"></div>
                                <div class="steel-bar"></div>
                                <div class="steel-bar"></div>
                            </div>
                        </div>
                        <div class="product-info">
                            <div class="product-title">เหล็กเส้นกลม RBxx</div>
                            <div class="product-specs">ขนาด: ø 10 น. x 00 ม. 0.00 กก.</div>
                            <div class="product-price">000.00 บาท/เส้น</div>
                            <button class="add-to-cart-btn">เพิ่มลงตะกร้า</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add some basic interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Search functionality
            const searchInput = document.querySelector('.search-input');
            const searchBtn = document.querySelector('.search-btn');
            
            searchBtn.addEventListener('click', function() {
                const searchTerm = searchInput.value.trim();
                if (searchTerm) {
                    alert('ค้นหา: ' + searchTerm);
                }
            });
            
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    searchBtn.click();
                }
            });
            
            // Add to cart functionality
            const addToCartBtns = document.querySelectorAll('.add-to-cart-btn');
            addToCartBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    btn.textContent = 'เพิ่มแล้ว ✓';
                    btn.style.backgroundColor = '#27ae60';
                    setTimeout(() => {
                        btn.textContent = 'เพิ่มลงตะกร้า';
                        btn.style.backgroundColor = '#2c3e50';
                    }, 2000);
                });
            });
            
            // Filter functionality
            const checkboxes = document.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    console.log('Filter changed:', this.id, this.checked);
                });
            });
            
            // Price filter
            const priceInputs = document.querySelectorAll('.price-inputs input');
            priceInputs.forEach(input => {
                input.addEventListener('change', function() {
                    console.log('Price filter changed:', this.value);
                });
            });
        });
    </script>

    <!-- Footer -->
    <?php include("footer.php");?>
</body>
</html>