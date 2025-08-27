let allProducts = [];
let filteredProducts = [];
let currentSort = 'latest';

// ฟังก์ชันดึงข้อมูลสินค้าจาก API
async function fetchProducts() {
    try {
        console.log("=== fetchProducts Debug ===");
        console.log("Current URL:", window.location.href);

        const response = await fetch('controllers/product_home.php');
        console.log("API Response status:", response.status);
        console.log("API Response ok:", response.ok);

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const contentType = response.headers.get('content-type');
        console.log("Response content-type:", contentType);

        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Response is not JSON:', text);
            throw new Error('Response is not valid JSON');
        }

        const result = await response.json();
        console.log("API Response data:", result);

        if (result.success && result.data) {
            let products = result.data;
            if (result.data.products) {
                products = result.data.products;
            }

            // map product data
            allProducts = products.map(product => {
                const productId = String(product.product_id || product.id || '').trim();
                if (!productId) {
                    console.warn('Product found with no ID:', product);
                }
                return {
                    id: productId,
                    name: product.name || 'ไม่ระบุชื่อ',
                    category: product.category_name || 'ไม่ระบุ',
                    price: parseFloat(product.price) || 0,
                    description: product.description || '',
                    images: product.images || [],
                    image: product.images && product.images.length > 0
                        ? product.images.find(img => img.is_main)?.image_url || product.images[0].image_url
                        : 'no-image.jpg',
                    date: new Date(product.created_at),
                    lot: product.lot,
                    stock: product.stock,
                    supplier: product.supplier_name || 'ไม่ระบุ',
                    received_date: product.received_date
                };
            });

            // กรองสินค้าที่ไม่มี ID ออก
            allProducts = allProducts.filter(product => product.id && product.id !== '');

            filteredProducts = [...allProducts];
            displayProducts(filteredProducts);
            updateCategoryFilter();

            console.log(`โหลดข้อมูลสินค้าสำเร็จ: ${allProducts.length} รายการ`);
        } else {
            console.error('Error fetching products:', result.message);
            showNoProductsMessage('ไม่สามารถโหลดข้อมูลสินค้าได้: ' + (result.message || 'Unknown error'));
        }
    } catch (error) {
        console.error("=== Fetch Error ===");
        console.error("Error details:", error);
        if (error.message.includes('404')) {
            showNoProductsMessage('ไม่พบไฟล์ API (404) - ตรวจสอบ path: controllers/product_home.php');
        } else if (error.message.includes('not valid JSON')) {
            showNoProductsMessage('เซิร์ฟเวอร์ตอบกลับข้อมูลที่ไม่ถูกต้อง');
        } else {
            showNoProductsMessage('เกิดข้อผิดพลาดในการโหลดข้อมูล: ' + error.message);
        }
    }
}

// แสดงสินค้า
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

    grid.innerHTML = productsToShow.map((product, index) => {
        const cleanProductId = String(product.id || '').trim();
        return `
        <div class="product-card" 
             data-category="${product.category}" 
             data-price="${product.price}" 
             data-product-id="${cleanProductId}" 
             data-product-index="${index}"
             style="cursor: pointer;">
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
            <button class="product-btn" data-product-index="${index}">
                เพิ่มในตะกร้า
            </button>
        </div>
    `;
    }).join('');

    // ใช้ event delegation
    grid.onclick = (event) => {
        const card = event.target.closest('.product-card');
        if (!card) return;

        const index = card.dataset.productIndex;
        const product = productsToShow[index];

        if (event.target.classList.contains('product-btn')) {
            addToCart(product.name, product.id);
        } else {
            viewProduct(product.id);
        }
    };
}

// แสดงข้อความเมื่อไม่พบสินค้า
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

// ฟังก์ชันเปิดหน้าสินค้า
function viewProduct(productId) {
    if (!productId) {
        alert('ไม่พบรหัสสินค้า กรุณาลองใหม่อีกครั้ง');
        return;
    }
    const cleanProductId = String(productId).trim();
    const targetUrl = `product.php?id=${encodeURIComponent(cleanProductId)}`;
    window.location.href = targetUrl;
}

// ฟังก์ชันเพิ่มลงตะกร้า
function addToCart(productName, productId) {
    console.log(`Adding to cart: ${productName} (ID: ${productId})`);
    alert(`เพิ่ม "${productName}" ลงในตะกร้าแล้ว`);
}

// ฟิลเตอร์หมวดหมู่
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

// ฟิลเตอร์และค้นหา
function applyCurrentFilters() {
    let filtered = [...allProducts];
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

    const checkedCategories = Array.from(document.querySelectorAll('input[type="checkbox"]:checked'))
        .map(checkbox => checkbox.value);
    if (checkedCategories.length > 0) {
        filtered = filtered.filter(product =>
            checkedCategories.includes(product.category)
        );
    }

    filteredProducts = filtered;
}

function filterByCategory() {
    applyCurrentFilters();
    sortProducts();
}

function searchProducts() {
    applyCurrentFilters();
    sortProducts();
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

// รีเซ็ตฟิลเตอร์
function clearAllFilters() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) searchInput.value = '';

    document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        checkbox.checked = false;
    });

    const sortSelect = document.getElementById('sortSelect');
    if (sortSelect) sortSelect.value = 'latest';
    currentSort = 'latest';

    filteredProducts = [...allProducts];
    sortProducts();
}

// init
document.addEventListener('DOMContentLoaded', function () {
    console.log("=== DOM Content Loaded ===");
    fetchProducts();

    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                searchProducts();
            }
        });
    }
});
