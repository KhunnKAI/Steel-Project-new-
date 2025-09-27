<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เกี่ยวกับเรา</title>
    <link href="header.css" rel="stylesheet">
    <link href="footer.css" rel="stylesheet">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter';
        background: #f8f9fa;
    }

    /* Hero Section */
    .hero {
        background: #051A37;
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

    .about-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
        margin: 40px 0;
    }

    .about-card {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 30px;
        text-align: center;
        transition: all 0.3s ease;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .about-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 25px rgba(211, 47, 47, 0.1);
        border-color: #d32f2f;
    }

    .about-card-icon {
        font-size: 3rem;
        color: #d32f2f;
        margin-bottom: 20px;
    }

    .about-card h3 {
        font-size: 1.5rem;
        color: #333;
        margin-bottom: 15px;
        font-weight: 600;
    }

    .about-card p {
        color: #666;
        line-height: 1.7;
    }

    .features-section {
        background: #f9f9f9;
        padding: 40px;
        margin: 40px -40px 0;
        border-radius: 10px;
    }

    .features-title {
        text-align: center;
        font-size: 2rem;
        color: #d32f2f;
        margin-bottom: 30px;
        font-weight: 600;
    }

    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 25px;
    }

    .feature-item {
        background: white;
        padding: 25px;
        border-radius: 8px;
        text-align: center;
        border-left: 4px solid #d32f2f;
        transition: all 0.3s ease;
    }

    .feature-item:hover {
        transform: translateX(5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .feature-item h4 {
        color: #d32f2f;
        font-size: 1.2rem;
        margin-bottom: 10px;
        font-weight: 600;
    }

    .feature-item p {
        color: #666;
        font-size: 0.95rem;
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

    .cta-title {
        font-size: 2rem;
        margin-bottom: 15px;
        font-weight: 600;
    }

    .cta-text {
        font-size: 1.1rem;
        margin-bottom: 30px;
        opacity: 0.95;
    }

    .cta-buttons {
        display: flex;
        gap: 20px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .cta-button {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        padding: 12px 30px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 50px;
        font-size: 1rem;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .cta-button:hover {
        background: rgba(255, 255, 255, 0.3);
        border-color: rgba(255, 255, 255, 0.6);
        transform: translateY(-2px);
    }

    .business-hours {
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

        .section-title {
            font-size: 2rem;
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

    <!-- Tab เกี่ยวกับเรา -->
    <main class="main-content">
        <div id="about" class="tab-content active">
            <h2 class="section-title">เกี่ยวกับเรา</h2>
            <p class="section-subtitle">
                ช้างเหล็กไทย คือผู้จำหน่ายเหล็กและวัสดุก่อสร้างคุณภาพสูงมากว่า 20 ปี ด้วยประสบการณ์และความเชี่ยวชาญ
                เราพร้อมให้บริการลูกค้าทั้งโครงการขนาดเล็กและขนาดใหญ่ ด้วยสินค้าคุณภาพและราคาที่เป็นธรรม
            </p>

            <div class="about-grid">
                <div class="about-card">
                    <div class="about-card-icon">🏗️</div>
                    <h3>ประสบการณ์ยาวนาน</h3>
                    <p>มากกว่า 20 ปีในธุรกิจเหล็กและวัสดุก่อสร้าง
                        พร้อมด้วยทีมงานมืออาชีพที่มีความเชี่ยวชาญในการให้คำปรึกษา</p>
                </div>
                <div class="about-card">
                    <div class="about-card-icon">⭐</div>
                    <h3>คุณภาพมาตรฐาน</h3>
                    <p>สินค้าทุกชิ้นผ่านการคัดสรรและตรวจสอบคุณภาพตามมาตรฐาน มอก. และมาตรฐานสากล รับประกันความทนทาน</p>
                </div>
                <div class="about-card">
                    <div class="about-card-icon">🚚</div>
                    <h3>บริการครบวงจร</h3>
                    <p>บริการจัดส่งทั่วประเทศ ตรงเวลา พร้อมบริการหลังการขายและคำปรึกษาทางเทคนิคจากผู้เชี่ยวชาญ</p>
                </div>
            </div>

            <div class="features-section">
                <h3 class="features-title">สินค้าและบริการของเรา</h3>
                <div class="features-grid">
                    <div class="feature-item">
                        <h4>🏭 เหล็กโครงสร้าง</h4>
                        <p>เหล็กเส้น, เหล็กแผ่น, เหล็กรูปพรรณทุกขนาด</p>
                    </div>
                    <div class="feature-item">
                        <h4>🔩 อุปกรณ์ยึดต่อ</h4>
                        <p>น็อต, สกรู, แป๊ปเหล็ก, อุปกรณ์เชื่อมต่อ</p>
                    </div>
                    <div class="feature-item">
                        <h4>⚒️ เครื่องมือช่าง</h4>
                        <p>เครื่องมือคุณภาพสำหรับช่างทุกระดับ</p>
                    </div>
                    <div class="feature-item">
                        <h4>📏 บริการตัดตามขนาด</h4>
                        <p>ตัดเหล็กตามความต้องการของลูกค้า</p>
                    </div>
                    <div class="feature-item">
                        <h4>💡 คำปรึกษาเทคนิค</h4>
                        <p>ให้คำแนะนำการเลือกใช้วัสดุที่เหมาะสม</p>
                    </div>
                    <div class="feature-item">
                        <h4>📦 จัดส่งทั่วประเทศ</h4>
                        <p>บริการขนส่งที่รวดเร็วและปลอดภัย</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include("footer.php");?>

</body>

</html>