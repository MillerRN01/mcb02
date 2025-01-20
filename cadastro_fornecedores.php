<?php
// Configuração inicial
session_start();
require_once 'conexao.php';
require_once 'functions/auth.php';
require_once 'functions/fornecedores.php';

// Verifica autenticação
checkAuth();

// Obtém dados do usuário
$usuario = $_SESSION['usuario'];
$dante = $_SESSION['dante'];
$foto = $_SESSION['foto'] ?? 'assets/img/default-avatar.png';
$email = $_SESSION['email'];

// Obtém estatísticas dos fornecedores
$stats = getFornecedoresStats($conn);

// Define as categorias disponíveis
$categorias = [
    ['id' => 'eletronicos', 'nome' => 'Eletrônicos'],
    ['id' => 'moveis', 'nome' => 'Móveis'],
    ['id' => 'alimentos', 'nome' => 'Alimentos'],
    ['id' => 'vestuario', 'nome' => 'Vestuário'],
    ['id' => 'informatica', 'nome' => 'Informática'],
    ['id' => 'outros', 'nome' => 'Outros']
];

// Configurações da página
$pageTitle = "Gestão de Fornecedores - Sistema de Gestão";
$currentPage = "fornecedores";

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema de gestão de fornecedores">
    <meta name="author" content="Seu Nome/Empresa">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="assets/img/icone.png" type="image/x-icon">
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/fornecedores.css">
    <link rel="stylesheet" href="assets/css/global.css">
    
    <!-- Bootstrap e ícones -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Preload de recursos críticos -->
    <link rel="preload" href="assets/js/fornecedores.js" as="script">
    <link rel="preload" href="assets/css/fornecedores.css" as="style">
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
        <div class="fornecedores-content">
            <!-- Header -->
            <div class="content-header d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">Gestão de Fornecedores</h1>
                <div class="header-actions">
                    <?php if (checkPermission('fornecedor_adicionar')): ?>
                        <button class="btn btn-primary" onclick="openNewSupplierModal()">
                            <i class="fas fa-plus"></i> Novo Fornecedor
                        </button>
                    <?php endif; ?>
                    <?php if (checkPermission('fornecedor_exportar')): ?>
                        <button class="btn btn-secondary" onclick="exportSuppliers()">
                            <i class="fas fa-file-export"></i> Exportar
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Cards de Resumo -->
            <div class="summary-cards">
                <div class="summary-card">
                    <div class="card-icon bg-primary">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div class="card-info">
                        <h3>Total de Fornecedores</h3>
                        <p class="card-value"><?php echo number_format($stats['total']); ?></p>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="card-icon bg-success">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="card-info">
                        <h3>Compras no Mês</h3>
                        <p class="card-value">
                            <?php echo formatMoney($stats['compras_mes']); ?>
                        </p>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="card-icon bg-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="card-info">
                        <h3>Pedidos Pendentes</h3>
                        <p class="card-value"><?php echo $stats['pedidos_pendentes']; ?></p>
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
                                       id="searchSupplier" 
                                       class="form-control" 
                                       placeholder="Buscar fornecedores..."
                                       aria-label="Buscar fornecedores">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="filters d-flex gap-3">
                                <select id="categoryFilter" class="form-select">
                                    <option value="">Todas as Categorias</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?php echo htmlspecialchars($categoria['id']); ?>">
                                            <?php echo htmlspecialchars($categoria['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <select id="statusFilter" class="form-select">
                                    <option value="">Status</option>
                                    <option value="ativo">Ativo</option>
                                    <option value="inativo">Inativo</option>
                                </select>

                                <select id="sortFilter" class="form-select">
                                    <option value="nome">Nome</option>
                                    <option value="avaliacao">Avaliação</option>
                                    <option value="ultima_compra">Última Compra</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de Fornecedores -->
            <div class="suppliers-grid" id="suppliersGrid">
                <!-- Cards serão inseridos via JavaScript -->
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

    <!-- Modal de Fornecedor -->
    <?php include 'components/supplier-modal.php'; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-masker/1.2.0/vanilla-masker.min.js"></script>
    <script src="assets/js/fornecedores.js"></script>
    <script src="assets/js/global.js"></script>

    <!-- Configurações iniciais -->
    <script>
        // Passa variáveis PHP para JavaScript
        const userConfig = {
            permissions: <?php echo json_encode(getUserPermissions()); ?>,
            dante: '<?php echo $dante; ?>',
            userId: <?php echo $_SESSION['user_id']; ?>
        };
    </script>
</body>
</html>