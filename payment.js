// State Management
let addresses = [];
let selectedAddressId = null;
let uploadedFile = null;
let provinces = [];
let currentCart = null;

// Flags to prevent duplicate loading
let provincesLoaded = false;
let cartLoaded = false;
let addressesLoaded = false;

// Debounce utility
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

document.addEventListener("DOMContentLoaded", () => {
    // Only initialize once
    if (document.readyState === 'loading') return;
    
    initializeEventListeners();
    loadProvinces();
    loadCart();
});

// Initialize all event listeners
function initializeEventListeners() {
    const paymentForm = document.getElementById("paymentForm");
    if (paymentForm && !paymentForm.hasAttribute('data-initialized')) {
        paymentForm.addEventListener("submit", handleFormSubmit);
        paymentForm.setAttribute('data-initialized', 'true');
    }

    const slipUpload = document.getElementById("slipUpload");
    const fileUploadSection = document.getElementById("fileUploadSection");

    if (slipUpload && !slipUpload.hasAttribute('data-initialized')) {
        slipUpload.addEventListener("change", handleFileSelect);
        slipUpload.removeAttribute("multiple");
        slipUpload.setAttribute('data-initialized', 'true');
    }

    if (fileUploadSection && !fileUploadSection.hasAttribute('data-initialized')) {
        fileUploadSection.addEventListener("dragover", handleDragOver);
        fileUploadSection.addEventListener("dragleave", handleDragLeave);
        fileUploadSection.addEventListener("drop", handleFileDrop);
        fileUploadSection.setAttribute('data-initialized', 'true');
    }

    const addAddressBtn = document.getElementById("addAddressBtn");
    const closeModal = document.getElementById("closeModal");
    const cancelBtn = document.getElementById("cancelBtn");
    const saveAddressBtn = document.getElementById("saveAddressBtn");

    if (addAddressBtn && !addAddressBtn.hasAttribute('data-initialized')) {
        addAddressBtn.addEventListener("click", openAddressModal);
        addAddressBtn.setAttribute('data-initialized', 'true');
    }

    if (closeModal && !closeModal.hasAttribute('data-initialized')) {
        closeModal.addEventListener("click", closeAddressModal);
        closeModal.setAttribute('data-initialized', 'true');
    }

    if (cancelBtn && !cancelBtn.hasAttribute('data-initialized')) {
        cancelBtn.addEventListener("click", closeAddressModal);
        cancelBtn.setAttribute('data-initialized', 'true');
    }

    if (saveAddressBtn && !saveAddressBtn.hasAttribute('data-initialized')) {
        saveAddressBtn.addEventListener("click", saveAddress);
        saveAddressBtn.setAttribute('data-initialized', 'true');
    }

    // Add cancel button event listener
    const cancelOrderBtn = document.querySelector('.btn-cancel');
    if (cancelOrderBtn && !cancelOrderBtn.hasAttribute('data-initialized')) {
        cancelOrderBtn.addEventListener('click', handleCancelClick);
        cancelOrderBtn.setAttribute('data-initialized', 'true');
    }
}

function showToast(message, type = "success") {
    const container = document.getElementById("toastContainer");
    if (!container) return;
    
    const toast = document.createElement("div");
    toast.className = `toast toast-${type}`;

    const content = document.createElement("div");
    content.className = "toast-content";
    content.textContent = message;

    const closeBtn = document.createElement("button");
    closeBtn.className = "toast-close";
    closeBtn.innerHTML = "&times;";
    closeBtn.onclick = () => toast.remove();

    toast.appendChild(content);
    toast.appendChild(closeBtn);
    container.appendChild(toast);

    setTimeout(() => toast.classList.add("show"), 100);

    setTimeout(() => {
        toast.classList.add("hide");
        setTimeout(() => toast.remove(), 400);
    }, 4000);
}

function formatCurrency(amount) {
    return new Intl.NumberFormat("th-TH", {
        style: "currency",
        currency: "THB"
    }).format(amount);
}

// Enhanced weight formatting with better fallback handling
function formatWeight(weight, unit = 'kg') {
    if (!weight || weight === 0 || isNaN(weight)) {
        return '0 kg';
    }
    
    const numWeight = parseFloat(weight);
    
    if (unit === 'g' && numWeight >= 1000) {
        return `${(numWeight / 1000).toFixed(2)} kg`;
    } else if (unit === 'kg' && numWeight < 1) {
        return `${(numWeight * 1000).toFixed(0)} g`;
    }
    
    return `${numWeight.toFixed(2)} ${unit}`;
}

// Optimized province loading with duplicate prevention
async function loadProvinces() {
    if (provincesLoaded) {
        console.log('Provinces already loaded, skipping...');
        return;
    }

    try {
        provincesLoaded = true;
        
        const response = await fetch('controllers/address_api.php?action=get_provinces', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            provinces = data.data || [];
            console.log('Provinces loaded successfully:', provinces.length);
        } else {
            throw new Error(data.message || 'Failed to load provinces');
        }
    } catch (error) {
        provincesLoaded = false; // Reset flag on error
        console.error('Error loading provinces:', error);
        showToast("ไม่สามารถโหลดข้อมูลจังหวัดได้", "error");
    }
}

// Optimized cart loading with duplicate prevention
async function loadCart() {
    if (cartLoaded) {
        console.log('Cart already loaded, skipping...');
        return;
    }

    const orderSummary = document.getElementById("orderSummary");
    if (!orderSummary) return;
    
    orderSummary.innerHTML = "<p style='text-align: center; color: #6c757d;'>กำลังโหลด...</p>";

    try {
        cartLoaded = true;
        
        const response = await fetch('controllers/get_cart.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin'
        });

        if (!response.ok) {
            if (response.status === 401) {
                throw new Error('กรุณาล็อกอินก่อนใช้งาน');
            }
            throw new Error('ไม่สามารถโหลดข้อมูลได้');
        }

        const data = await response.json();
        console.log('Cart data loaded successfully');

        if (data.success) {
            currentCart = data;
            renderCart(data);
            populateCustomerData(data.customer);
            
            // Load addresses only once
            await loadUserAddresses();
            if (data.address) {
                selectAddressByData(data.address);
            }

            checkWeightValidation();
        } else {
            throw new Error(data.message);
        }

    } catch (error) {
        cartLoaded = false; // Reset flag on error
        console.error('Error loading cart:', error);
        handleCartError(error, orderSummary);
    }
}

// Centralized weight calculation with caching
function calculateCurrentWeight() {
    if (!currentCart?.cart?.items) return 0;
    
    // Use cached weight if available and reliable
    if (currentCart.cart.totalWeight && currentCart.cart.totalWeight > 0) {
        return currentCart.cart.totalWeight;
    }
    
    // Calculate weight from items
    let totalWeight = 0;
    currentCart.cart.items.forEach(item => {
        if (item.weight && !isNaN(item.weight) && item.weight > 0) {
            let weight = parseFloat(item.weight);
            const unit = (item.weight_unit || 'kg').toLowerCase();
            
            // Convert to kg
            if (unit === 'g' || unit === 'gram') {
                weight = weight / 1000;
            }
            
            totalWeight += weight * item.quantity;
        }
    });
    
    // Cache the calculated weight
    if (currentCart.cart) {
        currentCart.cart.totalWeight = totalWeight;
    }
    
    return totalWeight;
}

function checkWeightValidation() {
    if (!currentCart || !currentCart.cart || !currentCart.cart.items) {
        return;
    }

    const totalWeight = calculateCurrentWeight();
    const submitBtn = document.querySelector("button[type=submit]");
    const weightWarningContainer = document.getElementById("weightWarningContainer");
    
    // Remove existing weight warning if any
    if (weightWarningContainer) {
        weightWarningContainer.remove();
    }

    if (totalWeight > 1000) {
        // Hide submit button and show weight warning
        if (submitBtn) {
            submitBtn.style.display = 'none';
        }

        showWeightExceededWarning(totalWeight, 1000, "น้ำหนักเกินขีดจำกัดที่กำหนด");

    } else {
        // Show submit button if weight is within limit
        if (submitBtn) {
            submitBtn.style.display = 'inline-block';
        }
    }
}

function handleCartError(error, orderSummary) {
    if (error.message.includes('ล็อกอิน')) {
        showToast("กรุณาล็อกอินก่อนใช้งาน", "error");
        setTimeout(() => {
            window.location.href = 'login.php';
        }, 2000);
    } else {
        showToast(error.message || "ไม่สามารถโหลดข้อมูลได้", "error");
        orderSummary.innerHTML = "<p style='text-align: center; color: #dc3545;'>เกิดข้อผิดพลาด</p>";
    }
}

// Optimized address loading with duplicate prevention
async function loadUserAddresses() {
    if (addressesLoaded) {
        console.log('Addresses already loaded, skipping...');
        return;
    }

    try {
        addressesLoaded = true;
        
        const response = await fetch('controllers/address_api.php?action=get_by_user', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin'
        });

        if (!response.ok) {
            if (response.status === 401) {
                console.log('User not authenticated for addresses');
                return;
            }
            throw new Error('ไม่สามารถโหลดที่อยู่ได้');
        }

        const data = await response.json();

        if (data.success) {
            addresses = (data.data || []).map(addr => ({
                id: addr.address_id,
                name: addr.recipient_name,
                phone: addr.phone || '',
                address: addr.address_line,
                subdistrict: addr.subdistrict || '',
                district: addr.district || '',
                province_id: addr.province_id,
                province: addr.province_name || addr.province || '',
                zipCode: addr.postal_code,
                is_main: addr.is_main
            }));

            const mainAddress = addresses.find(addr => addr.is_main == 1);
            if (mainAddress && !selectedAddressId) {
                selectedAddressId = mainAddress.id;
            }

            renderAddresses();
            console.log('Addresses loaded successfully:', addresses.length);
        } else {
            throw new Error(data.message || 'Failed to load addresses');
        }
    } catch (error) {
        addressesLoaded = false; // Reset flag on error
        console.error('Error loading addresses:', error);
    }
}

function selectAddressByData(addressData) {
    if (!addressData || !addressData.address_id) return;

    const existingAddress = addresses.find(addr => addr.id == addressData.address_id);
    if (existingAddress) {
        selectedAddressId = existingAddress.id;
        renderAddresses();
    }
}

// Load one address and prefill modal for editing
async function editAddress(addressId) {
    try {
        const response = await fetch(`controllers/address_api.php?action=get&address_id=${addressId}`, {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error('ไม่สามารถโหลดข้อมูลที่อยู่ได้');
        }

        const result = await response.json();
        if (!result.success || !result.data) {
            throw new Error(result.message || 'ไม่พบข้อมูลที่อยู่');
        }

        const addr = result.data;

        // Ensure provinces are loaded for the select
        await loadProvinces();

        // Open modal and prefill
        openAddressModal();
        const form = document.getElementById('addressForm');
        if (!form) return;

        const setVal = (selector, value) => {
            const el = form.querySelector(selector);
            if (el) el.value = value || '';
        };

        setVal('#address_id', addr.address_id);
        setVal('input[name="addressName"]', addr.recipient_name);
        setVal('input[name="phone"]', addr.phone);
        setVal('textarea[name="fullAddress"]', addr.address_line);
        setVal('input[name="subdistrict"]', addr.subdistrict);
        setVal('input[name="district"]', addr.district);
        setVal('input[name="zipCode"]', addr.postal_code);

        const provinceSelect = form.querySelector('select[name="province_id"]');
        if (provinceSelect) {
            // Populate options if not yet populated
            if (provinceSelect.options.length <= 1 && provinces.length > 0) {
                provinceSelect.innerHTML = '<option value="">เลือกจังหวัด *</option>';
                provinces.forEach(p => {
                    const option = document.createElement('option');
                    option.value = p.province_id;
                    option.textContent = p.name;
                    provinceSelect.appendChild(option);
                });
            }
            provinceSelect.value = addr.province_id || '';
        }

    } catch (error) {
        console.error('Error loading address for edit:', error);
        showToast(error.message || 'เกิดข้อผิดพลาดในการโหลดข้อมูลที่อยู่', 'error');
    }
}

// Debounced shipping recalculation
const debouncedRecalculateShipping = debounce(async (provinceId) => {
    return await performShippingRecalculation(provinceId);
}, 300);

async function recalculateShipping(provinceId) {
    return await debouncedRecalculateShipping(provinceId);
}

// Core shipping recalculation logic
async function performShippingRecalculation(provinceId) {
    if (!provinceId || !currentCart?.cart?.items?.length) {
        console.log('Missing data for shipping calculation');
        return false;
    }

    // Check if we need to recalculate (same province check)
    const currentProvince = currentCart.cart.shipping?.province_id;
    if (currentProvince && currentProvince == provinceId) {
        console.log('Same province, skipping recalculation');
        return true;
    }

    try {
        console.log('Recalculating shipping for province:', provinceId);

        const formData = new FormData();
        formData.append('action', 'recalculate_cart');
        formData.append('province_id', provinceId);

        const response = await fetch('controllers/shipping_api.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        // Handle weight exceeded case (422 status)
        if (response.status === 422) {
            const result = await response.json();
            if (result.weight_validation && !result.weight_validation.success) {
                // Update cart with partial data (subtotal + tax only)
                if (result.data) {
                    updateCartTotals(result.data);
                    updateCartSummary();
                }
                
                // Show weight warning and hide submit button
                handleWeightValidationError(result.weight_validation);
                checkWeightValidation();
                
                return false; // Cannot proceed with order
            }
        }

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: เกิดข้อผิดพลาดในการคํานวณค่าส่ง`);
        }

        const result = await response.json();
        console.log('Shipping recalculation completed successfully');

        if (result.success && result.data) {
            updateCartTotals(result.data);
            updateCartSummary();
            checkWeightValidation();
            return true;
        } else {
            throw new Error(result.message || 'ไม่สามารถคํานวณค่าส่งได้');
        }

    } catch (error) {
        console.error('Error recalculating shipping:', error);
        showToast(error.message || "เกิดข้อผิดพลาดในการคํานวณค่าส่ง", "error");
        return false;
    }
}

function updateCartSummary() {
    if (!currentCart?.cart) return;
    
    const summaryContainer = document.querySelector('.cart-summary');
    if (summaryContainer) {
        const newSummaryHtml = renderCartSummary(currentCart.cart);
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = newSummaryHtml;
        summaryContainer.replaceWith(tempDiv.firstElementChild);
    }
}

function updateCartTotals(newData) {
    if (!currentCart?.cart) return;
    
    const cart = currentCart.cart;
    cart.totalWeight = newData.total_weight || cart.totalWeight;
    cart.shipping = newData.shipping || cart.shipping;
    cart.taxRate = newData.tax?.rate || cart.taxRate;
    cart.taxAmount = newData.tax?.amount || cart.taxAmount;
    cart.grandTotal = newData.grand_total || cart.grandTotal;
    cart.subTotal = newData.subtotal || cart.subTotal;
    cart.totalItems = newData.total_items || cart.totalItems;
    
    // Add province tracking and weight validation status
    if (cart.shipping && newData.province_id) {
        cart.shipping.province_id = newData.province_id;
    }
    
    // Store weight validation status
    if (newData.weight_validation) {
        cart.weight_validation = newData.weight_validation;
    }
    
    // Store order capability status
    if (typeof newData.can_order !== 'undefined') {
        cart.can_order = newData.can_order;
    }
}

// Optimized cart rendering with separated concerns
function renderCart(data) {
    const { customer, cart } = data;
    const { items } = cart;
    const orderSummary = document.getElementById("orderSummary");

    if (!items || items.length === 0) {
        orderSummary.innerHTML = `<p style='text-align: center; color: #6c757d;'>ไม่มีสินค้าในตะกร้า</p>`;
        return;
    }

    // Render items section
    const itemsHtml = renderCartItems(items);
    
    // Render summary section  
    const summaryHtml = renderCartSummary(cart);
    
    orderSummary.innerHTML = itemsHtml + summaryHtml;
}

function renderCartItems(items) {
    let html = `<div class="cart-items">`;
    
    items.forEach((item, index) => {
        const imageUrl = item.image || getDefaultImage();
        
        html += `
            <div class="cart-item">
                <img src="${imageUrl}" alt="${item.name}" class="cart-item-image" 
                     onerror="this.src='${getDefaultImage()}'">
                <div class="cart-item-info">
                    <div class="cart-item-name">${item.name}</div>
                    <div class="cart-item-quantity">จำนวน: ${item.quantity}</div>
                    <div class="cart-item-price">ราคาต่อชิ้น: ${formatCurrency(item.price)}</div>
                </div>
            </div>
        `;
    });
    
    html += `</div>`;
    return html;
}

function calculateItemWeightDisplay(item) {
    if (!item.weight || item.weight === 0 || isNaN(item.weight)) {
        return 'ไม่ระบุ';
    }
    
    let itemWeight = parseFloat(item.weight);
    let weightUnit = (item.weight_unit || 'kg').toLowerCase();
    
    if (weightUnit === 'g' || weightUnit === 'gram') {
        itemWeight = itemWeight / 1000;
    }
    
    const totalItemWeight = itemWeight * item.quantity;
    let weightDisplay = formatWeight(totalItemWeight, 'kg');
    
    if (totalItemWeight !== itemWeight) {
        const unitWeight = formatWeight(item.weight, item.weight_unit || 'kg');
        weightDisplay += ` (${unitWeight} ต่อชิ้น)`;
    }
    
    return weightDisplay;
}

// Separated cart summary rendering for efficient updates
function renderCartSummary(cart) {
    const { totalItems, totalWeight, subTotal, shipping, taxRate, taxAmount, grandTotal } = cart;
    
    let html = `<div class="cart-summary">`;
    
    // Subtotal
    html += `
        <div class="summary-row">
            <span>รวมสินค้า (${totalItems} ชิ้น):</span> 
            <span>${formatCurrency(subTotal)}</span>
        </div>`;
    
    // Weight with warning if over limit
    const weightDisplay = formatWeight(totalWeight, 'kg');
    const weightClass = totalWeight > 1000 ? 'weight-warning' : '';
    html += `
        <div class="summary-row ${weightClass}">
            <span>น้ำหนักรวม:</span> 
            <span style="${totalWeight > 1000 ? 'color: #dc3545; font-weight: bold;' : ''}">${weightDisplay}</span>
        </div>`;
    
    // Shipping - handle weight exceeded case
    let shippingCost = extractShippingCost(shipping);
    let shippingDisplay = '';
    
    if (totalWeight > 1000) {
        shippingDisplay = `
            <div class="summary-row">
                <span>ค่าจัดส่ง:</span> 
                <span style="font-weight: bold; color: #dc3545;">ไม่สามารถคํานวณได้</span>
            </div>`;
    } else {
        shippingDisplay = `
            <div class="summary-row">
                <span>ค่าจัดส่ง:</span> 
                <span>${shippingCost === 0 ? "ฟรี" : formatCurrency(shippingCost)}</span>
            </div>`;
    }
    html += shippingDisplay;
    
    // Tax
    html += `
        <div class="summary-row">
            <span>ภาษี ${((taxRate || 0.07) * 100).toFixed(0)}%:</span> 
            <span>${formatCurrency(taxAmount)}</span>
        </div>`;
    
    // Grand total with conditional note
    if (totalWeight > 1000) {
        html += `
            <div class="summary-row total" style="border-top: 2px solid #dee2e6; padding-top: 10px;">
                <span>รวมทั้งหมด (ไม่รวมค่าส่ง):</span> 
                <span>${formatCurrency(grandTotal)}</span>
            </div>`;
    } else {
        html += `
            <div class="summary-row total" style="border-top: 2px solid #dee2e6; padding-top: 10px;">
                <span>รวมทั้งหมด:</span> 
                <span>${formatCurrency(grandTotal)}</span>
            </div>`;
    }
    
    html += `</div>`;
    return html;
}

function extractShippingCost(shipping) {
    if (!shipping) return 0;
    
    // Handle weight exceeded case where shipping cost is null
    if (shipping.weight_exceeded || shipping.cost === null) {
        return null;
    }
    
    if (typeof shipping === 'object') {
        return shipping.cost || 0;
    }
    
    return shipping || 0;
}

function getDefaultImage() {
    return 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\' viewBox=\'0 0 100 100\'%3E%3Crect width=\'100\' height=\'100\' fill=\'%23f0f0f0\'/%3E%3Ctext x=\'50\' y=\'50\' font-family=\'Arial\' font-size=\'12\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23666\'%3ENo Image%3C/text%3E%3C/svg%3E';
}

function populateCustomerData(customer) {
    if (customer.name) {
        const nameField = document.getElementById("fullName");
        if (nameField && !nameField.value) {
            nameField.value = customer.name;
        }
    }
    if (customer.email) {
        const emailField = document.getElementById("email");
        if (emailField && !emailField.value) {
            emailField.value = customer.email;
        }
    }
    if (customer.phone) {
        const phoneField = document.getElementById("phone");
        if (phoneField && !phoneField.value) {
            phoneField.value = customer.phone;
        }
    }
}

// File upload handlers
function handleFileSelect(e) {
    const files = e.target.files;
    if (files.length > 0) {
        processFile(files[0]);
    }
}

function handleDragOver(e) {
    e.preventDefault();
    e.currentTarget.classList.add("dragover");
}

function handleDragLeave(e) {
    e.preventDefault();
    e.currentTarget.classList.remove("dragover");
}

function handleFileDrop(e) {
    e.preventDefault();
    e.currentTarget.classList.remove("dragover");
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        processFile(files[0]);
    }
}

function processFile(file) {
    const uploadedFilesContainer = document.getElementById("uploadedFiles");
    if (!uploadedFilesContainer) return;

    if (!validateFile(file)) return;

    uploadedFile = null;
    uploadedFilesContainer.innerHTML = '';

    const fileId = Date.now() + Math.random();
    uploadedFile = { id: fileId, file: file };

    const fileItem = createFileItem(fileId, file);
    uploadedFilesContainer.appendChild(fileItem);
}

function validateFile(file) {
    const maxSize = 5 * 1024 * 1024; // 5MB
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];

    if (file.size > maxSize) {
        showToast("ไฟล์มีขนาดใหญ่เกินไป (เกิน 5MB)", "error");
        return false;
    }

    if (!allowedTypes.includes(file.type)) {
        showToast("ประเภทไฟล์ไม่ถูกต้อง", "error");
        return false;
    }

    return true;
}

function createFileItem(fileId, file) {
    const fileItem = document.createElement("div");
    fileItem.className = "uploaded-file-item";
    fileItem.dataset.fileId = fileId;

    const fileExtension = file.name.split('.').pop().toUpperCase();
    const fileSize = formatFileSize(file.size);

    fileItem.innerHTML = `
        <div class="file-info">
            <div class="file-icon">${fileExtension}</div>
            <div class="file-details">
                <div class="file-name">${file.name}</div>
                <div class="file-size">${fileSize}</div>
            </div>
        </div>
        <div class="file-actions">
            ${file.type.startsWith('image/') ? '<button type="button" class="file-preview-btn" onclick="previewFile(\'' + fileId + '\')">ดู</button>' : ''}
            <button type="button" class="file-remove-btn" onclick="removeFile('${fileId}')">ลบ</button>
        </div>
    `;

    return fileItem;
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function removeFile(fileId) {
    uploadedFile = null;
    const uploadedFilesContainer = document.getElementById("uploadedFiles");
    if (uploadedFilesContainer) {
        uploadedFilesContainer.innerHTML = '';
    }
}

function previewFile(fileId) {
    if (!uploadedFile || uploadedFile.id != fileId || !uploadedFile.file.type.startsWith('image/')) return;

    const reader = new FileReader();
    reader.onload = function (e) {
        const modal = document.createElement("div");
        modal.className = "modal-overlay active";
        modal.style.zIndex = "9999";
        modal.innerHTML = `
            <div style="background: white; border-radius: 12px; padding: 20px; max-width: 90%; max-height: 90%; overflow: auto; position: relative;">
                <button onclick="this.closest('.modal-overlay').remove()" style="position: absolute; top: 10px; right: 15px; background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
                <img src="${e.target.result}" alt="Preview" style="max-width: 100%; max-height: 70vh; object-fit: contain; border-radius: 8px;">
            </div>
        `;
        document.body.appendChild(modal);
    };
    reader.readAsDataURL(uploadedFile.file);
}

// Address management
function openAddressModal() {
    const modal = document.getElementById("addressModal");
    const form = document.getElementById("addressForm");

    if (!modal || !form) return;

    form.reset();
    // Clear hidden id when opening fresh
    const hiddenId = form.querySelector('#address_id');
    if (hiddenId) hiddenId.value = '';

    const provinceSelect = form.querySelector('select[name="province_id"]');
    if (provinceSelect) {
        provinceSelect.innerHTML = '<option value="">เลือกจังหวัด *</option>';

        if (provinces.length > 0) {
            provinces.forEach(province => {
                const option = document.createElement('option');
                option.value = province.province_id;
                option.textContent = province.name;
                provinceSelect.appendChild(option);
            });
        } else {
            showToast("กำลังโหลดข้อมูลจังหวัด กรุณารอสักครู่", "loading");
            loadProvinces().then(() => {
                if (provinces.length > 0) {
                    openAddressModal();
                } else {
                    showToast("ไม่สามารถโหลดข้อมูลจังหวัดได้", "error");
                }
            });
            return;
        }
    }

    modal.classList.add("active");
}

function closeAddressModal() {
    const modal = document.getElementById("addressModal");
    const form = document.getElementById("addressForm");
    
    if (modal) modal.classList.remove("active");
    if (form) form.reset();
}

async function saveAddress() {
    const form = document.getElementById("addressForm");
    if (!form) return;
    
    const formData = new FormData(form);

    const requiredFields = [
        { name: 'addressName', label: 'ชื่อผู้รับ' },
        { name: 'fullAddress', label: 'ที่อยู่เต็ม' },
        { name: 'subdistrict', label: 'ตำบล/แขวง' },
        { name: 'district', label: 'อำเภอ/เขต' },
        { name: 'province_id', label: 'จังหวัด' },
        { name: 'zipCode', label: 'รหัสไปรษณีย์' }
    ];

    for (const field of requiredFields) {
        const value = formData.get(field.name);
        if (!value || value.trim() === '') {
            showToast(`กรุณากรอก${field.label}`, "error");
            return;
        }
    }

    const zipCode = formData.get('zipCode');
    if (!/^\d{5}$/.test(zipCode)) {
        showToast("รหัสไปรษณีย์ต้องเป็นตัวเลข 5 หลัก", "error");
        return;
    }

    const editingAddressId = formData.get('address_id');
    const isEditing = editingAddressId && String(editingAddressId).trim() !== '';

    const addressData = {
        action: isEditing ? 'update' : 'create',
        recipient_name: formData.get("addressName").trim(),
        address_line: formData.get("fullAddress").trim(),
        subdistrict: formData.get("subdistrict").trim(),
        district: formData.get("district").trim(),
        province_id: formData.get("province_id"),
        postal_code: formData.get("zipCode").trim(),
        phone: formData.get("phone") ? formData.get("phone").trim() : '',
        is_main: addresses.length === 0 ? 1 : 0
    };

    if (isEditing) {
        addressData.address_id = editingAddressId;
    }

    try {
        const apiFormData = new FormData();
        Object.keys(addressData).forEach(key => {
            apiFormData.append(key, addressData[key]);
        });

        const response = await fetch('controllers/address_api.php', {
            method: 'POST',
            body: apiFormData,
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error('เกิดข้อผิดพลาดในการบันทึก');
        }

        const result = await response.json();

        if (result.success) {
            // Reset addresses loaded flag to force reload
            addressesLoaded = false;
            await loadUserAddresses();
            closeAddressModal();
            showToast(isEditing ? "แก้ไขที่อยู่สำเร็จ" : "เพิ่มที่อยู่สำเร็จ", "success");
        } else {
            throw new Error(result.message || 'ไม่สามารถบันทึกได้');
        }

    } catch (error) {
        console.error('Error saving address:', error);
        showToast(error.message || "เกิดข้อผิดพลาดในการบันทึก", "error");
    }
}

function renderAddresses() {
    const addressList = document.getElementById("addressList");
    if (!addressList) return;

    if (addresses.length === 0) {
        addressList.innerHTML = `
            <p style="color: #6c757d; text-align: center; padding: 20px;">
                ยังไม่มีที่อยู่จัดส่ง กรุณาเพิ่มที่อยู่ใหม่
            </p>
        `;
        return;
    }

    let html = "";
    addresses.forEach(address => {
        const isSelected = selectedAddressId == address.id;

        let addressDisplay = address.address;
        if (address.subdistrict) addressDisplay += ` ${address.subdistrict}`;
        if (address.district) addressDisplay += ` ${address.district}`;
        if (address.province) addressDisplay += ` ${address.province}`;
        if (address.zipCode) addressDisplay += ` ${address.zipCode}`;

        html += `
            <div class="address-item ${isSelected ? 'selected' : ''}" onclick="selectAddress(${address.id})">
                <div class="address-name">${address.name} ${address.is_main == 1 ? '(หลัก)' : ''}</div>
                <div class="address-info">
                    ${address.phone || ''}<br>
                    ${addressDisplay}
                </div>
                <div class="address-actions">
                    <button type="button" class="btn btn-default" onclick="event.stopPropagation(); selectAddress(${address.id})" ${isSelected ? 'style="background: #6c757d;" disabled' : ''}>
                        ${isSelected ? 'เลือกแล้ว' : 'เลือก'}
                    </button>
                    <button type="button" class="btn-edit-address" onclick="event.stopPropagation(); editAddress(${address.id})">แก้ไข</button>
                    <button type="button" class="btn btn-delete" onclick="event.stopPropagation(); deleteAddress(${address.id})">ลบ</button>
                </div>
            </div>
        `;
    });

    addressList.innerHTML = html;
}

// Enhanced address selection with proper shipping recalculation
async function selectAddress(addressId) {
    try {
        // Update UI immediately for better UX
        const previousSelection = selectedAddressId;
        selectedAddressId = addressId;
        renderAddresses();
        
        const formData = new FormData();
        formData.append('action', 'set_main');
        formData.append('address_id', addressId);

        const response = await fetch('controllers/address_api.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error('เกิดข้อผิดพลาดในการตั้งที่อยู่หลัก');
        }

        const result = await response.json();

        if (result.success) {
            // Update addresses state
            addresses.forEach(addr => {
                addr.is_main = addr.id == addressId ? 1 : 0;
            });

            // Only recalculate shipping if province changed
            const selectedAddress = addresses.find(addr => addr.id == addressId);
            if (selectedAddress?.province_id) {
                const currentProvinceId = currentCart?.cart?.shipping?.province_id;
                
                if (!currentProvinceId || currentProvinceId != selectedAddress.province_id) {
                    const success = await recalculateShipping(selectedAddress.province_id);
                    if (success) {
                        showToast("เลือกที่อยู่สำเร็จ", "success");
                    } else {
                        showToast("เลือกที่อยู่สำเร็จ", "success");
                    }
                } else {
                    showToast("เลือกที่อยู่สำเร็จ", "success");
                }
            }
        } else {
            // Revert UI state on failure
            selectedAddressId = previousSelection;
            renderAddresses();
            throw new Error(result.message || 'ไม่สามารถตั้งเป็นที่อยู่หลักได้');
        }

    } catch (error) {
        // Revert UI state on error
        selectedAddressId = previousSelection || null;
        renderAddresses();
        
        console.error('Error selecting address:', error);
        showToast(error.message || "เกิดข้อผิดพลาดในการเลือกที่อยู่", "error");
    }
}

async function deleteAddress(addressId) {
    if (!confirm("คุณต้องการลบที่อยู่นี้หรือไม่?")) return;
    
    try {
        const response = await fetch(`controllers/address_api.php?action=delete&address_id=${addressId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error('เกิดข้อผิดพลาดในการลบ');
        }

        const result = await response.json();

        if (result.success) {
            addresses = addresses.filter(addr => addr.id != addressId);
            if (selectedAddressId == addressId) {
                selectedAddressId = null;
            }
            renderAddresses();
            
            // Recalculate shipping if needed
            if (addresses.length > 0) {
                const mainAddress = addresses.find(addr => addr.is_main == 1);
                if (mainAddress) {
                    await recalculateShipping(mainAddress.province_id);
                }
            }
            
            showToast("ลบที่อยู่สำเร็จ", "success");
        } else {
            throw new Error(result.message || 'ไม่สามารถลบได้');
        }

    } catch (error) {
        console.error('Error deleting address:', error);
        showToast(error.message || "เกิดข้อผิดพลาดในการลบ", "error");
    }
}

function showWeightExceededWarning(actualWeight, limitWeight, errorMessage) {
    // Remove existing warning
    const existingWarning = document.getElementById("weightWarningContainer");
    if (existingWarning) {
        existingWarning.remove();
    }

    const warningDiv = document.createElement('div');
    warningDiv.id = 'weightWarningContainer';
    warningDiv.className = 'weight-warning-container';
    warningDiv.innerHTML = `
        <div style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); 
                    color: white; 
                    padding: 25px; 
                    border-radius: 12px; 
                    text-align: center; 
                    margin: 20px 0;
                    box-shadow: 0 4px 20px rgba(220, 53, 69, 0.4);
                    border: 2px solid rgba(255,255,255,0.1);">
            <div style="font-size: 52px; margin-bottom: 20px; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">⚠️</div>
            <h3 style="margin: 0 0 15px 0; font-size: 22px; font-weight: 700; text-shadow: 0 1px 2px rgba(0,0,0,0.2);">
                ขออภัย น้ำหนักสินค้าทั้งหมดมากกว่า ${limitWeight} กก.
            </h3>
            <div style="background: rgba(255,255,255,0.15); 
                       padding: 20px; 
                       border-radius: 10px; 
                       margin: 20px 0;
                       backdrop-filter: blur(5px);">
                <div style="font-size: 16px; line-height: 1.6; opacity: 0.9;">
                    <strong>ระบบไม่สามารถคำนวณค่าจัดส่งได้</strong><br>
                    หากต้องการสั่งซื้อเพิ่มเติม โปรดติดต่อพนักงานตามช่องทางหน้าติดต่อเรา
                </div>
            </div>
            <div style="margin-top: 25px; display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                <a href="contactus.php" 
                   style="display: inline-flex;
                          align-items: center;
                          gap: 8px;
                          background: rgba(255,255,255,0.2); 
                          color: white; 
                          padding: 15px 25px; 
                          border-radius: 10px; 
                          text-decoration: none; 
                          font-weight: 600;
                          font-size: 16px;
                          transition: all 0.3s ease;
                          backdrop-filter: blur(5px);"
                   onmouseover="this.style.background='rgba(255,255,255,0.3)'; this.style.transform='translateY(-2px)'"
                   onmouseout="this.style.background='rgba(255,255,255,0.2)'; this.style.transform='translateY(0)'">
                    📞 ติดต่อเรา
                </a>
            </div>
        </div>
    `;

    // Insert warning BEFORE the first section (customer information)
    const firstSection = document.querySelector('.section');
    if (firstSection) {
        firstSection.parentNode.insertBefore(warningDiv, firstSection);
    } else {
        // Fallback: insert at the beginning of the form content
        const content = document.querySelector('.content');
        if (content) {
            content.insertBefore(warningDiv, content.firstChild);
        }
    }
}

function handleWeightValidationError(weightValidation) {
    if (!weightValidation || weightValidation.success) {
        return;
    }
    
    const { weight, limit, error } = weightValidation;
    
    // Show weight exceeded warning
    showWeightExceededWarning(weight, limit, error);
    
    // Hide submit button
    const submitBtn = document.querySelector("button[type=submit]");
    if (submitBtn) {
        submitBtn.style.display = 'none';
    }
    
    // Show error toast
    showToast(error || "น้ำหนักเกินขีดจำกัดการจัดส่ง", "error");
}

// Enhanced form validation with weight checks
function validateForm() {
    // Check weight limit first with centralized calculation
    const totalWeight = calculateCurrentWeight();
    
    if (totalWeight > 1000) {
        showToast(`น้ำหนักรวม ${formatWeight(totalWeight, 'kg')} เกินขีดจำกัด 1,000 กก. ไม่สามารถสั่งซื้อได้`, "error");
        return false;
    }

    // Check if cart indicates order cannot proceed (from backend validation)
    if (currentCart?.cart?.can_order === false) {
        showToast("ไม่สามารถดำเนินการสั่งซื้อได้ในขณะนี้", "error");
        return false;
    }

    const requiredFields = [
        { id: 'fullName', message: 'กรุณากรอกชื่อ-นามสกุล' },
        { id: 'email', message: 'กรุณากรอกอีเมล' },
        { id: 'phone', message: 'กรุณากรอกหมายเลขโทรศัพท์' }
    ];

    for (const field of requiredFields) {
        const element = document.getElementById(field.id);
        if (!element || !element.value.trim()) {
            showToast(field.message, "error");
            element?.focus();
            return false;
        }
    }

    const email = document.getElementById('email').value;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        showToast("รูปแบบอีเมลไม่ถูกต้อง", "error");
        document.getElementById('email').focus();
        return false;
    }

    const phone = document.getElementById('phone').value;
    const phoneRegex = /^[0-9]{10}$/;
    if (!phoneRegex.test(phone.replace(/[-\s]/g, ''))) {
        showToast("หมายเลขโทรศัพท์ต้องเป็นตัวเลข 10 หลัก", "error");
        document.getElementById('phone').focus();
        return false;
    }

    if (!selectedAddressId) {
        showToast("กรุณาเลือกที่อยู่จัดส่ง", "error");
        return false;
    }

    if (!uploadedFile) {
        showToast("กรุณาอัพโหลดสลิปการโอนเงิน", "error");
        return false;
    }

    // Enhanced cart validation
    if (!currentCart || !currentCart.cart || !currentCart.cart.items || currentCart.cart.items.length === 0) {
        showToast("ตะกร้าสินค้าว่างเปล่า กรุณาเพิ่มสินค้าก่อน", "error");
        return false;
    }

    if (!currentCart.cart.grandTotal || currentCart.cart.grandTotal <= 0) {
        showToast("ราคารวมไม่ถูกต้อง กรุณาโหลดตะกร้าใหม่", "error");
        return false;
    }

    return true;
}

// Enhanced form submission with weight validation
async function handleFormSubmit(e) {
    e.preventDefault();

    // Frontend weight validation (first check)
    if (currentCart?.cart) {
        let totalWeight = calculateCurrentWeight();

        if (totalWeight > 1000) {
            showToast(`ไม่สามารถสั่งซื้อได้ น้ำหนักรวม ${formatWeight(totalWeight, 'kg')} เกินขีดจำกัด 1,000 กก.`, "error");
            return;
        }
    }

    if (!validateForm()) return;

    const submitBtn = e.target.querySelector("button[type=submit]");
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = "กำลังประมวลผล...";

    try {
        const formData = new FormData();

        // Add form fields
        formData.append('fullName', document.getElementById('fullName').value);
        formData.append('email', document.getElementById('email').value);
        formData.append('phone', document.getElementById('phone').value);
        formData.append('note', document.getElementById('note').value || '');

        const selectedAddress = addresses.find(addr => addr.id == selectedAddressId);
        if (selectedAddress) {
            formData.append('addressId', selectedAddress.id);
        }

        if (uploadedFile) {
            formData.append('paymentSlip', uploadedFile.file);
        }

        // Enhanced cart data submission
        formData.append('cartTotal', currentCart.cart.grandTotal);
        formData.append('cartItems', currentCart.cart.totalItems);
        formData.append('cartSubtotal', currentCart.cart.subTotal);
        formData.append('cartTax', currentCart.cart.taxAmount || 0);

        // Handle shipping cost properly
        let shippingCost = extractShippingCost(currentCart.cart.shipping);
        formData.append('cartShipping', shippingCost);

        // Weight data with improved calculation
        let finalWeight = calculateCurrentWeight();
        formData.append('cartWeight', finalWeight);

        // Convert cart items to JSON for database storage
        const cartItemsForDb = currentCart.cart.items.map(item => ({
            product_id: item.product_id || item.id,
            quantity: item.quantity,
            price: item.price,
            weight: item.weight || 0,
            weight_unit: item.weight_unit || 'kg',
            lot: item.lot || null
        }));
        formData.append('cartItemsJson', JSON.stringify(cartItemsForDb));

        // Add weight validation flag
        formData.append('hasValidWeight', finalWeight > 0 ? '1' : '0');


        const response = await fetch('controllers/submit_payment.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        // Handle different response statuses
        if (response.status === 422) {
            // Weight validation error from backend
            const result = await response.json();
            if (result.validation_details && result.validation_details.code === 'WEIGHT_LIMIT_EXCEEDED') {
                const { weight, limit } = result.validation_details;
                showWeightExceededWarning(weight, limit, result.message);
                showToast(result.message, "error");
                return;
            }
        }

        if (!response.ok) {
            const errorText = await response.text();
            console.error('Server response:', errorText);
            throw new Error(`HTTP ${response.status}: เกิดข้อผิดพลาดในการส่งข้อมูล`);
        }

        const result = await response.json();

        if (result.success) {
            showSuccessPopup(result.order_id || 'ORD' + Date.now());
        } else {
            // Handle specific error types
            if (result.validation_details?.code === 'WEIGHT_LIMIT_EXCEEDED') {
                handleWeightValidationError(result.validation_details);
            } else {
                throw new Error(result.message || 'ไม่สามารถส่งคำสั่งซื้อได้');
            }
        }

    } catch (error) {
        console.error('Form submission error:', error);
        showToast(error.message || "เกิดข้อผิดพลาดในการส่งข้อมูล", "error");
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
}

// Success popup function
function showSuccessPopup(orderId) {
    const popup = document.createElement('div');
    popup.className = 'modal-overlay active';
    popup.style.zIndex = '9999';
    popup.innerHTML = `
        <div class="modal" style="max-width: 500px; text-align: center;">
            <div style="padding: 40px 30px; background: linear-gradient(135deg, #28a745 0%, #34ce57 100%); color: white; border-radius: 12px 12px 0 0;">
                <div style="font-size: 60px; margin-bottom: 20px;">✅</div>
                <h2 style="margin: 0 0 10px 0; font-size: 24px;">คำสั่งซื้อสำเร็จ!</h2>
                <p style="margin: 0; opacity: 0.9;">หมายเลขคำสั่งซื้อ: ${orderId}</p>
            </div>
            <div style="padding: 30px; background: white; border-radius: 0 0 12px 12px;">
                <p style="color: #666; margin-bottom: 25px; line-height: 1.6;">
                    ขอบคุณสำหรับการสั่งซื้อ<br>
                    เราจะตรวจสอบการชำระเงินและจัดส่งสินค้าให้คุณโดยเร็วที่สุด
                </p>
                <button onclick="redirectToHome()" 
                        style="background: linear-gradient(135deg, #940606 0%, #b50707 100%); 
                               color: white; 
                               border: none; 
                               padding: 15px 30px; 
                               border-radius: 8px; 
                               font-size: 16px; 
                               font-weight: 600; 
                               cursor: pointer; 
                               transition: all 0.3s ease;
                               min-width: 150px;"
                        onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(148, 6, 6, 0.3)'"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                    กลับหน้าหลัก
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(popup);
    
    // Auto redirect after 5 seconds
    setTimeout(() => {
        redirectToHome();
    }, 5000);
}

function redirectToHome() {
    setTimeout(() => {
        window.location.href = 'home.php';
    }, 500);
}

function handleCancelClick() {
    window.location.href = 'cart.php';
}

// Force reload functions (for manual refresh if needed)
function forceReloadProvinces() {
    provincesLoaded = false;
    return loadProvinces();
}

function forceReloadCart() {
    cartLoaded = false;
    return loadCart();
}

function forceReloadAddresses() {
    addressesLoaded = false;
    return loadUserAddresses();
}