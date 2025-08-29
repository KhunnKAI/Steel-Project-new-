// Cart management system - แก้ไขปัญหาข้อมูลค้าง
class CartManager {
    constructor() {
        this.cart = this.loadCart();
        this.init();
    }

    // โหลดข้อมูลตะกร้าจาก localStorage
    loadCart() {
        try {
            const saved = localStorage.getItem('shopping_cart');
            return saved ? JSON.parse(saved) : {};
        } catch (error) {
            console.error('Error loading cart:', error);
            return {};
        }
    }

    // บันทึกข้อมูลตะกร้าลง localStorage
    saveCart() {
        try {
            localStorage.setItem('shopping_cart', JSON.stringify(this.cart));
        } catch (error) {
            console.error('Error saving cart:', error);
        }
    }

    // เพิ่มสินค้าลงตะกร้า - เพิ่มพารามิเตอร์น้ำหนัก
    addItem(productId, productName, price, quantity = 1, image = 'no-image.jpg', weight = 0) {
        if (!productId || !productName || !price) {
            console.error('Invalid product data');
            return false;
        }

        const itemKey = String(productId).trim();

        if (this.cart[itemKey]) {
            // ถ้ามีสินค้านี้อยู่แล้ว ให้เพิ่มจำนวน
            this.cart[itemKey].quantity += quantity;
        } else {
            // ถ้าไม่มี ให้เพิ่มใหม่
            this.cart[itemKey] = {
                id: itemKey,
                name: productName,
                price: parseFloat(price),
                quantity: quantity,
                weight: parseFloat(weight) || 0, // เพิ่มน้ำหนัก
                image: image,
                addedAt: new Date().toISOString()
            };
        }

        this.saveCart();
        this.updateCartDisplay();
        return true;
    }

    // อัพเดทจำนวนสินค้า
    updateQuantity(productId, newQuantity) {
        const itemKey = String(productId).trim();

        if (this.cart[itemKey]) {
            if (newQuantity <= 0) {
                this.removeItem(productId);
            } else {
                this.cart[itemKey].quantity = parseInt(newQuantity);
                this.saveCart();
                this.updateCartDisplay();
            }
        }
    }

    // เพิ่มจำนวนสินค้า
    increaseQuantity(productId) {
        const itemKey = String(productId).trim();
        if (this.cart[itemKey]) {
            this.cart[itemKey].quantity++;
            this.saveCart();
            this.updateCartDisplay();
        }
    }

    // ลดจำนวนสินค้า
    decreaseQuantity(productId) {
        const itemKey = String(productId).trim();
        if (this.cart[itemKey]) {
            if (this.cart[itemKey].quantity > 1) {
                this.cart[itemKey].quantity--;
                this.saveCart();
                this.updateCartDisplay();
            }
        }
    }

    // ลบสินค้าจากตะกร้า
    removeItem(productId) {
        const itemKey = String(productId).trim();
        if (this.cart[itemKey]) {
            delete this.cart[itemKey];
            this.saveCart();
            this.updateCartDisplay();
        }
    }

    // ล้างตะกร้าทั้งหมด
    clearCart() {
        this.cart = {};
        this.saveCart();
        this.updateCartDisplay();
    }

    // คำนวณจำนวนสินค้าทั้งหมด
    getTotalItems() {
        return Object.values(this.cart).reduce((total, item) => total + item.quantity, 0);
    }

    // คำนวณราคารวม
    getTotalPrice() {
        return Object.values(this.cart).reduce((total, item) => total + (item.price * item.quantity), 0);
    }

    // คำนวณน้ำหนักรวม
    getTotalWeight() {
        return Object.values(this.cart).reduce((total, item) => total + ((item.weight || 0) * item.quantity), 0);
    }

    // ดึงข้อมูลสินค้าในตะกร้า
    getCartItems() {
        return Object.values(this.cart);
    }

    // อัพเดทการแสดงผลตะกร้า
    updateCartDisplay() {
        const totalItems = this.getTotalItems();

        // อัพเดท badge ในหน้า header
        const cartBadge = document.getElementById('cartBadge');
        if (cartBadge) {
            cartBadge.textContent = totalItems;
            cartBadge.style.display = totalItems > 0 ? 'flex' : 'none';
        }

        // อัพเดท global variable ถ้ามี - แก้ไขให้ sync กัน
        if (typeof window.cartCount !== 'undefined') {
            window.cartCount = totalItems;
        }

        // อัพเดทหน้าตะกร้าถ้าอยู่ในหน้านั้น
        this.updateCartPage();

        // Dispatch event สำหรับให้ส่วนอื่นๆ รับรู้
        window.dispatchEvent(new CustomEvent('cartUpdated', {
            detail: {
                totalItems: totalItems,
                totalPrice: this.getTotalPrice(),
                totalWeight: this.getTotalWeight(),
                items: this.getCartItems()
            }
        }));
    }

    // อัพเดทหน้าตะกร้าสินค้า
    updateCartPage() {
        const cartContainer = document.querySelector('.cart-section');
        if (!cartContainer) return;

        const items = this.getCartItems();

        if (items.length === 0) {
            cartContainer.innerHTML = `
                <h1 class="cart-title">ตะกร้าสินค้า</h1>
                <div class="empty-cart">
                    <div style="text-align: center; padding: 60px 20px; color: #666;">
                        <div style="font-size: 48px; margin-bottom: 20px;">🛒</div>
                        <h3 style="margin-bottom: 10px;">ตะกร้าสินค้าของคุณว่างเปล่า</h3>
                        <p>เพิ่มสินค้าลงในตะกร้า</p>
                        <button onclick="window.location.href='home.php'" style="
                            margin-top: 20px; 
                            padding: 12px 24px; 
                            background: #051A37; 
                            color: white; 
                            border: none; 
                            border-radius: 8px; 
                            cursor: pointer;
                            font-size: 16px;
                        ">ดูสินค้า</button>
                    </div>
                </div>
            `;

            // ลบ summary section เมื่อตะกร้าว่าง
            const summarySection = document.querySelector('.summary-section');
            if (summarySection) {
                summarySection.style.display = 'none';
            }
            return;
        }

        // แสดง summary section เมื่อมีสินค้า
        const summarySection = document.querySelector('.summary-section');
        if (summarySection) {
            summarySection.style.display = 'block';
        }

        let cartHTML = '<h1 class="cart-title">ตะกร้าสินค้า</h1>';

        items.forEach(item => {
            const itemTotal = item.price * item.quantity;
            const itemWeight = (item.weight || 0) * item.quantity;

            cartHTML += `
                <div class="cart-item" data-product-id="${item.id}">
                    <div class="item-image">
                        ${item.image !== 'no-image.jpg'
                    ? `<img src="${item.image}" alt="${item.name}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">`
                    : `<span style="color: #888; font-size: 12px;">ภาพสินค้า</span>`
                }
                    </div>
                    <div class="item-details">
                        <div class="item-name">${item.name}</div>
                        <div class="item-desc">รายละเอียด</div>
                        <div class="item-price">฿${item.price.toLocaleString()}</div>
                        ${item.weight > 0 ? `<div class="item-weight" style="color: #666; font-size: 14px;">น้ำหนัก: ${item.weight} กก./ชิ้น</div>` : ''}
                    </div>
                    <div class="quantity-controls">
                        <button class="qty-btn" onclick="cartManager.decreaseQuantity('${item.id}')">-</button>
                        <input type="number" value="${item.quantity}" min="1" class="qty-input" 
                               onchange="cartManager.updateQuantity('${item.id}', this.value)">
                        <button class="qty-btn" onclick="cartManager.increaseQuantity('${item.id}')">+</button>
                    </div>
                    <div class="item-total">
                        <div style="color: #27ae60; font-weight: bold; font-size: 18px;">฿${itemTotal.toLocaleString()}</div>
                        ${itemWeight > 0 ? `<div style="color: #666; font-size: 14px;">${itemWeight.toFixed(2)} กก.</div>` : ''}
                    </div>
                    <button class="delete-btn" onclick="cartManager.removeItem('${item.id}')">ลบ</button>
                </div>
            `;
        });

        cartContainer.innerHTML = cartHTML;

        // อัพเดทสรุปยอด
        this.updateSummary();
    }

    // อัพเดทสรุปยอดรวม
    updateSummary() {
        const totalItems = this.getTotalItems();
        const subtotal = this.getTotalPrice();
        const totalWeight = this.getTotalWeight();

        const summarySection = document.querySelector('.summary-section');
        if (summarySection && totalItems > 0) {
            summarySection.innerHTML = `
                <h2 class="summary-title">สรุปยอด</h2>
                
                <div class="summary-row">
                    <span>จำนวนสินค้า</span>
                    <span>${totalItems} รายการ</span>
                </div>
                
                <div class="summary-row">
                    <span>ราคาสินค้ารวม</span>
                    <span>฿${subtotal.toLocaleString()}</span>
                </div>
                
                ${totalWeight > 0 ? `
                <div class="summary-row">
                    <span>น้ำหนักรวม</span>
                    <span>${totalWeight.toFixed(2)} กก.</span>
                </div>
                ` : ''}
                
                <div class="summary-row">
                    <span style="color: #666; font-style: italic;">ดำเนินการต่อเพื่อคำนวณค่าจัดส่ง</span>
                </div>
                
                <div class="summary-row total" style="border-top: 2px solid #eee; padding-top: 20px;">
                    <span>ยอดรวม (ยังไม่รวมค่าจัดส่ง)</span>
                    <span>฿${subtotal.toLocaleString()}</span>
                </div>
                
                <button class="checkout-btn" onclick="cartManager.checkout()">ดำเนินการสั่งซื้อ</button>
                <button class="clear-cart-btn" onclick="cartManager.clearCart()" style="
                    width: 100%; 
                    background: #6c757d; 
                    color: white; 
                    border: none; 
                    padding: 10px; 
                    border-radius: 8px; 
                    font-size: 14px; 
                    cursor: pointer; 
                    margin-top: 10px;
                ">ลบสินค้าทั้งหมด</button>
            `;
        }
    }

    // ฟังก์ชันชำระเงิน
    async checkout() {
        console.log('=== CHECKOUT DEBUG START ===');

        // ดึงสินค้าจาก cart
        const items = this.getCartItems();
        if (!items || items.length === 0) {
            alert('ตะกร้าสินค้าว่างเปล่า');
            console.warn('Cart is empty');
            return;
        }

        // เตรียม request payload
        const requestData = {
            items: items.map(item => ({
                product_id: item.id, // ใช้ key ที่ตรงกับ PHP
                quantity: item.quantity
            })),
            total_amount: this.getTotalPrice(),
            total_weight: this.getTotalWeight(),
            timestamp: new Date().toISOString()
        };

        console.log('Request payload:', requestData);

        // path ของ checkout.php
        const checkoutPath = './controllers/checkout.php';

        const checkoutBtn = document.querySelector('.checkout-btn');
        if (checkoutBtn) {
            checkoutBtn.disabled = true;
            checkoutBtn.textContent = 'กำลังประมวลผล...';
        }

        try {
            const response = await fetch(checkoutPath, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin', // ส่ง session cookie
                body: JSON.stringify(requestData)
            });

            console.log('Network request sent to:', checkoutPath);
            console.log('Response status:', response.status, response.statusText);

            // อ่าน response เป็น text ก่อน
            const responseText = await response.text();
            console.log('Raw response:', responseText);

            // parse JSON อย่างปลอดภัย
            let result;
            try {
                result = JSON.parse(responseText);
                console.log('Parsed JSON response:', result);
            } catch (jsonErr) {
                console.error('Failed to parse JSON:', jsonErr);
                throw new Error('Response is not valid JSON');
            }

            // ตรวจสอบ success / fail
            if (result.success) {
                console.log('Checkout success:', result);

                // เก็บข้อมูล checkout ใน localStorage
                localStorage.setItem('checkout_data', JSON.stringify({
                    items: items,
                    totalItems: this.getTotalItems(),
                    totalAmount: requestData.total_amount,
                    totalWeight: requestData.total_weight,
                    insertedItems: result.inserted_items,
                    serverResponse: result,
                    timestamp: new Date().toISOString()
                }));

                // ล้าง cart และไปหน้า payment
                this.clearCart();
                window.location.href = 'payment.php';

            } else if (result.redirect) {
                console.warn('User needs to login:', result.message);
                alert(result.message || 'กรุณาล็อกอินก่อนสั่งซื้อ');
                window.location.href = result.redirect;

            } else {
                console.error('Checkout failed:', result.message, result.error || '');
                throw new Error(result.message || 'เกิดข้อผิดพลาดในการสั่งซื้อ');
            }

        } catch (error) {
            console.error('Checkout exception:', error);
            alert('เกิดข้อผิดพลาดในการสั่งซื้อ: ' + error.message);

        } finally {
            if (checkoutBtn) {
                checkoutBtn.disabled = false;
                checkoutBtn.textContent = 'ดำเนินการสั่งซื้อ';
            }
            console.log('=== CHECKOUT DEBUG END ===');
        }
    }


    getCheckoutData() {
        try {
            const saved = localStorage.getItem('checkout_data');
            return saved ? JSON.parse(saved) : null;
        } catch (error) {
            console.error('Error loading checkout data:', error);
            return null;
        }
    }

    clearCheckoutData() {
        try {
            localStorage.removeItem('checkout_data');
            console.log('Checkout data cleared');
        } catch (error) {
            console.error('Error clearing checkout data:', error);
        }
    }

    // เริ่มต้นระบบ
    init() {
        console.log('Cart Manager initialized');

        // ตรวจสอบและซิงค์ข้อมูลกับ global cartCount
        const totalItems = this.getTotalItems();
        if (typeof window.cartCount !== 'undefined' && window.cartCount !== totalItems) {
            console.log('Syncing cart count:', window.cartCount, '->', totalItems);
            window.cartCount = totalItems;
        }

        this.updateCartDisplay();

        // Listen for storage changes (สำหรับ multiple tabs)
        window.addEventListener('storage', (e) => {
            if (e.key === 'shopping_cart') {
                this.cart = this.loadCart();
                this.updateCartDisplay();
            }
        });
    }

    // เพิ่มฟังก์ชันสำหรับ debug
    debugCart() {
        console.log('=== Cart Debug Info ===');
        console.log('Cart data:', this.cart);
        console.log('Total items:', this.getTotalItems());
        console.log('Total price:', this.getTotalPrice());
        console.log('Total weight:', this.getTotalWeight());
        console.log('Global cartCount:', typeof window.cartCount !== 'undefined' ? window.cartCount : 'undefined');
        console.log('localStorage:', localStorage.getItem('shopping_cart'));
        console.log('=====================');
    }
}

// สร้าง instance ของ CartManager
window.cartManager = new CartManager();

// ฟังก์ชันสำหรับใช้ในหน้าอื่นๆ - เพิ่มพารามิเตอร์น้ำหนัก
window.addToCart = function (productName, productId, price = 199, image = 'no-image.jpg', weight = 0) {
    console.log(`Adding to cart: ${productName} (ID: ${productId})`);

    if (cartManager.addItem(productId, productName, price, 1, image, weight)) {
        // แสดงการแจ้งเตือน
        if (typeof showToast === 'function') {
            showToast(`เพิ่ม "${productName}" ลงในตะกร้าแล้ว!`);
        } else {
            alert(`เพิ่ม "${productName}" ลงในตะกร้าแล้ว!`);
        }

        // เอฟเฟกต์กับปุ่ม
        const button = event.target;
        if (button) {
            const originalText = button.textContent;
            button.textContent = 'เพิ่มแล้ว!';
            button.style.background = '#28a745';

            setTimeout(() => {
                button.textContent = originalText;
                button.style.background = '';
            }, 1500);
        }
    }
};

// ฟังก์ชันสำหรับ legacy code - ซิงค์กับ cartManager
window.increaseQty = function (itemId) {
    cartManager.increaseQuantity(itemId);
};

window.decreaseQty = function (itemId) {
    cartManager.decreaseQuantity(itemId);
};

window.updateTotal = function (itemId) {
    // Legacy function - now handled automatically
    console.log('updateTotal called for item:', itemId);
    cartManager.updateCartDisplay();
};

window.updateSummary = function () {
    cartManager.updateSummary();
};

window.removeItem = function (button) {
    const cartItem = button.closest('.cart-item');
    if (cartItem) {
        const productId = cartItem.dataset.productId;
        if (productId) {
            cartManager.removeItem(productId);
        }
    }
};

// ฟังก์ชันสำหรับล้างข้อมูลตะกร้า (สำหรับ debug)
window.clearCartData = function () {
    if (confirm('คุณต้องการล้างข้อมูลตะกร้าทั้งหมดหรือไม่?')) {
        cartManager.clearCart();
        console.log('Cart cleared!');
    }
};

// ฟังก์ชันสำหรับ debug ตะกร้า
window.debugCart = function () {
    cartManager.debugCart();
};

// Event listener สำหรับอัพเดทตะกร้า
window.addEventListener('cartUpdated', function (e) {
    const { totalItems, totalPrice, totalWeight } = e.detail;

    // อัพเดท global cartCount ให้ตรงกัน
    if (typeof window.cartCount !== 'undefined') {
        window.cartCount = totalItems;
    }

    // อัพเดท cart badge
    if (typeof updateCartBadge === 'function') {
        updateCartBadge();
    }

});

// Initialize เมื่อ DOM โหลดเสร็จ
document.addEventListener('DOMContentLoaded', function () {
    console.log('Cart system ready');

    // ล้างข้อมูลเก่าในหน้า cart.php ที่เป็น hardcode
    const hardcodedItems = document.querySelectorAll('.cart-item[data-product-id]');
    if (hardcodedItems.length === 0) {
        // ถ้าไม่มี data-product-id แปลว่าเป็นข้อมูล hardcode ใน cart.php
        const allCartItems = document.querySelectorAll('.cart-item');
        if (allCartItems.length > 0 && !allCartItems[0].dataset.productId) {
            console.log('Removing hardcoded cart items...');
            // ให้ cartManager จัดการแสดงผลใหม่
            cartManager.updateCartPage();
        }
    }

    cartManager.updateCartDisplay();
});

// เพิ่มคำสั่ง console สำหรับ debug
console.log('Cart Manager loaded. Use debugCart() or clearCartData() for debugging.');