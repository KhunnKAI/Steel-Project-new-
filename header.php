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
        <div class="icon" id="cartIcon">
            🛒
            <div class="cart-badge" id="cartBadge">3</div>
        </div>

        <!-- Notification Icon - แสดงเฉพาะเมื่อล็อกอินแล้ว -->
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
                <a href="myorders.php">คำสั่งซื้อของฉัน</a>
                <a href="wishlist.php">รายการโปรด</a>
                <a href="settings.php">การตั้งค่า</a>
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
// ส่งข้อมูลจาก PHP ไปยัง JavaScript
window.phpIsLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
window.phpUserName = '<?php echo $isLoggedIn ? addslashes($userName) : ''; ?>';

// Debug log สำหรับตรวจสอบ
console.log('PHP Data loaded:');
console.log('- Login Status:', window.phpIsLoggedIn);
console.log('- User Name:', window.phpUserName);
console.log('- Session user_id:', '<?php echo $_SESSION['user_id'] ?? 'not set'; ?>');
console.log('- Cookie user_id:', '<?php echo $_COOKIE['user_id'] ?? 'not set'; ?>');
</script>

<script>
// Header Script - แก้ไขปัญหาตะกร้าขึ้น 0 หลังรีเฟรช
document.addEventListener('DOMContentLoaded', function() {
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
    let cartCount = loadCartCountFromStorage(); // โหลดจาก localStorage แทน hardcode
    
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
    console.log('Cart Count from localStorage:', cartCount);
    console.log('Login Status:', isLoggedIn);
    console.log('User Name:', userName);

    // Utility function to show toast notifications
    function showToast(message, type = 'success') {
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
            setTimeout(() => { this.style.animation = ''; }, 500);

            if (isLoggedIn) {
                // อัพเดท cartCount ล่าสุดก่อนแสดง toast
                cartCount = loadCartCountFromStorage();
                showToast(`🛒 ตะกร้าสินค้า: มีสินค้า ${cartCount} รายการ`);
                window.location.href = 'cart.php';
            } else {
                showToast('กรุณาเข้าสู่ระบบก่อนดูตะกร้าสินค้า', 'warning');
                setTimeout(() => { window.location.href = 'login.php'; }, 1500);
            }
        });
    }

    // Update cart badge function - อัพเดทให้โหลดจาก localStorage
    function updateCartBadge() {
        // โหลดจำนวนล่าสุดจาก localStorage
        const currentCartCount = loadCartCountFromStorage();
        cartCount = currentCartCount;

        if (cartBadge) {
            cartBadge.textContent = cartCount;
            cartBadge.style.display = cartCount > 0 ? 'flex' : 'none';
        }

        // อัพเดท global variable ถ้ามี
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

    // Handle logout - ใช้ AJAX แทนการ submit form
    window.handleLogout = function() {
        if (confirm('คุณต้องการออกจากระบบหรือไม่?')) {
            // แสดง loading toast
            showToast('กำลังออกจากระบบ...', 'info');

            // ส่ง AJAX request ไปยัง logout.php
            fetch('logout.php', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // ล้างข้อมูลตะกร้าเมื่อออกจากระบบ
                        localStorage.removeItem('shopping_cart');
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

            // ตรวจสอบว่าเป็น onclick function
            if (this.getAttribute('onclick')) {
                return; // ให้ onclick ทำงาน
            }

            // ตรวจสอบว่าเป็นลิงก์ไปยังไฟล์ PHP หรือ HTML
            if (href && !href.startsWith('#') && (href.includes('.php') || href.includes('.html'))) {
                // แสดง loading toast
                const linkText = this.textContent.trim();
                showToast(`กำลังไปที่ ${linkText}...`, 'info');
                return true; // ให้ลิงก์ทำงานปกติ
            }

            // สำหรับลิงก์อื่น ๆ ให้แสดง toast
            e.preventDefault();
            const linkText = this.textContent.trim();
            showToast(`เปิดหน้า: ${linkText}`, 'info');

            // Close dropdowns
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

    // เพิ่มการตรวจสอบสถานะการแจ้งเตือน (สำหรับผู้ใช้ที่ล็อกอินแล้ว)
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

    // Listen for cart updates from cartManager
    window.addEventListener('cartUpdated', function(e) {
        const { totalItems } = e.detail;
        cartCount = totalItems;
        updateCartBadge();
        console.log('Cart updated via event:', totalItems);
    });

    // Listen for localStorage changes (สำหรับ multiple tabs)
    window.addEventListener('storage', function(e) {
        if (e.key === 'shopping_cart') {
            console.log('localStorage changed, updating cart badge...');
            updateCartBadge();
        }
    });

    // Initialize
    updateCartBadge();
    checkNotifications();

    // Welcome message สำหรับผู้ใช้ที่ล็อกอิน
    if (isLoggedIn && userName) {
        setTimeout(() => {
            showToast(`ยินดีต้อนรับ ${userName}!`);
        }, 1000);
    } else {
        setTimeout(() => {
            showToast('ยินดีต้อนรับสู่ร้านค้าออนไลน์! กรุณาเข้าสู่ระบบเพื่อใช้งานเต็มรูปแบบ', 'info');
        }, 2000);
    }

    // เพิ่ม CSS สำหรับ shake animation
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
</script>