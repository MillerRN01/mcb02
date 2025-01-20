// Configuração inicial quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', function() {
    // Configuração das máscaras de input
    setupMasks();
    // Carregar estados brasileiros
    loadEstados();
    // Configurar eventos dos filtros
    setupFilters();
    // Carregar fornecedores iniciais
    loadSuppliers();
    // Configurar tabs do modal
    setupModalTabs();
});

// Configuração das máscaras de input
function setupMasks() {
    const maskOptions = {
        cnpj: '99.999.999/9999-99',
        phone: '(99) 99999-9999',
        cep: '99999-999'
    };

    document.querySelectorAll('.cnpj-mask').forEach(input => {
        VMasker(input).maskPattern(maskOptions.cnpj);
    });

    document.querySelectorAll('.phone-mask').forEach(input => {
        VMasker(input).maskPattern(maskOptions.phone);
    });

    document.querySelectorAll('.cep-mask').forEach(input => {
        VMasker(input).maskPattern(maskOptions.cep);
    });
}

// Carregar estados brasileiros
function loadEstados() {
    const estados = [
        { sigla: 'AC', nome: 'Acre' },
        { sigla: 'AL', nome: 'Alagoas' },
        { sigla: 'AP', nome: 'Amapá' },
        { sigla: 'AM', nome: 'Amazonas' },
        { sigla: 'BA', nome: 'Bahia' },
        { sigla: 'CE', nome: 'Ceará' },
        { sigla: 'DF', nome: 'Distrito Federal' },
        { sigla: 'ES', nome: 'Espírito Santo' },
        { sigla: 'GO', nome: 'Goiás' },
        { sigla: 'MA', nome: 'Maranhão' },
        { sigla: 'MT', nome: 'Mato Grosso' },
        { sigla: 'MS', nome: 'Mato Grosso do Sul' },
        { sigla: 'MG', nome: 'Minas Gerais' },
        { sigla: 'PA', nome: 'Pará' },
        { sigla: 'PB', nome: 'Paraíba' },
        { sigla: 'PR', nome: 'Paraná' },
        { sigla: 'PE', nome: 'Pernambuco' },
        { sigla: 'PI', nome: 'Piauí' },
        { sigla: 'RJ', nome: 'Rio de Janeiro' },
        { sigla: 'RN', nome: 'Rio Grande do Norte' },
        { sigla: 'RS', nome: 'Rio Grande do Sul' },
        { sigla: 'RO', nome: 'Rondônia' },
        { sigla: 'RR', nome: 'Roraima' },
        { sigla: 'SC', nome: 'Santa Catarina' },
        { sigla: 'SP', nome: 'São Paulo' },
        { sigla: 'SE', nome: 'Sergipe' },
        { sigla: 'TO', nome: 'Tocantins' }
    ];

    const selectEstado = document.querySelector('select[name="estado"]');
    estados.forEach(estado => {
        const option = document.createElement('option');
        option.value = estado.sigla;
        option.textContent = estado.nome;
        selectEstado.appendChild(option);
    });
}

// Configurar eventos dos filtros
function setupFilters() {
    const searchInput = document.getElementById('searchSupplier');
    const categoryFilter = document.getElementById('categoryFilter');
    const statusFilter = document.getElementById('statusFilter');
    const sortFilter = document.getElementById('sortFilter');

    let searchTimeout;

    // Evento de pesquisa com debounce
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            applyFilters();
        }, 500);
    });

    // Eventos de mudança nos filtros
    [categoryFilter, statusFilter, sortFilter].forEach(filter => {
        filter.addEventListener('change', applyFilters);
    });
}

// Aplicar filtros
function applyFilters() {
    const filters = {
        search: document.getElementById('searchSupplier').value,
        category: document.getElementById('categoryFilter').value,
        status: document.getElementById('statusFilter').value,
        sort: document.getElementById('sortFilter').value
    };

    loadSuppliers(filters);
}

// Carregar fornecedores
async function loadSuppliers(filters = {}) {
    try {
        const response = await fetch('fetch_suppliers.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(filters)
        });

        if (!response.ok) throw new Error('Erro ao carregar fornecedores');

        const data = await response.json();
        updateSuppliersGrid(data.suppliers);
        updatePagination(data.pagination);
        updateSummaryCards(data.summary);

    } catch (error) {
        showToast('Erro ao carregar fornecedores', 'error');
        console.error(error);
    }
}

// Atualizar grid de fornecedores
function updateSuppliersGrid(suppliers) {
    const grid = document.getElementById('suppliersGrid');
    grid.innerHTML = '';

    if (suppliers.length === 0) {
        grid.innerHTML = '<div class="no-results">Nenhum fornecedor encontrado</div>';
        return;
    }

    suppliers.forEach(supplier => {
        grid.appendChild(createSupplierCard(supplier));
    });
}

// Criar card de fornecedor
function createSupplierCard(supplier) {
    const card = document.createElement('div');
    card.className = 'supplier-card';
    card.innerHTML = `
        <h3>${supplier.razao_social}</h3>
        <p><i class="fas fa-building"></i> ${supplier.nome_fantasia || supplier.razao_social}</p>
        <p><i class="fas fa-id-card"></i> ${supplier.cnpj}</p>
        <p><i class="fas fa-envelope"></i> ${supplier.email}</p>
        <p><i class="fas fa-phone"></i> ${supplier.telefone}</p>
        <div class="card-actions">
            <button onclick="editSupplier(${supplier.id})" class="btn-edit">
                <i class="fas fa-edit"></i> Editar
            </button>
            <button onclick="deleteSupplier(${supplier.id})" class="btn-delete">
                <i class="fas fa-trash"></i> Excluir
            </button>
        </div>
    `;
    return card;
}

// Configurar tabs do modal
function setupModalTabs() {
    const tabs = document.querySelectorAll('.tab-btn');
    const contents = document.querySelectorAll('.tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const target = tab.getAttribute('data-tab');

            // Atualizar tabs
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            // Atualizar conteúdo
            contents.forEach(content => {
                content.style.display = content.id === target ? 'block' : 'none';
            });
        });
    });
}

// Buscar CEP
async function buscarCep() {
    const cepInput = document.querySelector('input[name="cep"]');
    const cep = cepInput.value.replace(/\D/g, '');

    if (cep.length !== 8) {
        showToast('CEP inválido', 'error');
        return;
    }

    try {
        const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
        const data = await response.json();

        if (data.erro) {
            showToast('CEP não encontrado', 'error');
            return;
        }

        // Preencher campos
        document.querySelector('input[name="logradouro"]').value = data.logradouro;
        document.querySelector('input[name="bairro"]').value = data.bairro;
        document.querySelector('input[name="cidade"]').value = data.localidade;
        document.querySelector('select[name="estado"]').value = data.uf;

        // Focar no campo número
        document.querySelector('input[name="numero"]').focus();

    } catch (error) {
        showToast('Erro ao buscar CEP', 'error');
        console.error(error);
    }
}

// Funções do modal
function openNewSupplierModal() {
    const modal = document.getElementById('supplierModal');
    const form = document.getElementById('supplierForm');
    form.reset();
    document.getElementById('modalTitle').textContent = 'Novo Fornecedor';
    modal.style.display = 'block';
}

function closeSupplierModal() {
    document.getElementById('supplierModal').style.display = 'none';
}

// Manipulação de produtos
function addProduct() {
    const productsList = document.getElementById('productsList');
    const productDiv = document.createElement('div');
    productDiv.className = 'product-item';
    productDiv.innerHTML = `
        <div class="form-grid">
            <div class="form-group">
                <label>Produto</label>
                <input type="text" name="produtos[]" required>
            </div>
            <div class="form-group">
                <label>Preço</label>
                <input type="number" name="precos[]" step="0.01" min="0" required>
            </div>
            <button type="button" class="btn-remove" onclick="removeProduct(this)">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    productsList.appendChild(productDiv);
}

function removeProduct(button) {
    button.closest('.product-item').remove();
}

// Salvar fornecedor
async function handleSupplierSubmit(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);

    try {
        const response = await fetch('save_supplier.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showToast('Fornecedor salvo com sucesso', 'success');
            closeSupplierModal();
            loadSuppliers();
        } else {
            showToast(data.message || 'Erro ao salvar fornecedor', 'error');
        }

    } catch (error) {
        showToast('Erro ao salvar fornecedor', 'error');
        console.error(error);
    }
}

// Editar fornecedor
async function editSupplier(id) {
    try {
        const response = await fetch(`get_supplier.php?id=${id}`);
        const supplier = await response.json();

        fillSupplierForm(supplier);
        document.getElementById('modalTitle').textContent = 'Editar Fornecedor';
        document.getElementById('supplierModal').style.display = 'block';

    } catch (error) {
        showToast('Erro ao carregar fornecedor', 'error');
        console.error(error);
    }
}

// Preencher formulário
function fillSupplierForm(supplier) {
    const form = document.getElementById('supplierForm');
    
    // Preencher campos básicos
    Object.keys(supplier).forEach(key => {
        const input = form.querySelector(`[name="${key}"]`);
        if (input) {
            input.value = supplier[key];
        }
    });

    // Preencher produtos
    const productsList = document.getElementById('productsList');
    productsList.innerHTML = '';
    
    if (supplier.produtos) {
        supplier.produtos.forEach(produto => {
            const productDiv = document.createElement('div');
            productDiv.className = 'product-item';
            productDiv.innerHTML = `
                <div class="form-grid">
                    <div class="form-group">
                        <label>Produto</label>
                        <input type="text" name="produtos[]" value="${produto.nome}" required>
                    </div>
                    <div class="form-group">
                        <label>Preço</label>
                        <input type="number" name="precos[]" value="${produto.preco}" step="0.01" min="0" required>
                    </div>
                    <button type="button" class="btn-remove" onclick="removeProduct(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            productsList.appendChild(productDiv);
        });
    }
}

// Excluir fornecedor
async function deleteSupplier(id) {
    if (!confirm('Tem certeza que deseja excluir este fornecedor?')) {
        return;
    }

    try {
        const response = await fetch('delete_supplier.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id })
        });

        const data = await response.json();

        if (data.success) {
            showToast('Fornecedor excluído com sucesso', 'success');
            loadSuppliers();
        } else {
            showToast(data.message || 'Erro ao excluir fornecedor', 'error');
        }

    } catch (error) {
        showToast('Erro ao excluir fornecedor', 'error');
        console.error(error);
    }
}

// Exportar fornecedores
async function exportSuppliers() {
    try {
        const response = await fetch('export_suppliers.php');
        const blob = await response.blob();
        
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'fornecedores.xlsx';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        a.remove();

    } catch (error) {
        showToast('Erro ao exportar fornecedores', 'error');
        console.error(error);
    }
}

// Utilitário para mostrar mensagens
function showToast(message, type = 'info') {
    // Implementar seu sistema de notificação preferido
    alert(message);
}

// Atualizar cards de resumo
function updateSummaryCards(summary) {
    document.querySelector('.card-value').textContent = summary.total;
    document.querySelectorAll('.card-value')[1].textContent = 
        new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' })
            .format(summary.compras_mes);
    document.querySelectorAll('.card-value')[2].textContent = summary.pedidos_pendentes;
}

// Atualizar paginação
function updatePagination(pagination) {
    const { current_page, total_pages } = pagination;
    document.querySelector('.page-info').textContent = `Página ${current_page} de ${total_pages}`;
    
    const prevButton = document.querySelector('.btn-page:first-child');
    const nextButton = document.querySelector('.btn-page:last-child');
    
    // Habilitar/desabilitar botões de navegação
    prevButton.disabled = current_page === 1;
    nextButton.disabled = current_page === total_pages;

    // Adicionar eventos de clique
    prevButton.onclick = () => {
        if (current_page > 1) {
            loadSuppliers({ page: current_page - 1 });
        }
    };

    nextButton.onclick = () => {
        if (current_page < total_pages) {
            loadSuppliers({ page: current_page + 1 });
        }
    };
}

// Event Listeners para fechar o modal quando clicar fora
window.addEventListener('click', (event) => {
    const modal = document.getElementById('supplierModal');
    if (event.target === modal) {
        closeSupplierModal();
    }
});

// Event Listener para tecla ESC fechar o modal
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeSupplierModal();
    }
});

// Validação de formulário personalizada
document.getElementById('supplierForm').addEventListener('submit', function(event) {
    event.preventDefault();
    
    // Validar CNPJ
    const cnpj = document.querySelector('input[name="cnpj"]').value.replace(/\D/g, '');
    if (!validarCNPJ(cnpj)) {
        showToast('CNPJ inválido', 'error');
        return;
    }

    // Validar email
    const email = document.querySelector('input[name="email"]').value;
    if (!validarEmail(email)) {
        showToast('Email inválido', 'error');
        return;
    }

    // Se passou nas validações, submete o formulário
    handleSupplierSubmit(event);
});

// Funções de validação
function validarCNPJ(cnpj) {
    cnpj = cnpj.replace(/[^\d]+/g,'');
    
    if(cnpj == '') return false;
    if (cnpj.length != 14) return false;

    // Elimina CNPJs invalidos conhecidos
    if (cnpj == "00000000000000" || 
        cnpj == "11111111111111" || 
        cnpj == "22222222222222" || 
        cnpj == "33333333333333" || 
        cnpj == "44444444444444" || 
        cnpj == "55555555555555" || 
        cnpj == "66666666666666" || 
        cnpj == "77777777777777" || 
        cnpj == "88888888888888" || 
        cnpj == "99999999999999")
        return false;

    // Valida DVs
    tamanho = cnpj.length - 2
    numeros = cnpj.substring(0,tamanho);
    digitos = cnpj.substring(tamanho);
    soma = 0;
    pos = tamanho - 7;
    for (i = tamanho; i >= 1; i--) {
        soma += numeros.charAt(tamanho - i) * pos--;
        if (pos < 2) pos = 9;
    }
    resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
    if (resultado != digitos.charAt(0)) return false;

    tamanho = tamanho + 1;
    numeros = cnpj.substring(0,tamanho);
    soma = 0;
    pos = tamanho - 7;
    for (i = tamanho; i >= 1; i--) {
        soma += numeros.charAt(tamanho - i) * pos--;
        if (pos < 2) pos = 9;
    }
    resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
    if (resultado != digitos.charAt(1)) return false;

    return true;
}

function validarEmail(email) {
    const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(email.toLowerCase());
}

// Formatação de valores monetários
function formatMoney(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
}

// Formatação de datas
function formatDate(date) {
    return new Intl.DateTimeFormat('pt-BR').format(new Date(date));
}

// Inicialização quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    // Carregar dados iniciais
    loadSuppliers();
    
    // Configurar máscaras de input
    setupMasks();
    
    // Configurar eventos de filtro
    setupFilters();
    
    // Configurar tabs do modal
    setupModalTabs();
});