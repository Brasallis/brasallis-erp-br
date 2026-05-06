/**
 * PDV Zen - Lógica de Operação (Brasallis)
 * Foco em minimalismo, velocidade e integração com legado.
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
        selectedIndex: -1,
        isProcessing: false
    },

    init() {
        console.log("PDV Zen Material v3.0 - Carregado com Sucesso");
        this.bindEvents();
        this.render();
    },

    bindEvents() {
        // Busca de Produtos (Atalho F2)
        const searchInput = document.getElementById('pdv-search');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.searchProducts(e.target.value));
            searchInput.addEventListener('keydown', (e) => this.handleSearchKeydown(e));
        }

        // Atalhos Globais
        document.addEventListener('keydown', (e) => {
            if (e.key === 'F2') {
                e.preventDefault();
                document.getElementById('pdv-search').focus();
            }
            if (e.key === 'F9') {
                e.preventDefault();
                if (this.state.cart.length > 0) this.openPaymentModal();
            }
            if (e.key === 'Escape') {
                const modalEl = document.getElementById('paymentModal');
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalEl.classList.contains('show') && modalInstance) {
                    modalInstance.hide();
                } else {
                    this.clearCart();
                }
            }
        });

        // Busca de Clientes
        const customerSearch = document.getElementById('customer-search');
        if (customerSearch) {
            customerSearch.addEventListener('input', (e) => this.searchCustomers(e.target.value));
        }

        // Campo de Desconto
        const discountInput = document.getElementById('cart-discount-input');
        if (discountInput) {
            discountInput.addEventListener('change', (e) => this.applyDiscount(e.target.value));
        }
    },

    searchProducts(query) {
        if (query.length < 2) {
            this.state.searchResults = [];
            this.state.selectedIndex = -1;
            this.renderResults();
            return;
        }

        // Usando API física legada
        fetch(`../api/search_products.php?term=${encodeURIComponent(query)}&in_stock=1`)
            .then(res => res.json())
            .then(products => {
                this.state.searchResults = products;
                this.state.selectedIndex = -1;
                this.renderResults();
            })
            .catch(err => console.error("Erro na busca:", err));
    },

    handleSearchKeydown(e) {
        const resultsCount = this.state.searchResults.length;
        if (resultsCount === 0) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            this.state.selectedIndex = (this.state.selectedIndex + 1) % resultsCount;
            this.renderResults();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            this.state.selectedIndex = (this.state.selectedIndex - 1 + resultsCount) % resultsCount;
            this.renderResults();
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (this.state.selectedIndex >= 0) {
                this.addToCart(this.state.searchResults[this.state.selectedIndex]);
            } else if (resultsCount === 1) {
                this.addToCart(this.state.searchResults[0]);
            }
        }
    },
    addToCart(product) {
        // Garantir campos corretos da API legada (price, name, etc)
        const itemPrice = parseFloat(product.price);
        const existing = this.state.cart.find(item => item.id === product.id);
        
        if (existing) {
            existing.qty++;
        } else {
            this.state.cart.push({ 
                id: product.id, 
                name: product.name, 
                price: itemPrice, 
                qty: 1 
            });
        }
        
        // Feedback Visual Mobile
        if (window.innerWidth < 992) {
            this.showMiniFeedback();
            
            // Animar o card clicado
            const cards = document.querySelectorAll('.m3-product-card');
            cards.forEach(card => {
                if (card.innerText.includes(product.name)) {
                    card.classList.add('m3-added');
                    setTimeout(() => card.classList.remove('m3-added'), 500);
                }
            });
        }

        // Limpar busca
        this.state.searchResults = [];
        this.state.selectedIndex = -1;
        const searchInput = document.getElementById('pdv-search');
        if (searchInput) {
            searchInput.value = '';
            searchInput.focus();
        }
        
        this.calculateTotals();
        this.render();
    },

    showMiniFeedback() {
        const sheet = document.getElementById('pdv-sheet');
        if (!sheet) return;
        sheet.style.transform = 'translateY(calc(100% - 90px))';
        setTimeout(() => {
            sheet.style.transform = '';
        }, 200);
    },

    removeFromCart(index) {
        this.state.cart.splice(index, 1);
        this.calculateTotals();
        this.render();
    },

    clearCart() {
        if (this.state.cart.length === 0) return;
        if (confirm("Deseja realmente limpar o carrinho?")) {
            this.state.cart = [];
            this.state.payments = [];
            this.state.selectedCustomer = null;
            this.state.discount = 0;
            this.calculateTotals();
            this.render();
        }
    },

    applyDiscount(value) {
        this.state.discount = parseFloat(value) || 0;
        this.calculateTotals();
        this.render();
    },

    searchCustomers(query) {
        if (query.length < 2) return;
        
        // Chama API de CRM
        fetch(`../api/v1/crm/clientes.php?search=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(response => {
                const results = response.data || [];
                this.renderCustomerResults(results);
            });
    },

    selectCustomer(customer) {
        this.state.selectedCustomer = customer;
        const searchInput = document.getElementById('customer-search');
        if (searchInput) searchInput.value = customer ? customer.nome : '';
        document.getElementById('customer-results').innerHTML = '';
        this.render();
    },

    calculateTotals() {
        this.state.subtotal = this.state.cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
        this.state.total = Math.max(0, this.state.subtotal - this.state.discount);
    },

    render() {
        this.renderCart();
        this.renderTotals();
        this.renderCustomerInfo();
        this.renderSheetCart();
    },

    renderSheetCart() {
        const container = document.getElementById('sheet-cart-items');
        if (!container) return;

        if (this.state.cart.length === 0) {
            container.innerHTML = '<p class="text-center text-muted my-5">Seu carrinho está vazio</p>';
            return;
        }

        container.innerHTML = this.state.cart.map((item, idx) => `
            <div class="d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded-4">
                <div class="d-flex align-items-center">
                    <div class="item-qty me-3">${item.qty}</div>
                    <div>
                        <div class="fw-bold text-navy small">${item.name}</div>
                        <div class="text-muted extra-small">R$ ${item.price.toFixed(2)}</div>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <div class="fw-bold text-navy me-3">R$ ${(item.price * item.qty).toFixed(2)}</div>
                    <button class="btn btn-sm text-danger" onclick="PDV.removeFromCart(${idx})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');
    },

    renderCart() {
        const container = document.getElementById('cart-container');
        if (!container) return;

        if (this.state.cart.length === 0) {
            container.innerHTML = `
                <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted opacity-30 py-5">
                    <i class="fas fa-shopping-basket fa-3x mb-2"></i>
                    <p class="small fw-bold">Carrinho Vazio</p>
                </div>
            `;
            document.getElementById('btn-open-payment').disabled = true;
            return;
        }

        document.getElementById('btn-open-payment').disabled = false;
        container.innerHTML = this.state.cart.map((item, idx) => `
            <div class="zen-item-card">
                <div class="d-flex align-items-center">
                    <div class="item-qty">${item.qty}</div>
                    <div class="product-info">
                        <div class="fw-bold text-navy small">${item.name}</div>
                        <div class="text-muted extra-small">R$ ${item.price.toFixed(2)}</div>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <div class="fw-bold text-success me-2">R$ ${(item.price * item.qty).toFixed(2)}</div>
                    <button class="btn btn-sm text-danger opacity-50" onclick="PDV.removeFromCart(${idx})">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </div>
        `).join('');
    },

    renderResults() {
        const grid = document.getElementById('pdv-results');
        if (!grid) return;

        if (this.state.searchResults.length === 0) {
            grid.innerHTML = `
                <div class="col-12 text-center py-5 text-muted opacity-30">
                    <i class="fas fa-barcode fa-4x mb-3"></i>
                    <p>Aguardando busca...</p>
                </div>
            `;
            return;
        }

        grid.innerHTML = this.state.searchResults.map((p, idx) => `
            <div class="m3-product-card ${this.state.selectedIndex === idx ? 'selected' : ''}" onclick='PDV.addToCart(${JSON.stringify(p)})'>
                <div class="product-icon-m3"><i class="fas fa-cube"></i></div>
                <div class="product-info">
                    <h6>${p.name}</h6>
                    <span class="extra-small text-muted">${p.sku || 'S/ SKU'}</span>
                </div>
                <div class="product-meta">
                    <span class="stock-tag ${p.quantity < 5 ? 'text-danger' : ''}">Est: ${p.quantity}</span>
                    <span class="price-tag">R$ ${parseFloat(p.price).toFixed(2)}</span>
                </div>
            </div>
        `).join('');
    },

    renderTotals() {
        const qty = this.state.cart.length;
        const totalFormatted = `R$ ${this.state.total.toFixed(2)}`;

        // Desktop
        const cartQtyEl = document.getElementById('cart-qty');
        const cartSubtotalEl = document.getElementById('cart-subtotal');
        const cartTotalEl = document.getElementById('cart-total');
        
        if (cartQtyEl) cartQtyEl.innerText = `${qty} itens`;
        if (cartSubtotalEl) cartSubtotalEl.innerText = `R$ ${this.state.subtotal.toFixed(2)}`;
        if (cartTotalEl) cartTotalEl.innerText = totalFormatted;
        
        // Mobile Bar
        const mobileQtyEl = document.getElementById('mobile-cart-qty');
        const mobileTotalEl = document.getElementById('mobile-cart-total');
        if (mobileQtyEl) mobileQtyEl.innerText = `${qty} itens no carrinho`;
        if (mobileTotalEl) mobileTotalEl.innerText = totalFormatted;

        const modalTotal = document.getElementById('modal-total-sale');
        if (modalTotal) modalTotal.innerText = totalFormatted;

        // Sync Sheet
        const sheetTotalEl = document.getElementById('sheet-total');
        const sheetQtyEl = document.getElementById('sheet-qty');
        const sheetFinalTotalEl = document.getElementById('sheet-final-total');
        const sheetDiscountEl = document.getElementById('sheet-discount');

        if (sheetTotalEl) sheetTotalEl.innerText = totalFormatted;
        if (sheetQtyEl) sheetQtyEl.innerText = qty;
        if (sheetFinalTotalEl) sheetFinalTotalEl.innerText = totalFormatted;
        if (sheetDiscountEl) sheetDiscountEl.innerText = `- R$ ${this.state.discount.toFixed(2)}`;
    },

    toggleBottomSheet() {
        const sheet = document.getElementById('pdv-sheet');
        const overlay = document.getElementById('sheet-overlay');
        sheet.classList.toggle('collapsed');
        overlay.classList.toggle('active');
        
        if (!sheet.classList.contains('collapsed')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    },

    filterCategory(cat, el) {
        document.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
        el.classList.add('active');
        
        // Simular filtro (no futuro usar API com category_id)
        this.searchProducts(cat === 'all' ? '' : cat);
    },

    toggleMobileView() {
        // Obsoleto mas mantido para compatibilidade se necessário
    },

    renderCustomerInfo() {
        const infoEl = document.getElementById('selected-customer-info');
        if (!infoEl) return;

        if (this.state.selectedCustomer) {
            infoEl.innerHTML = `
                <div class="d-flex align-items-center p-2 bg-success-soft rounded-3 mt-2">
                    <i class="fas fa-check-circle text-success me-2"></i>
                    <div class="extra-small fw-bold text-navy">${this.state.selectedCustomer.nome}</div>
                    <button class="btn btn-sm btn-link text-danger ms-auto p-0" onclick="PDV.selectCustomer(null)"><i class="fas fa-times"></i></button>
                </div>
            `;
        } else {
            infoEl.innerHTML = '';
        }
    },

    renderCustomerResults(results) {
        const container = document.getElementById('customer-results');
        if (!container) return;

        if (results.length === 0) {
            container.innerHTML = '<div class="p-2 small text-muted">Nenhum cliente encontrado</div>';
            return;
        }

        container.innerHTML = results.map(c => `
            <div class="p-2 border-bottom cursor-pointer hover-bg-light small" onclick='PDV.selectCustomer(${JSON.stringify(c)})'>
                <strong>${c.nome}</strong><br>
                <span class="text-muted extra-small">${c.cpf_cnpj || c.email || ''}</span>
            </div>
        `).join('');
    },

    // --- LÓGICA DE PAGAMENTO ---
    openPaymentModal() {
        this.state.payments = [];
        this.updatePaymentUI();
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('paymentModal'));
        modal.show();
    },

    fastCheckout(method) {
        // Limpa pagamentos anteriores e preenche o total com o método escolhido
        this.state.payments = [];
        const methodNames = {
            'dinheiro': 'Dinheiro',
            'pix': 'PIX',
            'cartao_debito': 'C. Débito',
            'cartao_credito': 'C. Crédito'
        };
        this.state.payments.push({ 
            method: method, 
            name: methodNames[method], 
            value: this.state.total 
        });
        
        this.updatePaymentUI();
        this.confirmSale(); // Confirma direto
    },

    addPayment() {
        const valInput = document.getElementById('payment-value-input');
        const val = parseFloat(valInput.value);
        if (isNaN(val) || val <= 0) return;

        const methodEl = document.getElementById('payment-method-select');
        const method = methodEl.value;
        const methodName = methodEl.options[methodEl.selectedIndex].text;

        this.state.payments.push({ method, name: methodName, value: val });
        valInput.value = '';
        this.updatePaymentUI();
    },

    removePayment(index) {
        this.state.payments.splice(index, 1);
        this.updatePaymentUI();
    },

    updatePaymentUI() {
        const list = document.getElementById('payments-list');
        const totalPaid = this.state.payments.reduce((sum, p) => sum + p.value, 0);
        const remaining = this.state.total - totalPaid;

        if (list) {
            list.innerHTML = this.state.payments.map((p, idx) => `
                <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3 border-0 bg-light rounded-3 mb-2">
                    <div class="small fw-bold">
                        <span class="text-navy opacity-50 me-2">${p.name}</span>
                        <span class="text-success">R$ ${p.value.toFixed(2)}</span>
                    </div>
                    <button class="btn btn-link text-danger p-0" onclick="PDV.removePayment(${idx})"><i class="fas fa-minus-circle"></i></button>
                </li>
            `).join('') || '<li class="text-center text-muted small py-3">Aguardando pagamento...</li>';
        }

        document.getElementById('modal-total-paid').innerText = `R$ ${totalPaid.toFixed(2)}`;
        
        const confirmBtn = document.getElementById('btn-confirm-sale');
        if (remaining <= 0.01) {
            confirmBtn.classList.remove('disabled');
            document.getElementById('remaining-container').classList.add('d-none');
            if (remaining < 0) {
                document.getElementById('change-container').classList.remove('d-none');
                document.getElementById('modal-change').innerText = `R$ ${Math.abs(remaining).toFixed(2)}`;
            } else {
                document.getElementById('change-container').classList.add('d-none');
            }
        } else {
            confirmBtn.classList.add('disabled');
            document.getElementById('remaining-container').classList.remove('d-none');
            document.getElementById('change-container').classList.add('d-none');
            document.getElementById('modal-remaining').innerText = `R$ ${remaining.toFixed(2)}`;
            document.getElementById('payment-value-input').value = remaining.toFixed(2);
        }
    },

    confirmSale() {
        if (this.state.isProcessing) return;
        this.state.isProcessing = true;

        const btn = document.getElementById('btn-confirm-sale');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> FINALIZANDO...';
        btn.classList.add('disabled');

        // Adaptando para API legada api/process_sale.php
        const payload = {
            cart: this.state.cart.map(item => ({
                id: item.id,
                quantity: item.qty,
                price: item.price
            })),
            payments: this.state.payments,
            cliente_id: this.state.selectedCustomer ? this.state.selectedCustomer.id : null,
            discount_amount: this.state.discount
        };

        fetch('../api/process_sale.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                this.showToast("Sucesso", "Venda finalizada com sucesso!", "success");
                
                // Fechar modal de pagamento corretamente
                const paymentEl = document.getElementById('paymentModal');
                const paymentModal = bootstrap.Modal.getOrCreateInstance(paymentEl);
                paymentModal.hide();
                
                // Força a remoção do backdrop caso a animação falhe (tela escura)
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';

                // Armazenar ID para impressão se solicitado
                this.state.lastSaleId = data.venda_id;

                // Abrir modal de sucesso com um delay seguro
                setTimeout(() => {
                    const successModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('successModal'));
                    successModal.show();
                }, 300);

                this.state.cart = [];
                this.state.payments = [];
                this.state.selectedCustomer = null;
                this.state.discount = 0;
                this.calculateTotals();
                this.render();
            } else {
                this.showToast("Erro", data.error || "Erro ao processar venda", "danger");
            }
        })
        .catch(err => {
            console.error(err);
            this.showToast("Erro", "Falha na comunicação com o servidor", "danger");
        })
        .finally(() => {
            this.state.isProcessing = false;
            btn.innerHTML = originalText;
            btn.classList.remove('disabled');
        });
    },

    showToast(title, msg, type) {
        const toast = document.createElement('div');
        toast.className = `zen-toast bg-${type}`;
        toast.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'} me-2"></i>
            <div><strong>${title}:</strong> ${msg}</div>
        `;
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 500);
        }, 3000);
    },

    printReceipt() {
        if (this.state.lastSaleId) {
            window.open(`imprimir_venda.php?id=${this.state.lastSaleId}`, '_blank');
        }
        this.closeSuccessModal();
    },

    closeSuccessModal() {
        const successModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('successModal'));
        successModal.hide();
        
        // Remover manualmente qualquer backdrop travado (fallback de segurança)
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';

        // Reset focus para a próxima venda
        const searchInput = document.getElementById('pdv-search');
        if (searchInput) searchInput.focus();
    }
};

// Iniciar ao carregar o DOM
document.addEventListener('DOMContentLoaded', () => PDV.init());
