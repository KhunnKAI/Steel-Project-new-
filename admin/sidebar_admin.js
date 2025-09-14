// Simple sidebar_admin.js - เวอร์ชันง่ายที่ใช้งานได้จริง

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

// Show access denied message
function showAccessDenied(sectionName) {
    alert(`คุณไม่มีสิทธิ์เข้าถึง${sectionName}\nกรุณาติดต่อผู้ดูแลระบบเพื่อขอสิทธิ์การเข้าถึง`);
}

// Confirm and perform logout
function confirmLogout() {
    if (confirm('คุณต้องการออกจากระบบหรือไม่?')) {
        showLoadingOverlay();
        performLogout();
    }
}

function showLoadingOverlay() {
    const overlay = document.createElement('div');
    overlay.id = 'logout-overlay';
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 9999;
        display: flex;
        justify-content: center;
        align-items: center;
    `;
    
    overlay.innerHTML = `
        <div style="background: white; padding: 30px; border-radius: 10px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
            <div style="width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #990000; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 15px;"></div>
            <div style="color: #333; font-weight: 500; font-family: Arial, sans-serif;">กำลังออกจากระบบ...</div>
        </div>
    `;
    
    // Add CSS animation if not exists
    if (!document.querySelector('#spin-animation')) {
        const style = document.createElement('style');
        style.id = 'spin-animation';
        style.textContent = `
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
    }
    
    document.body.appendChild(overlay);
}

function performLogout() {
    // Array of possible logout paths to try
    const logoutPaths = [
        'controllers/logout.php',
        '../controllers/logout.php', 
        'logout.php',
        '../logout.php'
    ];
    
    // Try each logout path
    tryLogoutPath(logoutPaths, 0);
}

function tryLogoutPath(paths, index) {
    if (index >= paths.length) {
        // If all paths fail, just redirect to login
        console.log('All logout paths failed, redirecting to login');
        redirectToLogin();
        return;
    }
    
    const currentPath = paths[index];
    console.log(`Trying logout path: ${currentPath}`);
    
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
        console.log('Logout response:', data);
        if (data && data.success) {
            window.location.href = data.redirect || 'login_admin.html';
        } else {
            throw new Error('Logout response indicates failure');
        }
    })
    .catch(error => {
        console.log(`Logout failed for path ${currentPath}:`, error);
        // Try next path
        setTimeout(() => tryLogoutPath(paths, index + 1), 100);
    });
}

function redirectToLogin() {
    // Clear any stored session data
    try {
        sessionStorage.clear();
        localStorage.clear();
    } catch (e) {
        console.log('Error clearing storage:', e);
    }
    
    // Try different login page paths
    const loginPaths = [
        'login_admin.html',
        '../login_admin.html',
        'admin/login_admin.html',
        'index.php'
    ];
    
    // Check which login page exists and redirect
    for (let path of loginPaths) {
        try {
            window.location.href = path;
            return;
        } catch (e) {
            console.log(`Failed to redirect to ${path}`);
        }
    }
    
    // Fallback - reload current page
    window.location.reload();
}

// Handle mobile menu interactions
document.addEventListener('DOMContentLoaded', function() {
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
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
    
    // Handle window resize
    window.addEventListener('resize', function() {
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

// Make functions available globally
window.toggleSidebar = toggleSidebar;
window.showAccessDenied = showAccessDenied;
window.confirmLogout = confirmLogout;