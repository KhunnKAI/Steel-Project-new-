// Global variables
let currentPage = 1;
let itemsPerPage = 20;
let allOrders = [];
let filteredOrders = [];
let currentOrderId = null;
let isLoading = false;

// Notes modal variables
let notesModalResolve = null;
let notesModalReject = null;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
    loadOrders();
});

function initializeEventListeners() {
    // Search input
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                applyFilters();
            }, 300);
        });
    }

    // Filter controls
    const statusFilter = document.getElementById('statusFilter');
    const dateFromFilter = document.getElementById('dateFromFilter');
    const dateToFilter = document.getElementById('dateToFilter');

    [statusFilter, dateFromFilter, dateToFilter].forEach(filter => {
        if (filter) {
            filter.addEventListener('change', applyFilters);
        }
    });

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
            toDate.setHours(23, 59, 59, 999); // End of day
            if (orderDate > toDate) return false;
        }

        return true;
    });

    currentPage = 1;
    displayOrders(filteredOrders, filteredOrders.length);
}

function updateStatistics(statistics) {
    // Initialize counters
    const stats = {
        pending_payment: 0,
        awaiting_shipment: 0,
        in_transit: 0,
        delivered: 0,
        cancelled: 0
    };

    // Count orders from statistics
    statistics.forEach(stat => {
        if (stats.hasOwnProperty(stat.status_code)) {
            stats[stat.status_code] = parseInt(stat.count) || 0;
        }
    });

    // Update DOM elements
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

    // Calculate pagination
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
    
    const statusClass = `status-${status.status_code}`;
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

    // Status-specific action buttons
    switch (status) {
        case 'pending_payment':
            if (order.payment_info?.slip_image) {
                buttons.push(`
                    <button class="btn btn-approve" onclick="approvePayment('${order.order_id}')" title="อนุมัติการชำระเงิน">
                        <i class="fas fa-check"> อนุมัติ </i>
                    </button>
                `);
                buttons.push(`
                    <button class="btn btn-reject" onclick="rejectPayment('${order.order_id}')" title="ปฏิเสธการชำระเงิน">
                        <i class="fas fa-times"> ปฏิเสธ </i>
                    </button>
                `);
            }
            break;

        case 'awaiting_shipment':
            buttons.push(`
                <button class="btn btn-ship" onclick="shipOrder('${order.order_id}')" title="จัดส่งสินค้า">
                    <i class="fas fa-truck"> จัดส่ง </i>
                </button>
            `);
            buttons.push(`
                <button class="btn btn-cancel" onclick="cancelOrder('${order.order_id}')" title="ยกเลิกคำสั่งซื้อ">
                    <i class="fas fa-ban"> ยกเลิก </i>
                </button>
            `);
            break;

        case 'in_transit':
            buttons.push(`
                <button class="btn btn-approve" onclick="markAsDelivered('${order.order_id}')" title="ยืนยันการจัดส่ง">
                    <i class="fas fa-check"> สำเร็จ </i>
                </button>
            `);
            buttons.push(`
                <button class="btn btn-cancel" onclick="cancelOrder('${order.order_id}')" title="ยกเลิกคำสั่งซื้อ">
                    <i class="fas fa-ban"> ยกเลิก </i>
                </button>
            `);
            break;

        case 'delivered':
            // Only view button for delivered orders - no cancel button
            break;

        case 'cancelled':
            // Only view button for cancelled orders
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

    // Previous button
    paginationHTML += `
        <button ${currentPage <= 1 ? 'disabled' : ''} onclick="changePage(${currentPage - 1})">
            <i class="fas fa-chevron-left"></i> ก่อนหน้า
        </button>
    `;

    // Page numbers with ellipsis logic
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

    // Next button
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
    
    // Scroll to top of table
    document.querySelector('.table-container')?.scrollIntoView({ behavior: 'smooth' });
}

// Order action functions
function viewOrder(orderId) {
    const order = allOrders.find(o => o.order_id === orderId);
    if (!order) {
        showNotification('ไม่พบข้อมูลคำสั่งซื้อ', 'error');
        return;
    }
    
    displayOrderDetails(order);
}

function approvePayment(orderId) {
    updateOrderStatus(orderId, 'awaiting_shipment');
}

async function rejectPayment(orderId) {
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
    updateOrderStatus(orderId, 'in_transit');
}

function markAsDelivered(orderId) {
    updateOrderStatus(orderId, 'delivered');
}

async function cancelOrder(orderId) {
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

        // Set modal content
        document.getElementById('notesModalTitle').textContent = title;
        document.getElementById('notesModalMessage').textContent = message;
        document.getElementById('notesTextarea').value = '';

        // Show modal
        document.getElementById('notesModal').style.display = 'block';
        
        // Focus on textarea
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
            
            // Reload orders data
            await loadOrders();
            
            // If modal is open for this order, refresh its content
            if (currentOrderId === orderId) {
                const updatedOrder = allOrders.find(o => o.order_id === orderId);
                if (updatedOrder) {
                    displayOrderDetails(updatedOrder);
                } else {
                    // If order not found after update, close modal
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
    
    // Update order details
    document.getElementById('detailCustomerName').textContent = order.customer_info?.name || '-';
    document.getElementById('detailCustomerPhone').textContent = order.customer_info?.phone || '-';
    document.getElementById('detailCustomerEmail').textContent = order.customer_info?.email || '-';
    
    // Address
    let addressText = '-';
    if (order.shipping_address) {
        const addr = order.shipping_address;
        addressText = `${addr.address_line || ''}, ${addr.subdistrict || ''}, ${addr.district || ''}, ${addr.province || ''} ${addr.postal_code || ''}`.trim();
    }
    document.getElementById('detailCustomerAddress').textContent = addressText;
    
    // Order info
    document.getElementById('detailOrderId').textContent = order.order_id;
    document.getElementById('detailOrderDate').textContent = formatDate(order.created_at);
    document.getElementById('detailOrderStatus').innerHTML = createStatusBadge(order.status);
    document.getElementById('detailOrderNotes').textContent = order.note || 'ไม่มี';
    
    // Payment section
    const paymentSection = document.getElementById('paymentSection');
    if (order.status?.status_code === 'pending_payment' && order.payment_info) {
        paymentSection.style.display = 'block';
        displayPaymentInfo(order.payment_info);
    } else {
        paymentSection.style.display = 'none';
    }
    
    // Order items
    displayOrderItems(order.order_items || []);
    
    // Total
    document.getElementById('detailOrderTotal').textContent = 
        `ยอดรวมทั้งหมด: ${formatCurrency(order.total_amount)}`;
    
    // Show modal
    document.getElementById('orderDetailsModal').style.display = 'block';
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

// Sidebar functions
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    const navbarToggle = document.querySelector('.navbar-toggle');
    
    if (sidebar && mainContent) {
        const isCollapsed = sidebar.classList.contains('collapsed');
        
        if (isCollapsed) {
            sidebar.classList.remove('collapsed');
            mainContent.style.marginLeft = '260px';
            if (navbarToggle) {
                navbarToggle.style.display = 'none';
            }
        } else {
            sidebar.classList.add('collapsed');
            mainContent.style.marginLeft = '0';
            if (navbarToggle) {
                navbarToggle.style.display = 'block';
            }
        }
    }
}

// Logout function
function handleLogout() {
    if (confirm('คุณต้องการออกจากระบบหรือไม่?')) {
        window.location.href = 'controllers/logout.php';
    }
}

// Responsive handling
function handleResize() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    const navbarToggle = document.querySelector('.navbar-toggle');
    
    if (window.innerWidth <= 768) {
        if (sidebar && !sidebar.classList.contains('collapsed')) {
            sidebar.classList.add('collapsed');
        }
        if (mainContent) {
            mainContent.style.marginLeft = '0';
        }
        if (navbarToggle) {
            navbarToggle.style.display = 'block';
        }
    } else {
        if (sidebar && sidebar.classList.contains('collapsed')) {
            sidebar.classList.remove('collapsed');
        }
        if (mainContent) {
            mainContent.style.marginLeft = '260px';
        }
        if (navbarToggle) {
            navbarToggle.style.display = 'none';
        }
    }
}

// Add resize listener
window.addEventListener('resize', handleResize);
window.addEventListener('load', handleResize);