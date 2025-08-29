// State Management
let addresses = [];
let selectedAddressId = null;
let uploadedFiles = [];

document.addEventListener("DOMContentLoaded", () => {
    initializeEventListeners();
    loadCart();
});

// Initialize all event listeners
function initializeEventListeners() {
    // Form submission
    const paymentForm = document.getElementById("paymentForm");
    if (paymentForm) {
        paymentForm.addEventListener("submit", handleFormSubmit);
    }

    // File upload
    const slipUpload = document.getElementById("slipUpload");
    const fileUploadSection = document.getElementById("fileUploadSection");

    if (slipUpload) {
        slipUpload.addEventListener("change", handleFileSelect);
    }

    if (fileUploadSection) {
        fileUploadSection.addEventListener("dragover", handleDragOver);
        fileUploadSection.addEventListener("dragleave", handleDragLeave);
        fileUploadSection.addEventListener("drop", handleFileDrop);
    }

    // Address modal
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

// Toast notification function
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

    // Show toast
    setTimeout(() => toast.classList.add("show"), 100);

    // Auto remove
    setTimeout(() => {
        toast.classList.add("hide");
        setTimeout(() => toast.remove(), 400);
    }, 4000);
}

// Currency formatter
function formatCurrency(amount) {
    return new Intl.NumberFormat("th-TH", {
        style: "currency",
        currency: "THB"
    }).format(amount);
}

// Load cart from PHP API
async function loadCart() {
    const orderSummary = document.getElementById("orderSummary");
    orderSummary.innerHTML = "<p style='text-align: center; color: #6c757d;'>กำลังโหลด...</p>";

    try {
        const response = await fetch('controllers/get_cart.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin' // Include session cookies
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'ไม่สามารถโหลดข้อมูลได้');
        }

        if (data.success) {
            renderCart(data);
            populateCustomerData(data.customer);
            
            // Load existing address if available
            if (data.address) {
                loadExistingAddress(data.address);
            }
        } else {
            throw new Error(data.message);
        }

    } catch (error) {
        console.error('Error loading cart:', error);
        
        if (error.message.includes('กรุณาล็อกอิน')) {
            showToast("กรุณาล็อกอินก่อนใช้งาน", "error");
            // Redirect to login page after delay
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 2000);
        } else {
            showToast(error.message || "ไม่สามารถโหลดข้อมูลได้", "error");
            orderSummary.innerHTML = "<p style='text-align: center; color: #dc3545;'>เกิดข้อผิดพลาด</p>";
        }
    }
}

// Load existing address from database
function loadExistingAddress(addressData) {
    if (!addressData) return;

    const existingAddress = {
        id: addressData.address_id,
        name: addressData.recipient_name || 'ที่อยู่หลัก',
        address: addressData.address_line,
        subdistrict: addressData.subdistrict || '',
        district: addressData.district || '',
        province: addressData.province,
        zipCode: addressData.postal_code,
        phone: addressData.phone || ''
    };

    addresses = [existingAddress];
    selectedAddressId = existingAddress.id;
    renderAddresses();
}

// Render cart data - Updated to match PHP response structure
function renderCart(data) {
    const { customer, cart } = data;
    const { items, totalItems, subTotal, shipping, taxRate, taxAmount, grandTotal } = cart;
    const orderSummary = document.getElementById("orderSummary");

    if (!items || items.length === 0) {
        orderSummary.innerHTML = `<p style='text-align: center; color: #6c757d;'>ไม่มีสินค้าในตะกร้า</p>`;
        return;
    }

    // Customer info
    let html = `
        <div class="customer-info">
            <h3>ข้อมูลลูกค้า</h3>
            <p><strong>ชื่อ:</strong> ${customer.name || '-'}</p>
            <p><strong>อีเมล:</strong> ${customer.email || '-'}</p>
            <p><strong>เบอร์โทร:</strong> ${customer.phone || '-'}</p>
        </div>
    `;

    // Cart items
    html += `<div class="cart-items">`;
    items.forEach(item => {
        // Use default image if no image available
        const imageUrl = item.image || 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\' viewBox=\'0 0 100 100\'%3E%3Crect width=\'100\' height=\'100\' fill=\'%23f0f0f0\'/%3E%3Ctext x=\'50\' y=\'50\' font-family=\'Arial\' font-size=\'12\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23666\'%3ENo Image%3C/text%3E%3C/svg%3E';
        
        html += `
            <div class="cart-item">
                <img src="${imageUrl}" alt="${item.name}" class="cart-item-image" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\' viewBox=\'0 0 100 100\'%3E%3Crect width=\'100\' height=\'100\' fill=\'%23f0f0f0\'/%3E%3Ctext x=\'50\' y=\'50\' font-family=\'Arial\' font-size=\'12\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23666\'%3ENo Image%3C/text%3E%3C/svg%3E'">
                <div class="cart-item-info">
                    <div class="cart-item-name">${item.name}</div>
                    <div class="cart-item-quantity">จำนวน: ${item.quantity}</div>
                    <div class="cart-item-price">ราคาต่อชิ้น: ${formatCurrency(item.price)}</div>
                    <div class="cart-item-total">ราคารวม: ${formatCurrency(item.itemTotal)}</div>
                </div>
            </div>
        `;
    });
    html += `</div>`;

    // Summary
    html += `
        <div class="cart-summary">
            <div class="summary-row">
                <span>รวมสินค้า (${totalItems} ชิ้น):</span> 
                <span>${formatCurrency(subTotal)}</span>
            </div>
            <div class="summary-row">
                <span>ค่าจัดส่ง:</span> 
                <span>${shipping === 0 ? "ฟรี" : formatCurrency(shipping)}</span>
            </div>
            <div class="summary-row">
                <span>ภาษี ${(taxRate * 100).toFixed(0)}%:</span> 
                <span>${formatCurrency(taxAmount)}</span>
            </div>
            <div class="summary-row total">
                <span>รวมทั้งหมด:</span> 
                <span>${formatCurrency(grandTotal)}</span>
            </div>
        </div>
    `;

    orderSummary.innerHTML = html;
}

// Populate customer data in form
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
    processFiles(files);
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
    processFiles(files);
}

function processFiles(files) {
    const uploadedFilesContainer = document.getElementById("uploadedFiles");

    Array.from(files).forEach(file => {
        // Validate file
        if (!validateFile(file)) return;

        // Add to uploaded files array
        const fileId = Date.now() + Math.random();
        uploadedFiles.push({ id: fileId, file: file });

        // Create file item HTML
        const fileItem = createFileItem(fileId, file);
        uploadedFilesContainer.appendChild(fileItem);
    });
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
    // Remove from array
    uploadedFiles = uploadedFiles.filter(f => f.id != fileId);

    // Remove from DOM
    const fileItem = document.querySelector(`[data-file-id="${fileId}"]`);
    if (fileItem) {
        fileItem.remove();
    }
}

function previewFile(fileId) {
    const fileData = uploadedFiles.find(f => f.id == fileId);
    if (!fileData || !fileData.file.type.startsWith('image/')) return;

    // Create preview modal (simple implementation)
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
    reader.readAsDataURL(fileData.file);
}

// Address management
function openAddressModal() {
    document.getElementById("addressModal").classList.add("active");
}

function closeAddressModal() {
    document.getElementById("addressModal").classList.remove("active");
    document.getElementById("addressForm").reset();
}

function saveAddress() {
    const form = document.getElementById("addressForm");
    const formData = new FormData(form);

    // Validate form
    if (!form.checkValidity()) {
        showToast("กรุณากรอกข้อมูลให้ครบถ้วน", "error");
        return;
    }

    const address = {
        id: Date.now(),
        name: formData.get("addressName"),
        address: formData.get("fullAddress"),
        province: formData.get("province"),
        zipCode: formData.get("zipCode")
    };

    addresses.push(address);
    renderAddresses();
    closeAddressModal();
    showToast("เพิ่มที่อยู่สำเร็จ", "success");
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
        const isSelected = selectedAddressId === address.id;
        
        // Format address display
        let addressDisplay = address.address;
        if (address.subdistrict) addressDisplay += ` ${address.subdistrict}`;
        if (address.district) addressDisplay += ` ${address.district}`;
        if (address.province) addressDisplay += ` ${address.province}`;
        if (address.zipCode) addressDisplay += ` ${address.zipCode}`;

        html += `
            <div class="address-item ${isSelected ? 'selected' : ''}" onclick="selectAddress(${address.id})">
                <div class="address-name">${address.name}</div>
                <div class="address-info">
                    ${addressDisplay}
                    ${address.phone ? `<br>โทร: ${address.phone}` : ''}
                </div>
                <div class="address-actions">
                    <button type="button" class="btn btn-default" onclick="event.stopPropagation(); selectAddress(${address.id})">
                        ${isSelected ? 'เลือกแล้ว' : 'เลือก'}
                    </button>
                    <button type="button" class="btn btn-edit" onclick="event.stopPropagation(); editAddress(${address.id})">แก้ไข</button>
                    <button type="button" class="btn btn-delete" onclick="event.stopPropagation(); deleteAddress(${address.id})">ลบ</button>
                </div>
            </div>
        `;
    });

    addressList.innerHTML = html;
}

function selectAddress(addressId) {
    selectedAddressId = addressId;
    renderAddresses();
}

function editAddress(addressId) {
    showToast("ฟีเจอร์แก้ไขที่อยู่จะพัฒนาในภายหลัง", "loading");
}

function deleteAddress(addressId) {
    if (confirm("คุณต้องการลบที่อยู่นี้หรือไม่?")) {
        addresses = addresses.filter(addr => addr.id !== addressId);
        if (selectedAddressId === addressId) {
            selectedAddressId = null;
        }
        renderAddresses();
        showToast("ลบที่อยู่สำเร็จ", "success");
    }
}

// Form submission
async function handleFormSubmit(e) {
    e.preventDefault();

    // Validate form
    if (!validateForm()) return;

    const submitBtn = e.target.querySelector("button[type=submit]");
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = "กำลังประมวลผล...";

    try {
        // Prepare form data
        const formData = new FormData();
        
        // Add customer data
        formData.append('fullName', document.getElementById('fullName').value);
        formData.append('email', document.getElementById('email').value);
        formData.append('phone', document.getElementById('phone').value);
        formData.append('company', document.getElementById('company').value || '');
        
        // Add selected address
        const selectedAddress = addresses.find(addr => addr.id === selectedAddressId);
        if (selectedAddress) {
            formData.append('addressId', selectedAddress.id);
            formData.append('addressName', selectedAddress.name);
            formData.append('addressDetails', selectedAddress.address);
            formData.append('province', selectedAddress.province);
            formData.append('zipCode', selectedAddress.zipCode);
        }
        
        // Add uploaded files
        uploadedFiles.forEach((fileData, index) => {
            formData.append(`paymentSlips[${index}]`, fileData.file);
        });

        showToast("กำลังส่งข้อมูล...", "loading");
        
        // Send to backend (you'll need to create submit_payment.php)
        const response = await fetch('submit_payment.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.message || 'เกิดข้อผิดพลาดในการส่งข้อมูล');
        }

        if (result.success) {
            showToast("ส่งคำสั่งซื้อสำเร็จ!", "success");
            
            setTimeout(() => {
                showToast("จะเปลี่ยนเส้นทางไปหน้าสำเร็จ", "success");
                // Redirect to success page
                if (result.redirect) {
                    window.location.href = result.redirect;
                } else {
                    window.location.href = 'order_success.php';
                }
            }, 1500);
        } else {
            throw new Error(result.message);
        }

    } catch (error) {
        console.error('Form submission error:', error);
        showToast(error.message || "เกิดข้อผิดพลาดในการส่งข้อมูล", "error");
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
}

function validateForm() {
    const form = document.getElementById("paymentForm");

    // Check required fields
    if (!form.checkValidity()) {
        showToast("กรุณากรอกข้อมูลให้ครบถ้วน", "error");
        return false;
    }

    // Check address selection
    if (!selectedAddressId) {
        showToast("กรุณาเลือกที่อยู่จัดส่ง", "error");
        return false;
    }

    // Check file upload
    if (uploadedFiles.length === 0) {
        showToast("กรุณาแนบสลิปการโอนเงิน", "error");
        return false;
    }

    return true;
}