<?php
// ========================
// SIDEBAR ADMIN - INCLUDES
// ========================
// ต้อง Include config.php ก่อนไฟล์นี้

$current_admin = getCurrentAdmin();

// ========================
// ROLE & PERMISSIONS CONFIG
// ========================
$role_permissions = [
    'manager' => ['dashboard', 'products', 'orders', 'admins', 'reports'],
    'sales' => ['dashboard', 'products', 'orders'],
    'warehouse' => ['dashboard', 'products', 'orders'],
    'shipping' => ['dashboard', 'orders'],
    'accounting' => ['dashboard', 'orders', 'reports'],
    'super' => ['dashboard', 'products', 'orders', 'admins', 'reports']
];

// ========================
// FUNCTION: ดึงสิทธิ์ผู้ใช้
// ========================
function getUserPermissions($admin, $rolePermissions) {
    $userPermissions = [];

    if ($admin) {
        if (!empty($admin['permissions'])) {
            $userPermissions = is_string($admin['permissions'])
                ? json_decode($admin['permissions'], true) ?: []
                : $admin['permissions'];
        } else {
            $userPermissions = $rolePermissions[$admin['position']] ?? ['dashboard'];
        }
    }

    return $userPermissions;
}

// ========================
// FUNCTION: ตรวจสอบสิทธิ์การเข้าถึง
// ========================
function hasPermission($permission, $userPermissions) {
    return in_array($permission, $userPermissions);
}

// ========================
// FUNCTION: รับชื่อบทบาท (Role)
// ========================
function getRoleDisplayName($role) {
    $roleNames = [
        'manager' => 'ผู้จัดการ',
        'sales' => 'พนักงานขาย',
        'warehouse' => 'พนักงานคลัง',
        'shipping' => 'พนักงานขนส่ง',
        'accounting' => 'พนักงานบัญชี',
        'super' => 'ผู้ดูแลระบบ'
    ];

    return $roleNames[$role] ?? $role;
}

// ได้รับสิทธิ์ผู้ใช้
$user_permissions = getUserPermissions($current_admin, $role_permissions);

// ========================
// MENU ITEMS CONFIGURATION
// ========================
$menu_items = [
    [
        'permission' => 'dashboard',
        'file' => 'dashboard_admin.php',
        'icon' => 'fa-tachometer-alt',
        'text' => 'แดชบอร์ด',
        'always_show' => true
    ],
    [
        'permission' => 'products',
        'file' => 'products_admin.php',
        'icon' => 'fa-box',
        'text' => 'จัดการสินค้า'
    ],
    [
        'permission' => 'orders',
        'file' => 'orders_admin.php',
        'icon' => 'fa-shopping-cart',
        'text' => 'จัดการคำสั่งซื้อ'
    ],
    [
        'permission' => 'admins',
        'file' => 'admins_admin.php',
        'icon' => 'fa-users-cog',
        'text' => 'จัดการผู้ดูแล'
    ],
    [
        'permission' => 'reports',
        'file' => 'reports_admin.php',
        'icon' => 'fa-chart-bar',
        'text' => 'รายงาน'
    ]
];

$current_file = basename($_SERVER['PHP_SELF']);
?>

<!-- ========================
     SIDEBAR HTML
     ======================== -->
<aside class="sidebar" id="sidebar">
    <!-- Logo Section -->
    <div class="logo">
        <div>
            <img src="image/logo_cropped.png" width="100px" alt="Logo">
        </div>
        <h2>ระบบผู้ดูแล</h2>

        <?php if ($current_admin): ?>
            <div style="text-align: center; margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.2);">
                <div style="color: rgba(255,255,255,0.8); font-size: 12px;">
                    <?php echo getRoleDisplayName($current_admin['position']); ?>
                </div>
                <div style="color: white; font-size: 14px; font-weight: 500; margin-top: 2px;">
                    <?php echo htmlspecialchars($current_admin['fullname']); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Navigation Menu -->
    <nav>
        <ul>
            <?php foreach ($menu_items as $item): ?>
                <?php
                $hasAccess = isset($item['always_show']) && $item['always_show']
                    ? true
                    : hasPermission($item['permission'], $user_permissions);

                $isActive = $current_file == $item['file'];
                ?>

                <li class="<?php echo $isActive ? 'active' : ''; ?> <?php echo !$hasAccess ? 'restricted' : ''; ?>"
                    <?php if (!$hasAccess): ?>
                    title="คุณไม่มีสิทธิ์เข้าถึงส่วนนี้"
                    style="opacity: 0.4; cursor: not-allowed;"
                    <?php endif; ?>>

                    <?php if ($hasAccess): ?>
                        <a href="<?php echo $item['file']; ?>">
                            <i class="fas <?php echo $item['icon']; ?>"></i>
                            <?php echo $item['text']; ?>
                        </a>
                    <?php else: ?>
                        <a href="javascript:void(0);" onclick="showAccessDenied('<?php echo $item['text']; ?>'); return false;">
                            <i class="fas <?php echo $item['icon']; ?>"></i>
                            <?php echo $item['text']; ?>
                            <i class="fas fa-lock" style="margin-left: auto; font-size: 12px; opacity: 0.6;"></i>
                        </a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>

            <!-- Logout Button -->
            <li style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 20px;">
                <a href="javascript:void(0);" onclick="confirmLogout();">
                    <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
                </a>
            </li>
        </ul>
    </nav>
</aside>

<!-- ========================
     INLINE STYLES
     ======================== -->
<style>
    .sidebar nav li.restricted {
        position: relative;
    }

    .sidebar nav li.restricted a {
        cursor: not-allowed;
        opacity: 0.6;
    }

    .sidebar nav li.restricted a:hover {
        background: transparent;
    }

    .sidebar .logo {
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    }
</style>