<?php
// Enhanced sidebar_admin.php with permissions integration
$current_admin = getCurrentAdmin();

// Default permissions based on role
$role_permissions = [
    'manager' => ['dashboard', 'products', 'orders', 'admins', 'reports'],
    'sales' => ['dashboard', 'products', 'orders'],
    'warehouse' => ['dashboard', 'products', 'orders'],
    'shipping' => ['dashboard', 'orders'],
    'accounting' => ['dashboard', 'orders', 'reports'],
    'super' => ['dashboard', 'products', 'orders', 'admins', 'reports']
];

// Get user's permissions
$user_permissions = [];
if ($current_admin) {
    if (!empty($current_admin['permissions'])) {
        $user_permissions = is_string($current_admin['permissions']) 
            ? json_decode($current_admin['permissions'], true) ?: []
            : $current_admin['permissions'];
    } else {
        $user_permissions = $role_permissions[$current_admin['position']] ?? ['dashboard'];
    }
}

// Helper function to check permissions
function hasPermission($permission, $user_permissions) {
    return in_array($permission, $user_permissions);
}

// Menu items configuration
$menu_items = [
    [
        'permission' => 'dashboard',
        'section' => 'dashboard',
        'file' => 'dashboard_admin.php',
        'icon' => 'fa-tachometer-alt',
        'text' => 'แดชบอร์ด',
        'always_show' => true // Dashboard should always be accessible
    ],
    [
        'permission' => 'products',
        'section' => 'products', 
        'file' => 'products_admin.php',
        'icon' => 'fa-box',
        'text' => 'จัดการสินค้า'
    ],
    [
        'permission' => 'orders',
        'section' => 'orders',
        'file' => 'orders_admin.php', 
        'icon' => 'fa-shopping-cart',
        'text' => 'จัดการคำสั่งซื้อ'
    ],
    [
        'permission' => 'admins',
        'section' => 'admins',
        'file' => 'admins_admin.php',
        'icon' => 'fa-users-cog', 
        'text' => 'จัดการผู้ดูแล'
    ],
    [
        'permission' => 'reports',
        'section' => 'reports',
        'file' => 'reports_admin.php',
        'icon' => 'fa-chart-bar',
        'text' => 'รายงาน'
    ]
];

$current_file = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar" id="sidebar">
    <div class="logo">
        <div>
            <img src="image/logo_cropped.png" width="100px" alt="Logo">
        </div>
        <h2>ระบบผู้ดูแล</h2>
        <?php if ($current_admin): ?>
            <div style="text-align: center; margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.2);">
                <div style="color: rgba(255,255,255,0.8); font-size: 12px;">
                    <?php 
                    $role_names = [
                        'manager' => 'ผู้จัดการ',
                        'sales' => 'พนักงานขาย', 
                        'warehouse' => 'พนักงานคลัง',
                        'shipping' => 'พนักงานขนส่ง',
                        'accounting' => 'พนักงานบัญชี',
                        'super' => 'ผู้ดูแลระบบ'
                    ];
                    echo $role_names[$current_admin['position']] ?? $current_admin['position'];
                    ?>
                </div>
                <div style="color: white; font-size: 14px; font-weight: 500; margin-top: 2px;">
                    <?php echo htmlspecialchars($current_admin['fullname']); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

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
            
            <li style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 20px;">
                <a href="javascript:void(0);" onclick="confirmLogout();">
                    <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
                </a>
            </li>
        </ul>
    </nav>
</aside>

<style>
/* Additional styles for permission-based sidebar */
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
    border-bottom: 1px solid rgba(255,255,255,0.2);
}

.permission-indicator {
    font-size: 10px;
    background: rgba(255,255,255,0.2);
    padding: 2px 6px;
    border-radius: 10px;
    margin-left: auto;
}
</style>

<script>
// Show access denied message
function showAccessDenied(sectionName) {
    alert(`คุณไม่มีสิทธิ์เข้าถึง${sectionName}\nกรุณาติดต่อผู้ดูแลระบบเพื่อขอสิทธิ์การเข้าถึง`);
}

// Confirm logout
function confirmLogout() {
    if (confirm('คุณต้องการออกจากระบบหรือไม่?')) {
        // Show loading
        var loadingDiv = document.createElement('div');
        loadingDiv.innerHTML = `
            <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
                        background: rgba(0,0,0,0.5); z-index: 9999; 
                        display: flex; justify-content: center; align-items: center;">
                <div style="background: white; padding: 20px; border-radius: 10px; text-align: center;">
                    <div style="width: 40px; height: 40px; border: 4px solid #f3f3f3; 
                                border-top: 4px solid #990000; border-radius: 50%; 
                                animation: spin 1s linear infinite; margin: 0 auto 15px;"></div>
                    <div>กำลังออกจากระบบ...</div>
                </div>
            </div>
        `;
        
        // Add spinner CSS
        var style = document.createElement('style');
        style.textContent = `
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
        document.body.appendChild(loadingDiv);
        
        // Perform logout
        performLogout();
    }
}

function performLogout() {
    // Try different logout paths
    var logoutPaths = [
        'controllers/logout.php',
        '../controllers/logout.php',
        'logout.php',
        '../logout.php'
    ];
    
    tryLogout(logoutPaths, 0);
}

function tryLogout(paths, index) {
    if (index >= paths.length) {
        // If all paths fail, clear session and redirect
        sessionStorage.clear();
        localStorage.clear();
        window.location.href = 'login_admin.html';
        return;
    }
    
    fetch(paths[index], {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest' 
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = data.redirect || 'login_admin.html';
        } else {
            throw new Error('Logout failed');
        }
    })
    .catch(error => {
        console.log('Logout attempt failed for ' + paths[index] + ':', error);
        tryLogout(paths, index + 1);
    });
}

// Mobile sidebar toggle
function toggleSidebar() {
    const sidebar = document.getElementById("sidebar");
    const main = document.querySelector(".main-content");

    if (sidebar) {
        sidebar.classList.toggle("show");
        if (main) {
            main.classList.toggle("overlay");
        }
    }
}

// Close sidebar when clicking outside on mobile
document.addEventListener("click", function(e) {
    const sidebar = document.getElementById("sidebar");
    const toggle = document.querySelector(".navbar-toggle");
    const main = document.querySelector(".main-content");

    if (!sidebar || !toggle) return;

    const clickedOutside = !sidebar.contains(e.target) && !toggle.contains(e.target);

    if (sidebar.classList.contains("show") && clickedOutside && window.innerWidth <= 768) {
        sidebar.classList.remove("show");
        if (main) {
            main.classList.remove("overlay");
        }
    }
});

// Handle window resize
window.addEventListener('resize', function() {
    const sidebar = document.getElementById("sidebar");
    const main = document.querySelector(".main-content");
    
    if (window.innerWidth > 768 && sidebar && sidebar.classList.contains("show")) {
        sidebar.classList.remove("show");
        if (main) {
            main.classList.remove("overlay");
        }
    }
});
</script>