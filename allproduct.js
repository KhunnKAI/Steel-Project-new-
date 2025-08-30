// Global variables
let allProducts = [];
let filteredProducts = [];
let currentSort = 'price_asc';
let currentLimit = 0;
let currentOffset = 0;

// API endpoint
const projectRoot = window.location.pathname.split('/')[1];
const API_ENDPOINTS = [
    `/${projectRoot}/controllers/product_home.php`
];

// Initialize page
document.addEventListener('DOMContentLoaded', function () {
    // ลบ initializeCartManager() ออกเพราะจะใช้ CartManager class แทน
    loadProducts();
    setupEventListeners();
});

// Setup event listeners
function setupEventListeners() {
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
    }

    const categoryCheckboxes = document.querySelectorAll('.category-item input[type="checkbox"]');
    categoryCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', applyFilters);
    });

    const minPrice = document.getElementById('min-price');
    const maxPrice = document.getElementById('max-price');
    if (minPrice) minPrice.addEventListener('change', applyFilters);
    if (maxPrice) maxPrice.addEventListener('change', applyFilters);

    const sortBtn = document.getElementById('sort-btn');
    if (sortBtn) sortBtn.addEventListener('click', toggleSort);
}

// Load products with multiple endpoint attempts
async function loadProducts(limit = 0, offset = 0) {
    showLoading();

    currentLimit = limit;
    currentOffset = offset;

    for (let i = 0; i < API_ENDPOINTS.length; i++) {
        const endpoint = API_ENDPOINTS[i];
        let url = endpoint;

        if (limit > 0) {
            url += `?limit=${limit}&offset=${offset}`;
        }

        try {
            const response = await fetch(url, {
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
                console.error('JSON parse error:', parseError, 'from', text);
                continue;
            }

            if (result && result.success) {
                allProducts = result.data.products || result.data || [];
                filteredProducts = [...allProducts];
                displayProducts();
                hideLoading();
                return;
            } else {
                throw new Error(result.message || 'API returned success: false');
            }

        } catch (error) {
            console.error(`API endpoint ${endpoint} failed:`, error);
            if (i === API_ENDPOINTS.length - 1) {
                showError(`ไม่สามารถเชื่อมต่อ API ได้:<br>${error.message}`);
            }
        }
    }
}

// Display products
function displayProducts() {
    const grid = document.getElementById('products-grid');
    if (!grid) {
        console.error('products-grid element not found');
        return;
    }

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

// Create product card HTML
// แก้ไขฟังก์ชัน createProductCard ใน allproduct.js
function createProductCard(product) {
    const imageUrl = getMainImageUrl(product);
    const dimensions = formatDimensions(product);
    const price = formatPrice(product.price);
    const stockStatus = getStockStatus(product.stock);
    const productDetailUrl = `product.php?id=${product.product_id}`;

    // **แก้ไข: เตรียมข้อมูลสำหรับ addToCart ให้ถูกต้อง**
    const productData = {
        id: product.product_id,
        name: product.name || 'ไม่ระบุชื่อ',
        price: parseFloat(product.price) || 0,
        weight: parseFloat(product.weight) || 0,
        image: imageUrl || 'no-image.jpg',
        stock: parseInt(product.stock) || 0
    };

    return `
        <div class="product-card" onclick="navigateToProduct('${product.product_id}')">
            <div class="product-image">
                ${imageUrl ?
                    `<img src="${imageUrl}" alt="${escapeHtml(product.name)}" 
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
                <div class="product-title">${escapeHtml(product.name) || 'ไม่ระบุชื่อ'}</div>
                <div class="product-specs">${dimensions}</div>
                ${product.lot ? `<div class="product-specs">Lot: ${escapeHtml(product.lot)}</div>` : ''}
                <div class="product-price">${price}</div>
                <div class="product-stock">${stockStatus}</div>
                <div class="action-buttons">
                    <button class="add-to-cart-btn" 
                            ${(product.stock || 0) <= 0 ? 'disabled' : ''} 
                            onclick="event.stopPropagation(); addToCartFromCard(this, ${JSON.stringify(productData).replace(/"/g, '&quot;')})">
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

// Escape HTML to prevent XSS
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Navigate to product detail page
function navigateToProduct(productId) {
    if (productId) {
        window.location.href = `product.php?id=${productId}`;
    }
}

function getMainImageUrl(product) {
    if (product.images && Array.isArray(product.images) && product.images.length > 0) {
        const mainImage = product.images.find(img => img.is_main === 1 || img.is_main === '1');
        let selectedImageUrl = mainImage ? mainImage.image_url : product.images[0].image_url;

        if (selectedImageUrl) {
            if (!selectedImageUrl.startsWith('http') && !selectedImageUrl.startsWith('/')) {
                const projectRoot = window.location.pathname.split('/')[1];
                selectedImageUrl = `/${projectRoot}/admin/controllers/uploads/products/${selectedImageUrl}`;
            }
        }

        return selectedImageUrl;
    }
    return null;
}

// Format dimensions
function formatDimensions(product) {
    let dimensions = [];
    if (product.width) dimensions.push(`กว้าง ${product.width} ${product.width_unit || 'mm'}`);
    if (product.length) dimensions.push(`ยาว ${product.length} ${product.length_unit || 'mm'}`);
    if (product.height) dimensions.push(`สูง ${product.height} ${product.height_unit || 'mm'}`);
    if (product.weight) dimensions.push(`หนัก ${product.weight} ${product.weight_unit || 'kg'}`);
    return dimensions.length > 0 ? dimensions.join(', ') : 'ไม่ระบุขนาด';
}

// Format price
function formatPrice(price) {
    if (!price || price == 0) return 'ราคาสอบถาม';
    return parseFloat(price).toLocaleString('th-TH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }) + ' บาท';
}

// Stock status
function getStockStatus(stock) {
    return (!stock || stock <= 0) ? 'สินค้าหมด' : `คงเหลือ ${stock} ชิ้น`;
}

// Loading & error
function showLoading() {
    const grid = document.getElementById('products-grid');
    if (grid) {
        grid.innerHTML = '<div class="loading">กำลังโหลดข้อมูลสินค้า...</div>';
    }
}

function hideLoading() {
    // Remove loading state if needed
}

function showError(message) {
    const grid = document.getElementById('products-grid');
    if (grid) {
        grid.innerHTML = `<div class="error">${message}</div>`;
    }
}

// Search & filter
function performSearch() {
    applyFilters();
}

function applyFilters() {
    const searchInput = document.getElementById('search-input');
    const minPriceInput = document.getElementById('min-price');
    const maxPriceInput = document.getElementById('max-price');
    
    const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
    const minPrice = minPriceInput ? (parseFloat(minPriceInput.value) || 0) : 0;
    const maxPrice = maxPriceInput ? (parseFloat(maxPriceInput.value) || Infinity) : Infinity;

    const selectedCategories = [];
    const categoryCheckboxes = document.querySelectorAll('.category-item input[type="checkbox"]:checked');
    categoryCheckboxes.forEach(checkbox => selectedCategories.push(checkbox.value));

    filteredProducts = allProducts.filter(product => {
        if (searchTerm && !(product.name || '').toLowerCase().includes(searchTerm)) return false;

        const productPrice = parseFloat(product.price) || 0;
        if (productPrice < minPrice || productPrice > maxPrice) return false;

        if (selectedCategories.length > 0 && !selectedCategories.includes(product.category_id)) return false;

        return true;
    });

    sortProducts();
    displayProducts();
}

// Toggle sort
function toggleSort() {
    const sortOptions = ['price_asc', 'price_desc', 'name_asc', 'name_desc'];
    const currentIndex = sortOptions.indexOf(currentSort);
    currentSort = sortOptions[(currentIndex + 1) % sortOptions.length];

    const sortText = {
        'price_asc': 'ราคา: ต่ำ-สูง',
        'price_desc': 'ราคา: สูง-ต่ำ',
        'name_asc': 'ชื่อ: A-Z',
        'name_desc': 'ชื่อ: Z-A'
    };
    const sortLabel = document.getElementById('sort-text');
    if (sortLabel) sortLabel.textContent = sortText[currentSort];

    sortProducts();
    displayProducts();
}

// Sort products
function sortProducts() {
    filteredProducts.sort((a, b) => {
        switch (currentSort) {
            case 'price_asc': return (parseFloat(a.price) || 0) - (parseFloat(b.price) || 0);
            case 'price_desc': return (parseFloat(b.price) || 0) - (parseFloat(a.price) || 0);
            case 'name_asc': return (a.name || '').localeCompare(b.name || '', 'th');
            case 'name_desc': return (b.name || '').localeCompare(a.name || '', 'th');
            default: return 0;
        }
    });
}

// **เพิ่มฟังก์ชันใหม่สำหรับจัดการการเพิ่มสินค้าจาก Product Card**
async function addToCartFromCard(button, productData) {
    const originalText = button.textContent;
    const originalColor = button.style.backgroundColor;

    if (button.disabled) return;

    button.disabled = true;
    button.textContent = 'กำลังเพิ่ม...';

    try {
        // รอให้ CartManager พร้อม
        if (!window.cartManager) {
            console.warn('CartManager not ready, waiting...');
            
            let attempts = 0;
            while (!window.cartManager && attempts < 30) {
                await new Promise(resolve => setTimeout(resolve, 100));
                attempts++;
            }
            
            if (!window.cartManager) {
                throw new Error('ระบบตะกร้าไม่พร้อมใช้งาน กรุณาโหลดหน้าใหม่');
            }
        }

        console.log('Adding product to cart:', productData);

        // ตรวจสอบ stock
        if (productData.stock <= 0) {
            throw new Error('สินค้าหมดแล้ว');
        }

        // เพิ่มสินค้าลงตะกร้าด้วยข้อมูลที่ถูกต้อง
        const success = window.cartManager.addItem(
            String(productData.id),
            productData.name,
            productData.price,
            1,
            productData.image,
            productData.weight
        );

        if (success) {
            button.textContent = 'เพิ่มแล้ว ✓';
            button.style.backgroundColor = '#27ae60';
            showToast(`เพิ่ม "${productData.name}" ลงในตะกร้าแล้ว!`);
            
            console.log('Successfully added to cart:', {
                id: productData.id,
                name: productData.name,
                price: productData.price,
                weight: productData.weight
            });
        } else {
            throw new Error('ไม่สามารถเพิ่มสินค้าลงตะกร้าได้');
        }

    } catch (error) {
        console.error('Add to cart from card error:', error);
        button.textContent = 'เกิดข้อผิดพลาด';
        button.style.backgroundColor = '#e74c3c';
        showToast(error.message, 'error');
    }

    // คืนค่าปุ่มเป็นปกติหลัง 2 วินาที
    setTimeout(() => {
        button.textContent = originalText;
        button.style.backgroundColor = originalColor || '#2c3e50';
        button.disabled = false;
    }, 2000);
}

// **แก้ไขฟังก์ชัน addToCart เดิมให้ทำงานได้ถูกต้อง**
async function addToCart(productId, event) {
    const button = event.target;
    const originalText = button.textContent;
    const originalColor = button.style.backgroundColor;

    if (button.disabled) return;

    button.disabled = true;
    button.textContent = 'กำลังเพิ่ม...';

    try {
        // รอให้ CartManager พร้อม
        if (!window.cartManager) {
            console.warn('CartManager not ready, waiting...');
            
            let attempts = 0;
            while (!window.cartManager && attempts < 30) {
                await new Promise(resolve => setTimeout(resolve, 100));
                attempts++;
            }
            
            if (!window.cartManager) {
                throw new Error('ระบบตะกร้าไม่พร้อมใช้งาน กรุณาโหลดหน้าใหม่');
            }
        }

        // หาข้อมูลสินค้าใน allProducts ก่อน
        let productData = allProducts.find(p => 
            String(p.product_id) === String(productId) || 
            p.product_id === productId
        );
        
        if (!productData) {
            console.log(`Product ${productId} not found in allProducts, fetching from API...`);
            productData = await fetchProductData(productId);
        }

        if (!productData) {
            throw new Error('ไม่พบข้อมูลสินค้า');
        }

        console.log('Product data for addToCart:', productData);

        // ตรวจสอบ stock
        const stock = parseInt(productData.stock) || 0;
        if (stock <= 0) {
            throw new Error('สินค้าหมดแล้ว');
        }

        // แปลงข้อมูลให้ถูกต้อง
        const price = parseFloat(productData.price) || 0;
        const weight = parseFloat(productData.weight) || 0;
        const name = productData.name || 'ไม่ระบุชื่อ';
        const image = getMainImageUrl(productData) || 'no-image.jpg';

        // เพิ่มสินค้าลงตะกร้า
        const success = window.cartManager.addItem(
            String(productData.product_id),
            name,
            price,
            1,
            image,
            weight
        );

        if (success) {
            button.textContent = 'เพิ่มแล้ว ✓';
            button.style.backgroundColor = '#27ae60';
            showToast(`เพิ่ม "${name}" ลงในตะกร้าแล้ว!`);
            
            console.log(`Successfully added product ${productId}:`, {
                name: name,
                price: price,
                weight: weight
            });
        } else {
            throw new Error('ไม่สามารถเพิ่มสินค้าลงตะกร้าได้');
        }

    } catch (error) {
        console.error('Add to cart error:', error);
        button.textContent = 'เกิดข้อผิดพลาด';
        button.style.backgroundColor = '#e74c3c';
        showToast(error.message, 'error');
    }

    // คืนค่าปุ่มเป็นปกติหลัง 2 วินาที
    setTimeout(() => {
        button.textContent = originalText;
        button.style.backgroundColor = originalColor || '#2c3e50';
        button.disabled = false;
    }, 2000);
}

// **ลบ global function addToCart แบบเก่าออกจาก cart.js**
// และใช้ฟังก์ชันใหม่ที่อยู่ใน allproduct.js แทน

// **เพิ่มฟังก์ชันตรวจสอบข้อมูลสินค้าที่แสดงใน Console**
function debugProductData() {
    console.log('=== Product Data Debug ===');
    console.log('All products loaded:', allProducts.length);
    
    if (allProducts.length > 0) {
        const sample = allProducts[0];
        console.log('Sample product:', {
            id: sample.product_id,
            name: sample.name,
            price: sample.price,
            weight: sample.weight,
            stock: sample.stock,
            images: sample.images ? sample.images.length : 0
        });
    }
    
    console.log('Cart items:', window.cartManager ? window.cartManager.getCartItems() : 'CartManager not ready');
    console.log('========================');
}

// เรียกใช้เมื่อ DOM โหลดเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    // รอให้ข้อมูลโหลดเสร็จแล้วแสดง debug info
    setTimeout(() => {
        debugProductData();
    }, 2000);
});

// เพิ่มคำสั่งสำหรับ debug
window.debugProductData = debugProductData;

// **แก้ไขฟังก์ชัน fetchProductData ให้มั่นใจว่าได้ข้อมูลครบถ้วน**
async function fetchProductData(productId) {
    try {
        const projectRoot = window.location.pathname.split('/')[1];
        const url = `/${projectRoot}/controllers/product_home.php?product_id=${productId}`;
        
        console.log(`Fetching product data from: ${url}`);
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const text = await response.text();
        console.log('Raw API response:', text); // Debug log
        
        let result;
        try {
            result = JSON.parse(text);
        } catch (parseError) {
            console.error('JSON parse error:', parseError, 'Response:', text);
            throw new Error('รูปแบบข้อมูลจากเซิร์ฟเวอร์ไม่ถูกต้อง');
        }

        if (result.success && result.data) {
            console.log('Product data fetched successfully:', result.data);
            
            // **แก้ไข: ตรวจสอบข้อมูลสำคัญก่อนส่งกลับ**
            const product = result.data;
            
            // Validate required fields
            if (!product.product_id) {
                throw new Error('ข้อมูล product_id ไม่ถูกต้อง');
            }
            
            if (!product.name) {
                console.warn('Product name is missing, using default');
                product.name = 'ไม่ระบุชื่อสินค้า';
            }
            
            if (product.price === null || product.price === undefined) {
                console.warn('Product price is missing, setting to 0');
                product.price = 0;
            }
            
            if (product.stock === null || product.stock === undefined) {
                console.warn('Product stock is missing, setting to 0');
                product.stock = 0;
            }

            return product;
        } else {
            throw new Error(result.message || 'ไม่พบข้อมูลสินค้า');
        }

    } catch (error) {
        console.error('Fetch product data error:', error);
        throw error;
    }
}

// **เพิ่มฟังก์ชันตรวจสอบข้อมูลในตะกร้าเทียบกับ database**
async function validateCartItems() {
    console.log('=== Validating Cart Items ===');
    
    if (!window.cartManager) {
        console.error('CartManager not available');
        return;
    }
    
    const cartItems = window.cartManager.getCartItems();
    console.log(`Checking ${cartItems.length} items in cart...`);
    
    for (const item of cartItems) {
        try {
            console.log(`Validating item ${item.id}:`, item);
            
            const freshData = await fetchProductData(item.id);
            
            if (freshData) {
                console.log(`Database data for ${item.id}:`, {
                    name: freshData.name,
                    price: freshData.price,
                    stock: freshData.stock,
                    weight: freshData.weight
                });
                
                // เปรียบเทียบข้อมูล
                if (item.name !== freshData.name) {
                    console.warn(`Name mismatch for ${item.id}: Cart="${item.name}" DB="${freshData.name}"`);
                }
                
                if (Math.abs(item.price - parseFloat(freshData.price)) > 0.01) {
                    console.warn(`Price mismatch for ${item.id}: Cart=${item.price} DB=${freshData.price}`);
                }
                
                if (Math.abs((item.weight || 0) - (parseFloat(freshData.weight) || 0)) > 0.01) {
                    console.warn(`Weight mismatch for ${item.id}: Cart=${item.weight} DB=${freshData.weight}`);
                }
            }
            
        } catch (error) {
            console.error(`Failed to validate item ${item.id}:`, error);
        }
    }
    
    console.log('=== Validation Complete ===');
}

// เพิ่มคำสั่งสำหรับ debug
window.validateCartItems = validateCartItems;

// เพิ่มฟังก์ชันตรวจสอบระบบ
function checkCartSystem() {
    console.log('=== Cart System Check ===');
    console.log('CartManager available:', !!window.cartManager);
    console.log('All products loaded:', allProducts.length);
    console.log('Toast function available:', typeof showToast);
    console.log('Cart items count:', window.cartManager ? window.cartManager.getTotalItems() : 'N/A');
    console.log('========================');
    
    if (!window.cartManager) {
        console.error('CartManager not found! Make sure cart.js is loaded properly.');
        return false;
    }
    
    return true;
}

// เรียกใช้เมื่อ DOM โหลดเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    // รอให้ CartManager พร้อม
    setTimeout(() => {
        checkCartSystem();
    }, 500);
});

// เพิ่มฟังก์ชัน debug สำหรับทดสอบ
window.testAddToCart = function() {
    console.log('Testing add to cart system...');
    
    if (allProducts.length > 0) {
        const testProduct = allProducts[0];
        console.log('Test product:', testProduct);
        
        // สร้าง mock event
        const mockEvent = {
            target: {
                disabled: false,
                textContent: 'ใส่ตะกร้า',
                style: {}
            }
        };
        
        addToCart(testProduct.product_id, mockEvent);
    } else {
        console.error('No products available for testing');
    }
};

// Toast notification system
function showToast(message, type = 'success') {
    const existingToast = document.querySelector('.toast');
    if (existingToast) {
        existingToast.remove();
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    if (!document.querySelector('#toast-styles')) {
        const style = document.createElement('style');
        style.id = 'toast-styles';
        style.textContent = `
            .toast {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                color: white;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                z-index: 10000;
                font-size: 14px;
                max-width: 300px;
                word-wrap: break-word;
                animation: slideInRight 0.3s ease-out;
            }
            .toast-success {
                background: #27ae60;
            }
            .toast-error {
                background: #e74c3c;
            }
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOutRight {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    }
    
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease-in';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, 3000);
}