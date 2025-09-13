<aside class="sidebar" id="sidebar">
    <div class="logo">
        <div>
            <img src="image/logo_cropped.png" width="100px" alt="Logo">
        </div>
        <h2>ระบบผู้ดูแล</h2>
    </div>

    <nav>
        <ul>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard_admin.php' ? 'active' : ''; ?>">
                <a href="#" onclick="showSection('dashboard'); return false;">
                    <i class="fas fa-tachometer-alt"></i> แดชบอร์ด
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'products_admin.php' ? 'active' : ''; ?>">
                <a href="#" onclick="showSection('products'); return false;">
                    <i class="fas fa-box"></i> จัดการสินค้า
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'orders_admin.php' ? 'active' : ''; ?>">
                <a href="#" onclick="showSection('orders'); return false;">
                    <i class="fas fa-shopping-cart"></i> จัดการคำสั่งซื้อ
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'admins_admin.php' ? 'active' : ''; ?>">
                <a href="#" onclick="showSection('admins'); return false;">
                    <i class="fas fa-users-cog"></i> จัดการผู้ดูแล
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports_admin.php' ? 'active' : ''; ?>">
                <a href="#" onclick="showSection('reports'); return false;">
                    <i class="fas fa-chart-bar"></i> รายงาน
                </a>
            </li>
            <li>
                <a href="#" onclick="handleLogout(); return false;">
                    <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
                </a>
            </li>
        </ul>
    </nav>
</aside>