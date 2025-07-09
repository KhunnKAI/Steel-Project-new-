<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ช้างเหล็กไทย - เปลี่ยนรหัสผ่านใหม่</title>
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

        .change-password-form {
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

        .form-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .form-btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .confirm-btn {
            background: #dc3545;
            color: white;
        }

        .confirm-btn:hover {
            background: #c82333;
        }

        .cancel-btn {
            background: #6c757d;
            color: white;
        }

        .cancel-btn:hover {
            background: #5a6268;
        }

        .form-btn:active {
            transform: translateY(1px);
        }

        .back-link {
            text-align: center;
            margin-top: 15px;
        }

        .back-link a {
            color: #2c5477;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .back-link a:hover {
            color: #dc3545;
            text-decoration: underline;
        }

        .password-requirements {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #6c757d;
        }

        .password-requirements h4 {
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .password-requirements ul {
            margin-left: 20px;
            line-height: 1.5;
        }

        .password-requirements li {
            margin-bottom: 4px;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
            display: none;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
            display: none;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                gap: 40px;
            }
            
            .title {
                font-size: 36px;
            }
            
            .change-password-form {
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
        
        <div class="change-password-form">
            <div class="form-header">
                <h2 class="form-title">เปลี่ยนรหัสผ่านใหม่</h2>
            </div>
            
            <div class="success-message" id="successMessage">
                เปลี่ยนรหัสผ่านเรียบร้อยแล้ว
            </div>
            
            <div class="error-message" id="errorMessage">
                รหัสผ่านไม่ตรงกัน กรุณาลองใหม่อีกครั้ง
            </div>
            
            <div class="password-requirements">
                <h4>ข้อกำหนดรหัสผ่าน:</h4>
                <ul>
                    <li>ความยาวอย่างน้อย 8 ตัวอักษร</li>
                    <li>ประกอบด้วยตัวอักษรพิมพ์ใหญ่และเล็ก</li>
                    <li>มีตัวเลขอย่างน้อย 1 ตัว</li>
                </ul>
            </div>
            
            <form id="changePasswordForm">
                <!--<div class="form-group">
                    <input type="password" class="form-input" id="oldPassword" placeholder="รหัสผ่านเดิม" required>
                </div>-->
                
                <div class="form-group">
                    <input type="password" class="form-input" id="newPassword" placeholder="รหัสผ่านใหม่" required>
                </div>
                
                <div class="form-group">
                    <input type="password" class="form-input" id="confirmPassword" placeholder="ยืนยันรหัสผ่านใหม่" required>
                </div>
                
                <div class="form-buttons">
                    <button type="submit" class="form-btn confirm-btn">ยืนยัน</button>
                    <button type="button" class="form-btn cancel-btn" onclick="cancelChange()">ยกเลิก</button>
                </div>
            </form>
            
            <!--<div class="back-link">
                <a href="#" onclick="goBack()">← กลับสู่หน้าหลัก</a>
            </div>-->
        </div>
    </div>

    <script>
        function validatePassword(password) {
            const minLength = password.length >= 8;
            const hasUpperCase = /[A-Z]/.test(password);
            const hasLowerCase = /[a-z]/.test(password);
            const hasNumbers = /\d/.test(password);
            
            return minLength && hasUpperCase && hasLowerCase && hasNumbers;
        }

        function showMessage(type, message) {
            const successMsg = document.getElementById('successMessage');
            const errorMsg = document.getElementById('errorMessage');
            
            // Hide both messages first
            successMsg.style.display = 'none';
            errorMsg.style.display = 'none';
            
            if (type === 'success') {
                successMsg.textContent = message;
                successMsg.style.display = 'block';
            } else {
                errorMsg.textContent = message;
                errorMsg.style.display = 'block';
            }
            
            // Hide message after 3 seconds
            setTimeout(() => {
                successMsg.style.display = 'none';
                errorMsg.style.display = 'none';
            }, 3000);
        }

        document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const oldPassword = document.getElementById('oldPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            // Check if old password is provided
            if (!oldPassword) {
                showMessage('error', 'กรุณากรอกรหัสผ่านเดิม');
                return;
            }
            
            // Check if new passwords match
            if (newPassword !== confirmPassword) {
                showMessage('error', 'รหัสผ่านใหม่ไม่ตรงกัน กรุณาลองใหม่อีกครั้ง');
                return;
            }
            
            // Validate new password strength
            if (!validatePassword(newPassword)) {
                showMessage('error', 'รหัสผ่านใหม่ไม่ตรงตามข้อกำหนด');
                return;
            }
            
            // Check if new password is different from old password
            if (oldPassword === newPassword) {
                showMessage('error', 'รหัสผ่านใหม่ต้องแตกต่างจากรหัสผ่านเดิม');
                return;
            }
            
            // Simulate password change
            showMessage('success', 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว');
            
            // Clear form
            this.reset();
        });

        function cancelChange() {
            if (confirm('คุณแน่ใจหรือไม่ที่จะยกเลิกการเปลี่ยนรหัสผ่าน?')) {
                document.getElementById('changePasswordForm').reset();
                document.getElementById('successMessage').style.display = 'none';
                document.getElementById('errorMessage').style.display = 'none';
            }
        }

        function goBack() {
            if (confirm('คุณต้องการกลับสู่หน้าหลักหรือไม่?')) {
                // In a real application, this would redirect to the main page
                alert('กลับสู่หน้าหลัก');
            }
        }

        // Real-time password validation feedback
        document.getElementById('newPassword').addEventListener('input', function() {
            const password = this.value;
            const isValid = validatePassword(password);
            
            if (password.length > 0) {
                this.style.borderColor = isValid ? '#28a745' : '#dc3545';
            } else {
                this.style.borderColor = '#e9ecef';
            }
        });

        document.getElementById('confirmPassword').addEventListener('input', function() {
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = this.value;
            
            if (confirmPassword.length > 0) {
                this.style.borderColor = newPassword === confirmPassword ? '#28a745' : '#dc3545';
            } else {
                this.style.borderColor = '#e9ecef';
            }
        });
    </script>
</body>
</html>