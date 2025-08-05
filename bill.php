<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สถานะในเสร็จ</title>
    <link href="header.css" rel="stylesheet">
    <link href="footer.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }

        .github-icon {
            width: 32px;
            height: 32px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
            font-weight: bold;
        }

        .admin-text {
            font-size: 0.9rem;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-icons {
            display: flex;
            gap: 0.5rem;
        }

        .user-icon {
            width: 32px;
            height: 32px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .user-icon:hover {
            background: rgba(255,255,255,0.3);
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .main-title {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 1.5rem;
            color: #333;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #555;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #d32f2f;
        }

        .form-input:disabled {
            background: #f5f5f5;
            color: #999;
        }

        .payment-form {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 12px;
            margin-top: 1rem;
        }

        .bank-info {
            background: #d32f2f;
            color: white;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 1.5rem;
            font-weight: bold;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .submit-btn {
            width: 100%;
            background: #1a237e;
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            background: #303f9f;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 35, 126, 0.3);
        }

        .address-section {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 12px;
            margin-top: 1rem;
        }

        .address-text {
            font-size: 0.9rem;
            line-height: 1.6;
            color: #666;
        }

        .status-timeline {
            margin-top: 2rem;
        }

        .timeline-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid #e1e5e9;
        }

        .timeline-item:last-child {
            border-bottom: none;
        }

        .timeline-icon {
            width: 50px;
            height: 50px;
            background: #e1e5e9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .timeline-icon.active {
            background: #d32f2f;
            color: white;
        }

        .timeline-content {
            flex: 1;
        }

        .timeline-title {
            font-weight: bold;
            margin-bottom: 0.25rem;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .section {
            animation: fadeInUp 0.6s ease;
        }

        .section:nth-child(2) { animation-delay: 0.1s; }
        .section:nth-child(3) { animation-delay: 0.2s; }
        .section:nth-child(4) { animation-delay: 0.3s; }
        .section:nth-child(5) { animation-delay: 0.4s; }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 1rem;
            }
            
            .section {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include("header.php");?>
    
    <div class="container">
        <div class="main-title">
            สถานะใบเสร็จ
        </div>

        <div class="section">
            <div class="section-title">สถานะสินค้า</div>
            <div class="form-group">
                <input type="text" class="form-input" value="คุณได้สั่งซื้อสินค้าสำเร็จแล้ว" disabled>
            </div>
        </div>

        <div class="section">
            <div class="section-title">ข้อมูลการชำระ</div>
            
            <div class="payment-form">
                <div class="form-label">แจ้งโอน</div>
                
                <div class="bank-info">
                    ธนาคาร กรุงไทย<br>
                    111-1-11111-1
                </div>

                <div class="form-group">
                    <input type="text" class="form-input" placeholder="ชื่อผู้โอนเงิน">
                </div>

                <div class="form-row">
                    <div>
                        <input type="time" class="form-input" placeholder="เวลาที่โอน">
                    </div>
                    <div>
                        <input type="date" class="form-input" placeholder="วันที่โอน">
                    </div>
                </div>

                <div class="form-group">
                    <input type="text" class="form-input" placeholder="แนบหลักฐาน">
                </div>

                <button class="submit-btn">ยืนยันการแจ้งโอน</button>
            </div>
        </div>

        <div class="section">
            <div class="section-title">ข้อมูลจัดส่ง</div>
            
            <div class="address-section">
                <div class="address-text">
                    <strong>ชื่อ:</strong> นางสาวปริชญา วันมา<br>
                    <strong>เบอร์:</strong> 0111111111<br>
                    <strong>ที่อยู่:</strong> 155/88 หมู่ 5 ซอย สีทาน 29 ถนน สีทน
แขวง คันนาดี เขต คันดอน กรุงเทพมหานคร 88888<br>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">สถานะสินค้า</div>
            
            <div class="status-timeline">
                <div class="timeline-item">
                    <div class="timeline-icon active">✓</div>
                    <div class="timeline-content">
                        <div class="timeline-title">คุณได้สร้างรายการสั่งซื้อแล้ว</div>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-icon">⏳</div>
                    <div class="timeline-content">
                        <div class="timeline-title">คุณได้แจ้งชำระเงินแล้ว</div>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-icon">📦</div>
                    <div class="timeline-content">
                        <div class="timeline-title">ร้านยืนยันยอดเงินแล้ว</div>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-icon">🚚</div>
                    <div class="timeline-content">
                        <div class="timeline-title">รอจัดส่ง</div>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-icon">✅</div>
                    <div class="timeline-content">
                        <div class="timeline-title">เสร็จเรียบร้อย</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include("footer.php");?>

    <script>
        // เพิ่มการทำงานของฟอร์ม
        document.querySelector('.submit-btn').addEventListener('click', function() {
            // ตรวจสอบการกรอกข้อมูล
            const inputs = document.querySelectorAll('.payment-form input[type="text"]');
            let allFilled = true;
            
            inputs.forEach(input => {
                if (input.value.trim() === '') {
                    allFilled = false;
                    input.style.borderColor = '#ff4444';
                } else {
                    input.style.borderColor = '#4caf50';
                }
            });

            if (allFilled) {
                // แสดงข้อความสำเร็จ
                this.textContent = 'ยืนยันสำเร็จ!';
                this.style.background = '#4caf50';
                
                // อัพเดทสถานะ timeline
                setTimeout(() => {
                    const secondIcon = document.querySelectorAll('.timeline-icon')[1];
                    secondIcon.classList.add('active');
                    secondIcon.innerHTML = '✓';
                }, 1000);

                // รีเซ็ตปุ่มหลังจาก 3 วินาที
                setTimeout(() => {
                    this.textContent = 'ยืนยันการแจ้งโอน';
                    this.style.background = '#1a237e';
                }, 3000);
            } else {
                // แสดงข้อความแจ้งเตือน
                this.textContent = 'กรุณากรอกข้อมูลให้ครบถ้วน';
                this.style.background = '#ff4444';
                
                setTimeout(() => {
                    this.textContent = 'ยืนยันการแจ้งโอน';
                    this.style.background = '#1a237e';
                }, 2000);
            }
        });

        // เพิ่มเอฟเฟกต์ focus สำหรับ input
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.style.borderColor = '#667eea';
                this.style.boxShadow = '0 0 0 3px rgba(102, 126, 234, 0.1)';
            });

            input.addEventListener('blur', function() {
                this.style.borderColor = '#e1e5e9';
                this.style.boxShadow = 'none';
            });
        });

        // Animation เมื่อโหลดหน้า
        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('.section');
            sections.forEach((section, index) => {
                section.style.animationDelay = `${index * 0.1}s`;
            });
        });

        // เพิ่มเอฟเฟกต์ hover สำหรับ timeline items
        document.querySelectorAll('.timeline-item').forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.style.backgroundColor = '#f8f9fa';
                this.style.transform = 'translateX(10px)';
                this.style.transition = 'all 0.3s ease';
            });

            item.addEventListener('mouseleave', function() {
                this.style.backgroundColor = 'transparent';
                this.style.transform = 'translateX(0)';
            });
        });
    </script>
</body>
</html>