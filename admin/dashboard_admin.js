// Admin Dashboard JavaScript - Improved Version
// UI interactions with API integration

class DashboardManager {
    constructor() {
        this.salesChart = null;
        this.currentPeriod = '7days';
        this.isLoading = false;
        this.retryCount = 0;
        this.maxRetries = 3;
        
        // Constants
        this.REFRESH_INTERVAL = 5 * 60 * 1000; // 5 minutes
        
        this.init();
    }

    // Initialize dashboard
    init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.onDOMLoaded());
        } else {
            this.onDOMLoaded();
        }
    }

    // Handle DOM loaded event
    async onDOMLoaded() {
        try {
            await this.initializeDashboard();
            this.startPeriodicUpdates();
            this.updateCurrentTime();
            
            // Start time update interval
            setInterval(() => this.updateCurrentTime(), 1000);
            
        } catch (error) {
            console.error('Dashboard initialization failed:', error);
            this.showErrorMessage('ไม่สามารถเริ่มต้นแดชบอร์ดได้ กรุณาลองใหม่อีกครั้ง');
        }
    }

    // Initialize dashboard components
    async initializeDashboard() {
        try {
            await this.loadDashboardData();
        } catch (error) {
            console.error('Dashboard initialization error:', error);
            throw new Error('ไม่สามารถโหลดข้อมูลได้ กรุณาลองใหม่อีกครั้ง');
        }
    }

    // Load dashboard data from API with retry logic
    async loadDashboardData() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showLoadingStates();
        
        try {
            // Reset retry count on new load attempt
            this.retryCount = 0;
            
            // Load all data concurrently for better performance
            await Promise.allSettled([
                this.loadOverviewData(),
                this.loadSalesData(),
                this.loadRecentActivity(),
                this.loadRecentOrders()
            ]);
            
        } catch (error) {
            console.error('Error loading dashboard data:', error);
            this.handleLoadError(error);
        } finally {
            this.hideLoadingStates();
            this.isLoading = false;
        }
    }

    // Load overview data with improved error handling
    async loadOverviewData() {
        try {
            const response = await this.fetchWithRetry(
                `controllers/get_dashboard.php?type=overview&period=${this.currentPeriod}`
            );
            
            if (response.success) {
                this.updateOverviewCards(response.data);
            } else {
                throw new Error(response.error || 'Failed to load overview data');
            }
        } catch (error) {
            console.error('Error loading overview data:', error);
            this.setDefaultOverviewData();
        }
    }

    // Load sales data with improved error handling
    async loadSalesData() {
        try {
            const response = await this.fetchWithRetry(
                `controllers/get_dashboard.php?type=sales&period=${this.currentPeriod}`
            );
            
            if (response.success) {
                this.updateSalesChart(response.data);
            } else {
                throw new Error(response.error || 'Failed to load sales data');
            }
        } catch (error) {
            console.error('Error loading sales data:', error);
            this.handleChartError(error);
        }
    }

    // Load recent activity with improved error handling
    async loadRecentActivity() {
        try {
            const response = await this.fetchWithRetry(
                'controllers/get_dashboard.php?type=recent_activity'
            );
            
            if (response.success) {
                this.updateRecentActivity(response.data);
            } else {
                throw new Error(response.error || 'Failed to load recent activity');
            }
        } catch (error) {
            console.error('Error loading recent activity:', error);
            this.updateRecentActivity([]);
        }
    }

    // Load recent orders with improved error handling
    async loadRecentOrders() {
        try {
            const response = await this.fetchWithRetry(
                'controllers/get_dashboard.php?type=recent_orders'
            );
            
            if (response.success) {
                this.updateRecentOrders(response.data);
            } else {
                throw new Error(response.error || 'Failed to load recent orders');
            }
        } catch (error) {
            console.error('Error loading recent orders:', error);
            this.updateRecentOrders([]);
        }
    }

    // Fetch with retry logic and better error handling
    async fetchWithRetry(url, options = {}) {
        let lastError;
        
        for (let i = 0; i <= this.maxRetries; i++) {
            try {
                const response = await fetch(url, {
                    ...options,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        ...options.headers
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Non-JSON response:', text);
                    throw new Error('Server returned invalid response format');
                }

                return await response.json();
                
            } catch (error) {
                lastError = error;
                console.warn(`Request attempt ${i + 1} failed:`, error.message);
                
                if (i < this.maxRetries) {
                    // Exponential backoff: wait 1s, 2s, 4s
                    await new Promise(resolve => setTimeout(resolve, Math.pow(2, i) * 1000));
                }
            }
        }
        
        throw lastError;
    }

    // Update overview cards with null checks
    updateOverviewCards(data) {
        if (!data) return;

        const updates = [
            { id: 'total-sales', value: data.total_sales, prefix: '฿', isMoney: true },
            { id: 'total-orders', value: data.total_orders },
            { id: 'total-products', value: data.total_products },
            { id: 'total-users', value: data.total_users },
            { id: 'pending-orders', value: data.pending_orders },
            { id: 'low-stock-count', value: data.low_stock_count }
        ];

        updates.forEach(({ id, value, prefix = '', suffix = '', isMoney = false }) => {
            const element = document.getElementById(id);
            if (element && value !== undefined && value !== null) {
                let formattedValue;

                if (!isNaN(value)) {
                    const num = Number.parseFloat(value);
                    if (isMoney) {
                        formattedValue = num.toLocaleString('th-TH', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                    } else {
                        formattedValue = num.toLocaleString('th-TH', {
                            minimumFractionDigits: 0,
                            maximumFractionDigits: 0
                        });
                    }
                } else {
                    formattedValue = value;
                }

                element.textContent = `${prefix}${formattedValue}${suffix}`;
            }
        });
    }

    // Set default overview data when API fails
    setDefaultOverviewData() {
        const defaults = [
            { id: 'total-sales', value: '฿0' },
            { id: 'total-orders', value: '0' },
            { id: 'total-products', value: '0' },
            { id: 'total-users', value: '0' },
            { id: 'pending-orders', value: '0' },
            { id: 'low-stock-count', value: '0' }
        ];

        defaults.forEach(({ id, value }) => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = value;
            }
        });
    }

    // Update sales chart with improved error handling
    updateSalesChart(salesData) {
        const ctx = document.getElementById('salesChart');
        if (!ctx) return;

        try {
            // Validate data
            if (!Array.isArray(salesData) || salesData.length === 0) {
                this.handleChartError(new Error('No sales data available'));
                return;
            }

            // Prepare chart data
            const labels = salesData.map(item => {
                try {
                    const date = new Date(item.date);
                    if (isNaN(date.getTime())) {
                        throw new Error('Invalid date');
                    }
                    return date.toLocaleDateString('th-TH', { 
                        weekday: 'short', 
                        day: 'numeric' 
                    });
                } catch (e) {
                    return 'N/A';
                }
            });
            
            const salesValues = salesData.map(item => Number(item.sales) || 0);
            const orderCounts = salesData.map(item => Number(item.orders) || 0);

            // Destroy existing chart
            if (this.salesChart) {
                this.salesChart.destroy();
                this.salesChart = null;
            }

            // Create gradient
            const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, 'rgba(76, 175, 80, 0.3)');
            gradient.addColorStop(1, 'rgba(76, 175, 80, 0.05)');

            this.salesChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'ยอดขาย (บาท)',
                        data: salesValues,
                        borderColor: '#4CAF50',
                        backgroundColor: gradient,
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#4CAF50',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 3,
                        pointRadius: 6,
                        pointHoverRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (context) => {
                                    const dataIndex = context.dataIndex;
                                    const sales = context.parsed.y;
                                    const orders = orderCounts[dataIndex];
                                    return [
                                        `ยอดขาย: ฿${sales.toLocaleString()}`,
                                        `คำสั่งซื้อ: ${orders} รายการ`
                                    ];
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0,0,0,0.1)',
                                drawBorder: false
                            },
                            ticks: {
                                callback: (value) => '฿' + value.toLocaleString(),
                                color: '#666',
                                font: { size: 12 }
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: {
                                color: '#666',
                                font: { size: 12 }
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });

        } catch (error) {
            console.error('Chart creation error:', error);
            this.handleChartError(error);
        }
    }

    // Update recent activity with improved HTML generation
    updateRecentActivity(activities) {
        const container = document.getElementById('recent-activity-list');
        if (!container) return;
        
        if (!Array.isArray(activities) || activities.length === 0) {
            container.innerHTML = this.createEmptyState('ไม่มีกิจกรรมล่าสุด');
            return;
        }
        
        const activityHtml = activities.map(activity => {
            const timeAgo = this.getTimeAgo(activity.activity_time);
            const amount = activity.amount ? 
                `฿${Number(activity.amount).toLocaleString()}` : '';
            const description = this.escapeHtml(activity.description || '');
            
            return `
                <div class="activity-item">
                    <div class="activity-content">
                        <div class="activity-description">${description}</div>
                        ${amount ? `<div class="activity-amount">${amount}</div>` : ''}
                    </div>
                    <div class="activity-time">${timeAgo}</div>
                </div>
            `;
        }).join('');
        
        container.innerHTML = activityHtml;
    }

    // Update recent orders with improved HTML generation
    updateRecentOrders(orders) {
        const container = document.getElementById('recent-orders-list');
        if (!container) return;
        
        if (!Array.isArray(orders) || orders.length === 0) {
            container.innerHTML = this.createEmptyState('ไม่มีคำสั่งซื้อล่าสุด');
            return;
        }
        
        // Create table structure
        const tableHtml = `
            <div class="table-responsive">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>หมายเลขคำสั่งซื้อ</th>
                            <th>ลูกค้า</th>
                            <th>สถานะ</th>
                            <th>ยอดรวม</th>
                            <th>เวลา</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${orders.map(order => {
                            const timeAgo = this.getTimeAgo(order.created_at);
                            const statusClass = this.getStatusClass(order.status_code);
                            const customerName = this.escapeHtml(order.customer_name || 'ไม่ระบุ');
                            const statusDesc = this.escapeHtml(order.status_desc || 'ไม่ระบุสถานะ');
                            const amount = Number(order.total_amount) || 0;
                            
                            return `
                                <tr class="order-row">
                                    <td class="order-id-cell">
                                        <span class="order-id-badge">#${this.escapeHtml(order.order_id || '')}</span>
                                    </td>
                                    <td class="customer-cell">
                                        <span class="customer-name">${customerName}</span>
                                    </td>
                                    <td class="status-cell">
                                        <span class="order-status ${statusClass}">${statusDesc}</span>
                                    </td>
                                    <td class="amount-cell">
                                        <span class="amount-value">฿${amount.toLocaleString()}</span>
                                    </td>
                                    <td class="time-cell">
                                        <span class="time-ago">${timeAgo}</span>
                                    </td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            </div>
        `;
        
        container.innerHTML = tableHtml;
    }

    // Utility functions
    createEmptyState(message) {
        return `<div style="text-align: center; color: #666; padding: 20px;">${message}</div>`;
    }

    escapeHtml(unsafe) {
        if (typeof unsafe !== 'string') return '';
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    getStatusClass(statusCode) {
        const statusMap = {
            'status01': 'status-pending',
            'status02': 'status-confirmed',
            'status03': 'status-paid',
            'status04': 'status-shipped',
            'status05': 'status-delivered',
            'status06': 'status-cancelled'
        };
        return statusMap[statusCode] || 'status-unknown';
    }

    getTimeAgo(datetime) {
        try {
            const now = new Date();
            const time = new Date(datetime);
            
            if (isNaN(time.getTime())) {
                return 'เวลาไม่ถูกต้อง';
            }
            
            const diffInSeconds = Math.floor((now - time) / 1000);
            
            if (diffInSeconds < 60) return 'เมื่อสักครู่';
            if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} นาทีที่แล้ว`;
            if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} ชั่วโมงที่แล้ว`;
            return `${Math.floor(diffInSeconds / 86400)} วันที่แล้ว`;
            
        } catch (error) {
            console.error('Error calculating time ago:', error);
            return 'ไม่ทราบเวลา';
        }
    }

    // UI State Management
    showLoadingStates() {
        const loadingElements = document.querySelectorAll('.loading-indicator');
        const contentElements = document.querySelectorAll('.dashboard-content');
        
        loadingElements.forEach(el => el.style.display = 'block');
        contentElements.forEach(el => el.style.opacity = '0.5');
    }

    hideLoadingStates() {
        const loadingElements = document.querySelectorAll('.loading-indicator');
        const contentElements = document.querySelectorAll('.dashboard-content');
        
        loadingElements.forEach(el => el.style.display = 'none');
        contentElements.forEach(el => el.style.opacity = '1');
    }

    showErrorMessage(message) {
        const errorContainer = document.getElementById('error-message');
        if (errorContainer) {
            errorContainer.textContent = message;
            errorContainer.style.display = 'block';
            setTimeout(() => {
                errorContainer.style.display = 'none';
            }, 5000);
        }
        console.error(message);
    }

    handleLoadError(error) {
        console.error('Dashboard load error:', error);
        this.showErrorMessage('เกิดข้อผิดพลาดในการโหลดข้อมูล กรุณาลองใหม่อีกครั้ง');
    }

    handleChartError(error) {
        console.error('Chart error:', error);
        const chartContainer = document.querySelector('.chart-container');
        if (chartContainer) {
            const canvas = chartContainer.querySelector('canvas');
            if (canvas) canvas.style.display = 'none';
            
            const existingError = chartContainer.querySelector('.chart-error');
            if (!existingError) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'chart-error';
                errorDiv.style.cssText = 'text-align: center; padding: 40px; color: #666;';
                errorDiv.textContent = 'ไม่สามารถโหลดกราฟได้ในขณะนี้';
                chartContainer.appendChild(errorDiv);
            }
        }
    }

    // Time and Animation Functions
    updateCurrentTime() {
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
        
        const timeString = now.toLocaleDateString('th-TH', options);
        const timeElement = document.getElementById('current-time');
        if (timeElement) {
            timeElement.textContent = timeString;
        }
    }

    // Periodic Updates
    startPeriodicUpdates() {
        setInterval(() => {
            if (!this.isLoading) {
                this.loadDashboardData();
            }
        }, this.REFRESH_INTERVAL);
    }

    // Cleanup
    cleanup() {
        if (this.salesChart) {
            this.salesChart.destroy();
            this.salesChart = null;
        }
    }
}

// Initialize dashboard when script loads
window.dashboardManager = new DashboardManager();

// Export for external use
window.dashboardUtils = {
    updateDashboardData: () => window.dashboardManager?.loadDashboardData(),
    loadDashboardData: () => window.dashboardManager?.loadDashboardData()
};