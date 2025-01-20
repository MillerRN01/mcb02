<?php
include_once 'conexao.php';
session_start();

// Constantes para mensagens
define('MSG_ERRO_LOGIN', 'Você precisa estar logado para acessar esta página.');
define('MSG_ERRO_PERMISSAO', 'Você não tem permissão para acessar esta página.');
define('MSG_ERRO_CONSULTA', 'Erro ao realizar a consulta de vendas.');

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

// Verifica se é administrador
if ($_SESSION['dante'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Obtém os dados da sessão
$dante = $_SESSION['dante'];
$usuario = $_SESSION['usuario'];
$foto = $_SESSION['foto'];
$email = $_SESSION['email'];

// Inicializa variáveis de filtro
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-d', strtotime('-30 days'));
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');
$vendedor = isset($_GET['vendedor']) ? $_GET['vendedor'] : '';
$cliente = isset($_GET['cliente']) ? $_GET['cliente'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

try {
    // Consulta base
    $sql = "SELECT v.id, v.data_venda, v.valor_total, v.status,
            c.nome as cliente_nome, vd.nome as vendedor_nome,
            f.forma_pagamento, v.observacoes
            FROM vendas v
            LEFT JOIN clientes c ON v.cliente_id = c.id
            LEFT JOIN vendedores vd ON v.vendedor_id = vd.id
            LEFT JOIN formas_pagamento f ON v.forma_pagamento_id = f.id
            WHERE v.data_venda BETWEEN ? AND ?";
    
    $params = [$data_inicio, $data_fim];
    $types = "ss";

    // Adiciona filtros adicionais
    if (!empty($vendedor)) {
        $sql .= " AND vd.id = ?";
        $params[] = $vendedor;
        $types .= "i";
    }
    if (!empty($cliente)) {
        $sql .= " AND c.id = ?";
        $params[] = $cliente;
        $types .= "i";
    }
    if (!empty($status)) {
        $sql .= " AND v.status = ?";
        $params[] = $status;
        $types .= "s";
    }

    $sql .= " ORDER BY v.data_venda DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    // Consulta para popular os selects de filtro
    $vendedores = $conn->query("SELECT id, nome FROM vendedores ORDER BY nome");
    $clientes = $conn->query("SELECT id, nome FROM clientes ORDER BY nome");
    
} catch (Exception $e) {
    $error = MSG_ERRO_CONSULTA . ": " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Vendas</title>
    <link rel="shortcut icon" href="uploades/fotos/icone.png" type="image/x-icon">
    <link rel="stylesheet" href="global.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" rel="stylesheet">
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" id="menuToggle">
                <i class="bi bi-list text-white fs-2"></i>
            </button>
            <span class="navbar-brand ms-2">MeuComerciodeBolso</span>
        </div>
    </nav>

    <!-- Menu Lateral -->
    <div class="sidebar p-3" id="sidebar">
        <!-- ... (mantém o mesmo código do menu lateral) ... -->
    </div>

    <!-- Conteúdo Principal -->
    <div class="container-fluid mt-4 px-4">
        <div class="row">
            <div class="col">
                <h2><i class="bi bi-search"></i> Consulta de Vendas</h2>
                
                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-funnel"></i> Filtros</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Período</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" name="data_inicio" value="<?php echo $data_inicio; ?>">
                                    <span class="input-group-text">até</span>
                                    <input type="date" class="form-control" name="data_fim" value="<?php echo $data_fim; ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Vendedor</label>
                                <select class="form-select" name="vendedor">
                                    <option value="">Todos</option>
                                    <?php while($vend = $vendedores->fetch_assoc()): ?>
                                        <option value="<?php echo $vend['id']; ?>" 
                                                <?php echo $vendedor == $vend['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($vend['nome']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Cliente</label>
                                <select class="form-select" name="cliente">
                                    <option value="">Todos</option>
                                    <?php while($cli = $clientes->fetch_assoc()): ?>
                                        <option value="<?php echo $cli['id']; ?>"
                                                <?php echo $cliente == $cli['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cli['nome']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="">Todos</option>
                                    <option value="concluida" <?php echo $status == 'concluida' ? 'selected' : ''; ?>>Concluída</option>
                                    <option value="pendente" <?php echo $status == 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                                    <option value="cancelada" <?php echo $status == 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search"></i> Filtrar
                                </button>
                                <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-secondary">
                                    <i class="bi bi-arrow-counterclockwise"></i> Limpar Filtros
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Resultados -->
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-table"></i> Resultados</h5>
                        <div>
                            <button class="btn btn-light btn-sm" onclick="exportarPDF()">
                                <i class="bi bi-file-pdf"></i> PDF
                            </button>
                            <button class="btn btn-light btn-sm" onclick="exportarExcel()">
                                <i class="bi bi-file-excel"></i> Excel
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <?php echo $error; ?>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Cliente</th>
                                            <th>Vendedor</th>
                                            <th>Valor Total</th>
                                            <th>Forma Pagamento</th>
                                            <th>Status</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result && $result->num_rows > 0): ?>
                                            <?php while ($venda = $result->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo date('d/m/Y', strtotime($venda['data_venda'])); ?></td>
                                                    <td><?php echo htmlspecialchars($venda['cliente_nome']); ?></td>
                                                    <td><?php echo htmlspecialchars($venda['vendedor_nome']); ?></td>
                                                    <td>R$ <?php echo number_format($venda['valor_total'], 2, ',', '.'); ?></td>
                                                    <td><?php echo htmlspecialchars($venda['forma_pagamento']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            echo $venda['status'] == 'concluida' ? 'success' : 
                                                                ($venda['status'] == 'pendente' ? 'warning' : 'danger'); 
                                                        ?>">
                                                            <?php echo ucfirst($venda['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-info btn-sm" onclick="verDetalhes(<?php echo $venda['id']; ?>)">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                        <button class="btn btn-warning btn-sm" onclick="imprimirComprovante(<?php echo $venda['id']; ?>)">
                                                            <i class="bi bi-printer"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center">Nenhuma venda encontrada</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Detalhes -->
    <div class="modal fade" id="detalhesModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalhes da Venda</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detalhesVendaContent">
                    <!-- Conteúdo será carregado via AJAX -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script src="global.js"></script>

    <script>
        // Função para ver detalhes da venda
        function verDetalhes(id) {
            $.get('ajax_detalhes_venda.php', { id: id }, function(data) {
                $('#detalhesVendaContent').html(data);
                new bootstrap.Modal(document.getElementById('detalhesModal')).show();
            });
        }

        // Função para imprimir comprovante
        function imprimirComprovante(id) {
            window.open('imprimir_comprovante.php?id=' + id, '_blank');
        }

        // Função para exportar para PDF
        function exportarPDF() {
            window.location.href = 'exportar_vendas.php?tipo=pdf' + window.location.search;
        }

        // Função para exportar para Excel
        function exportarExcel() {
            window.location.href = 'exportar_vendas.php?tipo=excel' + window.location.search;
        }

        // Inicialização de componentes
        $(document).ready(function() {
            // Inicializa select2 para melhor experiência em selects longos
            if ($.fn.select2) {
                $('.form-select').select2({
                    theme: 'bootstrap-5'
                });
            }

            // Atualiza automaticamente ao mudar filtros
            $('form select').change(function() {
                $(this).closest('form').submit();
            });
        });
    </script>
</body>
</html>