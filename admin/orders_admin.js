// Global variables
let currentPage = 1;
let itemsPerPage = 20;
let allOrders = [];
let filteredOrders = [];
let currentOrderId = null;
let isLoading = false;
let currentUser = null; // Store current user permissions

// Notes modal variables
let notesModalResolve = null;
let notesModalReject = null;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
    loadCurrentUser().then(() => {
        loadOrders();
    });
    handleResize();
});

// Load current user permissions
async function loadCurrentUser() {
    try {
        const response = await fetch('controllers/get_current_user.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Cache-Control': 'no-cache'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            currentUser = data.data;
            console.log('Current user loaded:', currentUser);
        } else {
            throw new Error(data.message || 'Failed to load user info');
        }
        
    } catch (error) {
        console.error('Error loading current user:', error);
        showNotification('ไม่สามารถโหลดข้อมูลผู้ใช้ได้: ' + error.message, 'error');
        // Redirect to login if unauthorized
        if (error.message.includes('401')) {
            window.location.href = 'controllers/logout.php';
        }
    }
}

// Check if user has permission for specific status action
function hasPermissionForStatus(statusCode, action = 'update') {
    if (!currentUser) return false;
    
    const position = currentUser.position;
    
    // Define permissions for each status and action
    const statusPermissions = {
        'pending_payment': {
            'approve': ['manager', 'super', 'accounting', 'sales'],
            'reject': ['manager', 'super', 'accounting', 'sales']
        },
        'awaiting_shipment': {
            'ship': ['manager', 'super', 'sales', 'warehouse'],
            'cancel': ['manager', 'super', 'sales', 'warehouse']
        },
        'in_transit': {
            'complete': ['manager', 'super', 'shipping', 'sales'],
            'cancel': ['manager', 'super', 'shipping', 'sales']
        },
        'delivered': {
            'view': ['manager', 'accounting', 'super', 'sales', 'warehouse', 'shipping']
        },
        'cancelled': {
            'view': ['manager', 'accounting', 'super', 'sales', 'warehouse', 'shipping']
        }
    };
    
    // General cancel permission
    if (action === 'cancel') {
        return ['manager', 'super', 'sales'].includes(position);
    }
    
    // Check specific status permissions
    const statusPerms = statusPermissions[statusCode];
    if (!statusPerms) return false;
    
    const actionPerms = statusPerms[action];
    if (!actionPerms) return false;
    
    return actionPerms.includes(position);
}

function initializeEventListeners() {
    // Search input - ลบการค้นหา real-time
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        // ลบการเรียก applyFilters() ที่นี่
        // เก็บไว้เฉพาะเพื่ออนุญาต Enter key
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });
    }

    // Filter controls - ลบการเรียก applyFilters() แบบอัตโนมัติ
    const statusFilter = document.getElementById('statusFilter');
    const dateFromFilter = document.getElementById('dateFromFilter');
    const dateToFilter = document.getElementById('dateToFilter');
    const searchBtn = document.getElementById('searchBtn');
    const resetBtn = document.getElementById('resetBtn');

    // ลบการเรียก applyFilters() ที่นี่
    // [statusFilter, dateFromFilter, dateToFilter].forEach(filter => {
    //     if (filter) {
    //         filter.addEventListener('change', applyFilters);
    //     }
    // });

    // Search button - กดปุ่มค้นหา
    if (searchBtn) {
        searchBtn.addEventListener('click', applyFilters);
    }

    // Reset button
    if (resetBtn) {
        resetBtn.addEventListener('click', resetFilters);
    }

    // Modal close events
    window.addEventListener('click', function(event) {
        const orderModal = document.getElementById('orderDetailsModal');
        const notesModal = document.getElementById('notesModal');
        
        if (event.target === orderModal) {
            closeOrderDetailsModal();
        }
        if (event.target === notesModal) {
            closeNotesModal();
        }
    });

    // Escape key to close modals
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeOrderDetailsModal();
            closeLightbox();
            closeNotesModal();
        }
    });
}

async function loadOrders() {
    if (isLoading) return;
    
    try {
        isLoading = true;
        showLoading();
        
        const response = await fetch('controllers/get_orders.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Cache-Control': 'no-cache'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            allOrders = data.data || [];
            updateStatistics(data.statistics || []);
            applyFilters();
        } else {
            throw new Error(data.message || 'Failed to load orders');
        }
        
    } catch (error) {
        console.error('Error loading orders:', error);
        showNotification('เกิดข้อผิดพลาดในการโหลดข้อมูล: ' + error.message, 'error');
        displayOrders([], 0);
    } finally {
        isLoading = false;
        hideLoading();
    }
}

function applyFilters() {
    const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
    const statusFilter = document.getElementById('statusFilter')?.value || '';
    const dateFromFilter = document.getElementById('dateFromFilter')?.value || '';
    const dateToFilter = document.getElementById('dateToFilter')?.value || '';

    filteredOrders = allOrders.filter(order => {
        // Search filter
        if (searchTerm) {
            const searchFields = [
                order.order_id,
                order.customer_info?.name || '',
                order.customer_info?.phone || '',
                order.customer_info?.email || ''
            ].join(' ').toLowerCase();
            
            if (!searchFields.includes(searchTerm)) {
                return false;
            }
        }

        // Status filter
        if (statusFilter && order.status?.status_code !== statusFilter) {
            return false;
        }

        // Date filters
        if (dateFromFilter) {
            const orderDate = new Date(order.created_at);
            const fromDate = new Date(dateFromFilter);
            if (orderDate < fromDate) return false;
        }

        if (dateToFilter) {
            const orderDate = new Date(order.created_at);
            const toDate = new Date(dateToFilter);
            toDate.setHours(23, 59, 59, 999);
            if (orderDate > toDate) return false;
        }

        return true;
    });

    currentPage = 1;
    displayOrders(filteredOrders, filteredOrders.length);
}

function resetFilters() {
    // Clear all filter inputs
    document.getElementById('searchInput').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('dateFromFilter').value = '';
    document.getElementById('dateToFilter').value = '';

    // Reset to show all orders
    currentPage = 1;
    displayOrders(allOrders, allOrders.length);
    
    showNotification('ตัวกรองถูกรีเซ็ตเรียบร้อย', 'success');
}


function updateStatistics(statistics) {
    const stats = {
        pending_payment: 0,
        awaiting_shipment: 0,
        in_transit: 0,
        delivered: 0,
        cancelled: 0
    };

    statistics.forEach(stat => {
        if (stats.hasOwnProperty(stat.status_code)) {
            stats[stat.status_code] = parseInt(stat.count) || 0;
        }
    });

    const elements = {
        pendingPaymentOrders: stats.pending_payment,
        awaitingShipmentOrders: stats.awaiting_shipment,
        inTransitOrders: stats.in_transit,
        deliveredOrders: stats.delivered,
        cancelledOrders: stats.cancelled
    };

    Object.entries(elements).forEach(([elementId, value]) => {
        const element = document.getElementById(elementId);
        if (element) {
            element.textContent = value.toLocaleString();
        }
    });
}

function displayOrders(orders, totalCount) {
    const tbody = document.getElementById('ordersTableBody');
    if (!tbody) return;

    if (!orders || orders.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" style="text-align: center; padding: 40px; color: #666;">
                    ไม่พบข้อมูลคำสั่งซื้อ
                </td>
            </tr>
        `;
        updateResultsInfo(0, totalCount);
        updatePagination(0);
        return;
    }

    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = Math.min(startIndex + itemsPerPage, orders.length);
    const currentPageOrders = orders.slice(startIndex, endIndex);

    tbody.innerHTML = currentPageOrders.map(order => createOrderRow(order)).join('');
    
    updateResultsInfo(orders.length, totalCount);
    updatePagination(orders.length);
}

function createOrderRow(order) {
    const statusBadge = createStatusBadge(order.status);
    const customerInfo = createCustomerInfo(order.customer_info);
    const itemsInfo = createItemsInfo(order.order_items);
    const orderDate = formatDate(order.created_at);
    const totalAmount = formatCurrency(order.total_amount);
    const actions = createActionButtons(order);

    return `
        <tr>
            <td><span class="order-id">${escapeHtml(order.order_id)}</span></td>
            <td>${customerInfo}</td>
            <td>${itemsInfo}</td>
            <td class="price-info">${totalAmount}</td>
            <td>${statusBadge}</td>
            <td class="date-info">${orderDate}</td>
            <td class="actions">${actions}</td>
        </tr>
    `;
}

function createStatusBadge(status) {
    if (!status) return '<span class="status-badge status-unknown">ไม่ทราบ</span>';
    
    // แปลง underscore เป็น hyphen เพื่อให้ match กับ CSS class names
    const statusClass = `status-${status.status_code.replace(/_/g, '-')}`;
    return `<span class="status-badge ${statusClass}">${escapeHtml(status.description)}</span>`;
}

function createCustomerInfo(customerInfo) {
    if (!customerInfo) return '-';
    
    return `
        <div class="customer-info">
            <div class="customer-name">${escapeHtml(customerInfo.name || 'ไม่ระบุ')}</div>
            <div class="customer-phone">${escapeHtml(customerInfo.phone || '-')}</div>
        </div>
    `;
}

function createItemsInfo(orderItems) {
    if (!orderItems || orderItems.length === 0) {
        return '<span style="color: #999;">ไม่มีรายการ</span>';
    }

    const firstItem = orderItems[0];
    const itemCount = orderItems.length;
    
    let itemsText = escapeHtml(firstItem.product_name || 'สินค้า');
    if (itemCount > 1) {
        itemsText += `<span class="item-count">+${itemCount - 1}</span>`;
    }
    
    return `<div class="order-items">${itemsText}</div>`;
}

function createActionButtons(order) {
    const status = order.status?.status_code;
    let buttons = [];

    // View button (always available)
    buttons.push(`
        <button class="btn btn-view" onclick="viewOrder('${order.order_id}')" title="ดูรายละเอียด">
            <i class="fas fa-eye"></i>
        </button>
    `);

    // Status-specific action buttons with permission checks
    switch (status) {
        case 'pending_payment':
            if (order.payment_info?.slip_image) {
                // Check approve permission
                if (hasPermissionForStatus('pending_payment', 'approve')) {
                    buttons.push(`
                        <button class="btn btn-approve" onclick="approvePayment('${order.order_id}')" title="อนุมัติการชำระเงิน">
                            <i class="fas fa-check"> อนุมัติ </i>
                        </button>
                    `);
                } else {
                    buttons.push(`
                        <button class="btn btn-approve btn-disabled" disabled title="ไม่มีสิทธิ์อนุมัติ">
                            <i class="fas fa-lock"> ล็อค </i>
                        </button>
                    `);
                }
                
                // Check reject permission
                if (hasPermissionForStatus('pending_payment', 'reject')) {
                    buttons.push(`
                        <button class="btn btn-reject" onclick="rejectPayment('${order.order_id}')" title="ปฏิเสธการชำระเงิน">
                            <i class="fas fa-times"> ปฏิเสธ </i>
                        </button>
                    `);
                } else {
                    buttons.push(`
                        <button class="btn btn-reject btn-disabled" disabled title="ไม่มีสิทธิ์ปฏิเสธ">
                            <i class="fas fa-lock"> ล็อค </i>
                        </button>
                    `);
                }
            }
            break;

        case 'awaiting_shipment':
            // Check ship permission
            if (hasPermissionForStatus('awaiting_shipment', 'ship')) {
                buttons.push(`
                    <button class="btn btn-ship" onclick="shipOrder('${order.order_id}')" title="จัดส่งสินค้า">
                        <i class="fas fa-truck"> จัดส่ง </i>
                    </button>
                `);
            } else {
                buttons.push(`
                    <button class="btn btn-ship btn-disabled" disabled title="ไม่มีสิทธิ์จัดส่ง">
                        <i class="fas fa-lock"> ล็อค </i>
                    </button>
                `);
            }
            
            // Check cancel permission
            if (hasPermissionForStatus('awaiting_shipment', 'cancel')) {
                buttons.push(`
                    <button class="btn btn-cancel" onclick="cancelOrder('${order.order_id}')" title="ยกเลิกคำสั่งซื้อ">
                        <i class="fas fa-ban"> ยกเลิก </i>
                    </button>
                `);
            } else {
                buttons.push(`
                    <button class="btn btn-cancel btn-disabled" disabled title="ไม่มีสิทธิ์ยกเลิก">
                        <i class="fas fa-lock"> ล็อค </i>
                    </button>
                `);
            }
            break;

        case 'in_transit':
            // Check complete permission
            if (hasPermissionForStatus('in_transit', 'complete')) {
                buttons.push(`
                    <button class="btn btn-approve" onclick="markAsDelivered('${order.order_id}')" title="ยืนยันการจัดส่ง">
                        <i class="fas fa-check"> สำเร็จ </i>
                    </button>
                `);
            } else {
                buttons.push(`
                    <button class="btn btn-approve btn-disabled" disabled title="ไม่มีสิทธิ์ยืนยัน">
                        <i class="fas fa-lock"> ล็อค </i>
                    </button>
                `);
            }
            
            // Check cancel permission
            if (hasPermissionForStatus('in_transit', 'cancel')) {
                buttons.push(`
                    <button class="btn btn-cancel" onclick="cancelOrder('${order.order_id}')" title="ยกเลิกคำสั่งซื้อ">
                        <i class="fas fa-ban"> ยกเลิก </i>
                    </button>
                `);
            } else {
                buttons.push(`
                    <button class="btn btn-cancel btn-disabled" disabled title="ไม่มีสิทธิ์ยกเลิก">
                        <i class="fas fa-lock"> ล็อค </i>
                    </button>
                `);
            }
            break;

        case 'delivered':
        case 'cancelled':
            // Only view button for completed orders
            break;
    }

    return buttons.join('');
}

function updateResultsInfo(filteredCount, totalCount) {
    const resultsInfo = document.getElementById('resultsInfo');
    if (resultsInfo) {
        if (filteredCount === totalCount) {
            resultsInfo.textContent = `แสดง ${filteredCount.toLocaleString()} รายการ`;
        } else {
            resultsInfo.textContent = `แสดง ${filteredCount.toLocaleString()} จาก ${totalCount.toLocaleString()} รายการ`;
        }
    }
}

function updatePagination(totalItems) {
    const pagination = document.getElementById('pagination');
    if (!pagination) return;

    const totalPages = Math.ceil(totalItems / itemsPerPage);
    
    if (totalPages <= 1) {
        pagination.innerHTML = '';
        return;
    }

    let paginationHTML = '';

    paginationHTML += `
        <button ${currentPage <= 1 ? 'disabled' : ''} onclick="changePage(${currentPage - 1})">
            <i class="fas fa-chevron-left"></i> ก่อนหน้า
        </button>
    `;

    const maxVisiblePages = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

    if (endPage - startPage + 1 < maxVisiblePages) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }

    if (startPage > 1) {
        paginationHTML += `<button onclick="changePage(1)">1</button>`;
        if (startPage > 2) {
            paginationHTML += `<span class="pagination-dots">...</span>`;
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        paginationHTML += `
            <button ${i === currentPage ? 'class="active"' : ''} onclick="changePage(${i})">
                ${i}
            </button>
        `;
    }

    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            paginationHTML += `<span class="pagination-dots">...</span>`;
        }
        paginationHTML += `<button onclick="changePage(${totalPages})">${totalPages}</button>`;
    }

    paginationHTML += `
        <button ${currentPage >= totalPages ? 'disabled' : ''} onclick="changePage(${currentPage + 1})">
            ถัดไป <i class="fas fa-chevron-right"></i>
        </button>
    `;

    pagination.innerHTML = paginationHTML;
}

function changePage(page) {
    const totalPages = Math.ceil(filteredOrders.length / itemsPerPage);
    if (page < 1 || page > totalPages) return;
    
    currentPage = page;
    displayOrders(filteredOrders, filteredOrders.length);
    
    document.querySelector('.table-container')?.scrollIntoView({ behavior: 'smooth' });
}

// Order action functions with permission validation
function viewOrder(orderId) {
    const order = allOrders.find(o => o.order_id === orderId);
    if (!order) {
        showNotification('ไม่พบข้อมูลคำสั่งซื้อ', 'error');
        return;
    }
    
    displayOrderDetails(order);
}

function approvePayment(orderId) {
    if (!hasPermissionForStatus('pending_payment', 'approve')) {
        showNotification('คุณไม่มีสิทธิ์ในการอนุมัติการชำระเงิน', 'error');
        return;
    }
    updateOrderStatus(orderId, 'awaiting_shipment');
}

async function rejectPayment(orderId) {
    if (!hasPermissionForStatus('pending_payment', 'reject')) {
        showNotification('คุณไม่มีสิทธิ์ในการปฏิเสธการชำระเงิน', 'error');
        return;
    }
    
    try {
        const notes = await showNotesModal('กรุณาระบุเหตุผลในการปฏิเสธการชำระเงิน', 'ปฏิเสธการชำระเงิน');
        if (notes !== null) {
            updateOrderStatus(orderId, 'cancelled', `ปฏิเสธการชำระเงิน: ${notes}`);
        }
    } catch (error) {
        // User cancelled
    }
}

function shipOrder(orderId) {
    if (!hasPermissionForStatus('awaiting_shipment', 'ship')) {
        showNotification('คุณไม่มีสิทธิ์ในการจัดส่งสินค้า', 'error');
        return;
    }
    updateOrderStatus(orderId, 'in_transit');
}

function markAsDelivered(orderId) {
    if (!hasPermissionForStatus('in_transit', 'complete')) {
        showNotification('คุณไม่มีสิทธิ์ในการยืนยันการจัดส่ง', 'error');
        return;
    }
    updateOrderStatus(orderId, 'delivered');
}

async function cancelOrder(orderId) {
    const order = allOrders.find(o => o.order_id === orderId);
    if (!order) {
        showNotification('ไม่พบข้อมูลคำสั่งซื้อ', 'error');
        return;
    }
    
    if (!hasPermissionForStatus(order.status.status_code, 'cancel')) {
        showNotification('คุณไม่มีสิทธิ์ในการยกเลิกคำสั่งซื้อ', 'error');
        return;
    }
    
    try {
        const notes = await showNotesModal('กรุณาระบุเหตุผลในการยกเลิกคำสั่งซื้อ', 'ยกเลิกคำสั่งซื้อ');
        if (notes !== null) {
            updateOrderStatus(orderId, 'cancelled', `ยกเลิกคำสั่งซื้อ: ${notes}`);
        }
    } catch (error) {
        // User cancelled
    }
}

// Notes Modal Functions
function showNotesModal(message, title) {
    return new Promise((resolve, reject) => {
        notesModalResolve = resolve;
        notesModalReject = reject;

        document.getElementById('notesModalTitle').textContent = title;
        document.getElementById('notesModalMessage').textContent = message;
        document.getElementById('notesTextarea').value = '';

        document.getElementById('notesModal').style.display = 'block';
        
        setTimeout(() => {
            document.getElementById('notesTextarea').focus();
        }, 100);
    });
}

function closeNotesModal() {
    document.getElementById('notesModal').style.display = 'none';
    if (notesModalReject) {
        notesModalReject(new Error('User cancelled'));
        notesModalReject = null;
        notesModalResolve = null;
    }
}

function submitNotes() {
    const notes = document.getElementById('notesTextarea').value.trim();
    if (notes === '') {
        showNotification('กรุณากรอกเหตุผล', 'error');
        return;
    }
    
    document.getElementById('notesModal').style.display = 'none';
    if (notesModalResolve) {
        notesModalResolve(notes);
        notesModalResolve = null;
        notesModalReject = null;
    }
}

function cancelNotes() {
    closeNotesModal();
}

async function updateOrderStatus(orderId, newStatus, notes = '') {
    try {
        const response = await fetch('controllers/update_order_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                order_id: orderId,
                status: newStatus,
                notes: notes
            })
        });

        const data = await response.json();

        if (data.success) {
            showNotification(data.message || 'อัปเดตสถานะสำเร็จ', 'success');
            
            await loadOrders();
            
            if (currentOrderId === orderId) {
                const updatedOrder = allOrders.find(o => o.order_id === orderId);
                if (updatedOrder) {
                    displayOrderDetails(updatedOrder);
                } else {
                    closeOrderDetailsModal();
                    showNotification('ไม่พบข้อมูลคำสั่งซื้อที่อัปเดต', 'warning');
                }
            }
            
        } else {
            throw new Error(data.message || 'Failed to update status');
        }

    } catch (error) {
        console.error('Error updating order status:', error);
        showNotification('เกิดข้อผิดพลาดในการอัปเดตสถานะ: ' + error.message, 'error');
    }
}

function displayOrderDetails(order) {
    currentOrderId = order.order_id;
    
    document.getElementById('detailCustomerName').textContent = order.customer_info?.name || '-';
    document.getElementById('detailCustomerPhone').textContent = order.customer_info?.phone || '-';
    document.getElementById('detailCustomerEmail').textContent = order.customer_info?.email || '-';
    
    let addressText = '-';
    if (order.shipping_address) {
        const addr = order.shipping_address;
        addressText = `${addr.address_line || ''}, ${addr.subdistrict || ''}, ${addr.district || ''}, ${addr.province || ''} ${addr.postal_code || ''}`.trim();
    }
    document.getElementById('detailCustomerAddress').textContent = addressText;
    
    document.getElementById('detailOrderId').textContent = order.order_id;
    document.getElementById('detailOrderDate').textContent = formatDate(order.created_at);
    document.getElementById('detailOrderStatus').innerHTML = createStatusBadge(order.status);
    document.getElementById('detailOrderNotes').textContent = order.note || 'ไม่มี';
    
    const paymentSection = document.getElementById('paymentSection');
    if (order.status?.status_code === 'pending_payment' && order.payment_info) {
        paymentSection.style.display = 'block';
        displayPaymentInfo(order.payment_info);
        updatePaymentActions(order.order_id);
    } else {
        paymentSection.style.display = 'none';
    }
    
    displayOrderItems(order.order_items || []);
    
    document.getElementById('detailOrderTotal').textContent = 
        `ยอดรวมทั้งหมด: ${formatCurrency(order.total_amount)}`;
    
    document.getElementById('orderDetailsModal').style.display = 'block';
}

function updatePaymentActions(orderId) {
    const verificationActions = document.querySelector('.verification-actions');
    if (!verificationActions) return;
    
    let actionsHTML = '';
    
    if (hasPermissionForStatus('pending_payment', 'approve')) {
        actionsHTML += `
            <button class="approve-payment-btn" onclick="approvePayment('${orderId}')" type="button">
                <i class="fas fa-check"></i>
                อนุมัติการชำระเงิน
            </button>
        `;
    } else {
        actionsHTML += `
            <button class="approve-payment-btn btn-disabled" disabled type="button">
                <i class="fas fa-lock"></i>
                ไม่มีสิทธิ์อนุมัติ
            </button>
        `;
    }
    
    if (hasPermissionForStatus('pending_payment', 'reject')) {
        actionsHTML += `
            <button class="reject-payment-btn" onclick="rejectPayment('${orderId}')" type="button">
                <i class="fas fa-times"></i>
                ปฏิเสธการชำระเงิน
            </button>
        `;
    } else {
        actionsHTML += `
            <button class="reject-payment-btn btn-disabled" disabled type="button">
                <i class="fas fa-lock"></i>
                ไม่มีสิทธิ์ปฏิเสธ
            </button>
        `;
    }
    
    verificationActions.innerHTML = actionsHTML;
}

function displayPaymentInfo(paymentInfo) {
    const paymentSlip = document.getElementById('paymentSlip');
    const noSlipMessage = document.getElementById('noSlipMessage');
    
    if (paymentInfo.slip_image) {
        paymentSlip.src = paymentInfo.slip_image;
        paymentSlip.style.display = 'block';
        noSlipMessage.style.display = 'none';
    } else {
        paymentSlip.style.display = 'none';
        noSlipMessage.style.display = 'block';
    }
}

function displayOrderItems(orderItems) {
    const tbody = document.getElementById('orderItemsList');
    
    if (!orderItems || orderItems.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: #666;">ไม่มีรายการสินค้า</td></tr>';
        return;
    }
    
    tbody.innerHTML = orderItems.map(item => `
        <tr>
            <td>${escapeHtml(item.product_name || 'ไม่ระบุ')}</td>
            <td>${item.quantity?.toLocaleString() || 0}</td>
            <td>${formatCurrency(item.price_each || 0)}</td>
            <td>${formatCurrency(item.line_total || 0)}</td>
        </tr>
    `).join('');
}

function closeOrderDetailsModal() {
    document.getElementById('orderDetailsModal').style.display = 'none';
    currentOrderId = null;
}

// Lightbox functions
function openLightbox(imageSrc) {
    const lightbox = document.getElementById('lightbox');
    const lightboxImage = document.getElementById('lightboxImage');
    
    if (lightbox && lightboxImage && imageSrc) {
        lightboxImage.src = imageSrc;
        lightbox.style.display = 'block';
    }
}

function closeLightbox() {
    const lightbox = document.getElementById('lightbox');
    if (lightbox) {
        lightbox.style.display = 'none';
    }
}

// Utility functions
function showLoading() {
    const loadingIndicator = document.getElementById('loadingIndicator');
    if (loadingIndicator) {
        loadingIndicator.style.display = 'block';
    }
}

function hideLoading() {
    const loadingIndicator = document.getElementById('loadingIndicator');
    if (loadingIndicator) {
        loadingIndicator.style.display = 'none';
    }
}

function showNotification(message, type = 'info') {
    // Remove existing notifications
    document.querySelectorAll('.notification').forEach(n => n.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        notification.remove();
    }, 5000);
    
    // Allow manual removal
    notification.addEventListener('click', () => {
        notification.remove();
    });
}

function formatCurrency(amount) {
    return '฿' + (parseFloat(amount) || 0).toLocaleString('th-TH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function formatDate(dateString) {
    if (!dateString) return '-';
    
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('th-TH', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (error) {
        return dateString;
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Responsive design handler
function handleResize() {
    const navbar = document.querySelector('.navbar-toggle');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');

    if (window.innerWidth <= 768) {
        // Mobile view
        if (navbar) navbar.style.display = 'block';
        if (mainContent) {
            mainContent.style.marginLeft = '0';
            mainContent.style.padding = '80px 15px 30px 15px';
        }
    } else {
        // Desktop view
        if (navbar) navbar.style.display = 'none';
        if (sidebar && sidebar.classList.contains('show')) {
            sidebar.classList.remove('show');
        }
        if (mainContent) {
            mainContent.classList.remove('overlay');
            mainContent.style.marginLeft = '260px';
            mainContent.style.padding = '30px';
        }
    }
}

// Add resize listener
window.addEventListener('resize', handleResize);
window.addEventListener('load', handleResize);