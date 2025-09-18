let currentUserId = null;
let allOrders = [];
let filteredOrders = [];
let currentPage = 1;
const ordersPerPage = 10;

// API Configuration
const API_BASE_URL = './';
const API_ENDPOINTS = {
    ORDER: API_BASE_URL + 'controllers/order_api.php'
};

// Initialize page
document.addEventListener('DOMContentLoaded', function () {
    initializePage();
});

function initializePage() {
    currentUserId = getCookie('user_id');
    if (!currentUserId) {
        window.location.href = 'login.php';
        return;
    }

    loadOrderData();
}

// API Helper Functions
function showLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) overlay.classList.add('active');
}

function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) overlay.classList.remove('active');
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

// Order Data Functions
async function loadOrderData() {
    try {
        showLoading();

        const response = await fetch(`${API_ENDPOINTS.ORDER}?action=get_user_orders&user_id=${currentUserId}`, {
            method: "GET"
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            allOrders = data.data || [];
            filteredOrders = [...allOrders];
            updateStatistics();
            displayOrders();
        } else {
            showError(data.message || 'ไม่สามารถโหลดข้อมูลคำสั่งซื้อได้');
            displayOrders([]);
        }
    } catch (error) {
        console.error("Error loading orders:", error);
        showError('เกิดข้อผิดพลาดในการโหลดข้อมูลคำสั่งซื้อ');
        displayOrders([]);
    } finally {
        hideLoading();
    }
}

function updateStatistics() {
    const totalOrders = allOrders.length;
    const pendingOrders = allOrders.filter(order =>
        ['pending_payment', 'awaiting_shipment', 'in_transit'].includes(order.status.status_code)
    ).length;
    const completedOrders = allOrders.filter(order =>
        order.status.status_code === 'delivered'
    ).length;
    const totalSpent = allOrders
        .filter(order => order.status.status_code !== 'cancelled')
        .reduce((sum, order) => sum + parseFloat(order.total_amount || 0), 0);

    document.getElementById('totalOrders').textContent = totalOrders;
    document.getElementById('pendingOrders').textContent = pendingOrders;
    document.getElementById('completedOrders').textContent = completedOrders;
    document.getElementById('totalSpent').textContent = formatCurrency(totalSpent);
}

function displayOrders() {
    const container = document.getElementById('orderContainer');
    const countElement = document.getElementById('orderCount');

    if (!container) return;

    // Update order count
    if (countElement) {
        const totalCount = filteredOrders.length;
        countElement.textContent = `พบคำสั่งซื้อ ${totalCount} รายการ`;
    }

    if (!filteredOrders || filteredOrders.length === 0) {
        container.innerHTML = `
                    <div class="no-orders">
                        <div class="no-orders-icon">📦</div>
                        <div class="no-orders-text">ไม่พบคำสั่งซื้อ</div>
                        <div class="no-orders-subtext">คุณยังไม่มีประวัติการสั่งซื้อสินค้า</div>
                    </div>
                `;
        document.getElementById('pagination').style.display = 'none';
        return;
    }

    // Calculate pagination
    const totalPages = Math.ceil(filteredOrders.length / ordersPerPage);
    const startIndex = (currentPage - 1) * ordersPerPage;
    const endIndex = startIndex + ordersPerPage;
    const currentOrders = filteredOrders.slice(startIndex, endIndex);

    let html = '';
    currentOrders.forEach((order) => {
        const orderDate = new Date(order.created_at).toLocaleDateString('th-TH', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });

        const statusText = getStatusText(order.status.status_code);
        const statusClass = getStatusClass(order.status.status_code);

        // Action buttons based on status
        let actionButtons = `<a href="bill.php?order_id=${order.order_id}" class="btn-view">ดูรายละเอียด</a>`;

        if (order.status.status_code === 'pending_payment') {
            actionButtons += `<button class="btn-cancel" onclick="cancelOrder('${order.order_id}')">ยกเลิกคำสั่งซื้อ</button>`;
        } else if (order.status.status_code === 'delivered') {
            actionButtons += `<button class="btn-reorder" onclick="reorder('${order.order_id}')">สั่งซื้ออีกครั้ง</button>`;
        }

        html += `
                    <div class="order-item">
                        <div class="order-details">
                            <div class="order-id">เลขคำสั่งซื้อ ${order.order_id}</div>
                            <div class="order-date">
                                วันที่สั่งซื้อ ${orderDate}
                                <span class="status-badge ${statusClass}">${statusText}</span>
                            </div>
                            <div class="order-amount">จำนวนสินค้า ${order.total_quantity || 0} รายการ</div>
                            <div class="order-total">ยอดรวม ${formatCurrency(order.total_amount)}</div>
                        </div>
                        <div class="order-actions">
                            ${actionButtons}
                        </div>
                    </div>
                `;
    });

    container.innerHTML = html;

    // Update pagination
    updatePagination(totalPages);
}

function updatePagination(totalPages) {
    const pagination = document.getElementById('pagination');
    const pageInfo = document.getElementById('pageInfo');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');

    if (totalPages <= 1) {
        pagination.style.display = 'none';
        return;
    }

    pagination.style.display = 'flex';
    pageInfo.textContent = `หน้า ${currentPage} จาก ${totalPages}`;
    prevBtn.disabled = currentPage <= 1;
    nextBtn.disabled = currentPage >= totalPages;
}

function changePage(direction) {
    const totalPages = Math.ceil(filteredOrders.length / ordersPerPage);

    if (direction === 'prev' && currentPage > 1) {
        currentPage--;
    } else if (direction === 'next' && currentPage < totalPages) {
        currentPage++;
    }

    displayOrders();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Filter and Sort Functions
function applyFilters() {
    const statusFilter = document.getElementById('statusFilter').value;
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;

    filteredOrders = allOrders.filter(order => {
        // Status filter
        if (statusFilter && order.status.status_code !== statusFilter) {
            return false;
        }

        // Date filter
        const orderDate = new Date(order.created_at).toISOString().split('T')[0];
        if (dateFrom && orderDate < dateFrom) {
            return false;
        }
        if (dateTo && orderDate > dateTo) {
            return false;
        }

        return true;
    });

    currentPage = 1;
    displayOrders();
    showSuccess('ใช้ตัวกรองข้อมูลแล้ว');
}

function clearFilters() {
    document.getElementById('statusFilter').value = '';
    document.getElementById('dateFrom').value = '';
    document.getElementById('dateTo').value = '';

    filteredOrders = [...allOrders];
    currentPage = 1;
    displayOrders();
    showSuccess('ล้างตัวกรองแล้ว');
}

function sortOrders() {
    const sortValue = document.getElementById('sortOrder').value;

    filteredOrders.sort((a, b) => {
        switch (sortValue) {
            case 'newest':
                return new Date(b.created_at) - new Date(a.created_at);
            case 'oldest':
                return new Date(a.created_at) - new Date(b.created_at);
            case 'amount_high':
                return parseFloat(b.total_amount) - parseFloat(a.total_amount);
            case 'amount_low':
                return parseFloat(a.total_amount) - parseFloat(b.total_amount);
            default:
                return new Date(b.created_at) - new Date(a.created_at);
        }
    });

    currentPage = 1;
    displayOrders();
}

// Order Actions
async function cancelOrder(orderId) {
    if (!confirm('คุณต้องการยกเลิกคำสั่งซื้อนี้หรือไม่?')) {
        return;
    }

    try {
        showLoading();

        const formData = new FormData();
        formData.append('action', 'cancel_order');
        formData.append('order_id', orderId);
        formData.append('user_id', currentUserId);

        const response = await fetch(API_ENDPOINTS.ORDER, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showSuccess('ยกเลิกคำสั่งซื้อเรียบร้อยแล้ว');
            await loadOrderData(); // Reload orders
        } else {
            showError(result.message || 'ไม่สามารถยกเลิกคำสั่งซื้อได้');
        }
    } catch (error) {
        console.error('Error cancelling order:', error);
        showError('เกิดข้อผิดพลาดในการยกเลิกคำสั่งซื้อ');
    } finally {
        hideLoading();
    }
}

async function reorder(orderId) {
    if (!confirm('คุณต้องการสั่งซื้อสินค้าเหล่านี้อีกครั้งหรือไม่?')) {
        return;
    }

    try {
        showLoading();

        // Get order details first
        const response = await fetch(`${API_ENDPOINTS.ORDER}?action=get_order_details&order_id=${orderId}`);
        const result = await response.json();

        if (result.success && result.data.order_items) {
            // Add items to cart
            const cart = JSON.parse(localStorage.getItem('shopping_cart') || '{}');

            result.data.order_items.forEach(item => {
                const productId = item.product_id.toString();
                if (cart[productId]) {
                    cart[productId].quantity += parseInt(item.quantity);
                } else {
                    cart[productId] = {
                        product_id: item.product_id,
                        name: item.product_name,
                        price: parseFloat(item.price),
                        quantity: parseInt(item.quantity),
                        image: item.product_image || ''
                    };
                }
            });

            localStorage.setItem('shopping_cart', JSON.stringify(cart));

            // Update cart badge if function exists
            if (typeof window.updateCartBadge === 'function') {
                window.updateCartBadge();
            }

            showSuccess('เพิ่มสินค้าในตะกร้าแล้ว');

            // Ask if user wants to go to cart
            setTimeout(() => {
                if (confirm('ต้องการไปที่ตะกร้าสินค้าเพื่อทำการสั่งซื้อหรือไม่?')) {
                    window.location.href = 'cart.php';
                }
            }, 1500);

        } else {
            showError('ไม่สามารถโหลดข้อมูลสินค้าได้');
        }
    } catch (error) {
        console.error('Error reordering:', error);
        showError('เกิดข้อผิดพลาดในการสั่งซื้ออีกครั้ง');
    } finally {
        hideLoading();
    }
}

// Utility Functions
function getStatusText(statusCode) {
    const statusMap = {
        'pending_payment': 'รอการชำระเงิน',
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

// Set current date as default for date filters
document.addEventListener('DOMContentLoaded', function () {
    const today = new Date();
    const thirtyDaysAgo = new Date();
    thirtyDaysAgo.setDate(today.getDate() - 30);

    // Set default date range to last 30 days
    document.getElementById('dateFrom').value = thirtyDaysAgo.toISOString().split('T')[0];
    document.getElementById('dateTo').value = today.toISOString().split('T')[0];
});

// Export orders (optional feature)
function exportOrders() {
    if (filteredOrders.length === 0) {
        showError('ไม่มีข้อมูลคำสั่งซื้อที่จะส่งออก');
        return;
    }

    const csvContent = "data:text/csv;charset=utf-8," +
        "เลขคำสั่งซื้อ,วันที่สั่งซื้อ,สถานะ,จำนวนสินค้า,ยอดรวม\n" +
        filteredOrders.map(order => {
            const date = new Date(order.created_at).toLocaleDateString('th-TH');
            const status = getStatusText(order.status.status_code);
            return `${order.order_id},"${date}","${status}",${order.total_quantity || 0},${order.total_amount}`;
        }).join("\n");

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `orders_${new Date().toISOString().split('T')[0]}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    showSuccess('ส่งออกข้อมูลคำสั่งซื้อแล้ว');
}