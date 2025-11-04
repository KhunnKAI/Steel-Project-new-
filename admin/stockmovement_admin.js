// ========================
// CONFIGURATION
// ========================
const API_BASE_URL = './controllers/get_stock.php';

// ========================
// GLOBAL VARIABLES
// ========================
let movements = [];
let products = [];
let currentPage = 1;
const itemsPerPage = 10;

// ========================
// INITIALIZATION
// ========================
// FUNCTION: เริ่มต้นหน้าเมื่อ DOM โหลดเสร็จ
document.addEventListener('DOMContentLoaded', function () {
    loadProducts();
    updateStats();
    loadMovements();
    setupEventListeners();
});

// ========================
// EVENT LISTENERS SETUP
// ========================
// FUNCTION: ตั้งค่าฟังก์ชันฟัง (listener) สำหรับปุ่มและฟิลด์อินพุตทั้งหมด
function setupEventListeners() {
    // Search button listener
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

    // Filter change listeners
    document.getElementById('movementTypeFilter')?.addEventListener('change', () => {});
    document.getElementById('startDateFilter')?.addEventListener('change', () => {});
    document.getElementById('endDateFilter')?.addEventListener('change', () => {});
    document.getElementById('userFilter')?.addEventListener('input', () => {});
}

// ========================
// DATA LOADING FUNCTIONS
// ========================
// FUNCTION: โหลดข้อมูลสินค้าจาก API
async function loadProducts() {
    try {
        showLoading('กำลังโหลดรายการสินค้า...');
        
        const response = await fetch(`${API_BASE_URL}?action=get_products`);
        const result = await response.json();
        
        if (result.success) {
            products = result.data;
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

// FUNCTION: โหลดข้อมูลการเคลื่อนไหวสินค้าจาก API
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
            showError('เกิดข้อผิดพลาดในการโหลดข้อมูลการเคลื่อนไหว: ' + result.message);
        }
    } catch (error) {
        console.error('Error loading movements:', error);
        showError('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์');
    } finally {
        hideLoading();
    }
}

// FUNCTION: อัปเดตสถิติจาก API
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

// ========================
// DISPLAY FUNCTIONS
// ========================
// FUNCTION: แสดงข้อมูลการเคลื่อนไหวในตาราง
function displayMovements(movementList) {
    const tbody = document.getElementById('movementsTableBody');
    
    tbody.innerHTML = '';

    if (movementList.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="10" style="text-align: center; padding: 2rem; color: var(--text-light);">
                    <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                    ไม่พบข้อมูลการเคลื่อนไหวสินค้า
                </td>
            </tr>
        `;
        return;
    }

    movementList.forEach(movement => {
        const row = document.createElement('tr');

        const typeClass = `type-${movement.change_type}`;
        const typeText = movement.change_type_text || movement.change_type;
        const quantityClass = movement.quantity_change > 0 ? 'quantity-positive' : 'quantity-negative';
        const quantitySign = movement.quantity_change > 0 ? '+' : '';
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

// FUNCTION: ตั้งค่าปุ่มเปลี่ยนหน้า (pagination)
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

    currentPage = paginationData.current_page;
}

// ========================
// FILTER FUNCTIONS
// ========================
// FUNCTION: ใช้ตัวกรองและโหลดข้อมูลใหม่
function applyFilters() {
    currentPage = 1;
    loadMovements();
}

// FUNCTION: รีเซ็ตตัวกรองทั้งหมด
function resetAllFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('movementTypeFilter').value = '';
    document.getElementById('startDateFilter').value = '';
    document.getElementById('endDateFilter').value = '';
    document.getElementById('userFilter').value = '';
    currentPage = 1;
    loadMovements();
}

// ========================
// UTILITY FUNCTIONS
// ========================
// FUNCTION: แปลงรูปแบบวันและเวลาสำหรับการแสดงผล
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

// FUNCTION: แสดงหน้าต่างโหลดข้อมูล
function showLoading(message = 'กำลังโหลด...') {
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

// FUNCTION: ซ่อนหน้าต่างโหลดข้อมูล
function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

// FUNCTION: แสดงข้อความข้อผิดพลาด
function showError(message) {
    alert('เกิดข้อผิดพลาด: ' + message);
}

// FUNCTION: แสดงข้อความสำเร็จ
function showSuccess(message) {
    alert(message);
}