<?php
include_once 'conexao.php';
session_start();

// Verifica autenticação
if (!isset($_SESSION['usuario'])) {
    die('Não autorizado');
}

if (!isset($_GET['id'])) {
    die('ID não fornecido');
}

$id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

try {
    // Consulta detalhes da venda
    $stmt = $conn->prepare("
        SELECT v.*, 
               c.nome as cliente_nome,
               vd.nome as vendedor_nome,
               f.forma_pagamento,
               GROUP_CONCAT(CONCAT(p.nome, ' (', iv.quantidade, ' x R$ ', iv.preco_unitario, ')') SEPARATOR '<br>') as itens
        FROM vendas v
        LEFT JOIN clientes c ON v.cliente_id = c.id
        LEFT JOIN vendedores vd ON v.vendedor_id = vd.id
        LEFT JOIN formas_pagamento f ON v.forma_pagamento_id = f.id
        LEFT JOIN itens_venda iv ON v.id = iv.venda_id
        LEFT JOIN produtos p ON iv.produto_id = p.id
        WHERE v.id = ?
        GROUP BY v.id
    ");
    
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $venda = $result->fetch_assoc();

    if (!$venda) {
        die('Venda não encontrada');
    }
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-6">
            <h6 class="mb-3">Informações Gerais</h6>
            <table class="table table-bordered">
                <tr>
                    <th>Data</th>
                    <td><?php echo date('d/m/Y H:i', strtotime($venda['data_venda'])); ?></td>
                </tr>
                <tr>
                    <th>Cliente</th>
                    <td><?php echo htmlspecialchars($venda['cliente_nome']); ?></td>
                </tr>
                <tr>
                    <th>Vendedor</th>
                    <td><?php echo htmlspecialchars($venda['vendedor_nome']); ?></td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        <span class="badge bg-<?php 
                            echo $venda['status'] == 'concluida' ? 'success' : 
                                ($venda['status'] == 'pendente' ? 'warning' : 'danger'); 
                        ?>">
                            <?php echo ucfirst($venda['status']); ?>
                        </span>
                    </td>
                </tr>
            </table>
        </div>
        <div class="col-md-6">
            <h6 class="mb-3">Informações de Pagamento</h6>
            <table class="table table-bordered">
                <tr>
                    <th>Forma de Pagamento</th>
                    <td><?php echo htmlspecialchars($venda['forma_pagamento']); ?></td>
                </tr>
                <tr>
                    <th>Valor Total</th>
                    <td>R$ <?php echo number_format($venda['valor_total'], 2, ',', '.'); ?></td>
                </tr>
                <?php if ($venda['desconto'] > 0): ?>
                    <tr>
                        <th>Desconto</th>
                        <td>R$ <?php echo number_format($venda['desconto'], 2, ',', '.'); ?></td>
                    </tr>
                <?php endif; ?>
                <?php if (!empty($venda['observacoes'])): ?>
                    <tr>
                        <th>Observações</th>
                        <td><?php echo nl2br(htmlspecialchars($venda['observacoes'])); ?></td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-12">
            <h6 class="mb-3">Itens da Venda</h6>
            <div class="table-responsive">
                <?php echo $venda['itens']; ?>
            </div>
        </div>
    </div>
</div>

<?php
} catch (Exception $e) {
    echo "Erro ao carregar detalhes: " . $e->getMessage();
}
?>