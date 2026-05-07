/**
 * PDV NEXUS v5.0 — FULL PROFESSIONAL FLOW
 * Professional checkout, modal handling, and mobile responsiveness.
 */

const PDV = {
    state: {
        products: [],
        cart: [],
        subtotal: 0,
        discount: 0,
        total: 0,
        selectedCustomer: null,
        isSheetExpanded: false
    },

    init() {
        console.log("PDV Nexus v5.0 Active");
        this.loadData();
        this.setupEventListeners();
        this.moveModalsToBody();
    },

    moveModalsToBody() {
        document.querySelectorAll('.modal').forEach(m => document.body.appendChild(m));
    },

    loadData() {
        fetch('../api/get_produtos.php')
            .then(res => res.json())
            .then(data => {
                this.state.products = data;
                this.renderProducts();
                this.renderChips();
            })
            .catch(err => this.notify("Erro ao carregar catálogo", "error"));
    },

    setupEventListeners() {
        const searchInput = document.getElementById('pdv-search-input');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                const term = e.target.value.toLowerCase();
                const filtered = this.state.products.filter(p => 
                    p.name.toLowerCase().includes(term) || 
                    (p.category && p.category.toLowerCase().includes(term))
                );
                this.renderProducts(filtered);
            });
        }
    },

    renderChips() {
        const categories = [...new Set(this.state.products.map(p => p.category).filter(Boolean))];
        const container = document.getElementById('pdv-chips');
        if (!container) return;

        container.innerHTML = `
            <div class="pdv-chip active" onclick="PDV.filterByCategory(null, this)">Tudo</div>
            ${categories.map(c => `<div class="pdv-chip" onclick="PDV.filterByCategory('${c}', this)">${c}</div>`).join('')}
        `;
    },

    filterByCategory(cat, el) {
        document.querySelectorAll('.pdv-chip').forEach(c => c.classList.remove('active'));
        el.classList.add('active');
        const filtered = cat ? this.state.products.filter(p => p.category === cat) : null;
        this.renderProducts(filtered);
    },

    renderProducts(filtered = null) {
        const products = filtered || this.state.products;
        const grid = document.getElementById('product-grid');
        if (!grid) return;

        grid.innerHTML = products.map((p, index) => `
            <div class="pdv-product-card" onclick="PDV.addToCart(${p.id})" style="animation-delay: ${index * 0.02}s">
                <div class="pdv-item-icon"><i class="fas ${p.icon || 'fa-box'}"></i></div>
                <div class="pdv-item-info">
                    <span class="pdv-item-name">${p.name}</span>
                    <span class="pdv-item-price">${this.formatCurrency(p.price)}</span>
                    <div class="pdv-item-stock"><i class="fas fa-cubes"></i> ${p.stock}</div>
                </div>
            </div>
        `).join('');
    },

    addToCart(productId) {
        const product = this.state.products.find(p => p.id === productId);
        if (!product) return;

        const inCart = this.state.cart.find(i => i.id === productId);
        if (inCart) inCart.quantity++;
        else this.state.cart.push({ ...product, quantity: 1 });

        this.calculateTotals();
        this.renderCart();
        this.renderSheet();
        this.notify(`${product.name} no carrinho`, "success");
    },

    updateQuantity(id, delta) {
        const item = this.state.cart.find(i => i.id === id);
        if (!item) return;

        item.quantity += delta;
        if (item.quantity <= 0) this.state.cart = this.state.cart.filter(i => i.id !== id);

        this.calculateTotals();
        this.renderCart();
        this.renderSheet();
    },

    calculateTotals() {
        this.state.subtotal = this.state.cart.reduce((acc, i) => acc + (i.price * i.quantity), 0);
        this.state.total = this.state.subtotal - this.state.discount;
    },

    renderCart() {
        const container = document.getElementById('cart-container');
        if (!container) return;

        document.getElementById('cart-subtotal').textContent = this.formatCurrency(this.state.subtotal);
        document.getElementById('cart-total').textContent = this.formatCurrency(this.state.total);

        if (this.state.cart.length === 0) {
            container.innerHTML = `<div class="pdv-empty-cart"><p>Carrinho vazio</p></div>`;
            document.getElementById('btn-open-payment').disabled = true;
            return;
        }

        container.innerHTML = this.state.cart.map(i => `
            <div class="pdv-cart-item">
                <div class="pdv-cart-item-info">
                    <span class="pdv-cart-item-name">${i.name}</span>
                    <span class="pdv-cart-item-meta">${this.formatCurrency(i.price)}</span>
                </div>
                <div class="pdv-qty-control">
                    <button class="pdv-qty-btn" onclick="PDV.updateQuantity(${i.id}, -1)">-</button>
                    <span class="pdv-qty-val">${i.quantity}</span>
                    <button class="pdv-qty-btn" onclick="PDV.updateQuantity(${i.id}, 1)">+</button>
                </div>
                <div class="pdv-cart-item-price">${this.formatCurrency(i.price * i.quantity)}</div>
            </div>
        `).join('');

        document.getElementById('btn-open-payment').disabled = false;
    },

    renderSheet() {
        const sheet = document.getElementById('pdv-sheet');
        if (!sheet) return;

        if (this.state.cart.length > 0) sheet.classList.add('has-items');
        else { sheet.classList.remove('has-items', 'expanded'); this.state.isSheetExpanded = false; }

        const itemsContainer = document.getElementById('sheet-cart-items');
        if (itemsContainer) {
            itemsContainer.innerHTML = this.state.cart.map(i => `
                <div class="pdv-cart-item">
                    <div class="pdv-cart-item-info">
                        <span class="pdv-cart-item-name">${i.name}</span>
                        <span class="pdv-cart-item-meta">${this.formatCurrency(i.price)}</span>
                    </div>
                    <div class="pdv-qty-control">
                        <button class="pdv-qty-btn" onclick="PDV.updateQuantity(${i.id}, -1)">-</button>
                        <span class="pdv-qty-val">${i.quantity}</span>
                        <button class="pdv-qty-btn" onclick="PDV.updateQuantity(${i.id}, 1)">+</button>
                    </div>
                    <div class="pdv-cart-item-price">${this.formatCurrency(i.price * i.quantity)}</div>
                </div>
            `).join('');
        }

        document.getElementById('sheet-total').textContent = this.formatCurrency(this.state.total);
        document.getElementById('sheet-final-total').textContent = this.formatCurrency(this.state.total);
    },

    toggleSheet() {
        const sheet = document.getElementById('pdv-sheet');
        const backdrop = document.getElementById('sheet-backdrop');
        this.state.isSheetExpanded = !this.state.isSheetExpanded;

        if (this.state.isSheetExpanded) {
            sheet.classList.add('expanded');
            backdrop.style.display = 'block';
            setTimeout(() => backdrop.style.opacity = '1', 10);
        } else {
            sheet.classList.remove('expanded');
            backdrop.style.opacity = '0';
            setTimeout(() => backdrop.style.display = 'none', 300);
        }
    },

    collapseSheet() {
        if (this.state.isSheetExpanded) this.toggleSheet();
    },

    openPaymentModal() {
        this.collapseSheet();
        const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
        document.getElementById('modal-total-sale').textContent = this.formatCurrency(this.state.total);
        modal.show();
    },

    confirmSale(method) {
        // Enviar para o banco de dados via API
        const data = {
            cliente_id: this.state.selectedCustomer ? this.state.selectedCustomer.id : null,
            total: this.state.total,
            pagamento: method,
            itens: this.state.cart
        };

        this.notify("Processando venda...", "info");

        fetch('../api/process_venda.php', {
            method: 'POST',
            body: JSON.stringify(data)
        }).then(res => res.json())
          .then(res => {
              if (res.success) {
                  bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
                  new bootstrap.Modal(document.getElementById('successModal')).show();
                  this.clearCart();
              } else {
                  this.notify(res.message || "Erro ao salvar venda", "error");
              }
          });
    },

    clearCart() {
        this.state.cart = [];
        this.state.discount = 0;
        this.state.selectedCustomer = null;
        this.calculateTotals();
        this.renderCart();
        this.renderSheet();
    },

    closeSuccessModal() {
        bootstrap.Modal.getInstance(document.getElementById('successModal')).hide();
    },

    formatCurrency(val) {
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(val || 0);
    },

    notify(msg, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `bh-toast bh-toast-${type}`;
        toast.innerHTML = `<div class="bh-toast-msg">${msg}</div>`;
        document.body.appendChild(toast);
        setTimeout(() => toast.classList.add('active'), 10);
        setTimeout(() => { toast.classList.remove('active'); setTimeout(() => toast.remove(), 400); }, 3000);
    }
};

document.addEventListener('DOMContentLoaded', () => PDV.init());
