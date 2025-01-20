<?php
require_once 'conexao.php';
require_once 'classes/Auth.php';
require_once 'classes/CashRegister.php';

// Inicializa o sistema de autenticação
$auth = new Auth();
$auth->checkAuth();
$userData = $auth->getUserData();

// Inicializa o controle de caixa
$cashRegister = new CashRegister($conn);
$currentBalance = $cashRegister->getCurrentBalance();
$todayTransactions = $cashRegister->getTodayTransactions();
$cashierStatus = $cashRegister->getCashierStatus();

// Obtém resumo do caixa
$summary = $cashRegister->getDailySummary();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Caixa - MeuComerciodeBolso</title>
    <link rel="shortcut icon" href="uploads/fotos/icone.png" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/caixa.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <?php include 'templates/navbar.php'; ?>
    
    <!-- Sidebar -->
    <?php include 'templates/sidebar.php'; ?>

    <!-- Conteúdo Principal -->
    <div class="container-fluid main-content">
        <div class="row">
            <!-- Resumo do Caixa -->
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-cash-stack"></i> Status do Caixa
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="status-indicator <?php echo $cashierStatus ? 'opened' : 'closed'; ?>">
                            <?php echo $cashierStatus ? 'Caixa Aberto' : 'Caixa Fechado'; ?>
                        </div>
                        <div class="current-balance">
                            <h3>Saldo Atual</h3>
                            <h2>R$ <?php echo number_format($currentBalance, 2, ',', '.'); ?></h2>
                        </div>
                        <div class="daily-summary">
                            <div class="summary-item">
                                <span>Entradas:</span>
                                <strong class="text-success">R$ <?php echo number_format($summary['income'], 2, ',', '.'); ?></strong>
                            </div>
                            <div class="summary-item">
                                <span>Saídas:</span>
                                <strong class="text-danger">R$ <?php echo number_format($summary['expense'], 2, ',', '.'); ?></strong>
                            </div>
                        </div>
                        <div class="action-buttons mt-3">
                            <?php if (!$cashierStatus): ?>
                                <button class="btn btn-success w-100 mb-2" onclick="openCashier()">
                                    <i class="bi bi-unlock"></i> Abrir Caixa
                                </button>
                            <?php else: ?>
                                <button class="btn btn-danger w-100 mb-2" onclick="closeCashier()">
                                    <i class="bi bi-lock"></i> Fechar Caixa
                                </button>
                            <?php endif; ?>
                            <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#transactionModal">
                                <i class="bi bi-plus-circle"></i> Nova Transação
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de Transações -->
            <div class="col-md-8 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-list-check"></i> Transações do Dia
                        </h5>
                        <div class="header-actions">
                            <button class="btn btn-light btn-sm" onclick="printDailyReport()">
                                <i class="bi bi-printer"></i> Imprimir
                            </button>
                            <button class="btn btn-light btn-sm ms-2" onclick="exportTransactions()">
                                <i class="bi bi-download"></i> Exportar
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Hora</th>
                                        <th>Tipo</th>
                                        <th>Descrição</th>
                                        <th>Valor</th>
                                        <th>Método</th>
                                        <th>Operador</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($todayTransactions as $transaction): ?>
                                        <tr class="<?php echo $transaction['tipo'] === 'entrada' ? 'table-success' : 'table-danger'; ?>">
                                            <td><?php echo date('H:i', strtotime($transaction['data_hora'])); ?></td>
                                            <td>
                                                <span class="badge <?php echo $transaction['tipo'] === 'entrada' ? 'bg-success' : 'bg-danger'; ?>">
                                                    <?php echo ucfirst($transaction['tipo']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($transaction['descricao']); ?></td>
                                            <td>R$ <?php echo number_format($transaction['valor'], 2, ',', '.'); ?></td>
                                            <td><?php echo htmlspecialchars($transaction['metodo_pagamento']); ?></td>
                                            <td><?php echo htmlspecialchars($transaction['operador']); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-info" onclick="viewTransaction(<?php echo $transaction['id']; ?>)">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <?php if ($auth->isAdmin()): ?>
                                                    <button class="btn btn-sm btn-danger" onclick="cancelTransaction(<?php echo $transaction['id']; ?>)">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Nova Transação -->
    <div class="modal fade" id="transactionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nova Transação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="transactionForm" onsubmit="handleTransaction(event)">
                        <div class="mb-3">
                            <label class="form-label">Tipo de Transação</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="tipo" id="entrada" value="entrada" checked>
                                <label class="btn btn-outline-success" for="entrada">Entrada</label>
                                <input type="radio" class="btn-check" name="tipo" id="saida" value="saida">
                                <label class="btn btn-outline-danger" for="saida">Saída</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="valor" class="form-label">Valor</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="number" class="form-control" id="valor" name="valor" step="0.01" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="2" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="metodo" class="form-label">Método de Pagamento</label>
                            <select class="form-select" id="metodo" name="metodo" required>
                                <option value="dinheiro">Dinheiro</option>
                                <option value="pix">PIX</option>
                                <option value="cartao_credito">Cartão de Crédito</option>
                                <option value="cartao_debito">Cartão de Débito</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="transactionForm" class="btn btn-primary">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/global.js"></script>
    <script src="assets/js/caixa.js"></script>
</body>
</html>