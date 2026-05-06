/**
 * PDV NEXUS v4.5 — ZEN PREMIUM
 * Business Logic & Animations
 */

const PDV = {
    state: {
        products: [],
        cart: [],
        subtotal: 0,
        discount: 0,
        total: 0,
        payments: [],
        selectedCustomer: null,
        isSheetExpanded: false
    },

    init() {
        console.log("PDV Nexus v4.5 Initializing...");
        this.loadData();
        this.setupEventListeners();
        this.moveModalsToBody();
    },

    moveModalsToBody() {
        // Essential to fix stacking context "Dark Screen" issues
        document.querySelectorAll('.modal').forEach(modal => {
            document.body.appendChild(modal);
        });
    },

    loadData() {
        // Simulated or real fetch
        fetch('../api/get_produtos.php')
            .then(res => res.json())
            .then(data => {
                this.state.products = data;
                this.renderProducts();
                this.renderChips();
            })
            .catch(err => {
                console.error("Error loading products:", err);
                this.notify("Falha ao carregar catálogo", "error");
            });
    },

    setupEventListeners() {
        // Search functionality
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
            ${categories.map(c => `
                <div class="pdv-chip" onclick="PDV.filterByCategory('${c}', this)">${c}</div>
            `).join('')}
        `;
    },

    filterByCategory(cat, el) {
        document.querySelectorAll('.pdv-chip').forEach(c => c.classList.remove('active'));
        el.classList.add('active');

        if (!cat) {
            this.renderProducts();
        } else {
            const filtered = this.state.products.filter(p => p.category === cat);
            this.renderProducts(filtered);
        }
    },

    renderProducts(filtered = null) {
        const products = filtered || this.state.products;
        const grid = document.getElementById('product-grid');
        if (!grid) return;

        if (products.length === 0) {
            grid.innerHTML = '<div class="pdv-empty-msg">Nenhum produto encontrado.</div>';
            return;
        }

        grid.innerHTML = products.map((p, index) => `
            <div class="pdv-product-card" onclick="PDV.addToCart(${p.id})" style="animation-delay: ${index * 0.03}s">
                <div class="pdv-item-icon">
                    <i class="fas ${p.icon || 'fa-box'}"></i>
                </div>
                <div class="pdv-item-info">
                    <span class="pdv-item-name">${p.name}</span>
                    <span class="pdv-item-price">${this.formatCurrency(p.price)}</span>
                    <div class="pdv-item-stock">
                        <i class="fas fa-cubes"></i> ${p.stock} em estoque
                    </div>
                </div>
            </div>
        `).join('');
    },

    addToCart(productId) {
        const product = this.state.products.find(p => p.id === productId);
        if (!product) return;

        const inCart = this.state.cart.find(i => i.id === productId);
        if (inCart) {
            inCart.quantity++;
        } else {
            this.state.cart.push({ ...product, quantity: 1 });
        }

        this.calculateTotals();
        this.renderCart();
        this.renderSheet();
        this.notify(`${product.name} adicionado`, "success");
        
        // Feedback visual no FAB se estiver no mobile
        this.updateGlobalBadges();
    },

    updateQuantity(id, delta) {
        const item = this.state.cart.find(i => i.id === id);
        if (!item) return;

        item.quantity += delta;
        if (item.quantity <= 0) {
            this.state.cart = this.state.cart.filter(i => i.id !== id);
        }

        this.calculateTotals();
        this.renderCart();
        this.renderSheet();
        this.updateGlobalBadges();
    },

    calculateTotals() {
        this.state.subtotal = this.state.cart.reduce((acc, i) => acc + (i.price * i.quantity), 0);
        this.state.total = this.state.subtotal - this.state.discount;
    },

    renderCart() {
        const container = document.getElementById('cart-container');
        if (!container) return;

        // Desktop Totals
        const totalEl = document.getElementById('cart-total');
        if (totalEl) totalEl.textContent = this.formatCurrency(this.state.total);

        if (this.state.cart.length === 0) {
            container.innerHTML = `
                <div class="pdv-empty-cart">
                    <div class="pdv-empty-cart-icon"><i class="fas fa-shopping-basket"></i></div>
                    <h3>Carrinho Vazio</h3>
                    <p>Adicione itens para começar.</p>
                </div>
            `;
            const btn = document.getElementById('btn-open-payment');
            if(btn) btn.disabled = true;
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

        const btn = document.getElementById('btn-open-payment');
        if(btn) btn.disabled = false;
    },

    renderSheet() {
        try {
            const sheet = document.getElementById('pdv-sheet');
            if (!sheet) return;

            if (this.state.cart.length > 0) {
                sheet.classList.add('has-items');
            } else {
                sheet.classList.remove('has-items', 'expanded');
                this.state.isSheetExpanded = false;
            }

            const itemsContainer = document.getElementById('sheet-cart-items');
            if (itemsContainer) {
                itemsContainer.innerHTML = this.state.cart.map(i => `
                    <div class="pdv-cart-item">
                        <div class="pdv-cart-item-info">
                            <span class="pdv-cart-item-name">${i.name}</span>
                            <span class="pdv-cart-item-meta">${i.quantity}x ${this.formatCurrency(i.price)}</span>
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

            const sheetTotal = document.getElementById('sheet-total');
            if (sheetTotal) sheetTotal.textContent = this.formatCurrency(this.state.total);
            
            const sheetFinalTotal = document.getElementById('sheet-final-total');
            if (sheetFinalTotal) sheetFinalTotal.textContent = this.formatCurrency(this.state.total);
            
        } catch (e) {
            console.error("Render Sheet error:", e);
        }
    },

    toggleSheet() {
        const sheet = document.getElementById('pdv-sheet');
        const backdrop = document.getElementById('sheet-backdrop');
        this.state.isSheetExpanded = !this.state.isSheetExpanded;

        if (this.state.isSheetExpanded) {
            sheet.classList.add('expanded');
            if(backdrop) { backdrop.style.display = 'block'; setTimeout(() => backdrop.style.opacity = '1', 10); }
        } else {
            sheet.classList.remove('expanded');
            if(backdrop) { backdrop.style.opacity = '0'; setTimeout(() => backdrop.style.display = 'none', 300); }
        }
        this.updateGlobalBadges();
    },

    collapseSheet() {
        if (this.state.isSheetExpanded) this.toggleSheet();
    },

    updateGlobalBadges() {
        const qty = this.state.cart.reduce((acc, i) => acc + i.quantity, 0);
        const fab = document.getElementById('pdv-cart-fab');
        if (fab && window.innerWidth <= 991) {
            if (qty > 0 && !this.state.isSheetExpanded) {
                fab.style.display = 'flex';
                setTimeout(() => fab.style.transform = 'scale(1)', 10);
            } else {
                fab.style.transform = 'scale(0)';
                setTimeout(() => fab.style.display = 'none', 300);
            }
        }
    },

    formatCurrency(val) {
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(val || 0);
    },

    notify(msg, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `bh-toast bh-toast-${type}`;
        toast.innerHTML = `
            <div class="bh-toast-content">
                <span class="bh-toast-msg">${msg}</span>
            </div>
        `;
        document.body.appendChild(toast);
        setTimeout(() => toast.classList.add('active'), 10);
        setTimeout(() => {
            toast.classList.remove('active');
            setTimeout(() => toast.remove(), 400);
        }, 3000);
    }
};

// Initialize
document.addEventListener('DOMContentLoaded', () => PDV.init());
