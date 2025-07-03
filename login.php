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
        }

        .login-btn:hover {
            background: #c82333;
        }

        .login-btn:active {
            transform: translateY(1px);
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
                <img src="image/logo.png" width="300px">
            </div>
            <h1 class="title">ช้างเหล็กไทย</h1>
            <p class="subtitle">เหล็กคุณภาพ แกร่งทุกงาน มั่นใจช้างเหล็กไทย</p>
        </div>
        
        <div class="login-form">
            <div class="form-header">
                <div class="user-icon">👤</div>
                <h2 class="form-title">เข้าสู่ระบบ</h2>
            </div>
            
            <form>
                <div class="form-group">
                    <input type="text" class="form-input" placeholder="ชื่อผู้ใช้">
                </div>
                
                <div class="form-group">
                    <input type="password" class="form-input" placeholder="รหัสผ่าน">
                </div>
                
                <button type="submit" class="login-btn">เข้าสู่ระบบ</button>

                <div class="forgot-password">
                    <a href="#" class="forgot-link" onclick="openForgotPasswordModal()">ลืมรหัสผ่าน</a>
                    <a href="http://localhost/NewProject/register.php" class="register-link">ลงทะเบียน</a>
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
                <input type="email" class="modal-input" placeholder="กรอกอีเมลของคุณ">
                <div class="modal-buttons">
                    <button class="modal-btn modal-btn-primary" onclick="submitForgotPassword()">ส่ง</button>
                    <button class="modal-btn modal-btn-secondary" onclick="closeForgotPasswordModal()">ยกเลิก</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openForgotPasswordModal() {
            document.getElementById('forgotPasswordModal').style.display = 'block';
        }

        function closeForgotPasswordModal() {
            document.getElementById('forgotPasswordModal').style.display = 'none';
        }

        function submitForgotPassword() {
            const email = document.querySelector('.modal-input').value;
            if (email) {
                alert('ส่งลิงก์รีเซ็ตรหัสผ่านไปยังอีเมลของคุณแล้ว');
                closeForgotPasswordModal();
                document.querySelector('.modal-input').value = '';
            } else {
                alert('กรุณากรอกอีเมลของคุณ');
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('forgotPasswordModal');
            if (event.target === modal) {
                closeForgotPasswordModal();
            }
        }
    </script>
</body>
</html>