<?php
// Configuração inicial
session_start();
require_once 'conexao.php';
require_once 'functions/auth.php';
require_once 'functions/funcionarios.php';

// Verifica autenticação e permissão de admin
checkAuth();
checkAdminAccess();

// Obtém dados do usuário
$usuario = $_SESSION['usuario'];
$dante = $_SESSION['dante'];
$foto = $_SESSION['foto'] ?? 'assets/img/default-avatar.png';
$email = $_SESSION['email'];

// Obtém estatísticas dos funcionários
$stats = getFuncionariosStats($conn);

// Define departamentos disponíveis
$departamentos = [
    ['id' => 'vendas', 'nome' => 'Vendas'],
    ['id' => 'financeiro', 'nome' => 'Financeiro'],
    ['id' => 'rh', 'nome' => 'Recursos Humanos'],
    ['id' => 'ti', 'nome' => 'TI'],
    ['id' => 'logistica', 'nome' => 'Logística']
];

// Define cargos disponíveis
$cargos = [
    ['id' => 'vendedor', 'nome' => 'Vendedor'],
    ['id' => 'gerente', 'nome' => 'Gerente'],
    ['id' => 'supervisor', 'nome' => 'Supervisor'],
    ['id' => 'analista', 'nome' => 'Analista']
];

// Define status disponíveis
$statusList = [
    ['id' => 'ativo', 'nome' => 'Ativo'],
    ['id' => 'ferias', 'nome' => 'Férias'],
    ['id' => 'afastado', 'nome' => 'Afastado'],
    ['id' => 'inativo', 'nome' => 'Inativo']
];

// Configurações da página
$pageTitle = "Gerenciamento de Funcionários - Sistema de Gestão";
$currentPage = "funcionarios";

// Configurações de upload
$uploadConfig = [
    'max_file_size' => 5 * 1024 * 1024, // 5MB
    'allowed_types' => ['image/jpeg', 'image/png', 'application/pdf'],
    'upload_dir' => 'uploads/documentos/'
];

// Verifica e cria diretório de upload se não existir
if (!file_exists($uploadConfig['upload_dir'])) {
    mkdir($uploadConfig['upload_dir'], 0777, true);
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema de gerenciamento de funcionários">
    <meta name="author" content="Seu Nome/Empresa">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="assets/img/icone.png" type="image/x-icon">
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/funcionarios.css">
    <link rel="stylesheet" href="assets/css/global.css">
    
    <!-- Bootstrap e ícones -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Preload de recursos críticos -->
    <link rel="preload" href="assets/js/funcionarios.js" as="script">
    <link rel="preload" href="assets/css/funcionarios.css" as="style">
</head>
<body>
    <!-- Navbar -->
    <?php include 'components/navbar.php'; ?>

    <!-- Menu Lateral -->
    <div class="sidebar p-3" id="sidebar">
        <!-- Perfil do usuário -->
        <div class="user-profile card mb-3">
            <div class="card-body text-center">
                <img src="<?php echo htmlspecialchars($foto); ?>"
                     class="profile-image rounded-circle mb-2"
                     alt="Foto do Usuário"
                     width="80" height="80">
                <h5 class="card-title"><?php echo htmlspecialchars($usuario); ?></h5>
                <p class="card-text text-muted"><?php echo htmlspecialchars($email); ?></p>
            </div>
        </div>

        <!-- Menu de navegação -->
        <?php include 'components/sidebar-menu.php'; ?>
    </div>

    <!-- Conteúdo Principal -->
    <main class="main-content">
        <div class="funcionarios-content">
            <!-- Header -->
            <div class="content-header d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">Gerenciamento de Funcionários</h1>
                <div class="header-actions">
                    <?php if (checkPermission('funcionario_adicionar')): ?>
                        <button class="btn btn-primary" onclick="openNewFuncionarioModal()">
                            <i class="fas fa-user-plus"></i> Novo Funcionário
                        </button>
                    <?php endif; ?>
                    <?php if (checkPermission('funcionario_exportar')): ?>
                        <button class="btn btn-secondary" onclick="exportFuncionarios()">
                            <i class="fas fa-file-export"></i> Exportar
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Cards de Resumo -->
            <div class="summary-cards">
                <div class="summary-card">
                    <div class="card-icon bg-primary">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="card-info">
                        <h3>Total de Funcionários</h3>
                        <p class="card-value"><?php echo number_format($stats['total']); ?></p>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="card-icon bg-warning">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div class="card-info">
                        <h3>Em Férias</h3>
                        <p class="card-value"><?php echo number_format($stats['ferias']); ?></p>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="card-icon bg-success">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="card-info">
                        <h3>Performance</h3>
                        <p class="card-value"><?php echo number_format($stats['performance'], 1); ?>%</p>
                        <span class="text-muted">Média geral</span>
                    </div>
                </div>
            </div>

            <!-- Filtros e Pesquisa -->
            <div class="filters-section card">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" 
                                       id="searchFuncionario" 
                                       class="form-control" 
                                       placeholder="Buscar funcionários..."
                                       aria-label="Buscar funcionários">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="filters d-flex gap-3">
                                <select id="departamentoFilter" class="form-select">
                                    <option value="">Departamento</option>
                                    <?php foreach ($departamentos as $departamento): ?>
                                        <option value="<?php echo htmlspecialchars($departamento['id']); ?>">
                                            <?php echo htmlspecialchars($departamento['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <select id="cargoFilter" class="form-select">
                                    <option value="">Cargo</option>
                                    <?php foreach ($cargos as $cargo): ?>
                                        <option value="<?php echo htmlspecialchars($cargo['id']); ?>">
                                            <?php echo htmlspecialchars($cargo['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <select id="statusFilter" class="form-select">
                                    <option value="">Status</option>
                                    <?php foreach ($statusList as $status): ?>
                                        <option value="<?php echo htmlspecialchars($status['id']); ?>">
                                            <?php echo htmlspecialchars($status['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de Funcionários -->
            <div class="funcionarios-grid" id="funcionariosGrid">
                <div class="loading-spinner text-center d-none">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                </div>
            </div>

            <!-- Paginação -->
            <nav aria-label="Navegação de páginas" class="pagination-container">
                <div class="pagination justify-content-center">
                    <button class="btn btn-outline-primary btn-page" disabled>
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <span class="page-info mx-3">Página 1 de 5</span>
                    <button class="btn btn-outline-primary btn-page">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </nav>
        </div>
    </main>

    <!-- Modal de Funcionário -->
    <?php include 'components/funcionario-modal.php'; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-masker/1.2.0/vanilla-masker.min.js"></script>
    <script src="assets/js/funcionarios.js"></script>
    <script src="assets/js/global.js"></script>

    <!-- Configurações iniciais -->
    <script>
        // Passa variáveis PHP para JavaScript
        const userConfig = {
            permissions: <?php echo json_encode(getUserPermissions()); ?>,
            dante: '<?php echo $dante; ?>',
            userId: <?php echo $_SESSION['user_id']; ?>,
            uploadConfig: <?php echo json_encode($uploadConfig); ?>
        };
    </script>
</body>
</html>