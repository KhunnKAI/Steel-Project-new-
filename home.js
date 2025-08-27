// ฟังก์ชันสำหรับไปหน้าสินค้า - ปรับปรุงให้แสดง debug info มากขึ้น
function viewProduct(productId) {
    console.log('=== viewProduct Debug Info ===');
    console.log('viewProduct called with ID:', productId, typeof productId);
    console.log('Current URL:', window.location.href);

    // ตรวจสอบว่า productId ถูกต้อง
    if (!productId || productId === 'null' || productId === 'undefined' || productId.toString().trim() === '') {
        console.error('Product ID is invalid:', productId);
        alert('ไม่พบรหัสสินค้า กรุณาลองใหม่อีกครั้ง');
        return;
    }

    // แปลงเป็น string เพื่อความปลอดภัย
    const cleanProductId = String(productId).trim();
    console.log('Clean product ID:', cleanProductId);

    // Debug: แสดงข้อมูลสินค้าปัจจุบัน
    console.log('Current products array:', allProducts.map(p => ({ id: p.id, name: p.name })));

    // ค้นหาสินค้าใน array เพื่อยืนยันว่ามีข้อมูล
    const foundProduct = allProducts.find(p => String(p.id) === cleanProductId);
    console.log('Found product in array:', foundProduct);

    if (!foundProduct) {
        console.error('Product not found in current data');
        alert('ไม่พบข้อมูลสินค้า กรุณาโหลดหน้าใหม่');
        return;
    }

    // Debug: แสดง URL ที่จะไป
    const targetUrl = `product.php?id=${encodeURIComponent(cleanProductId)}`;
    console.log('Target URL:', targetUrl);

    // เพิ่ม debug parameter ถ้าอยู่ใน debug mode
    const isDebugMode = window.location.search.includes('debug=true');
    const finalUrl = isDebugMode ? `${targetUrl}&debug=true` : targetUrl;
    console.log('Final URL with debug:', finalUrl);

    try {
        console.log('Attempting to navigate to:', finalUrl);

        // นำทางไปหน้าสินค้าโดยตรง - ไม่ต้องตรวจสอบล็อกอิน
        window.location.href = finalUrl;

    } catch (error) {
        console.error('Navigation error:', error);
        alert('เกิดข้อผิดพลาดในการเปิดหน้าสินค้า: ' + error.message);
    }
}

// ปรับปรุงฟังก์ชัน addProductEventListeners ให้ใช้ data-product-id แทน index
function addProductEventListeners(productsToShow) {
    const productCards = document.querySelectorAll('.product-card');

    console.log('=== Event Listeners Debug ===');
    console.log(`Adding event listeners to ${productCards.length} product cards`);
    console.log('Products to show count:', productsToShow.length);

    productCards.forEach((card, index) => {
        const cardProductId = card.getAttribute('data-product-id');

        // หา product จาก productsToShow โดยใช้ productId
        const product = productsToShow.find(p => String(p.id) === String(cardProductId));

        console.log(`Setting up card ${index}:`, {
            productId: product?.id,
            productName: product?.name,
            cardDataId: cardProductId,
            cardElement: card
        });

        // Event listener สำหรับคลิกที่ card (ไปหน้าสินค้า)
        card.addEventListener('click', function (event) {
            console.log('=== Card Click Event ===');
            console.log('Card clicked:', index, product?.name);
            console.log('Event target:', event.target);
            console.log('Clicked element classes:', event.target.classList.toString());

            // ถ้าคลิกที่ปุ่ม ให้หยุดการทำงาน
            if (event.target.classList.contains('product-btn') ||
                event.target.closest('.product-btn')) {
                console.log('Button clicked, stopping card navigation');
                return;
            }

            if (product && product.id) {
                console.log('Product card clicked - calling viewProduct:', {
                    productId: product.id,
                    productName: product.name
                });
                viewProduct(product.id);
            } else {
                console.error('Product data not found!', {
                    cardDataId: cardProductId,
                    productsArray: productsToShow
                });
                alert('เกิดข้อผิดพลาด: ไม่พบข้อมูลสินค้า กรุณาลองใหม่อีกครั้ง');
            }
        });

        // Event listener สำหรับปุ่มเพิ่มในตะกร้า
        const addToCartBtn = card.querySelector('.product-btn');
        if (addToCartBtn) {
            addToCartBtn.addEventListener('click', function (event) {
                event.stopPropagation();
                event.preventDefault();

                console.log('Add to cart button clicked for product:', product?.name);

                if (product) {
                    if (checkLoginStatus()) {
                        addToCart(product.name, product.id);
                    } else {
                        alert('กรุณาเข้าสู่ระบบก่อนเพิ่มสินค้าลงตะกร้า');
                        window.location.href = 'login.php';
                    }
                } else {
                    console.error('Product not found for cart addition:', cardProductId);
                }
            });
        }
    });

    console.log(`Successfully added event listeners to ${productCards.length} product cards`);
}


// ปรับปรุงฟังก์ชันตรวจสอบสถานะการล็อกอิน
function checkLoginStatus() {
    // ตรวจสอบจาก session/cookie/localStorage/DOM elements
    const loginChecks = [
        // ตรวจสอบจาก PHP session (ถ้ามี element ที่บอกสถานะ)
        document.querySelector('.user-info'),
        document.querySelector('.logout-btn'),
        document.querySelector('[data-user-id]'),
        document.querySelector('.user-name'),

        // ตรวจสอบจาก body class
        document.body.classList.contains('logged-in'),
        document.body.dataset.loggedIn === 'true',

        // ตรวจสอบจาก localStorage
        localStorage.getItem('user_logged_in') === 'true',
        localStorage.getItem('user_id'),

        // ตรวจสอบจาก cookie (basic check)
        document.cookie.includes('user_session='),
        document.cookie.includes('logged_in=true'),

        // ตรวจสอบจาก hidden input หรือ meta tag
        document.querySelector('meta[name="user-logged-in"]')?.content === 'true',
        document.querySelector('input[name="logged_in"]')?.value === 'true'
    ];

    // นับจำนวนการตรวจสอบที่เป็น true
    const positiveChecks = loginChecks.filter(check => !!check).length;
    const isLoggedIn = positiveChecks > 0;

    console.log('Login status checks:', {
        userInfo: !!document.querySelector('.user-info'),
        logoutBtn: !!document.querySelector('.logout-btn'),
        userIdAttr: !!document.querySelector('[data-user-id]'),
        bodyClass: document.body.classList.contains('logged-in'),
        bodyDataset: document.body.dataset.loggedIn,
        localStorage: localStorage.getItem('user_logged_in'),
        cookieCheck: document.cookie.includes('user_session'),
        totalPositive: positiveChecks,
        finalResult: isLoggedIn
    });

    return isLoggedIn;
}

// ปรับปรุง fetchProducts เพื่อเพิ่ม debug info
async function fetchProducts() {
    try {
        console.log('=== fetchProducts Debug ===');
        console.log('Current URL:', window.location.href);
        console.log('Login status:', checkLoginStatus());
        console.log('API URL:', 'controllers/product_home.php');

        const response = await fetch('controllers/product_home.php');
        console.log('API Response status:', response.status);
        console.log('API Response ok:', response.ok);

        if (!response.ok) {
            console.error('HTTP error details:', {
                status: response.status,
                statusText: response.statusText,
                url: response.url
            });
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const contentType = response.headers.get('content-type');
        console.log('Response content-type:', contentType);

        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Non-JSON Response received:', text.substring(0, 500));
            throw new Error('Response is not valid JSON');
        }

        const result = await response.json();
        console.log('API Response data:', result);

        if (result.success && result.data) {
            let products = result.data;

            if (result.data.products) {
                products = result.data.products;
            }

            console.log('Products received:', products.length);

            // ตรวจสอบและทำความสะอาดข้อมูล product ID
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
                };
            });

            // กรองสินค้าที่ไม่มี ID ออก
            const originalCount = allProducts.length;
            allProducts = allProducts.filter(product => product.id && product.id !== '');
            const filteredCount = allProducts.length;

            if (originalCount !== filteredCount) {
                console.warn(`Filtered out ${originalCount - filteredCount} products without valid IDs`);
            }

            filteredProducts = [...allProducts];
            displayProducts(filteredProducts);
            updateCategoryFilter();

            console.log(`Successfully loaded ${allProducts.length} products`);
            console.log('Sample product IDs:', allProducts.slice(0, 3).map(p => ({ id: p.id, name: p.name })));

        } else {
            console.error('API returned error:', result.message);
            showNoProductsMessage('ไม่สามารถโหลดข้อมูลสินค้าได้: ' + (result.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('=== Fetch Error ===');
        console.error('Error details:', error);
        console.error('Error stack:', error.stack);

        if (error.message.includes('404')) {
            showNoProductsMessage('ไม่พบไฟล์ API (404) - ตรวจสอบ path: controllers/product_home.php');
        } else if (error.message.includes('not valid JSON')) {
            showNoProductsMessage('เซิร์ฟเวอร์ตอบกลับข้อมูลที่ไม่ถูกต้อง (ไม่ใช่ JSON)');
        } else if (error.message.includes('403') || error.message.includes('401')) {
            showNoProductsMessage('ไม่มีสิทธิ์เข้าถึงข้อมูล - กรุณาล็อกอินก่อน');
        } else {
            showNoProductsMessage('เกิดข้อผิดพลาดในการโหลดข้อมูล: ' + error.message);
        }
    }
}

// เพิ่มฟังก์ชัน debug สำหรับ click events
function debugClickEvents() {
    console.log('=== Debug Click Events ===');

    const cards = document.querySelectorAll('.product-card');
    console.log(`Found ${cards.length} product cards`);

    cards.forEach((card, index) => {
        const productId = card.getAttribute('data-product-id');
        const productIndex = card.getAttribute('data-product-index');
        const hasListener = card.onclick || card.addEventListener.length;

        console.log(`Card ${index}:`, {
            element: card,
            productId: productId,
            productIndex: productIndex,
            hasClickListener: !!hasListener,
            classList: card.classList.toString()
        });

        // ทดสอบคลิก
        card.style.border = '2px dashed red';
        card.title = `Debug: Product ID ${productId}`;
    });
}

// ปรับปรุง Initialize
document.addEventListener('DOMContentLoaded', function () {
    console.log('=== DOM Content Loaded ===');
    console.log('Current page URL:', window.location.href);
    console.log('Document ready state:', document.readyState);

    // ตรวจสอบ debug mode
    const isDebugMode = window.location.search.includes('debug=true');
    console.log('Debug mode:', isDebugMode);

    if (isDebugMode) {
        // เพิ่มปุ่ม debug
        const debugContainer = document.createElement('div');
        debugContainer.style.cssText = 'position: fixed; top: 10px; right: 10px; z-index: 9999; display: flex; flex-direction: column; gap: 5px;';

        const testApiBtn = document.createElement('button');
        testApiBtn.textContent = 'Test API';
        testApiBtn.onclick = async () => {
            try {
                const response = await fetch('controllers/product_home.php');
                console.log('API Test - Status:', response.status);
                const text = await response.text();
                console.log('API Test - Response:', text.substring(0, 500));
                alert(`API Status: ${response.status}\nResponse: ${text.substring(0, 200)}...`);
            } catch (error) {
                console.error('API Test Error:', error);
                alert('API Test Failed: ' + error.message);
            }
        };
        testApiBtn.style.cssText = 'background: red; color: white; padding: 8px; border: none; border-radius: 3px; cursor: pointer; font-size: 12px;';

        const debugClickBtn = document.createElement('button');
        debugClickBtn.textContent = 'Debug Clicks';
        debugClickBtn.onclick = debugClickEvents;
        debugClickBtn.style.cssText = 'background: blue; color: white; padding: 8px; border: none; border-radius: 3px; cursor: pointer; font-size: 12px;';

        const testNavBtn = document.createElement('button');
        testNavBtn.textContent = 'Test Navigation';
        testNavBtn.onclick = () => {
            const testId = '1'; // ใช้ ID ที่มีอยู่จริง
            console.log('Testing navigation with ID:', testId);
            viewProduct(testId);
        };
        testNavBtn.style.cssText = 'background: green; color: white; padding: 8px; border: none; border-radius: 3px; cursor: pointer; font-size: 12px;';

        debugContainer.appendChild(testApiBtn);
        debugContainer.appendChild(debugClickBtn);
        debugContainer.appendChild(testNavBtn);
        document.body.appendChild(debugContainer);
    }

    // โหลดข้อมูลสินค้า
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
        refreshButton.addEventListener('click', fetchProducts);
    }
});