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
                    product_id: productId,
                    name: product.name || 'ไม่ระบุชื่อ',
                    category: product.category_name || 'ไม่ระบุ',
                    category_id: product.category_id || '',
                    price: parseFloat(product.price) || 0,
                    description: product.description || '',
                    weight: product.weight ? parseFloat(product.weight) : 0,
                    weight_unit: product.weight_unit || 'kg',
                    width: product.width || 0,
                    length: product.length || 0,
                    height: product.height || 0,
                    width_unit: product.width_unit || 'mm',
                    length_unit: product.length_unit || 'mm',
                    height_unit: product.height_unit || 'mm',
                    grade: product.grade || '',
                    unit: product.unit || 'กก.',
                    images: product.images || [],
                    image: product.images && product.images.length > 0
                        ? product.images.find(img => img.is_main)?.image_url || product.images[0].image_url
                        : 'no-image.jpg',
                    date: new Date(product.created_at),
                    lot: product.lot,
                    stock: parseInt(product.stock) || 0,
                    supplier: product.supplier_name || 'ไม่ระบุ',
                    supplier_name: product.supplier_name || 'ไม่ระบุ',
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
            <div class="product-price">฿${product.price.toLocaleString()}</div>
            ${product.weight && product.weight > 0 ? `<div class="product-weight" style="color:#555; font-size:14px; margin-bottom: 10px;">น้ำหนัก: ${product.weight} ${product.weight_unit || 'กก.'} / ชิ้น</div>` : ''}
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
                weight: product.weight || 0,
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

// เพิ่มฟังก์ชันสำหรับกรองราคา
function applyPriceFilter() {
    const minPrice = parseFloat(document.getElementById('minPrice').value) || 0;
    const maxPrice = parseFloat(document.getElementById('maxPrice').value) || Infinity;
    
    console.log(`กรองราคา: ${minPrice} - ${maxPrice}`);
    
    // ตรวจสอบว่าราคาต่ำสุดไม่มากกว่าราคาสูงสุด
    if (minPrice > maxPrice && maxPrice !== Infinity) {
        alert('ราคาต่ำสุดไม่ควรมากกว่าราคาสูงสุด');
        return;
    }
    
    applyAllFilters();
}

// ปรับปรุงฟังก์ชัน applyCurrentFilters ให้รวมการกรองราคา
function applyAllFilters() {
    let filtered = [...allProducts];
    
    /*
    // 1. กรองตามคำค้นหา
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
    */

    // 2. กรองตามหมวดหมู่
    const checkedCategories = Array.from(document.querySelectorAll('input[type="checkbox"]:checked'))
        .map(checkbox => checkbox.value);
    if (checkedCategories.length > 0) {
        filtered = filtered.filter(product =>
            checkedCategories.includes(product.category)
        );
    }

    // 3. กรองตามราคา
    const minPrice = parseFloat(document.getElementById('minPrice').value) || 0;
    const maxPrice = parseFloat(document.getElementById('maxPrice').value) || Infinity;
    
    if (minPrice > 0 || maxPrice < Infinity) {
        filtered = filtered.filter(product => {
            const price = parseFloat(product.price) || 0;
            return price >= minPrice && price <= maxPrice;
        });
    }

    filteredProducts = filtered;
    console.log(`ผลการกรอง: เหลือ ${filteredProducts.length} จาก ${allProducts.length} รายการ`);
    
    // เรียงลำดับและแสดงผล
    applySorting();
}

// แยกฟังก์ชันการเรียงลำดับออกมา
function applySorting() {
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

// อัพเดทฟังก์ชันเดิมให้ใช้ระบบใหม่
function filterByCategory() {
    applyAllFilters();
}

function searchProducts() {
    applyAllFilters();
}

function sortProducts() {
    applySorting();
}

// ปรับปรุง clearAllFilters ให้ล้างช่องราคาด้วย
function clearAllFilters() {
    // ล้างช่องค้นหา
    const searchInput = document.getElementById('searchInput');
    if (searchInput) searchInput.value = '';

    // ล้างหมวดหมู่
    document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        checkbox.checked = false;
    });

    // ล้างช่องราคา
    const minPriceInput = document.getElementById('minPrice');
    const maxPriceInput = document.getElementById('maxPrice');
    if (minPriceInput) minPriceInput.value = '';
    if (maxPriceInput) maxPriceInput.value = '';

    // รีเซ็ตการเรียงลำดับ
    const sortSelect = document.getElementById('sortSelect');
    if (sortSelect) sortSelect.value = 'latest';
    currentSort = 'latest';

    // แสดงสินค้าทั้งหมด
    filteredProducts = [...allProducts];
    applySorting();
}

// เพิ่ม Event Listeners สำหรับช่องราคา (กดปุ่มเท่านั้น)
function setupPriceFilterListeners() {
    const minPriceInput = document.getElementById('minPrice');
    const maxPriceInput = document.getElementById('maxPrice');
    
    // ให้กด Enter ได้ในช่องราคา
    if (minPriceInput) {
        minPriceInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyPriceFilter();
            }
        });
    }
    
    if (maxPriceInput) {
        maxPriceInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyPriceFilter();
            }
        });
    }
    
    // Event listener สำหรับปุ่ม "ใช้ตัวกรอง"
    const priceFilterBtn = document.querySelector('.filter-apply-btn');
    if (priceFilterBtn) {
        priceFilterBtn.addEventListener('click', applyPriceFilter);
    }
}

// อัพเดท DOMContentLoaded event listener
document.addEventListener('DOMContentLoaded', function () {
    console.log("=== DOM Content Loaded ===");
    console.log("Current URL:", window.location.href);
    console.log("Document ready state:", document.readyState);

    // ตั้งค่า Event Listeners
    setupPriceFilterListeners();

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

// เพิ่มฟังก์ชันสำหรับใช้ preset ราคา (ถ้าต้องการ)
function applyPricePreset(min, max) {
    const minPriceInput = document.getElementById('minPrice');
    const maxPriceInput = document.getElementById('maxPrice');
    
    if (minPriceInput) minPriceInput.value = min || '';
    if (maxPriceInput) maxPriceInput.value = max || '';
    
    applyPriceFilter();
}

// ฟังก์ชันสำหรับแสดงราคาในรูปแบบที่อ่านง่าย
function formatPrice(price) {
    return new Intl.NumberFormat('th-TH', {
        style: 'currency',
        currency: 'THB',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    }).format(price);
}

// เพิ่มฟังก์ชันตรวจสอบว่ามีตัวกรองใดทำงานอยู่หรือไม่
function hasActiveFilters() {
    const searchInput = document.getElementById('searchInput');
    const minPrice = document.getElementById('minPrice');
    const maxPrice = document.getElementById('maxPrice');
    const checkedCategories = document.querySelectorAll('input[type="checkbox"]:checked');
    
    return (
        (searchInput && searchInput.value.trim()) ||
        (minPrice && minPrice.value) ||
        (maxPrice && maxPrice.value) ||
        checkedCategories.length > 0
    );
}


// ฟังก์ชันตรวจสอบว่าข้อความมีคำค้นหาหรือไม่
function containsSearchTerms(text, searchTokens) {
    if (!text || !searchTokens || searchTokens.length === 0) return false;
    
    const normalizedText = normalizeSearchText(text);
    
    // ตรวจสอบว่าทุกคำค้นหามีอยู่ในข้อความหรือไม่
    return searchTokens.every(token => normalizedText.includes(token));
}

// ฟังก์ชันคำนวณคะแนนความเกี่ยวข้อง
function calculateRelevanceScore(product, searchTokens) {
    let score = 0;
    
    // น้ำหนักการให้คะแนน
    const weights = {
        name: 10,        // ชื่อสินค้าสำคัญที่สุด
        category: 5,     // หมวดหมู่
        description: 3,  // คำอธิบาย
        supplier: 2,     // ผู้จัดจำหน่าย
        grade: 2         // เกรด
    };
    
    searchTokens.forEach(token => {
        // ตรวจสอบในชื่อสินค้า
        if (containsSearchTerms(product.name, [token])) {
            score += weights.name;
            // ให้คะแนนเพิ่มถ้าตรงกันตั้งแต่ตัวแรก
            if (normalizeSearchText(product.name).startsWith(token)) {
                score += 5;
            }
        }
        
        // ตรวจสอบในหมวดหมู่
        if (containsSearchTerms(product.category, [token])) {
            score += weights.category;
        }
        
        // ตรวจสอบในคำอธิบาย
        if (containsSearchTerms(product.description, [token])) {
            score += weights.description;
        }
        
        // ตรวจสอบในผู้จัดจำหน่าย
        if (containsSearchTerms(product.supplier, [token])) {
            score += weights.supplier;
        }
        
        // ตรวจสอบในเกรด
        if (containsSearchTerms(product.grade, [token])) {
            score += weights.grade;
        }
    });
    
    return score;
}

// ฟังก์ชันปรับปรุงการค้นหาให้รองรับไทยและอังกฤษได้ดีขึ้น
function normalizeSearchText(text) {
    if (!text) return '';
    
    return text
        .toLowerCase()
        .trim()
        // ลบช่องว่างส่วนเกิน
        .replace(/\s+/g, ' ')
        // ลบเครื่องหมายพิเศษ
        .replace(/[^\w\s\u0E00-\u0E7F]/g, '');
}

// ฟังก์ชันแยกคำค้นหา
function tokenizeSearch(searchText) {
    if (!searchText) return [];
    
    const normalized = normalizeSearchText(searchText);
    // แยกคำด้วยช่องว่าง
    const tokens = normalized.split(' ').filter(token => token.length > 0);
    
    return tokens;
}

// ฟังก์ชันตรวจสอบว่าข้อความมีคำค้นหาหรือไม่
function containsSearchTerms(text, searchTokens) {
    if (!text || !searchTokens || searchTokens.length === 0) return false;
    
    const normalizedText = normalizeSearchText(text);
    
    // ตรวจสอบว่าทุกคำค้นหามีอยู่ในข้อความหรือไม่
    return searchTokens.every(token => normalizedText.includes(token));
}

// ฟังก์ชันคำนวณคะแนนความเกี่ยวข้อง
function calculateRelevanceScore(product, searchTokens) {
    let score = 0;
    
    // น้ำหนักการให้คะแนน
    const weights = {
        name: 10,        // ชื่อสินค้าสำคัญที่สุด
        category: 5,     // หมวดหมู่
        description: 3,  // คำอธิบาย
        supplier: 2,     // ผู้จัดจำหน่าย
        grade: 2         // เกรด
    };
    
    searchTokens.forEach(token => {
        // ตรวจสอบในชื่อสินค้า
        if (containsSearchTerms(product.name, [token])) {
            score += weights.name;
            // ให้คะแนนเพิ่มถ้าตรงกันตั้งแต่ตัวแรก
            if (normalizeSearchText(product.name).startsWith(token)) {
                score += 5;
            }
        }
        
        // ตรวจสอบในหมวดหมู่
        if (containsSearchTerms(product.category, [token])) {
            score += weights.category;
        }
        
        // ตรวจสอบในคำอธิบาย
        if (containsSearchTerms(product.description, [token])) {
            score += weights.description;
        }
        
        // ตรวจสอบในผู้จัดจำหน่าย
        if (containsSearchTerms(product.supplier, [token])) {
            score += weights.supplier;
        }
        
        // ตรวจสอบในเกรด
        if (containsSearchTerms(product.grade, [token])) {
            score += weights.grade;
        }
    });
    
    return score;
}

// 1. ปรับปรุงฟังก์ชัน normalizeSearchText ให้รองรับภาษาไทยและอังกฤษได้ดีขึ้น
function normalizeSearchText(text) {
    if (!text) return '';
    
    return text
        .toLowerCase()
        .trim()
        // ลบช่องว่างส่วนเกิน
        .replace(/\s+/g, ' ')
        // ลบเครื่องหมายพิเศษ แต่คงไวยากรณ์ไทย
        .replace(/[^\w\s\u0E00-\u0E7F\u0E80-\u0EFF]/g, '')
        // แปลงตัวอักษรไทยที่คล้ายกัน
        .replace(/[กข]/g, 'ก')
        .replace(/[คฆ]/g, 'ค')
        .replace(/[ฎด]/g, 'ด')
        .replace(/[ตท]/g, 'ต')
        .replace(/[บป]/g, 'ป')
        .replace(/[ผพฟ]/g, 'พ')
        .replace(/[สศษ]/g, 'ส')
        .replace(/[หฮ]/g, 'ห')
        // แปลงสระที่คล้ายกัน
        .replace(/[ำาๅ]/g, 'า');
}

// 2. เพิ่มฟังก์ชันตรวจจับการพิมพ์ผิดภาษา (Eng-Thai keyboard)
function detectTypingLanguage(text) {
    const thaiChars = (text.match(/[\u0E00-\u0E7F]/g) || []).length;
    const engChars = (text.match(/[a-zA-Z]/g) || []).length;
    const totalChars = thaiChars + engChars;
    
    if (totalChars === 0) return 'mixed';
    
    return (thaiChars / totalChars) > 0.5 ? 'thai' : 'english';
}

// 3. เพิ่มการแปลงคีย์บอร์ดไทย-อังกฤษ
function convertKeyboard(text) {
    // แมป QWERTY -> ไทย
    const qwertyToThai = {
        'q': 'ๆ', 'w': 'ไ', 'e': 'ำ', 'r': 'พ', 't': 'ะ', 'y': 'ั', 'u': 'ี', 'i': 'ร', 'o': 'น', 'p': 'ย',
        'a': 'ฟ', 's': 'ห', 'd': 'ก', 'f': 'ด', 'g': 'เ', 'h': '้', 'j': '่', 'k': 'า', 'l': 'ส',
        'z': 'ผ', 'x': 'ป', 'c': 'แ', 'v': 'อ', 'b': 'ิ', 'n': 'ื', 'm': 'ท'
    };
    
    // แมป ไทย -> QWERTY
    const thaiToQwerty = {};
    Object.keys(qwertyToThai).forEach(key => {
        thaiToQwerty[qwertyToThai[key]] = key;
    });
    
    const converted = [];
    
    // แปลง Eng -> Thai
    const engToThai = text.replace(/[a-z]/g, char => qwertyToThai[char] || char);
    if (engToThai !== text) converted.push(engToThai);
    
    // แปลง Thai -> Eng
    const thaiToEng = text.replace(/[\u0E00-\u0E7F]/g, char => thaiToQwerty[char] || char);
    if (thaiToEng !== text) converted.push(thaiToEng);
    
    return converted;
}

// 4. เพิ่มฟังก์ชันสำหรับ fuzzy matching
function calculateStringDistance(str1, str2) {
    const len1 = str1.length;
    const len2 = str2.length;
    const matrix = [];
    
    for (let i = 0; i <= len2; i++) {
        matrix[i] = [i];
    }
    
    for (let j = 0; j <= len1; j++) {
        matrix[0][j] = j;
    }
    
    for (let i = 1; i <= len2; i++) {
        for (let j = 1; j <= len1; j++) {
            if (str2.charAt(i - 1) === str1.charAt(j - 1)) {
                matrix[i][j] = matrix[i - 1][j - 1];
            } else {
                matrix[i][j] = Math.min(
                    matrix[i - 1][j - 1] + 1,
                    matrix[i][j - 1] + 1,
                    matrix[i - 1][j] + 1
                );
            }
        }
    }
    
    return matrix[len2][len1];
}

// 5. ปรับปรุงฟังก์ชันค้นหาหลัก
function advancedSearch(products, searchTerm) {
    if (!searchTerm || searchTerm.trim() === '') {
        return products;
    }
    
    const normalizedSearch = normalizeSearchText(searchTerm);
    const keyboardVariants = convertKeyboard(normalizedSearch);
    
    console.log('=== Advanced Search Debug ===');
    console.log('Original search:', searchTerm);
    console.log('Normalized search:', normalizedSearch);
    console.log('Keyboard variants:', keyboardVariants);
    
    return products.filter(product => {
        const productName = normalizeSearchText(product.name || '');
        const productCategory = normalizeSearchText(product.category || '');
        const productDescription = normalizeSearchText(product.description || '');
        const productSupplier = normalizeSearchText(product.supplier || '');
        const productGrade = normalizeSearchText(product.grade || '');
        
        // สร้างรายการข้อความที่จะค้นหา
        const searchableTexts = [productName, productCategory, productDescription, productSupplier, productGrade];
        const allSearchTerms = [normalizedSearch, ...keyboardVariants];
        
        // 1. ตรวจสอบการตรงกันแบบตรง
        const exactMatch = allSearchTerms.some(term =>
            searchableTexts.some(text =>
                text.includes(term) || text.split(' ').some(word => word.includes(term))
            )
        );
        
        if (exactMatch) {
            console.log(`✓ Exact match: "${product.name}"`);
            return true;
        }
        
        // 2. ตรวจสอบการค้นหาแบบเริ่มต้นคำ
        const wordStartMatch = allSearchTerms.some(term =>
            searchableTexts.some(text =>
                text.split(' ').some(word => word.startsWith(term))
            )
        );
        
        if (wordStartMatch) {
            console.log(`✓ Word start match: "${product.name}"`);
            return true;
        }
        
        // 3. ตรวจสอบการค้นหาแบบ fuzzy (สำหรับคำยาว > 3 ตัวอักษร)
        if (searchTerm.length > 3) {
            const fuzzyMatch = allSearchTerms.some(term =>
                searchableTexts.some(text => {
                    const words = text.split(' ');
                    return words.some(word => {
                        if (word.length < 3) return false;
                        const distance = calculateStringDistance(term, word);
                        const threshold = Math.floor(word.length * 0.3); // ยอมให้ผิด 30%
                        return distance <= threshold;
                    });
                })
            );
            
            if (fuzzyMatch) {
                console.log(`✓ Fuzzy match: "${product.name}"`);
                return true;
            }
        }
        
        // 4. ตรวจสอบการค้นหาแบบย่อย (abbreviation)
        if (searchTerm.length <= 3) {
            const abbreviationMatch = searchableTexts.some(text => {
                const words = text.split(' ');
                if (words.length >= searchTerm.length) {
                    const firstLetters = words.slice(0, searchTerm.length)
                        .map(word => word.charAt(0))
                        .join('');
                    return firstLetters === normalizedSearch;
                }
                return false;
            });
            
            if (abbreviationMatch) {
                console.log(`✓ Abbreviation match: "${product.name}"`);
                return true;
            }
        }
        
        return false;
    });
}

// 6. อัพเดทฟังก์ชัน applyAllFilters ให้ใช้ระบบค้นหาใหม่
function applyAllFilters() {
    let filtered = [...allProducts];
    
    console.log('=== Enhanced Search Debug ===');
    console.log('Total products:', allProducts.length);
    
    // 1. กรองตามคำค้นหา (ใช้ระบบใหม่)
    const searchInput = document.getElementById('searchInput');
    const searchTerm = searchInput ? searchInput.value.trim() : '';
    
    if (searchTerm && searchTerm.length > 0) {
        filtered = advancedSearch(filtered, searchTerm);
        console.log(`After advanced search: ${filtered.length} items found`);
    }

    // 2. กรองตามหมวดหมู่
    const checkedCategories = Array.from(document.querySelectorAll('input[type="checkbox"]:checked'))
        .map(checkbox => checkbox.value);
    if (checkedCategories.length > 0) {
        filtered = filtered.filter(product =>
            checkedCategories.includes(product.category)
        );
        console.log(`After category filter: ${filtered.length} items`);
    }

    // 3. กรองตามราคา
    const minPrice = parseFloat(document.getElementById('minPrice')?.value) || 0;
    const maxPrice = parseFloat(document.getElementById('maxPrice')?.value) || Infinity;
    
    if (minPrice > 0 || maxPrice < Infinity) {
        filtered = filtered.filter(product => {
            const price = parseFloat(product.price) || 0;
            return price >= minPrice && price <= maxPrice;
        });
        console.log(`After price filter: ${filtered.length} items`);
    }

    filteredProducts = filtered;
    console.log(`Final filtered products: ${filteredProducts.length} จาก ${allProducts.length} รายการ`);
    
    // แสดงผลแบบเรียงลำดับตามความเกี่ยวข้องถ้ามีการค้นหา
    if (searchTerm) {
        displayProducts(filteredProducts);
    } else {
        applySorting();
    }
}

// 7. เพิ่มฟังก์ชันให้คำแนะนำเมื่อไม่พบผลการค้นหา
function showSearchSuggestions(originalTerm) {
    const suggestions = [];
    
    // หาคำที่คล้ายกัน
    allProducts.forEach(product => {
        const productWords = normalizeSearchText(product.name).split(' ');
        productWords.forEach(word => {
            if (word.length > 2 && calculateStringDistance(normalizeSearchText(originalTerm), word) <= 2) {
                if (!suggestions.includes(word) && suggestions.length < 5) {
                    suggestions.push(word);
                }
            }
        });
    });
    
    // หาหมวดหมู่ที่คล้ายกัน
    const categories = [...new Set(allProducts.map(p => p.category))];
    categories.forEach(category => {
        const normalizedCategory = normalizeSearchText(category);
        if (normalizedCategory.includes(normalizeSearchText(originalTerm)) && !suggestions.includes(category)) {
            suggestions.push(category);
        }
    });
    
    return suggestions;
}

// 8. ปรับปรุงฟังก์ชัน showNoProductsMessage ให้แสดงคำแนะนำ
function showNoProductsMessage(message, searchTerm = '') {
    const grid = document.getElementById('productsGrid');
    if (!grid) return;
    
    let content = `
        <div class="no-products" style="text-align: center; padding: 40px; grid-column: 1 / -1;">
            <h3 style="color: #666; margin-bottom: 10px;">${message}</h3>
    `;
    
    if (searchTerm) {
        const suggestions = showSearchSuggestions(searchTerm);
        const keyboardVariants = convertKeyboard(normalizeSearchText(searchTerm));
        
        if (suggestions.length > 0) {
            content += `
                <div style="margin: 20px 0;">
                    <p style="color: #888; margin-bottom: 10px;">คำแนะนำการค้นหา:</p>
                    <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                        ${suggestions.map(suggestion => `
                            <button onclick="searchSuggestion('${suggestion}')" 
                                    style="padding: 5px 15px; border: 1px solid #ddd; background: #f8f9fa; 
                                           border-radius: 20px; cursor: pointer; color: #007bff;">
                                ${suggestion}
                            </button>
                        `).join('')}
                    </div>
                </div>
            `;
        }
        
        if (keyboardVariants.length > 0) {
            content += `
                <div style="margin: 20px 0;">
                    <p style="color: #888; margin-bottom: 10px;">หรือคุณหมายถึง:</p>
                    <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                        ${keyboardVariants.map(variant => `
                            <button onclick="searchSuggestion('${variant}')" 
                                    style="padding: 5px 15px; border: 1px solid #ddd; background: #fff3cd; 
                                           border-radius: 20px; cursor: pointer; color: #856404;">
                                ${variant}
                            </button>
                        `).join('')}
                    </div>
                </div>
            `;
        }
    }
    
    content += `
            <p style="color: #888;">กรุณาลองใช้คำค้นหาอื่น หรือเปลี่ยนตัวกรอง</p>
        </div>
    `;
    
    grid.innerHTML = content;
}

// 9. เพิ่มฟังก์ชันสำหรับคลิกคำแนะนำ
function searchSuggestion(suggestion) {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.value = suggestion;
        applyAllFilters();
    }
}

// 10. ปรับปรุง event listener สำหรับการค้นหาแบบ manual เท่านั้น
function setupAdvancedSearchListeners() {
    const searchInput = document.getElementById('searchInput');
    if (!searchInput) return;
    
    // ค้นหาเฉพาะเมื่อกด Enter เท่านั้น
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault(); // ป้องกันการ submit form
            applyAllFilters();
        }
    });
    
    // หากมีปุ่มค้นหา ให้เพิ่ม event listener
    const searchButton = document.getElementById('searchButton') || document.querySelector('[onclick="searchProducts()"]');
    if (searchButton) {
        searchButton.addEventListener('click', function(e) {
            e.preventDefault();
            applyAllFilters();
        });
    }
    
    // ล้างผลการค้นหาเมื่อลบข้อความหมด (กด Enter หรือคลิกปุ่มค้นหา)
    // ไม่ทำอะไรอัตโนมัติ ต้องกดค้นหาเสมอ
    console.log('✅ Advanced search listeners setup (manual search only)');
}

// ฟังก์ชันสำหรับปุ่มค้นหา (ใช้แทน searchProducts เดิม)
function searchProducts() {
    console.log('🔍 Manual search triggered');
    applyAllFilters();
}

// เรียกใช้งานเมื่อ DOM โหลดเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    setupAdvancedSearchListeners();
});