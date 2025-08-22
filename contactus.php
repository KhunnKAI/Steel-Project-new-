<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ติดต่อเรา</title>
    <link href="header.css" rel="stylesheet">
    <link href="footer.css" rel="stylesheet">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #f8f9fa;
    }

    /* Hero Section */
    .hero {
        background: linear-gradient(135deg, #1e3a5f 0%, #2c5477 100%);
        color: white;
        padding: 60px 0;
        text-align: center;
    }

    .hero-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .logo-page {
        width: 100px;
        height: 100px;
        background: white;
        border-radius: 50%;
        margin: 0 auto 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    }

    .hero-title {
        font-size: 42px;
        font-weight: bold;
        margin-bottom: 15px;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
    }

    .hero-subtitle {
        font-size: 16px;
        opacity: 0.9;
    }

    /* Main Content */
    .main-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px 20px;
    }

    .section-title {
        font-size: 2.5rem;
        color: #d32f2f;
        margin-bottom: 20px;
        font-weight: 600;
    }

    .section-subtitle {
        font-size: 1.2rem;
        color: #666;
        margin-bottom: 40px;
        line-height: 1.8;
    }

    .contact-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 30px;
        margin: 40px 0;
    }

    .contact-card {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 30px;
        text-align: center;
        transition: all 0.3s ease;
    }

    .contact-card:hover {
        border-color: #d32f2f;
        box-shadow: 0 5px 20px rgba(211, 47, 47, 0.1);
    }

    .contact-icon {
        width: 60px;
        height: 60px;
        background: #d32f2f;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        color: white;
        font-size: 1.5rem;
    }

    .contact-card h3 {
        font-size: 1.3rem;
        color: #333;
        margin-bottom: 15px;
        font-weight: 600;
    }

    .contact-card p {
        color: #666;
        line-height: 1.6;
    }

    .contact-card a {
        color: #d32f2f;
        text-decoration: none;
    }

    .contact-card a:hover {
        text-decoration: underline;
    }

    .cta-section {
        background: linear-gradient(135deg, #d32f2f 0%, #b71c1c 100%);
        color: white;
        padding: 40px;
        border-radius: 10px;
        text-align: center;
        margin: 40px 0 0;
    }

    business-hours {
        background: #fff3e0;
        border: 1px solid #ffcc02;
        border-radius: 8px;
        padding: 25px;
        margin: 30px 0;
    }

    .business-hours h4 {
        color: #e65100;
        font-size: 1.3rem;
        margin-bottom: 15px;
        font-weight: 600;
    }

    .hours-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
    }

    .hours-item {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid rgba(230, 81, 0, 0.1);
    }

    .hours-item:last-child {
        border-bottom: none;
    }

    @media (max-width: 768px) {
        .nav-tabs {
            flex-direction: column;
        }

        .logo {
            font-size: 2.2rem;
        }

        .tab-content {
            padding: 20px;
        }

        .cta-buttons {
            flex-direction: column;
            align-items: center;
        }
    }
    </style>
</head>

<body>
    <!-- Header -->
    <?php include("header.php");?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-container">
            <div class="logo-page">
                <img src="image/logo.png" width="300px">
            </div>
            <h1 class="hero-title">ช้างเหล็กไทย</h1>
            <p class="hero-subtitle">เหล็กคุณภาพ แกร่งทุกงาน มั่นใจช้างเหล็กไทย</p>
        </div>
    </section>

    <!-- Tab ติดต่อเรา -->
    <main class="main-content">
        <div id="contact" class="tab-content">
            <h2 class="section-title">ติดต่อเรา</h2>
            <p class="section-subtitle">
                พร้อมให้บริการและคำปรึกษาเกี่ยวกับเหล็กและวัสดุก่อสร้างตลอด 24 ชั่วโมง
                ติดต่อเราผ่านช่องทางที่สะดวกที่สุดสำหรับคุณ
            </p>

            <div class="contact-info">
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <h3>โทรศัพท์</h3>
                    <p>
                        สำนักงาน: <a href="tel:02-XXX-XXXX">02-XXX-XXXX</a><br>
                        มือถือ: <a href="tel:08X-XXX-XXXX">08X-XXX-XXXX</a><br>
                        แฟ็กซ์: 02-XXX-XXXX
                    </p>
                </div>
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h3>อีเมล</h3>
                    <p>
                        ทั่วไป: <a href="mailto:info@changthaisteel.com">info@changthaisteel.com</a><br>
                        ฝ่ายขาย: <a href="mailto:sales@changthaisteel.com">sales@changthaisteel.com</a><br>
                        สอบถามราคา: <a href="mailto:quote@changthaisteel.com">quote@changthaisteel.com</a>
                    </p>
                </div>
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h3>ที่อยู่</h3>
                    <p>
                        123 ถนนเหล็ก แขวงช่างเหล็ก<br>
                        เขตอุตสาหกรรม กรุงเทพฯ 10XXX<br>
                        <small>ใกล้สถานี BTS/MRT สถานีXXX</small>
                    </p>
                </div>
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fab fa-line"></i>
                    </div>
                    <h3>LINE Official</h3>
                    <p>
                        LINE ID: @changthaisteel<br>
                        <small>สำหรับขอใบเสนอราคาด่วน<br>และคำปรึกษาเร่งด่วน</small>
                    </p>
                </div>
            </div>

            <div class="business-hours">
                <h4><i class="fas fa-clock" style="margin-right: 10px;"></i>เวลาทำการ</h4>
                <div class="hours-grid">
                    <div class="hours-item">
                        <span><strong>จันทร์ - ศุกร์</strong></span>
                        <span>8:00 - 18:00 น.</span>
                    </div>
                    <div class="hours-item">
                        <span><strong>เสาร์</strong></span>
                        <span>8:00 - 16:00 น.</span>
                    </div>
                    <div class="hours-item">
                        <span><strong>อาทิตย์</strong></span>
                        <span>9:00 - 15:00 น.</span>
                    </div>
                    <div class="hours-item">
                        <span><strong>วันหยุดนักขัตฤกษ์</strong></span>
                        <span>ปิดทำการ</span>
                    </div>
                </div>
            </div>

            <div class="cta-section">
                <h3 class="cta-title">พร้อมให้บริการคุณแล้ววันนี้</h3>
                <p class="cta-text">
                    ติดต่อเราเพื่อรับคำปรึกษาฟรี ใบเสนอราคาพิเศษ และบริการจัดส่งที่รวดเร็ว
                </p>
                <div class="cta-buttons">
                    <a href="tel:02XXXXXXX" class="cta-button">
                        <i class="fas fa-phone"></i>โทรเลย
                    </a>
                    <a href="mailto:sales@changthaisteel.com" class="cta-button">
                        <i class="fas fa-envelope"></i>ส่งอีเมล
                    </a>
                    <a href="#" class="cta-button">
                        <i class="fab fa-line"></i>LINE Chat
                    </a>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include("footer.php");?>

</body>

</html>