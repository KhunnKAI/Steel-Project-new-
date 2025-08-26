<!-- <?php
// Remove session_start() from here since config.php handles it properly
require_once 'controllers/config.php';

// Require login to access this page
requireLogin();

// Get current admin information
$current_admin = getCurrentAdmin();
if (!$current_admin) {
    // If admin not found in database, logout
    header("Location: controllers/logout.php");
    exit();
}
?> -->

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสินค้า - ระบบจัดการร้านค้า</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="products_admin.css">

</head>

<body>
    <div class="navbar-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </div>

    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="logo">
                <div>
                    <img src="image/logo_cropped.png" width="100px">
                </div>
                <h2>ระบบผู้ดูแล</h2>
            </div>

            <nav>
                <ul>
                    <li>
                        <a href="dashboard_admin.php" onclick="showSection('dashboard')">
                            <i class="fas fa-tachometer-alt"></i>
                            แดชบอร์ด
                        </a>
                    </li>
                    <li class="active">
                        <a href="products_admin.php" onclick="showSection('products')">
                            <i class="fas fa-box"></i>
                            จัดการสินค้า
                        </a>
                    </li>
                    <li>
                        <a href="orders_admin.php" onclick="showSection('orders')">
                            <i class="fas fa-shopping-cart"></i>
                            จัดการคำสั่งซื้อ
                        </a>
                    </li>
                    <li>
                        <a href="admins_admin.php" onclick="showSection('admins')">
                            <i class="fas fa-users-cog"></i>
                            จัดการผู้ดูแล
                        </a>
                    </li>
                    <li>
                        <a href="reports_admin.php" onclick="showSection('reports')">
                            <i class="fas fa-chart-bar"></i>
                            รายงาน
                        </a>
                    </li>
                    <li>
                        <a href="#" onclick="handleLogout()">
                            <i class="fas fa-sign-out-alt"></i>
                            ออกจากระบบ
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1><i class="fas fa-box"></i> จัดการสินค้า</h1>
                <div class="search-add">
                    <div class="search-container">
                        <input type="text" id="searchInput" placeholder="ค้นหาชื่อสินค้า, ล็อต หรือรหัสสินค้า...">
                        <i class="fas fa-search"></i>
                    </div>
                    <button class="add-btn" onclick="openAddModal()">
                        <i class="fas fa-plus"></i> เพิ่มสินค้า
                    </button>
                    <!-- <button class="stockmovement-btn" onclick="window.location.href='stockmovement_admin.html'">
                        <i class="fas fa-exchange-alt"></i> บันทึกการเคลื่อนไหวสินค้า
                    </button> -->
                </div>
            </div>

            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-number" id="totalProducts">0</div>
                    <div class="stat-label">สินค้าทั้งหมด</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="lowStockProducts">0</div>
                    <div class="stat-label">สินค้าใกล้หมด</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="filteredProducts">0</div>
                    <div class="stat-label">ผลการค้นหา</div>
                </div>
            </div>

            <div class="filters-section">
                <div class="filters-header">
                    <i class="fas fa-filter"></i>
                    <h3>ตัวกรองข้อมูล</h3>
                    <div id="activeFiltersCount" class="filter-count-badge" style="display: none;">0</div>
                </div>
                <div class="filters-row">
                    <div class="filter-group">
                        <label><i class="fas fa-tags"></i> หมวดหมู่</label>
                        <select id="categoryFilter">
                            <option value="">ทั้งหมด</option>
                            <option value="rebar">เหล็กเส้น</option>
                            <option value="steelplate">เหล็กแผ่น</option>
                            <option value="structuralsteel">เหล็กรูปพรรณ</option>
                            <option value="wiremesh">เหล็กตะแกรง/ตาข่าย</option>
                            <option value="steelproducts">อื่น ๆ</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-warehouse"></i> สถานะสต็อก</label>
                        <select id="stockFilter">
                            <option value="">ทั้งหมด</option>
                            <option value="high">สต็อกเพียงพอ</option>
                            <option value="medium">สต็อกปานกลาง</option>
                            <option value="low">สต็อกต่ำ</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-sort"></i> เรียงตาม</label>
                        <select id="sortFilter">
                            <option value="name">ชื่อสินค้า</option>
                            <option value="stock">จำนวนคงเหลือ</option>
                            <option value="lot">ล็อต</option>
                            <option value="receivedDate_desc">วันที่รับเข้า (ใหม่-เก่า)</option>
                            <option value="receivedDate_asc">วันที่รับเข้า (เก่า-ใหม่)</option>
                            <option value="supplier">ซัพพลายเออร์</option>
                        </select>
                    </div>
                    <div class="date-range-group">
                        <div class="date-input-wrapper">
                            <label><i class="fas fa-calendar-alt"></i> วันที่รับเข้า ตั้งแต่</label>
                            <input type="date" id="startDateFilter">
                        </div>
                        <div class="date-input-wrapper">
                            <label><i class="fas fa-calendar-check"></i> ถึง</label>
                            <input type="date" id="endDateFilter">
                        </div>
                        <button class="clear-dates-btn" onclick="clearDateFilters()">
                            <i class="fas fa-times"></i>
                            ล้างวันที่
                        </button>
                    </div>
                    <div class="filter-reset-all">
                        <button class="reset-all-btn" onclick="resetAllFilters()">
                            <i class="fas fa-undo"></i>
                            รีเซ็ตทั้งหมด
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <table id="productsTable">
                    <thead>
                        <tr>
                            <th>รหัสสินค้า</th>
                            <th>ข้อมูลสินค้า</th>
                            <th>หมวดหมู่</th>
                            <th>ล็อต</th>
                            <th>จำนวนคงเหลือ</th>
                            <th>ราคา</th>
                            <th>วันที่รับเข้า</th>
                            <th>ซัพพลายเออร์</th>
                            <th>การจัดการ</th>
                        </tr>

                    </thead>
                    <tbody id="productsTableBody">
                        <!-- Product rows will be dynamically inserted here -->
                    </tbody>
                </table>
            </div>

            <div class="pagination" id="pagination">
                <!-- Pagination buttons will be inserted here -->
            </div>
        </main>
    </div>

    <!-- Add/Edit Product Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">เพิ่มสินค้าใหม่</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form id="productForm">
                <div class="form-grid">
                    <!-- ลบ field รหัสสินค้าออก -->
                    <div class="form-group">
                        <label>หมวดหมู่ *</label>
                        <select id="productCategory" required>
                            <option value="">เลือกหมวดหมู่</option>
                            <option value="rebar">เหล็กเส้น</option>
                            <option value="steelplate">เหล็กแผ่น</option>
                            <option value="structuralsteel">เหล็กรูปพรรณ</option>
                            <option value="wiremesh">เหล็กตะแกรง/ตาข่าย</option>
                            <option value="steelproducts">อื่น ๆ</option>
                        </select>
                    </div>
                </div>

                <div class="form-group form-grid-full">
                    <label>ชื่อสินค้า *</label>
                    <input type="text" id="productName" required>
                </div>

                <div class="form-group">
                    <label>คำอธิบาย</label>
                    <textarea id="productDescription" rows="3"></textarea>
                </div>

                <!-- Image Upload Section ยังคงเหมือนเดิม -->
                <div class="images-section" id="imagesSection">
                    <h3><i class="fas fa-images"></i> รูปภาพสินค้า</h3>

                    <div class="drop-zone" id="dropZone">
                        <input type="file" id="imageInput" class="file-input" multiple accept="image/*">
                        <div class="drop-zone-content">
                            <i class="fas fa-cloud-upload-alt drop-zone-icon"></i>
                            <div class="drop-zone-text">ลากและวางรูปภาพที่นี่</div>
                            <div class="drop-zone-subtext">หรือคลิกเพื่อเลือกไฟล์ (สามารถเลือกได้หลายไฟล์)</div>
                            <button type="button" class="upload-button"
                                onclick="document.getElementById('imageInput').click()">
                                <i class="fas fa-plus"></i>
                                เลือกรูปภาพ
                            </button>
                        </div>
                    </div>

                    <div class="image-preview-container" id="imagePreviewContainer">
                        <!-- Image previews will be inserted here -->
                    </div>
                </div>

                <!-- Dimensions Section ยังคงเหมือนเดิม -->
                <div class="dimensions-section">
                    <h3><i class="fas fa-ruler-combined"></i> ข้อมูลขนาดและน้ำหนัก</h3>

                    <div class="dimension-row">
                        <div class="form-group">
                            <label>ความกว้าง</label>
                            <input type="number" id="productWidth" step="0.01" min="0" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label>หน่วย</label>
                            <select id="widthUnit">
                                <option value="mm">มม.</option>
                                <option value="cm">ซม.</option>
                                <option value="m">ม.</option>
                                <option value="inch">นิ้ว</option>
                            </select>
                        </div>
                    </div>

                    <div class="dimension-row">
                        <div class="form-group">
                            <label>ความยาว</label>
                            <input type="number" id="productLength" step="0.01" min="0" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label>หน่วย</label>
                            <select id="lengthUnit">
                                <option value="mm">มม.</option>
                                <option value="cm">ซม.</option>
                                <option value="m">ม.</option>
                                <option value="inch">นิ้ว</option>
                            </select>
                        </div>
                    </div>

                    <div class="dimension-row">
                        <div class="form-group">
                            <label>ส่วนสูง/ความหนา</label>
                            <input type="number" id="productHeight" step="0.01" min="0" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label>หน่วย</label>
                            <select id="heightUnit">
                                <option value="mm">มม.</option>
                                <option value="cm">ซม.</option>
                                <option value="m">ม.</option>
                                <option value="inch">นิ้ว</option>
                            </select>
                        </div>
                    </div>

                    <div class="dimension-row">
                        <div class="form-group">
                            <label>น้ำหนัก</label>
                            <input type="number" id="productWeight" step="0.01" min="0" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label>หน่วย</label>
                            <select id="weightUnit">
                                <option value="kg">กก.</option>
                                <option value="g">กรัม</option>
                                <option value="ton">ตัน</option>
                                <option value="lb">ปอนด์</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>ล็อต *</label>
                        <input type="text" id="productLot" required>
                    </div>
                    <div class="form-group">
                        <label>จำนวนคงเหลือ *</label>
                        <input type="number" id="productStock" required min="0">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>ราคาต่อหน่วย (บาท)</label>
                        <input type="number" id="productPrice" step="0.01" min="0">
                    </div>
                    <div class="form-group">
                        <label>วันที่รับเข้า *</label>
                        <input type="date" id="productReceivedDate" required>
                    </div>
                </div>

                <!-- แทนที่ field ซัพพลายเออร์เดิม -->
                <div class="form-group">
                    <label>ซัพพลายเออร์ *</label>
                    <select id="productSupplier" required>
                        <option value="">เลือกซัพพลายเออร์</option>
                        <option value="บจก. โอเชี่ยนซัพพลายเออร์ จำกัด (Ocean Supplier)">บจก. โอเชี่ยนซัพพลายเออร์ จำกัด
                            (Ocean Supplier)</option>
                        <option value="Metallic Corporation Limited (MCC / Metallic Steel Center)">Metallic Corporation
                            Limited (MCC / Metallic Steel Center)</option>
                        <option value="Millcon Steel (MILL)">Millcon Steel (MILL)</option>
                        <option value="Navasiam Steel Co., Ltd.">Navasiam Steel Co., Ltd.</option>
                        <option value="กิจไพบูลย์ เม็ททอล">กิจไพบูลย์ เม็ททอล</option>
                        <option value="Chuephaibul Steel (เชื้อไพบูลย์ สตีล)">Chuephaibul Steel (เชื้อไพบูลย์ สตีล)
                        </option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="button" class="cancel-btn" onclick="closeModal()">ยกเลิก</button>
                    <button type="submit" class="save-btn">บันทึก</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Product View Modal -->
    <div id="productViewModal" class="modal">
        <div class="modal-content product-view-modal">
            <div class="modal-header">
                <h2 id="viewModalTitle"><i class="fas fa-eye"></i> รายละเอียดสินค้า</h2>
                <button class="close-btn" onclick="closeViewModal()">&times;</button>
            </div>

            <div class="product-view-content">
                <!-- Product Header -->
                <div class="product-view-header">
                    <div class="product-view-code">
                        <i class="fas fa-barcode"></i>
                        <span id="viewProductCode">-</span>
                    </div>
                    <div class="product-view-category">
                        <span id="viewProductCategory" class="category-badge">-</span>
                    </div>
                </div>

                <!-- Product Main Info -->
                <div class="product-view-main">
                    <div class="product-view-images">
                        <div class="main-image-container" id="viewMainImageContainer">
                            <div class="no-image-placeholder">
                                <i class="fas fa-image"></i>
                                <span>ไม่มีรูปภาพ</span>
                            </div>
                        </div>
                        <div class="thumbnail-gallery" id="viewThumbnailGallery">
                            <!-- Thumbnails will be inserted here -->
                        </div>
                    </div>

                    <div class="product-view-details">
                        <h3 id="viewProductName">-</h3>
                        <p id="viewProductDescription" class="product-description-text">-</p>

                        <div class="product-view-specs">
                            <div class="spec-item">
                                <label><i class="fas fa-layer-group"></i> ล็อต:</label>
                                <span id="viewProductLot" class="lot-value">-</span>
                            </div>
                            <div class="spec-item">
                                <label><i class="fas fa-warehouse"></i> จำนวนคงเหลือ:</label>
                                <span id="viewProductStock" class="stock-value">-</span>
                            </div>
                            <div class="spec-item">
                                <label><i class="fas fa-tag"></i> ราคาต่อหน่วย:</label>
                                <span id="viewProductPrice" class="price-value">-</span>
                            </div>
                            <div class="spec-item">
                                <label><i class="fas fa-calendar-alt"></i> วันที่รับเข้า:</label>
                                <span id="viewProductDate" class="date-value">-</span>
                            </div>
                            <div class="spec-item">
                                <label><i class="fas fa-truck"></i> ซัพพลายเออร์:</label>
                                <span id="viewProductSupplier" class="supplier-value">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dimensions Section -->
                <div class="product-view-dimensions" id="viewDimensionsSection">
                    <h4><i class="fas fa-ruler-combined"></i> ข้อมูลขนาดและน้ำหนัก</h4>
                    <div class="dimensions-grid" id="viewDimensionsGrid">
                        <!-- Dimensions will be inserted here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Gallery Modal -->
    <div id="imageGalleryModal" class="image-gallery-modal">
        <div class="gallery-content">
            <button class="gallery-close-btn" onclick="closeImageGallery()">&times;</button>
            <img id="galleryImage" class="gallery-image" src="" alt="">
            <div class="gallery-controls">
                <button class="gallery-nav-btn" id="prevImageBtn" onclick="navigateImage(-1)">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <div class="gallery-info">
                    <span id="currentImageIndex">1</span> / <span id="totalImages">1</span>
                </div>
                <button class="gallery-nav-btn" id="nextImageBtn" onclick="navigateImage(1)">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>

    <script src="products_admin.js"></script>

</body>

</html>