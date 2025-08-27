function increaseQty(itemId) {
    const qtyInput = document.getElementById(`qty${itemId}`);
    qtyInput.value = parseInt(qtyInput.value) + 1;
    updateTotal(itemId);
}

function decreaseQty(itemId) {
    const qtyInput = document.getElementById(`qty${itemId}`);
    if (parseInt(qtyInput.value) > 1) {
        qtyInput.value = parseInt(qtyInput.value) - 1;
        updateTotal(itemId);
    }
}

function updateTotal(itemId) {
    const qty = parseInt(document.getElementById(`qty${itemId}`).value);
    const price = 199;
    const total = qty * price;
    document.getElementById(`total${itemId}`).textContent = `฿${total}`;
    updateSummary();
}

function updateSummary() {
    let subtotal = 0;
    const items = document.querySelectorAll('.cart-item');
    let itemCount = 0;

    items.forEach((item, index) => {
        const qty = parseInt(item.querySelector('.qty-input').value);
        const price = 199;
        subtotal += qty * price;
        itemCount += qty;
    });

    const shipping = 40;
    const finalTotal = subtotal + shipping;

    document.getElementById('subtotal').textContent = `฿${subtotal}`;
    document.getElementById('discount-total').textContent = `฿${finalTotal}`;
    document.getElementById('final-total').textContent = `฿${finalTotal}`;

    // Update item count
    const summaryRows = document.querySelectorAll('.summary-row');
    summaryRows[0].querySelector('span').textContent = `${itemCount} รายการ`;
}

function removeItem(button) {
    const item = button.closest('.cart-item');
    item.remove();
    updateSummary();
}

// Initialize
updateSummary();