// Sample product data - will be replaced by database data
let products = [];
let categories = [];
let suppliers = [];
let currentPage = 1;
const itemsPerPage = 10;
let filteredProducts = [...products];
let currentEditId = null;
let currentViewId = null;
let productImages = [];
let currentGalleryImages = [];
let currentImageIndex = 0;

// API endpoints
const API_BASE = 'http://localhost/steelproject/admin/controllers/';
const ENDPOINTS = {
    products: API_BASE + 'get_product.php',
    addProduct: API_BASE + 'add_product.php',
    manageProduct: API_BASE + 'manage_product.php',
    uploadImage: API_BASE + 'upload_image.php'
};

// Load initial data when page loads
async function loadInitialData() {
    try {
        await Promise.all([
            loadProducts(),
            loadCategories(),
            loadSuppliers()
        ]);
        renderProducts();
    } catch (error) {
        console.error('Error loading initial data:', error);
        showNotification('เกิดข้อผิดพลาดในการโหลดข้อมูล', 'error');
    }
}

// FIXED: Load products from API with improved data structure handling
async function loadProducts() {
    try {
        const response = await fetch(ENDPOINTS.products);
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const result = await response.json();
        console.log('Products API Response:', result);

        if (result.success && result.data) {
            // กลุ่มสินค้าตาม product_id และรวมรูปภาพ
            const productMap = new Map();

            result.data.forEach(product => {
                const productId = product.product_id;

                if (!productMap.has(productId)) {
                    productMap.set(productId, {
                        id: productId,
                        productCode: productId,
                        name: product.name || '',
                        description: product.description || '',
                        category: product.category_id || '',
                        categoryName: product.category_name || '',
                        lot: product.lot || '',
                        stock: Number(product.stock) || 0,
                        price: Number(product.price) || 0,
                        receivedDate: product.received_date
                            ? new Date(product.received_date).toISOString().split('T')[0]
                            : null,
                        supplier: product.supplier_id || '',
                        supplierName: product.supplier_name || '',
                        dimensions: {
                            width: {
                                value: Number(product.width) || 0,
                                unit: product.width_unit || 'mm'
                            },
                            length: {
                                value: Number(product.length) || 0,
                                unit: product.length_unit || 'mm'
                            },
                            height: {
                                value: Number(product.height) || 0,
                                unit: product.height_unit || 'mm'
                            },
                            weight: {
                                value: Number(product.weight) || 0,
                                unit: product.weight_unit || 'kg'
                            }
                        },
                        images: []
                    });
                }

                // FIXED: เพิ่มรูปภาพจาก product.images array
                if (product.images && Array.isArray(product.images)) {
                    const existingProduct = productMap.get(productId);
                    product.images.forEach(imgObj => {
                        // imgObj มี structure: {productimage_id, image_url, is_main, created_at, updated_at}
                        if (imgObj.image_url && !existingProduct.images.includes(imgObj.image_url)) {
                            if (imgObj.is_main == 1 || imgObj.is_main === true) {
                                existingProduct.images.unshift(imgObj.image_url);
                            } else {
                                existingProduct.images.push(imgObj.image_url);
                            }
                        }
                    });
                }
            });

            products = Array.from(productMap.values());
            filteredProducts = [...products];
            console.log('Processed products:', products);
        } else {
            console.warn('Invalid products response:', result);
            products = [];
            filteredProducts = [];
        }

    } catch (error) {
        console.error('Error loading products:', error);
        showNotification('เกิดข้อผิดพลาดในการโหลดข้อมูลสินค้า: ' + error.message, 'error');
        throw error;
    }
}

// Load categories (hardcoded data)
async function loadCategories() {
    categories = [
        { id: 'rb', name: 'เหล็กเส้น' },
        { id: 'sp', name: 'เหล็กแผ่น' },
        { id: 'ss', name: 'เหล็กรูปพรรณ' },
        { id: 'wm', name: 'เหล็กตะแกรง/ตาข่าย' },
        { id: 'ot', name: 'อื่น ๆ' }
    ];
    populateCategoryDropdown();
}

// Load suppliers (hardcoded data)
async function loadSuppliers() {
    suppliers = [
        { id: 'SUP01', name: 'บจก. โอเชียนซัพพลายเออร์ จำกัด (Ocean Supplier)' },
        { id: 'SUP02', name: 'Metallic Corporation Limited (MCC / Metallic Steel Center)' },
        { id: 'SUP03', name: 'Millcon Steel (MILL)' },
        { id: 'SUP04', name: 'Navasiam Steel Co., Ltd.' },
        { id: 'SUP05', name: 'กิจไพบูลย์ เมททอล' },
        { id: 'SUP06', name: 'Chuephaibul Steel (เชื้อไพบูลย์ สตีล)' }
    ];
    populateSupplierDropdown();
}

// Populate category dropdown
function populateCategoryDropdown() {
    const categorySelect = document.getElementById('productCategory');
    const categoryFilter = document.getElementById('categoryFilter');

    if (categorySelect) {
        categorySelect.innerHTML = '<option value="">เลือกหมวดหมู่</option>';
        categories.forEach(category => {
            categorySelect.innerHTML += `<option value="${category.id}">${category.name}</option>`;
        });
    }

    if (categoryFilter) {
        categoryFilter.innerHTML = '<option value="">ทั้งหมด</option>';
        categories.forEach(category => {
            categoryFilter.innerHTML += `<option value="${category.id}">${category.name}</option>`;
        });
    }
}

// Populate supplier dropdown
function populateSupplierDropdown() {
    const supplierSelect = document.getElementById('productSupplier');
    if (supplierSelect) {
        supplierSelect.innerHTML = '<option value="">เลือกซัพพลายเออร์</option>';
        suppliers.forEach(supplier => {
            supplierSelect.innerHTML += `<option value="${supplier.id}">${supplier.name}</option>`;
        });
    }
}

// Get category name in Thai
function getCategoryName(categoryId) {
    const category = categories.find(cat => cat.id === categoryId);
    return category ? category.name : categoryId;
}

// Get supplier name
function getSupplierName(supplierId) {
    const supplier = suppliers.find(sup => sup.id === supplierId);
    return supplier ? supplier.name : supplierId;
}

// FIXED: Enhanced image handling
async function handleImageFiles(files) {
    const validExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    const tasks = [];

    for (const file of files) {
        const ext = file.name.split('.').pop().toLowerCase();
        if (!file.type.startsWith('image/') || !validExtensions.includes(ext)) {
            showNotification(`ไฟล์ ${file.name} ไม่ใช่รูปภาพที่รองรับ`, 'warning');
            continue;
        }

        if (file.size > 5 * 1024 * 1024) { // 5MB
            showNotification(`ไฟล์ ${file.name} มีขนาดเกิน 5MB`, 'warning');
            continue;
        }

        try {
            if (!currentEditId) {
                // สำหรับสินค้าใหม่ → เก็บ base64
                const reader = new FileReader();
                reader.onload = function (e) {
                    productImages.push(e.target.result);
                    renderImagePreviews();
                };
                reader.readAsDataURL(file);
            } else {
                // สำหรับสินค้าที่มีอยู่ → อัปโหลดทันที
                tasks.push(uploadImageFile(file, currentEditId));
            }
        } catch (error) {
            console.error('Error handling image:', error);
            showNotification(`เกิดข้อผิดพลาดกับไฟล์ ${file.name}`, 'error');
        }
    }

    // รอ upload ทั้งหมดให้เสร็จ
    if (tasks.length > 0) {
        try {
            const results = await Promise.all(tasks);
            const successful = results.filter(r => r.success).length;
            const failed = results.filter(r => !r.success).length;

            if (successful > 0) {
                showNotification(`อัปโหลดสำเร็จ ${successful} รูป${failed > 0 ? `, ล้มเหลว ${failed} รูป` : ''}`,
                    failed > 0 ? 'warning' : 'success');
                // Refresh product data to show new images
                await loadProducts();
                if (currentEditId) {
                    const product = products.find(p => p.id === currentEditId);
                    if (product) {
                        productImages = [...(product.images || [])];
                        renderImagePreviews();
                    }
                }
            }
        } catch (err) {
            console.error('Upload error:', err);
            showNotification('มีบางรูปอัปโหลดไม่สำเร็จ', 'error');
        }
    }
}

// Validate product form
function validateProductForm() {
    const name = getElementValue('productName');
    const categoryId = getElementValue('productCategory');
    const supplierId = getElementValue('productSupplier');

    if (!name.trim()) {
        showNotification('กรุณากรอกชื่อสินค้า', 'error');
        return false;
    }

    // Validate category if selected
    if (categoryId && !categories.find(cat => cat.id === categoryId)) {
        showNotification('หมวดหมู่สินค้าที่เลือกไม่ถูกต้อง', 'error');
        return false;
    }

    // Validate supplier if selected
    if (supplierId && !suppliers.find(sup => sup.id === supplierId)) {
        showNotification('ซัพพลายเออร์ที่เลือกไม่ถูกต้อง', 'error');
        return false;
    }

    return true;
}

// FIXED: Upload image file to server with better error handling
async function uploadImageFile(file, productId, isMain = false) {
    const formData = new FormData();
    formData.append('image', file);
    formData.append('product_id', productId);
    formData.append('is_main', isMain ? '1' : '0');

    try {
        const response = await fetch(ENDPOINTS.uploadImage, {
            method: 'POST',
            body: formData
        });

        console.log('Upload response status:', response.status);

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const responseText = await response.text();
        console.log('Upload raw response:', responseText);

        let result;
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            throw new Error('Invalid response from server');
        }

        console.log('Upload parsed result:', result);

        if (result.status === 'success' && result.success === true) {
            return { 
                success: true, 
                url: result.url || result.data?.image_url, 
                message: result.message 
            };
        } else {
            throw new Error(result.message || 'อัปโหลดล้มเหลว');
        }
    } catch (error) {
        console.error('Error uploading image:', error);
        return { success: false, error: error.message };
    }
}

// Render image previews
function renderImagePreviews() {
    const container = document.getElementById('imagePreviewContainer');
    if (!container) return;

    container.innerHTML = '';

    productImages.forEach((image, index) => {
        const preview = document.createElement('div');
        preview.className = 'image-preview';
        preview.innerHTML = `
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
        `;
        container.appendChild(preview);
    });
}

// Remove image from preview
function removeImage(index) {
    productImages.splice(index, 1);
    renderImagePreviews();
    showNotification('ลบรูปภาพแล้ว', 'info');
}

// View image in gallery
function viewImage(index) {
    currentGalleryImages = [...productImages];
    currentImageIndex = index;
    openImageGallery();
}

// Image gallery functions
function openImageGallery() {
    const modal = document.getElementById('imageGalleryModal');
    if (!modal || currentGalleryImages.length === 0) return;

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

function closeImageGallery() {
    const modal = document.getElementById('imageGalleryModal');
    if (modal) modal.style.display = 'none';
}

function navigateImage(direction) {
    const newIndex = currentImageIndex + direction;
    if (newIndex >= 0 && newIndex < currentGalleryImages.length) {
        currentImageIndex = newIndex;
        openImageGallery();
    }
}

// Get stock status
function getStockStatus(stock) {
    if (stock >= 100) return { class: 'high', text: 'เพียงพอ' };
    if (stock >= 50) return { class: 'medium', text: 'ปานกลาง' };
    return { class: 'low', text: 'ต่ำ' };
}

// Format date for display
function formatDate(dateString) {
    if (!dateString) return 'ไม่ระบุ';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return 'ไม่ระบุ';
    return date.toLocaleDateString('th-TH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// Format dimensions for display
function formatDimensions(dimensions) {
    if (!dimensions) return '';

    let parts = [];
    if (dimensions.width && dimensions.width.value) {
        parts.push(`กว้าง ${dimensions.width.value} ${dimensions.width.unit}`);
    }
    if (dimensions.length && dimensions.length.value) {
        parts.push(`ยาว ${dimensions.length.value} ${dimensions.length.unit}`);
    }
    if (dimensions.height && dimensions.height.value) {
        parts.push(`หนา ${dimensions.height.value} ${dimensions.height.unit}`);
    }
    if (dimensions.weight && dimensions.weight.value) {
        parts.push(`น้ำหนัก ${dimensions.weight.value} ${dimensions.weight.unit}`);
    }

    return parts.length > 0 ? parts.join(' × ') : '';
}

// Render products table with improved error handling
function renderProducts() {
    const tbody = document.getElementById('productsTableBody');
    if (!tbody) return;

    try {
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        const pageProducts = filteredProducts.slice(startIndex, endIndex);

        tbody.innerHTML = pageProducts.map(product => {
            const stockStatus = getStockStatus(product.stock);
            const dimensionsText = formatDimensions(product.dimensions);
            const hasImages = product.images && product.images.length > 0;
            const imageCount = hasImages ? product.images.length : 0;
            const supplierName = product.supplierName || getSupplierName(product.supplier) || 'ไม่ระบุ';
            const categoryName = product.categoryName || getCategoryName(product.category) || 'ไม่ระบุ';

            return `
                <tr>
                    <td>
                        <span class="product-code">${product.productCode || product.id}</span>
                    </td>
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
                                ${dimensionsText ? `<div class="product-dimensions">${dimensionsText}</div>` : ''}
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="category-badge" data-category="${product.category}">${categoryName}</span>
                    </td>
                    <td>
                        <span class="lot-badge">${product.lot || 'ไม่ระบุ'}</span>
                    </td>
                    <td>
                        <div class="stock-info">
                            <div class="stock-number">${product.stock} ชิ้น</div>
                            <span class="stock-status ${stockStatus.class}">${stockStatus.text}</span>
                        </div>
                    </td>
                    <td>
                        <div class="price-info">
                            ${product.price > 0 ? `฿${product.price.toLocaleString()}` : 'ไม่ระบุราคา'}
                        </div>
                    </td>
                    <td>
                        <div class="date-info">${formatDate(product.receivedDate)}</div>
                    </td>
                    <td>
                        <div class="supplier-info" title="${supplierName}">${supplierName}</div>
                    </td>
                    <td class="actions">
                        <button class="view-btn" onclick="viewProduct('${product.id}')" title="ดูรายละเอียด">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="edit-btn" onclick="editProduct('${product.id}')" title="แก้ไข">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="delete-btn" onclick="deleteProduct('${product.id}')" title="ลบ">
                            <i class="fas fa-trash"></i>
                        </button>
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

// FIXED: Save product form
function setupProductForm() {
    const productForm = document.getElementById('productForm');
    if (!productForm) return;

    productForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        if (!validateProductForm()) {
            return;
        }

        const formData = {
            name: getElementValue('productName'),
            description: getElementValue('productDescription'),
            category_id: getElementValue('productCategory') || null,
            supplier_id: getElementValue('productSupplier') || null,
            lot: getElementValue('productLot') || '',
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

        try {
            let response, result;

            if (currentEditId) {
                // Update existing product
                formData.product_id = currentEditId;
                formData._method = 'PUT';

                console.log('Updating product with data:', formData);

                response = await fetch(ENDPOINTS.manageProduct, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });

                console.log('Update response status:', response.status);

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const responseText = await response.text();
                console.log('Update raw response:', responseText);

                if (!responseText.trim()) {
                    throw new Error('Server ไม่ได้ส่ง response กลับมา');
                }

                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    // ถ้า response status ok แต่ parse ไม่ได้ ให้ถือว่าสำเร็จ
                    if (response.ok) {
                        showNotification('แก้ไขสินค้าเรียบร้อยแล้ว', 'success');
                        closeModal();
                        await loadProducts();
                        applyFilters();
                        return;
                    } else {
                        throw new Error(`เกิดข้อผิดพลาด HTTP ${response.status}: ${responseText}`);
                    }
                }

                console.log('Update parsed result:', result);

                if (result.success === true || result.status === 'success') {
                    showNotification('แก้ไขสินค้าเรียบร้อยแล้ว', 'success');
                } else {
                    throw new Error(result.message || 'แก้ไขสินค้าล้มเหลว');
                }

            } else {
                // Add new product
                console.log('Adding new product with data:', formData);

                response = await fetch(ENDPOINTS.addProduct, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const responseText = await response.text();
                console.log('Add raw response:', responseText);

                if (!responseText.trim()) {
                    throw new Error('Server ไม่ได้ส่ง response กลับมา');
                }

                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    throw new Error('Server ส่ง response ที่ไม่ใช่ JSON: ' + responseText.substring(0, 200));
                }

                console.log('Add parsed result:', result);

                if (result.success) {
                    showNotification(`เพิ่มสินค้าเรียบร้อยแล้ว (รหัส: ${result.product_id})`, 'success');

                    // Upload images if any
                    if (productImages.length > 0 && result.product_id) {
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
                                    showNotification(`เพิ่มสินค้าสำเร็จ แต่อัปโหลดรูปที่ ${i + 1} ล้มเหลว`, 'warning');
                                }
                            }
                        }
                    }
                } else {
                    throw new Error(result.message || 'เพิ่มสินค้าล้มเหลว');
                }
            }

            closeModal();
            await loadProducts();
            applyFilters();

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
    });
}

// FIXED: Enhanced delete product function
async function deleteProduct(id) {
    if (!confirm('คุณแน่ใจหรือไม่ว่าต้องการลบสินค้านี้?')) {
        return;
    }

    try {
        console.log('Deleting product with ID:', id);

        const response = await fetch(ENDPOINTS.manageProduct, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                _method: 'DELETE',
                product_id: id
            })
        });

        console.log('Delete response status:', response.status);

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const responseText = await response.text();
        console.log('Delete raw response:', responseText);

        let result;
        try {
            if (!responseText.trim()) {
                throw new Error('Server ไม่ได้ส่ง response กลับมา');
            }
            result = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            // ถ้า response status ok แต่ parse ไม่ได้ ให้ถือว่าสำเร็จ
            if (response.ok) {
                showNotification('ลบสินค้าเรียบร้อยแล้ว', 'success');
                await loadProducts();
                applyFilters();
                const viewModal = document.getElementById('productViewModal');
                if (viewModal && viewModal.style.display === 'block') {
                    closeViewModal();
                }
                return;
            } else {
                throw new Error(`เกิดข้อผิดพลาด HTTP ${response.status}`);
            }
        }

        console.log('Delete parsed result:', result);

        if (result.success === true || result.status === 'success') {
            await loadProducts();
            applyFilters();
            showNotification('ลบสินค้าเรียบร้อยแล้ว', 'success');

            // ปิด view modal ถ้าเปิดอยู่
            const viewModal = document.getElementById('productViewModal');
            if (viewModal && viewModal.style.display === 'block') {
                closeViewModal();
            }
        } else {
            throw new Error(result.message || 'ลบสินค้าล้มเหลว');
        }

    } catch (error) {
        console.error('Error deleting product:', error);
        showNotification('เกิดข้อผิดพลาดในการลบสินค้า: ' + error.message, 'error');
    }
}

// Helper function to get element value safely
function getElementValue(id) {
    const element = document.getElementById(id);
    return element ? element.value : '';
}

// Enhanced notification function
function showNotification(message, type = 'info') {
    // Remove existing notifications
    document.querySelectorAll('.app-notification').forEach(n => n.remove());

    const notification = document.createElement('div');
    notification.className = 'app-notification';
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 600;
        z-index: 3000;
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.3s ease;
        max-width: 400px;
        word-wrap: break-word;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        ${type === 'success' ? 'background: #28a745;' : ''}
        ${type === 'error' ? 'background: #dc3545;' : ''}
        ${type === 'warning' ? 'background: #ffc107; color: #212529;' : ''}
        ${type === 'info' ? 'background: #17a2b8;' : ''}
    `;
    notification.textContent = message;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateX(0)';
    }, 100);

    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (document.body.contains(notification)) {
                document.body.removeChild(notification);
            }
        }, 300);
    }, type === 'error' ? 5000 : 3000);
}

// Filter and search functions
function applyFilters() {
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const stockFilter = document.getElementById('stockFilter');
    const startDateFilter = document.getElementById('startDateFilter');
    const endDateFilter = document.getElementById('endDateFilter');

    const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
    const categoryValue = categoryFilter ? categoryFilter.value : '';
    const stockValue = stockFilter ? stockFilter.value : '';
    const startDate = startDateFilter ? startDateFilter.value : '';
    const endDate = endDateFilter ? endDateFilter.value : '';

    filteredProducts = products.filter(product => {
        const matchesSearch = !searchTerm ||
            product.name.toLowerCase().includes(searchTerm) ||
            (product.lot && product.lot.toLowerCase().includes(searchTerm)) ||
            (product.productCode && product.productCode.toLowerCase().includes(searchTerm)) ||
            (product.description && product.description.toLowerCase().includes(searchTerm));

        const matchesCategory = !categoryValue || product.category === categoryValue;

        let matchesStock = true;
        if (stockValue === 'high') matchesStock = product.stock >= 100;
        else if (stockValue === 'medium') matchesStock = product.stock >= 50 && product.stock < 100;
        else if (stockValue === 'low') matchesStock = product.stock < 50;

        // Date range filter
        let matchesDateRange = true;
        if (product.receivedDate) {
            const productDate = new Date(product.receivedDate);
            if (!isNaN(productDate.getTime())) {
                if (startDate) {
                    const filterStartDate = new Date(startDate);
                    matchesDateRange = matchesDateRange && productDate >= filterStartDate;
                }
                if (endDate) {
                    const filterEndDate = new Date(endDate);
                    matchesDateRange = matchesDateRange && productDate <= filterEndDate;
                }
            }
        }

        return matchesSearch && matchesCategory && matchesStock && matchesDateRange;
    });

    applySorting();
    currentPage = 1;
    renderProducts();
}

// Sort products function
function applySorting() {
    const sortFilter = document.getElementById('sortFilter');
    const sortBy = sortFilter ? sortFilter.value : 'name';

    filteredProducts.sort((a, b) => {
        switch (sortBy) {
            case 'name':
                return a.name.localeCompare(b.name, 'th');
            case 'stock':
                return b.stock - a.stock;
            case 'lot':
                return (a.lot || '').localeCompare((b.lot || ''), 'th');
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
                const supplierA = a.supplierName || getSupplierName(a.supplier) || '';
                const supplierB = b.supplierName || getSupplierName(b.supplier) || '';
                return supplierA.localeCompare(supplierB, 'th');
            default:
                return 0;
        }
    });
}

// Render pagination
function renderPagination() {
    const totalPages = Math.ceil(filteredProducts.length / itemsPerPage);
    const pagination = document.getElementById('pagination');

    if (!pagination) return;

    if (totalPages <= 1) {
        pagination.innerHTML = '';
        return;
    }

    let buttons = [];

    // Previous button
    buttons.push(`
        <button onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
            <i class="fas fa-chevron-left"></i>
        </button>
    `);

    // Page numbers with smart pagination
    const maxVisiblePages = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

    if (endPage - startPage < maxVisiblePages - 1) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }

    if (startPage > 1) {
        buttons.push(`<button onclick="changePage(1)">1</button>`);
        if (startPage > 2) {
            buttons.push(`<span class="pagination-ellipsis">...</span>`);
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        buttons.push(`
            <button onclick="changePage(${i})" ${i === currentPage ? 'class="active"' : ''}>
                ${i}
            </button>
        `);
    }

    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            buttons.push(`<span class="pagination-ellipsis">...</span>`);
        }
        buttons.push(`<button onclick="changePage(${totalPages})">${totalPages}</button>`);
    }

    // Next button
    buttons.push(`
        <button onclick="changePage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>
            <i class="fas fa-chevron-right"></i>
        </button>
    `);

    pagination.innerHTML = buttons.join('');
}

// Change page
function changePage(page) {
    const totalPages = Math.ceil(filteredProducts.length / itemsPerPage);
    if (page >= 1 && page <= totalPages) {
        currentPage = page;
        renderProducts();
        // Scroll to top of table
        document.getElementById('productsTableBody')?.scrollIntoView({ behavior: 'smooth' });
    }
}

// Update statistics
function updateStats() {
    const totalProducts = products.length;
    const lowStockProducts = products.filter(p => p.stock < 50).length;
    const filteredCount = filteredProducts.length;

    const totalElement = document.getElementById('totalProducts');
    const lowStockElement = document.getElementById('lowStockProducts');
    const filteredElement = document.getElementById('filteredProducts');

    if (totalElement) totalElement.textContent = totalProducts.toLocaleString();
    if (lowStockElement) lowStockElement.textContent = lowStockProducts.toLocaleString();
    if (filteredElement) filteredElement.textContent = filteredCount.toLocaleString();
}

// Modal functions
function openAddModal() {
    const modalTitle = document.getElementById('modalTitle');
    const productForm = document.getElementById('productForm');
    const receivedDateInput = document.getElementById('productReceivedDate');
    const modal = document.getElementById('productModal');

    if (modalTitle) modalTitle.textContent = 'เพิ่มสินค้าใหม่';
    if (productForm) productForm.reset();

    // Set default date to today
    if (receivedDateInput) {
        const today = new Date().toISOString().split('T')[0];
        receivedDateInput.value = today;
    }

    currentEditId = null;
    productImages = [];
    renderImagePreviews();

    if (modal) modal.style.display = 'block';
}

function closeModal() {
    const modal = document.getElementById('productModal');
    if (modal) modal.style.display = 'none';
    currentEditId = null;
    productImages = [];
}

// Enhanced edit product function
async function editProduct(id) {
    const product = products.find(p => p.id === id);
    if (!product) {
        showNotification('ไม่พบสินค้าที่ต้องการแก้ไข', 'error');
        return;
    }

    currentEditId = id;

    const modalTitle = document.getElementById('modalTitle');
    if (modalTitle) modalTitle.textContent = 'แก้ไขสินค้า';

    // Fill form fields
    const fields = {
        productName: product.name,
        productDescription: product.description,
        productCategory: product.category,
        productLot: product.lot,
        productStock: product.stock,
        productPrice: product.price,
        productReceivedDate: product.receivedDate,
        productSupplier: product.supplier
    };

    Object.entries(fields).forEach(([id, value]) => {
        const element = document.getElementById(id);
        if (element) element.value = value || '';
    });

    // Fill dimension fields
    if (product.dimensions) {
        const dimensionFields = {
            productWidth: product.dimensions.width?.value || '',
            widthUnit: product.dimensions.width?.unit || 'mm',
            productLength: product.dimensions.length?.value || '',
            lengthUnit: product.dimensions.length?.unit || 'mm',
            productHeight: product.dimensions.height?.value || '',
            heightUnit: product.dimensions.height?.unit || 'mm',
            productWeight: product.dimensions.weight?.value || '',
            weightUnit: product.dimensions.weight?.unit || 'kg'
        };

        Object.entries(dimensionFields).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) element.value = value;
        });
    }

    // Load existing images
    productImages = product.images ? [...product.images] : [];
    renderImagePreviews();

    const modal = document.getElementById('productModal');
    if (modal) modal.style.display = 'block';
}

// Enhanced view product function
function viewProduct(id) {
    const product = products.find(p => p.id === id);
    if (!product) {
        showNotification('ไม่พบสินค้าที่ต้องการดู', 'error');
        return;
    }

    currentViewId = id;

    // Fill basic info
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
        const element = document.getElementById(id);
        if (element) element.textContent = value;
    });

    // Set category badge
    const categoryBadge = document.getElementById('viewProductCategory');
    if (categoryBadge) {
        categoryBadge.textContent = product.categoryName || getCategoryName(product.category) || 'ไม่ระบุ';
        categoryBadge.setAttribute('data-category', product.category);
    }

    // Handle images
    const mainImageContainer = document.getElementById('viewMainImageContainer');
    const thumbnailGallery = document.getElementById('viewThumbnailGallery');

    if (mainImageContainer && thumbnailGallery) {
        if (product.images && product.images.length > 0) {
            // Set main image
            mainImageContainer.innerHTML = `
                <img src="${product.images[0]}" alt="${product.name}" onclick="viewProductImages('${product.id}')" loading="lazy">
            `;

            // Set thumbnails
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
            mainImageContainer.innerHTML = `
                <div class="no-image-placeholder">
                    <i class="fas fa-image"></i>
                    <span>ไม่มีรูปภาพ</span>
                </div>
            `;
            thumbnailGallery.innerHTML = '';
        }
    }

    // Handle dimensions
    const dimensionsSection = document.getElementById('viewDimensionsSection');
    const dimensionsGrid = document.getElementById('viewDimensionsGrid');

    if (dimensionsSection && dimensionsGrid && product.dimensions) {
        const dimensions = [];

        if (product.dimensions.width && product.dimensions.width.value) {
            dimensions.push({
                label: 'ความกว้าง',
                value: product.dimensions.width.value,
                unit: product.dimensions.width.unit
            });
        }

        if (product.dimensions.length && product.dimensions.length.value) {
            dimensions.push({
                label: 'ความยาว',
                value: product.dimensions.length.value,
                unit: product.dimensions.length.unit
            });
        }

        if (product.dimensions.height && product.dimensions.height.value) {
            dimensions.push({
                label: 'ความสูง/หนา',
                value: product.dimensions.height.value,
                unit: product.dimensions.height.unit
            });
        }

        if (product.dimensions.weight && product.dimensions.weight.value) {
            dimensions.push({
                label: 'น้ำหนัก',
                value: product.dimensions.weight.value,
                unit: product.dimensions.weight.unit
            });
        }

        if (dimensions.length > 0) {
            dimensionsGrid.innerHTML = dimensions.map(dim => `
                <div class="dimension-item">
                    <div class="dimension-label">${dim.label}</div>
                    <div class="dimension-value">${dim.value}<span class="dimension-unit">${dim.unit}</span></div>
                </div>
            `).join('');
            dimensionsSection.style.display = 'block';
        } else {
            dimensionsSection.style.display = 'none';
        }
    }

    const modal = document.getElementById('productViewModal');
    if (modal) modal.style.display = 'block';
}

// View product images in gallery
function viewProductImages(productId) {
    const product = products.find(p => p.id === productId);
    if (product && product.images && product.images.length > 0) {
        currentGalleryImages = [...product.images];
        currentImageIndex = 0;
        openImageGallery();
    } else {
        showNotification('ไม่มีรูปภาพสำหรับสินค้านี้', 'info');
    }
}

// Change main image in view modal
function changeMainImage(imageSrc, index) {
    const container = document.getElementById('viewMainImageContainer');
    if (container) {
        container.innerHTML = `
            <img src="${imageSrc}" alt="รูปหลัก" onclick="viewProductImages('${currentViewId}')" loading="lazy">
        `;
    }

    // Update active thumbnail
    document.querySelectorAll('.thumbnail').forEach((thumb, i) => {
        thumb.classList.toggle('active', i === index);
    });
}

// Close view modal
function closeViewModal() {
    const modal = document.getElementById('productViewModal');
    if (modal) modal.style.display = 'none';
    currentViewId = null;
}

// Edit product from view modal
function editProductFromView() {
    if (currentViewId) {
        closeViewModal();
        editProduct(currentViewId);
    }
}

// Delete product from view modal
function deleteProductFromView() {
    if (currentViewId) {
        closeViewModal();
        deleteProduct(currentViewId);
    }
}

// Advanced search with debounce
let searchTimeout;
function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 300);
}

// Sort products when dropdown changes
function onSortChange() {
    applySorting();
    renderProducts();
}

// Clear date filters
function clearDateFilters() {
    const startDate = document.getElementById('startDateFilter');
    const endDate = document.getElementById('endDateFilter');

    if (startDate) startDate.value = '';
    if (endDate) endDate.value = '';

    applyFilters();
    showNotification('ล้างตัวกรองวันที่แล้ว', 'info');
}

// Reset all filters
function resetAllFilters() {
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const stockFilter = document.getElementById('stockFilter');
    const sortFilter = document.getElementById('sortFilter');
    const startDateFilter = document.getElementById('startDateFilter');
    const endDateFilter = document.getElementById('endDateFilter');

    if (searchInput) searchInput.value = '';
    if (categoryFilter) categoryFilter.value = '';
    if (stockFilter) stockFilter.value = '';
    if (sortFilter) sortFilter.value = 'name';
    if (startDateFilter) startDateFilter.value = '';
    if (endDateFilter) endDateFilter.value = '';

    applyFilters();
    showNotification('รีเซ็ตตัวกรองทั้งหมดแล้ว', 'success');
}

// Drag and drop functionality
function setupDragAndDrop() {
    const dropZone = document.getElementById('dropZone');
    const imageInput = document.getElementById('imageInput');
    const imagesSection = document.getElementById('imagesSection');

    if (!dropZone || !imageInput) return;

    // Prevent default drag behaviors
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });

    // Highlight drop zone when item is dragged over it
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
        if (imagesSection) imagesSection.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
        if (imagesSection) imagesSection.addEventListener(eventName, unhighlight, false);
    });

    // Handle dropped files
    dropZone.addEventListener('drop', handleDrop, false);

    // Handle file input change
    imageInput.addEventListener('change', function (e) {
        handleImageFiles(e.target.files);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    function highlight() {
        dropZone.classList.add('drag-over');
        if (imagesSection) imagesSection.classList.add('drag-over');
    }

    function unhighlight() {
        dropZone.classList.remove('drag-over');
        if (imagesSection) imagesSection.classList.remove('drag-over');
    }

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        handleImageFiles(files);
    }
}

// Toggle sidebar
function toggleSidebar() {
    const sidebar = document.getElementById("sidebar");
    const main = document.querySelector(".main-content");

    if (sidebar) {
        sidebar.classList.toggle("show");
        if (main) main.classList.toggle("overlay");
    }
}

// Show different sections
function showSection(section) {
    if (section === 'dashboard') {
        window.location.href = 'dashboard_admin.html';
    } else if (section === 'orders') {
        window.location.href = 'orders_admin.html';
    } else if (section === 'admins') {
        window.location.href = 'admins_admin.html';
    } else if (section === 'reports') {
        window.location.href = 'reports_admin.html';
    } else if (section === 'products') {
        // Close sidebar on mobile after selection
        if (window.innerWidth <= 768) {
            const sidebar = document.getElementById("sidebar");
            const main = document.querySelector(".main-content");
            if (sidebar && main) {
                sidebar.classList.remove("show");
                main.classList.remove("overlay");
            }
        }
    }
}

// Add refresh function
async function refreshProducts() {
    try {
        const refreshBtn = document.querySelector('.refresh-btn');
        if (refreshBtn) {
            refreshBtn.disabled = true;
            refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        }

        await loadProducts();
        applyFilters();
        showNotification('รีเฟรชข้อมูลแล้ว', 'success');
    } catch (error) {
        console.error('Error refreshing products:', error);
        showNotification('เกิดข้อผิดพลาดในการรีเฟรชข้อมูล', 'error');
    } finally {
        const refreshBtn = document.querySelector('.refresh-btn');
        if (refreshBtn) {
            refreshBtn.disabled = false;
            refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i>';
        }
    }
}

// Enhanced event listeners setup
function setupEventListeners() {
    // Search input with debounce
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', debounceSearch);
    }

    // Filter dropdowns
    const categoryFilter = document.getElementById('categoryFilter');
    if (categoryFilter) {
        categoryFilter.addEventListener('change', applyFilters);
    }

    const stockFilter = document.getElementById('stockFilter');
    if (stockFilter) {
        stockFilter.addEventListener('change', applyFilters);
    }

    const sortFilter = document.getElementById('sortFilter');
    if (sortFilter) {
        sortFilter.addEventListener('change', onSortChange);
    }

    // Date filters
    const startDateFilter = document.getElementById('startDateFilter');
    if (startDateFilter) {
        startDateFilter.addEventListener('change', applyFilters);
    }

    const endDateFilter = document.getElementById('endDateFilter');
    if (endDateFilter) {
        endDateFilter.addEventListener('change', applyFilters);
    }

    // Modal close events
    const productModal = document.getElementById('productModal');
    if (productModal) {
        productModal.addEventListener('click', function (e) {
            if (e.target === this) {
                closeModal();
            }
        });
    }

    const productViewModal = document.getElementById('productViewModal');
    if (productViewModal) {
        productViewModal.addEventListener('click', function (e) {
            if (e.target === this) {
                closeViewModal();
            }
        });
    }

    const imageGalleryModal = document.getElementById('imageGalleryModal');
    if (imageGalleryModal) {
        imageGalleryModal.addEventListener('click', function (e) {
            if (e.target === this) {
                closeImageGallery();
            }
        });
    }

    // Keyboard shortcuts
    document.addEventListener('keydown', function (e) {
        // Close modals with Escape key
        if (e.key === 'Escape') {
            if (productModal && productModal.style.display === 'block') {
                closeModal();
            }
            if (productViewModal && productViewModal.style.display === 'block') {
                closeViewModal();
            }
            if (imageGalleryModal && imageGalleryModal.style.display === 'block') {
                closeImageGallery();
            }
        }

        // Gallery navigation with arrow keys
        if (imageGalleryModal && imageGalleryModal.style.display === 'block') {
            if (e.key === 'ArrowLeft') {
                e.preventDefault();
                navigateImage(-1);
            } else if (e.key === 'ArrowRight') {
                e.preventDefault();
                navigateImage(1);
            }
        }

        // Ctrl+N or Cmd+N for new product
        if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
            e.preventDefault();
            openAddModal();
        }

        // Ctrl+F or Cmd+F for search focus
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }
    });

    // Paste image functionality
    document.addEventListener('paste', function (e) {
        if (productModal && productModal.style.display === 'block') {
            const items = e.clipboardData.items;
            for (let i = 0; i < items.length; i++) {
                if (items[i].type.indexOf('image') !== -1) {
                    const file = items[i].getAsFile();
                    handleImageFiles([file]);
                    showNotification('วางรูปภาพจากคลิปบอร์ดแล้ว', 'success');
                    break;
                }
            }
        }
    });

    // Close sidebar when clicking outside (mobile only)
    document.addEventListener("click", function (e) {
        const sidebar = document.getElementById("sidebar");
        const toggle = document.querySelector(".navbar-toggle");
        const main = document.querySelector(".main-content");

        if (!sidebar) return;

        const clickedOutside = !sidebar.contains(e.target) &&
            (!toggle || !toggle.contains(e.target));

        if (sidebar.classList.contains("show") && clickedOutside && window.innerWidth <= 768) {
            sidebar.classList.remove("show");
            if (main) main.classList.remove("overlay");
        }
    });
}

// Export functions for global access
window.viewProduct = viewProduct;
window.editProduct = editProduct;
window.deleteProduct = deleteProduct;
window.openAddModal = openAddModal;
window.closeModal = closeModal;
window.closeViewModal = closeViewModal;
window.editProductFromView = editProductFromView;
window.deleteProductFromView = deleteProductFromView;
window.changeMainImage = changeMainImage;
window.changePage = changePage;
window.toggleSidebar = toggleSidebar;
window.showSection = showSection;
window.clearDateFilters = clearDateFilters;
window.resetAllFilters = resetAllFilters;
window.viewProductImages = viewProductImages;
window.viewImage = viewImage;
window.removeImage = removeImage;
window.closeImageGallery = closeImageGallery;
window.navigateImage = navigateImage;
window.onSortChange = onSortChange;
window.applyFilters = applyFilters;
window.debounceSearch = debounceSearch;
window.refreshProducts = refreshProducts;
window.showNotification = showNotification;

// Main initialization
document.addEventListener('DOMContentLoaded', function () {
    console.log('Initializing Products Management System...');

    Promise.resolve()
        .then(() => {
            setupEventListeners();
            setupDragAndDrop();
            setupProductForm();
            return loadInitialData();
        })
        .then(() => {
            console.log('Products Management System initialized successfully');
        })
        .catch(error => {
            console.error('Failed to initialize Products Management System:', error);
            showNotification('ระบบไม่สามารถเริ่มต้นได้: ' + error.message, 'error');
        });
});