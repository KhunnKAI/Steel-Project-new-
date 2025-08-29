
let allProducts = [];
let filteredProducts = [];
let currentSort = 'latest';

// ฟังก์ชันดึงข้อมูลสินค้าจาก API
async function fetchProducts() {
    try {
        console.log("=== fetchProducts Debug ===");
        console.log("Current URL:", window.location.href);
        console.log("Fetching from:", 'controllers/product_home.php');

        const response = await fetch('controllers/product_home.php');
        console.log("API Response status:", response.status);
        console.log("API Response ok:", response.ok);
        console.log("Response URL:", response.url);

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const contentType = response.headers.get('content-type');
        console.log("Response content-type:", contentType);

        const text = await response.text();
        console.log("Raw response:", text);

        if (!contentType || !contentType.includes('application/json')) {
            console.error('Response is not JSON:', text);
            throw new Error('Response is not valid JSON');
        }

        let result;
        try {
            result = JSON.parse(text);
        } catch (parseError) {
            console.error('JSON Parse Error:', parseError);
            console.error('Raw text:', text);
            throw new Error('Invalid JSON response');
        }

        console.log("Parsed API Response:", result);

        if (result.success && result.data) {
            let products = result.data;

            // ตรวจสอบว่า data เป็น array หรือไม่
            if (!Array.isArray(products)) {
                console.error('Products data is not an array:', products);
                throw new Error('Invalid products data format');
            }

            console.log(`Found ${products.length} products`);

            // map product data
            allProducts = products.map((product, index) => {
                console.log(`Processing product ${index}:`, product);

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

            console.log(`Filtered products: ${allProducts.length} items`);
            console.log("Final products array:", allProducts);

            filteredProducts = [...allProducts];
            displayProducts(filteredProducts);
            updateCategoryFilter();

            console.log(`โหลดข้อมูลสินค้าสำเร็จ: ${allProducts.length} รายการ`);

        } else {
            console.error('API returned error:', result);
            const errorMessage = result.message || 'Unknown error';
            showNoProductsMessage('ไม่สามารถโหลดข้อมูลสินค้าได้: ' + errorMessage);
        }

    } catch (error) {
        console.error("=== Fetch Error ===");
        console.error("Error type:", error.constructor.name);
        console.error("Error message:", error.message);
        console.error("Stack trace:", error.stack);

        let errorMessage = 'เกิดข้อผิดพลาดในการโหลดข้อมูล: ';

        if (error.message.includes('404')) {
            errorMessage += 'ไม่พบไฟล์ API (404) - ตรวจสอบ path: controllers/product_home.php';
        } else if (error.message.includes('Failed to fetch') || error.message.includes('NetworkError')) {
            errorMessage += 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้';
        } else if (error.message.includes('not valid JSON') || error.message.includes('Invalid JSON')) {
            errorMessage += 'เซิร์ฟเวอร์ตอบกลับข้อมูลที่ไม่ถูกต้อง';
        } else {
            errorMessage += error.message;
        }

        showNoProductsMessage(errorMessage);
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
                เพิ่มใส่ตะกร้า
            </button>

        </div>
    `;
    }).join('');

    // ใช้ event delegation
    grid.onclick = (event) => {
        const card = event.target.closest('.product-card');
        if (!card) return;

        const index = card.dataset.productIndex;
        const product = filteredProducts[index];

        if (event.target.classList.contains('product-btn')) {
            handleAddToCart(product, event);
        } else {
            viewProduct(product.id);
        }
    };

}

// ฟังก์ชันจัดการการเพิ่มลงตะกร้า
function handleAddToCart(product, event) {
    if (!product || !product.id) return;

    console.log(`Adding to cart: ${product.name} (ID: ${product.id})`);

    // ใช้ CartManager แทน localStorage แบบเดิม
    if (typeof cartManager !== 'undefined' && cartManager.addItem) {
        cartManager.addItem(
            product.id,
            product.name,
            product.price,
            1,
            product.image,
            product.weight || 0  // ถ้ามี weight
        );

        // แสดง Toast / Alert
        if (typeof showToast === 'function') {
            showToast(`เพิ่ม "${product.name}" ลงในตะกร้าแล้ว!`);
        } else {
            alert(`เพิ่ม "${product.name}" ลงในตะกร้าแล้ว!`);
        }

        // เอฟเฟกต์ปุ่ม
        if (event && event.target) {
            const button = event.target;
            const originalText = button.textContent;
            const originalBg = button.style.background;

            button.textContent = 'เพิ่มแล้ว!';
            button.style.background = '#28a745';
            button.disabled = true;

            setTimeout(() => {
                button.textContent = originalText;
                button.style.background = originalBg;
                button.disabled = false;
            }, 1500);
        }

    } else {
        console.error('CartManager ยังโหลดไม่เสร็จ');
    }
}


function fallbackAddToCart(product, event) {
    // เก็บข้อมูลใน localStorage โดยตรง
    try {
        let cart = JSON.parse(localStorage.getItem('shopping_cart') || '{}');
        const itemKey = String(product.id).trim();

        if (cart[itemKey]) {
            cart[itemKey].quantity += 1;
        } else {
            cart[itemKey] = {
                id: itemKey,
                name: product.name,
                price: product.price,
                quantity: 1,
                image: product.image,
                addedAt: new Date().toISOString()
            };
        }

        localStorage.setItem('shopping_cart', JSON.stringify(cart));

        // อัพเดท badge
        const totalItems = Object.values(cart).reduce((total, item) => total + item.quantity, 0);
        const cartBadge = document.getElementById('cartBadge');
        if (cartBadge) {
            cartBadge.textContent = totalItems;
            cartBadge.style.display = totalItems > 0 ? 'flex' : 'none';
        }

        if (typeof showToast === 'function') {
            showToast(`เพิ่ม "${product.name}" ลงในตะกร้าแล้ว!`);
        } else {
            alert(`เพิ่ม "${product.name}" ลงในตะกร้าแล้ว!`);
        }

        // เอฟเฟกต์ปุ่ม
        if (event && event.target) {
            const button = event.target;
            const originalText = button.textContent;
            button.textContent = 'เพิ่มแล้ว!';
            button.style.background = '#28a745';
            button.disabled = true;

            setTimeout(() => {
                button.textContent = originalText;
                button.style.background = '';
                button.disabled = false;
            }, 1500);
        }

    } catch (error) {
        console.error('Fallback add to cart failed:', error);
        alert('เกิดข้อผิดพลาดในการเพิ่มสินค้า กรุณาลองใหม่อีกครั้ง');
    }
}

// เพิ่มฟังก์ชันตรวจสอบว่าระบบตะกร้าพร้อมใช้งาน
function waitForCartSystem(callback, maxAttempts = 10) {
    let attempts = 0;

    function check() {
        if (typeof window.cartManager !== 'undefined' && window.cartManager.addItem) {
            callback();
        } else if (attempts < maxAttempts) {
            attempts++;
            setTimeout(check, 100);
        } else {
            console.error('Cart system failed to load after maximum attempts');
        }
    }

    check();
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

function waitForDependencies(callback, attempts = 0, maxAttempts = 30) {
    console.log(`Waiting for dependencies... attempt ${attempts + 1}`);
    console.log('Cart Manager available:', typeof window.cartManager);
    console.log('Cart Manager object:', window.cartManager);

    if (typeof window.cartManager !== 'undefined') {
        console.log("✅ Cart Manager loaded!");
        callback();
    } else if (attempts < maxAttempts) {
        setTimeout(() => waitForDependencies(callback, attempts + 1, maxAttempts), 200);
    } else {
        console.warn("⚠️ Cart Manager not found after maximum attempts, proceeding anyway");
        console.log("Available window objects:", Object.keys(window).filter(key => key.includes('cart')));
        callback(); // เรียกต่อไปเพื่อให้โหลดสินค้า
    }
}

// แก้ไข DOMContentLoaded event listener
document.addEventListener('DOMContentLoaded', function () {
    console.log("=== DOM Content Loaded ===");
    console.log("Current URL:", window.location.href);
    console.log("Document ready state:", document.readyState);

    // ลองโหลดสินค้าทันที (ไม่รอ cartManager)
    console.log("🔄 Starting immediate product fetch...");
    fetchProducts();

    // และยังคงรอ cartManager ในพื้นหลัง
    waitForDependencies(() => {
        console.log("🔄 Dependencies loaded, fetching products again if needed...");
        // ถ้ายังไม่มีสินค้าแสดง ให้โหลดใหม่
        if (allProducts.length === 0) {
            fetchProducts();
        }
    });

    // Setup search input
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                searchProducts();
            }
        });
        console.log("✅ Search input event listener added");
    } else {
        console.warn("⚠️ Search input element not found");
    }

    // 🟢 ตรวจสอบ logout success แล้วล้าง cart
    const params = new URLSearchParams(window.location.search);
    if (params.get("logout") === "success") {
        console.log("✅ Detected logout success, clearing cart...");

        localStorage.removeItem("shopping_cart");
        localStorage.removeItem("cart");

        if (window.cartManager && typeof window.cartManager.clearCartData === "function") {
            window.cartManager.clearCartData();
        }

        const cartBadge = document.getElementById('cartBadge');
        if (cartBadge) {
            cartBadge.textContent = "0";
            cartBadge.style.display = "none";
        }
    }
});
