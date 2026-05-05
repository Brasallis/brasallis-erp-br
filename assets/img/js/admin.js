document.addEventListener('DOMContentLoaded', function () {

    // Gráfico de Vendas por Categoria
    const salesByCategoryCtx = document.getElementById('salesByCategoryChart');
    if (salesByCategoryCtx) {
        new Chart(salesByCategoryCtx, {
            type: 'doughnut',
            data: {
                labels: ['Eletrônicos', 'Acessórios', 'Periféricos'],
                datasets: [{
                    label: 'Vendas por Categoria',
                    data: [45, 25, 30],
                    backgroundColor: [
                        'rgba(66, 133, 244, 0.8)', // Azul Google
                        'rgba(52, 168, 83, 0.8)', // Verde Google
                        'rgba(251, 188, 5, 0.8)'   // Amarelo Google
                    ],
                    borderColor: [
                        'rgba(66, 133, 244, 1)',
                        'rgba(52, 168, 83, 1)',
                        'rgba(251, 188, 5, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Distribuição de Vendas por Categoria'
                    }
                }
            }
        });
    }

    // Lógica para o modal de edição de produto
    const editProductModal = document.getElementById('editProductModal');
    if (editProductModal) {
        editProductModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const productId = button.getAttribute('data-id');

            // Faz uma requisição para buscar os dados do produto
            fetch(`get_product.php?id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                    } else {
                        const modalTitle = editProductModal.querySelector('.modal-title');
                        const productIdInput = editProductModal.querySelector('#editProductId');
                        const productNameInput = editProductModal.querySelector('#editProductName');
                        const productDescriptionInput = editProductModal.querySelector('#editProductDescription');
                        const productPriceInput = editProductModal.querySelector('#editProductPrice');
                        const productQuantityInput = editProductModal.querySelector('#editProductQuantity');
                        const productMinimumStockInput = editProductModal.querySelector('#editProductMinimumStock');
                        const productCategoryInput = editProductModal.querySelector('#editProductCategory');
                        const productBatchInput = editProductModal.querySelector('#editProductBatch');
                        const productValidityInput = editProductModal.querySelector('#editProductValidity');
                        const productObservationsInput = editProductModal.querySelector('#editProductObservations');

                        modalTitle.textContent = `Editar Produto: ${data.name}`;
                        productIdInput.value = data.id;
                        productNameInput.value = data.name;
                        productDescriptionInput.value = data.description;
                        productPriceInput.value = data.price;
                        productQuantityInput.value = data.quantity;
                        productMinimumStockInput.value = data.minimum_stock;
                        productCategoryInput.value = data.category || '';
                        productBatchInput.value = data.lote || '';
                        productValidityInput.value = data.validade || '';
                        productObservationsInput.value = data.observacoes || '';
                    }
                })
                .catch(error => {
                    console.error('Erro ao buscar dados do produto:', error);
                    alert('Ocorreu um erro ao buscar os dados do produto.');
                });
        });
    }

    // Lógica para o modal de edição de usuário
    // REMOVIDO: A lógica agora está no próprio arquivo usuarios.php para suportar setores e cargos corretamente.
    /*
    const editUserModal = document.getElementById('editUserModal');
    if (editUserModal) {
        // ... (Logic moved to usuarios.php)
    }
    */
});