// Enhanced Cart management system - ระบบจัดการน้ำหนักรวมไม่เกิน 1000 kg
class CartManager {
    constructor() {
        this.cart = this.loadCart();
        this.maxWeight = 1000; // น้ำหนักสูงสุด 1000 กก.
        this.warningWeight = 800; // แจ้งเตือนเมื่อใกล้ขีดจำกัด
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

    // เพิ่มสินค้าลงตะกร้า
    addItem(productId, productName, price, quantity = 1, image = 'no-image.jpg', weight = 0) {
        if (!productId || !productName || price === undefined || price === null) {
            console.error('Invalid product data:', { productId, productName, price });
            return false;
        }

        const itemKey = String(productId).trim();
        const numericPrice = parseFloat(price);
        const numericWeight = parseFloat(weight) || 0;
        const numericQuantity = parseInt(quantity) || 1;

        if (isNaN(numericPrice) || numericPrice < 0) {
            console.error('Invalid price:', price);
            return false;
        }

        // ตรวจสอบน้ำหนักที่จะเพิ่ม
        const additionalWeight = numericWeight * numericQuantity;
        const weightCheckResult = this.validateWeightAddition(additionalWeight, productName);
        
        if (!weightCheckResult.canAdd) {
            this.showWeightAlert(weightCheckResult.message, weightCheckResult.type);
            return false;
        }

        if (this.cart[itemKey]) {
            // ถ้ามีสินค้านี้อยู่แล้ว ให้เพิ่มจำนวน
            this.cart[itemKey].quantity += numericQuantity;
        } else {
            // ถ้าไม่มี ให้เพิ่มใหม่
            this.cart[itemKey] = {
                id: itemKey,
                name: productName,
                price: numericPrice,
                quantity: numericQuantity,
                weight: numericWeight,
                image: image,
                addedAt: new Date().toISOString()
            };
        }

        console.log(`Added to cart: ${productName} (${itemKey}) - ฿${numericPrice} x${numericQuantity}, Weight: ${additionalWeight.toFixed(2)} กก.`);
        
        this.saveCart();
        this.updateCartDisplay();
        
        // แสดงการแจ้งเตือนถ้าน้ำหนักใกล้ขีดจำกัด
        this.checkWeightWarning();
        
        return true;
    }

    // ตรวจสอบการเพิ่มน้ำหนัก
    validateWeightAddition(additionalWeight, productName = '') {
        const currentWeight = this.getTotalWeight();
        const newTotalWeight = currentWeight + additionalWeight;
        
        if (newTotalWeight > this.maxWeight) {
            const exceededWeight = newTotalWeight - this.maxWeight;
            return {
                canAdd: false,
                type: 'error',
                message: `❌ ไม่สามารถเพิ่ม "${productName}" ได้\n\n` +
                        `🏋️ น้ำหนักปัจจุบัน: ${currentWeight.toFixed(2)} กก.\n` +
                        `➕ น้ำหนักที่จะเพิ่ม: ${additionalWeight.toFixed(2)} กก.\n` +
                        `⚖️ น้ำหนักรวม: ${newTotalWeight.toFixed(2)} กก.\n` +
                        `🚫 เกินขีดจำกัด: ${exceededWeight.toFixed(2)} กก.\n\n` +
                        `💡 กรุณาลดจำนวนสินค้าหรือเลือกสินค้าที่มีน้ำหนักน้อยกว่า`
            };
        }
        
        return { canAdd: true };
    }

    // แสดงการแจ้งเตือนน้ำหนัก
    showWeightAlert(message, type = 'info') {
        const alertStyles = {
            error: { bg: '#fee', border: '#e74c3c', color: '#c0392b' },
            warning: { bg: '#fff3cd', border: '#f39c12', color: '#d68910' },
            info: { bg: '#e8f4fd', border: '#3498db', color: '#2980b9' }
        };
        
        const style = alertStyles[type] || alertStyles.info;
        
        // สร้าง modal สำหรับแสดงข้อความ
        const modal = document.createElement('div');
        modal.style.cssText = `
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); z-index: 10000; display: flex;
            align-items: center; justify-content: center;
        `;
        
        const alertBox = document.createElement('div');
        alertBox.style.cssText = `
            background: ${style.bg}; border: 2px solid ${style.border};
            color: ${style.color}; padding: 20px; border-radius: 12px;
            max-width: 400px; width: 90%; box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        `;
        
        alertBox.innerHTML = `
            <div style="white-space: pre-line; line-height: 1.5; margin-bottom: 15px;">
                ${message}
            </div>
            <button style="
                background: ${style.border}; color: white; border: none;
                padding: 8px 16px; border-radius: 6px; cursor: pointer;
                font-size: 14px; font-weight: bold;
            ">ตกลง</button>
        `;
        
        modal.appendChild(alertBox);
        document.body.appendChild(modal);
        
        // ปิด modal เมื่อคลิกปุ่มหรือพื้นหลัง
        const closeModal = () => document.body.removeChild(modal);
        alertBox.querySelector('button').onclick = closeModal;
        modal.onclick = (e) => e.target === modal && closeModal();
    }

    // ตรวจสอบและแจ้งเตือนน้ำหนัก
    checkWeightWarning() {
        const currentWeight = this.getTotalWeight();
        const weightPercentage = (currentWeight / this.maxWeight) * 100;
        
        if (currentWeight >= this.warningWeight && currentWeight < this.maxWeight) {
            const remainingWeight = this.maxWeight - currentWeight;
            this.showWeightAlert(
                `⚠️ น้ำหนักใกล้ขีดจำกัดแล้ว!\n\n` +
                `🏋️ น้ำหนักปัจจุบัน: ${currentWeight.toFixed(2)} กก. (${weightPercentage.toFixed(1)}%)\n` +
                `📦 สามารถเพิ่มได้อีก: ${remainingWeight.toFixed(2)} กก.\n` +
                `🚚 ขีดจำกัดสูงสุด: ${this.maxWeight} กก.`,
                'warning'
            );
        }
    }

    // อัพเดทจำนวนสินค้า
    updateQuantity(productId, newQuantity) {
        const itemKey = String(productId).trim();

        if (this.cart[itemKey]) {
            const newQty = parseInt(newQuantity);
            
            if (newQty <= 0) {
                this.removeItem(productId);
                return true;
            }

            // ตรวจสอบน้ำหนักเมื่ออัพเดทจำนวน
            const itemWeight = this.cart[itemKey].weight || 0;
            const currentWeight = this.getTotalWeight();
            const oldItemWeight = this.cart[itemKey].quantity * itemWeight;
            const newItemWeight = newQty * itemWeight;
            const weightDifference = newItemWeight - oldItemWeight;
            const newTotalWeight = currentWeight + weightDifference;
            
            if (newTotalWeight > this.maxWeight) {
                const maxPossibleQty = Math.floor((this.maxWeight - (currentWeight - oldItemWeight)) / itemWeight);
                this.showWeightAlert(
                    `❌ ไม่สามารถอัพเดทจำนวนเป็น ${newQty} ได้\n\n` +
                    `🏋️ น้ำหนักปัจจุบัน: ${currentWeight.toFixed(2)} กก.\n` +
                    `⚖️ น้ำหนักใหม่: ${newTotalWeight.toFixed(2)} กก.\n` +
                    `🚫 เกินขีดจำกัด: ${(newTotalWeight - this.maxWeight).toFixed(2)} กก.\n\n` +
                    `💡 จำนวนสูงสุดที่เพิ่มได้: ${maxPossibleQty} ชิ้น`,
                    'error'
                );
                
                // รีเซ็ตค่า input กลับเป็นเดิม
                const input = document.querySelector(`input[onchange*="${productId}"]`);
                if (input) {
                    input.value = this.cart[itemKey].quantity;
                }
                return false;
            }
            
            this.cart[itemKey].quantity = newQty;
            this.saveCart();
            this.updateCartDisplay();
            return true;
        }
        return false;
    }

    // เพิ่มจำนวนสินค้า
    increaseQuantity(productId) {
        const itemKey = String(productId).trim();
        if (this.cart[itemKey]) {
            const itemWeight = this.cart[itemKey].weight || 0;
            const additionalWeight = itemWeight;
            
            const weightCheckResult = this.validateWeightAddition(additionalWeight, this.cart[itemKey].name);
            
            if (!weightCheckResult.canAdd) {
                this.showWeightAlert(weightCheckResult.message, weightCheckResult.type);
                return false;
            }
            
            this.cart[itemKey].quantity++;
            this.saveCart();
            this.updateCartDisplay();
            this.checkWeightWarning();
            return true;
        }
        return false;
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
            return true;
        }
        return false;
    }

    // ลบสินค้าจากตะกร้า
    removeItem(productId) {
        const itemKey = String(productId).trim();
        if (this.cart[itemKey]) {
            const itemName = this.cart[itemKey].name;
            const itemWeight = (this.cart[itemKey].weight || 0) * this.cart[itemKey].quantity;
            
            delete this.cart[itemKey];
            this.saveCart();
            this.updateCartDisplay();
            
            console.log(`Removed from cart: ${itemName}, Weight freed: ${itemWeight.toFixed(2)} กก.`);
            return true;
        }
        return false;
    }

    // ล้างตะกร้าทั้งหมด
    clearCart() {
        const totalWeight = this.getTotalWeight();
        this.cart = {};
        this.saveCart();
        this.updateCartDisplay();
        console.log(`Cart cleared. Weight freed: ${totalWeight.toFixed(2)} กก.`);
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

    // ตรวจสอบว่าสามารถเพิ่มน้ำหนักได้หรือไม่
    canAddWeight(additionalWeight) {
        const currentWeight = this.getTotalWeight();
        const newTotalWeight = currentWeight + additionalWeight;
        return newTotalWeight <= this.maxWeight;
    }

    // คำนวณน้ำหนักที่เหลือ
    getRemainingWeight() {
        return this.maxWeight - this.getTotalWeight();
    }

    // คำนวณเปอร์เซ็นต์น้ำหนักที่ใช้ไป
    getWeightPercentage() {
        return (this.getTotalWeight() / this.maxWeight) * 100;
    }

    // คำนวณจำนวนสูงสุดที่สามารถเพิ่มได้สำหรับสินค้าชิ้นนั้น
    getMaxAddableQuantity(productWeight) {
        if (!productWeight || productWeight <= 0) return 9999; // ไม่มีข้อจำกัดถ้าไม่มีน้ำหนัก
        
        const remainingWeight = this.getRemainingWeight();
        return Math.floor(remainingWeight / productWeight);
    }

    // ตรวจสอบน้ำหนักรวมไม่ให้เกิน 1000 กก.
    validateWeightLimit() {
        const totalWeight = this.getTotalWeight();
        if (totalWeight > this.maxWeight) {
            this.showWeightAlert(
                `🚫 น้ำหนักรวมเกินขีดจำกัด!\n\n` +
                `⚖️ น้ำหนักปัจจุบัน: ${totalWeight.toFixed(2)} กก.\n` +
                `🚚 ขีดจำกัดสูงสุด: ${this.maxWeight} กก.\n` +
                `❌ เกินขีดจำกัด: ${(totalWeight - this.maxWeight).toFixed(2)} กก.\n\n` +
                `💡 กรุณาลดจำนวนสินค้าก่อนดำเนินการต่อ`,
                'error'
            );
            return false;
        }
        return true;
    }

    // ดึงข้อมูลสินค้าในตะกร้า
    getCartItems() {
        return Object.values(this.cart);
    }

    // สร้าง weight indicator bar
    createWeightIndicator() {
        const percentage = this.getWeightPercentage();
        const currentWeight = this.getTotalWeight();
        const remainingWeight = this.getRemainingWeight();
        
        let barColor = '#27ae60'; // เขียว
        if (percentage >= 80) barColor = '#e74c3c'; // แดง
        else if (percentage >= 60) barColor = '#f39c12'; // ส้ม
        
        return `
            <div class="weight-indicator" style="margin: 15px 0;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                    <span style="font-weight: bold; color: #2c3e50;">น้ำหนักรวม</span>
                    <span style="color: ${barColor}; font-weight: bold;">
                        ${currentWeight.toFixed(2)} / ${this.maxWeight} กก.
                    </span>
                </div>
                <div style="background: #ecf0f1; border-radius: 10px; height: 20px; overflow: hidden;">
                    <div style="
                        background: ${barColor}; 
                        height: 100%; 
                        width: ${Math.min(percentage, 100)}%; 
                        transition: all 0.3s ease;
                        border-radius: 10px;
                        ${percentage >= 95 ? 'animation: pulse 1s infinite;' : ''}
                    "></div>
                </div>
                <div style="display: flex; justify-content: space-between; margin-top: 5px; font-size: 12px; color: #7f8c8d;">
                    <span>${percentage.toFixed(1)}% ของขีดจำกัด</span>
                    <span>เหลือ ${remainingWeight.toFixed(2)} กก.</span>
                </div>
                ${percentage >= 80 && percentage < 100 ? `
                    <div style="
                        background: #fff3cd; border: 1px solid #f39c12; 
                        color: #d68910; padding: 8px; border-radius: 6px; 
                        margin-top: 8px; font-size: 12px; text-align: center;
                    ">
                        ⚠️ น้ำหนักใกล้ขีดจำกัดแล้ว กรุณาระวัง !
                    </div>
                ` : ''}
                ${percentage == 100 ? `
                    <div style="
                        background:rgb(255, 205, 205); border: 1px solidrgb(243, 18, 18); 
                        color:rgb(214, 16, 16); padding: 8px; border-radius: 6px; 
                        margin-top: 8px; font-size: 12px; text-align: center;
                    ">
                        ⚠️ น้ำหนักถึงขีดจำกัดแล้ว !
                    </div>
                ` : ''}
            </div>
            <style>
                @keyframes pulse {
                    0%, 100% { opacity: 1; }
                    50% { opacity: 0.7; }
                }
            </style>
        `;
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

        // อัพเดท global variable ถ้ามี
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
                weightPercentage: this.getWeightPercentage(),
                remainingWeight: this.getRemainingWeight(),
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

            const summarySection = document.querySelector('.summary-section');
            if (summarySection) {
                summarySection.style.display = 'none';
            }
            return;
        }

        const summarySection = document.querySelector('.summary-section');
        if (summarySection) {
            summarySection.style.display = 'block';
        }

        let cartHTML = '<h1 class="cart-title">ตะกร้าสินค้า</h1>';

        // เพิ่ม Weight Indicator
        if (this.getTotalWeight() > 0) {
            cartHTML += this.createWeightIndicator();
        }

        items.forEach(item => {
            const itemTotal = item.price * item.quantity;
            const itemWeight = (item.weight || 0) * item.quantity;
            const maxQty = this.getMaxAddableQuantity(item.weight || 0) + item.quantity;

            cartHTML += `
                <div class="cart-item" data-product-id="${item.id}">
                    <div class="item-image">
                        ${item.image !== 'no-image.jpg'
                    ? `<img src="${item.image}" alt="${item.name}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">`
                    : `<span style="color: #888; font-size: 12px;">ไม่มีรูปภาพ</span>`
                }
                    </div>
                    <div class="item-details">
                        <div class="item-name">${item.name}</div>
                        <div class="item-price">฿${item.price.toLocaleString()}</div>
                        ${item.weight > 0 ? `
                            <div class="item-weight" style="color: #666; font-size: 14px;">
                                น้ำหนัก: ${item.weight} กก./ชิ้น
                                ${maxQty < 999 ? `<br><span style="color: #e74c3c; font-size: 12px;">สูงสุด: ${maxQty} ชิ้น</span>` : ''}
                            </div>
                        ` : ''}
                    </div>
                    <div class="quantity-controls">
                        <button class="qty-btn" onclick="cartManager.decreaseQuantity('${item.id}')" 
                                ${item.quantity <= 1 ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''}>-</button>
                        <input type="number" value="${item.quantity}" min="1" max="${maxQty}" class="qty-input" 
                               onchange="cartManager.updateQuantity('${item.id}', this.value)"
                               title="สูงสุด ${maxQty} ชิ้น">
                        <button class="qty-btn" onclick="cartManager.increaseQuantity('${item.id}')"
                                ${item.quantity >= maxQty ? 'disabled style="opacity: 0.5; cursor: not-allowed;" title="ถึงขีดจำกัดน้ำหนักแล้ว"' : ''}>+</button>
                    </div>
                    <div class="item-total">
                        <div style="color: #27ae60; font-weight: bold; font-size: 18px;">฿${itemTotal.toLocaleString()}</div>
                        ${itemWeight > 0 ? `<div style="color: #666; font-size: 14px;">${itemWeight.toFixed(2)} กก.</div>` : ''}
                    </div>
                    <button class="delete-btn" onclick="cartManager.removeItem('${item.id}')" title="ลบสินค้า">🗑️</button>
                </div>
            `;
        });

        cartContainer.innerHTML = cartHTML;
        this.updateSummary();
    }

    // อัพเดทสรุปยอดรวม
    updateSummary() {
        const totalItems = this.getTotalItems();
        const subtotal = this.getTotalPrice();
        const totalWeight = this.getTotalWeight();
        const weightPercentage = this.getWeightPercentage();

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
                <div class="summary-row ${totalWeight > this.warningWeight ? 'weight-warning' : ''}">
                    <span>น้ำหนักรวม</span>
                    <span>
                        ${totalWeight.toFixed(2)} กก. 
                        <span style="font-size: 12px; color: #7f8c8d;">(${weightPercentage.toFixed(1)}%)</span>
                        ${totalWeight > this.warningWeight ? '⚠️' : ''}
                    </span>
                </div>
                
                <div class="summary-row">
                    <span>น้ำหนักคงเหลือ</span>
                    <span style="color: ${this.getRemainingWeight() < 200 ? '#e74c3c' : '#27ae60'};">
                        ${this.getRemainingWeight().toFixed(2)} กก.
                    </span>
                </div>
                
                ${totalWeight > this.warningWeight && totalWeight < this.maxWeight ? `
                <div class="summary-row weight-alert" style="
                    background: #fff3cd; border: 1px solid #f39c12; 
                    color: #d68910; padding: 10px; border-radius: 6px; 
                    margin: 10px 0; font-size: 14px; text-align: center;
                ">
                    ⚠️ น้ำหนักใกล้ถึงขีดจำกัด (${this.maxWeight} กก.)
                </div>
                ` : ''}

                ${totalWeight == this.maxWeight ? `
                <div class="summary-row weight-alert" style="
                    background:rgb(255, 205, 205); border: 1px solidrgb(243, 18, 18); 
                    color:rgb(214, 16, 16); padding: 10px; border-radius: 6px; 
                    margin: 10px 0; font-size: 14px; text-align: center;
                ">
                    ⚠️ น้ำหนักถึงขีดจำกัดแล้ว (${this.maxWeight} กก.)
                </div>
                ` : ''}

                ` : ''}
                
                <div class="summary-row">
                    <span style="color: #666; font-style: italic;">ดำเนินการต่อเพื่อคำนวณค่าจัดส่ง</span>
                </div>
                
                <div class="summary-row total" style="border-top: 2px solid #eee; padding-top: 20px;">
                    <span>ยอดรวม (ยังไม่รวมค่าจัดส่ง)</span>
                    <span>฿${subtotal.toLocaleString()}</span>
                </div>
                
                <button class="checkout-btn" onclick="cartManager.checkout()" 
                        ${!this.validateWeightLimit() ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''}>
                    ${!this.validateWeightLimit() ? '⚠️ เกินขีดจำกัดน้ำหนัก' : 'ดำเนินการสั่งซื้อ'}
                </button>
                
                <div style="display: flex; gap: 10px; margin-top: 10px;">
                    <button class="clear-cart-btn" onclick="cartManager.clearCart()" style="
                        flex: 1;
                        background: #6c757d; 
                        color: white; 
                        border: none; 
                        padding: 10px; 
                        border-radius: 8px; 
                        font-size: 14px; 
                        cursor: pointer;
                    ">ลบทั้งหมด</button>
                    
                </div>
            `;
        }
    }

    // ฟังก์ชันปรับปรุงน้ำหนักอัตโนมัติ
    optimizeWeight() {
        const items = this.getCartItems();
        const totalWeight = this.getTotalWeight();
        
        if (totalWeight <= this.maxWeight) {
            this.showWeightAlert(
                `✅ น้ำหนักปัจจุบันอยู่ในขีดจำกัด\n\n` +
                `🏋️ น้ำหนักรวม: ${totalWeight.toFixed(2)} กก.\n` +
                `🚚 ขีดจำกัด: ${this.maxWeight} กก.\n` +
                `📏 เหลือ: ${this.getRemainingWeight().toFixed(2)} กก.`,
                'info'
            );
            return;
        }

        // เรียงสินค้าตามน้ำหนักต่อราคา (หาสินค้าที่คุ้มค่าที่สุด)
        const sortedItems = items.sort((a, b) => {
            const ratioA = a.weight > 0 ? a.price / a.weight : Infinity;
            const ratioB = b.weight > 0 ? b.price / b.weight : Infinity;
            return ratioB - ratioA; // เรียงจากคุ้มค่าที่สุด
        });

        let currentWeight = 0;
        const optimizedCart = {};
        const removedItems = [];

        // เลือกสินค้าที่คุ้มค่าที่สุดให้พอดีกับขีดจำกัด
        for (const item of sortedItems) {
            const itemWeight = item.weight || 0;
            
            if (itemWeight === 0) {
                // สินค้าไม่มีน้ำหนัก เพิ่มได้ทั้งหมด
                optimizedCart[item.id] = { ...item };
            } else {
                // คำนวณจำนวนที่เพิ่มได้สูงสุด
                const maxQty = Math.floor((this.maxWeight - currentWeight) / itemWeight);
                
                if (maxQty > 0) {
                    const finalQty = Math.min(item.quantity, maxQty);
                    optimizedCart[item.id] = { ...item, quantity: finalQty };
                    currentWeight += finalQty * itemWeight;
                    
                    if (finalQty < item.quantity) {
                        removedItems.push({
                            name: item.name,
                            removedQty: item.quantity - finalQty,
                            totalQty: item.quantity
                        });
                    }
                } else {
                    removedItems.push({
                        name: item.name,
                        removedQty: item.quantity,
                        totalQty: item.quantity
                    });
                }
            }
        }

        // อัพเดทตะกร้า
        this.cart = optimizedCart;
        this.saveCart();
        this.updateCartDisplay();

        // แสดงรายการที่ถูกลดหรือลบ
        if (removedItems.length > 0) {
            let message = `ปรับปรุงน้ำหนักเรียบร้อย!\n\n`;
            message += `น้ำหนักใหม่: ${this.getTotalWeight().toFixed(2)} กก.\n\n`;
            message += `รายการที่ถูกปรับปรุง:\n`;
            
            removedItems.forEach(removed => {
                if (removed.removedQty === removed.totalQty) {
                    message += `❌ ${removed.name}: ลบทั้งหมด (${removed.totalQty} ชิ้น)\n`;
                } else {
                    message += `⬇️ ${removed.name}: ลดจาก ${removed.totalQty} เป็น ${removed.totalQty - removed.removedQty} ชิ้น\n`;
                }
            });
            
            this.showWeightAlert(message, 'info');
        }
    }

    // ฟังก์ชันชำระเงิน
    async checkout() {
        console.log('=== CHECKOUT DEBUG START ===');

        const items = this.getCartItems();
        if (!items || items.length === 0) {
            alert('ตะกร้าสินค้าว่างเปล่า');
            console.warn('Cart is empty');
            return;
        }

        // ตรวจสอบน้ำหนักรวมไม่ให้เกิน 1000 กก. ก่อน checkout
        if (!this.validateWeightLimit()) {
            return;
        }

        const requestData = {
            items: items.map(item => ({
                product_id: item.id,
                quantity: item.quantity
            })),
            total_amount: this.getTotalPrice(),
            total_weight: this.getTotalWeight(),
            weight_percentage: this.getWeightPercentage(),
            timestamp: new Date().toISOString()
        };

        console.log('Request payload:', requestData);
        console.log('Weight summary:', {
            current: this.getTotalWeight(),
            max: this.maxWeight,
            percentage: this.getWeightPercentage(),
            remaining: this.getRemainingWeight()
        });

        const checkoutPath = './controllers/checkout.php';

        const checkoutBtn = document.querySelector('.checkout-btn');
        if (checkoutBtn) {
            checkoutBtn.disabled = true;
            checkoutBtn.textContent = '🔄 กำลังประมวลผล...';
        }

        try {
            const response = await fetch(checkoutPath, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify(requestData)
            });

            console.log('Network request sent to:', checkoutPath);
            console.log('Response status:', response.status, response.statusText);

            const responseText = await response.text();
            console.log('Raw response:', responseText);

            let result;
            try {
                result = JSON.parse(responseText);
                console.log('Parsed JSON response:', result);
            } catch (jsonErr) {
                console.error('Failed to parse JSON:', jsonErr);
                throw new Error('Response is not valid JSON');
            }

            if (result.success) {
                console.log('Checkout success:', result);

                localStorage.setItem('checkout_data', JSON.stringify({
                    items: items,
                    totalItems: this.getTotalItems(),
                    totalAmount: requestData.total_amount,
                    totalWeight: requestData.total_weight,
                    weightPercentage: requestData.weight_percentage,
                    insertedItems: result.inserted_items,
                    serverResponse: result,
                    timestamp: new Date().toISOString()
                }));

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
            this.showWeightAlert(
                `เกิดข้อผิดพลาดในการสั่งซื้อ\n\n` +
                `รายละเอียด: ${error.message}\n` +
                `กรุณาลองใหม่อีกครั้ง`,
                'error'
            );

        } finally {
            if (checkoutBtn) {
                checkoutBtn.disabled = false;
                checkoutBtn.textContent = '🛒 ดำเนินการสั่งซื้อ';
            }
            console.log('=== CHECKOUT DEBUG END ===');
        }
    }

    // ดึงข้อมูล checkout
    getCheckoutData() {
        try {
            const saved = localStorage.getItem('checkout_data');
            return saved ? JSON.parse(saved) : null;
        } catch (error) {
            console.error('Error loading checkout data:', error);
            return null;
        }
    }

    // ล้างข้อมูล checkout
    clearCheckoutData() {
        try {
            localStorage.removeItem('checkout_data');
            console.log('Checkout data cleared');
        } catch (error) {
            console.error('Error clearing checkout data:', error);
        }
    }

    // สร้างรายงานน้ำหนัก
    getWeightReport() {
        const items = this.getCartItems();
        const totalWeight = this.getTotalWeight();
        
        const report = {
            summary: {
                totalWeight: totalWeight,
                maxWeight: this.maxWeight,
                remainingWeight: this.getRemainingWeight(),
                percentage: this.getWeightPercentage(),
                status: totalWeight > this.maxWeight ? 'exceeded' : 
                       totalWeight > this.warningWeight ? 'warning' : 'normal'
            },
            items: items.map(item => ({
                name: item.name,
                quantity: item.quantity,
                unitWeight: item.weight || 0,
                totalWeight: (item.weight || 0) * item.quantity,
                weightPercentage: totalWeight > 0 ? (((item.weight || 0) * item.quantity) / totalWeight) * 100 : 0
            })).sort((a, b) => b.totalWeight - a.totalWeight)
        };
        
        return report;
    }

    // แสดงรายงานน้ำหนัก
    showWeightReport() {
        const report = this.getWeightReport();
        
        let message = `รายงานน้ำหนักตะกร้าสินค้า\n\n`;
        message += `น้ำหนักรวม: ${report.summary.totalWeight.toFixed(2)} กก. (${report.summary.percentage.toFixed(1)}%)\n`;
        message += `ขีดจำกัด: ${report.summary.maxWeight} กก.\n`;
        message += `คงเหลือ: ${report.summary.remainingWeight.toFixed(2)} กก.\n\n`;
        
        if (report.items.length > 0) {
            message += `📦 รายการสินค้า (เรียงตามน้ำหนัก):\n`;
            report.items.forEach((item, index) => {
                if (item.totalWeight > 0) {
                    message += `${index + 1}. ${item.name}\n`;
                    message += `   ${item.quantity} ชิ้น × ${item.unitWeight} กก. = ${item.totalWeight.toFixed(2)} กก. (${item.weightPercentage.toFixed(1)}%)\n`;
                }
            });
        }
        
        this.showWeightAlert(message, report.summary.status === 'exceeded' ? 'error' : 
                            report.summary.status === 'warning' ? 'warning' : 'info');
    }

    // เริ่มต้นระบบ
    init() {
        console.log('Enhanced Cart Manager initialized with weight limit:', this.maxWeight, 'kg');

        const totalItems = this.getTotalItems();
        const totalWeight = this.getTotalWeight();
        
        console.log('Cart summary:', {
            items: totalItems,
            weight: totalWeight,
            percentage: this.getWeightPercentage().toFixed(1) + '%'
        });

        if (typeof window.cartCount !== 'undefined' && window.cartCount !== totalItems) {
            console.log('Syncing cart count:', window.cartCount, '->', totalItems);
            window.cartCount = totalItems;
        }

        this.updateCartDisplay();

        // ตรวจสอบน้ำหนักเริ่มต้น
        if (totalWeight > this.maxWeight) {
            console.warn('Initial weight exceeds limit:', totalWeight, 'kg');
            setTimeout(() => {
                this.showWeightAlert(
                    `ตะกร้าสินค้ามีน้ำหนักเกินขีดจำกัด!\n\n` +
                    `น้ำหนักปัจจุบัน: ${totalWeight.toFixed(2)} กก.\n` +
                    `ขีดจำกัด: ${this.maxWeight} กก.\n` +
                    `กิน: ${(totalWeight - this.maxWeight).toFixed(2)} กก.\n\n` +
                    `ใช้ปุ่ม "ปรับน้ำหนัก" เพื่อปรับอัตโนมัติ`,
                    'warning'
                );
            }, 1000);
        }

        // Listen for storage changes (สำหรับ multiple tabs)
        window.addEventListener('storage', (e) => {
            if (e.key === 'shopping_cart') {
                this.cart = this.loadCart();
                this.updateCartDisplay();
                console.log('Cart synced from other tab');
            }
        });
    }

    // เพิ่มฟังก์ชันสำหรับ debug
    debugCart() {
        console.log('=== Enhanced Cart Debug Info ===');
        console.log('Cart data:', this.cart);
        console.log('Total items:', this.getTotalItems());
        console.log('Total price:', this.getTotalPrice());
        console.log('Total weight:', this.getTotalWeight(), 'kg');
        console.log('Weight percentage:', this.getWeightPercentage().toFixed(1) + '%');
        console.log('Remaining weight:', this.getRemainingWeight().toFixed(2), 'kg');
        console.log('Max weight:', this.maxWeight, 'kg');
        console.log('Warning threshold:', this.warningWeight, 'kg');
        console.log('Weight status:', this.getTotalWeight() > this.maxWeight ? 'EXCEEDED' : 
                                     this.getTotalWeight() > this.warningWeight ? 'WARNING' : 'NORMAL');
        console.log('Global cartCount:', typeof window.cartCount !== 'undefined' ? window.cartCount : 'undefined');
        console.log('localStorage:', localStorage.getItem('shopping_cart'));
        console.log('Weight report:', this.getWeightReport());
        console.log('=====================================');
    }
}

// สร้าง instance ของ Enhanced CartManager
window.cartManager = new CartManager();

// ฟังก์ชันสำหรับ legacy code - ซิงค์กับ cartManager
window.increaseQty = function (itemId) {
    return cartManager.increaseQuantity(itemId);
};

window.decreaseQty = function (itemId) {
    return cartManager.decreaseQuantity(itemId);
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

// ฟังก์ชันแสดงรายงานน้ำหนัก
window.showWeightReport = function () {
    cartManager.showWeightReport();
};

// ฟังก์ชันปรับปรุงน้ำหนักอัตโนมัติ
window.optimizeCartWeight = function () {
    cartManager.optimizeWeight();
};

// Event listener สำหรับอัพเดทตะกร้า
window.addEventListener('cartUpdated', function (e) {
    const { totalItems, totalPrice, totalWeight, weightPercentage, remainingWeight } = e.detail;

    // อัพเดท global cartCount ให้ตรงกัน
    if (typeof window.cartCount !== 'undefined') {
        window.cartCount = totalItems;
    }

    // อัพเดท cart badge
    if (typeof updateCartBadge === 'function') {
        updateCartBadge();
    }

    // แสดงข้อมูลน้ำหนักใน console (สำหรับ debug)
    if (totalWeight > 0) {
        console.log(`🏋️ Weight: ${totalWeight.toFixed(2)}/${cartManager.maxWeight} kg (${weightPercentage.toFixed(1)}%)`);
    }
});

// Initialize เมื่อ DOM โหลดเสร็จ
document.addEventListener('DOMContentLoaded', function () {
    console.log('Enhanced Cart system ready with weight management');

    // ล้างข้อมูลเก่าในหน้า cart.php ที่เป็น hardcode
    const hardcodedItems = document.querySelectorAll('.cart-item[data-product-id]');
    if (hardcodedItems.length === 0) {
        const allCartItems = document.querySelectorAll('.cart-item');
        if (allCartItems.length > 0 && !allCartItems[0].dataset.productId) {
            console.log('Removing hardcoded cart items...');
            cartManager.updateCartPage();
        }
    }

    cartManager.updateCartDisplay();
    
    // ตรวจสอบน้ำหนักเริ่มต้น
    setTimeout(() => {
        const initialWeight = cartManager.getTotalWeight();
        if (initialWeight > cartManager.warningWeight) {
            console.warn('⚠️ Initial cart weight is high:', initialWeight, 'kg');
        }
    }, 500);
});



// เพิ่มคำสั่ง console สำหรับ debug และจัดการน้ำหนัก
console.log('Enhanced Cart Manager loaded with weight management features.');
console.log('Available commands:');
console.log('- debugCart() : แสดงข้อมูล debug');
console.log('- clearCartData() : ล้างตะกร้าทั้งหมด');
console.log('- showWeightReport() : แสดงรายงานน้ำหนัก');
console.log('- optimizeCartWeight() : ปรับปรุงน้ำหนักอัตโนมัติ');