<?php
include_once 'conexao.php'; // Inclua sua conexão com o banco de dados
session_start(); // Inicia a sessão

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php"); // Redireciona para a página de index se não estiver logado
    exit();
}

$dante = $_SESSION['dante'];  // Pode ser 'admin' ou 'funcionario'

// Obtém os dados da sessão
$usuario = $_SESSION['usuario'];
$foto = $_SESSION['foto'];
$email = $_SESSION['email'];

// Função para validar e-mail
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Processa o envio do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    if (!validarEmail($email)) {
        $error = "Por favor, insira um e-mail válido.";
    } else {
        // Aqui você pode adicionar a lógica para salvar as configurações no banco de dados
        // Exemplo de atualização no banco de dados
        // $query = "UPDATE usuarios SET email = ? WHERE id = ?";
        // $stmt = $conn->prepare($query);
        // $stmt->execute([$email, $_SESSION['id']]);

        $success = "Configurações salvas com sucesso!";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - Sistema de Gestão</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="configuracoes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="global.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        function validarEmail(email) {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        }

        function salvarConfiguracoes() {
            const email = document.querySelector('input[name="email"]').value;
            if (!validarEmail(email)) {
                alert("Por favor, insira um e-mail válido.");
                return;
            }
            document.getElementById('empresaForm').submit();
        }

        function buscarCep() {
            // Implementar a lógica para buscar o CEP e preencher os campos
        }

        function uploadLogo() {
            // Implementar a lógica para fazer upload do logo
        }

        function removeLogo() {
            // Implementar a lógica para remover o logo
            document.getElementById("logoPreview").src = "assets/img/logo-placeholder.png";
        }

        function testEmailConfig() {
            // Implementar a lógica para testar as configurações de e-mail
        }

        function backupNow() {
            // Implementar a lógica para fazer backup agora
        }

        function restoreBackup() {
            // Implementar a lógica para restaurar backup
        }

        function configureGDrive() {
            // Implementar a lógica para configurar Google Drive
        }

        function configureDropbox() {
            // Implementar a lógica para configurar Dropbox
        }

        function showApiKey() {
            // Implementar a lógica para mostrar a chave da API
        }

        function generateNewApiKey() {
            // Implementar a lógica para gerar uma nova chave da API
        }
    </script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" id="menuToggle"><i class="bi bi-list text-white fs-2"></i></button>
            <span class="navbar-brand ms-2">MeuComerciodeBolso</span>
        </div>
    </nav>

    <div class="sidebar p-3" id="sidebar">
        <div class="card mb-3" style="width: 100%;">
            <div class="card-body text-center">
                <img src="<?php echo $foto ? $foto : 'https://via.placeholder.com/100'; ?>"
                    class="card-img-top rounded-circle mb-2"
                    alt="Foto do Usuário"
                    style="width: 80px; height: 80px; object-fit: cover;">
                <h5 class="card-title"><?php echo htmlspecialchars($usuario); ?></h5>
                <p class="card-text"><?php echo htmlspecialchars($email); ?></p>
            </div>
        </div>
        <div class="sidebar-header">
            <h5 class="mb-0"><i class="bi bi-person"></i> Cadastro</h5>
        </div>
        <ul class="sidebar-list list-unstyled">
            <li><a href="categoria.php"><i class="bi bi-list"></i> Categoria</a></li>
            <li><a href="cadastro_produto.php"><i class="bi bi-box"></i> Produtos e Serviços</a></li>
            <li><a href="modificador.php"><i class="bi bi-pencil-square"></i> Modificador</a></li>
            <li><a href="cadastro_cliente.php"><i class="bi bi-person-check"></i> Clientes</a></li>
            <li><a href="cadastro_fornecedores.php"><i class="bi bi-truck"></i> Fornecedor</a></li>
            <li><a href="vendedores.php"><i class="bi bi-person-badge"></i> Vendedores</a></li>
        </ul>

        <?php if ($dante === 'admin'): ?>
            <div class="sidebar-header">
                <h5 class="mb-0"><i class="bi bi-gear"></i> Gestão</h5>
            </div>
            <ul class="sidebar-list list-unstyled">
                <li><a href="consulta_vendas.php"><i class="bi bi-search"></i> Consulta Vendas</a></li>
                <li><a href="estoque.php"><i class="bi bi-box"></i> Consultar estoque</a></li>
                <li><a href="caixa.php"><i class="bi bi-cash-stack"></i> Controle de caixa</a></li>
                <li><a href="fiado.php"><i class="bi bi-credit-card"></i> Fiado</a></li>
                <li><a href="cadastro_funcionario.php"><i class="bi bi-person-badge"></i> Cadastro de Funcionario</a></li>
                <li><a href="financeiro.php"><i class="bi bi-wallet"></i> Financeiro</a></li>
            </ul>
        <?php endif; ?>

        <div class="sidebar-header">
            <h5 class="mb-0"><i class="bi bi-gear"></i> Preferências</h5>
        </div>
        <ul class="sidebar-list list-unstyled">
            <li><a href="ajuda.php"><i class="bi bi-question-circle"></i> Ajuda</a></li>
            <li><a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </div>

    <main class="main-content">
        <div class="configuracoes-content">
            <div class="content-header">
                <h1>Configurações do Sistema</h1>
                <div class="header-actions">
                    <button class="btn-primary" onclick="salvarConfiguracoes()">
                        <i class="fas fa-save"></i> Salvar Alterações
                    </button>
                </div>
            </div>

            <div class="config-container">
                <div class="config-menu">
                    <div class="menu-item active" data-tab="empresa">
                        <i class="fas fa-building"></i>
                        Dados da Empresa
                    </div>
                    <div class="menu-item" data-tab="sistema">
                        <i class="fas fa-cogs"></i>
                        Sistema
                    </div>
                    <div class="menu-item" data-tab="fiscal">
                        <i class="fas fa-file-invoice-dollar"></i>
                        Fiscal
                    </div>
                    <div class="menu-item" data-tab="email">
                        <i class="fas fa-envelope"></i>
                        Configurações de E-mail
                    </div>
                    <div class="menu-item" data-tab="backup">
                        <i class="fas fa-database"></i>
                        Backup
                    </div>
                    <div class="menu-item" data-tab="integracao">
                        <i class="fas fa-plug"></i>
                        Integrações
                    </div>
                    <div class="menu-item" data-tab="aparencia">
                        <i class="fas fa-paint-brush"></i>
                        Aparência
                    </div>
                </div>

                <div class="config-content">
                    <!-- Dados da Empresa -->
                    <div class="config-tab active" id="empresa">
                        <h2>Dados da Empresa</h2>
                        <form id="empresaForm" method="POST">
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger"><?php echo $error; ?></div>
                            <?php endif; ?>
                            <?php if (isset($success)): ?>
                                <div class="alert alert-success"><?php echo $success; ?></div>
                            <?php endif; ?>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Razão Social*</label>
                                    <input type="text" name="razao_social" required>
                                </div>
                                <div class="form-group">
                                    <label>Nome Fantasia*</label>
                                    <input type="text" name="nome_fantasia" required>
                                </div>
                                <div class="form-group">
                                    <label>CNPJ*</label>
                                    <input type="text" name="cnpj" class="cnpj-mask" required>
                                </div>
                                <div class="form-group">
                                    <label>Inscrição Estadual</label>
                                    <input type="text" name="ie">
                                </div>
                                <div class="form-group">
                                    <label>Telefone*</label>
                                    <input type="tel" name="telefone" class="phone-mask" required>
                                </div>
                                <div class="form-group">
                                    <label>E-mail*</label>
                                    <input type="email" name="email" required value="<?php echo htmlspecialchars($email); ?>">
                                </div>
                            </div>

                            <div class="form-section">
                                <h3>Endereço</h3>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>CEP*</label>
                                        <div class="cep-group">
                                            <input type="text" name="cep" class="cep-mask" required>
                                            <button type="button" onclick="buscarCep()">Buscar</button>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Logradouro*</label>
                                        <input type="text" name="logradouro" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Número*</label>
                                        <input type="text" name="numero" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Complemento</label>
                                        <input type="text" name="complemento">
                                    </div>
                                    <div class="form-group">
                                        <label>Bairro*</label>
                                        <input type="text" name="bairro" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Cidade*</label>
                                        <input type="text" name="cidade" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Estado*</label>
                                        <select name="estado" required>
                                            <option value="">Selecione</option>
                                            <!-- Estados serão carregados via JavaScript -->
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h3>Logotipo</h3>
                                <div class="logo-upload">
                                    <img id="logoPreview" src="assets/img/logo-placeholder.png" alt="Logo">
                                    <div class="upload-actions">
                                        <button type="button" class="btn-secondary" onclick="uploadLogo()">
                                            <i class="fas fa-upload"></i> Enviar Logo
                                        </button>
                                        <button type="button" class="btn-danger" onclick="removeLogo()">
                                            <i class="fas fa-trash"></i> Remover
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Sistema -->
                    <div class="config-tab" id="sistema">
                        <h2>Configurações do Sistema</h2>
                        <form id="sistemaForm">
                            <div class="form-section">
                                <h3>Geral</h3>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Nome do Sistema</label>
                                        <input type="text" name="nome_sistema">
                                    </div>
                                    <div class="form-group">
                                        <label>Fuso Horário</label>
                                        <select name="timezone">
                                            <option value="America/Sao_Paulo">Brasília (GMT-3)</option>
                                            <!-- Outras opções de fuso -->
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Formato de Data</label>
                                        <select name="date_format">
                                            <option value="dd/mm/yyyy">DD/MM/YYYY</option>
                                            <option value="mm/dd/yyyy">MM/DD/YYYY</option>
                                            <option value="yyyy-mm-dd">YYYY-MM-DD</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Moeda Padrão</label>
                                        <select name="currency">
                                            <option value="BRL">Real (R$)</option>
                                            <option value="USD">Dólar ($)</option>
                                            <option value="EUR">Euro (€)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h3>Segurança</h3>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Tempo de Sessão (minutos)</label>
                                        <input type="number" name="session_timeout" min="5" max="120">
                                    </div>
                                    <div class="form-group">
                                        <label>Tentativas de Login</label>
                                        <input type="number" name="login_attempts" min="3" max="10">
                                    </div>
                                </div>
                                <div class="checkbox-group">
                                    <label>
                                        <input type="checkbox" name="force_ssl">
                                        Forçar SSL/HTTPS
                                    </label>
                                    <label>
                                        <input type="checkbox" name="two_factor">
                                        Habilitar Autenticação em Dois Fatores
                                    </label>
                                </div>
                            </div>

                            <div class="form-section">
                                <h3>Notificações</h3>
                                <div class="checkbox-group">
                                    <label>
                                        <input type="checkbox" name="notify_low_stock">
                                        Alertar Estoque Baixo
                                    </label>
                                    <label>
                                        <input type="checkbox" name="notify_new_order">
                                        Notificar Novas Vendas
                                    </label>
                                    <label>
                                        <input type="checkbox" name="notify_payment">
                                        Notificar Pagamentos
                                    </label>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Fiscal -->
                    <div class="config-tab" id="fiscal">
                        <h2>Configurações Fiscais</h2>
                        <form id="fiscalForm">
                            <div class="form-section">
                                <h3>Tributação</h3>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Regime Tributário</label>
                                        <select name="regime_tributario">
                                            <option value="simples">Simples Nacional</option>
                                            <option value="lucro_presumido">Lucro Presumido</option>
                                            <option value="lucro_real">Lucro Real</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Ambiente NFe</label>
                                        <select name="ambiente_nfe">
                                            <option value="producao">Produção</option>
                                            <option value="homologacao">Homologação</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h3>Certificado Digital</h3>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Certificado A1</label>
                                        <div class="file-upload">
                                            <input type="file" name="certificado" accept=".pfx">
                                            <button type="button" class="btn-secondary">
                                                <i class="fas fa-upload"></i> Upload
                                            </button>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Senha do Certificado</label>
                                        <input type
                                        <input type="password" name="senha_certificado" required>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Configurações de E-mail -->
                    <div class="config-tab" id="email">
                        <h2>Configurações de E-mail</h2>
                        <form id="emailForm">
                            <div class="form-section">
                                <h3>SMTP</h3>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Servidor SMTP</label>
                                        <input type="text" name="smtp_server" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Porta</label>
                                        <input type="number" name="smtp_port" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Usuário</label>
                                        <input type="text" name="smtp_user" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Senha</label>
                                        <input type="password" name="smtp_password" required>
                                    </div>
                                </div>
                                <button type="button" class="btn-primary" onclick="testEmailConfig()">
                                    <i class="fas fa-paper-plane"></i> Testar Configurações
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Backup -->
                    <div class="config-tab" id="backup">
                        <h2>Backup</h2>
                        <form id="backupForm">
                            <div class="form-section">
                                <h3>Fazer Backup</h3>
                                <button type="button" class="btn-primary" onclick="backupNow()">
                                    <i class="fas fa-database"></i> Fazer Backup Agora
                                </button>
                            </div>
                            <div class="form-section">
                                <h3>Restaurar Backup</h3>
                                <button type="button" class="btn-secondary" onclick="restoreBackup()">
                                    <i class="fas fa-undo"></i> Restaurar Backup
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Integrações -->
                    <div class="config-tab" id="integracao">
                        <h2>Integrações</h2>
                        <form id="integracaoForm">
                            <div class="form-section">
                                <h3>Google Drive</h3>
                                <button type="button" class="btn-secondary" onclick="configureGDrive()">
                                    <i class="fas fa-cloud"></i> Configurar Google Drive
                                </button>
                            </div>
                            <div class="form-section">
                                <h3>Dropbox</h3>
                                <button type="button" class="btn-secondary" onclick="configureDropbox()">
                                    <i class="fas fa-cloud-upload-alt"></i> Configurar Dropbox
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Aparência -->
                    <div class="config-tab" id="aparencia">
                        <h2>Aparência</h2>
                        <form id="aparenciaForm">
                            <div class="form-section">
                                <h3>Temas</h3>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Selecione o Tema</label>
                                        <select name="tema">
                                            <option value="default">Padrão</option>
                                            <option value="dark">Escuro</option>
                                            <option value="light">Claro</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
