// Configuration
const API_BASE_URL = './controllers/get_stock.php';

// Global variables
let movements = [];
let products = [];
let currentPage = 1;
const itemsPerPage = 10;

// Initialize the page
document.addEventListener('DOMContentLoaded', function () {
    loadProducts();
    updateStats();
    loadMovements();
    setupEventListeners();
});

// Setup event listeners
function setupEventListeners() {
    // Search button listener (instead of real-time search)
    const searchBtn = document.getElementById('searchBtn');
    if (searchBtn) {
        searchBtn.addEventListener('click', () => {
            currentPage = 1;
            loadMovements();
        });
    }

    // Allow Enter key to trigger search
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                currentPage = 1;
                loadMovements();
            }
        });
    }
    
    // *** เปลี่ยนจากการค้นหาแบบเรียลไทม์ให้เป็นเฉพาะการอัปเดต UI เท่านั้น ***
    // ไม่เรียก loadMovements() เมื่อเปลี่ยนค่าตัวกรอง
    document.getElementById('movementTypeFilter').addEventListener('change', () => {
        // เพียงอัปเดต UI หากจำเป็น
    });
    
    document.getElementById('startDateFilter').addEventListener('change', () => {
        // เพียงอัปเดต UI หากจำเป็น
    });
    
    document.getElementById('endDateFilter').addEventListener('change', () => {
        // เพียงอัปเดต UI หากจำเป็น
    });
    
    document.getElementById('userFilter').addEventListener('input', () => {
        // เพียงอัปเดต UI หากจำเป็น
    });

    // Modal form event listeners (only if elements exist)
    const quantityInput = document.getElementById('quantity');
    const movementTypeSelect = document.getElementById('movementType');
    const productSelect = document.getElementById('productSelect');
    const movementForm = document.getElementById('movementForm');
    
    if (quantityInput) quantityInput.addEventListener('input', calculateNewStock);
    if (movementTypeSelect) {
        movementTypeSelect.addEventListener('change', () => {
            calculateNewStock();
            updateModalTitle();
        });
    }
    if (productSelect) productSelect.addEventListener('change', handleProductSelection);
    if (movementForm) movementForm.addEventListener('submit', handleFormSubmission);
    
    // Close modal when clicking outside (only if modal exists)
    const modal = document.getElementById('movementModal');
    if (modal) {
        window.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal();
            }
        });
    }
}

// Load products from API
async function loadProducts() {
    try {
        showLoading('กำลังโหลดรายการสินค้า...');
        
        const response = await fetch(`${API_BASE_URL}?action=get_products`);
        const result = await response.json();
        
        if (result.success) {
            products = result.data;
            populateProductSelect();
        } else {
            showError('เกิดข้อผิดพลาดในการโหลดรายการสินค้า: ' + result.message);
        }
    } catch (error) {
        console.error('Error loading products:', error);
        showError('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์');
    } finally {
        hideLoading();
    }
}

// Populate product select dropdown
function populateProductSelect() {
    const productSelect = document.getElementById('productSelect');
    
    // Only populate if element exists
    if (!productSelect) return;
    
    // Clear existing options except the first one
    productSelect.innerHTML = '<option value="">เลือกสินค้า</option>';
    
    products.forEach(product => {
        const option = document.createElement('option');
        option.value = product.product_id;
        option.textContent = product.display_name;
        option.dataset.lot = product.lot || '';
        option.dataset.stock = product.stock || 0;
        productSelect.appendChild(option);
    });
}

// Load movements from API
async function loadMovements() {
    try {
        showLoading('กำลังโหลดข้อมูล...');
        
        const params = new URLSearchParams({
            action: 'get_movements',
            page: currentPage,
            limit: itemsPerPage
        });
        
        // Add filter parameters
        const searchTerm = document.getElementById('searchInput').value.trim();
        const typeFilter = document.getElementById('movementTypeFilter').value;
        const startDate = document.getElementById('startDateFilter').value;
        const endDate = document.getElementById('endDateFilter').value;
        const userFilter = document.getElementById('userFilter').value.trim();
        
        if (searchTerm) params.append('search', searchTerm);
        if (typeFilter) params.append('change_type', typeFilter);
        if (startDate) params.append('start_date', startDate);
        if (endDate) params.append('end_date', endDate);
        if (userFilter) params.append('user_filter', userFilter);
        
        const response = await fetch(`${API_BASE_URL}?${params}`);
        const result = await response.json();
        
        if (result.success) {
            movements = result.data;
            displayMovements(movements);
            setupPagination(result.pagination);
        } else {
            showError('เกิดข้อผิดพลาดในการโหลดข้อมูล: ' + result.message);
        }
    } catch (error) {
        console.error('Error loading movements:', error);
        showError('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์');
    } finally {
        hideLoading();
    }
}

// Update statistics from API
async function updateStats() {
    try {
        const response = await fetch(`${API_BASE_URL}?action=get_stats`);
        const result = await response.json();
        
        if (result.success) {
            const stats = result.data;
            document.getElementById('totalMovements').textContent = stats.total_movements;
            document.getElementById('todayMovements').textContent = stats.today_movements;
            document.getElementById('receivedToday').textContent = stats.received_today;
            document.getElementById('dispatchedToday').textContent = stats.dispatched_today;
        }
    } catch (error) {
        console.error('Error loading stats:', error);
    }
}

// Display movements in table
function displayMovements(movementList) {
    const tbody = document.getElementById('movementsTableBody');
    
    tbody.innerHTML = '';

    if (movementList.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="10" style="text-align: center; padding: 2rem; color: var(--text-light);">
                    <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                    ไม่พบข้อมูลการเคลื่อนไหวสต็อก
                </td>
            </tr>
        `;
        return;
    }

    movementList.forEach(movement => {
        const row = document.createElement('tr');

        // Map change_type to display text and classes
        const typeClass = `type-${movement.change_type}`;
        const typeText = movement.change_type_text || movement.change_type;

        // Determine quantity display
        const quantityClass = movement.quantity_change > 0 ? 'quantity-positive' : 'quantity-negative';
        const quantitySign = movement.quantity_change > 0 ? '+' : '';

        // Format user/admin name
        const performedBy = movement.admin_name || movement.user_name || 'ระบบ';

        row.innerHTML = `
            <td>${formatDateTime(movement.created_at)}</td>
            <td><span class="movement-type ${typeClass}">${typeText}</span></td>
            <td><strong>${movement.product_id}</strong></td>
            <td>${movement.product_name}</td>
            <td>${movement.product_lot || '-'}</td>
            <td class="quantity-cell ${quantityClass}">${quantitySign}${Math.abs(movement.quantity_change)}</td>
            <td><strong>${movement.quantity_before}</strong></td>
            <td><strong>${movement.quantity_after}</strong></td>
            <td>${performedBy}</td>
            <td>${movement.note || '-'}</td>
        `;

        tbody.appendChild(row);
    });
}

// Setup pagination
function setupPagination(paginationData) {
    const paginationContainer = document.getElementById('pagination');
    paginationContainer.innerHTML = '';

    if (paginationData.total_pages <= 1) return;

    // Previous button
    const prevBtn = document.createElement('button');
    prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
    prevBtn.disabled = paginationData.current_page === 1;
    prevBtn.onclick = () => {
        if (currentPage > 1) {
            currentPage--;
            loadMovements();
        }
    };
    paginationContainer.appendChild(prevBtn);

    // Page numbers
    const startPage = Math.max(1, paginationData.current_page - 2);
    const endPage = Math.min(paginationData.total_pages, startPage + 4);

    for (let i = startPage; i <= endPage; i++) {
        const pageBtn = document.createElement('button');
        pageBtn.textContent = i;
        pageBtn.className = i === paginationData.current_page ? 'active' : '';
        pageBtn.onclick = () => {
            currentPage = i;
            loadMovements();
        };
        paginationContainer.appendChild(pageBtn);
    }

    // Next button
    const nextBtn = document.createElement('button');
    nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
    nextBtn.disabled = paginationData.current_page === paginationData.total_pages;
    nextBtn.onclick = () => {
        if (currentPage < paginationData.total_pages) {
            currentPage++;
            loadMovements();
        }
    };
    paginationContainer.appendChild(nextBtn);

    // Update current page
    currentPage = paginationData.current_page;
}

// Format datetime for display
function formatDateTime(datetime) {
    const date = new Date(datetime);
    return date.toLocaleString('th-TH', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Modal functions (kept for compatibility, but may not be needed)
function openMovementModal(type = '') {
    const modal = document.getElementById('movementModal');
    const movementTypeSelect = document.getElementById('movementType');

    // Only proceed if modal exists
    if (!modal) {
        console.warn('Movement modal not found in DOM');
        return;
    }

    if (type && movementTypeSelect) {
        movementTypeSelect.value = type;
        updateModalTitle();
    }

    resetForm();
    setCurrentDateTime();
    modal.style.display = 'block';
}

function closeModal() {
    const modal = document.getElementById('movementModal');
    if (modal) {
        modal.style.display = 'none';
        resetForm();
    }
}

function updateModalTitle() {
    const type = document.getElementById('movementType')?.value;
    const modalTitle = document.getElementById('modalTitle');
    
    if (!modalTitle) return;
    
    const icons = {
        'in': 'fas fa-plus-circle',
        'out': 'fas fa-minus-circle',
        'adjust': 'fas fa-edit'
    };
    const titles = {
        'in': 'บันทึกการรับสินค้าเข้า',
        'out': 'บันทึกการเบิกสินค้าออก',
        'adjust': 'บันทึกการปรับปรุงสต็อก'
    };

    if (type && titles[type]) {
        modalTitle.innerHTML = `<i class="${icons[type]}"></i> ${titles[type]}`;
    } else {
        modalTitle.innerHTML = '<i class="fas fa-plus-circle"></i> บันทึกการเคลื่อนไหวสต็อก';
    }
}

function setCurrentDateTime() {
    const datetimeInput = document.getElementById('movementDateTime');
    if (!datetimeInput) return;
    
    const now = new Date();
    const datetime = new Date(now.getTime() - now.getTimezoneOffset() * 60000)
        .toISOString()
        .slice(0, 16);
    datetimeInput.value = datetime;
}

function handleProductSelection() {
    const select = document.getElementById('productSelect');
    const selectedOption = select?.options[select.selectedIndex];
    
    if (!selectedOption) return;
    
    const productLotInput = document.getElementById('productLot');
    const currentStockInput = document.getElementById('currentStock');
    
    if (selectedOption.value) {
        if (productLotInput) productLotInput.value = selectedOption.dataset.lot || '';
        if (currentStockInput) currentStockInput.value = selectedOption.dataset.stock || 0;
        calculateNewStock();
    } else {
        if (productLotInput) productLotInput.value = '';
        if (currentStockInput) currentStockInput.value = '';
        const newStockInput = document.getElementById('newStock');
        if (newStockInput) newStockInput.value = '';
    }
}

function calculateNewStock() {
    const currentStockInput = document.getElementById('currentStock');
    const quantityInput = document.getElementById('quantity');
    const movementTypeSelect = document.getElementById('movementType');
    const newStockInput = document.getElementById('newStock');
    
    if (!currentStockInput || !quantityInput || !movementTypeSelect || !newStockInput) return;
    
    const currentStock = parseInt(currentStockInput.value) || 0;
    const quantity = parseInt(quantityInput.value) || 0;
    const type = movementTypeSelect.value;

    let newStock = currentStock;

    if (type === 'in') {
        newStock = currentStock + quantity;
    } else if (type === 'out') {
        newStock = currentStock - quantity;
    } else if (type === 'adjust') {
        // For adjustment, the quantity is the final stock amount
        newStock = quantity;
    }

    newStockInput.value = Math.max(0, newStock);
}

async function handleFormSubmission(e) {
    e.preventDefault();

    const productSelect = document.getElementById('productSelect');
    const selectedProduct = products.find(p => p.product_id === productSelect?.value);
    
    if (!selectedProduct) {
        showError('กรุณาเลือกสินค้า');
        return;
    }

    const quantityInput = document.getElementById('quantity');
    const quantity = parseInt(quantityInput?.value || '0');
    if (!quantity || quantity <= 0) {
        showError('กรุณาระบุจำนวนที่ถูกต้อง');
        return;
    }

    const movementTypeSelect = document.getElementById('movementType');
    const movementType = movementTypeSelect?.value;
    if (!movementType) {
        showError('กรุณาเลือกประเภทการเคลื่อนไหว');
        return;
    }

    const notesInput = document.getElementById('notes');
    const movementData = {
        action: 'save_movement',
        product_id: productSelect.value,
        change_type: movementType,
        quantity_change: movementType === 'out' ? -quantity : quantity,
        reference_type: 'manual',
        reference_id: null,
        user_id: null,
        admin_id: 'ADMIN001', // Should get from session
        note: notesInput?.value || 'การปรับปรุงจาก Admin Panel'
    };

    try {
        showLoading('กำลังบันทึกข้อมูล...');
        
        const response = await fetch(API_BASE_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(movementData)
        });

        const result = await response.json();

        if (result.success) {
            showSuccess('บันทึกการเคลื่อนไหวสต็อกเรียบร้อยแล้ว');
            closeModal();
            
            // Reload data
            await Promise.all([
                updateStats(),
                loadProducts(),
                loadMovements()
            ]);
        } else {
            showError('เกิดข้อผิดพลาด: ' + result.message);
        }
    } catch (error) {
        console.error('Error saving movement:', error);
        showError('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์');
    } finally {
        hideLoading();
    }
}

function resetForm() {
    const form = document.getElementById('movementForm');
    if (!form) return;
    
    form.reset();
    
    const productLotInput = document.getElementById('productLot');
    const currentStockInput = document.getElementById('currentStock');
    const newStockInput = document.getElementById('newStock');
    const performedByInput = document.getElementById('performedBy');
    
    if (productLotInput) productLotInput.value = '';
    if (currentStockInput) currentStockInput.value = '';
    if (newStockInput) newStockInput.value = '';
    if (performedByInput) performedByInput.value = 'Admin';
}

function applyFilters() {
    currentPage = 1;
    loadMovements();
}

// Reset all filters
function resetAllFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('movementTypeFilter').value = '';
    document.getElementById('startDateFilter').value = '';
    document.getElementById('endDateFilter').value = '';
    document.getElementById('userFilter').value = '';
    currentPage = 1;
    loadMovements();
}

// Utility functions for loading and error messages
function showLoading(message = 'กำลังโหลด...') {
    // Create loading overlay if not exists
    let overlay = document.getElementById('loadingOverlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'loadingOverlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            color: white;
            font-size: 18px;
        `;
        document.body.appendChild(overlay);
    }
    
    overlay.innerHTML = `
        <div style="text-align: center;">
            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 1rem;"></i>
            <div>${message}</div>
        </div>
    `;
    overlay.style.display = 'flex';
}

function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

function showError(message) {
    alert('เกิดข้อผิดพลาด: ' + message);
}

function showSuccess(message) {
    alert(message);
}