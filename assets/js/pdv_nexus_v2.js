/**
 * PDV NEXUS v2.0 — Logic & UI Controller
 * Build for performance and native-app feel.
 */

const PDV = {
    state: {
        cart: [],
        payments: [],
        selectedCustomer: null,
        discount: 0,
        subtotal: 0,
        total: 0,
        searchResults: [],
        activeCategory: 'all',
        isSheetExpanded: false,
        isProcessing: false
    },

    init() {
        console.log("Nexus PDV initialized — Google Standard UX");
        this.bindEvents();
        this.loadInitialCatalog();
    },

    bindEvents() {
        // Search Input
        const searchInput = document.getElementById('pdv-search');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.handleSearch(e.target.value));
            searchInput.addEventListener('keydown', (e) => this.handleGlobalShortcuts(e));
        }

        // Discount Input (Desktop)
        const discountInput = document.getElementById('cart-discount-input');
        if (discountInput) {
            discountInput.addEventListener('input', (e) => this.updateDiscount(parseFloat(e.target.value) || 0));
        }

        // Discount Input (Mobile Sheet)
        const sheetDiscountInput = document.getElementById('sheet-discount-input');
        if (sheetDiscountInput) {
            sheetDiscountInput.addEventListener('input', (e) => this.updateDiscount(parseFloat(e.target.value) || 0));
        }

        // Global Keyboard Shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'F9') {
                e.preventDefault();
                if (this.state.cart.length > 0) this.openPaymentModal();
            }
            if (e.key === 'Escape') {
                this.collapseSheet();
                // Close modals if open
                const activeModal = document.querySelector('.modal.show');
                if (activeModal) {
                    const modal = bootstrap.Modal.getInstance(activeModal);
                    if (modal) modal.hide();
                }
            }
        });

        // Customer Search
        const customerInput = document.getElementById('customer-search');
        if (customerInput) {
            customerInput.addEventListener('input', (e) => this.searchCustomers(e.target.value));
        }
    },

    // =====================================================
    // CATALOG & SEARCH
    // =====================================================
    loadInitialCatalog() {
        this.handleSearch(''); // Load popular/recent or all
    },

    handleSearch(query) {
        const clearBtn = document.getElementById('search-clear-btn');
        if (clearBtn) clearBtn.style.display = query.length > 0 ? 'block' : 'none';

        const category = this.state.activeCategory;
        const url = `/api/search_products.php?term=${encodeURIComponent(query)}&categoria_id=${category}&in_stock=1`;

        fetch(url)
            .then(res => res.json())
            .then(data => {
                console.log("PDV API Results:", data);
                this.state.searchResults = data;
                this.renderResults();
            })
            .catch(err => console.error("Search error:", err));
    },

    filterCategory(id, el) {
        this.state.activeCategory = id;
        // Update UI
        document.querySelectorAll('.pdv-chip').forEach(c => c.classList.remove('active'));
        if (el) el.classList.add('active');
        
        const query = document.getElementById('pdv-search').value;
        this.handleSearch(query);
    },

    clearSearch() {
        const input = document.getElementById('pdv-search');
        input.value = '';
        input.focus();
        this.handleSearch('');
    },

    // =====================================================
    // CART MANAGEMENT
    // =====================================================
    addToCart(product) {
        if (product.quantity <= 0) {
            this.notify("Produto sem estoque disponível.", "error");
            return;
        }

        const existing = this.state.cart.find(i => i.id === product.id);
        if (existing) {
            if (existing.quantity >= product.quantity) {
                this.notify("Quantidade máxima em estoque atingida.", "warning");
                return;
            }
            existing.quantity++;
        } else {
            this.state.cart.push({
                ...product,
                quantity: 1
            });
        }
        this.notify(`Adicionado: ${product.name}`, "success");
        this.render();
    },

    updateQuantity(id, delta) {
        const item = this.state.cart.find(i => i.id === id);
        if (!item) return;

        item.quantity += delta;
        if (item.quantity <= 0) {
            this.state.cart = this.state.cart.filter(i => i.id !== id);
        }
        this.render();
    },

    updateDiscount(val) {
        this.state.discount = val;
        
        // Sincronizar inputs
        const dInput = document.getElementById('cart-discount-input');
        const sInput = document.getElementById('sheet-discount-input');
        if (dInput) dInput.value = val.toFixed(2);
        if (sInput) sInput.value = val.toFixed(2);
        
        this.render();
    },

    clearCart() {
        if (this.state.cart.length === 0) return;
        if (confirm("Deseja limpar todo o carrinho?")) {
            this.state.cart = [];
            this.state.discount = 0;
            this.render();
        }
    },

    // =====================================================
    // MOBILE SHEET LOGIC
    // =====================================================
    toggleSheet() {
        this.state.isSheetExpanded ? this.collapseSheet() : this.expandSheet();
    },

    expandSheet() {
        if (this.state.cart.length === 0) return;
        this.state.isSheetExpanded = true;
        const sheet = document.getElementById('pdv-sheet');
        if (sheet) {
            sheet.classList.add('expanded');
            sheet.style.setProperty('bottom', '72px', 'important');
        }
        const backdrop = document.getElementById('sheet-backdrop');
        if (backdrop) {
            backdrop.style.display = 'block';
            setTimeout(() => backdrop.style.opacity = '1', 10);
        }
    },

    collapseSheet() {
        this.state.isSheetExpanded = false;
        const sheet = document.getElementById('pdv-sheet');
        if (sheet) {
            sheet.classList.remove('expanded');
            sheet.style.setProperty('bottom', '-100%', 'important');
        }
        const backdrop = document.getElementById('sheet-backdrop');
        if (backdrop) {
            backdrop.style.opacity = '0';
            setTimeout(() => backdrop.style.display = 'none', 300);
        }
    },

    // =====================================================
    // PAYMENT LOGIC
    // =====================================================
    openPaymentModal() {
        if (this.state.cart.length === 0) return;
        
        this.state.payments = [];
        this.updatePaymentSummary();
        
        const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
        modal.show();
        
        // Auto-focus value input
        setTimeout(() => {
            const modalTotalSale = document.getElementById('modal-total-sale');
            if (modalTotalSale) modalTotalSale.textContent = this.formatCurrency(this.state.total);
            
            const valInput = document.getElementById('payment-value-input');
            if (valInput) {
                valInput.value = this.state.total.toFixed(2);
                valInput.select();
            }
        }, 300);
    },

    fastCheckout(method) {
        this.state.payments = [{
            method: method,
            value: this.state.total,
            name: this.getPaymentName(method)
        }];
        this.updatePaymentSummary();
        this.confirmSale();
    },

    addPayment() {
        const method = document.getElementById('payment-method-select').value;
        const value = parseFloat(document.getElementById('payment-value-input').value);

        if (isNaN(value) || value <= 0) return;

        this.state.payments.push({
            method: method,
            value: value,
            name: this.getPaymentName(method)
        });

        document.getElementById('payment-value-input').value = '';
        this.updatePaymentSummary();
    },

    removePayment(index) {
        this.state.payments.splice(index, 1);
        this.updatePaymentSummary();
    },

    updatePaymentSummary() {
        const totalPaid = this.state.payments.reduce((acc, p) => acc + p.value, 0);
        const remaining = Math.max(0, this.state.total - totalPaid);
        const change = Math.max(0, totalPaid - this.state.total);

        document.getElementById('modal-total-paid').textContent = this.formatCurrency(totalPaid);
        document.getElementById('modal-remaining').textContent = this.formatCurrency(remaining);
        document.getElementById('modal-change').textContent = this.formatCurrency(change);

        // UI Toggles
        document.getElementById('remaining-container').classList.toggle('d-none', remaining <= 0);
        document.getElementById('change-container').classList.toggle('d-none', change <= 0);

        // Confirm Button state
        const btn = document.getElementById('btn-confirm-sale');
        btn.disabled = totalPaid < this.state.total;
        btn.classList.toggle('disabled', totalPaid < this.state.total);

        this.renderPaymentList();
    },

    confirmSale() {
        if (this.state.isProcessing) return;
        this.state.isProcessing = true;

        const payload = {
            cart: this.state.cart,
            payments: this.state.payments,
            discount_amount: this.state.discount,
            cliente_id: this.state.selectedCustomer ? this.state.selectedCustomer.id : null
        };

        fetch('/api/process_sale.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            this.state.isProcessing = false;
            if (data.success) {
                this.state.lastSaleId = data.venda_id;
                this.notify("Venda processada com sucesso!", "success");
                this.showSuccess();
            } else {
                this.notify("Erro ao processar: " + data.error, "error");
            }
        })
        .catch(err => {
            this.state.isProcessing = false;
            this.notify("Erro crítico de conexão.", "error");
        });
    },

    showSuccess() {
        // Hide payment modal
        bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
        
        // Show success modal
        const successModal = new bootstrap.Modal(document.getElementById('successModal'));
        successModal.show();
        
        // Clear PDV state
        this.state.cart = [];
        this.state.payments = [];
        this.state.discount = 0;
        this.state.selectedCustomer = null;
        this.render();
    },

    closeSuccessModal() {
        bootstrap.Modal.getInstance(document.getElementById('successModal')).hide();
    },

    printReceipt() {
        if (!this.state.lastSaleId) return;
        const url = `/employee/imprimir_venda.php?id=${this.state.lastSaleId}`;
        window.open(url, '_blank', 'width=400,height=600');
    },

    // =====================================================
    // RENDERING
    // =====================================================
    render() {
        this.calculateTotals();
        this.renderCart();
        this.renderSheet();
        this.updateGlobalBadges();
    },

    calculateTotals() {
        this.state.subtotal = this.state.cart.reduce((acc, i) => acc + (i.price * i.quantity), 0);
        this.state.total = Math.max(0, this.state.subtotal - this.state.discount);
    },

    renderResults() {
        const container = document.getElementById('pdv-results');
        
        // Safety check to prevent "map is not a function" error
        if (!Array.isArray(this.state.searchResults)) {
            console.error("API error: searchResults is not an array", this.state.searchResults);
            container.innerHTML = `
                <div class="pdv-empty-state">
                    <div class="pdv-empty-icon text-danger"><i class="fas fa-exclamation-triangle"></i></div>
                    <h3>Erro de Conexão</h3>
                    <p>Não foi possível carregar os produtos. Tente recarregar a página.</p>
                </div>`;
            return;
        }

        if (this.state.searchResults.length === 0) {
            container.innerHTML = `
                <div class="pdv-empty-state">
                    <div class="pdv-empty-icon"><i class="fas fa-search"></i></div>
                    <h3>Sem resultados</h3>
                    <p>Não encontramos produtos com esse nome.</p>
                </div>`;
            return;
        }

        container.innerHTML = this.state.searchResults.map(p => `
            <div class="pdv-product-card" onclick='PDV.addToCart(${JSON.stringify(p)})'>
                <div class="pdv-item-icon">
                    <i class="fas fa-cube"></i>
                </div>
                <div class="pdv-item-info">
                    <span class="pdv-item-name">${p.name}</span>
                    <span class="pdv-item-price">${this.formatCurrency(p.price)}</span>
                    <span class="pdv-item-stock">Estoque: ${p.quantity}</span>
                </div>
            </div>
        `).join('');
    },

    renderCart() {
        const container = document.getElementById('cart-container');
        if(!container) return;
        
        // Update totals regardless of cart emptiness
        document.getElementById('cart-subtotal').textContent = this.formatCurrency(this.state.subtotal);
        document.getElementById('cart-total').textContent = this.formatCurrency(this.state.total);

        if (this.state.cart.length === 0) {
            container.innerHTML = `
                <div class="text-center text-muted" style="margin-top: 80px; padding: 20px;">
                    <div style="width: 80px; height: 80px; background: #f0f4f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                        <i class="fas fa-shopping-basket" style="font-size: 2rem; color: #0061a4; opacity: 0.5;"></i>
                    </div>
                    <p class="small fw-bold" style="color: #001d35; font-size: 1rem;">Carrinho Vazio</p>
                    <p class="small text-muted" style="line-height: 1.4;">Selecione produtos no catálogo para iniciar a venda.</p>
                </div>
            `;
            const btn = document.getElementById('btn-open-payment');
            if(btn) btn.disabled = true;
            return;
        } else {
            container.innerHTML = this.state.cart.map((i, index) => `
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
                    <div class="pdv-cart-item-price ms-3 text-end" style="min-width: 80px;">
                        ${this.formatCurrency(i.price * i.quantity)}
                    </div>
                </div>
            `).join('');
        }
        
        const btn = document.getElementById('btn-open-payment');
        if(btn) btn.disabled = this.state.cart.length === 0;
    },

    renderSheet() {
        const itemsContainer = document.getElementById('sheet-cart-items');
        itemsContainer.innerHTML = this.state.cart.map(i => `
            <div class="pdv-cart-item">
                <div class="pdv-cart-item-info">
                    <span class="pdv-cart-item-name">${i.name}</span>
                    <span class="pdv-cart-item-meta">${i.quantity}x ${this.formatCurrency(i.price)}</span>
                </div>
                <div class="pdv-cart-item-price">
                    ${this.formatCurrency(i.price * i.quantity)}
                </div>
            </div>
        `).join('');

        document.getElementById('sheet-total').textContent = this.formatCurrency(this.state.total);
        const sheetDiscountEl = document.getElementById('sheet-discount');
        if (sheetDiscountEl) sheetDiscountEl.textContent = `- ${this.formatCurrency(this.state.discount)}`;
        document.getElementById('sheet-final-total').textContent = this.formatCurrency(this.state.total);
        document.getElementById('sheet-qty').textContent = this.state.cart.reduce((acc, i) => acc + i.quantity, 0);
    },

    renderPaymentList() {
        const list = document.getElementById('payments-list');
        list.innerHTML = this.state.payments.map((p, idx) => `
            <li class="pdv-payment-item">
                <div class="pdv-payment-info">
                    <span class="pdv-payment-name">${p.name}</span>
                    <span class="pdv-payment-val">${this.formatCurrency(p.value)}</span>
                </div>
                <i class="fas fa-times pdv-remove-payment" onclick="PDV.removePayment(${idx})"></i>
            </li>
        `).join('');
    },

    updateGlobalBadges() {
        const qty = this.state.cart.reduce((acc, i) => acc + i.quantity, 0);
        
        const topBadge = document.getElementById('topbar-cart-badge');
        if (topBadge) topBadge.textContent = qty;
        
        const countBadge = document.getElementById('cart-count-badge');
        if (countBadge) countBadge.textContent = `${qty} itens`;
    },

    // =====================================================
    // UTILS
    // =====================================================
    formatCurrency(val) {
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(val);
    },

    getPaymentName(method) {
        const names = {
            'dinheiro': 'Dinheiro',
            'pix': 'PIX',
            'cartao_debito': 'Cartão Débito',
            'cartao_credito': 'Cartão Crédito'
        };
        return names[method] || method;
    },

    notify(msg, type = 'info') {
        // Sistema de Toast Premium (Google Style)
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const icons = {
            'success': 'fa-check-circle',
            'error': 'fa-exclamation-circle',
            'warning': 'fa-exclamation-triangle',
            'info': 'fa-info-circle'
        };

        const titles = {
            'success': 'Sucesso',
            'error': 'Erro',
            'warning': 'Atenção',
            'info': 'Aviso'
        };

        const toast = document.createElement('div');
        toast.className = `bh-toast bh-toast-${type}`;
        toast.innerHTML = `
            <div class="bh-toast-icon"><i class="fas ${icons[type]}"></i></div>
            <div class="bh-toast-content">
                <span class="bh-toast-title">${titles[type]}</span>
                <span class="bh-toast-msg">${msg}</span>
            </div>
        `;

        container.appendChild(toast);
        
        // Trigger animação
        setTimeout(() => toast.classList.add('active'), 10);

        // Auto-remove
        setTimeout(() => {
            toast.classList.remove('active');
            setTimeout(() => toast.remove(), 400);
        }, 3500);

        toast.onclick = () => {
            toast.classList.remove('active');
            setTimeout(() => toast.remove(), 400);
        };
    },

    searchCustomers(query) {
        const results = document.getElementById('customer-results');
        if (query.length < 2) {
            results.style.display = 'none';
            return;
        }

        fetch(`/api/get_clientes.php?term=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                if (data.length > 0) {
                    results.innerHTML = data.map(c => `
                        <div class="pdv-customer-result-item" onclick='PDV.selectCustomer(${JSON.stringify(c)})'>
                            <strong>${c.nome}</strong><br>
                            <small>${c.cpf_cnpj || 'Sem documento'}</small>
                        </div>
                    `).join('');
                    results.style.display = 'block';
                } else {
                    results.style.display = 'none';
                }
            });
    },

    selectCustomer(customer) {
        this.state.selectedCustomer = customer;
        const results = document.getElementById('customer-results');
        results.style.display = 'none';
        
        const input = document.getElementById('customer-search');
        input.value = customer.nome;
        input.classList.add('selected');
        
        document.getElementById('selected-customer-info').innerHTML = `
            <div class="small mt-1 text-primary fw-bold">
                <i class="fas fa-check-circle"></i> ${customer.nome} selecionado
            </div>`;
    }
};

// Initialize
document.addEventListener('DOMContentLoaded', () => PDV.init());
