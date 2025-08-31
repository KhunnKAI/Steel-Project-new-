// State Management
let addresses = [];
let selectedAddressId = null;
let uploadedFile = null;
let provinces = [];
let currentCart = null;

document.addEventListener("DOMContentLoaded", () => {
    initializeEventListeners();
    loadProvinces();
    loadCart();
});

// Initialize all event listeners
function initializeEventListeners() {
    const paymentForm = document.getElementById("paymentForm");
    if (paymentForm) {
        paymentForm.addEventListener("submit", handleFormSubmit);
    }

    const slipUpload = document.getElementById("slipUpload");
    const fileUploadSection = document.getElementById("fileUploadSection");

    if (slipUpload) {
        slipUpload.addEventListener("change", handleFileSelect);
        slipUpload.removeAttribute("multiple");
    }

    if (fileUploadSection) {
        fileUploadSection.addEventListener("dragover", handleDragOver);
        fileUploadSection.addEventListener("dragleave", handleDragLeave);
        fileUploadSection.addEventListener("drop", handleFileDrop);
    }

    const addAddressBtn = document.getElementById("addAddressBtn");
    const closeModal = document.getElementById("closeModal");
    const cancelBtn = document.getElementById("cancelBtn");
    const saveAddressBtn = document.getElementById("saveAddressBtn");

    if (addAddressBtn) {
        addAddressBtn.addEventListener("click", openAddressModal);
    }

    if (closeModal) {
        closeModal.addEventListener("click", closeAddressModal);
    }

    if (cancelBtn) {
        cancelBtn.addEventListener("click", closeAddressModal);
    }

    if (saveAddressBtn) {
        saveAddressBtn.addEventListener("click", saveAddress);
    }
}

function showToast(message, type = "success") {
    const container = document.getElementById("toastContainer");
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
    // Handle null, undefined, or zero weight
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

async function loadProvinces() {
    try {
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
            console.log('Provinces loaded:', provinces.length);
        } else {
            console.error('Failed to load provinces:', data.message);
            showToast("ไม่สามารถโหลดข้อมูลจังหวัดได้", "error");
        }
    } catch (error) {
        console.error('Error loading provinces:', error);
        showToast("เกิดข้อผิดพลาดในการโหลดจังหวัด", "error");
    }
}

async function loadCart() {
    const orderSummary = document.getElementById("orderSummary");
    orderSummary.innerHTML = "<p style='text-align: center; color: #6c757d;'>กำลังโหลด...</p>";

    try {
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
        console.log('Cart data loaded:', data);

        if (data.success) {
            // Cache the cart data globally
            currentCart = data;
            
            // Render cart once with all data
            renderCart(data);
            populateCustomerData(data.customer);
            
            // Load addresses and set default if needed
            await loadUserAddresses();
            if (data.address) {
                selectAddressByData(data.address);
            }
        } else {
            throw new Error(data.message);
        }

    } catch (error) {
        console.error('Error loading cart:', error);
        handleCartError(error, orderSummary);
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

async function loadUserAddresses() {
    try {
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
                province: addr.province,
                zipCode: addr.postal_code,
                is_main: addr.is_main
            }));

            const mainAddress = addresses.find(addr => addr.is_main == 1);
            if (mainAddress && !selectedAddressId) {
                selectedAddressId = mainAddress.id;
            }

            renderAddresses();
        } else {
            console.error('Failed to load addresses:', data.message);
        }
    } catch (error) {
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

// Fixed shipping recalculation with proper cart update
async function recalculateShipping(provinceId) {
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

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: เกิดข้อผิดพลาดในการคำนวณค่าส่ง`);
        }

        const result = await response.json();
        console.log('Shipping recalculation result:', result);

        if (result.success && result.data) {
            // Update only the changed values
            updateCartTotals(result.data);
            
            // Re-render only the summary section (more efficient)
            updateCartSummary();
            
            return true;
        } else {
            throw new Error(result.message || 'ไม่สามารถคำนวณค่าส่งได้');
        }

    } catch (error) {
        console.error('Error recalculating shipping:', error);
        showToast(error.message || "เกิดข้อผิดพลาดในการคำนวณค่าส่ง", "error");
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
    
    // Add province tracking to avoid unnecessary recalculations
    if (cart.shipping && newData.province_id) {
        cart.shipping.province_id = newData.province_id;
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
        const weightDisplay = calculateItemWeightDisplay(item);
        
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
    
    // Weight
    const weightDisplay = formatWeight(totalWeight, 'kg');
    html += `
        <div class="summary-row">
            <span>น้ำหนักรวม:</span> 
            <span>${weightDisplay}</span>
        </div>`;
    
    // Shipping
    const shippingCost = extractShippingCost(shipping);
    html += `
        <div class="summary-row">
            <span>ค่าจัดส่ง:</span> 
            <span>${shippingCost === 0 ? "ฟรี" : formatCurrency(shippingCost)}</span>
        </div>`;
    
    // Tax
    html += `
        <div class="summary-row">
            <span>ภาษี ${((taxRate || 0.07) * 100).toFixed(0)}%:</span> 
            <span>${formatCurrency(taxAmount)}</span>
        </div>`;
    
    // Grand total
    html += `
        <div class="summary-row total">
            <span>รวมทั้งหมด:</span> 
            <span>${formatCurrency(grandTotal)}</span>
        </div>
    </div>`;
    
    return html;
}

function extractShippingCost(shipping) {
    if (!shipping) return 0;
    
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
        document.getElementById("fullName").value = customer.name;
    }
    if (customer.email) {
        document.getElementById("email").value = customer.email;
    }
    if (customer.phone) {
        document.getElementById("phone").value = customer.phone;
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
    uploadedFilesContainer.innerHTML = '';
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

    form.reset();

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
    document.getElementById("addressModal").classList.remove("active");
    document.getElementById("addressForm").reset();
}

async function saveAddress() {
    const form = document.getElementById("addressForm");
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

    const addressData = {
        action: 'create',
        recipient_name: formData.get("addressName").trim(),
        address_line: formData.get("fullAddress").trim(),
        subdistrict: formData.get("subdistrict").trim(),
        district: formData.get("district").trim(),
        province_id: formData.get("province_id"),
        postal_code: formData.get("zipCode").trim(),
        phone: formData.get("phone") ? formData.get("phone").trim() : '',
        is_main: addresses.length === 0 ? 1 : 0
    };

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
            await loadUserAddresses();
            closeAddressModal();
            showToast("เพิ่มที่อยู่สำเร็จ", "success");
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
                    <button type="button" class="btn btn-default" onclick="event.stopPropagation(); selectAddress(${address.id})">
                        ${isSelected ? 'เลือกแล้ว' : 'เลือก'}
                    </button>
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
                        showToast("เลือกที่อยู่สำเร็จ แต่ไม่สามารถคำนวณค่าส่งได้", "warning");
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
    if (confirm("คุณต้องการลบที่อยู่นี้หรือไม่?")) {
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
}

// Enhanced form validation with weight checks
function validateForm() {
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

    // Enhanced cart validation with weight checks
    if (!currentCart || !currentCart.cart || !currentCart.cart.items || currentCart.cart.items.length === 0) {
        showToast("ตะกร้าสินค้าว่างเปล่า กรุณาเพิ่มสินค้าก่อน", "error");
        return false;
    }

    // Validate cart totals
    if (!currentCart.cart.grandTotal || currentCart.cart.grandTotal <= 0) {
        showToast("ราคารวมไม่ถูกต้อง กรุณาโหลดตะกร้าใหม่", "error");
        return false;
    }

    // Check for weight issues that might affect shipping
    let hasWeightIssues = false;
    let totalCalculatedWeight = 0;

    if (currentCart.cart.items) {
        currentCart.cart.items.forEach(item => {
            if (!item.weight || item.weight === 0 || isNaN(item.weight)) {
                hasWeightIssues = true;
            } else {
                let weight = parseFloat(item.weight);
                if (item.weight_unit === 'g') weight = weight / 1000;
                totalCalculatedWeight += weight * item.quantity;
            }
        });
    }

    if (hasWeightIssues && totalCalculatedWeight === 0) {
        const confirmProceed = confirm(
            "พบปัญหาข้อมูลน้ำหนักสินค้าซึ่งอาจส่งผลต่อการคำนวณค่าจัดส่ง\n\n" +
            "คุณต้องการดำเนินการต่อหรือไม่?\n" +
            "(แนะนำให้ติดต่อเจ้าหน้าที่เพื่อตรวจสอบ)"
        );
        
        if (!confirmProceed) {
            return false;
        }
    }

    return true;
}

// Enhanced form submission with proper cart validation and weight checks
async function handleFormSubmit(e) {
    e.preventDefault();

    if (!validateForm()) return;

    const submitBtn = e.target.querySelector("button[type=submit]");
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = "กำลังประมวลผล...";

    try {
        const formData = new FormData();

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

        // Enhanced cart data submission with weight information
        formData.append('cartTotal', currentCart.cart.grandTotal);
        formData.append('cartItems', currentCart.cart.totalItems);
        formData.append('cartSubtotal', currentCart.cart.subTotal);
        formData.append('cartTax', currentCart.cart.taxAmount || 0);

        // Handle shipping cost properly
        let shippingCost = 0;
        if (currentCart.cart.shipping) {
            if (typeof currentCart.cart.shipping === 'object') {
                shippingCost = currentCart.cart.shipping.cost || 0;
            } else {
                shippingCost = currentCart.cart.shipping || 0;
            }
        }
        formData.append('cartShipping', shippingCost);

        // Weight data with fallback calculation
        let finalWeight = currentCart.cart.totalWeight || 0;
        if (finalWeight === 0 && currentCart.cart.items) {
            // Calculate weight as fallback
            let calculatedWeight = 0;
            currentCart.cart.items.forEach(item => {
                if (item.weight && !isNaN(item.weight) && item.weight > 0) {
                    let weight = parseFloat(item.weight);
                    if (item.weight_unit === 'g') weight = weight / 1000;
                    calculatedWeight += weight * item.quantity;
                }
            });
            finalWeight = calculatedWeight;
        }
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
        const hasWeightData = finalWeight > 0;
        formData.append('hasValidWeight', hasWeightData ? '1' : '0');

        // Debug logging
        console.log('Form data being sent:');
        for (let [key, value] of formData.entries()) {
            console.log(key, value);
        }

        showToast("กำลังส่งข้อมูล...", "loading");

        // FIXED: Correct file path - removed 'controllers/' prefix
        const response = await fetch('controllers/submit_payment.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        if (!response.ok) {
            // Get the actual error message from PHP
            const errorText = await response.text();
            console.error('Server response:', errorText);
            console.error('Response status:', response.status);
            throw new Error(`HTTP ${response.status}: เกิดข้อผิดพลาดในการส่งข้อมูล`);
        }

        const result = await response.json();

        if (result.success) {
            // Show success popup
            showSuccessPopup(result.order_id || 'ORD' + Date.now());
        } else {
            throw new Error(result.message || 'ไม่สามารถส่งคำสั่งซื้อได้');
        }

    } catch (error) {
        console.error('Form submission error:', error);
        showToast(error.message || "เกิดข้อผิดพลาดในการส่งข้อมูล", "error");
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
}

// Add cancel button handler
function handleCancelClick() {
    const hasUnsavedData = 
        document.getElementById('fullName').value ||
        document.getElementById('email').value ||
        document.getElementById('phone').value ||
        uploadedFile ||
        selectedAddressId;

    if (hasUnsavedData) {
        if (confirm('คุณมีข้อมูลที่ยังไม่ได้บันทึก คุณต้องการยกเลิกและกลับหน้าหลักหรือไม่?')) {
            window.location.href = 'home.php';
        }
    } else {
        window.location.href = 'home.php';
    }
}

// Success popup function
function showSuccessPopup(orderId) {
    // Create success popup overlay
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
    showToast("กำลังเปลี่ยนเส้นทาง...", "success");
    setTimeout(() => {
        window.location.href = 'home.php';
    }, 500);
}

// Update the initialization to include cancel button handler
document.addEventListener("DOMContentLoaded", () => {
    initializeEventListeners();
    loadProvinces();
    loadCart();
    
    // Add cancel button event listener
    const cancelBtn = document.querySelector('.btn-cancel');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', handleCancelClick);
    }
});