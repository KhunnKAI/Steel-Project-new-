// Toggle sidebar (สำหรับมือถือ)
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

// เปิด section ต่างๆ (เรียกหน้าอื่น)
function showSection(section) {
    // ปิด sidebar บนมือถือเมื่อคลิกเมนู
    if (window.innerWidth <= 768) {
        const sidebar = document.getElementById("sidebar");
        const main = document.querySelector(".main-content");
        if (sidebar && sidebar.classList.contains("show")) {
            sidebar.classList.remove("show");
            if (main) {
                main.classList.remove("overlay");
            }
        }
    }

    // นำทางไปหน้าต่างๆ
    if (section === 'products') {
        window.location.href = 'products_admin.php';
    } else if (section === 'orders') {
        window.location.href = 'orders_admin.php';
    } else if (section === 'admins') {
        window.location.href = 'admins_admin.php';
    } else if (section === 'reports') {
        window.location.href = 'reports_admin.php';
    } else if (section === 'dashboard') {
        window.location.href = 'dashboard_admin.php';
    }
}

// Handle logout
async function handleLogout() {
    if (confirm('คุณต้องการออกจากระบบหรือไม่?')) {
        try {
            const response = await fetch('controllers/logout.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest' 
                }
            });
            
            const data = await response.json();
            if (data.success) {
                window.location.href = data.redirect;
            } else {
                // ถ้า response ไม่ success ให้ redirect ไปหน้า login
                window.location.href = 'login.php';
            }
        } catch (error) {
            console.error('Logout error:', error);
            // ถ้าเกิด error ให้ redirect ไปหน้า login
            window.location.href = 'login.php';
        }
    }
}

// ปิด sidebar เมื่อคลิกนอก (มือถือ)
document.addEventListener("click", function (e) {
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

// ปิด sidebar เมื่อเปลี่ยนขนาดหน้าจอ
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

// ให้ฟังก์ชันเหล่านี้เข้าถึงได้ globally
window.toggleSidebar = toggleSidebar;
window.showSection = showSection;
window.handleLogout = handleLogout;