// Enhanced Cart management system - ระบบจัดการน้ำหนักรวมไม่เกิน 1000 kg และ Stock Control
class CartManager {
    constructor() {
        this.cart = this.loadCart();
        this.maxWeight = 1000; // น้ำหนักสูงสุด 1000 กก.
        this.warningWeight = 800; // แจ้งเตือนเมื่อใกล้ขีดจำกัด
        this.productStock = new Map(); // เก็บข้อมูล stock ของสินค้า
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

    // ดึงข้อมูล stock ของสินค้าจาก API
    async fetchProductStock(productId) {
        try {
            const projectRoot = window.location.pathname.split('/')[1];
            const response = await fetch(`/${projectRoot}/controllers/product_home.php?product_id=${productId}&get_stock=true`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            
            if (result.success && result.data) {
                const stock = parseInt(result.data.stock) || 0;
                this.productStock.set(productId, stock);
                console.log(`Stock fetched for product ${productId}: ${stock} units`);
                return stock;
            } else {
                console.warn(`Failed to fetch stock for product ${productId}:`, result.message);
                return 0;
            }
        } catch (error) {
            console.error(`Error fetching stock for product ${productId}:`, error);
            return 0;
        }
    }

    // ตรวจสอบ stock ของสินค้า
    async validateStock(productId, requestedQuantity) {
        let availableStock = this.productStock.get(productId);
        
        // ถ้าไม่มีข้อมูล stock ในหน่วยความจำ ให้ดึงจาก API
        if (availableStock === undefined) {
            availableStock = await this.fetchProductStock(productId);
        }

        // คำนวณจำนวนที่อยู่ในตะกร้าแล้ว
        const currentInCart = this.cart[productId] ? this.cart[productId].quantity : 0;
        const totalRequestedQuantity = currentInCart + requestedQuantity;

        console.log(`Stock validation for product ${productId}:`);
        console.log(`- Available stock: ${availableStock}`);
        console.log(`- Currently in cart: ${currentInCart}`);
        console.log(`- Requested to add: ${requestedQuantity}`);
        console.log(`- Total requested: ${totalRequestedQuantity}`);

        return {
            available: availableStock,
            currentInCart: currentInCart,
            canAdd: totalRequestedQuantity <= availableStock,
            maxCanAdd: Math.max(0, availableStock - currentInCart),
            totalRequested: totalRequestedQuantity
        };
    }

    // แสดงการแจ้งเตือน stock
    showStockAlert(message, type = 'info') {
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

    // เพิ่มสินค้าลงตะกร้า (ปรับปรุงให้ตรวจสอบ stock)
    async addItem(productId, productName, price, quantity = 1, image = 'no-image.jpg', weight = 0) {
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

        // ตรวจสอบ stock ก่อน
        console.log(`🔍 Validating stock for product: ${productName} (${itemKey})`);
        const stockValidation = await this.validateStock(itemKey, numericQuantity);
        
        if (!stockValidation.canAdd) {
            this.showStockAlert(
                `❌ ไม่สามารถเพิ่ม "${productName}" ได้\n\n` +
                `📦 Stock คงเหลือ: ${stockValidation.available} ชิ้น\n` +
                `🛒 ในตะกร้าแล้ว: ${stockValidation.currentInCart} ชิ้น\n` +
                `➕ ต้องการเพิ่ม: ${numericQuantity} ชิ้น\n` +
                `⚠️ รวมจะได้: ${stockValidation.totalRequested} ชิ้น\n\n` +
                `💡 สามารถเพิ่มได้อีกสูงสุด: ${stockValidation.maxCanAdd} ชิ้น`,
                'error'
            );
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
                stock: stockValidation.available, // เก็บข้อมูル stock ไว้
                addedAt: new Date().toISOString()
            };
        }

        console.log(`✅ Added to cart: ${productName} (${itemKey}) - ฿${numericPrice} x${numericQuantity}, Weight: ${additionalWeight.toFixed(2)} กก., Stock: ${stockValidation.available}`);
        
        this.saveCart();
        this.updateCartDisplay();
        
        // แสดงการแจ้งเตือนถ้าน้ำหนักใกล้ขีดจำกัด
        this.checkWeightWarning();
        
        // แสดงการแจ้งเตือนถ้า stock ใกล้หมด
        this.checkStockWarning(itemKey, stockValidation);
        
        return true;
    }

    // ตรวจสอบและแจ้งเตือน stock ใกล้หมด
    checkStockWarning(productId, stockValidation) {
        const remaining = stockValidation.maxCanAdd;
        const productName = this.cart[productId] ? this.cart[productId].name : 'สินค้า';
        
        if (remaining <= 5 && remaining > 0) {
            this.showStockAlert(
                `⚠️ Stock ใกล้หมดแล้ว!\n\n` +
                `📦 "${productName}"\n` +
                `🛒 ในตะกร้า: ${stockValidation.currentInCart} ชิ้น\n` +
                `📦 Stock คงเหลือ: ${stockValidation.available} ชิ้น\n` +
                `➕ เพิ่มได้อีก: ${remaining} ชิ้น`,
                'warning'
            );
        } else if (remaining === 0) {
            this.showStockAlert(
                `🚫 Stock หมดแล้ว!\n\n` +
                `📦 "${productName}"\n` +
                `🛒 ในตะกร้า: ${stockValidation.currentInCart} ชิ้น\n` +
                `📦 Stock คงเหลือ: ${stockValidation.available} ชิ้น\n` +
                `❌ ไม่สามารถเพิ่มได้อีก`,
                'error'
            );
        }
    }

    // อัพเดทจำนวนสินค้า (ปรับปรุงให้ตรวจสอบ stock)
    async updateQuantity(productId, newQuantity) {
        const itemKey = String(productId).trim();

        if (!this.cart[itemKey]) {
            console.error(`Product ${productId} not found in cart`);
            return false;
        }

        const newQty = parseInt(newQuantity);
        
        if (newQty <= 0) {
            this.removeItem(productId);
            return true;
        }

        // ตรวจสอบ stock
        const currentQuantity = this.cart[itemKey].quantity;
        const quantityDifference = newQty - currentQuantity;

        if (quantityDifference > 0) {
            // ถ้าต้องการเพิ่ม ต้องตรวจสอบ stock
            const stockValidation = await this.validateStock(itemKey, quantityDifference);
            
            if (!stockValidation.canAdd) {
                this.showStockAlert(
                    `❌ ไม่สามารถอัพเดทจำนวนเป็น ${newQty} ได้\n\n` +
                    `📦 Stock คงเหลือ: ${stockValidation.available} ชิ้น\n` +
                    `🛒 ในตะกร้าปัจจุบัน: ${currentQuantity} ชิ้น\n` +
                    `➕ ต้องการเป็น: ${newQty} ชิ้น\n\n` +
                    `💡 จำนวนสูงสุดที่ตั้งได้: ${stockValidation.available} ชิ้น`,
                    'error'
                );
                
                // รีเซ็ตค่า input กลับเป็นเดิม
                const input = document.querySelector(`input[onchange*="${productId}"]`);
                if (input) {
                    input.value = currentQuantity;
                }
                return false;
            }
        }

        // ตรวจสอบน้ำหนักเมื่ออัพเดทจำนวน
        const itemWeight = this.cart[itemKey].weight || 0;
        const currentWeight = this.getTotalWeight();
        const oldItemWeight = currentQuantity * itemWeight;
        const newItemWeight = newQty * itemWeight;
        const weightDifference = newItemWeight - oldItemWeight;
        const newTotalWeight = currentWeight + weightDifference;
        
        if (newTotalWeight > this.maxWeight) {
            const maxPossibleQty = Math.floor((this.maxWeight - (currentWeight - oldItemWeight)) / itemWeight);
            this.showWeightAlert(
                `❌ ไม่สามารถอัพเดทจำนวนเป็น ${newQty} ได้ (เกินน้ำหนัก)\n\n` +
                `🏋️ น้ำหนักปัจจุบัน: ${currentWeight.toFixed(2)} กก.\n` +
                `⚖️ น้ำหนักใหม่: ${newTotalWeight.toFixed(2)} กก.\n` +
                `🚫 เกินขีดจำกัด: ${(newTotalWeight - this.maxWeight).toFixed(2)} กก.\n\n` +
                `💡 จำนวนสูงสุดที่เพิ่มได้: ${maxPossibleQty} ชิ้น`,
                'error'
            );
            
            // รีเซ็ตค่า input กลับเป็นเดิม
            const input = document.querySelector(`input[onchange*="${productId}"]`);
            if (input) {
                input.value = currentQuantity;
            }
            return false;
        }
        
        this.cart[itemKey].quantity = newQty;
        this.saveCart();
        this.updateCartDisplay();
        
        console.log(`✅ Updated quantity for ${this.cart[itemKey].name}: ${currentQuantity} → ${newQty}`);
        return true;
    }

    // เพิ่มจำนวนสินค้า (ปรับปรุงให้ตรวจสอบ stock)
    async increaseQuantity(productId) {
        const itemKey = String(productId).trim();
        if (!this.cart[itemKey]) {
            console.error(`Product ${productId} not found in cart`);
            return false;
        }

        // ตรวจสอบ stock ก่อนเพิ่ม
        const stockValidation = await this.validateStock(itemKey, 1);
        
        if (!stockValidation.canAdd) {
            this.showStockAlert(
                `❌ ไม่สามารถเพิ่ม "${this.cart[itemKey].name}" ได้อีก\n\n` +
                `📦 Stock คงเหลือ: ${stockValidation.available} ชิ้น\n` +
                `🛒 ในตะกร้าแล้ว: ${stockValidation.currentInCart} ชิ้น\n` +
                `💡 ไม่สามารถเพิ่มได้อีก`,
                'error'
            );
            return false;
        }

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
        
        console.log(`✅ Increased quantity for ${this.cart[itemKey].name}: ${this.cart[itemKey].quantity}`);
        return true;
    }

    // ลดจำนวนสินค้า (ไม่ต้องตรวจสอบ stock)
    decreaseQuantity(productId) {
        const itemKey = String(productId).trim();
        if (this.cart[itemKey]) {
            if (this.cart[itemKey].quantity > 1) {
                this.cart[itemKey].quantity--;
                this.saveCart();
                this.updateCartDisplay();
                console.log(`✅ Decreased quantity for ${this.cart[itemKey].name}: ${this.cart[itemKey].quantity}`);
            }
            return true;
        }
        return false;
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
                message: `❌ ไม่สามารถเพิ่ม "${productName}" ได้ (เกินน้ำหนัก)\n\n` +
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
        this.showStockAlert(message, type); // ใช้ฟังก์ชันเดียวกัน
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

    // ลบสินค้าจากตะกร้า
    removeItem(productId) {
        const itemKey = String(productId).trim();
        if (this.cart[itemKey]) {
            const itemName = this.cart[itemKey].name;
            const itemWeight = (this.cart[itemKey].weight || 0) * this.cart[itemKey].quantity;
            
            delete this.cart[itemKey];
            this.saveCart();
            this.updateCartDisplay();
            
            console.log(`✅ Removed from cart: ${itemName}, Weight freed: ${itemWeight.toFixed(2)} กก.`);
            return true;
        }
        return false;
    }

    // ล้างตะกร้าทั้งหมด
    clearCart() {
        const totalWeight = this.getTotalWeight();
        this.cart = {};
        this.productStock.clear(); // ล้างข้อมูล stock ด้วย
        this.saveCart();
        this.updateCartDisplay();
        console.log(`✅ Cart cleared. Weight freed: ${totalWeight.toFixed(2)} กก.`);
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

    // คำนวณจำนวนสูงสุดที่สามารถเพิ่มได้สำหรับสินค้าชิ้นนั้น (รวม stock และน้ำหนัก)
    async getMaxAddableQuantity(productId, productWeight) {
        // ตรวจสอบข้อจำกัดด้าน stock
        const stockValidation = await this.validateStock(productId, 999999); // จำนวนมาก ๆ เพื่อดู max
        const maxByStock = stockValidation.maxCanAdd;
        
        // ตรวจสอบข้อจำกัดด้านน้ำหนัก
        let maxByWeight = 9999;
        if (productWeight && productWeight > 0) {
            const remainingWeight = this.getRemainingWeight();
            maxByWeight = Math.floor(remainingWeight / productWeight);
        }
        
        // ใช้ข้อจำกัดที่น้อยที่สุด
        const result = Math.min(maxByStock, maxByWeight);
        
        console.log(`Max addable quantity for product ${productId}:`);
        console.log(`- By stock: ${maxByStock}`);
        console.log(`- By weight: ${maxByWeight}`);
        console.log(`- Final: ${result}`);
        
        return result;
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

    // อัพเดทหน้าตะกร้าสินค้า (ปรับปรุงให้แสดงข้อมูล stock)
    async updateCartPage() {
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
                        <button onclick="window.location.href='allproduct.php'" style="
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

        // สร้าง HTML สำหรับแต่ละสินค้า
        for (const item of items) {
            const itemTotal = item.price * item.quantity;
            const itemWeight = (item.weight || 0) * item.quantity;
            const maxQty = await this.getMaxAddableQuantity(item.id, item.weight || 0) + item.quantity;
            const currentStock = this.productStock.get(item.id) || item.stock || 0;
            const stockRemaining = currentStock - item.quantity;

            // กำหนดสีแสดง stock status
            let stockColor = '#27ae60'; // เขียว
            let stockText = `คงเหลือ ${stockRemaining} ชิ้น`;
            
            if (stockRemaining <= 0) {
                stockColor = '#e74c3c'; // แดง
                stockText = 'สต็อกหมด';
            } else if (stockRemaining <= 5) {
                stockColor = '#f39c12'; // ส้ม
                stockText = `เหลือน้อย ${stockRemaining} ชิ้น`;
            }

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
                        
                        <div class="item-stock-info" style="font-size: 14px; margin: 5px 0;">
                            <div style="color: ${stockColor}; font-weight: bold;">
                                📦 ${stockText}
                            </div>
                            
                        </div>

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
                                ${item.quantity >= maxQty || stockRemaining <= 0 ? 'disabled style="opacity: 0.5; cursor: not-allowed;" title="ถึงขีดจำกัดหรือสต็อกหมดแล้ว"' : ''}>+</button>
                    </div>
                    <div class="item-total">
                        <div style="color: #27ae60; font-weight: bold; font-size: 18px;">฿${itemTotal.toLocaleString()}</div>
                        ${itemWeight > 0 ? `<div style="color: #666; font-size: 14px;">${itemWeight.toFixed(2)} กก.</div>` : ''}
                    </div>
                    <button class="delete-btn" onclick="cartManager.removeItem('${item.id}')" title="ลบสินค้า">🗑️</button>
                </div>
            `;
        }

        cartContainer.innerHTML = cartHTML;
        this.updateSummary();
    }

    // อัพเดทสรุปยอดรวม (เพิ่มข้อมูล stock warning)
    updateSummary() {
        const totalItems = this.getTotalItems();
        const subtotal = this.getTotalPrice();
        const totalWeight = this.getTotalWeight();
        const weightPercentage = this.getWeightPercentage();

        // ตรวจสอบ stock warnings
        const stockWarnings = [];
        Object.values(this.cart).forEach(item => {
            const currentStock = this.productStock.get(item.id) || item.stock || 0;
            const stockRemaining = currentStock - item.quantity;
            
            if (stockRemaining < 0) {
                stockWarnings.push(`❌ ${item.name}: สต็อกไม่เพียงพอ (ขอ ${item.quantity} มี ${currentStock})`);
            } else if (stockRemaining <= 5 && stockRemaining > 0) {
                stockWarnings.push(`⚠️ ${item.name}: สต็อกเหลือน้อย (${stockRemaining} ชิ้น)`);
            }
        });

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
                ` : ''}

                ${stockWarnings.length > 0 ? `
                <div class="stock-warnings" style="
                    background: #fff3cd; border: 1px solid #f39c12; 
                    color: #d68910; padding: 10px; border-radius: 6px; 
                    margin: 10px 0; font-size: 13px;
                ">
                    <div style="font-weight: bold; margin-bottom: 5px;">📦 แจ้งเตือนสต็อก:</div>
                    ${stockWarnings.map(warning => `<div>• ${warning}</div>`).join('')}
                </div>
                ` : ''}

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
                
                <div class="summary-row">
                    <span style="color: #666; font-style: italic;">ดำเนินการต่อเพื่อคำนวณค่าจัดส่ง</span>
                </div>
                
                <div class="summary-row total" style="border-top: 2px solid #eee; padding-top: 20px;">
                    <span>ยอดรวม (ยังไม่รวมค่าจัดส่ง)</span>
                    <span>฿${subtotal.toLocaleString()}</span>
                </div>
                
                <button class="checkout-btn" onclick="cartManager.checkout()" 
                        ${!this.validateWeightLimit() || stockWarnings.some(w => w.includes('❌')) ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''}>
                    ${!this.validateWeightLimit() ? '⚠️ เกินขีดจำกัดน้ำหนัก' : 
                      stockWarnings.some(w => w.includes('❌')) ? '⚠️ สต็อกไม่เพียงพอ' : 
                      'ดำเนินการสั่งซื้อ'}
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

    // ฟังก์ชันชำระเงิน (ปรับปรุงให้ตรวจสอบ stock ก่อน checkout)
    async checkout() {
        console.log('=== CHECKOUT WITH STOCK VALIDATION START ===');

        const items = this.getCartItems();
        if (!items || items.length === 0) {
            alert('ตะกร้าสินค้าว่างเปล่า');
            console.warn('Cart is empty');
            return;
        }

        // ตรวจสอบน้ำหนักรวมไม่ให้เกิน 1000 กก.
        if (!this.validateWeightLimit()) {
            return;
        }

        // ตรวจสอบ stock สำหรับทุกสินค้าก่อน checkout
        console.log('🔍 Validating stock for all items before checkout...');
        
        let hasStockIssues = false;
        const stockIssues = [];

        for (const item of items) {
            const stockValidation = await this.validateStock(item.id, 0); // ตรวจสอบ stock ปัจจุบัน
            const currentStock = stockValidation.available;
            
            if (item.quantity > currentStock) {
                hasStockIssues = true;
                stockIssues.push({
                    name: item.name,
                    requested: item.quantity,
                    available: currentStock
                });
                console.warn(`❌ Stock issue: ${item.name} (requested: ${item.quantity}, available: ${currentStock})`);
            }
        }

        if (hasStockIssues) {
            let stockMessage = '❌ ไม่สามารถดำเนินการสั่งซื้อได้\n\n📦 สินค้าที่สต็อกไม่เพียงพอ:\n';
            stockIssues.forEach(issue => {
                stockMessage += `• ${issue.name}: ขอ ${issue.requested} มี ${issue.available} ชิ้น\n`;
            });
            stockMessage += '\n💡 กรุณาปรับจำนวนหรือลบสินค้าที่สต็อกไม่พอ';
            
            this.showStockAlert(stockMessage, 'error');
            
            // อัพเดทหน้าตะกร้าให้แสดงสถานะล่าสุด
            await this.refreshAllStock();
            return;
        }

        const requestData = {
            items: items.map(item => ({
                product_id: item.id,
                quantity: item.quantity,
                stock_validated: true // ระบุว่าได้ตรวจสอบ stock แล้ว
            })),
            total_amount: this.getTotalPrice(),
            total_weight: this.getTotalWeight(),
            weight_percentage: this.getWeightPercentage(),
            stock_validation: items.map(item => ({
                product_id: item.id,
                requested_quantity: item.quantity,
                available_stock: this.productStock.get(item.id) || 0
            })),
            timestamp: new Date().toISOString()
        };

        console.log('Request payload with stock validation:', requestData);

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

            console.log('Checkout request sent with stock validation');
            const responseText = await response.text();
            console.log('Raw checkout response:', responseText);

            let result;
            try {
                result = JSON.parse(responseText);
            } catch (jsonErr) {
                console.error('Failed to parse checkout JSON:', jsonErr);
                throw new Error('Response is not valid JSON');
            }

            if (result.success) {
                console.log('✅ Checkout with stock validation successful:', result);

                localStorage.setItem('checkout_data', JSON.stringify({
                    items: items,
                    totalItems: this.getTotalItems(),
                    totalAmount: requestData.total_amount,
                    totalWeight: requestData.total_weight,
                    weightPercentage: requestData.weight_percentage,
                    stockValidation: requestData.stock_validation,
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
                
                // ถ้าเป็นปัญหา stock ให้รีเฟรชข้อมูล
                if (result.message && result.message.includes('stock')) {
                    await this.refreshAllStock();
                }
                
                throw new Error(result.message || 'เกิดข้อผิดพลาดในการสั่งซื้อ');
            }

        } catch (error) {
            console.error('Checkout exception:', error);
            this.showStockAlert(
                `เกิดข้อผิดพลาดในการสั่งซื้อ\n\n` +
                `รายละเอียด: ${error.message}\n` +
                `กรุณาตรวจสอบสต็อกและลองใหม่อีกครั้ง`,
                'error'
            );

        } finally {
            if (checkoutBtn) {
                checkoutBtn.disabled = false;
                checkoutBtn.textContent = '🛒 ดำเนินการสั่งซื้อ';
            }
            console.log('=== CHECKOUT WITH STOCK VALIDATION END ===');
        }
    }

    // เริ่มต้นระบบ
    init() {
        console.log('Enhanced Cart Manager initialized with stock control and weight limit:', this.maxWeight, 'kg');

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
        }

        // รีเฟรช stock data เมื่อเริ่มต้น (ไม่บล็อค UI)
        setTimeout(async () => {
            if (totalItems > 0) {
                console.log('🔄 Initial stock refresh...');
                await this.refreshAllStock();
            }
        }, 1000);

        // Listen for storage changes (สำหรับ multiple tabs)
        window.addEventListener('storage', (e) => {
            if (e.key === 'shopping_cart') {
                this.cart = this.loadCart();
                this.updateCartDisplay();
                console.log('Cart synced from other tab');
            }
        });
    }

    // Debug functions
    debugCart() {
        console.log('=== Enhanced Cart with Stock Control Debug Info ===');
        console.log('Cart data:', this.cart);
        console.log('Stock data:', Array.from(this.productStock.entries()));
        console.log('Total items:', this.getTotalItems());
        console.log('Total price:', this.getTotalPrice());
        console.log('Total weight:', this.getTotalWeight(), 'kg');
        console.log('Weight percentage:', this.getWeightPercentage().toFixed(1) + '%');
        console.log('Remaining weight:', this.getRemainingWeight().toFixed(2), 'kg');
        
        // Stock analysis
        const stockAnalysis = Object.values(this.cart).map(item => ({
            name: item.name,
            inCart: item.quantity,
            availableStock: this.productStock.get(item.id) || item.stock || 0,
            stockAfterCart: (this.productStock.get(item.id) || item.stock || 0) - item.quantity
        }));
        
        console.log('Stock analysis:', stockAnalysis);
        console.log('================================================');
    }
}

// สร้าง instance ของ Enhanced CartManager
window.cartManager = new CartManager();

// Legacy functions compatibility
window.increaseQty = function (itemId) {
    return cartManager.increaseQuantity(itemId);
};

window.decreaseQty = function (itemId) {
    return cartManager.decreaseQuantity(itemId);
};

window.updateTotal = function (itemId) {
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

window.clearCartData = function () {
    if (confirm('คุณต้องการล้างข้อมูลตะกร้าทั้งหมดหรือไม่?')) {
        cartManager.clearCart();
        console.log('Cart cleared!');
    }
};

window.debugCart = function () {
    cartManager.debugCart();
};

window.refreshCartStock = function() {
    cartManager.refreshAllStock();
};

// Event listeners
window.addEventListener('cartUpdated', function (e) {
    const { totalItems, totalPrice, totalWeight, weightPercentage, remainingWeight } = e.detail;

    if (typeof window.cartCount !== 'undefined') {
        window.cartCount = totalItems;
    }

    if (typeof updateCartBadge === 'function') {
        updateCartBadge();
    }

    if (totalWeight > 0) {
        console.log(`🏋️ Weight: ${totalWeight.toFixed(2)}/${cartManager.maxWeight} kg (${weightPercentage.toFixed(1)}%)`);
    }
});

// Initialize เมื่อ DOM โหลดเสร็จ
document.addEventListener('DOMContentLoaded', function () {
    console.log('Enhanced Cart system ready with stock control and weight management');

    cartManager.updateCartDisplay();
    
    setTimeout(() => {
        const initialWeight = cartManager.getTotalWeight();
        if (initialWeight > cartManager.warningWeight) {
            console.warn('⚠️ Initial cart weight is high:', initialWeight, 'kg');
        }
    }, 500);
});

// Console commands
console.log('Enhanced Cart Manager loaded with stock control and weight management features.');
console.log('Available commands:');
console.log('- debugCart() : แสดงข้อมูล debug รวมถึงข้อมูล stock');
console.log('- clearCartData() : ล้างตะกร้าทั้งหมด');
console.log('- refreshCartStock() : รีเฟรชข้อมูล stock ทั้งหมด');