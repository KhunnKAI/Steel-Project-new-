// ดึง product ID จาก URL
function getProductIdFromURL() {
    const urlParams = new URLSearchParams(window.location.search);
    const id = urlParams.get('id') || urlParams.get('product_id');
    console.log('URL Parameters:', window.location.search);
    console.log('Extracted Product ID:', id);
    return id;
}

// ฟังก์ชันดึงข้อมูลสินค้าจาก API
async function fetchProductDetails(productId) {
    try {
        console.log('Fetching product details for ID:', productId);
        const response = await fetch(`controllers/product_home.php?product_id=${productId}`);

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const responseText = await response.text();
            console.error('Non-JSON Response:', responseText);
            throw new Error('Response is not valid JSON');
        }

        const result = await response.json();
        console.log('API Response:', result);

        if (result.success && result.data) {
            return result.data;
        } else {
            throw new Error(result.message || 'Failed to fetch product');
        }
    } catch (error) {
        console.error('Error fetching product:', error);
        throw error;
    }
}

// ฟังก์ชันสำหรับจัดการ path รูปภาพ - แก้ไขการตรวจสอบ URL
function getImagePath(imageUrl) {
    if (!imageUrl) return null;
    
    console.log('Processing image URL:', imageUrl);
    
    // ถ้า URL มี protocol เต็ม (http/https) ให้ใช้เลย
    if (imageUrl.startsWith('http://') || imageUrl.startsWith('https://')) {
        return imageUrl;
    }
    
    // ถ้าเป็น path สัมพันธ์ที่เริ่มด้วย / ให้ใช้เลย
    if (imageUrl.startsWith('/')) {
        return imageUrl;
    }
    
    // ถ้ามี path admin อยู่แล้วให้ใช้เลย
    if (imageUrl.includes('admin/')) {
        return imageUrl;
    }
    
    // สำหรับกรณีอื่นๆ ให้ใช้เป็น path สัมพันธ์
    const imagePath = imageUrl;
    console.log('Using image path as-is:', imagePath);
    
    return imagePath;
}

// ฟังก์ชันแสดงข้อมูลสินค้า
function displayProductDetails(product) {
    console.log('Displaying product details:', product);

    // อัปเดตชื่อสินค้า
    const titleElement = document.querySelector('.product-title');
    if (titleElement) {
        titleElement.textContent = product.name || 'ไม่ระบุชื่อ';
    }

    // อัปเดตรายละเอียดขนาด
    const specsElement = document.querySelector('.product-specs');
    if (specsElement) {
        const dimensions = [];
        if (product.width && product.width !== '0.00') {
            dimensions.push(`กว้าง ${product.width} ${product.width_unit || 'mm'}`);
        }
        if (product.length && product.length !== '0.00') {
            dimensions.push(`ยาว ${product.length} ${product.length_unit || 'mm'}`);
        }
        if (product.height && product.height !== '0.00') {
            dimensions.push(`สูง ${product.height} ${product.height_unit || 'mm'}`);
        }
        if (product.weight && product.weight !== '0.00') {
            dimensions.push(`หนัก ${product.weight} ${product.weight_unit || 'kg'}`);
        }

        specsElement.innerHTML = dimensions.length > 0 ? dimensions.join(' | ') : 'ไม่ระบุขนาด';
    }

    // อัปเดตคำอธิบายสินค้า
    const descriptionElement = document.querySelector('.product-description');
    if (descriptionElement) {
        descriptionElement.textContent = product.description || 'ไม่มีคำอธิบาย';
    }

    // อัปเดตราคา
    const priceElement = document.querySelector('.product-price');
    if (priceElement) {
        const price = parseFloat(product.price) || 0;
        priceElement.textContent = `฿${price.toLocaleString()} บาท/เส้น`;
    }


    const stockElement = document.querySelector('.product-stock');
    if (stockElement) {
        const stockStatus = product.stock > 0 ? `คงเหลือ ${product.stock} ชิ้น` : 'หมดสต็อค';
        stockElement.innerHTML = `<strong></strong> ${stockStatus}`;
        
        // เปลี่ยนสีตามสถานะสต็อค
        if (product.stock > 0) {
            stockElement.style.color = '#666';
        } else {
            stockElement.style.color = '#e74c3c';
        }
    }

    // แสดงรูปภาพ
    displayProductImages(product.images || []);

    // อัปเดต breadcrumb
    updateBreadcrumb(product.category_name);

    // อัปเดต document title
    document.title = `${product.name} - ช่างเหล็กไทย`;
}

// ฟังก์ชันแสดงรูปภาพสินค้า - เพิ่มการรอ DOM elements
function displayProductImages(images) {
    console.log('Attempting to display images:', images);
    
    const mainImageContainer = document.querySelector('.main-image');
    const thumbnailContainer = document.querySelector('.thumbnail-images');

    console.log('Main image container found:', !!mainImageContainer);
    console.log('Thumbnail container found:', !!thumbnailContainer);

    if (!mainImageContainer || !thumbnailContainer) {
        console.warn('Image containers not found - checking if we need to wait for DOM');
        
        // ตรวจสอบว่า DOM พร้อมหรือยัง
        if (document.readyState !== 'complete') {
            console.log('Document not ready, waiting...');
            window.addEventListener('load', () => displayProductImages(images));
            return;
        }
        
        // หากยัง DOM พร้อมแล้วแต่ยังหา elements ไม่เจอ ให้รอสักครู่
        let retryCount = 0;
        const maxRetries = 10;
        
        const retryFind = () => {
            retryCount++;
            console.log(`Retry attempt ${retryCount}/${maxRetries}`);
            
            const mainImg = document.querySelector('.main-image');
            const thumbImg = document.querySelector('.thumbnail-images');
            
            if (mainImg && thumbImg) {
                console.log('Found containers on retry, proceeding...');
                displayProductImages(images);
            } else if (retryCount < maxRetries) {
                setTimeout(retryFind, 200);
            } else {
                console.error('Failed to find image containers after multiple retries');
                // สร้าง containers ใหม่หากจำเป็น
                createImageContainers();
                setTimeout(() => displayProductImages(images), 100);
            }
        };
        
        setTimeout(retryFind, 200);
        return;
    }

    if (images && images.length > 0) {
        console.log('Processing images:', images);

        // หารูปหลัก (is_main = 1) หรือรูปแรก
        const mainImage = images.find(img => img.is_main == 1) || images[0];
        console.log('Main image selected:', mainImage);

        // แสดงรูปหลัก
        const mainImagePath = getImagePath(mainImage.image_url);
        console.log('Main image path:', mainImagePath);

        if (mainImagePath) {
            mainImageContainer.innerHTML = `
                <img src="${mainImagePath}" alt="รูปสินค้า" 
                     style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;"
                     onload="console.log('Main image loaded successfully')"
                     onerror="handleImageError(this)">
            `;
        } else {
            console.log('No main image path, showing placeholder');
            mainImageContainer.innerHTML = createProductPlaceholder();
        }

        // แสดง thumbnails
        if (images.length > 1) {
            thumbnailContainer.innerHTML = images.map((image, index) => {
                const imagePath = getImagePath(image.image_url);
                const isActive = (image.is_main == 1 || index === 0) ? 'active' : '';
                
                console.log(`Thumbnail ${index}:`, { imagePath, isActive, image });
                
                if (imagePath) {
                    return `
                        <div class="thumbnail ${isActive}" data-image-url="${imagePath}">
                            <img src="${imagePath}" alt="รูปสินค้า ${index + 1}" 
                                 style="width: 100%; height: 100%; object-fit: cover; border-radius: 3px;"
                                 onload="console.log('Thumbnail ${index} loaded')"
                                 onerror="handleThumbnailError(this)">
                        </div>
                    `;
                } else {
                    return `
                        <div class="thumbnail ${isActive}">
                            ${createThumbnailPlaceholder()}
                        </div>
                    `;
                }
            }).join('');

            // เพิ่ม event listeners หลังจากสร้าง HTML
            setTimeout(addThumbnailListeners, 200);
        } else {
            // แสดง thumbnail เดียว
            const singleImagePath = getImagePath(mainImage.image_url);
            if (singleImagePath) {
                thumbnailContainer.innerHTML = `
                    <div class="thumbnail active" data-image-url="${singleImagePath}">
                        <img src="${singleImagePath}" alt="รูปสินค้า" 
                             style="width: 100%; height: 100%; object-fit: cover; border-radius: 3px;"
                             onload="console.log('Single thumbnail loaded')"
                             onerror="handleThumbnailError(this)">
                    </div>
                `;
            } else {
                thumbnailContainer.innerHTML = `
                    <div class="thumbnail active">
                        ${createThumbnailPlaceholder()}
                    </div>
                `;
            }
        }
    } else {
        // ไม่มีรูปภาพ - แสดง placeholder
        console.log('No images found, showing placeholder');
        mainImageContainer.innerHTML = createProductPlaceholder();
        thumbnailContainer.innerHTML = `
            <div class="thumbnail active">
                ${createThumbnailPlaceholder()}
            </div>
        `;
    }
}

// ฟังก์ชันจัดการ error สำหรับรูปภาพหลัก
function handleImageError(imgElement) {
    console.error('Failed to load main image:', imgElement.src);
    imgElement.style.display = 'none';
    imgElement.parentNode.innerHTML = createProductPlaceholder();
}

// ฟังก์ชันจัดการ error สำหรับ thumbnail
function handleThumbnailError(imgElement) {
    console.error('Failed to load thumbnail:', imgElement.src);
    imgElement.style.display = 'none';
    imgElement.parentNode.innerHTML = createThumbnailPlaceholder();
}

// ฟังก์ชันสร้าง image containers หากไม่พบ
function createImageContainers() {
    console.log('Creating image containers...');
    
    const productContainer = document.querySelector('.product-container');
    if (!productContainer) {
        console.error('Product container not found');
        return;
    }
    
    // ตรวจสอบว่ามี product-images div หรือยัง
    let imagesDiv = document.querySelector('.product-images');
    if (!imagesDiv) {
        console.log('Creating product-images div');
        imagesDiv = document.createElement('div');
        imagesDiv.className = 'product-images';
        
        // สร้าง main-image
        const mainImageDiv = document.createElement('div');
        mainImageDiv.className = 'main-image';
        
        // สร้าง thumbnail-images
        const thumbnailDiv = document.createElement('div');
        thumbnailDiv.className = 'thumbnail-images';
        
        imagesDiv.appendChild(mainImageDiv);
        imagesDiv.appendChild(thumbnailDiv);
        
        // เพิ่มเข้าไปใน product-container (ก่อน product-info)
        const productInfo = document.querySelector('.product-info');
        if (productInfo) {
            productContainer.insertBefore(imagesDiv, productInfo);
        } else {
            productContainer.appendChild(imagesDiv);
        }
        
        console.log('Image containers created successfully');
    }
}

// ฟังก์ชันสร้าง placeholder สำหรับรูปภาพหลัก
function createProductPlaceholder() {
    return `<div class="product-image-placeholder" style="
        width: 100%; 
        height: 100%; 
        background: #f5f5f5; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        border-radius: 8px;
        color: #666;
        font-size: 18px;
        text-align: center;
        min-height: 400px;
    ">
        <div>
            <div style="font-size: 64px; color: #ccc; margin-bottom: 15px;">📷</div>
            ไม่มีรูปภาพ
        </div>
    </div>`;
}

// ฟังก์ชันสร้าง placeholder สำหรับ thumbnail
function createThumbnailPlaceholder() {
    return `<div class="thumbnail-placeholder" style="
        width: 100%; 
        height: 100%; 
        background: #f5f5f5; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        border-radius: 3px;
        color: #999;
        min-height: 80px;
    ">
        <div style="font-size: 24px;">📷</div>
    </div>`;
}

// ฟังก์ชันเพิ่ม event listeners สำหรับ thumbnails
function addThumbnailListeners() {
    const thumbnails = document.querySelectorAll('.thumbnail');
    const mainImageContainer = document.querySelector('.main-image');

    console.log(`Adding listeners to ${thumbnails.length} thumbnails`);

    thumbnails.forEach(thumbnail => {
        thumbnail.addEventListener('click', function () {
            // ลบ active class จาก thumbnails ทั้งหมด
            thumbnails.forEach(t => t.classList.remove('active'));
            // เพิ่ม active class ให้ thumbnail ที่คลิก
            this.classList.add('active');

            // เปลี่ยนรูปหลัก
            const imageUrl = this.dataset.imageUrl;
            console.log('Thumbnail clicked, changing to:', imageUrl);
            
            if (imageUrl && mainImageContainer) {
                mainImageContainer.innerHTML = `
                    <img src="${imageUrl}" alt="รูปสินค้า" 
                         style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;"
                         onload="console.log('Main image changed successfully')"
                         onerror="handleImageError(this)">
                `;
            }
        });
    });
}

// ฟังก์ชันอัปเดต breadcrumb
function updateBreadcrumb(categoryName) {
    const breadcrumbElement = document.querySelector('.breadcrumb');
    if (breadcrumbElement && categoryName) {
        breadcrumbElement.innerHTML = `
            < <a href="allproduct.php">กลับไปหน้าสินค้า</a> | ${categoryName}
        `;
    }
}

// ฟังก์ชันแสดงข้อผิดพลาด
function showError(message) {
    console.error('Showing error:', message);
    const container = document.querySelector('.product-container');
    if (container) {
        container.innerHTML = `
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                <h2 style="color: #e74c3c; margin-bottom: 20px;">เกิดข้อผิดพลาด</h2>
                <p style="color: #666; margin-bottom: 20px;">${message}</p>
                <a href="allproduct.php" style="background: #2c3e50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
                    กลับไปหน้าหลัก
                </a>
            </div>
        `;
    }
}

// ฟังก์ชันแสดงสถานะการโหลด
function showLoading() {
    console.log('Showing loading state');
    
    // อัปเดตเฉพาะข้อความใน elements ที่มีอยู่แล้ว
    const titleElement = document.querySelector('.product-title');
    if (titleElement) {
        titleElement.textContent = 'กำลังโหลด...';
    }
    
    const specsElement = document.querySelector('.product-specs');
    if (specsElement) {
        specsElement.textContent = 'กำลังโหลดข้อมูล...';
    }
    
    const descriptionElement = document.querySelector('.product-description');
    if (descriptionElement) {
        descriptionElement.innerHTML = `
            <div style="text-align: center; padding: 20px;">
                <div class="loading-spinner"></div>
                <p style="margin-top: 15px; color: #666;">กำลังโหลดข้อมูลสินค้า...</p>
            </div>
        `;
    }
    
    // แสดง loading ใน main image
    const mainImageContainer = document.querySelector('.main-image');
    if (mainImageContainer) {
        mainImageContainer.innerHTML = `
            <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #666;">
                <div style="text-align: center;">
                    <div class="loading-spinner" style="margin-bottom: 15px;"></div>
                    <p>กำลังโหลดรูปภาพ...</p>
                </div>
            </div>
        `;
    }
}

// เพิ่ม event listeners สำหรับปุ่ม
function addButtonListeners(product) {
    // ปุ่มซื้อ
    const buyButton = document.querySelector('.btn-primary');
    if (buyButton) {
        buyButton.addEventListener('click', function () {
            if (product.stock <= 0) {
                alert('ขออภัย สินค้านี้หมดสต็อค');
                return;
            }
            alert(`กำลังดำเนินการสั่งซื้อ "${product.name}"`);
        });
    }

    // ปุ่มใส่ในตะกร้า
    const cartButton = document.querySelector('.btn-secondary');
    if (cartButton) {
        cartButton.addEventListener('click', async function () {
            if (product.stock <= 0) {
                alert('ขออภัย สินค้านี้หมดสต็อค');
                return;
            }

            // Ensure cart manager is available
            async function ensureCartReady() {
                if (window.cartManager && typeof window.cartManager.addItem === 'function') {
                    return true;
                }
                // try to load cart.js dynamically if missing
                try {
                    await new Promise((resolve, reject) => {
                        const existing = document.querySelector('script[src$="cart.js"]');
                        if (existing) {
                            // wait a tick for it to initialize
                            setTimeout(resolve, 100);
                            return;
                        }
                        const s = document.createElement('script');
                        s.src = 'cart.js';
                        s.onload = resolve;
                        s.onerror = reject;
                        document.body.appendChild(s);
                    });
                    return !!(window.cartManager && typeof window.cartManager.addItem === 'function');
                } catch (e) {
                    console.error('Failed to load cart.js', e);
                    return false;
                }
            }

            const ready = await ensureCartReady();
            if (!ready) {
                alert('ไม่สามารถเพิ่มลงตะกร้าได้ กรุณารีเฟรชหน้าแล้วลองใหม่');
                return;
            }

            const productId = product.product_id || product.id;
            const name = product.name || 'สินค้า';
            const price = parseFloat(product.price) || 0;
            const weight = parseFloat(product.weight) || 0;
            const image = (product.images && product.images.length > 0) ? (product.images.find(i => i.is_main == 1) || product.images[0]).image_url : 'no-image.jpg';
            const imagePath = getImagePath(image);

            const ok = window.cartManager.addItem(productId, name, price, 1, imagePath, weight);
            if (ok) {
                if (typeof window.showToast === 'function') {
                    window.showToast(`เพิ่ม "${name}" ลงตะกร้าแล้ว`);
                } else {
                    alert(`เพิ่ม "${name}" ลงตะกร้าแล้ว`);
                }
            }
        });
    }

    // ปุ่มติดต่อสอบถาม
    const contactButton = document.querySelector('.btn-contact');
    if (contactButton) {
        contactButton.addEventListener('click', function () {
            const message = `สอบถามเกี่ยวกับสินค้า: ${product.name} (รหัส: ${product.product_id})`;
            const phoneNumber = '0123456789';
            const whatsappUrl = `https://wa.me/${phoneNumber}?text=${encodeURIComponent(message)}`;
            window.open(whatsappUrl, '_blank');
        });
    }
}

// ฟังก์ชันทดสอบ API
async function testProductAPI(productId) {
    try {
        console.log('=== Testing API Connection ===');
        console.log('Product ID:', productId);
        console.log('API URL:', `controllers/product_home.php?product_id=${productId}`);
        
        const response = await fetch(`controllers/product_home.php?product_id=${productId}`);
        console.log('Response Status:', response.status);
        console.log('Response OK:', response.ok);
        
        const contentType = response.headers.get('content-type');
        console.log('Content-Type:', contentType);
        
        if (contentType && contentType.includes('application/json')) {
            const result = await response.json();
            console.log('JSON Response:', result);
            
            if (result.success && result.data && result.data.images) {
                console.log('Images found:', result.data.images);
                result.data.images.forEach((img, index) => {
                    console.log(`Image ${index}:`, {
                        url: img.image_url,
                        is_main: img.is_main,
                        processed_url: getImagePath(img.image_url)
                    });
                });
            }
        } else {
            const text = await response.text();
            console.log('Text Response:', text.substring(0, 1000));
        }
    } catch (error) {
        console.error('API Test Error:', error);
    }
}

// ฟังก์ชันหลัก
async function initProductPage() {
    console.log('=== Initializing Product Page ===');

    const productId = getProductIdFromURL();

    if (!productId) {
        console.error('No product ID found in URL');
        showError('ไม่พบรหัสสินค้าที่ต้องการ กรุณาตรวจสอบ URL หรือคลิกจากหน้าสินค้า');
        return;
    }

    console.log('Product ID found:', productId);
    
    // เพิ่มปุ่มทดสอบ API ถ้าเป็น debug mode
    if (window.location.search.includes('debug=true')) {
        const testBtn = document.createElement('button');
        testBtn.textContent = 'Test API';
        testBtn.onclick = () => testProductAPI(productId);
        testBtn.style.cssText = 'position: fixed; top: 10px; right: 10px; z-index: 9999; background: red; color: white; padding: 10px; border: none; border-radius: 5px; cursor: pointer;';
        document.body.appendChild(testBtn);
    }

    showLoading();

    try {
        const product = await fetchProductDetails(productId);
        console.log('Product data received:', product);

        if (!product) {
            throw new Error('ไม่พบข้อมูลสินค้า');
        }

        displayProductDetails(product);
        addButtonListeners(product);

        console.log('Product page initialization completed successfully');

    } catch (error) {
        console.error('Failed to load product:', error);
        showError('ไม่สามารถโหลดข้อมูลสินค้าได้: ' + error.message);
    }
}

// Initialize เมื่อหน้าเว็บโหลดเสร็จ
document.addEventListener('DOMContentLoaded', function () {
    console.log('DOM loaded, starting product page initialization...');
    
    // เพิ่ม CSS สำหรับ loading spinner และ effects
    if (!document.querySelector('#product-styles')) {
        const style = document.createElement('style');
        style.id = 'product-styles';
        style.textContent = `
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            .loading-spinner {
                border: 4px solid #f3f3f3;
                border-top: 4px solid #3498db;
                border-radius: 50%;
                width: 40px;
                height: 40px;
                animation: spin 2s linear infinite;
                margin: 0 auto;
            }
            .thumbnail {
                cursor: pointer;
                transition: all 0.3s ease;
                border: 2px solid transparent;
            }
            .thumbnail:hover {
                border-color: #3498db;
                transform: scale(1.05);
            }
            .thumbnail.active {
                border-color: #2980b9;
                box-shadow: 0 0 10px rgba(52, 152, 219, 0.3);
            }
        `;
        document.head.appendChild(style);
    }
    
    initProductPage();
});

// Error handling
window.addEventListener('error', function (e) {
    console.error('JavaScript Error:', e.error);
});

window.addEventListener('unhandledrejection', function (e) {
    console.error('Unhandled Promise Rejection:', e.reason);
});

// Export functions สำหรับการใช้งานจากภายนอก
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        getProductIdFromURL,
        fetchProductDetails,
        displayProductDetails,
        initProductPage
    };
}