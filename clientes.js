// Configuração das máscaras de input
document.addEventListener('DOMContentLoaded', function() {
    // Configuração das máscaras
    const maskOptions = {
        cpf: '999.999.999-99',
        cnpj: '99.999.999/9999-99',
        phone: '(99) 99999-9999',
        cep: '99999-999'
    };

    // Aplicar máscaras nos inputs
    function applyMasks() {
        document.querySelectorAll('.cpf-mask').forEach(input => {
            VMasker(input).maskPattern(maskOptions.cpf);
        });
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

    // Aplicar máscaras iniciais
    applyMasks();

    // Estados brasileiros
    const estados = [
        { value: 'AC', label: 'Acre' },
        { value: 'AL', label: 'Alagoas' },
        { value: 'AP', label: 'Amapá' },
        { value: 'AM', label: 'Amazonas' },
        { value: 'BA', label: 'Bahia' },
        { value: 'CE', label: 'Ceará' },
        { value: 'DF', label: 'Distrito Federal' },
        { value: 'ES', label: 'Espírito Santo' },
        { value: 'GO', label: 'Goiás' },
        { value: 'MA', label: 'Maranhão' },
        { value: 'MT', label: 'Mato Grosso' },
        { value: 'MS', label: 'Mato Grosso do Sul' },
        { value: 'MG', label: 'Minas Gerais' },
        { value: 'PA', label: 'Pará' },
        { value: 'PB', label: 'Paraíba' },
        { value: 'PR', label: 'Paraná' },
        { value: 'PE', label: 'Pernambuco' },
        { value: 'PI', label: 'Piauí' },
        { value: 'RJ', label: 'Rio de Janeiro' },
        { value: 'RN', label: 'Rio Grande do Norte' },
        { value: 'RS', label: 'Rio Grande do Sul' },
        { value: 'RO', label: 'Rondônia' },
        { value: 'RR', label: 'Roraima' },
        { value: 'SC', label: 'Santa Catarina' },
        { value: 'SP', label: 'São Paulo' },
        { value: 'SE', label: 'Sergipe' },
        { value: 'TO', label: 'Tocantins' }
    ];

    // Preencher select de estados
    const estadoSelect = document.querySelector('select[name="estado"]');
    estados.forEach(estado => {
        const option = document.createElement('option');
        option.value = estado.value;
        option.textContent = estado.label;
        estadoSelect.appendChild(option);
    });

    // Gerenciamento de tabs do formulário
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const tab = button.dataset.tab;
            
            // Atualizar botões
            tabButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            
            // Atualizar conteúdo
            tabContents.forEach(content => {
                content.style.display = content.id === tab ? 'block' : 'none';
            });
        });
    });

    // Toggle tipo de pessoa
    const tipoPessoaInputs = document.querySelectorAll('input[name="tipo_pessoa"]');
    const pessoaFisicaFields = document.getElementById('pessoaFisicaFields');
    const pessoaJuridicaFields = document.getElementById('pessoaJuridicaFields');

    tipoPessoaInputs.forEach(input => {
        input.addEventListener('change', () => {
            if (input.value === 'pf') {
                pessoaFisicaFields.style.display = 'block';
                pessoaJuridicaFields.style.display = 'none';
            } else {
                pessoaFisicaFields.style.display = 'none';
                pessoaJuridicaFields.style.display = 'block';
            }
        });
    });

    // Busca de CEP
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
            console.error('Erro ao buscar CEP:', error);
            showToast('Erro ao buscar CEP', 'error');
        }
    }

    // Filtros e pesquisa
    const searchInput = document.getElementById('pesquisa_cliente');
    const filtroStatus = document.getElementById('filtroStatus');
    const filtroTipo = document.getElementById('filtroTipo');
    const filtroCadastro = document.getElementById('filtroCadastro');

    let searchTimeout;

    function applyFilters() {
        const filters = {
            search: searchInput.value,
            status: filtroStatus.value,
            tipo: filtroTipo.value,
            order: filtroCadastro.value
        };

        fetchClients(filters);
    }

    // Debounce para pesquisa
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 500);
    });

    // Eventos para filtros
    [filtroStatus, filtroTipo, filtroCadastro].forEach(filter => {
        filter.addEventListener('change', applyFilters);
    });

    // Funções CRUD
    async function fetchClients(filters = {}) {
        try {
            const response = await fetch('fetch_clients.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(filters)
            });

            const data = await response.json();
            updateClientsGrid(data);
            updatePagination(data.pagination);
            updateStats(data.stats);

        } catch (error) {
            console.error('Erro ao buscar clientes:', error);
            showToast('Erro ao carregar clientes', 'error');
        }
    }

    // Função para salvar cliente
    async function handleClientSubmit(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);

        try {
            const response = await fetch('save_client.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showToast('Cliente salvo com sucesso', 'success');
                closeClientModal();
                fetchClients(); // Atualiza a lista
            } else {
                showToast(data.message || 'Erro ao salvar cliente', 'error');
            }

        } catch (error) {
            console.error('Erro ao salvar cliente:', error);
            showToast('Erro ao salvar cliente', 'error');
        }
    }

    // Exportação
    async function exportClients() {
        try {
            const response = await fetch('export_clients.php');
            const blob = await response.blob();
            
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'clientes.xlsx';
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            a.remove();

        } catch (error) {
            console.error('Erro ao exportar clientes:', error);
            showToast('Erro ao exportar clientes', 'error');
        }
    }

    // Utilitários
    function showToast(message, type = 'info') {
        // Implementar seu sistema de toast/notificação preferido
        alert(message);
    }

    function updateClientsGrid(data) {
        const grid = document.getElementById('clientsGrid');
        grid.innerHTML = ''; // Limpa o grid atual

        if (data.clients.length === 0) {
            grid.innerHTML = '<div class="col-12"><p>Nenhum cliente encontrado.</p></div>';
            return;
        }

        data.clients.forEach(client => {
            const card = createClientCard(client);
            grid.appendChild(card);
        });
    }

    function createClientCard(client) {
        const div = document.createElement('div');
        div.className = 'col-md-4 mb-3';
        div.innerHTML = `
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">${client.nome}</h5>
                    <p class="card-text">Email: ${client.email}</p>
                    <p class="card-text">Telefone: ${client.telefone}</p>
                    <p class="card-text">CPF/CNPJ: ${client.documento}</p>
                    <button class="btn btn-info" onclick="editClient(${client.id})">Editar</button>
                    <button class="btn btn-danger" onclick="deleteClient(${client.id})">Excluir</button>
                </div>
            </div>
        `;
        return div;
    }

    function updatePagination(pagination) {
        // Implementar atualização da paginação
    }

    function updateStats(stats) {
        document.getElementById('totalClientes').innerText = stats.total;
        document.getElementById('novosClientes').innerText = stats.novos;
        document.getElementById('clientesAtivos').innerText = stats.ativos;
    }

    // Inicialização
    fetchClients();
});

// Funções globais
function openNewClientModal() {
    const modal = document.getElementById('clientModal');
    const form = document.getElementById('clientForm');
    form.reset();
    document.getElementById('modalTitle').textContent = 'Novo Cliente';
    new bootstrap.Modal(modal).show();
}

function closeClientModal() {
    const modal = document.getElementById('clientModal');
    bootstrap.Modal.getInstance(modal).hide();
}

async function editClient(id) {
    try {
        const response = await fetch(`get_client.php?id=${id}`);
        const client = await response.json();
        
        fillClientForm(client);
        document.getElementById('modalTitle').textContent = 'Editar Cliente';
        new bootstrap.Modal(document.getElementById('clientModal')).show();

    } catch (error) {
        console.error('Erro ao carregar cliente:', error);
        showToast('Erro ao carregar dados do cliente', 'error');
    }
}

async function deleteClient(id) {
    if (!confirm('Tem certeza que deseja excluir este cliente?')) {
        return;
    }

    try {
        const response = await fetch('delete_client.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id })
        });

        const data = await response.json();

        if (data.success) {
            showToast('Cliente excluído com sucesso', 'success');
            fetchClients();
        } else {
            showToast(data.message || 'Erro ao excluir cliente', 'error');
        }

    } catch (error) {
        console.error('Erro ao excluir cliente:', error);
        showToast('Erro ao excluir cliente', 'error');
    }
}

function fillClientForm(client) {
    const form = document.getElementById('clientForm');
    
    // Preencher campos do formulário
    Object.keys(client).forEach(key => {
        const input = form.querySelector(`[name="${key}"]`);
        if (input) {
            input.value = client[key];
        }
    });

    // Ajustar tipo de pessoa
    const tipoPessoa = client.tipo_pessoa || 'pf';
    document.querySelector(`input[name="tipo_pessoa"][value="${tipoPessoa}"]`).checked = true;
    
    // Mostrar campos apropriados
    document.getElementById('pessoaFisicaFields').style.display = tipoPessoa === 'pf' ? 'block' : 'none';
    document.getElementById('pessoaJuridicaFields').style.display = tipoPessoa === 'pj' ? 'block' : 'none';
}