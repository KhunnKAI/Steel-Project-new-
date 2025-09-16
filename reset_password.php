<?php
// reset_password.php - Page to reset password using token
// This is a simple standalone page that calls controllers/auth_api.php with action=reset_password
require 'vendor/autoload.php';

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รีเซ็ตรหัสผ่าน</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; background: #f5f7fb; margin: 0; padding: 0; }
        .container { max-width: 420px; margin: 60px auto; background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); padding: 28px; }
        h1 { font-size: 22px; margin: 0 0 6px; color: #1f2937; text-align: center; }
        p.desc { color: #6b7280; font-size: 14px; margin: 0 0 20px; text-align: center; }
        .input { width: 90%; padding: 12px 14px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 15px; background: #f9fafb; outline: none; transition: border-color .2s; margin-bottom: 14px; }
        .input:focus { border-color: #2563eb; background: #fff; }
        .btn { width: 100%; padding: 12px; border: 0; border-radius: 10px; background: #dc3545; color: #fff; font-weight: 600; cursor: pointer; font-size: 15px; }
        .btn:disabled { background: #9ca3af; cursor: not-allowed; }
        .alert { display: none; padding: 12px 14px; border-radius: 10px; font-size: 14px; margin-bottom: 14px; }
        .alert.success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert.error { background: #fee2e2; color: #7f1d1d; border: 1px solid #fecaca; }
        .footer { text-align: center; margin-top: 14px; font-size: 13px; color: #6b7280; }
        .link { color: #2563eb; text-decoration: none; }
    </style>
    <script>
        async function submitReset(event) {
            event.preventDefault();
            const token = new URLSearchParams(window.location.search).get('token') || document.getElementById('token').value.trim();
            const new_password = document.getElementById('new_password').value;
            const confirm_password = document.getElementById('confirm_password').value;

            const alertBox = document.getElementById('alert');
            function show(type, msg) { alertBox.className = 'alert ' + type; alertBox.textContent = msg; alertBox.style.display = 'block'; }

            if (!token) { show('error', 'โทเคนไม่ถูกต้อง'); return; }
            if (!new_password || !confirm_password) { show('error', 'กรุณากรอกรหัสผ่านให้ครบ'); return; }
            if (new_password !== confirm_password) { show('error', 'การยืนยันรหัสผ่านไม่ตรงกัน'); return; }

            try {
                document.getElementById('btn').disabled = true;
                const res = await fetch('controllers/auth_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'reset_password', token, new_password, confirm_password })
                });
                const data = await res.json();
                if (data.success) {
                    show('success', data.message || 'รีเซ็ตรหัสผ่านสำเร็จ');
                    setTimeout(() => { window.location.href = 'login.php'; }, 1500);
                } else {
                    show('error', data.message || 'ไม่สามารถรีเซ็ตรหัสผ่านได้');
                }
            } catch (e) {
                show('error', 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์');
            } finally {
                document.getElementById('btn').disabled = false;
            }
        }
    </script>
    </head>
<body>
    <div class="container">
        <h1>รีเซ็ตรหัสผ่าน</h1>
        <p class="desc">กรอกรหัสผ่านใหม่ของคุณ</p>
        <div id="alert" class="alert"></div>
        <form onsubmit="submitReset(event)">
            <input type="password" id="new_password" class="input" placeholder="รหัสผ่านใหม่" required>
            <input type="password" id="confirm_password" class="input" placeholder="ยืนยันรหัสผ่านใหม่" required>
            <input type="hidden" id="token" value="<?php echo htmlspecialchars($_GET['token'] ?? ''); ?>">
            <button id="btn" class="btn" type="submit">ยืนยันการรีเซ็ต</button>
        </form>
        <div class="footer">
            <a class="link" href="login.php">กลับไปหน้าเข้าสู่ระบบ</a>
        </div>
    </div>
</body>
</html>



