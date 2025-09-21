document.getElementById('registerForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);
    const registerBtn = document.getElementById('registerBtn');
    const btnText = document.getElementById('btnText');
    const alertMessage = document.getElementById('alertMessage');

    // Clear previous errors
    clearFormErrors();
    hideAlert();

    // Client-side validation
    if (!validateForm(formData)) {
        return;
    }

    // Show loading state
    registerBtn.disabled = true;
    btnText.innerHTML = '<span class="loading"></span>กำลังลงทะเบียน...';

    try {
        // Add action parameter
        formData.append('action', 'signup');

        const response = await fetch('controllers/auth_api.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showAlert('success', result.message);
            form.reset();

            // Redirect after success
            setTimeout(() => {
                if (result.redirect) {
                    window.location.href = result.redirect;
                } else {
                    window.location.href = 'login.php';
                }
            }, 2000);
        } else {
            showAlert('error', result.message);
            handleFieldErrors(result.code);
        }

    } catch (error) {
        console.error('Fetch Error:', error);
        console.log('Response URL:', error.response?.url);
        showAlert('error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ: ' + error.message);
    } finally {
        // Reset button state
        registerBtn.disabled = false;
        btnText.textContent = 'ลงทะเบียน';
    }
});

function validateForm(formData) {
    let isValid = true;
    const password = formData.get('password');
    const confirmPassword = formData.get('confirmPassword');
    const email = formData.get('email');
    const phone = formData.get('phone');

    // Validate required fields
    const requiredFields = ['name', 'email', 'phone', 'password', 'confirmPassword'];
    for (const field of requiredFields) {
        const value = formData.get(field);
        if (!value || value.trim() === '') {
            markFieldError(field);
            isValid = false;
        }
    }

    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (email && !emailRegex.test(email)) {
        markFieldError('email');
        showAlert('error', 'รูปแบบอีเมลไม่ถูกต้อง');
        isValid = false;
    }

    // Validate Thai phone format (must start with 0 and have exactly 10 digits)
    const phoneRegex = /^0\d{9}$/;
    if (phone && !phoneRegex.test(phone)) {
        markFieldError('phone');
        showAlert('error', 'รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง');
        isValid = false;
    }

    // Validate password strength
    if (password && password.length < 8) {
        markFieldError('password');
        showAlert('error', 'รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร');
        isValid = false;
    }

    if (password && !/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(password)) {
        markFieldError('password');
        showAlert('error', 'รหัสผ่านต้องประกอบด้วยตัวอักษรพิมพ์เล็ก พิมพ์ใหญ่ และตัวเลข');
        isValid = false;
    }

    // Validate password confirmation
    if (password !== confirmPassword) {
        markFieldError('confirmPassword');
        showAlert('error', 'การยืนยันรหัสผ่านไม่ตรงกัน');
        isValid = false;
    }

    return isValid;
}

function showAlert(type, message) {
    const alertMessage = document.getElementById('alertMessage');
    alertMessage.className = `alert alert-${type}`;
    alertMessage.textContent = message;
    alertMessage.style.display = 'block';

    // Auto hide success messages
    if (type === 'success') {
        setTimeout(() => {
            hideAlert();
        }, 5000);
    }
}

function hideAlert() {
    const alertMessage = document.getElementById('alertMessage');
    alertMessage.style.display = 'none';
}

function markFieldError(fieldName) {
    const field = document.getElementById(fieldName);
    if (field) {
        field.classList.add('error');
        field.addEventListener('input', function () {
            this.classList.remove('error');
        }, { once: true });
    }
}

function clearFormErrors() {
    const errorFields = document.querySelectorAll('.form-input.error');
    errorFields.forEach(field => {
        field.classList.remove('error');
    });
}

function handleFieldErrors(errorCode) {
    switch (errorCode) {
        case 'INVALID_EMAIL':
            markFieldError('email');
            break;
        case 'INVALID_PHONE':
            markFieldError('phone');
            break;
        case 'WEAK_PASSWORD':
            markFieldError('password');
            break;
        case 'PASSWORD_MISMATCH':
            markFieldError('confirmPassword');
            break;
        case 'EMAIL_EXISTS':
            markFieldError('email');
            break;
    }
}

// Real-time password validation
document.getElementById('password').addEventListener('input', function () {
    const password = this.value;
    const confirmPassword = document.getElementById('confirmPassword');

    if (confirmPassword.value && password !== confirmPassword.value) {
        confirmPassword.classList.add('error');
    } else {
        confirmPassword.classList.remove('error');
    }
});

document.getElementById('confirmPassword').addEventListener('input', function () {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;

    if (password && password !== confirmPassword) {
        this.classList.add('error');
    } else {
        this.classList.remove('error');
    }
});

// Real-time phone number validation
document.getElementById('phone').addEventListener('input', function () {
    const phone = this.value;
    const phoneRegex = /^0\d{9}$/;
    
    if (phone && !phoneRegex.test(phone)) {
        this.classList.add('error');
    } else {
        this.classList.remove('error');
    }
});