document.addEventListener('DOMContentLoaded', function() {
    loadSales();
    loadVendedores();
    setupEventListeners();
});

// Event Listeners
function setupEventListeners() {
    // Pesquisa de vendas
    document.getElementById('searchSale').addEventListener('input', debounce(function(e) {
        searchSales(e.target.value);
    }, 300));

    // Filtros de data
    document.getElementById('startDate').addEventListener('change', filterSales);
    document.getElementById('endDate').addEventListener('change', filterSales);
    document.getElementById('statusFilter').addEventListener('change', filterSales);
    document.getElementById('vendedorFilter').addEventListener('change', filterSales);

    // Pesquisa de clientes
    document.getElementById('clientSearch').addEventListener('input', debounce(function(e) {
        searchClients(e.target.value);
    }, 300));

    // Pesquisa de produtos
    document.getElementById('productSearch').addEventListener('input', debounce(function(e) {
        searchProducts(e.target.value);
    }, 300));

    // Atualização de desconto
    document.getElementById('discount').addEventListener('input', updateTotalValue);
}

// Funções de Carregamento
async function loadSales(page = 1) {
    try {
        const sales = await fetchSalesFromAPI(); // Simular chamada à API
        renderSales(sales);
    } catch (error) {
        showError('Erro ao carregar vendas');
    }
}

async function loadVendedores() {
    try {
        const vendedores = await fetchVendedoresFromAPI(); // Simular chamada à API
        const select = document.getElementById('vendedorFilter');
        vendedores.forEach(vendedor => {
            select.add(new Option(vendedor.nome, vendedor.id));
        });
    } catch (error) {
        showError('Erro ao carregar vendedores');
    }
}

// Funções de Renderização
function renderSales(sales) {
    const tbody = document.getElementById('salesTableBody');
    tbody.innerHTML = '';

    sales.forEach(sale => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${sale.numero}</td>
            <td>${formatDate(sale.data)}</td>
            <td>${sale.cliente}</td>
            <td>${sale.vendedor}</td>
            <td>${formatCurrency(sale.valor)}</td>
            <td>
                <span class="status-badge status-${sale.status}">
                    ${formatStatus(sale.status)}
                </span>
            </td>
            <td>
                <button class="btn-icon" onclick="viewSale(${sale.id})" title="Ver Detalhes">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn-icon" onclick="printSale(${sale.id})" title="Imprimir">
                    <i class="fas fa-print"></i>
                </button>
                ${sale.status === 'pendente' ? `
                    <button class="btn-icon" onclick="cancelSale(${sale.id})" title="Cancelar">
                        <i class="fas fa-times"></i>
                    </button>
                ` : ''}
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// Funções do Modal de Nova Venda
function openNewSaleModal() {
    document.getElementById('modalTitle').textContent = 'Nova Venda';
    document.getElementById('saleForm').reset();
    document.getElementById('selectedProducts').innerHTML = '';
    updateTotalValue();
    document.getElementById('saleModal').style.display = 'block';
}

function closeSaleModal() {
    document.getElementById('saleModal').style.display = 'none';
    document.getElementById('saleForm').reset();
}

async function handleSaleSubmit(event) {
    event.preventDefault();
    
    try {
        const formData = new FormData(event.target);
        const data = Object.fromEntries(formData);
        
        // Validações básicas
        if (!data.forma_pagamento) {
            throw new Error('Selecione uma forma de pagamento');
        }

        const selectedProducts = getSelectedProducts();
        if (selectedProducts.length === 0) {
            throw new Error('Adicione pelo menos um produto');
        }

        // Preparar dados da venda
        const saleData = {
            ...data,
            produtos: selectedProducts,
            valor_total: calculateTotal(),
            desconto: parseFloat(data.desconto || 0)
        };

        // Enviar dados para o servidor
        await saveSale(saleData);
        
        showSuccess('Venda realizada com sucesso!');
        closeSaleModal();
        loadSales(); // Recarregar lista
    } catch (error) {
        showError(error.message);
    }
}

// Funções de Busca
async function searchClients(term) {
    try {
        const clients = await fetchClientsFromAPI(); // Simular busca de clientes
        const resultsDiv = document.getElementById('clientResults');
        resultsDiv.innerHTML = '';
        
        if (term.length < 3) {
            resultsDiv.style.display = 'none';
            return;
        }

        const filteredClients = clients.filter(client => 
            client.nome.toLowerCase().includes(term.toLowerCase()) ||
            client.cpf.includes(term)
        );

        if (filteredClients.length > 0) {
            filteredClients.forEach(client => {
                const div = document.createElement('div');
                div.className = 'search-result-item';
                div.textContent = `${client.nome} - CPF: ${client.cpf}`;
                div.onclick = () => selectClient(client);
                resultsDiv.appendChild(div);
            });
            resultsDiv.style.display = 'block';
        } else {
            resultsDiv.style.display = 'none';
        }
    } catch (error) {
        showError('Erro ao buscar clientes');
    }
}

async function searchProducts(term) {
    try {
        const products = await fetchProductsFromAPI(); // Simular busca de produtos
        const resultsDiv = document.getElementById('productResults');
        resultsDiv.innerHTML = '';
        
        if (term.length < 3) {
            resultsDiv.style.display = 'none';
            return;
        }

        const filteredProducts = products.filter(product => 
            product.nome.toLowerCase().includes(term.toLowerCase())
        );

        if (filteredProducts.length > 0) {
            filteredProducts.forEach(product => {
                const div = document.createElement('div');
                div.className = 'search-result-item';
                div.textContent = `${product.nome} - ${formatCurrency(product.preco)}`;
                div.onclick = () => addProduct(product);
                resultsDiv.appendChild(div);
            });
            resultsDiv.style.display = 'block';
        } else {
            resultsDiv.style.display = 'none';
        }
    } catch (error) {
        showError('Erro ao buscar produtos');
    }
}

// Funções de Manipulação de Produtos
function addProduct(product) {
    const selectedProducts = document.getElementById('selectedProducts');
    
    // Verificar se o produto já foi adicionado
    if (document.getElementById(`product-${product.id}`)) {
        showError('Este produto já foi adicionado');
        return;
    }

    const div = document.createElement('div');
    div.id = `product-${product.id}`;
    div.className = 'product-item';
    div.innerHTML = `
        <div class="product-info">
            <strong>${product.nome}</strong>
            <div>${formatCurrency(product.preco)}</div>
        </div>
        <div class="product-actions">
            <input type="number" 
                   class="quantity-input" 
                   value="1" 
                   min="1" 
                   max="${product.estoque}"
                   onchange="updateProductTotal(${product.id}, ${product.preco}, this.value)">
            <button type="button" class="btn-icon" onclick="removeProduct(${product.id})">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;

    selectedProducts.appendChild(div);
    document.getElementById('productResults').style.display = 'none';
    document.getElementById('productSearch').value = '';
    updateTotalValue();
}

function removeProduct(productId) {
    const product = document.getElementById(`product-${productId}`);
    if (product) {
        product.remove();
        updateTotalValue();
    }
}

// Funções de Cálculo
function updateProductTotal(productId, price, quantity) {
    updateTotalValue();
}

function updateTotalValue() {
    const subtotal = calculateTotal();
    const discountPercent = parseFloat(document.getElementById('discount').value || 0);
    const discount = (subtotal * discountPercent) / 100;
    const total = subtotal - discount;

    document.getElementById('subtotal').textContent = formatCurrency(subtotal);
    document.getElementById('total').textContent = formatCurrency(total);
}

function calculateTotal() {
    let total = 0;
    const products = document.querySelectorAll('.product-item');
    
    products.forEach(product => {
        const price = parseFloat(product.querySelector('.product-info div').textContent.replace(/[^0-9,-]/g, '').replace(',', '.'));
        const quantity = parseInt(product.querySelector('.quantity-input').value);
        total += price * quantity;
    });

    return total;
}

// Funções Utilitárias
function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('pt-BR');
}

function formatStatus(status) {
    const statusMap = {
        'concluida': 'Concluída',
        'pendente': 'Pendente',
        'cancelada': 'Cancelada'
    };
    return statusMap[status] || status;
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function showSuccess(message) {
    // Implementar notificação de sucesso
    alert(message); // Temporário, considere usar uma biblioteca de notificações
}

function showError(message) {
    // Implementar notificação de erro
    alert(message); // Temporário, considere usar uma biblioteca de notificações
}

// Funções Assíncronas Simuladas (API)
async function fetchSalesFromAPI() {
    // Simulação de chamada à API
    return [
        { id: 1, numero: "V001", data: "2024-01-20", cliente: "João Silva", vendedor: "Maria Santos", valor: 1500.00, status: "concluida" },
        // Mais vendas aqui
    ];
}

async function fetchVendedoresFromAPI() {
    // Simulação de chamada à API
    return [
        { id: 1, nome: "Maria Santos" },
        { id: 2, nome: "Pedro Oliveira" }
    ];
}

async function fetchClientsFromAPI() {
    // Simulação de busca de clientes
    return [
        { id: 1, nome: "João Silva", cpf: "123.456.789-00" },
        { id: 2, nome: "Maria Oliveira", cpf: "987.654.321-00" }
    ];
}

async function fetchProductsFromAPI() {
    // Simulação de busca de produtos
    return [
        { id: 1, nome: "Notebook Dell", preco: 4500.00, estoque: 10 },
        { id: 2, nome: "iPhone 13", preco: 5999.00, estoque: 5 }
    ];
}

async function saveSale(saleData) {
    // Simulação de envio de dados para o servidor
    console.log('Dados da venda enviados:', saleData);
}
