// ========================
// SIDEBAR ADMIN - MAIN FUNCTIONS
// ========================

// ========================
// FUNCTION: สลับการแสดง/ซ่อน Sidebar
// ========================
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

// ========================
// FUNCTION: แสดงข้อความไม่มีสิทธิ์เข้าถึง
// ========================
function showAccessDenied(sectionName) {
    alert(`คุณไม่มีสิทธิ์เข้าถึง${sectionName}\nกรุณาติดต่อผู้ดูแลระบบเพื่อขอสิทธิ์การเข้าถึง`);
}

// ========================
// FUNCTION: ยืนยันการออกจากระบบ
// ========================
function confirmLogout() {
    if (confirm('คุณต้องการออกจากระบบหรือไม่?')) {
        showLoadingOverlay();
        performLogout();
    }
}

// ========================
// FUNCTION: แสดง Loading Overlay
// ========================
function showLoadingOverlay() {
    const overlay = document.createElement('div');
    overlay.id = 'logout-overlay';
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9999;
        display: flex;
        justify-content: center;
        align-items: center;
    `;

    overlay.innerHTML = `
        <div style="
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        ">
            <div style="
                width: 40px;
                height: 40px;
                border: 4px solid #f3f3f3;
                border-top: 4px solid #990000;
                border-radius: 50%;
                animation: spinLoader 1s linear infinite;
                margin: 0 auto 15px;
            "></div>
            <div style="
                color: #333;
                font-weight: 500;
                font-family: Arial, sans-serif;
            ">กำลังออกจากระบบ...</div>
        </div>
    `;

    // เพิ่ม CSS animation
    if (!document.querySelector('#spin-loader-animation')) {
        const style = document.createElement('style');
        style.id = 'spin-loader-animation';
        style.textContent = `
            @keyframes spinLoader {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
    }

    document.body.appendChild(overlay);
}

// ========================
// FUNCTION: ทำการออกจากระบบ
// ========================
function performLogout() {
    const logoutPaths = [
        'controllers/logout.php',
        '../controllers/logout.php',
        'logout.php',
        '../logout.php'
    ];

    tryLogoutPath(logoutPaths, 0);
}

// ========================
// FUNCTION: ลองเข้าถึง Logout Path
// ========================
function tryLogoutPath(paths, index) {
    if (index >= paths.length) {
        redirectToLogin();
        return;
    }

    const currentPath = paths[index];

    fetch(currentPath, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data && data.success) {
                window.location.href = data.redirect || 'login_admin.html';
            } else {
                throw new Error('Logout failed');
            }
        })
        .catch(error => {
            setTimeout(() => tryLogoutPath(paths, index + 1), 100);
        });
}

// ========================
// FUNCTION: เปลี่ยนเส้นทางไปหน้า Login
// ========================
function redirectToLogin() {
    const loginPaths = [
        'login_admin.html',
        '../login_admin.html',
        'admin/login_admin.html',
        'index.php'
    ];

    for (let path of loginPaths) {
        try {
            window.location.href = path;
            return;
        } catch (e) {
            console.log(`Failed to redirect to ${path}`);
        }
    }

    window.location.reload();
}

// ========================
// EVENT LISTENERS
// ========================

// ปิด Sidebar เมื่อคลิกนอก Sidebar บน Mobile
document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('click', function (e) {
        const sidebar = document.getElementById('sidebar');
        const toggle = document.querySelector('.navbar-toggle');
        const main = document.querySelector('.main-content');

        if (!sidebar) return;

        const clickedOutside = !sidebar.contains(e.target) &&
            (!toggle || !toggle.contains(e.target));

        if (sidebar.classList.contains('show') && clickedOutside && window.innerWidth <= 768) {
            sidebar.classList.remove('show');
            if (main) {
                main.classList.remove('overlay');
            }
        }
    });

    // จัดการการปรับขนาดหน้าต่าง
    window.addEventListener('resize', function () {
        const sidebar = document.getElementById('sidebar');
        const main = document.querySelector('.main-content');

        if (window.innerWidth > 768 && sidebar && sidebar.classList.contains('show')) {
            sidebar.classList.remove('show');
            if (main) {
                main.classList.remove('overlay');
            }
        }
    });
});

// ========================
// GLOBAL FUNCTION EXPORTS
// ========================
window.toggleSidebar = toggleSidebar;
window.showAccessDenied = showAccessDenied;
window.confirmLogout = confirmLogout;