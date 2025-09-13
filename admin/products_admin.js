// Optimized Products Management System
let products = [], categories = [], suppliers = [], currentPage = 1, filteredProducts = [];
let currentEditId = null, currentViewId = null, productImages = [], currentGalleryImages = [], currentImageIndex = 0;
const itemsPerPage = 10;

// API Configuration
const API_BASE = './controllers/';
const ENDPOINTS = {
    products: API_BASE + 'get_product.php',
    addProduct: API_BASE + 'add_product.php',
    manageProduct: API_BASE + 'manage_product.php',
    uploadImage: API_BASE + 'upload_image.php'
};

// Initialize application
async function init() {
    try {
        await Promise.all([loadProducts(), loadCategories(), loadSuppliers()]);
        setupEventListeners();
        setupDragAndDrop();
        setupProductForm();
        renderProducts();
        console.log('System initialized successfully');
    } catch (error) {
        console.error('Initialization failed:', error);
        showNotification('ระบบไม่สามารถเริ่มต้นได้: ' + error.message, 'error');
    }
}

// Load products from API
async function loadProducts() {
    try {
        const response = await fetch(ENDPOINTS.products);
        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const responseText = await response.text();
        if (!responseText.trim()) throw new Error('Empty response');

        const result = JSON.parse(responseText);
        if (!result.success || !result.data) throw new Error(result.message || 'No data');

        const productMap = new Map();
        result.data.forEach(product => {
            const id = product.product_id;
            if (!productMap.has(id)) {
                productMap.set(id, {
                    id, productCode: id, name: product.name || '',
                    description: product.description || '', category: product.category_id || '',
                    categoryName: product.category_name || '', lot: product.lot || '',
                    stock: Number(product.stock) || 0, price: Number(product.price) || 0,
                    receivedDate: product.received_date ? new Date(product.received_date).toISOString().split('T')[0] : null,
                    supplier: product.supplier_id || '', supplierName: product.supplier_name || '',
                    dimensions: {
                        width: { value: Number(product.width) || 0, unit: product.width_unit || 'mm' },
                        length: { value: Number(product.length) || 0, unit: product.length_unit || 'mm' },
                        height: { value: Number(product.height) || 0, unit: product.height_unit || 'mm' },
                        weight: { value: Number(product.weight) || 0, unit: product.weight_unit || 'kg' }
                    },
                    images: []
                });
            }
            if (product.images?.length) {
                const existingProduct = productMap.get(id);
                product.images.forEach(img => {
                    if (img.image_url && !existingProduct.images.includes(img.image_url)) {
                        img.is_main ? existingProduct.images.unshift(img.image_url) : existingProduct.images.push(img.image_url);
                    }
                });
            }
        });

        products = Array.from(productMap.values());
        filteredProducts = [...products];
    } catch (error) {
        console.error('Error loading products:', error);
        showNotification('เกิดข้อผิดพลาดในการโหลดข้อมูลสินค้า: ' + error.message, 'error');
        throw error;
    }
}

// Load static data
function loadCategories() {
    categories = [
        { id: 'rb', name: 'เหล็กเส้น' }, { id: 'sp', name: 'เหล็กแผ่น' },
        { id: 'ss', name: 'เหล็กรูปพรรณ' }, { id: 'wm', name: 'เหล็กตะแกรง/ตาข่าย' },
        { id: 'ot', name: 'อื่น ๆ' }
    ];
    populateDropdown('productCategory', 'categoryFilter', categories, 'เลือกหมวดหมู่', 'ทั้งหมด');
}

function loadSuppliers() {
    suppliers = [
        { id: 'SUP01', name: 'บจก. โอเชียนซัพพลายเออร์ จำกัด (Ocean Supplier)' },
        { id: 'SUP02', name: 'Metallic Corporation Limited (MCC / Metallic Steel Center)' },
        { id: 'SUP03', name: 'Millcon Steel (MILL)' }, { id: 'SUP04', name: 'Navasiam Steel Co., Ltd.' },
        { id: 'SUP05', name: 'กิจไพบูลย์ เมททอล' }, { id: 'SUP06', name: 'Chuephaibul Steel (เชื้อไพบูลย์ สตีล)' }
    ];
    populateDropdown('productSupplier', null, suppliers, 'เลือกซัพพลายเออร์');
}

// Utility functions
function populateDropdown(selectId, filterId, data, placeholder, filterPlaceholder = null) {
    const select = document.getElementById(selectId);
    if (select) {
        select.innerHTML = `<option value="">${placeholder}</option>` +
            data.map(item => `<option value="${item.id}">${item.name}</option>`).join('');
    }
    if (filterId) {
        const filter = document.getElementById(filterId);
        if (filter) {
            filter.innerHTML = `<option value="">${filterPlaceholder || placeholder}</option>` +
                data.map(item => `<option value="${item.id}">${item.name}</option>`).join('');
        }
    }
}

function getCategoryName(id) { return categories.find(c => c.id === id)?.name || id; }
function getSupplierName(id) { return suppliers.find(s => s.id === id)?.name || id; }
function getElementValue(id) { return document.getElementById(id)?.value || ''; }

function formatDate(dateString) {
    if (!dateString) return 'ไม่ระบุ';
    const date = new Date(dateString);
    return isNaN(date.getTime()) ? 'ไม่ระบุ' : date.toLocaleDateString('th-TH', { year: 'numeric', month: 'short', day: 'numeric' });
}

function getStockStatus(stock) {
    if (stock >= 100) return { class: 'high', text: 'เพียงพอ' };
    if (stock >= 50) return { class: 'medium', text: 'ปานกลาง' };
    return { class: 'low', text: 'ต่ำ' };
}

// Image handling
async function handleImageFiles(files) {
    const validExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    const tasks = [];

    for (const file of files) {
        const ext = file.name.split('.').pop().toLowerCase();
        if (!file.type.startsWith('image/') || !validExts.includes(ext)) {
            showNotification(`ไฟล์ ${file.name} ไม่ใช่รูปภาพที่รองรับ`, 'warning');
            continue;
        }
        if (file.size > 5 * 1024 * 1024) {
            showNotification(`ไฟล์ ${file.name} มีขนาดเกิน 5MB`, 'warning');
            continue;
        }

        if (!currentEditId) {
            const reader = new FileReader();
            reader.onload = e => { productImages.push(e.target.result); renderImagePreviews(); };
            reader.readAsDataURL(file);
        } else {
            tasks.push(uploadImageFile(file, currentEditId));
        }
    }

    if (tasks.length > 0) {
        try {
            const results = await Promise.all(tasks);
            const successful = results.filter(r => r.success).length;
            const failed = results.length - successful;

            if (successful > 0) {
                showNotification(`อัปโหลดสำเร็จ ${successful} รูป${failed > 0 ? `, ล้มเหลว ${failed} รูป` : ''}`,
                    failed > 0 ? 'warning' : 'success');
                await loadProducts();
                if (currentEditId) {
                    const product = products.find(p => p.id === currentEditId);
                    if (product) { productImages = [...(product.images || [])]; renderImagePreviews(); }
                }
            }
        } catch (err) {
            console.error('Upload error:', err);
            showNotification('มีบางรูปอัปโหลดไม่สำเร็จ', 'error');
        }
    }
}

async function uploadImageFile(file, productId, isMain = false) {
    try {
        const formData = new FormData();
        formData.append('image', file);
        formData.append('product_id', productId);
        formData.append('is_main', isMain ? '1' : '0');

        const response = await fetch(ENDPOINTS.uploadImage, { method: 'POST', body: formData });
        if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);

        const result = JSON.parse(await response.text());
        if (result.status === 'success' && result.success === true) {
            return { success: true, url: result.url || result.data?.image_url };
        } else {
            throw new Error(result.message || 'อัปโหลดล้มเหลว');
        }
    } catch (error) {
        console.error('Error uploading image:', error);
        return { success: false, error: error.message };
    }
}

function renderImagePreviews() {
    const container = document.getElementById('imagePreviewContainer');
    if (!container) return;

    container.innerHTML = productImages.map((image, index) => `
        <div class="image-preview">
            <img src="${image}" alt="Product Image ${index + 1}" loading="lazy">
            ${index === 0 ? '<div class="main-image-indicator"><i class="fas fa-star"></i> หลัก</div>' : ''}
            <div class="image-preview-overlay">
                <button class="preview-action-btn preview-view-btn" onclick="viewImage(${index})">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="preview-action-btn preview-delete-btn" onclick="removeImage(${index})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `).join('');
}

// Product operations
async function saveProduct(formData) {
    try {
        const endpoint = currentEditId ? ENDPOINTS.manageProduct : ENDPOINTS.addProduct;
        if (currentEditId) {
            formData.product_id = currentEditId;
            formData._method = 'PUT';
        }

        const response = await fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const responseText = await response.text();
        if (!responseText.trim()) {
            // If successful but no response, assume success
            if (response.ok) {
                showNotification(currentEditId ? 'แก้ไขสินค้าเรียบร้อยแล้ว' : 'เพิ่มสินค้าเรียบร้อยแล้ว', 'success');
                closeModal();
                await loadProducts();
                applyFilters();
                return;
            }
            throw new Error('Server ไม่ได้ส่ง response กลับมา');
        }

        const result = JSON.parse(responseText);
        if (result.success || result.status === 'success') {
            const message = currentEditId ? 'แก้ไขสินค้าเรียบร้อยแล้ว' : `เพิ่มสินค้าเรียบร้อยแล้ว (รหัส: ${result.product_id})`;
            showNotification(message, 'success');

            // Handle image uploads for new products
            if (!currentEditId && productImages.length > 0 && result.product_id) {
                for (let i = 0; i < productImages.length; i++) {
                    const image = productImages[i];
                    if (image.startsWith('data:')) {
                        try {
                            const response = await fetch(image);
                            const blob = await response.blob();
                            const file = new File([blob], `image_${i}.jpg`, { type: 'image/jpeg' });
                            await uploadImageFile(file, result.product_id, i === 0);
                        } catch (uploadError) {
                            console.error('Error uploading image:', uploadError);
                        }
                    }
                }
            }

            closeModal();
            await loadProducts();
            applyFilters();
        } else {
            throw new Error(result.message || 'บันทึกข้อมูลล้มเหลว');
        }
    } catch (error) {
        console.error('Error saving product:', error);
        let errorMessage = 'เกิดข้อผิดพลาด: ';
        if (error.message.includes('Failed to fetch')) {
            errorMessage += 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้';
        } else {
            errorMessage += error.message;
        }
        showNotification(errorMessage, 'error');
    }
}

// Updated deleteProduct function with better error handling
async function deleteProduct(id) {
    if (!confirm('คุณแน่ใจหรือไม่ว่าต้องการลบสินค้านี้?')) return;

    try {
        const response = await fetch(ENDPOINTS.manageProduct, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ 
                _method: 'DELETE', 
                product_id: id 
            })
        });

        if (!response.ok) {
            throw new Error(`HTTP Error ${response.status}`);
        }

        const responseText = await response.text();
        
        // Handle empty response (success case)
        if (!responseText.trim()) {
            if (response.ok) {
                showNotification('ลบสินค้าเรียบร้อยแล้ว', 'success');
                await loadProducts();
                applyFilters();
                if (document.getElementById('productViewModal')?.style.display === 'block') {
                    closeViewModal();
                }
                return;
            }
        }

        const result = JSON.parse(responseText);
        
        if (result.success || result.status === 'success') {
            await loadProducts();
            applyFilters();
            showNotification('ลบสินค้าเรียบร้อยแล้ว', 'success');
            
            if (document.getElementById('productViewModal')?.style.display === 'block') {
                closeViewModal();
            }
        } else {
            // Handle specific error cases with better user messages
            let errorMessage = result.message || 'ลบสินค้าล้มเหลว';
            
            if (errorMessage.includes('used in active orders')) {
                errorMessage = 'ไม่สามารถลบสินค้านี้ได้ เนื่องจากมีการใช้งานในออเดอร์ที่ยังไม่เสร็จสิ้น\nสามารถลบได้เมื่อออเดอร์ทั้งหมดจัดส่งเสร็จแล้ว หรือยกเลิกแล้ว';
                
                // Show option to edit instead
                if (confirm('ไม่สามารถลบสินค้านี้ได้เนื่องจากมีออเดอร์ที่ยังไม่เสร็จสิ้น\n\nสามารถลบได้เมื่อ:\n- ออเดอร์ทั้งหมดจัดส่งเสร็จแล้ว (status04)\n- หรือออเดอร์ถูกยกเลิกแล้ว (status05)\n\nต้องการแก้ไขข้อมูลสินค้าแทนหรือไม่?')) {
                    if (document.getElementById('productViewModal')?.style.display === 'block') {
                        closeViewModal();
                    }
                    editProduct(id);
                    return;
                }
            } else if (errorMessage.includes('used in orders')) {
                errorMessage = 'ไม่สามารถลบสินค้านี้ได้ เนื่องจากมีการใช้งานในออเดอร์แล้ว';
            } else if (errorMessage.includes('not found')) {
                errorMessage = 'ไม่พบสินค้าที่ต้องการลบ อาจถูกลบไปแล้ว';
            }
            
            showNotification(errorMessage, 'warning');
        }
        
    } catch (error) {
        console.error('Error deleting product:', error);
        
        let errorMessage = 'เกิดข้อผิดพลาดในการลบสินค้า: ';
        
        if (error.message.includes('Failed to fetch')) {
            errorMessage += 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้';
        } else {
            errorMessage += error.message;
        }
        
        showNotification(errorMessage, 'error');
    }
}

// Rendering functions
function renderProducts() {
    const tbody = document.getElementById('productsTableBody');
    if (!tbody) return;

    try {
        const startIndex = (currentPage - 1) * itemsPerPage;
        const pageProducts = filteredProducts.slice(startIndex, startIndex + itemsPerPage);

        tbody.innerHTML = pageProducts.map(product => {
            const stockStatus = getStockStatus(product.stock);
            const hasImages = product.images?.length > 0;
            const imageCount = hasImages ? product.images.length : 0;
            const supplierName = product.supplierName || getSupplierName(product.supplier) || 'ไม่ระบุ';
            const categoryName = product.categoryName || getCategoryName(product.category) || 'ไม่ระบุ';

            return `
                <tr>
                    <td><span class="product-code">${product.productCode || product.id}</span></td>
                    <td>
                        <div class="product-info">
                            <div class="product-image-cell" ${hasImages ? `onclick="viewProductImages('${product.id}')" style="cursor: pointer;"` : ''}>
                                ${hasImages ?
                    `<img src="${product.images[0]}" alt="${product.name}" loading="lazy">
                                     ${imageCount > 1 ? `<div class="multiple-images-indicator">+${imageCount - 1}</div>` : ''}` :
                    '<i class="fas fa-image no-image-placeholder"></i>'
                }
                            </div>
                            <div class="product-details">
                                <div class="product-name">${product.name}</div>
                                ${product.description ? `<div class="product-description">${product.description}</div>` : ''}
                            </div>
                        </div>
                    </td>
                    <td><span class="category-badge" data-category="${product.category}">${categoryName}</span></td>
                    <td><span class="lot-badge">${product.lot || 'ไม่ระบุ'}</span></td>
                    <td>
                        <div class="stock-info">
                            <div class="stock-number">${product.stock} ชิ้น</div>
                            <span class="stock-status ${stockStatus.class}">${stockStatus.text}</span>
                        </div>
                    </td>
                    <td><div class="price-info">${product.price > 0 ? `฿${product.price.toLocaleString()}` : 'ไม่ระบุราคา'}</div></td>
                    <td><div class="date-info">${formatDate(product.receivedDate)}</div></td>
                    <td><div class="supplier-info" title="${supplierName}">${supplierName}</div></td>
                    <td class="actions">
                        <button class="view-btn" onclick="viewProduct('${product.id}')" title="ดูรายละเอียด"><i class="fas fa-eye"></i></button>
                        <button class="edit-btn" onclick="editProduct('${product.id}')" title="แก้ไข"><i class="fas fa-edit"></i></button>
                        <button class="delete-btn" onclick="deleteProduct('${product.id}')" title="ลบ"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `;
        }).join('');

        renderPagination();
        updateStats();
    } catch (error) {
        console.error('Error rendering products:', error);
        tbody.innerHTML = '<tr><td colspan="9" class="text-center">เกิดข้อผิดพลาดในการแสดงข้อมูล</td></tr>';
    }
}

function renderPagination() {
    const totalPages = Math.ceil(filteredProducts.length / itemsPerPage);
    const pagination = document.getElementById('pagination');
    if (!pagination || totalPages <= 1) { pagination.innerHTML = ''; return; }

    let buttons = [`<button onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}><i class="fas fa-chevron-left"></i></button>`];

    const maxVisible = 5;
    let start = Math.max(1, currentPage - Math.floor(maxVisible / 2));
    let end = Math.min(totalPages, start + maxVisible - 1);
    if (end - start < maxVisible - 1) start = Math.max(1, end - maxVisible + 1);

    if (start > 1) {
        buttons.push(`<button onclick="changePage(1)">1</button>`);
        if (start > 2) buttons.push(`<span class="pagination-ellipsis">...</span>`);
    }

    for (let i = start; i <= end; i++) {
        buttons.push(`<button onclick="changePage(${i})" ${i === currentPage ? 'class="active"' : ''}>${i}</button>`);
    }

    if (end < totalPages) {
        if (end < totalPages - 1) buttons.push(`<span class="pagination-ellipsis">...</span>`);
        buttons.push(`<button onclick="changePage(${totalPages})">${totalPages}</button>`);
    }

    buttons.push(`<button onclick="changePage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}><i class="fas fa-chevron-right"></i></button>`);
    pagination.innerHTML = buttons.join('');
}

function generateLotNumber() {
    const now = new Date();
    const day = String(now.getDate()).padStart(2, '0');
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const year = now.getFullYear();

    // สร้างเลขสุ่ม 5 หลัก
    const randomNumber = Math.floor(Math.random() * 100000).toString().padStart(5, '0');

    return `LOT${day}${month}${year}${randomNumber}`;
}

// Modal operations
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'เพิ่มสินค้าใหม่';
    document.getElementById('productForm').reset();
    document.getElementById('productReceivedDate').value = new Date().toISOString().split('T')[0];

    // สร้างล็อตอัตโนมัติและตั้งค่าเป็น readonly
    const lotField = document.getElementById('productLot');
    if (lotField) {
        lotField.value = generateLotNumber();
        lotField.readOnly = true;
        lotField.style.backgroundColor = '#f8f9fa';
        lotField.style.cursor = 'not-allowed';
    }

    currentEditId = null;
    productImages = [];
    renderImagePreviews();
    document.getElementById('productModal').style.display = 'block';
}

function editProduct(id) {
    const product = products.find(p => p.id === id);
    if (!product) {
        showNotification('ไม่พบสินค้าที่ต้องการแก้ไข', 'error');
        return;
    }

    currentEditId = id;
    document.getElementById('modalTitle').textContent = 'แก้ไขสินค้า';

    const fields = ['productName', 'productDescription', 'productCategory', 'productStock', 'productPrice', 'productReceivedDate', 'productSupplier'];
    const values = [product.name, product.description, product.category, product.stock, product.price, product.receivedDate, product.supplier];

    fields.forEach((field, i) => {
        const el = document.getElementById(field);
        if (el) el.value = values[i] || '';
    });

    // จัดการฟิลด์ล็อต - แสดงล็อตเดิมและไม่ให้แก้ไข
    const lotField = document.getElementById('productLot');
    if (lotField) {
        lotField.value = product.lot || '';
        lotField.readOnly = true;
        lotField.style.backgroundColor = '#f8f9fa';
        lotField.style.cursor = 'not-allowed';
    }

    if (product.dimensions) {
        const dimFields = ['productWidth', 'widthUnit', 'productLength', 'lengthUnit', 'productHeight', 'heightUnit', 'productWeight', 'weightUnit'];
        const dimValues = [
            product.dimensions.width?.value || '', product.dimensions.width?.unit || 'mm',
            product.dimensions.length?.value || '', product.dimensions.length?.unit || 'mm',
            product.dimensions.height?.value || '', product.dimensions.height?.unit || 'mm',
            product.dimensions.weight?.value || '', product.dimensions.weight?.unit || 'kg'
        ];
        dimFields.forEach((field, i) => {
            const el = document.getElementById(field);
            if (el) el.value = dimValues[i];
        });
    }

    productImages = product.images ? [...product.images] : [];
    renderImagePreviews();
    document.getElementById('productModal').style.display = 'block';
}

function viewProduct(id) {
    const product = products.find(p => p.id === id);
    if (!product) { showNotification('ไม่พบสินค้าที่ต้องการดู', 'error'); return; }

    currentViewId = id;

    const elements = {
        viewProductCode: product.productCode || product.id,
        viewProductName: product.name,
        viewProductDescription: product.description || 'ไม่มีคำอธิบาย',
        viewProductLot: product.lot || 'ไม่ระบุ',
        viewProductStock: `${product.stock} ชิ้น`,
        viewProductPrice: product.price > 0 ? `฿${product.price.toLocaleString()}` : 'ไม่ระบุราคา',
        viewProductDate: formatDate(product.receivedDate),
        viewProductSupplier: product.supplierName || getSupplierName(product.supplier) || 'ไม่ระบุ'
    };

    Object.entries(elements).forEach(([id, value]) => {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    });

    const categoryBadge = document.getElementById('viewProductCategory');
    if (categoryBadge) {
        categoryBadge.textContent = product.categoryName || getCategoryName(product.category) || 'ไม่ระบุ';
        categoryBadge.setAttribute('data-category', product.category);
    }

    // Handle images
    const mainImageContainer = document.getElementById('viewMainImageContainer');
    const thumbnailGallery = document.getElementById('viewThumbnailGallery');

    if (product.images?.length > 0) {
        mainImageContainer.innerHTML = `<img src="${product.images[0]}" alt="${product.name}" onclick="viewProductImages('${product.id}')" loading="lazy">`;
        if (product.images.length > 1) {
            thumbnailGallery.innerHTML = product.images.map((image, index) => `
                <div class="thumbnail ${index === 0 ? 'active' : ''}" onclick="changeMainImage('${image}', ${index})">
                    <img src="${image}" alt="รูปที่ ${index + 1}" loading="lazy">
                </div>
            `).join('');
        } else {
            thumbnailGallery.innerHTML = '';
        }
    } else {
        mainImageContainer.innerHTML = '<div class="no-image-placeholder"><i class="fas fa-image"></i><span>ไม่มีรูปภาพ</span></div>';
        thumbnailGallery.innerHTML = '';
    }

    document.getElementById('productViewModal').style.display = 'block';
}

// Filter and search
function applyFilters() {
    const searchTerm = getElementValue('searchInput').toLowerCase();
    const categoryValue = getElementValue('categoryFilter');
    const stockValue = getElementValue('stockFilter');
    const startDate = getElementValue('startDateFilter');
    const endDate = getElementValue('endDateFilter');

    filteredProducts = products.filter(product => {
        const matchesSearch = !searchTerm ||
            product.name.toLowerCase().includes(searchTerm) ||
            (product.lot || '').toLowerCase().includes(searchTerm) ||
            (product.productCode || '').toLowerCase().includes(searchTerm) ||
            (product.description || '').toLowerCase().includes(searchTerm);

        const matchesCategory = !categoryValue || product.category === categoryValue;

        let matchesStock = true;
        if (stockValue === 'high') matchesStock = product.stock >= 100;
        else if (stockValue === 'medium') matchesStock = product.stock >= 50 && product.stock < 100;
        else if (stockValue === 'low') matchesStock = product.stock < 50;

        let matchesDateRange = true;
        if (product.receivedDate) {
            const productDate = new Date(product.receivedDate);
            if (!isNaN(productDate.getTime())) {
                if (startDate) matchesDateRange = matchesDateRange && productDate >= new Date(startDate);
                if (endDate) matchesDateRange = matchesDateRange && productDate <= new Date(endDate);
            }
        }

        return matchesSearch && matchesCategory && matchesStock && matchesDateRange;
    });

    applySorting();
    currentPage = 1;
    renderProducts();
}

function applySorting() {
    const sortBy = getElementValue('sortFilter') || 'name';
    filteredProducts.sort((a, b) => {
        switch (sortBy) {
            case 'name': return a.name.localeCompare(b.name, 'th');
            case 'stock': return b.stock - a.stock;
            case 'lot': return (a.lot || '').localeCompare((b.lot || ''), 'th');
            case 'receivedDate_desc':
                if (!a.receivedDate && !b.receivedDate) return 0;
                if (!a.receivedDate) return 1;
                if (!b.receivedDate) return -1;
                return new Date(b.receivedDate) - new Date(a.receivedDate);
            case 'receivedDate_asc':
                if (!a.receivedDate && !b.receivedDate) return 0;
                if (!a.receivedDate) return 1;
                if (!b.receivedDate) return -1;
                return new Date(a.receivedDate) - new Date(b.receivedDate);
            case 'supplier':
                return (a.supplierName || getSupplierName(a.supplier) || '').localeCompare(b.supplierName || getSupplierName(b.supplier) || '', 'th');
            default: return 0;
        }
    });
}

// Utility functions for UI
function updateStats() {
    const totalProducts = products.length;
    const lowStockProducts = products.filter(p => p.stock < 50).length;
    const filteredCount = filteredProducts.length;

    ['totalProducts', 'lowStockProducts', 'filteredProducts'].forEach((id, i) => {
        const el = document.getElementById(id);
        if (el) el.textContent = [totalProducts, lowStockProducts, filteredCount][i].toLocaleString();
    });
}

function showNotification(message, type = 'info') {
    document.querySelectorAll('.app-notification').forEach(n => n.remove());

    const notification = document.createElement('div');
    notification.className = 'app-notification';
    const colors = {
        success: '#28a745', error: '#dc3545', warning: '#ffc107', info: '#17a2b8'
    };
    notification.style.cssText = `
        position: fixed; top: 20px; right: 20px; padding: 15px 20px; border-radius: 8px;
        color: white; font-weight: 600; z-index: 3000; opacity: 0; transform: translateX(100%);
        transition: all 0.3s ease; max-width: 400px; word-wrap: break-word;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15); background: ${colors[type] || colors.info};
        ${type === 'warning' ? 'color: #212529;' : ''}
    `;
    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(() => { notification.style.opacity = '1'; notification.style.transform = 'translateX(0)'; }, 100);
    setTimeout(() => {
        notification.style.opacity = '0'; notification.style.transform = 'translateX(100%)';
        setTimeout(() => document.body.contains(notification) && document.body.removeChild(notification), 300);
    }, type === 'error' ? 5000 : 3000);
}

// Event handlers and setup
function setupProductForm() {
    const form = document.getElementById('productForm');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const name = getElementValue('productName');
        if (!name.trim()) {
            showNotification('กรุณากรอกชื่อสินค้า', 'error');
            return;
        }

        // สำหรับสินค้าใหม่ ให้สร้างล็อตใหม่อีกครั้งเพื่อความแน่ใจ
        let lot = getElementValue('productLot');
        if (!currentEditId) {
            lot = generateLotNumber();
        }

        const formData = {
            name,
            description: getElementValue('productDescription'),
            category_id: getElementValue('productCategory') || null,
            supplier_id: getElementValue('productSupplier') || null,
            lot: lot, // ใช้ล็อตที่สร้างอัตโนมัติ
            stock: parseInt(getElementValue('productStock')) || 0,
            price: parseFloat(getElementValue('productPrice')) || 0,
            received_date: getElementValue('productReceivedDate'),
            width: parseFloat(getElementValue('productWidth')) || null,
            length: parseFloat(getElementValue('productLength')) || null,
            height: parseFloat(getElementValue('productHeight')) || null,
            weight: parseFloat(getElementValue('productWeight')) || null,
            width_unit: getElementValue('widthUnit') || 'mm',
            length_unit: getElementValue('lengthUnit') || 'mm',
            height_unit: getElementValue('heightUnit') || 'mm',
            weight_unit: getElementValue('weightUnit') || 'kg'
        };

        await saveProduct(formData);
    });

    // เพิ่ม event listener สำหรับการรีเซ็ตฟอร์ม
    const resetButton = form.querySelector('button[type="reset"]');
    if (resetButton) {
        resetButton.addEventListener('click', () => {
            setTimeout(() => {
                if (!currentEditId) {
                    const lotField = document.getElementById('productLot');
                    if (lotField) {
                        lotField.value = generateLotNumber();
                        lotField.readOnly = true;
                        lotField.style.backgroundColor = '#f8f9fa';
                        lotField.style.cursor = 'not-allowed';
                    }
                }
            }, 10);
        });
    }
}

function setupDragAndDrop() {
    const dropZone = document.getElementById('dropZone');
    const imageInput = document.getElementById('imageInput');
    if (!dropZone || !imageInput) return;

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, e => { e.preventDefault(); e.stopPropagation(); }, false);
    });

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => dropZone.classList.add('drag-over'), false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => dropZone.classList.remove('drag-over'), false);
    });

    dropZone.addEventListener('drop', e => handleImageFiles(e.dataTransfer.files), false);
    imageInput.addEventListener('change', e => handleImageFiles(e.target.files));
}

let searchTimeout;
function setupEventListeners() {
    // Search with debounce
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(applyFilters, 300);
        });
    }

    // Filters
    ['categoryFilter', 'stockFilter', 'startDateFilter', 'endDateFilter'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', applyFilters);
    });

    const sortFilter = document.getElementById('sortFilter');
    if (sortFilter) sortFilter.addEventListener('change', () => { applySorting(); renderProducts(); });

    // Modal events
    const modals = [
        { id: 'productModal', close: closeModal },
        { id: 'productViewModal', close: closeViewModal },
        { id: 'imageGalleryModal', close: closeImageGallery }
    ];

    modals.forEach(({ id, close }) => {
        const modal = document.getElementById(id);
        if (modal) modal.addEventListener('click', e => { if (e.target === modal) close(); });
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            modals.forEach(({ id, close }) => {
                const modal = document.getElementById(id);
                if (modal?.style.display === 'block') close();
            });
        }

        // Gallery navigation
        const galleryModal = document.getElementById('imageGalleryModal');
        if (galleryModal?.style.display === 'block') {
            if (e.key === 'ArrowLeft') { e.preventDefault(); navigateImage(-1); }
            if (e.key === 'ArrowRight') { e.preventDefault(); navigateImage(1); }
        }

        // Shortcuts
        if ((e.ctrlKey || e.metaKey) && e.key === 'n') { e.preventDefault(); openAddModal(); }
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') { e.preventDefault(); searchInput?.focus(); }
    });
}

// Image gallery functions
function viewImage(index) { currentGalleryImages = [...productImages]; currentImageIndex = index; openImageGallery(); }
function viewProductImages(productId) {
    const product = products.find(p => p.id === productId);
    if (product?.images?.length > 0) {
        currentGalleryImages = [...product.images]; currentImageIndex = 0; openImageGallery();
    }
}

function openImageGallery() {
    const modal = document.getElementById('imageGalleryModal');
    if (!modal || !currentGalleryImages.length) return;

    const image = document.getElementById('galleryImage');
    const currentIndex = document.getElementById('currentImageIndex');
    const totalImages = document.getElementById('totalImages');
    const prevBtn = document.getElementById('prevImageBtn');
    const nextBtn = document.getElementById('nextImageBtn');

    if (image) image.src = currentGalleryImages[currentImageIndex];
    if (currentIndex) currentIndex.textContent = currentImageIndex + 1;
    if (totalImages) totalImages.textContent = currentGalleryImages.length;
    if (prevBtn) prevBtn.disabled = currentImageIndex === 0;
    if (nextBtn) nextBtn.disabled = currentImageIndex === currentGalleryImages.length - 1;

    modal.style.display = 'block';
}

function navigateImage(direction) {
    const newIndex = currentImageIndex + direction;
    if (newIndex >= 0 && newIndex < currentGalleryImages.length) {
        currentImageIndex = newIndex; openImageGallery();
    }
}

function removeImage(index) { productImages.splice(index, 1); renderImagePreviews(); showNotification('ลบรูปภาพแล้ว', 'info'); }

function closeModal() {
    document.getElementById('productModal').style.display = 'none';
    currentEditId = null;
    productImages = [];

    // รีเซ็ตสถานะฟิลด์ล็อต
    const lotField = document.getElementById('productLot');
    if (lotField) {
        lotField.readOnly = false;
        lotField.style.backgroundColor = '';
        lotField.style.cursor = '';
        lotField.value = '';
    }
}

function closeViewModal() { document.getElementById('productViewModal').style.display = 'none'; currentViewId = null; }
function closeImageGallery() { document.getElementById('imageGalleryModal').style.display = 'none'; }

function changePage(page) {
    const totalPages = Math.ceil(filteredProducts.length / itemsPerPage);
    if (page >= 1 && page <= totalPages) { currentPage = page; renderProducts(); }
}

function changeMainImage(imageSrc, index) {
    const container = document.getElementById('viewMainImageContainer');
    if (container) {
        container.innerHTML = `<img src="${imageSrc}" alt="รูปหลัก" onclick="viewProductImages('${currentViewId}')" loading="lazy">`;
    }
    document.querySelectorAll('.thumbnail').forEach((thumb, i) => thumb.classList.toggle('active', i === index));
}

async function refreshProducts() {
    try {
        const btn = document.querySelector('.refresh-btn');
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; }
        await loadProducts(); applyFilters(); showNotification('รีเฟรชข้อมูลแล้ว', 'success');
    } catch (error) {
        showNotification('เกิดข้อผิดพลาดในการรีเฟรชข้อมูล', 'error');
    } finally {
        const btn = document.querySelector('.refresh-btn');
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-sync-alt"></i>'; }
    }
}

function clearDateFilters() {
    ['startDateFilter', 'endDateFilter'].forEach(id => {
        const el = document.getElementById(id); if (el) el.value = '';
    });
    applyFilters(); showNotification('ล้างตัวกรองวันที่แล้ว', 'info');
}

function resetAllFilters() {
    const filters = [
        { id: 'searchInput', value: '' }, { id: 'categoryFilter', value: '' },
        { id: 'stockFilter', value: '' }, { id: 'sortFilter', value: 'name' },
        { id: 'startDateFilter', value: '' }, { id: 'endDateFilter', value: '' }
    ];
    filters.forEach(({ id, value }) => {
        const el = document.getElementById(id); if (el) el.value = value;
    });
    applyFilters(); showNotification('รีเซ็ตตัวกรองทั้งหมดแล้ว', 'success');
}

// Global function exports
const globalFunctions = {
    viewProduct, editProduct, deleteProduct, openAddModal, closeModal, closeViewModal,
    editProductFromView: () => { if (currentViewId) { closeViewModal(); editProduct(currentViewId); } },
    deleteProductFromView: () => { if (currentViewId) { closeViewModal(); deleteProduct(currentViewId); } },
    changeMainImage, changePage, clearDateFilters, resetAllFilters,
    viewProductImages, viewImage, removeImage, closeImageGallery, navigateImage,
    onSortChange: () => { applySorting(); renderProducts(); }, applyFilters,
    debounceSearch: () => { clearTimeout(searchTimeout); searchTimeout = setTimeout(applyFilters, 300); },
    refreshProducts, showNotification
};

Object.assign(window, globalFunctions);

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', init)