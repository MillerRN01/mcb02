<?php
include_once 'conexao.php'; // Inclua sua conexão com o banco de dados
session_start(); // Inicia a sessão

// Função para verificar se o usuário está logado
function verificarLogin() {
    if (!isset($_SESSION['usuario'])) {
        header("Location: index.php"); // Redireciona para a página de index se não estiver logado
        exit();
    }
}

// Chama a função para verificar o login
verificarLogin();

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
    <title>Relatórios - Sistema de Gestão</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="relatorios.css">
    <link rel="stylesheet" href="global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</head>

<body>
    <!-- Menu Lateral -->
    <div class="sidebar p-3" id="sidebar">
        <div class="card mb-3" style="width: 100%;">
            <div class="card-body text-center">
                <img src="<?php echo $foto; ?>" class="card-img-top rounded-circle mb-2" alt="Foto do Usuário" style="width: 80px; height: 80px; object-fit: cover;">
                <h5 class="card-title"><?php echo $usuario; ?></h5><!-- Nome do usuário -->
                <p class="card-text"><?php echo $email; ?></p><!-- Email do usuário -->
            </div>
        </div>
        <!-- Seção Cadastro -->
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

        <!-- Seção Gestão (admin) -->
        <?php if ($dante === 'admin'): ?>
            <div class="sidebar-header">
                <h5 class="mb-0"><i class="bi bi-gear"></i> Gestão</h5>
            </div>
            <ul class="sidebar-list list-unstyled">
                <li><a href="consulta_vendas.php"><i class="bi bi-search"></i> Consulta Vendas</a></li>
                <li><a href="estoque.php"><i class="bi bi-box"></i> Consultar Estoque</a></li>
                <li><a href="caixa.php"><i class="bi bi-cash-stack"></i> Controle de Caixa</a></li>
                <li><a href="fiado.php"><i class="bi bi-credit-card"></i> Fiado</a></li>
                <li><a href="cadastro_funcionario.php"><i class="bi bi-person-badge"></i> Cadastro de Funcionário</a></li>
                <li><a href="financeiro.php"><i class="bi bi-wallet"></i> Financeiro</a></li>
            </ul>

            <!-- Seção Relatório -->
            <div class="sidebar-header">
                <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Relatório</h5>
            </div>
            <ul class="sidebar-list list-unstyled">
                <li><a href="relatorios.php"><i class="bi bi-bar-chart"></i> Relatórios</a></li>
                <li><a href="relatorios_consolidados.php"><i class="bi bi-pie-chart"></i> Relatórios Consolidados</a></li>
            </ul>
        <?php endif; ?>

        <!-- Seção Configuração -->
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
        <!-- Top Bar -->
        <div class="top-bar">
            <!-- ... (mesmo conteúdo do dashboard) ... -->
        </div>

        <!-- Relatórios Content -->
        <div class="relatorios-content">
            <!-- Header -->
            <div class="content-header">
                <h1>Relatórios e Análises</h1>
                <div class="header-actions">
                    <button class="btn-secondary" onclick="exportCurrentReport()">
                        <i class="fas fa-file-export"></i> Exportar
                    </button>
                    <button class="btn-primary" onclick="printReport()">
                        <i class="fas fa-print"></i> Imprimir
                    </button>
                </div>
            </div>

            <!-- Filtros Globais -->
            <div class="global-filters">
                <div class="date-range">
                    <label>Período:</label>
                    <input type="date" id="startDate">
                    <span>até</span>
                    <input type="date" id="endDate">
                </div>
                <div class="quick-filters">
                    <button onclick="setDateRange('hoje')">Hoje</button>
                    <button onclick="setDateRange('semana')">Última Semana</button>
                    <button onclick="setDateRange('mes')">Último Mês</button>
                    <button onclick="setDateRange('ano')">Este Ano</button>
                </div>
            </div>

            <!-- Grid de Relatórios -->
            <div class="reports-grid">
                <!-- Vendas -->
                <div class="report-card">
                    <div class="report-header">
                        <h3>Relatório de Vendas</h3>
                        <div class="report-actions">
                            <button onclick="refreshReport('vendas')">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <button onclick="toggleFullscreen(this)">
                                <i class="fas fa-expand"></i>
                            </button>
                        </div>
                    </div>
                    <div class="report-content">
                        <canvas id="vendasChart"></canvas>
                        <div class="report-metrics">
                            <div class="metric">
                                <span class="metric-label">Total de Vendas</span>
                                <span class="metric-value">R$ 45.678,90</span>
                                <span class="metric-trend positive">+12.5%</span>
                            </div>
                            <div class="metric">
                                <span class="metric-label">Ticket Médio</span>
                                <span class="metric-value">R$ 150,00</span>
                                <span class="metric-trend negative">-3.2%</span>
                            </div>
                            <div class="metric">
                                <span class="metric-label">Total de Pedidos</span>
                                <span class="metric-value">304</span>
                                <span class="metric-trend positive">+8.7%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Produtos -->
                <div class="report-card">
                    <div class="report-header">
                        <h3>Produtos Mais Vendidos</h3>
                        <div class="report-actions">
                            <button onclick="refreshReport('produtos')">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <button onclick="toggleFullscreen(this)">
                                <i class="fas fa-expand"></i>
                            </button>
                        </div>
                    </div>
                    <div class="report-content">
                        <canvas id="produtosChart"></canvas>
                        <div class="top-products">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Produto</th>
                                        <th>Qtde</th>
                                        <th>Valor</th>
                                        <th>%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Dados serão inseridos via JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Clientes -->
                <div class="report-card">
                    <div class="report-header">
                        <h3>Análise de Clientes</h3>
                        <div class="report-actions">
                            <button onclick="refreshReport('clientes')">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <button onclick="toggleFullscreen(this)">
                                <i class="fas fa-expand"></i>
                            </button>
                        </div>
                    </div>
                    <div class="report-content">
                        <canvas id="clientesChart"></canvas>
                        <div class="customer-metrics">
                            <div class="metric">
                                <span class="metric-label">Novos Clientes</span>
                                <span class="metric-value">45</span>
                                <span class="metric-trend positive">+15.8%</span>
                            </div>
                            <div class="metric">
                                <span class="metric-label">Taxa de Retenção</span>
                                <span class="metric-value">78%</span>
                                <span class="metric-trend positive">+2.3%</span>
                            </div>
                            <div class="metric">
                                <span class="metric-label">Clientes Ativos</span>
                                <span class="metric-value">892</span>
                                <span class="metric-trend positive">+5.4%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Financeiro -->
                <div class="report-card">
                    <div class="report-header">
                        <h3>Relatório Financeiro</h3>
                        <div class="report-actions">
                            <button onclick="refreshReport('financeiro')">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <button onclick="toggleFullscreen(this)">
                                <i class="fas fa-expand"></i>
                            </button>
                        </div>
                    </div>
                    <div class="report-content">
                        <canvas id="financeiroChart"></canvas>
                        <div class="financial-metrics">
                            <div class="metric">
                                <span class="metric-label">Receita</span>
                                <span class="metric-value">R$ 85.432,10</span>
                                <span class="metric-trend positive">+18.5%</span>
                            </div>
                            <div class="metric">
                                <span class="metric-label">Despesas</span>
                                <span class="metric-value">R$ 34.567,89</span>
                                <span class="metric-trend negative">+7.2%</span>
                            </div>
                            <div class="metric">
                                <span class="metric-label">Lucro</span>
                                <span class="metric-value">R$ 50.864,21</span>
                                <span class="metric-trend positive">+25.8%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de Relatórios Disponíveis -->
            <div class="available-reports">
                <h3>Relatórios Disponíveis</h3>
                <div class="reports-list">
                    <div class="report-item" onclick="generateReport('vendas_detalhado')">
                        <i class="fas fa-file-alt"></i>
                        <div class="report-info">
                            <h4>Vendas Detalhadas</h4>
                            <p>Relatório completo de vendas com detalhes por produto e cliente</p>
                        </div>
                        <i class="fas fa-chevron-right"></i>
                    </div>

                    <div class="report-item" onclick="generateReport('estoque')">
                        <i class="fas fa-boxes"></i>
                        <div class="report-info">
                            <h4>Controle de Estoque</h4>
                            <p>Análise de estoque, produtos em baixa e giro de mercadoria</p>
                        </div>
                        <i class="fas fa-chevron-right"></i>
                    </div>

                    <div class="report-item" onclick="generateReport('financeiro_completo')">
                        <i class="fas fa-chart-line"></i>
                        <div class="report-info">
                            <h4>Financeiro Completo</h4>
                            <p>Relatório financeiro detalhado com DRE e fluxo de caixa</p>
                        </div>
                        <i class="fas fa-chevron-right"></i>
                    </div>

                    <div class="report-item" onclick="generateReport('comissoes')">
                        <i class="fas fa-user-tie"></i>
                        <div class="report-info">
                            <h4>Comissões</h4>
                            <p>Relatório de comissões por vendedor e período</p>
                        </div>
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal de Relatório -->
    <div id="reportModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeReportModal()">&times;</span>
            <h2 id="reportTitle">Gerando Relatório</h2>
            <div class="report-options">
                <div class="form-group">
                    <label>Formato:</label>
                    <select id="reportFormat">
                        <option value="pdf">PDF</option>
                        <option value="excel">Excel</option>
                        <option value="csv">CSV</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Agrupar por:</label>
                    <select id="reportGrouping">
                        <option value="dia">Dia</option>
                        <option value="semana">Semana</option>
                        <option value="mes">Mês</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Incluir:</label>
                    <div class="checkbox-group">
                        <label>
                            <input type="checkbox" checked> Gráficos
                        </label>
                        <label>
                            <input type="checkbox" checked> Tabelas
                        </label>
                        <label>
                            <input type="checkbox" checked> Análises
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-actions">
                <button class="btn-secondary" onclick="closeReportModal()">Cancelar</button>
                <button class="btn-primary" onclick="downloadReport()">
                    <i class="fas fa-download"></i> Baixar Relatório
                </button>
            </div>
        </div>
    </div>

    <script src="relatorios.js"></script>
    <script src="global.js"></script>
</body>

</html>
