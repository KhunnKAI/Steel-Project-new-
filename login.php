<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ช้างเหล็กไทย - เข้าสู่ระบบ</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3a5f 0%, #2c5477 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            display: flex;
            align-items: center;
            gap: 80px;
            max-width: 1000px;
            width: 100%;
        }

        .left-section {
            flex: 1;
            text-align: center;
            color: white;
        }

        .logo {
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 50%;
            margin: 0 auto 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }

        .title {
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .subtitle {
            font-size: 18px;
            opacity: 0.9;
            line-height: 1.4;
        }

        .login-form {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 16px 48px rgba(0,0,0,0.2);
            width: 350px;
        }

        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .user-icon {
            width: 60px;
            height: 60px;
            background: #e9ecef;
            border-radius: 50%;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #6c757d;
        }

        .form-title {
            font-size: 20px;
            color: #333;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
            background: #f8f9fa;
        }

        .form-input:focus {
            outline: none;
            border-color: #2c5477;
            background: white;
        }

        .login-btn {
            width: 100%;
            padding: 12px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
            position: relative;
        }

        .login-btn:hover {
            background: #c82333;
        }

        .login-btn:active {
            transform: translateY(1px);
        }

        .login-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
        }

        .loading-spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid #ffffff;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 8px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .forgot-password {
            text-align: center;
            margin-top: 15px;
        }

        .forgot-link {
            color: #2c5477;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
            margin-right: 15px;
        }

        .forgot-link:hover {
            color: #dc3545;
            text-decoration: underline;
        }

        .register {
            text-align: center;
            margin-top: 15px;
        }

        .register-link {
            color: #2c5477;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .register-link:hover {
            color: #a3a3a3;
            text-decoration: underline;
        }

        /* Alert Messages */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
        }

        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .alert-info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            padding: 20px 30px;
            border-bottom: 1px solid #e9ecef;
            text-align: center;
        }

        .modal-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin: 0;
        }

        .modal-body {
            padding: 30px;
        }

        .modal-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            margin-bottom: 20px;
            transition: border-color 0.3s ease;
            background: #f8f9fa;
        }

        .modal-input:focus {
            outline: none;
            border-color: #2c5477;
            background: white;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
        }

        .modal-btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .modal-btn-primary {
            background: #dc3545;
            color: white;
        }

        .modal-btn-primary:hover {
            background: #c82333;
        }

        .modal-btn-secondary {
            background: #6c757d;
            color: white;
        }

        .modal-btn-secondary:hover {
            background: #5a6268;
        }

        .modal-btn:active {
            transform: translateY(1px);
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                gap: 40px;
            }
            
            .title {
                font-size: 36px;
            }
            
            .login-form {
                width: 100%;
                max-width: 350px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left-section">
            <div class="logo">
                <img src="image/logo.png" width="300px" alt="Logo">
            </div>
            <h1 class="title">ช้างเหล็กไทย</h1>
            <p class="subtitle">เหล็กคุณภาพ แกร่งทุกงาน มั่นใจช้างเหล็กไทย</p>
        </div>
        
        <div class="login-form">
            <div class="form-header">
                <div class="user-icon">👤</div>
                <h2 class="form-title">เข้าสู่ระบบ</h2>
            </div>
            
            <!-- Alert Messages -->
            <div id="alertMessage" class="alert"></div>
            
            <form id="loginForm">
                <div class="form-group">
                    <input type="email" id="email" name="email" class="form-input" placeholder="อีเมล" required>
                </div>
                
                <div class="form-group">
                    <input type="password" id="password" name="password" class="form-input" placeholder="รหัสผ่าน" required>
                </div>
                
                <button type="submit" id="loginBtn" class="login-btn">
                    <span class="loading-spinner" id="loadingSpinner"></span>
                    <span id="loginBtnText">เข้าสู่ระบบ</span>
                </button>

                <div class="forgot-password">
                    <a href="#" class="forgot-link" onclick="openForgotPasswordModal()">ลืมรหัสผ่าน</a>
                    <a href="register.php" class="register-link">ลงทะเบียน</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div id="forgotPasswordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">ลืมรหัสผ่าน?</h3>
            </div>
            <div class="modal-body">
                <input type="email" id="forgotEmail" class="modal-input" placeholder="กรอกอีเมลของคุณ">
                <div class="modal-buttons">
                    <button class="modal-btn modal-btn-primary" onclick="submitForgotPassword()">ส่ง</button>
                    <button class="modal-btn modal-btn-secondary" onclick="closeForgotPasswordModal()">ยกเลิก</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // API Configuration
        const API_BASE_URL = 'controllers/auth_api.php'; // เปลี่ยนเป็น URL ที่ถูกต้องของ API

        // Login Form Handler
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            
            // Validate inputs
            if (!email || !password) {
                showAlert('กรุณากรอกอีเมลและรหัสผ่าน', 'error');
                return;
            }
            
            // Validate email format
            if (!isValidEmail(email)) {
                showAlert('รูปแบบอีเมลไม่ถูกต้อง', 'error');
                return;
            }
            
            try {
                setLoading(true);
                hideAlert();
                
                // Call login API
                const response = await fetch(API_BASE_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'login',
                        email: email,
                        password: password
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert(data.message, 'success');
                    
                    // Store user data in sessionStorage for this session
                    if (data.data) {
                        sessionStorage.setItem('user_data', JSON.stringify(data.data));
                    }
                    
                    // Redirect after successful login
                    setTimeout(() => {
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        } else {
                            window.location.href = 'index.php';
                        }
                    }, 1500);
                    
                } else {
                    showAlert(data.message || 'เกิดข้อผิดพลาดในการเข้าสู่ระบบ', 'error');
                }
                
            } catch (error) {
                console.error('Login error:', error);
                showAlert('เกิดข้อผิดพลาดในการติดต่อเซิร์ฟเวอร์', 'error');
            } finally {
                setLoading(false);
            }
        });

        // Forgot Password Modal Functions
        function openForgotPasswordModal() {
            document.getElementById('forgotPasswordModal').style.display = 'block';
        }

        function closeForgotPasswordModal() {
            document.getElementById('forgotPasswordModal').style.display = 'none';
            document.getElementById('forgotEmail').value = '';
        }

        async function submitForgotPassword() {
            const email = document.getElementById('forgotEmail').value.trim();
            
            if (!email) {
                alert('กรุณากรอกอีเมลของคุณ');
                return;
            }
            
            if (!isValidEmail(email)) {
                alert('รูปแบบอีเมลไม่ถูกต้อง');
                return;
            }
            
            try {
                const response = await fetch(API_BASE_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'forgot_password',
                        email: email
                    })
                });
                const data = await response.json();
                showAlert(data.message || 'หากอีเมลนี้อยู่ในระบบ เราได้ส่งลิงก์รีเซ็ตรหัสผ่านให้แล้ว', 'success');
                closeForgotPasswordModal();
                
            } catch (error) {
                console.error('Forgot password error:', error);
                showAlert('เกิดข้อผิดพลาดในการส่งอีเมล', 'error');
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('forgotPasswordModal');
            if (event.target === modal) {
                closeForgotPasswordModal();
            }
        }

        // Utility Functions
        function setLoading(isLoading) {
            const loginBtn = document.getElementById('loginBtn');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const loginBtnText = document.getElementById('loginBtnText');
            
            if (isLoading) {
                loginBtn.disabled = true;
                loadingSpinner.style.display = 'inline-block';
                loginBtnText.textContent = 'กำลังเข้าสู่ระบบ...';
            } else {
                loginBtn.disabled = false;
                loadingSpinner.style.display = 'none';
                loginBtnText.textContent = 'เข้าสู่ระบบ';
            }
        }

        function showAlert(message, type = 'info') {
            const alertElement = document.getElementById('alertMessage');
            alertElement.className = `alert alert-${type}`;
            alertElement.textContent = message;
            alertElement.style.display = 'block';
            
            // Auto hide success messages after 3 seconds
            if (type === 'success') {
                setTimeout(() => {
                    hideAlert();
                }, 3000);
            }
        }

        function hideAlert() {
            const alertElement = document.getElementById('alertMessage');
            alertElement.style.display = 'none';
        }

        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        // Check if user is already logged in (optional)
        function checkExistingLogin() {
            // Check if user data exists in cookies or sessionStorage
            const userData = sessionStorage.getItem('user_data');
            if (userData) {
                try {
                    const user = JSON.parse(userData);
                    if (user.user_id) {
                        // User might already be logged in, you can redirect or show a message
                        console.log('User might already be logged in:', user.name);
                    }
                } catch (error) {
                    // Invalid session data, clear it
                    sessionStorage.removeItem('user_data');
                }
            }
        }

        // Test API connection on page load
        async function testApiConnection() {
            try {
                const response = await fetch(API_BASE_URL + '?action=test');
                const data = await response.json();
                
                if (data.success) {
                    console.log('API connection successful:', data.message);
                    console.log('Database status:', data.data.database_connected ? 'Connected' : 'Disconnected');
                } else {
                    console.warn('API test failed:', data.message);
                }
            } catch (error) {
                console.error('API connection test failed:', error);
                // Optionally show a warning to the user
                // showAlert('เชื่อมต่อเซิร์ฟเวอร์ไม่ได้ กรุณาลองใหม่อีกครั้ง', 'error');
            }
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            checkExistingLogin();
            testApiConnection();
        });

        // Handle Enter key in password field
        document.getElementById('password').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('loginForm').dispatchEvent(new Event('submit'));
            }
        });
    </script>
</body>
</html>