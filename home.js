let allProducts = [];
let filteredProducts = [];
let currentSort = 'latest';

// ฟังก์ชันดึงข้อมูลสินค้าจาก API
async function fetchProducts() {
    try {
        // แก้ไข path ให้ถูกต้อง - เปลี่ยนจาก \ เป็น /
        const response = await fetch('controllers/product_home.php');
        
        // ตรวจสอบ response status ก่อน
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        // ตรวจสอบ content-type
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Response is not JSON:', text);
            throw new Error('Response is not valid JSON');
        }
        
        const result = await response.json();
        
        if (result.success && result.data) {
            // ตรวจสอบโครงสร้างข้อมูลจาก PHP
            let products = result.data;
            
            // ถ้าข้อมูลมาในรูปแบบ pagination
            if (result.data.products) {
                products = result.data.products;
            }
            
            // แปลงข้อมูลจาก PHP format เป็น JavaScript format
            allProducts = products.map(product => ({
                id: product.product_id,
                name: product.name || 'ไม่ระบุชื่อ',
                category: product.category_name || 'ไม่ระบุ',
                price: parseFloat(product.price) || 0,
                description: product.description || '',
                images: product.images || [],
                // ใช้รูปแรกเป็น main image หรือ placeholder
                image: product.images && product.images.length > 0 
                    ? product.images.find(img => img.is_main)?.image_url || product.images[0].image_url
                    : 'no-image.jpg',
                date: new Date(product.created_at),
                lot: product.lot,
                stock: product.stock,
                dimensions: {
                    width: product.width,
                    length: product.length,
                    height: product.height,
                    weight: product.weight,
                    width_unit: product.width_unit,
                    length_unit: product.length_unit,
                    height_unit: product.height_unit,
                    weight_unit: product.weight_unit
                },
                supplier: product.supplier_name || 'ไม่ระบุ',
                received_date: product.received_date
            }));
            
            filteredProducts = [...allProducts];
            displayProducts(filteredProducts);
            updateCategoryFilter(); // สร้างตัวกรองหมวดหมู่อัตโนมัติ
            
            console.log(`โหลดข้อมูลสินค้าสำเร็จ: ${allProducts.length} รายการ`);
            
        } else {
            console.error('Error fetching products:', result.message);
            showNoProductsMessage('ไม่สามารถโหลดข้อมูลสินค้าได้: ' + (result.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Fetch error:', error);
        if (error.message.includes('404')) {
            showNoProductsMessage('ไม่พบไฟล์ API (404) - ตรวจสอบ path: controllers/product_home.php');
        } else if (error.message.includes('not valid JSON')) {
            showNoProductsMessage('เซิร์ฟเวอร์ตอบกลับข้อมูลที่ไม่ถูกต้อง');
        } else {
            showNoProductsMessage('เกิดข้อผิดพลาดในการโหลดข้อมูล: ' + error.message);
        }
    }
}

function displayProducts(productsToShow) {
    const grid = document.getElementById('productsGrid');
    const resultsCount = document.getElementById('resultsCount');

    if (!grid) {
        console.error('Element with id "productsGrid" not found');
        return;
    }
    
    if (resultsCount) {
        resultsCount.textContent = `แสดงสินค้าทั้งหมด ${productsToShow.length} รายการ`;
    }

    if (productsToShow.length === 0) {
        showNoProductsMessage('ไม่พบสินค้าที่ค้นหา');
        return;
    }

    grid.innerHTML = productsToShow.map(product => `
        <div class="product-card" data-category="${product.category}" data-price="${product.price}">
            <div class="product-image">
                <span class="product-category">${product.category}</span>
                ${product.image !== 'no-image.jpg' 
                    ? `<img src="${product.image}" alt="${product.name}" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                       <div style="display:none; padding: 20px; background: #f5f5f5; text-align: center; color: #666;">
                           ${product.name}
                       </div>`
                    : `<div style="padding: 20px; background: #f5f5f5; text-align: center; color: #666;">
                           ${product.name}
                       </div>`
                }
            </div>
            <div class="product-name">${product.name}</div>
            <div class="product-description">${product.description}</div>
            <div class="product-price">฿${product.price.toLocaleString()}</div>
            <button class="product-btn" onclick="addToCart('${product.name}', ${product.id})">
                เพิ่มในตะกร้า
            </button>
        </div>
    `).join('');
}

function showNoProductsMessage(message) {
    const grid = document.getElementById('productsGrid');
    if (grid) {
        grid.innerHTML = `
            <div class="no-products" style="text-align: center; padding: 40px; grid-column: 1 / -1;">
                <h3 style="color: #666; margin-bottom: 10px;">${message}</h3>
                <p style="color: #888;">กรุณาลองใช้คำค้นหาอื่น หรือเปลี่ยนตัวกรอง</p>
            </div>
        `;
    }
}

// สร้างตัวกรองหมวดหมู่อัตโนมัติจากข้อมูลสินค้า
function updateCategoryFilter() {
    const categories = [...new Set(allProducts.map(product => product.category))];
    const categoryFilter = document.getElementById('categoryFilter');
    
    if (categoryFilter && categories.length > 0) {
        const checkboxHtml = categories.map(category => `
            <label style="display: block; margin: 5px 0;">
                <input type="checkbox" value="${category}" onchange="filterByCategory()" style="margin-right: 8px;">
                ${category}
            </label>
        `).join('');
        
        categoryFilter.innerHTML = checkboxHtml;
    }
}

function sortProducts() {
    const sortValue = document.getElementById('sortSelect')?.value || 'latest';
    currentSort = sortValue;

    switch (sortValue) {
        case 'price-high':
            filteredProducts.sort((a, b) => b.price - a.price);
            break;
        case 'price-low':
            filteredProducts.sort((a, b) => a.price - b.price);
            break;
        case 'name-az':
            filteredProducts.sort((a, b) => a.name.localeCompare(b.name, 'th'));
            break;
        case 'latest':
        default:
            filteredProducts.sort((a, b) => b.date - a.date);
            break;
    }

    displayProducts(filteredProducts);
}

function searchProducts() {
    const searchInput = document.getElementById('searchInput');
    const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';

    if (!searchTerm) {
        filteredProducts = [...allProducts];
    } else {
        filteredProducts = allProducts.filter(product =>
            product.name.toLowerCase().includes(searchTerm) ||
            product.description.toLowerCase().includes(searchTerm) ||
            product.category.toLowerCase().includes(searchTerm) ||
            product.supplier.toLowerCase().includes(searchTerm)
        );
    }

    applyCurrentFilters();
    sortProducts();
}

function filterByCategory() {
    applyCurrentFilters();
    sortProducts();
}

function applyPriceFilter() {
    applyCurrentFilters();
    sortProducts();
}

function applyCurrentFilters() {
    let filtered = [...allProducts];

    // Apply search filter
    const searchInput = document.getElementById('searchInput');
    const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
    
    if (searchTerm) {
        filtered = filtered.filter(product =>
            product.name.toLowerCase().includes(searchTerm) ||
            product.description.toLowerCase().includes(searchTerm) ||
            product.category.toLowerCase().includes(searchTerm) ||
            product.supplier.toLowerCase().includes(searchTerm)
        );
    }

    // Apply category filter
    const checkedCategories = Array.from(document.querySelectorAll('input[type="checkbox"]:checked'))
        .map(checkbox => checkbox.value);

    if (checkedCategories.length > 0) {
        filtered = filtered.filter(product =>
            checkedCategories.includes(product.category)
        );
    }

    // Apply price filter
    const minPriceInput = document.getElementById('minPrice');
    const maxPriceInput = document.getElementById('maxPrice');
    const minPrice = minPriceInput ? minPriceInput.value : '';
    const maxPrice = maxPriceInput ? maxPriceInput.value : '';

    if (minPrice) {
        filtered = filtered.filter(product => product.price >= parseInt(minPrice));
    }

    if (maxPrice) {
        filtered = filtered.filter(product => product.price <= parseInt(maxPrice));
    }

    filteredProducts = filtered;
}

function clearAllFilters() {
    // Clear search
    const searchInput = document.getElementById('searchInput');
    if (searchInput) searchInput.value = '';

    // Clear price range
    const minPriceInput = document.getElementById('minPrice');
    const maxPriceInput = document.getElementById('maxPrice');
    if (minPriceInput) minPriceInput.value = '';
    if (maxPriceInput) maxPriceInput.value = '';

    // Clear category checkboxes
    document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        checkbox.checked = false;
    });

    // Reset sort to latest
    const sortSelect = document.getElementById('sortSelect');
    if (sortSelect) sortSelect.value = 'latest';
    currentSort = 'latest';

    // Reset products
    filteredProducts = [...allProducts];
    sortProducts();
}

function addToCart(productName, productId) {
    // ส่งข้อมูลไปยัง API หรือ session สำหรับตะกร้าสินค้า
    console.log(`Adding product to cart: ${productName} (ID: ${productId})`);
    alert(`เพิ่ม "${productName}" ลงในตะกร้าแล้ว`);
}

// รีเฟรชข้อมูลสินค้า
function refreshProducts() {
    console.log('Refreshing products...');
    fetchProducts();
}

// ฟังก์ชันทดสอบการเชื่อมต่อ API
async function testAPI() {
    try {
        const response = await fetch('controllers/product_home.php');
        console.log('API Response Status:', response.status);
        console.log('API Response Headers:', response.headers);
        const text = await response.text();
        console.log('API Raw Response:', text.substring(0, 500) + '...');
    } catch (error) {
        console.error('API Test Error:', error);
    }
}

// Initialize the page
document.addEventListener('DOMContentLoaded', function () {
    console.log('DOM Content Loaded - Initializing...');
    
    // เพิ่มปุ่มทดสอบ API (สำหรับ debug)
    if (window.location.search.includes('debug=true')) {
        const debugBtn = document.createElement('button');
        debugBtn.textContent = 'Test API';
        debugBtn.onclick = testAPI;
        debugBtn.style.cssText = 'position: fixed; top: 10px; right: 10px; z-index: 9999; background: red; color: white; padding: 10px;';
        document.body.appendChild(debugBtn);
    }
    
    // โหลดข้อมูลสินค้าเมื่อหน้าเว็บโหลดเสร็จ
    fetchProducts();

    // Add enter key support for search
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                searchProducts();
            }
        });
    }

    // เพิ่มปุ่มรีเฟรชข้อมูล (ถ้ามี)
    const refreshButton = document.getElementById('refreshButton');
    if (refreshButton) {
        refreshButton.addEventListener('click', refreshProducts);
    }
});

// ฟังก์ชันเสริมสำหรับการจัดการข้อผิดพลาด
window.addEventListener('error', function(e) {
    console.error('JavaScript Error:', e.error);
});

// ฟังก์ชันเสริมสำหรับ debug
window.addEventListener('unhandledrejection', function(e) {
    console.error('Unhandled Promise Rejection:', e.reason);
});

// Export functions สำหรับการใช้งานจากภายนอก
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        fetchProducts,
        displayProducts,
        searchProducts,
        sortProducts,
        clearAllFilters
    };
}