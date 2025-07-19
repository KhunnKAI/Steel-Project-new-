<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ช้างเหล็กไทย - ลงทะเบียน</title>
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

        .register-form {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 16px 48px rgba(0,0,0,0.2);
            width: 350px;
            max-height: 80vh;
            overflow-y: auto;
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

        .register-btn {
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
            margin-top: 10px;
        }

        .register-btn:hover {
            background: #c82333;
        }

        .register-btn:active {
            transform: translateY(1px);
        }

        .login-link {
            text-align: center;
            margin-top: 15px;
        }

        .login-link a {
            color: #2c5477;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .login-link a:hover {
            color: #dc3545;
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                gap: 40px;
            }
            
            .title {
                font-size: 36px;
            }
            
            .register-form {
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
        
        <div class="register-form">
            <div class="form-header">
                <h2 class="form-title">ลงทะเบียน</h2>
            </div>
            
            <form>
                <div class="form-group">
                    <input type="text" class="form-input" placeholder="ชื่อ-นามสกุล" required>
                </div>
                
                <div class="form-group">
                    <input type="email" class="form-input" placeholder="อีเมล" required>
                </div>
                
                <div class="form-group">
                    <input type="tel" class="form-input" placeholder="เบอร์โทรศัพท์" required>
                </div>
                
                <div class="form-group">
                    <input type="password" class="form-input" placeholder="รหัสผ่าน" required>
                </div>
                
                <div class="form-group">
                    <input type="password" class="form-input" placeholder="ยืนยันรหัสผ่าน" required>
                </div>
                
                <button type="submit" class="register-btn">ลงทะเบียน</button>
                
                <div class="login-link">
                    <a href="http://localhost/newproject/login.php?#">มีบัญชีอยู่แล้ว? เข้าสู่ระบบ</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>