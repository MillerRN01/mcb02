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
$usuario = htmlspecialchars($_SESSION['usuario']);
$foto = htmlspecialchars($_SESSION['foto'] ?? 'https://via.placeholder.com/100');
$email = htmlspecialchars($_SESSION['email']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendas - Sistema de Vendas</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/vendas.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</head>
<body>
    <!-- Navbar com o ícone de menu e o nome ao lado -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" id="menuToggle"><i class="bi bi-list text-white fs-2"></i></button>
            <span class="navbar-brand ms-2">MeuComerciodeBolso</span>
        </div>
    </nav>

    <!-- Menu Lateral -->
    <div class="sidebar p-3" id="sidebar">
        <div class="card mb-3" style="width: 100%;">
            <div class="card-body text-center">
                <img src="<?php echo $foto; ?>" class="card-img-top rounded-circle mb-2" alt="Foto do Usuário" style="width: 80px; height: 80px; object-fit: cover;">
                <h5 class="card-title"><?php echo $usuario; ?></h5>
                <p class="card-text"><?php echo $email; ?></p>
            </div>
        </div>

        <!-- Seções do Menu -->
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
                <li><a href="cadastro_funcionario.php"><i class="bi bi-person-badge"></i> Cadastro de Funcionário</a></li>
                <li><a href="financeiro.php"><i class="bi bi-wallet"></i> Financeiro</a></li>
            </ul>

            <div class="sidebar-header">
                <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Relatório</h5>
            </div>
            <ul class="sidebar-list list-unstyled">
                <li><a href="relatorios.php"><i class="bi bi-bar-chart"></i> Relatórios</a></li>
                <li><a href="relatorios_consolidados.php"><i class="bi bi-pie-chart"></i> Relatórios consolidados</a></li>
            </ul>
        <?php endif; ?>

        <div class="sidebar-header">
            <h5 class="mb-0"><i class="bi bi-gear"></i> Preferências</h5>
        </div>
        <ul class="sidebar-list list-unstyled">
            <li><a href="configuracoes.php"><i class="bi bi-gear"></i> Configurações</a></li>
            <li><a href="ajuda.php"><i class="bi bi-question-circle"></i> Ajuda</a></li>
            <li><a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <main class="main-content">
        <div class="top-bar">
            <!-- ... (mesmo conteúdo do dashboard) ... -->
        </div>

        <!-- Vendas Content -->
        <div class="vendas-content">
            <div class="content-header">
                <h1>Gerenciamento de Vendas</h1>
                <div class="header-actions">
                    <button class="btn-primary" onclick="openNewSaleModal()">
                        <i class="fas fa-plus"></i> Nova Venda
                    </button>
                    <button class="btn-secondary" onclick="exportSales()">
                        <i class="fas fa-file-export"></i> Exportar
                    </button>
                </div>
            </div>

            <!-- Filtros e Pesquisa -->
            <div class="filters-section">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchSale" placeholder="Buscar vendas...">
                </div>
                <div class="filters">
                    <div class="date-filter">
                        <input type="date" id="startDate">
                        <span>até</span>
                        <input type="date" id="endDate">
                    </div>
                    <select id="statusFilter">
                        <option value="">Todos os Status</option>
                        <option value="1">Concluída</option>
                        <option value="2">Pendente</option>
                        <option value="3">Cancelada</option>
                    </select>
                    <select id="vendedorFilter">
                        <option value="">Todos os Vendedores</option>
                    </select>
                </div>
            </div>

            <!-- Resumo de Vendas -->
            <div class="sales-summary">
                <div class="summary-card">
                    <div class="summary-icon" style="background-color: #4CAF50;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="summary-info">
                        <h3>Vendas Concluídas</h3>
                        <p class="summary-value">R$ 15.789,00</p>
                        <span>Hoje</span>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="summary-icon" style="background-color: #FFC107;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="summary-info">
                        <h3>Vendas Pendentes</h3>
                        <p class="summary-value">R$ 3.450,00</p>
                        <span>5 vendas</span>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="summary-icon" style="background-color: #F44336;">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="summary-info">
                        <h3>Vendas Canceladas</h3>
                        <p class="summary-value">R$ 890,00</p>
                        <span>Hoje</span>
                    </div>
                </div>
            </div>

            <!-- Tabela de Vendas -->
            <div class="table-responsive">
                <table class="sales-table">
                    <thead>
                        <tr>
                            <th>Nº Venda</th>
                            <th>Data</th>
                            <th>Cliente</th>
                            <th>Vendedor</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="salesTableBody">
                        <!-- Vendas serão carregadas via JavaScript -->
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <div class="pagination">
                <button class="btn-page" disabled>
                    <i class="fas fa-chevron-left"></i>
                </button>
                <span class="page-info">Página 1 de 10</span>
                <button class="btn-page">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </main>

    <!-- Modal de Nova Venda -->
    <div id="saleModal" class="modal">
        <div class="modal-content modal-lg">
            <span class="close" onclick="closeSaleModal()">&times;</span>
            <h2 id="modalTitle">Nova Venda</h2>
            
            <form id="saleForm" onsubmit="handleSaleSubmit(event)">
                <div class="sale-form-grid">
                    <!-- Informações do Cliente -->
                    <div class="form-section">
                        <h3>Informações do Cliente</h3>
                        <div class="form-group">
                            <label>Cliente*</label>
                            <div class="search-select">
                                <input type="text" id="clientSearch" placeholder="Buscar cliente..." required>
                                <div id="clientResults" class="search-results"></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>CPF na Nota</label>
                            <input type="text" id="cpfNota" name="cpf_na_nota" pattern="\d{11}" title="Digite um CPF válido (11 dígitos)">
                        </div>
                    </div>

                    <!-- Produtos -->
                    <div class="form-section">
                        <h3>Produtos</h3>
                        <div class="products-list">
                            <div class="product-search">
                                <input type="text" id="productSearch" placeholder="Buscar produto..." required>
                                <div id="productResults" class="search-results"></div>
                            </div>
                            <div id="selectedProducts" class="selected-products">
                                <!-- Produtos selecionados serão adicionados aqui -->
                            </div>
                        </div>
                    </div>

                    <!-- Pagamento -->
                    <div class="form-section">
                        <h3>Pagamento</h3>
                        <div class="form-group">
                            <label>Forma de Pagamento*</label>
                            <select name="forma_pagamento" required>
                                <option value="">Selecione</option>
                                <option value="dinheiro">Dinheiro</option>
                                <option value="cartao_credito">Cartão de Crédito</option>
                                <option value="cartao_debito">Cartão de Débito</option>
                                <option value="pix">PIX</option>
                            </select>
                        </div>
                        <div class="payment-summary">
                            <div class="summary-row">
                                <span>Subtotal:</span>
                                <span id="subtotal">R$ 0,00</span>
                            </div>
                            <div class="summary-row">
                                <span>Desconto:</span>
                                <div class="discount-input">
                                    <input type="number" id="discount" name="desconto" min="0" step="0.01" value="0">
                                    <span>%</span>
                                </div>
                            </div>
                            <div class="summary-row total">
                                <span>Total:</span>
                                <span id="total">R$ 0,00</span>
                            </div>
                        </div>
                    </div>

                    <!-- Observações -->
                    <div class="form-section">
                        <h3>Observações</h3>
                        <textarea name="observacoes" rows="3" placeholder="Observações sobre a venda..."></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeSaleModal()">Cancelar</button>
                    <button type="submit" class="btn-primary">Finalizar Venda</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/vendas.js"></script>
    <script src="js/global.js"></script>
</body>
</html>
