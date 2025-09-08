// Initialize the page
document.addEventListener('DOMContentLoaded', async function() {
    updateCurrentTime();
    setInterval(updateCurrentTime, 1000);
    
    // Set default date range (last 30 days)
    const endDate = new Date();
    const startDate = new Date();
    startDate.setDate(startDate.getDate() - 30);
    
    document.getElementById('startDate').value = formatDate(startDate);
    document.getElementById('endDate').value = formatDate(endDate);
    
    // Load default report
    await loadReport('sales');
    
    // Initialize event listeners
    initializeEventListeners();
});

function initializeEventListeners() {
    // Report type change handler
    const reportTypeSelect = document.getElementById('reportType');
    if (reportTypeSelect) {
        reportTypeSelect.addEventListener('change', function() {
            const reportType = this.value;
            showReport(reportType);
        });
    }
    
    // Apply filters button
    const applyButton = document.querySelector('.btn-primary');
    if (applyButton && !applyButton.hasAttribute('data-listener-added')) {
        applyButton.addEventListener('click', applyFilters);
        applyButton.setAttribute('data-listener-added', 'true');
    }
    
    // Search functionality
    initializeTableSearch();
}

function updateCurrentTime() {
    const now = new Date();
    const options = {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        timeZone: 'Asia/Bangkok'
    };
    
    const timeElement = document.getElementById('current-time');
    if (timeElement) {
        timeElement.textContent = now.toLocaleDateString('th-TH', options);
    }
}

function formatDate(date) {
    return date.toISOString().split('T')[0];
}

function formatCurrency(amount) {
    if (amount === null || amount === undefined || isNaN(amount)) {
        return '฿0';
    }
    return new Intl.NumberFormat('th-TH', {
        style: 'currency',
        currency: 'THB'
    }).format(amount);
}

function formatNumber(number) {
    if (number === null || number === undefined || isNaN(number)) {
        return '0';
    }
    return new Intl.NumberFormat('th-TH').format(number);
}

// Sidebar toggle for mobile
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.classList.toggle('show');
    }
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(e) {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.querySelector('.navbar-toggle');
    
    if (window.innerWidth <= 768 && 
        sidebar && toggle &&
        !sidebar.contains(e.target) && 
        !toggle.contains(e.target) && 
        sidebar.classList.contains('show')) {
        sidebar.classList.remove('show');
    }
});

// Report tab switching
function showReport(reportType) {
    // Update tab buttons
    const tabs = document.querySelectorAll('.tab-button');
    tabs.forEach(tab => {
        tab.classList.remove('active');
        const onclick = tab.getAttribute('onclick');
        if (onclick && onclick.includes(`'${reportType}'`)) {
            tab.classList.add('active');
        }
    });
    
    // Update report content
    const contents = document.querySelectorAll('.report-content');
    contents.forEach(content => content.classList.remove('active'));
    
    const targetContent = document.getElementById(`${reportType}-report`);
    if (targetContent) {
        targetContent.classList.add('active');
    }
    
    // Update report type filter
    const reportTypeSelect = document.getElementById('reportType');
    if (reportTypeSelect) {
        reportTypeSelect.value = reportType;
    }
    
    // Load report data
    loadReport(reportType);
}

// Load report data from backend API
async function loadReport(reportType) {
    showLoading();
    
    try {
        // Get filter values with null checks
        const startDateElement = document.getElementById('startDate');
        const endDateElement = document.getElementById('endDate');
        const categoryElement = document.getElementById('productCategory');
        
        const startDate = startDateElement ? startDateElement.value : formatDate(new Date(Date.now() - 30 * 24 * 60 * 60 * 1000));
        const endDate = endDateElement ? endDateElement.value : formatDate(new Date());
        const categoryId = categoryElement ? categoryElement.value : 'all';

        switch(reportType) {
            case 'sales':
                await loadSalesReport(startDate, endDate, categoryId);
                break;
            case 'stock':
                await loadStockReport(categoryId);
                break;
            case 'movement':
                await loadMovementReport(startDate, endDate);
                break;
            case 'shipping':
                await loadShippingReport(startDate, endDate);
                break;
            case 'customer':
                await loadCustomerReport(startDate, endDate);
                break;
            default:
                console.warn('Unknown report type:', reportType);
                showNotification('ประเภทรายงานไม่ถูกต้อง', 'warning');
        }
    } catch (error) {
        console.error('Error loading report:', error);
        showNotification('เกิดข้อผิดพลาดในการโหลดรายงาน: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

function showLoading() {
    const loading = document.querySelector('.loading');
    if (loading) {
        loading.style.display = 'block';
    }
}

function hideLoading() {
    const loading = document.querySelector('.loading');
    if (loading) {
        loading.style.display = 'none';
    }
}

// Fixed API call function to handle both single objects and arrays properly
async function apiCall(reportType, params = {}) {
    try {
        const url = new URL(window.location.origin + window.location.pathname.replace('reports_admin.php', '') + 'controllers/get_reports.php');
        url.searchParams.append('type', reportType);
        
        Object.keys(params).forEach(key => {
            if (params[key] !== null && params[key] !== undefined && params[key] !== '') {
                url.searchParams.append(key, params[key]);
            }
        });
        
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Non-JSON response:', text);
            throw new Error('Server returned non-JSON response');
        }
        
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        return data;
    } catch (error) {
        console.error('API call failed:', error);
        throw error;
    }
}

// Sales Report - Fixed to handle proper data structure (removed period parameter)
async function loadSalesReport(startDate, endDate, categoryId) {
    try {
        // Load all sales data with proper error handling
        const [summaryData, productData, topProductsData] = await Promise.allSettled([
            apiCall('sales_summary', { start_date: startDate, end_date: endDate, category: categoryId }),
            apiCall('sales_by_product', { start_date: startDate, end_date: endDate, category: categoryId }),
            apiCall('top_products', { start_date: startDate, end_date: endDate, category: categoryId })
        ]);
        
        // Handle summary data
        if (summaryData.status === 'fulfilled') {
            const summary = summaryData.value;
            updateElement('total-sales', formatCurrency(summary.total_sales || 0));
            updateElement('total-orders', formatNumber(summary.total_orders || 0));
            updateElement('total-customers', formatNumber(summary.unique_customers || 0));
            updateElement('avg-order', formatCurrency(summary.avg_order_value || 0));
            updateElement('growth-rate', (summary.growth_rate || 0) + '%');
        } else {
            console.error('Failed to load sales summary:', summaryData.reason);
            // Set default values
            updateElement('total-sales', '฿0');
            updateElement('total-orders', '0');
            updateElement('total-customers', '0');
            updateElement('avg-order', '฿0');
            updateElement('growth-rate', '0%');
        }
        
        // Handle product data
        if (productData.status === 'fulfilled') {
            populateSalesTable(productData.value);
        } else {
            console.error('Failed to load product data:', productData.reason);
            populateErrorTable('sales-table', 7);
        }
        
        // Handle top products data
        if (topProductsData.status === 'fulfilled') {
            populateBestSellingTable(topProductsData.value);
        } else {
            console.error('Failed to load top products data:', topProductsData.reason);
            populateErrorTable('best-selling-table', 6);
        }
        
    } catch (error) {
        console.error('Error loading sales report:', error);
        showNotification('ไม่สามารถโหลดรายงานยอดขายได้', 'error');
        populateErrorTable('sales-table', 7);
        populateErrorTable('best-selling-table', 6);
    }
}

function populateSalesTable(data) {
    const tbody = document.querySelector('#sales-table tbody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    if (!Array.isArray(data) || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">ไม่มีข้อมูล</td></tr>';
        return;
    }
    
    data.forEach(item => {
        const row = tbody.insertRow();
        row.innerHTML = `
            <td>${item.product_id || '-'}</td>
            <td>${item.product_name || '-'}</td>
            <td>${getCategoryNameThai(item.category_name || item.category_id) || '-'}</td>
            <td class="text-center">${formatNumber(item.total_quantity || item.quantity_sold)}</td>
            <td class="text-center">${formatCurrency(item.total_sales || item.total_revenue)}</td>
            <td class="text-center">${formatCurrency(item.avg_price || item.price)}</td>
            <td class="text-center">${formatNumber(item.order_count || item.orders)}</td>
        `;
    });
}

function populateBestSellingTable(data) {
    const tbody = document.querySelector('#best-selling-table tbody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    if (!Array.isArray(data) || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">ไม่มีข้อมูล</td></tr>';
        return;
    }
    
    data.forEach(item => {
        const row = tbody.insertRow();
        const status = getStockStatus(item.current_stock || item.stock_quantity);
        
        row.innerHTML = `
            <td>${item.product_id || '-'}</td>
            <td>${item.product_name || '-'}</td>
            <td class="text-center">${formatNumber(item.total_sold || item.quantity_sold)}</td>
            <td class="text-center">${formatCurrency(item.total_revenue || item.revenue)}</td>
            <td class="text-center">${formatNumber(item.current_stock || item.stock_quantity || 0)}</td>
            <td><span class="status-badge ${status.class}">${status.text}</span></td>
        `;
    });
}

// Stock Report - Fixed to handle proper data structure
async function loadStockReport(categoryId) {
    try {
        const [stockData, reorderData, stockValueData] = await Promise.allSettled([
            apiCall('stock_summary', { category: categoryId }),
            apiCall('reorder_point', { category: categoryId }),
            apiCall('stock_value', { category: categoryId })
        ]);
        
        // Handle stock summary
        let totalProducts = 0;
        let lowStockCount = 0;
        let urgentCount = 0;
        let totalStockValue = 0;
        
        if (stockData.status === 'fulfilled') {
            const stock = Array.isArray(stockData.value) ? stockData.value : [stockData.value];
            totalProducts = stock.length;
        }
        
        if (reorderData.status === 'fulfilled') {
            const reorder = Array.isArray(reorderData.value) ? reorderData.value : [reorderData.value];
            urgentCount = reorder.filter(item => item.stock_status === 'URGENT' || item.urgency === 'urgent').length;
            lowStockCount = reorder.filter(item => item.stock_status === 'LOW' || item.urgency === 'low').length;
        }
        
        if (stockValueData.status === 'fulfilled') {
            const valueData = stockValueData.value;
            if (Array.isArray(valueData)) {
                totalStockValue = valueData.reduce((sum, item) => sum + (item.total_value || 0), 0);
            } else if (valueData.total_value) {
                totalStockValue = valueData.total_value;
            }
        }
        
        updateElement('total-products', formatNumber(totalProducts));
        updateElement('low-stock-count', formatNumber(lowStockCount));
        updateElement('critical-stock-count', formatNumber(urgentCount));
        updateElement('total-stock-value', formatCurrency(totalStockValue));
        
        // Populate tables
        if (stockData.status === 'fulfilled') {
            populateStockTable(stockData.value);
        } else {
            populateErrorTable('stock-table', 7);
        }
        
        if (reorderData.status === 'fulfilled') {
            populateReorderTable(reorderData.value);
        } else {
            populateErrorTable('reorder-table', 6);
        }
        
    } catch (error) {
        console.error('Error loading stock report:', error);
        showNotification('ไม่สามารถโหลดรายงานสต็อกได้', 'error');
        populateErrorTable('stock-table', 7);
        populateErrorTable('reorder-table', 6);
    }
}

function populateStockTable(data) {
    const tbody = document.querySelector('#stock-table tbody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    const stockArray = Array.isArray(data) ? data : [data];
    
    if (stockArray.length === 0 || !stockArray[0]) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">ไม่มีข้อมูล</td></tr>';
        return;
    }
    
    stockArray.forEach(item => {
        const row = tbody.insertRow();
        const status = getStockStatus(item.current_stock || item.stock_quantity);
        const stockValue = (item.current_stock || item.stock_quantity || 0) * (item.price || 0);
        
        row.innerHTML = `
            <td>${item.product_id || '-'}</td>
            <td>${item.product_name || '-'}</td>
            <td>${getCategoryNameThai(item.category_name || item.category_id) || '-'}</td>
            <td class="text-center">${formatNumber(item.current_stock || item.stock_quantity || 0)}</td>
            <td class="text-center">${formatCurrency(item.price || 0)}</td>
            <td><span class="status-badge ${status.class}">${status.text}</span></td>
            <td class="text-center">${formatCurrency(stockValue)}</td>
        `;
    });
}

function populateReorderTable(data) {
    const tbody = document.querySelector('#reorder-table tbody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    const reorderArray = Array.isArray(data) ? data : [data];
    
    if (reorderArray.length === 0 || !reorderArray[0]) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">ไม่มีข้อมูล</td></tr>';
        return;
    }
    
    reorderArray.forEach(item => {
        const row = tbody.insertRow();
        const urgency = getUrgencyClass(item.days_stock_remaining || item.days_remaining);
        const daysRemaining = item.days_stock_remaining || item.days_remaining || 'N/A';
        
        row.innerHTML = `
            <td>${item.product_id || '-'}</td>
            <td>${item.product_name || '-'}</td>
            <td class="text-center">${formatNumber(item.current_stock || item.stock_quantity || 0)}</td>
            <td class="text-center">${formatNumber(item.avg_daily_sales || item.daily_usage || 0)}</td>
            <td class="text-center ${urgency}">${daysRemaining} วัน</td>
            <td><span class="status-badge status-${(item.stock_status || item.urgency || '').toLowerCase()}">${getStockStatusThai(item.stock_status || item.urgency)}</span></td>
        `;
    });
}

// Movement Report - Fixed to handle proper data structure
async function loadMovementReport(startDate, endDate) {
    try {
        const movementData = await apiCall('stock_movement', {
            start_date: startDate,
            end_date: endDate
        });
        
        const movements = Array.isArray(movementData) ? movementData : [movementData];
        
        // Calculate summary from movement data
        let incomingCount = 0;
        let outgoingCount = 0;
        let stockAdjustments = 0;
        
        movements.forEach(item => {
            if (!item) return;
            
            const changeType = item.change_type || item.movement_type || item.type;
            const quantity = Math.abs(item.quantity_change || item.quantity || 0);
            
            switch(changeType) {
                case 'in':
                case 'receive':
                case 'adjustment_in':
                    incomingCount += quantity;
                    break;
                case 'out':
                case 'sale':
                case 'adjustment_out':
                    outgoingCount += quantity;
                    break;
                case 'adjust':
                case 'adjustment':
                    stockAdjustments += 1;
                    break;
            }
        });
        
        const netMovement = incomingCount - outgoingCount;
        
        updateElement('incoming-stock', formatNumber(incomingCount));
        updateElement('outgoing-stock', formatNumber(outgoingCount));
        updateElement('net-movement', formatNumber(netMovement));
        updateElement('stock-adjustments', formatNumber(stockAdjustments));
        
        populateMovementTable(movements);
        
    } catch (error) {
        console.error('Error loading movement report:', error);
        showNotification('ไม่สามารถโหลดรายงานการเคลื่อนไหวได้', 'error');
        populateErrorTable('movement-table', 9);
    }
}

function populateMovementTable(data) {
    const tbody = document.querySelector('#movement-table tbody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    const movements = Array.isArray(data) ? data : [data];
    
    if (movements.length === 0 || !movements[0]) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center">ไม่มีข้อมูล</td></tr>';
        return;
    }
    
    movements.forEach(item => {
        const row = tbody.insertRow();
        const changeType = item.change_type || item.movement_type || item.type;
        const typeInfo = getMovementTypeFromDB(changeType);
        
        row.innerHTML = `
            <td>${formatThaiDate(item.created_at || item.movement_date || item.date)}</td>
            <td>${item.product_id || '-'}</td>
            <td>${item.product_name || '-'}</td>
            <td><span class="${typeInfo.class}">${typeInfo.text}</span></td>
            <td class="text-center">${formatNumber(Math.abs(item.quantity_change || item.quantity || 0))}</td>
            <td class="text-center">${formatNumber(item.quantity_before || item.before_quantity || 0)}</td>
            <td class="text-center">${formatNumber(item.quantity_after || item.after_quantity || 0)}</td>
            <td>${getReferenceThai(item.reference_type || item.reference)}</td>
            <td>${item.admin_name || item.user_name || item.performed_by || '-'}</td>
        `;
    });
}

// Shipping Report - Fixed to handle proper data structure
async function loadShippingReport(startDate, endDate) {
    try {
        const [summaryData, zoneData] = await Promise.allSettled([
            apiCall('shipping_summary', { start_date: startDate, end_date: endDate }),
            apiCall('shipping_by_zone', { start_date: startDate, end_date: endDate })
        ]);
        
        let deliveredOrders = 0;
        let pendingOrders = 0;
        let totalShippingFee = 0;
        let avgShippingFee = 0;
        
        if (summaryData.status === 'fulfilled') {
            const summary = summaryData.value;
            if (Array.isArray(summary)) {
                deliveredOrders = summary.filter(item => item.status_code === 'delivered' || item.status === 'delivered')
                                         .reduce((sum, item) => sum + (item.order_count || item.orders || 0), 0);
                pendingOrders = summary.filter(item => item.status_code === 'awaiting_shipment' || item.status === 'pending')
                                      .reduce((sum, item) => sum + (item.order_count || item.orders || 0), 0);
                totalShippingFee = summary.reduce((sum, item) => sum + (item.total_shipping_fee || item.shipping_fee || 0), 0);
                avgShippingFee = summary.reduce((sum, item) => sum + (item.avg_shipping_fee || 0), 0) / summary.length;
            } else if (summary) {
                deliveredOrders = summary.delivered_orders || 0;
                pendingOrders = summary.pending_orders || 0;
                totalShippingFee = summary.total_shipping_fee || 0;
                avgShippingFee = summary.avg_shipping_fee || 0;
            }
        }
        
        updateElement('shipped-orders', formatNumber(deliveredOrders));
        updateElement('pending-orders', formatNumber(pendingOrders));
        updateElement('total-shipping-fee', formatCurrency(totalShippingFee));
        updateElement('avg-shipping-fee', formatCurrency(avgShippingFee));
        
        if (zoneData.status === 'fulfilled') {
            populateShippingTable(zoneData.value);
        } else {
            populateErrorTable('shipping-table', 6);
        }
        
    } catch (error) {
        console.error('Error loading shipping report:', error);
        showNotification('ไม่สามารถโหลดรายงานการจัดส่งได้', 'error');
        populateErrorTable('shipping-table', 6);
    }
}

function populateShippingTable(data) {
    const tbody = document.querySelector('#shipping-table tbody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    const zones = Array.isArray(data) ? data : [data];
    
    if (zones.length === 0 || !zones[0]) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">ไม่มีข้อมูล</td></tr>';
        return;
    }
    
    zones.forEach(item => {
        const row = tbody.insertRow();
        const totalOrders = item.total_orders || item.orders || 0;
        const deliveredOrders = item.delivered_orders || item.successful_deliveries || totalOrders;
        const successRate = totalOrders > 0 ? ((deliveredOrders / totalOrders) * 100).toFixed(1) : '0';
        
        row.innerHTML = `
            <td>${item.zone_name || item.shipping_zone || '-'}</td>
            <td class="text-center">${formatNumber(totalOrders)}</td>
            <td class="text-center">${formatNumber(deliveredOrders)}</td>
            <td class="text-center">${successRate}%</td>
            <td class="text-center">${formatCurrency(item.total_shipping_fee || item.shipping_fee || 0)}</td>
            <td class="text-center">${formatCurrency(item.avg_shipping_fee || item.average_fee || 0)}</td>
        `;
    });
}

// Customer Report - Fixed to handle proper data structure
async function loadCustomerReport(startDate, endDate) {
    try {
        const [summaryData, topCustomersData] = await Promise.allSettled([
            apiCall('customer_summary', { start_date: startDate, end_date: endDate }),
            apiCall('top_customers', { start_date: startDate, end_date: endDate })
        ]);
        
        let newCustomers = 0;
        let returningCustomers = 0;
        let avgOrderValue = 0;
        let avgOrdersPerCustomer = 0;
        
        if (summaryData.status === 'fulfilled') {
            const summary = summaryData.value;
            newCustomers = summary.new_customers || 0;
            returningCustomers = summary.returning_customers || 0;
            avgOrderValue = summary.avg_order_value || 0;
            avgOrdersPerCustomer = summary.avg_orders_per_customer || 0;
        }
        
        updateElement('new-customers', formatNumber(newCustomers));
        updateElement('returning-customers', formatNumber(returningCustomers));
        updateElement('avg-order-value', formatCurrency(avgOrderValue));
        updateElement('avg-orders-per-customer', formatNumber(avgOrdersPerCustomer));
        
        if (topCustomersData.status === 'fulfilled') {
            populateCustomerTable(topCustomersData.value);
        } else {
            populateErrorTable('customer-table', 6);
        }
        
    } catch (error) {
        console.error('Error loading customer report:', error);
        showNotification('ไม่สามารถโหลดรายงานลูกค้าได้', 'error');
        populateErrorTable('customer-table', 6);
    }
}

function populateCustomerTable(data) {
    const tbody = document.querySelector('#customer-table tbody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    const customers = Array.isArray(data) ? data : [data];
    
    if (customers.length === 0 || !customers[0]) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">ไม่มีข้อมูล</td></tr>';
        return;
    }
    
    customers.forEach(item => {
        const row = tbody.insertRow();
        row.innerHTML = `
            <td>${item.customer_name || item.name || item.fullname || '-'}</td>
            <td>${item.email || '-'}</td>
            <td class="text-center">${formatNumber(item.total_orders || item.orders || 0)}</td>
            <td class="text-center">${formatCurrency(item.total_spent || item.total_amount || 0)}</td>
            <td class="text-center">${formatCurrency(item.avg_order_value || item.average_order || 0)}</td>
            <td>${formatThaiDate(item.last_order_date || item.last_order)}</td>
        `;
    });
}

// Helper functions
function updateElement(id, value) {
    const element = document.getElementById(id);
    if (element) {
        element.textContent = value;
    }
}

function populateErrorTable(tableId, colSpan) {
    const tbody = document.querySelector(`#${tableId} tbody`);
    if (tbody) {
        tbody.innerHTML = `<tr><td colspan="${colSpan}" class="text-center">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>`;
    }
}

function getCategoryNameThai(category) {
    const categories = {
        'rb': 'เหล็กเส้น',
        'sp': 'เหล็กแผ่น', 
        'ss': 'เหล็กรูปพรรณ',
        'wm': 'เหล็กตะแกรง/ตาข่าย',
        'ot': 'อื่นๆ'
    };
    return categories[category] || category || 'ไม่ระบุ';
}

function getStockStatus(stock) {
    const stockNum = parseInt(stock) || 0;
    if (stockNum > 50) {
        return { class: 'status-in-stock', text: 'ปกติ' };
    } else if (stockNum > 10) {
        return { class: 'status-low-stock', text: 'ใกล้หมด' };
    } else {
        return { class: 'status-critical', text: 'วิกฤต' };
    }
}

function getStockStatusThai(status) {
    const statusMap = {
        'OK': 'ปกติ',
        'LOW': 'ใกล้หมด',
        'URGENT': 'วิกฤต',
        'urgent': 'วิกฤต',
        'low': 'ใกล้หมด',
        'normal': 'ปกติ'
    };
    return statusMap[status] || status || 'ไม่ทราบ';
}

function getMovementTypeFromDB(changeType) {
    const types = {
        'in': { class: 'movement-in', text: 'รับเข้า' },
        'out': { class: 'movement-out', text: 'เบิกออก' },
        'adjust': { class: 'movement-adjust', text: 'ปรับปรุง' },
        'receive': { class: 'movement-in', text: 'รับเข้า' },
        'sale': { class: 'movement-out', text: 'ขาย' },
        'adjustment': { class: 'movement-adjust', text: 'ปรับปรุง' },
        'adjustment_in': { class: 'movement-in', text: 'ปรับเพิ่ม' },
        'adjustment_out': { class: 'movement-out', text: 'ปรับลด' }
    };
    return types[changeType] || { class: '', text: changeType || 'ไม่ทราบ' };
}

function getReferenceThai(referenceType) {
    const references = {
        'order': 'คำสั่งซื้อ',
        'cancel': 'ยกเลิก',
        'receive': 'รับสินค้า',
        'manual': 'ปรับด้วยตนเอง',
        'adjustment': 'ปรับปรุงสต็อก',
        'sale': 'การขาย',
        'return': 'การคืนสินค้า'
    };
    return references[referenceType] || referenceType || 'ไม่ระบุ';
}

function getUrgencyClass(days) {
    const daysNum = parseFloat(days);
    if (!days || isNaN(daysNum) || daysNum <= 3) return 'urgency-urgent';
    if (daysNum <= 7) return 'urgency-warning';
    return 'urgency-normal';
}

function formatThaiDate(dateString) {
    if (!dateString) return 'N/A';
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return 'N/A';
        return date.toLocaleDateString('th-TH', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (error) {
        return 'N/A';
    }
}

// Filter functions
function resetFilters() {
    const endDate = new Date();
    const startDate = new Date();
    startDate.setDate(startDate.getDate() - 30);
    
    const startDateEl = document.getElementById('startDate');
    const endDateEl = document.getElementById('endDate');
    const categoryEl = document.getElementById('productCategory');
    const reportTypeEl = document.getElementById('reportType');
    
    if (startDateEl) startDateEl.value = formatDate(startDate);
    if (endDateEl) endDateEl.value = formatDate(endDate);
    if (categoryEl) categoryEl.value = 'all';
    if (reportTypeEl) reportTypeEl.value = 'sales';
    
    showReport('sales');
}

async function applyFilters() {
    const reportTypeEl = document.getElementById('reportType');
    const reportType = reportTypeEl ? reportTypeEl.value : 'sales';
    
    try {
        await loadReport(reportType);
        showNotification('ปรับใช้ตัวกรองสำเร็จ', 'success');
    } catch (error) {
        showNotification('เกิดข้อผิดพลาดในการปรับใช้ตัวกรอง', 'error');
    }
}

// Export Excel function - Generate Excel file using SheetJS
async function exportReport(format = 'excel') {
    const activeReport = document.querySelector('.report-content.active');
    if (!activeReport) {
        showNotification('ไม่พบรายงานที่จะส่งออก', 'warning');
        return;
    }

    const reportType = activeReport.id.split('-')[0];
    let filename = '';
    let data = [];

    try {
        // Extract data and set filename
        switch(reportType) {
            case 'sales':
                data = extractTableData('sales-table');
                filename = 'รายงานยอดขาย_' + formatDate(new Date());
                break;
            case 'stock':
                data = extractTableData('stock-table');
                filename = 'รายงานสต็อก_' + formatDate(new Date());
                break;
            case 'movement':
                data = extractTableData('movement-table');
                filename = 'รายงานการเคลื่อนไหว_' + formatDate(new Date());
                break;
            case 'shipping':
                data = extractTableData('shipping-table');
                filename = 'รายงานการจัดส่ง_' + formatDate(new Date());
                break;
            case 'customer':
                data = extractTableData('customer-table');
                filename = 'รายงานลูกค้า_' + formatDate(new Date());
                break;
            default:
                showNotification('ไม่รองรับการส่งออกสำหรับรายงานนี้', 'warning');
                return;
        }

        if (data.length === 0) {
            showNotification('ไม่มีข้อมูลสำหรับส่งออก', 'warning');
            return;
        }

        if (format === 'excel') {
            // ✅ Create workbook and worksheet here
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.aoa_to_sheet(data);
            XLSX.utils.book_append_sheet(wb, ws, "Report");
            XLSX.writeFile(wb, filename + '.xlsx');
        }

        showNotification('ส่งออกรายงานสำเร็จ', 'success');
    } catch (error) {
        console.error('Export error:', error);
        showNotification('เกิดข้อผิดพลาดในการส่งออกรายงาน', 'error');
    }
}

function extractTableData(tableId) {
    const table = document.getElementById(tableId);
    if (!table) return [];

    const data = [];
    const headers = [];

    // Extract headers
    const headerRow = table.querySelector('thead tr');
    if (headerRow) {
        Array.from(headerRow.cells).forEach(cell => {
            headers.push(cell.textContent.trim());
        });
        data.push(headers);
    }

    // Extract data rows
    const tbody = table.querySelector('tbody');
    if (tbody) {
        Array.from(tbody.rows).forEach(row => {
            if (row.cells.length > 1 && !row.cells[0].getAttribute('colspan')) {
                const rowData = [];
                Array.from(row.cells).forEach(cell => {
                    let text = cell.textContent.replace(/\s+/g, ' ').trim();

                    // Remove status badge styling
                    const badge = cell.querySelector('.status-badge');
                    if (badge) {
                        text = badge.textContent.trim();
                    }

                    // Try convert to number if possible
                    const num = text.replace(/,/g, '');
                    if (!isNaN(num) && num !== '') {
                        rowData.push(Number(num));
                    } else {
                        rowData.push(text);
                    }
                });
                data.push(rowData);
            }
        });
    }

    return data;
}

function showNotification(message, type = 'info') {
    // Remove any existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => {
        if (document.body.contains(notification)) {
            document.body.removeChild(notification);
        }
    });
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 10000;
        animation: slideIn 0.3s ease;
        max-width: 400px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        font-family: 'Prompt', sans-serif;
        word-wrap: break-word;
    `;
    
    // Set background color based on type
    const colors = {
        success: '#28a745',
        info: '#17a2b8',
        warning: '#ffc107',
        error: '#dc3545'
    };
    notification.style.backgroundColor = colors[type] || colors.info;
    
    // Adjust text color for warning
    if (type === 'warning') {
        notification.style.color = '#333';
    }
    
    notification.textContent = message;
    document.body.appendChild(notification);
    
    // Remove notification after timeout
    const timeout = type === 'error' ? 5000 : 3000;
    setTimeout(() => {
        if (document.body.contains(notification)) {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                if (document.body.contains(notification)) {
                    document.body.removeChild(notification);
                }
            }, 300);
        }
    }, timeout);
}

// Table search functionality
function initializeTableSearch() {
    const searchInputs = document.querySelectorAll('.table-search');
    
    searchInputs.forEach(input => {
        // Remove existing listeners to avoid duplicates
        const newInput = input.cloneNode(true);
        input.parentNode.replaceChild(newInput, input);
        
        newInput.addEventListener('keyup', function() {
            const tableId = this.getAttribute('data-table');
            const table = document.getElementById(tableId);
            
            if (!table) return;
            
            const searchTerm = this.value.toLowerCase();
            const tbody = table.getElementsByTagName('tbody')[0];
            
            if (!tbody) return;
            
            const rows = tbody.getElementsByTagName('tr');
            let visibleRows = 0;
            
            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const cells = row.getElementsByTagName('td');
                let found = false;
                
                // Skip if this is an error/no data row
                if (cells.length === 1 && cells[0].getAttribute('colspan')) {
                    continue;
                }
                
                for (let j = 0; j < cells.length; j++) {
                    if (cells[j].textContent.toLowerCase().includes(searchTerm)) {
                        found = true;
                        break;
                    }
                }
                
                if (found) {
                    row.style.display = '';
                    visibleRows++;
                } else {
                    row.style.display = 'none';
                }
            }
            
            // Show message if no results found
            if (visibleRows === 0 && searchTerm.trim() !== '') {
                // Check if no results row already exists
                let noResultsRow = tbody.querySelector('.no-results-row');
                if (!noResultsRow) {
                    noResultsRow = tbody.insertRow();
                    noResultsRow.className = 'no-results-row';
                    const colCount = table.querySelector('thead tr').cells.length;
                    noResultsRow.innerHTML = `<td colspan="${colCount}" class="text-center" style="color: #999; font-style: italic;">ไม่พบข้อมูลที่ตรงกับการค้นหา</td>`;
                }
                noResultsRow.style.display = '';
            } else {
                // Hide no results row if it exists
                const noResultsRow = tbody.querySelector('.no-results-row');
                if (noResultsRow) {
                    noResultsRow.style.display = 'none';
                }
            }
        });
    });
}

async function handleLogout() {
    if (!confirm('คุณต้องการออกจากระบบหรือไม่?')) return;
    
    try {
        const response = await fetch('controllers/logout.php', {
            method: 'POST', 
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        const data = await response.json();
        
        if (data.success) {
            window.location.href = data.redirect;
        } else {
            throw new Error(data.error || 'Logout failed');
        }
    } catch (error) {
        console.error('Logout error:', error);
        // Fallback to direct redirect
        window.location.href = 'controllers/logout.php';
    }
}

// Add CSS for notification animations and improved styling
if (!document.getElementById('notification-styles')) {
    const style = document.createElement('style');
    style.id = 'notification-styles';
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: bold;
            text-align: center;
            white-space: nowrap;
        }
        
        .status-in-stock { background-color: #d4edda; color: #155724; }
        .status-low-stock { background-color: #fff3cd; color: #856404; }
        .status-critical, .status-urgent { background-color: #f8d7da; color: #721c24; }
        .status-out-of-stock { background-color: #f8d7da; color: #721c24; }
        
        .status-ok, .status-normal { background-color: #d4edda; color: #155724; }
        .status-low { background-color: #fff3cd; color: #856404; }
        
        .movement-in { color: #28a745; font-weight: bold; }
        .movement-out { color: #dc3545; font-weight: bold; }
        .movement-adjust { color: #17a2b8; font-weight: bold; }
        
        .urgency-urgent { color: #dc3545; font-weight: bold; }
        .urgency-warning { color: #ffc107; font-weight: bold; }
        .urgency-normal { color: #28a745; }
        
        .loading {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
            background: rgba(255,255,255,0.95);
            color: #333;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        .no-results-row td {
            padding: 20px !important;
            color: #999 !important;
            font-style: italic;
        }
        
        /* Improve table search input styling */
        .table-search {
            transition: all 0.3s ease;
        }
        
        .table-search:focus {
            transform: scale(1.02);
            box-shadow: 0 0 0 2px rgba(153, 0, 0, 0.2);
        }
        
        /* Loading spinner animation */
        .loading-spinner {
            margin-bottom: 15px;
        }
    `;
    document.head.appendChild(style);
}

// Global error handlers
window.addEventListener('error', function(e) {
    console.error('Global error:', e.error);
    showNotification('เกิดข้อผิดพลาดในระบบ', 'error');
});

window.addEventListener('unhandledrejection', function(e) {
    console.error('Unhandled promise rejection:', e.reason);
    showNotification('เกิดข้อผิดพลาดในการโหลดข้อมูล', 'error');
});

// Ensure all functions are available globally
window.toggleSidebar = toggleSidebar;
window.showReport = showReport;
window.resetFilters = resetFilters;
window.applyFilters = applyFilters;
window.exportReport = exportReport;
window.handleLogout = handleLogout;