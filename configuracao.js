document.addEventListener('DOMContentLoaded', function() {
    initializeMasks();
    setupEventListeners();
    loadConfigurations();
    loadEstados();
});

// Inicialização de Máscaras
function initializeMasks() {
    const cnpjInput = document.querySelector('.cnpj-mask');
    const phoneInputs = document.querySelectorAll('.phone-mask');
    const cepInputs = document.querySelectorAll('.cep-mask');

    if (cnpjInput) VMasker(cnpjInput).maskPattern('99.999.999/9999-99');
    
    phoneInputs.forEach(input => {
        VMasker(input).maskPattern('(99) 99999-9999');
    });

    cepInputs.forEach(input => {
        VMasker(input).maskPattern('99999-999');
    });
}

// Configuração de Event Listeners
function setupEventListeners() {
    // Navegação no menu de configurações
    document.querySelectorAll('.menu-item').forEach(item => {
        item.addEventListener('click', () => switchTab(item.dataset.tab));
    });

    // Mudança de tema
    document.querySelectorAll('input[name="theme"]').forEach(radio => {
        radio.addEventListener('change', handleThemeChange);
    });

    // Atualização de cores personalizadas
    document.querySelectorAll('#customColors input[type="color"]').forEach(input => {
        input.addEventListener('change', updateCustomTheme);
    });

    // Habilitar botão de salvar ao alterar formulários
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('change', () => {
            document.querySelector('.btn-primary').disabled = false;
        });
    });
}

// Carregamento de Dados
async function loadConfigurations() {
    try {
        const config = await fetchConfigurations();
        populateFields(config);
    } catch (error) {
        showError('Erro ao carregar configurações');
    }
}

// Simulação de dados
async function fetchConfigurations() {
    return new Promise(resolve => {
        setTimeout(() => {
            resolve({
                empresa: {
                    razao_social: "Empresa Exemplo LTDA",
                    nome_fantasia: "Empresa Exemplo",
                    cnpj: "12.345.678/0001-90",
                    ie: "123456789",
                    telefone: "(11) 99999-9999",
                    email: "contato@exemplo.com",
                    endereco: {
                        cep: "12345-678",
                        logradouro: "Rua Exemplo",
                        numero: "123",
                        complemento: "Sala 1",
                        bairro: "Centro",
                        cidade: "São Paulo",
                        estado: "SP"
                    }
                },
                sistema: {
                    nome_sistema: "Sistema de Gestão",
                    timezone: "America/Sao_Paulo",
                    date_format: "dd/mm/yyyy",
                    currency: "BRL",
                    session_timeout: 30,
                    login_attempts: 3,
                    force_ssl: true,
                    two_factor: false
                },
                aparencia: {
                    theme: "light",
                    colors: {
                        primary: "#4CAF50",
                        secondary: "#2196F3",
                        background: "#FFFFFF",
                        text: "#333333"
                    },
                    layout_density: "comfortable",
                    system_font: "roboto"
                }
            });
        }, 500);
    });
}

// Navegação entre Tabs
function switchTab(tabId) {
    document.querySelectorAll('.menu-item').forEach(item => {
        item.classList.toggle('active', item.dataset.tab === tabId);
    });

    document.querySelectorAll('.config-tab').forEach(tab => {
        tab.classList.toggle('active', tab.id === tabId);
    });
}

// Manipulação de Temas
function handleThemeChange(event) {
    const customColors = document.getElementById('customColors');
    customColors.style.display = event.target.value === 'custom' ? 'block' : 'none';

    if (event.target.value !== 'custom') {
        applyTheme(event.target.value);
    }
}

function updateCustomTheme() {
    const colors = {
        primary: document.querySelector('input[name="primary_color"]').value,
        secondary: document.querySelector('input[name="secondary_color"]').value,
        background: document.querySelector('input[name="background_color"]').value,
        text: document.querySelector('input[name="text_color"]').value
    };

    applyCustomTheme(colors);
}

function applyTheme(theme) {
    const root = document.documentElement;
    const themes = {
        light: {
            primary: '#4CAF50',
            secondary: '#2196F3',
            background: '#FFFFFF',
            text: '#333333'
        },
        dark: {
            primary: '#66BB6A',
            secondary: '#42A5F5',
            background: '#1A1A1A',
            text: '#FFFFFF'
        }
    };

    const colors = themes[theme];
    if (colors) {
        Object.entries(colors).forEach(([property, value]) => {
            root.style.setProperty(`--${property}-color`, value);
        });
    }
}

function applyCustomTheme(colors) {
    const root = document.documentElement;
    Object.entries(colors).forEach(([property, value]) => {
        root.style.setProperty(`--${property}-color`, value);
    });
}

// Preenchimento de Campos
function populateFields(config) {
    // Preencher dados da empresa
    Object.entries(config.empresa).forEach(([key, value]) => {
        if (typeof value === 'object') {
            Object.entries(value).forEach(([subKey, subValue]) => {
                const input = document.querySelector(`[name="${subKey}"]`);
                if (input) input.value = subValue;
            });
        } else {
            const input = document.querySelector(`[name="${key}"]`);
            if (input) input.value = value;
        }
    });

    // Preencher configurações do sistema
    Object.entries(config.sistema).forEach(([key, value]) => {
        const input = document.querySelector(`[name="${key}"]`);
        if (input) {
            if (input.type === 'checkbox') {
                input.checked = value;
            } else {
                input.value = value;
            }
        }
    });

    // Preencher configurações de aparência
    const themeInput = document.querySelector(`input[name="theme"][value="${config.aparencia.theme}"]`);
    if (themeInput) themeInput.checked = true;

    Object.entries(config.aparencia.colors).forEach(([key, value]) => {
        const input = document.querySelector(`input[name="${key}_color"]`);
        if (input) input.value = value;
    });
}

// Funções de Ação
async function salvarConfiguracoes() {
    try {
        const forms = document.querySelectorAll('form');
        const config = {};

        forms.forEach(form => {
            const formData = new FormData(form);
            config[form.id.replace('Form', '')] = Object.fromEntries(formData);
        });

        // Simular salvamento
        await new Promise(resolve => setTimeout(resolve, 1000));

        showSuccess('Configurações salvas com sucesso!');
        document.querySelector('.btn-primary').disabled = true;
    } catch (error) {
        showError('Erro ao salvar configurações');
    }
}

async function uploadLogo() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';

    input.onchange = async function(e) {
        const file = e.target.files[0];
        if (file) {
            try {
                await new Promise(resolve => setTimeout(resolve, 1000));
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('logoPreview').src = e.target.result;
                };
                reader.readAsDataURL(file);

                showSuccess('Logo atualizada com sucesso!');
            } catch (error) {
                showError('Erro ao fazer upload da logo');
            }
        }
    };

    input.click();
}

function removeLogo() {
    if (confirm('Tem certeza que deseja remover a logo?')) {
        document.getElementById('logoPreview').src = 'assets/img/logo-placeholder.png';
        showSuccess('Logo removida com sucesso!');
    }
}

// Funções de Integração
function showApiKey() {
    const input = document.querySelector('.key-display input');
    const button = document.querySelector('.key-display button i');

    input.type = input.type === 'password' ? 'text' : 'password';
    button.classList.toggle('fa-eye', input.type === 'password');
    button.classList.toggle('fa-eye-slash', input.type === 'text');
}

async function generateNewApiKey() {
    if (confirm('Tem certeza que deseja gerar uma nova chave? A chave atual será invalidada.')) {
        try {
            await new Promise(resolve => setTimeout(resolve, 1000));
            
            const newKey = 'sk_' + Math.random().toString(36).substr(2, 32);
            document.querySelector('.key-display input').value = newKey;
            
            showSuccess('Nova chave de API gerada com sucesso!');
        } catch (error) {
            showError('Erro ao gerar nova chave de API');
        }
    }
}

// Funções de Backup
async function backupNow() {
    try {
        await new Promise(resolve => setTimeout(resolve, 2000));
        showSuccess('Backup realizado com sucesso!');
    } catch (error) {
        showError('Erro ao realizar backup');
    }
}

function restoreBackup() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.bak,.sql';

    input.onchange = async function(e) {
        const file = e.target.files[0];
        if (file) {
            if (confirm('Tem certeza que deseja restaurar este backup? Todos os dados atuais serão substituídos.')) {
                try {
                    await new Promise(resolve => setTimeout(resolve, 2000));
                    showSuccess('Backup restaurado com sucesso!');
                } catch (error) {
                    showError('Erro ao restaurar backup');
                }
            }
        }
    };

    input.click();
}

// Funções Utilitárias
async function loadEstados() {
    const estados = [
        { uf: 'AC', nome: 'Acre' },
        { uf: 'AL', nome: 'Alagoas' },
        { uf: 'AP', nome: 'Amapá' },
        { uf: 'AM', nome: 'Amazonas' },
        { uf: 'BA', nome: 'Bahia' },
        { uf: 'CE', nome: 'Ceará' },
        { uf: 'DF', nome: 'Distrito Federal' },
        { uf: 'ES', nome: 'Espírito Santo' },
        { uf: 'GO', nome: 'Goiás' },
        { uf: 'MA', nome: 'Maranhão' },
        { uf: 'MT', nome: 'Mato Grosso' },
        { uf: 'MS', nome: 'Mato Grosso do Sul' },
        { uf: 'MG', nome: 'Minas Gerais' },
        { uf: 'PA', nome: 'Pará' },
        { uf: 'PB', nome: 'Paraíba' },
        { uf: 'PR', nome: 'Paraná' },
        { uf: 'PE', nome: 'Pernambuco' },
        { uf: 'PI', nome: 'Piauí' },
        { uf: 'RJ', nome: 'Rio de Janeiro' },
        { uf: 'RN', nome: 'Rio Grande do Norte' },
        { uf: 'RS', nome: 'Rio Grande do Sul' },
        { uf: 'RO', nome: 'Rondônia' },
        { uf: 'RR', nome: 'Roraima' },
        { uf: 'SC', nome: 'Santa Catarina' },
        { uf: 'SP', nome: 'São Paulo' },
        { uf: 'SE', nome: 'Sergipe' },
        { uf: 'TO', nome: 'Tocantins' }
    ];

    const select = document.querySelector('select[name="estado"]');
    estados.forEach(estado => {
        const option = document.createElement('option');
        option.value = estado.uf;
        option.textContent = estado.nome;
        select.appendChild(option);
    });
}

async function buscarCep() {
    const cep = document.querySelector('input[name="cep"]').value.replace(/\D/g, '');
    
    if (cep.length !== 8) {
        showError('CEP inválido');
        return;
    }

    try {
        const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
        const data = await response.json();

        if (data.erro) {
            showError('CEP não encontrado');
            return;
        }

        // Preencher campos com dados do CEP
        document.querySelector('input[name="logradouro"]').value = data.logradouro;
        document.querySelector('input[name="bairro"]').value = data.bairro;
        document.querySelector('input[name="cidade"]').value = data.localidade;
        document.querySelector('select[name="estado"]').value = data.uf;
    } catch (error) {
        showError('Erro ao buscar CEP');
    }
}

// Funções de Notificação
function showSuccess(message) {
    alert(message); // Implementar notificação mais elegante
}

function showError(message) {
    alert(message); // Implementar notificação mais elegante
}
