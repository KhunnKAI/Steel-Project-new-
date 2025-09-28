let currentUserId = null;
let currentCustomerData = null;
let isEditingAddress = false;

// API Configuration
const API_BASE_URL = './'; // or your actual base URL
const API_ENDPOINTS = {
    CUSTOMER: API_BASE_URL + 'controllers/customer_api.php',
    ADDRESS: API_BASE_URL + 'controllers/address_api.php',
    ORDER: API_BASE_URL + 'controllers/order_api.php'
};

// Initialize page
document.addEventListener('DOMContentLoaded', function () {
    initializePage();
    setupEventListeners();
});

function initializePage() {
    // Check if user is logged in
    currentUserId = getCookie('user_id');
    if (!currentUserId) {
        window.location.href = 'login.php';
        return;
    }

    // Load user data
    loadCustomerData();
    loadAddressData();
    loadOrderData();

    // Initialize provinces dropdown
    loadProvinces();
}

function setupEventListeners() {
    // Address modal
    const addAddressBtn = document.getElementById('addAddressBtn');
    if (addAddressBtn) {
        addAddressBtn.addEventListener('click', openAddressModal);
    }

    // Password strength checking
    const newPasswordField = document.getElementById('newPassword');
    const confirmPasswordField = document.getElementById('confirmPassword');

    if (newPasswordField) {
        newPasswordField.addEventListener('input', function () {
            const password = this.value;
            const strengthResult = checkPasswordStrength(password);
            const strengthDiv = document.getElementById('passwordStrength');

            if (password) {
                strengthDiv.textContent = strengthResult.text;
                strengthDiv.className = 'password-strength ' + strengthResult.className;

                if (confirmPasswordField.value) {
                    checkPasswordMatch(password, confirmPasswordField.value);
                }
            } else {
                strengthDiv.textContent = '';
                strengthDiv.className = 'password-strength';
                const matchDiv = document.getElementById('passwordMatch');
                if (matchDiv) {
                    matchDiv.textContent = '';
                    matchDiv.className = 'password-strength';
                }
            }
        });
    }

    if (confirmPasswordField) {
        confirmPasswordField.addEventListener('input', function () {
            const password = newPasswordField ? newPasswordField.value : '';
            const confirmPassword = this.value;
            checkPasswordMatch(password, confirmPassword);
        });
    }

    // Form validation
    const postalCodeField = document.getElementById('postalCode');
    if (postalCodeField) {
        postalCodeField.addEventListener('input', function (e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length > 5) {
                this.value = this.value.substring(0, 5);
            }
        });
    }

    const phoneFields = ['recipientPhone', 'editPhone'];
    phoneFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', function (e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 10) {
                    value = value.substring(0, 10);
                }
                e.target.value = value;
            });
        }
    });

    // Modal close handlers
    setupModalCloseHandlers();
}

// NEW: Setup modal close handlers consistently
function setupModalCloseHandlers() {
    // Close modals when clicking outside
    window.addEventListener('click', function (event) {
        const editModal = document.getElementById('editModal');
        const passwordModal = document.getElementById('passwordModal');
        const addressModal = document.getElementById('addressModal');

        if (event.target === editModal) {
            closeEditModal();
        }
        if (event.target === passwordModal) {
            closePasswordModal();
        }
        if (event.target === addressModal) {
            closeAddressModal();
        }
    });

    // Close modals with Escape key
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeEditModal();
            closePasswordModal();
            if (document.getElementById('addressModal').classList.contains('active')) {
                closeAddressModal();
            }
        }
    });
}

// API Helper Functions
function showLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.classList.add('active');
    }
}

function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.classList.remove('active');
    }
}

function showError(message) {
    const errorDiv = document.getElementById('errorMessage');
    if (errorDiv) {
        const displayMessage = Array.isArray(message) ? message.join('\n') : message;
        errorDiv.innerHTML = displayMessage.replace(/\n/g, '<br>');
        errorDiv.style.display = 'block';
        errorDiv.scrollIntoView({ behavior: 'smooth' });

        setTimeout(() => {
            errorDiv.style.display = 'none';
        }, 8000);
    } else {
        alert(Array.isArray(message) ? message.join('\n') : message);
    }
}

function showSuccess(message) {
    const successDiv = document.getElementById('successMessage');
    if (successDiv) {
        successDiv.textContent = message;
        successDiv.style.display = 'block';
        successDiv.scrollIntoView({ behavior: 'smooth' });

        setTimeout(() => {
            successDiv.style.display = 'none';
        }, 3000);
    }
}

function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

function hideMessages() {
    const successDiv = document.getElementById('successMessage');
    const errorDiv = document.getElementById('errorMessage');

    if (successDiv) successDiv.style.display = 'none';
    if (errorDiv) errorDiv.style.display = 'none';
}

// Customer Data Functions
async function loadCustomerData() {
    try {
        showLoading();

        const response = await fetch(`${API_ENDPOINTS.CUSTOMER}?action=get&customer_id=${currentUserId}`, {
            method: 'GET'
        });

        const result = await response.json();

        if (result.success && result.data) {
            currentCustomerData = result.data;
            displayCustomerData(result.data);
        } else {
            showError(result.message || 'ไม่สามารถโหลดข้อมูลลูกค้าได้');
        }
    } catch (error) {
        console.error('Error loading customer data:', error);
        showError('เกิดข้อผิดพลาดในการโหลดข้อมูล');
    } finally {
        hideLoading();
    }
}

function displayCustomerData(data) {
    const nameEl = document.getElementById('displayName');
    const phoneEl = document.getElementById('displayPhone');
    const emailEl = document.getElementById('displayEmail');

    if (nameEl) nameEl.textContent = data.name || '-';
    if (phoneEl) phoneEl.textContent = data.phone || '-';
    if (emailEl) emailEl.textContent = data.email || '-';

    // Enable buttons
    const editBtn = document.getElementById('editBtn');
    const passwordBtn = document.getElementById('passwordBtn');
    if (editBtn) editBtn.disabled = false;
    if (passwordBtn) passwordBtn.disabled = false;
}

// Address Data Functions
async function loadAddressData() {
    try {
        console.log('Loading address data for user:', currentUserId);

        const response = await fetch(`${API_ENDPOINTS.ADDRESS}?action=get_by_user&user_id=${currentUserId}`, {
            method: "GET"
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        console.log("Address data response:", data);

        if (data.success) {
            console.log(`Loaded ${data.count || 0} addresses`);
            displayAddresses(data.data || []);
        } else {
            console.error("Failed to load addresses:", data.message);
            showError(data.message || 'ไม่สามารถโหลดข้อมูลที่อยู่ได้');
            displayAddresses([]);
        }
    } catch (error) {
        console.error("Error loading addresses:", error);
        showError('เกิดข้อผิดพลาดในการโหลดที่อยู่');
        displayAddresses([]);
    }
}

function displayAddresses(addresses) {
    const container = document.getElementById('addressContainer');
    if (!container) return;

    if (!addresses || addresses.length === 0) {
        container.innerHTML = '<div class="no-addresses">ยังไม่มีที่อยู่จัดส่ง</div>';
        return;
    }

    let html = '';
    addresses.forEach((address, index) => {
        const isMain = address.is_main === 1 || address.is_main === '1' || address.is_main === true;
        html += `
            <div class="address-item ${isMain ? 'selected' : ''}" data-id="${address.address_id}">
                <div class="address-content">
                    <div class="address-details">
                        <div class="address-name">${address.recipient_name || ''}</div>
                        <div class="address-info">
                            ${address.phone || ''}<br>
                            ${address.address_line || ''}<br>
                            ${address.subdistrict || ''} ${address.district || ''} ${address.province_name || address.province || ''} ${address.postal_code || ''}
                        </div>
                    </div>
                    <div class="address-actions">
                        <button class="btn-default" onclick="setMainAddress('${address.address_id}')" ${isMain ? 'style="background: #6c757d;" disabled' : ''}>
                            ${isMain ? 'เลือกแล้ว' : 'เลือก'}
                        </button>
                        <button class="btn-edit-address" onclick="editAddress('${address.address_id}')">แก้ไข</button>
                        <button class="btn-delete" onclick="deleteAddress('${address.address_id}')">ลบ</button>
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

// Order Data Functions
async function loadOrderData() {
    try {
        console.log('Loading order data for user:', currentUserId);

        const response = await fetch(`${API_ENDPOINTS.ORDER}?action=get_user_orders&user_id=${currentUserId}&limit=10`, {
            method: "GET"
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        console.log("Order data response:", data);

        if (data.success) {
            console.log(`Loaded ${data.data.length} orders`);
            displayOrders(data.data || []);
        } else {
            console.error("Failed to load orders:", data.message);
            showError(data.message || 'ไม่สามารถโหลดข้อมูลคำสั่งซื้อได้');
            displayOrders([]);
        }
    } catch (error) {
        console.error("Error loading orders:", error);
        showError('เกิดข้อผิดพลาดในการโหลดข้อมูลคำสั่งซื้อ');
        displayOrders([]);
    }
}

function displayOrders(orders) {
    const orderSection = document.querySelector('.order-section');
    if (!orderSection) return;

    // Find the order items container or create one
    let orderContainer = orderSection.querySelector('.order-items-container');
    if (!orderContainer) {
        // Create container for dynamic orders
        orderContainer = document.createElement('div');
        orderContainer.className = 'order-items-container';
        
        // Insert after the title
        const title = orderSection.querySelector('.order-title');
        if (title) {
            title.insertAdjacentElement('afterend', orderContainer);
        }
    }

    if (!orders || orders.length === 0) {
        orderContainer.innerHTML = '<div class="no-orders">ยังไม่มีคำสั่งซื้อ</div>';
        return;
    }

    let html = '';
    orders.forEach((order) => {
        const orderDate = new Date(order.created_at).toLocaleDateString('th-TH', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        });

        const statusText = getStatusText(order.status.status_code);
        const statusClass = getStatusClass(order.status.status_code);

        // Get first product image if available
        const firstProductImage = order.order_items && order.order_items.length > 0 && order.order_items[0].product_image ? 
            order.order_items[0].product_image : null;

        html += `
            <div class="order-item">
                <div class="order-details">
                    <div class="order-id">เลขคำสั่งซื้อ ${order.order_id}</div>
                    <div class="order-date">วันที่สั่งซื้อ ${orderDate}<br>สถานะ : <span class="status-badge ${statusClass}">${statusText}</span></div>
                    <div class="order-amount">จำนวนที่สั่งซื้อ ${order.total_quantity || 0}</div>
                    <div class="order-total">ยอดรวม ${formatCurrency(order.total_amount)}</div>
                </div>
                <div class="order-actions">
                    <a href="bill.php?order_id=${order.order_id}" class="btn btn-view">ดูรายละเอียด</a>
                </div>
            </div>
        `;
    });

    orderContainer.innerHTML = html;
}

function getStatusText(statusCode) {
    const statusMap = {
        'pending_payment': 'รอตรวจสอบการการชำระเงิน',
        'awaiting_shipment': 'รอจัดส่ง',
        'in_transit': 'กำลังจัดส่ง',
        'delivered': 'จัดส่งแล้ว',
        'cancelled': 'ยกเลิก'
    };
    return statusMap[statusCode] || statusCode;
}

function getStatusClass(statusCode) {
    const classMap = {
        'pending_payment': 'status-pending',
        'awaiting_shipment': 'status-awaiting',
        'in_transit': 'status-transit',
        'delivered': 'status-delivered',
        'cancelled': 'status-cancelled'
    };
    return classMap[statusCode] || 'status-default';
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('th-TH', {
        style: 'currency',
        currency: 'THB',
        minimumFractionDigits: 2
    }).format(amount);
}

// Province Functions
async function loadProvinces() {
    const select = document.getElementById('province');
    if (!select) {
        console.error('Province select element not found');
        return;
    }

    try {
        console.log('Loading provinces from API...');

        select.innerHTML = '<option value="">กำลังโหลด...</option>';
        select.disabled = true;

        const response = await fetch(`${API_ENDPOINTS.ADDRESS}?action=get_provinces`);

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        console.log('Province API response:', result);

        if (result.success && result.data && Array.isArray(result.data) && result.data.length > 0) {
            console.log('Successfully loaded provinces from API:', result.data.length, 'provinces');
            populateProvinceSelect(result.data);
        } else {
            throw new Error('API returned no province data or invalid format');
        }

    } catch (error) {
        console.error('Error loading provinces from API:', error);
        showError('ไม่สามารถโหลดข้อมูลจังหวัดได้ กรุณาลองใหม่อีกครั้ง');

        select.innerHTML = '<option value="">เกิดข้อผิดพลาดในการโหลดจังหวัด</option>';
        select.disabled = false;
    }
}

function populateProvinceSelect(provinceData) {
    const select = document.getElementById('province');
    if (!select) {
        console.error('Province select element not found');
        return;
    }

    select.innerHTML = '';

    const defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.textContent = 'เลือกจังหวัด';
    select.appendChild(defaultOption);

    console.log('Populating province select with', provinceData.length, 'provinces');

    try {
        const processedProvinces = provinceData.map(p => ({
            id: p.province_id,
            name: p.name,
            zone: p.zone_id
        }));

        processedProvinces.sort((a, b) => a.name.localeCompare(b.name, 'th'));

        processedProvinces.forEach(province => {
            const option = document.createElement('option');
            option.value = province.id;
            option.textContent = province.name;
            option.dataset.zone = province.zone || '';
            select.appendChild(option);
        });

        console.log(`Successfully populated ${processedProvinces.length} provinces`);
        select.disabled = false;

    } catch (error) {
        console.error('Error populating province select:', error);
        showError('เกิดข้อผิดพลาดในการแสดงข้อมูลจังหวัด');

        select.innerHTML = '<option value="">เกิดข้อผิดพลาดในการโหลดจังหวัด</option>';
        select.disabled = false;
    }
}

// Address Functions
async function saveAddress() {
    console.log('=== DEBUG SAVE ADDRESS ===');
    console.log('Current User ID:', currentUserId);

    const form = document.getElementById('addressForm');
    if (!form || !form.checkValidity()) {
        if (form) form.reportValidity();
        return;
    }

    const addressId = document.getElementById('addressId').value.trim();
    const isEditing = !!addressId;

    const recipientName = document.getElementById('recipientName').value.trim();
    const recipientPhone = document.getElementById('recipientPhone').value.trim();
    const addressLine = document.getElementById('addressLine').value.trim();
    const subdistrict = document.getElementById('subdistrict').value.trim();
    const district = document.getElementById('district').value.trim();
    const provinceSelect = document.getElementById('province');
    const provinceId = provinceSelect ? provinceSelect.value : '';
    const postalCode = document.getElementById('postalCode').value.trim();

    console.log('=== PROVINCE DEBUG ===');
    console.log('Selected province_id:', provinceId);
    console.log('Province_id type:', typeof provinceId);
    console.log('Province select element:', provinceSelect);
    if (provinceSelect) {
        console.log('Selected option:', provinceSelect.options[provinceSelect.selectedIndex]);
        console.log('All options:', Array.from(provinceSelect.options).map(opt => ({ value: opt.value, text: opt.text })));
    }
    console.log('======================');

    // Enhanced validation with better error messages
    const errors = [];

    if (!recipientName) errors.push('กรุณากรอกชื่อผู้รับ');
    if (!recipientPhone) errors.push('กรุณากรอกเบอร์โทรศัพท์');
    if (!addressLine) errors.push('กรุณากรอกที่อยู่');
    if (!subdistrict) errors.push('กรุณากรอกตำบล/แขวง');
    if (!district) errors.push('กรุณากรอกอำเภอ/เขต');
    if (!provinceId) errors.push('กรุณาเลือกจังหวัด');
    if (!postalCode) errors.push('กรุณากรอกรหัสไปรษณีย์');

    if (recipientPhone && !/^0[0-9]{9}$/.test(recipientPhone.replace(/[-\s]/g, ''))) {
        errors.push('เบอร์โทรศัพท์ไม่ถูกต้อง (ต้องเป็นเลข 10 หลักขึ้นต้นด้วย 0)');
    }

    if (postalCode && !/^[0-9]{5}$/.test(postalCode)) {
        errors.push('รหัสไปรษณีย์ต้องเป็นตัวเลข 5 หลัก');
    }

    if (errors.length > 0) {
        console.log('Validation errors:', errors);
        showError(errors.join('\n'));
        return;
    }

    try {
        showLoading();

        // *** เพิ่มส่วนนี้: ตรวจสอบว่าเป็นที่อยู่แรกของ user หรือไม่ ***
        let shouldSetAsMain = false;
        
        if (!isEditing) {
            // ถ้าเป็นการเพิ่มที่อยู่ใหม่ ให้ตรวจสอบว่ามีที่อยู่อยู่แล้วหรือไม่
            try {
                const checkResponse = await fetch(`${API_ENDPOINTS.ADDRESS}?action=get_by_user&user_id=${currentUserId}`);
                const checkResult = await checkResponse.json();
                
                if (checkResult.success && (!checkResult.data || checkResult.data.length === 0)) {
                    // ไม่มีที่อยู่เดิม ให้ตั้งเป็น main
                    shouldSetAsMain = true;
                    console.log('This will be the first address, setting as main');
                }
            } catch (checkError) {
                console.log('Error checking existing addresses, will not set as main:', checkError);
            }
        }

        const formData = new FormData();
        formData.append('action', isEditing ? 'update' : 'create');
        formData.append('user_id', currentUserId);

        if (isEditing) {
            formData.append('address_id', addressId);
        }

        formData.append('recipient_name', recipientName);
        formData.append('phone', recipientPhone);
        formData.append('address_line', addressLine);
        formData.append('subdistrict', subdistrict);
        formData.append('district', district);
        formData.append('province_id', provinceId);
        formData.append('postal_code', postalCode);
        
        // *** แก้ไขส่วนนี้: ส่ง is_main = 1 ถ้าเป็นที่อยู่แรก ***
        formData.append('is_main', shouldSetAsMain ? '1' : '0');

        const response = await fetch(API_ENDPOINTS.ADDRESS, {
            method: 'POST',
            body: formData
        });

        console.log('Response status:', response.status);

        const responseText = await response.text();
        console.log('Raw response:', responseText);

        let result;
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            console.error('Failed to parse response as JSON:', parseError);
            console.error('Response text:', responseText);
            throw new Error('Server returned invalid JSON response');
        }

        console.log('Parsed API result:', result);

        if (result.success) {
            showSuccess(isEditing ? 'แก้ไขที่อยู่เรียบร้อยแล้ว' : 'เพิ่มที่อยู่เรียบร้อยแล้ว');
            closeAddressModal();
            await loadAddressData();
        } else {
            let errorMessage = result.message || 'ไม่สามารถบันทึกที่อยู่ได้';

            if (result.errors && Array.isArray(result.errors)) {
                errorMessage += '\n\nข้อผิดพลาด:\n• ' + result.errors.join('\n• ');
            }

            showError(errorMessage);
        }
    } catch (error) {
        console.error('Error saving address:', error);
        showError('เกิดข้อผิดพลาดในการบันทึกที่อยู่: ' + error.message);
    } finally {
        hideLoading();
    }
}

// แก้ไขฟังก์ชัน editAddress ให้ทำงานได้อย่างถูกต้อง
async function editAddress(addressId) {
    try {
        showLoading();
        hideMessages();
        
        console.log('Starting to edit address:', addressId);
        
        // ดึงข้อมูล address ก่อน
        const response = await fetch(`${API_ENDPOINTS.ADDRESS}?action=get&address_id=${addressId}`, {
            method: 'GET'
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        console.log('Edit address result:', result);

        if (result.success && result.data) {
            const address = result.data;
            console.log('Address data:', address);

            // เปิด modal ก่อนที่จะกำหนดค่า
            openAddressModal();
            
            // ตั้งค่า modal สำหรับการแก้ไข
            document.getElementById('addressModalTitle').textContent = 'แก้ไขที่อยู่';
            document.getElementById('saveAddressBtn').textContent = 'บันทึกการแก้ไข';
            isEditingAddress = true;

            // เติมข้อมูลในฟิลด์ต่างๆ (ยกเว้นจังหวัด)
            document.getElementById('addressId').value = address.address_id;
            document.getElementById('recipientName').value = address.recipient_name || '';
            document.getElementById('recipientPhone').value = address.phone || '';
            document.getElementById('addressLine').value = address.address_line || '';
            document.getElementById('subdistrict').value = address.subdistrict || '';
            document.getElementById('district').value = address.district || '';
            document.getElementById('postalCode').value = address.postal_code || '';

            // จัดการ provinces แยกต่างหาก
            await handleProvinceSelection(address.province_id);

        } else {
            throw new Error(result.message || 'ไม่สามารถโหลดข้อมูลที่อยู่ได้');
        }
    } catch (error) {
        console.error('Error loading address for edit:', error);
        showError('เกิดข้อผิดพลาดในการโหลดข้อมูลที่อยู่: ' + error.message);
    } finally {
        hideLoading();
    }
}

// ฟังก์ชันใหม่สำหรับจัดการการเลือก province
async function handleProvinceSelection(provinceId) {
    const provinceSelect = document.getElementById('province');
    
    if (!provinceSelect) {
        console.error('Province select element not found');
        return;
    }

    try {
        console.log('Handling province selection for ID:', provinceId);
        
        // ตรวจสอบว่า provinces โหลดแล้วหรือยัง
        if (provinceSelect.options.length <= 1) {
            console.log('Provinces not loaded, loading now...');
            
            // แสดง loading state ใน select
            provinceSelect.innerHTML = '<option value="">กำลังโหลดจังหวัด...</option>';
            provinceSelect.disabled = true;
            
            // โหลด provinces
            await loadProvincesForEdit();
        }

        // รอให้ provinces โหลดเสร็จ
        await waitForProvincesLoaded();
        
        // กำหนดค่า province ที่ถูกต้อง
        if (provinceId) {
            console.log('Setting province value to:', provinceId);
            provinceSelect.value = provinceId;
            
            // ตรวจสอบว่าค่าถูกกำหนดแล้ว
            if (provinceSelect.value === provinceId) {
                console.log('Province set successfully');
            } else {
                console.warn('Failed to set province value');
                
                // ลองหาตัวเลือกที่ตรงกัน
                const matchingOption = Array.from(provinceSelect.options)
                    .find(option => option.value === provinceId.toString());
                
                if (matchingOption) {
                    matchingOption.selected = true;
                    console.log('Province set using alternative method');
                } else {
                    console.error('No matching province option found for ID:', provinceId);
                    showError('ไม่พบข้อมูลจังหวัดที่ตรงกัน');
                }
            }
        }
        
    } catch (error) {
        console.error('Error handling province selection:', error);
        showError('เกิดข้อผิดพลาดในการกำหนดจังหวัด: ' + error.message);
    }
}

// ฟังก์ชันโหลด provinces สำหรับการแก้ไขโดยเฉพาะ
async function loadProvincesForEdit() {
    const select = document.getElementById('province');
    if (!select) {
        throw new Error('Province select element not found');
    }

    try {
        console.log('Loading provinces for edit...');

        const response = await fetch(`${API_ENDPOINTS.ADDRESS}?action=get_provinces`);

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        console.log('Province API response for edit:', result);

        if (result.success && result.data && Array.isArray(result.data) && result.data.length > 0) {
            console.log('Successfully loaded provinces for edit:', result.data.length, 'provinces');
            populateProvinceSelectForEdit(result.data);
        } else {
            throw new Error('API returned no province data or invalid format');
        }

    } catch (error) {
        console.error('Error loading provinces for edit:', error);
        throw error; // Re-throw เพื่อให้ caller handle
    }
}

// ฟังก์ชัน populate provinces สำหรับการแก้ไข
function populateProvinceSelectForEdit(provinceData) {
    const select = document.getElementById('province');
    if (!select) {
        throw new Error('Province select element not found');
    }

    // Clear existing options
    select.innerHTML = '';

    // Add default option
    const defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.textContent = 'เลือกจังหวัด';
    select.appendChild(defaultOption);

    console.log('Populating province select for edit with', provinceData.length, 'provinces');

    try {
        // Process and sort provinces
        const processedProvinces = provinceData.map(p => ({
            id: p.province_id.toString(), // แปลงเป็น string เพื่อความแน่ใจ
            name: p.name,
            zone: p.zone_id
        }));

        processedProvinces.sort((a, b) => a.name.localeCompare(b.name, 'th'));

        // Add province options
        processedProvinces.forEach(province => {
            const option = document.createElement('option');
            option.value = province.id;
            option.textContent = province.name;
            option.dataset.zone = province.zone || '';
            select.appendChild(option);
        });

        console.log(`Successfully populated ${processedProvinces.length} provinces for edit`);
        select.disabled = false;

    } catch (error) {
        console.error('Error populating province select for edit:', error);
        throw error;
    }
}

// ฟังก์ชันรอให้ provinces โหลดเสร็จ
function waitForProvincesLoaded() {
    return new Promise((resolve, reject) => {
        const select = document.getElementById('province');
        let attempts = 0;
        const maxAttempts = 50; // 5 วินาที (50 x 100ms)
        
        const checkProvinces = () => {
            attempts++;
            
            if (select.options.length > 1) {
                console.log('Provinces loaded successfully after', attempts, 'attempts');
                resolve();
            } else if (attempts >= maxAttempts) {
                console.warn('Timeout waiting for provinces to load');
                reject(new Error('Timeout waiting for provinces to load'));
            } else {
                setTimeout(checkProvinces, 100);
            }
        };
        
        checkProvinces();
    });
}

async function deleteAddress(addressId) {
    if (!confirm('คุณต้องการลบที่อยู่นี้หรือไม่?')) {
        return;
    }

    try {
        showLoading();

        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('address_id', addressId);
        formData.append('user_id', currentUserId);

        const response = await fetch(API_ENDPOINTS.ADDRESS, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showSuccess('ลบที่อยู่เรียบร้อยแล้ว');
            loadAddressData();
        } else {
            showError(result.message || 'ไม่สามารถลบที่อยู่ได้');
        }
    } catch (error) {
        console.error('Error deleting address:', error);
        showError('เกิดข้อผิดพลาดในการลบที่อยู่');
    } finally {
        hideLoading();
    }
}

async function setMainAddress(addressId) {
    try {
        showLoading();

        const formData = new FormData();
        formData.append('action', 'set_main');
        formData.append('address_id', addressId);
        formData.append('user_id', currentUserId);

        const response = await fetch(API_ENDPOINTS.ADDRESS, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showSuccess('ตั้งเป็นที่อยู่หลักเรียบร้อยแล้ว');
            await loadAddressData();
        } else {
            showError(result.message || 'ไม่สามารถตั้งเป็นที่อยู่หลักได้');
        }
    } catch (error) {
        console.error('Error setting main address:', error);
        showError('เกิดข้อผิดพลาดในการตั้งที่อยู่หลัก');
    } finally {
        hideLoading();
    }
}

// Modal Functions
function openEditModal() {
    if (!currentCustomerData) return;

    document.getElementById('editName').value = currentCustomerData.name || '';
    document.getElementById('editPhone').value = currentCustomerData.phone || '';
    document.getElementById('editEmail').value = currentCustomerData.email || '';

    document.getElementById('editModal').style.display = 'block';
    document.body.style.overflow = 'hidden'; // NEW: Prevent background scroll
    hideMessages();
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
    document.body.style.overflow = 'auto'; // NEW: Restore scroll
}

function openPasswordModal() {
    document.getElementById('passwordModal').style.display = 'block';
    document.body.style.overflow = 'hidden'; // NEW: Prevent background scroll
    document.getElementById('passwordForm').reset();

    const strengthDiv = document.getElementById('passwordStrength');
    const matchDiv = document.getElementById('passwordMatch');
    if (strengthDiv) {
        strengthDiv.textContent = '';
        strengthDiv.className = 'password-strength';
    }
    if (matchDiv) {
        matchDiv.textContent = '';
        matchDiv.className = 'password-strength';
    }

    hideMessages();

    setTimeout(() => {
        const field = document.getElementById('currentPassword');
        if (field) field.focus();
    }, 100);
}

function closePasswordModal() {
    document.getElementById('passwordModal').style.display = 'none';
    document.body.style.overflow = 'auto'; // NEW: Restore scroll
}

function openAddressModal() {
    // Reset modal for new address unless we're editing
    if (!isEditingAddress) {
        document.getElementById('addressModalTitle').textContent = 'เพิ่มที่อยู่ใหม่';
        document.getElementById('saveAddressBtn').textContent = 'บันทึกที่อยู่';
        document.getElementById('addressForm').reset();
        document.getElementById('addressId').value = '';
        
        // โหลด provinces เฉพาะเมื่อเพิ่มใหม่
        loadProvinces();
    }

    document.getElementById('addressModal').classList.add('active');
    document.body.style.overflow = 'hidden';
    hideMessages();
}

function closeAddressModal() {
    document.getElementById('addressModal').classList.remove('active');
    document.body.style.overflow = 'auto';
    document.getElementById('addressForm').reset();
    isEditingAddress = false; // Reset editing state
}

function validateAddressForm() {
    const form = document.getElementById('addressForm');
    const errors = [];
    
    // ตรวจสอบฟิลด์ที่จำเป็น
    const requiredFields = [
        { id: 'recipientName', label: 'ชื่อผู้รับ' },
        { id: 'recipientPhone', label: 'เบอร์โทรศัพท์' },
        { id: 'addressLine', label: 'ที่อยู่' },
        { id: 'subdistrict', label: 'ตำบล/แขวง' },
        { id: 'district', label: 'อำเภอ/เขต' },
        { id: 'province', label: 'จังหวัด' },
        { id: 'postalCode', label: 'รหัสไปรษณีย์' }
    ];
    
    requiredFields.forEach(field => {
        const element = document.getElementById(field.id);
        if (!element || !element.value.trim()) {
            errors.push(`กรุณากรอก${field.label}`);
        }
    });
    
    // ตรวจสอบรูปแบบเบอร์โทรศัพท์
    const phone = document.getElementById('recipientPhone').value.trim();
    if (phone && !/^0[0-9]{9}$/.test(phone.replace(/[-\s]/g, ''))) {
        errors.push('เบอร์โทรศัพท์ไม่ถูกต้อง (ต้องเป็นเลข 10 หลักขึ้นต้นด้วย 0)');
    }
    
    // ตรวจสอบรหัสไปรษณีย์
    const postalCode = document.getElementById('postalCode').value.trim();
    if (postalCode && !/^[0-9]{5}$/.test(postalCode)) {
        errors.push('รหัสไปรษณีย์ต้องเป็นตัวเลข 5 หลัก');
    }
    
    return errors;
}

// Form Functions
async function confirmEdit() {
    const name = document.getElementById('editName').value.trim();
    const phone = document.getElementById('editPhone').value.trim();
    const email = document.getElementById('editEmail').value.trim();

    if (!name || !phone || !email) {
        showError('กรุณากรอกข้อมูลให้ครบถ้วน');
        return;
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showError('กรุณากรอกอีเมลในรูปแบบที่ถูกต้อง');
        return;
    }

    if (!/^[0-9]{10}$/.test(phone.replace(/-/g, ''))) {
        showError('กรุณากรอกเบอร์โทรศัพท์ที่ถูกต้อง (10 หลัก)');
        return;
    }

    try {
        showLoading();

        const formData = new FormData();
        formData.append('action', 'update');
        formData.append('customer_id', currentUserId);
        formData.append('name', name);
        formData.append('phone', phone);
        formData.append('email', email);

        const response = await fetch(API_ENDPOINTS.CUSTOMER, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            document.getElementById('displayName').textContent = name;
            document.getElementById('displayPhone').textContent = phone;
            document.getElementById('displayEmail').textContent = email;

            showSuccess('แก้ไขข้อมูลเรียบร้อยแล้ว');
            closeEditModal();

            // Update currentCustomerData
            currentCustomerData = { ...currentCustomerData, name, phone, email };
        } else {
            showError(result.message || 'ไม่สามารถแก้ไขข้อมูลได้');
        }
    } catch (error) {
        console.error('Error updating customer data:', error);
        showError('เกิดข้อผิดพลาดในการแก้ไขข้อมูล');
    } finally {
        hideLoading();
    }
}

async function confirmPasswordChange() {
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    // Validation
    if (!currentPassword.trim() || !newPassword.trim() || !confirmPassword.trim()) {
        alert('กรุณากรอกรหัสผ่านให้ครบถ้วน');
        return;
    }

    if (newPassword.length < 8) {
        alert('รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร');
        return;
    }

    if (newPassword === currentPassword) {
        alert('รหัสผ่านใหม่ต้องแตกต่างจากรหัสผ่านเดิม');
        return;
    }

    if (newPassword !== confirmPassword) {
        alert('รหัสผ่านใหม่ไม่ตรงกัน กรุณาตรวจสอบอีกครั้ง');
        return;
    }

    try {
        showLoading();

        const formData = new FormData();
        formData.append('action', 'change_password');
        formData.append('customer_id', currentUserId);
        formData.append('current_password', currentPassword);
        formData.append('new_password', newPassword);

        const response = await fetch(API_ENDPOINTS.CUSTOMER, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showSuccess('เปลี่ยนรหัสผ่านเรียบร้อยแล้ว');
            closePasswordModal();

            setTimeout(() => {
                    document.cookie = 'user_id=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
                    window.location.href = 'login.php';
            }, 2000);
        } else {
            showError(result.message || 'ไม่สามารถเปลี่ยนรหัสผ่านได้');
        }
    } catch (error) {
        console.error('Error changing password:', error);
        showError('เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน');
    } finally {
        hideLoading();
    }
}

// Utility Functions
function checkPasswordMatch(password, confirmPassword) {
    const matchDiv = document.getElementById('passwordMatch');
    if (!matchDiv) return;

    if (confirmPassword) {
        if (password === confirmPassword) {
            matchDiv.textContent = '✓ รหัสผ่านตรงกัน';
            matchDiv.className = 'password-strength strength-strong';
        } else {
            matchDiv.textContent = '✗ รหัสผ่านไม่ตรงกัน';
            matchDiv.className = 'password-strength strength-weak';
        }
    } else {
        matchDiv.textContent = '';
        matchDiv.className = 'password-strength';
    }
}

function checkPasswordStrength(password) {
    let strength = 0;
    let text = '';
    let className = '';

    if (password.length >= 8) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;

    switch (strength) {
        case 0:
        case 1:
            text = 'รหัสผ่านแข็งแรงน้อยมาก';
            className = 'strength-weak';
            break;
        case 2:
        case 3:
            text = 'รหัสผ่านแข็งแรงปานกลาง';
            className = 'strength-medium';
            break;
        case 4:
        case 5:
            text = 'รหัสผ่านแข็งแรง';
            className = 'strength-strong';
            break;
    }

    return { strength, text, className };
}

function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const toggleBtn = field ? field.nextElementSibling : null;

    if (field && toggleBtn && toggleBtn.classList.contains('show-password')) {
        if (field.type === 'password') {
            field.type = 'text';
            toggleBtn.textContent = 'ซ่อน';
        } else {
            field.type = 'password';
            toggleBtn.textContent = 'แสดง';
        }
    }
}

function hideMessages() {
    const successDiv = document.getElementById('successMessage');
    const errorDiv = document.getElementById('errorMessage');

    if (successDiv) successDiv.style.display = 'none';
    if (errorDiv) errorDiv.style.display = 'none';
}

// Event Handlers for closing modals
window.onclick = function (event) {
    const editModal = document.getElementById('editModal');
    const passwordModal = document.getElementById('passwordModal');
    const addressModal = document.getElementById('addressModal');

    if (event.target === editModal) {
        closeEditModal();
    }
    if (event.target === passwordModal) {
        closePasswordModal();
    }
    if (event.target === addressModal) {
        closeAddressModal();
    }
}

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        closeEditModal();
        closePasswordModal();
        if (document.getElementById('addressModal').classList.contains('active')) {
            closeAddressModal();
        }
    }
});