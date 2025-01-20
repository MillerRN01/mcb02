// Estado global
let currentPage = 1;
let funcionarios = [];
let filteredFuncionarios = [];
let currentFuncionario = null;

// Inicialização
document.addEventListener('DOMContentLoaded', () => {
    initializeMasks();
    setupEventListeners();
    loadFuncionarios();
});

// Configuração de máscaras
function initializeMasks() {
    const cpfInputs = document.querySelectorAll('.cpf-mask');
    const phoneInputs = document.querySelectorAll('.phone-mask');

    cpfInputs.forEach(input => {
        VMasker(input).maskPattern('999.999.999-99');
    });

    phoneInputs.forEach(input => {
        VMasker(input).maskPattern('(99) 99999-9999');
    });
}

// Configuração de event listeners
function setupEventListeners() {
    // Filtros
    document.getElementById('searchFuncionario').addEventListener('input', handleSearch);
    document.getElementById('departamentoFilter').addEventListener('change', handleFilters);
    document.getElementById('cargoFilter').addEventListener('change', handleFilters);
    document.getElementById('statusFilter').addEventListener('change', handleFilters);

    // Tabs do modal
    const tabButtons = document.querySelectorAll('.tab-btn');
    tabButtons.forEach(button => {
        button.addEventListener('click', () => switchTab(button.dataset.tab));
    });

    // Form submit
    document.getElementById('funcionarioForm').addEventListener('submit', handleFuncionarioSubmit);
}

// Carregamento de funcionários
async function loadFuncionarios() {
    try {
        showLoading(true);
        const response = await fetch('api/funcionarios.php');
        const data = await response.json();
        
        funcionarios = data.funcionarios;
        filteredFuncionarios = [...funcionarios];
        
        updateFuncionariosGrid();
        updatePagination(data.pagination);
    } catch (error) {
        showError('Erro ao carregar funcionários');
        console.error(error);
    } finally {
        showLoading(false);
    }
}

// Atualização do grid de funcionários
function updateFuncionariosGrid() {
    const grid = document.getElementById('funcionariosGrid');
    grid.innerHTML = '';

    const startIndex = (currentPage - 1) * 10;
    const endIndex = startIndex + 10;
    const pageItems = filteredFuncionarios.slice(startIndex, endIndex);

    pageItems.forEach(funcionario => {
        grid.appendChild(createFuncionarioCard(funcionario));
    });
}

// Criação do card de funcionário
function createFuncionarioCard(funcionario) {
    const card = document.createElement('div');
    card.className = 'funcionario-card';
    card.innerHTML = `
        <div class="card-header">
            <img src="${funcionario.foto || 'assets/img/default-avatar.png'}" 
                 alt="${funcionario.nome}"
                 class="funcionario-avatar">
            <div class="funcionario-info">
                <h3>${funcionario.nome}</h3>
                <p>${funcionario.cargo} - ${funcionario.departamento}</p>
            </div>
            <span class="status-badge ${funcionario.status}">${funcionario.status}</span>
        </div>
        <div class="card-body">
            <div class="info-item">
                <i class="fas fa-envelope"></i>
                <span>${funcionario.email}</span>
            </div>
            <div class="info-item">
                <i class="fas fa-phone"></i>
                <span>${funcionario.telefone}</span>
            </div>
        </div>
        <div class="card-actions">
            <button onclick="editFuncionario(${funcionario.id})" class="btn-edit">
                <i class="fas fa-edit"></i> Editar
            </button>
            <button onclick="viewDetails(${funcionario.id})" class="btn-view">
                <i class="fas fa-eye"></i> Detalhes
            </button>
        </div>
    `;
    return card;
}

// Manipulação do modal
function openNewFuncionarioModal() {
    currentFuncionario = null;
    document.getElementById('modalTitle').textContent = 'Novo Funcionário';
    document.getElementById('funcionarioForm').reset();
    showModal('funcionarioModal');
}

function closeFuncionarioModal() {
    hideModal('funcionarioModal');
    document.getElementById('funcionarioForm').reset();
}

// Submissão do formulário
async function handleFuncionarioSubmit(event) {
    event.preventDefault();
    
    try {
        showLoading(true);
        const formData = new FormData(event.target);
        
        const response = await fetch('api/funcionarios.php', {
            method: currentFuncionario ? 'PUT' : 'POST',
            body: formData
        });

        const data = await response.json();
        
        if (data.success) {
            showSuccess(currentFuncionario ? 'Funcionário atualizado' : 'Funcionário cadastrado');
            closeFuncionarioModal();
            loadFuncionarios();
        } else {
            showError(data.message);
        }
    } catch (error) {
        showError('Erro ao salvar funcionário');
        console.error(error);
    } finally {
        showLoading(false);
    }
}

// Funções auxiliares
function showLoading(show) {
    const spinner = document.querySelector('.loading-spinner');
    spinner.classList.toggle('d-none', !show);
}

function showError(message) {
    // Implementar toast de erro
    alert(message);
}

function showSuccess(message) {
    // Implementar toast de sucesso
    alert(message);
}

function switchTab(tabId) {
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.tab === tabId);
    });
    
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.toggle('active', content.id === tabId);
    });
}

// Exportação
function exportFuncionarios() {
    // Implementar exportação para Excel/PDF
    console.log('Exportar funcionários');
}

// Busca e filtros
function handleSearch(event) {
    const searchTerm = event.target.value.toLowerCase();
    filterFuncionarios();
}

function handleFilters() {
    filterFuncionarios();
}

function filterFuncionarios() {
    const searchTerm = document.getElementById('searchFuncionario').value.toLowerCase();
    const departamento = document.getElementById('departamentoFilter').value;
    const cargo = document.getElementById('cargoFilter').value;
    const status = document.getElementById('statusFilter').value;

    filteredFuncionarios = funcionarios.filter(funcionario => {
        const matchSearch = funcionario.nome.toLowerCase().includes(searchTerm) ||
                          funcionario.email.toLowerCase().includes(searchTerm);
        const matchDepartamento = !departamento || funcionario.departamento === departamento;
        const matchCargo = !cargo || funcionario.cargo === cargo;
        const matchStatus = !status || funcionario.status === status;

        return matchSearch && matchDepartamento && matchCargo && matchStatus;
    });

    currentPage = 1;
    updateFuncionariosGrid();
    updatePagination({
        current_page: 1,
        total_pages: Math.ceil(filteredFuncionarios.length / 10)
    });
}