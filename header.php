<?php
// Start session at the beginning
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// ฟังก์ชันเช็ค remember me cookie
function checkRememberMe() {
    // Check if user is logged in via cookies but not in session
    if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_id']) && !empty($_COOKIE['user_id'])) {
        // Restore session from cookies
        $_SESSION['user_id'] = $_COOKIE['user_id'];
        $_SESSION['name'] = $_COOKIE['name'] ?? '';
        $_SESSION['email'] = $_COOKIE['email'] ?? '';
        $_SESSION['logged_in'] = true;
        
        // TODO: ในการใช้งานจริงควรตรวจสอบ token กับฐานข้อมูล
        // เพื่อความปลอดภัย
    }
}

// เรียกใช้ฟังก์ชันเช็ค remember me
checkRememberMe();

// ตรวจสอบสถานะการล็อกอิน - เช็คทั้ง session และ cookie
$isLoggedIn = (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) || 
              (isset($_COOKIE['user_id']) && !empty($_COOKIE['user_id']));

// ดึงชื่อผู้ใช้จาก session หรือ cookie
$userName = '';
if ($isLoggedIn) {
    $userName = $_SESSION['name'] ?? $_COOKIE['name'] ?? '';
}

// Debug - เพิ่มการตรวจสอบข้อมูล
error_log("Header Debug - isLoggedIn: " . ($isLoggedIn ? 'true' : 'false'));
error_log("Header Debug - userName: " . $userName);
error_log("Header Debug - SESSION user_id: " . ($_SESSION['user_id'] ?? 'not set'));
error_log("Header Debug - COOKIE user_id: " . ($_COOKIE['user_id'] ?? 'not set'));
?>

<div class="header">
    <div class="logo">
        <img src="image/logo.png" width="100px">
    </div>
    <div class="header-nav">
        <a href="home.php">หน้าหลัก</a>
        <a href="allproduct.php">สินค้า</a>
        <a href="aboutme.php">เกี่ยวกับเรา</a>
        <a href="contactus.php">ติดต่อเรา</a>
    </div>

    <div class="header-icons">
        <!-- Cart Icon -->
        <?php if ($isLoggedIn): ?>
        <div class="icon" id="cartIcon">
            🛒
            <div class="cart-badge" id="cartBadge">0</div>
        </div>
        <?php endif; ?>

        <!---
        Notification Icon - แสดงเฉพาะเมื่อล็อกอินแล้ว
        <?php if ($isLoggedIn): ?>
        <div class="icon dropdown" id="notificationIcon">
            🔔
            <div class="dropdown-content" id="notificationDropdown">
                <a href="bill.php">สถานะคำสั่งซื้อ</a>
                <a href="notifications.php">การแจ้งเตือนทั้งหมด</a>
                <a href="promotions.php">โปรโมชั่น</a>
            </div>
        </div>
        <?php endif; ?>
        -->

        <!-- Profile Icon -->
        <div class="icon dropdown" id="profileIcon">
            👤
            <div class="dropdown-content" id="profileDropdown">
                <?php if ($isLoggedIn): ?>
                <!-- เมนูสำหรับผู้ใช้ที่ล็อกอินแล้ว -->
                <div class="user-info">
                    <span>สวัสดี, <?php echo htmlspecialchars($userName); ?>!</span>
                </div>
                <a href="profile.php">โปรไฟล์</a>
                <a href="history.php">คำสั่งซื้อของฉัน</a>
                <hr class="dropdown-divider">
                <a href="logout.php">ออกจากระบบ</a>
                <?php else: ?>
                <!-- เมนูสำหรับผู้ใช้ที่ยังไม่ได้ล็อกอิน -->
                <a href="register.php">ลงทะเบียน</a>
                <a href="login.php">เข้าสู่ระบบ</a>
                <hr class="dropdown-divider">
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// ป้องกันการรันซ้ำของ Header Script
if (typeof window.headerScriptInitialized === 'undefined') {
    window.headerScriptInitialized = true;

    // ส่งข้อมูลจาก PHP ไปยัง JavaScript - แก้ไขการ escape string
    window.phpIsLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
    window.phpUserName = <?php echo json_encode($userName, JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

    // Debug log สำหรับตรวจสอบ
    console.log('=== PHP Data loaded ===');
    console.log('- Login Status:', window.phpIsLoggedIn);
    console.log('- User Name:', window.phpUserName);
    console.log('- Session user_id:', '<?php echo $_SESSION['user_id'] ?? 'not set'; ?>');
    console.log('- Cookie user_id:', '<?php echo $_COOKIE['user_id'] ?? 'not set'; ?>');
    console.log('- User Name Length:', window.phpUserName.length);
    console.log('========================');

    // Header Script - แก้ไขปัญหาตะกร้าขึ้น 0 หลังรีเฟรช
    document.addEventListener('DOMContentLoaded', function() {
        // ป้องกันการรันซ้ำ
        if (window.headerDOMLoaded) {
            console.log('Header DOM already loaded, skipping...');
            return;
        }
        window.headerDOMLoaded = true;

        console.log('=== Header DOM Content Loaded ===');

        // ฟังก์ชันโหลดจำนวนสินค้าจาก localStorage
        function loadCartCountFromStorage() {
            try {
                const saved = localStorage.getItem('shopping_cart');
                if (saved) {
                    const cartData = JSON.parse(saved);
                    return Object.values(cartData).reduce((total, item) => total + item.quantity, 0);
                }
            } catch (error) {
                console.error('Error loading cart count:', error);
            }
            return 0;
        }

        // Global variables
        let cartCount = loadCartCountFromStorage();

        const cartIcon = document.getElementById('cartIcon');
        const cartBadge = document.getElementById('cartBadge');
        const notificationIcon = document.getElementById('notificationIcon');
        const notificationDropdown = document.getElementById('notificationDropdown');
        const profileIcon = document.getElementById('profileIcon');
        const profileDropdown = document.getElementById('profileDropdown');

        // ตรวจสอบสถานะการล็อกอินจาก PHP
        const isLoggedIn = typeof window.phpIsLoggedIn !== 'undefined' ? window.phpIsLoggedIn : false;
        const userName = typeof window.phpUserName !== 'undefined' ? window.phpUserName : '';

        // Debug log สำหรับตรวจสอบ
        console.log('=== JavaScript Variables ===');
        console.log('Cart Count from localStorage:', cartCount);
        console.log('Login Status:', isLoggedIn);
        console.log('User Name:', userName);
        console.log('User Name Type:', typeof userName);
        console.log('User Name Trimmed:', userName.trim());
        console.log('============================');

        // Utility function to show toast notifications
        function showToast(message, type = 'success') {
            console.log('Showing toast:', message, 'Type:', type);

            const toast = document.createElement('div');
            const bgColor = type === 'success' ? 'linear-gradient(135deg, #4caf50, #45a049)' :
                type === 'warning' ? 'linear-gradient(135deg, #ff9800, #f57c00)' :
                type === 'info' ? 'linear-gradient(135deg, #2196f3, #1976d2)' :
                'linear-gradient(135deg, #f44336, #d32f2f)';

            toast.className = 'toast';
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${bgColor};
                color: white;
                padding: 15px 20px;
                border-radius: 12px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                z-index: 10000;
                animation: slideInRight 0.5s ease;
                max-width: 300px;
                font-size: 14px;
            `;
            toast.textContent = message;

            // เพิ่ม CSS animation
            if (!document.querySelector('#toast-styles')) {
                const style = document.createElement('style');
                style.id = 'toast-styles';
                style.textContent = `
                    @keyframes slideInRight {
                        from { transform: translateX(100%); opacity: 0; }
                        to { transform: translateX(0); opacity: 1; }
                    }
                `;
                document.head.appendChild(style);
            }

            document.body.appendChild(toast);

            setTimeout(() => {
                toast.style.animation = 'slideInRight 0.5s ease reverse';
                setTimeout(() => toast.remove(), 500);
            }, 3000);
        }

        // Cart functionality
        if (cartIcon) {
            cartIcon.addEventListener('click', function(e) {
                e.preventDefault();
                this.style.animation = 'shake 0.5s';
                setTimeout(() => {
                    this.style.animation = '';
                }, 500);

                if (isLoggedIn) {
                    cartCount = loadCartCountFromStorage();
                    showToast(`🛒 ตะกร้าสินค้า: มีสินค้า ${cartCount} รายการ`);
                    window.location.href = 'cart.php';
                } else {
                    showToast('กรุณาเข้าสู่ระบบก่อนดูตะกร้าสินค้า', 'warning');
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 1500);
                }
            });
        }

        // Update cart badge function
        function updateCartBadge() {
            const currentCartCount = loadCartCountFromStorage();
            cartCount = currentCartCount;

            if (cartBadge) {
                cartBadge.textContent = cartCount;
                cartBadge.style.display = cartCount > 0 ? 'flex' : 'none';
            }

            if (typeof window.cartCount !== 'undefined') {
                window.cartCount = cartCount;
            }

            console.log('Updated cart badge:', cartCount);
        }


        // ทำให้ function เป็น global
        window.updateCartBadge = updateCartBadge;
        window.showToast = showToast;

        // Notification dropdown (เฉพาะเมื่อล็อกอินแล้ว)
        if (notificationIcon && notificationDropdown) {
            notificationIcon.addEventListener('click', function(e) {
                e.stopPropagation();
                notificationDropdown.classList.toggle('show');
                if (profileDropdown) {
                    profileDropdown.classList.remove('show');
                }

                this.style.animation = 'shake 0.5s';
                setTimeout(() => {
                    this.style.animation = '';
                }, 500);
            });
        }

        // Profile dropdown
        if (profileIcon && profileDropdown) {
            profileIcon.addEventListener('click', function(e) {
                e.stopPropagation();
                profileDropdown.classList.toggle('show');
                if (notificationDropdown) {
                    notificationDropdown.classList.remove('show');
                }
            });
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (notificationIcon && notificationDropdown && !notificationIcon.contains(e.target)) {
                notificationDropdown.classList.remove('show');
            }
            if (profileIcon && profileDropdown && !profileIcon.contains(e.target)) {
                profileDropdown.classList.remove('show');
            }
        });

        // Handle logout
        window.handleLogout = function() {
            if (confirm('คุณต้องการออกจากระบบหรือไม่?')) {
                showToast('กำลังออกจากระบบ...', 'info');

                fetch('logout.php', {
                        method: 'POST'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            localStorage.removeItem('shopping_cart');
                            // Clear welcome message flags
                            sessionStorage.removeItem('welcome_message_shown');
                            sessionStorage.removeItem('guest_welcome_shown');
                            window.location.href = data.redirect;
                        }
                    })
                    .catch(error => {
                        console.error('Logout error:', error);
                        window.location.href = 'login.php';
                    });
            }
        };

        // Handle dropdown menu actions
        document.querySelectorAll('.dropdown-content a').forEach(item => {
            item.addEventListener('click', function(e) {
                const href = this.getAttribute('href');

                if (this.getAttribute('onclick')) {
                    return;
                }

                if (href && !href.startsWith('#') && (href.includes('.php') || href.includes(
                        '.html'))) {
                    const linkText = this.textContent.trim();
                    showToast(`กำลังไปที่ ${linkText}...`, 'info');
                    return true;
                }

                e.preventDefault();
                const linkText = this.textContent.trim();
                showToast(`เปิดหน้า: ${linkText}`, 'info');

                if (notificationDropdown) {
                    notificationDropdown.classList.remove('show');
                }
                if (profileDropdown) {
                    profileDropdown.classList.remove('show');
                }
            });
        });

        // Add hover effects to icons
        document.querySelectorAll('.icon').forEach(icon => {
            icon.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-3px) scale(1.05)';
            });

            icon.addEventListener('mouseleave', function() {
                this.style.transform = '';
            });
        });

        // เพิ่มการตรวจสอบสถานะการแจ้งเตือน
        function checkNotifications() {
            if (isLoggedIn && notificationIcon) {
                if (Math.random() < 0.3) {
                    if (!notificationIcon.querySelector('.notification-dot')) {
                        const dot = document.createElement('div');
                        dot.className = 'notification-dot';
                        notificationIcon.appendChild(dot);
                    }
                }
            }
        }

        // Listen for cart updates
        window.addEventListener('cartUpdated', function(e) {
            const {
                totalItems
            } = e.detail;
            cartCount = totalItems;
            updateCartBadge();
            console.log('Cart updated via event:', totalItems);
        });

        // Listen for localStorage changes
        window.addEventListener('storage', function(e) {
            if (e.key === 'shopping_cart') {
                console.log('localStorage changed, updating cart badge...');
                updateCartBadge();
            }
        });

        // Initialize
        updateCartBadge();
        checkNotifications();

        // Welcome message - แก้ไขและเพิ่ม debug
        console.log('=== Welcome Message Check ===');
        console.log('isLoggedIn:', isLoggedIn);
        console.log('userName:', userName);
        console.log('userName length:', userName ? userName.length : 0);
        console.log('userName trimmed:', userName ? userName.trim() : '');

        if (isLoggedIn && userName && userName.trim() !== '') {
            console.log('User is logged in and has name, checking welcome message...');

            // ใช้ key ที่แตกต่างกันสำหรับแต่ละผู้ใช้
            const welcomeKey = `welcome_message_shown_${userName.trim()}`;
            const welcomeShown = sessionStorage.getItem(welcomeKey);

            console.log('Welcome key:', welcomeKey);
            console.log('Welcome shown:', welcomeShown);

            if (!welcomeShown) {
                console.log('Showing welcome message for:', userName.trim());
                setTimeout(() => {
                    showToast(`ยินดีต้อนรับ ${userName.trim()}!`, 'success');
                    sessionStorage.setItem(welcomeKey, 'true');
                    console.log('Welcome message shown and flag set');
                }, 1000);
            } else {
                console.log('Welcome message already shown for this user');
            }
        } else if (!isLoggedIn) {
            console.log('User not logged in, checking guest welcome...');
            const guestWelcomeShown = sessionStorage.getItem('guest_welcome_shown');

            if (!guestWelcomeShown) {
                console.log('Showing guest welcome message');
                setTimeout(() => {
                    showToast('ยินดีต้อนรับสู่ร้านค้าออนไลน์! กรุณาเข้าสู่ระบบเพื่อใช้งานเต็มรูปแบบ',
                        'info');
                    sessionStorage.setItem('guest_welcome_shown', 'true');
                }, 2000);
            }
        } else {
            console.log('User logged in but no name available');
        }

        console.log('==============================');

        // เพิ่ม CSS สำหรับ animations
        if (!document.querySelector('#header-animations')) {
            const style = document.createElement('style');
            style.id = 'header-animations';
            style.textContent = `
                @keyframes shake {
                    0%, 100% { transform: translateX(0); }
                    25% { transform: translateX(-5px) rotate(-5deg); }
                    75% { transform: translateX(5px) rotate(5deg); }
                }
            `;
            document.head.appendChild(style);
        }

        console.log('Header script initialized with cart count:', cartCount);
    });
}
</script>