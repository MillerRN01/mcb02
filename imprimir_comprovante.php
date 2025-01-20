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
    // Consulta dados da venda
    $stmt = $conn->prepare("
        SELECT v.*, 
               c.nome as cliente_nome,
               c.cpf as cliente_cpf,
               vd.nome as vendedor_nome,
               f.forma_pagamento
        FROM vendas v
        LEFT JOIN clientes c ON v.cliente_id = c.id
        LEFT JOIN vendedores vd ON v.vendedor_id = vd.id
        LEFT JOIN formas_pagamento f ON v.forma_pagamento_id = f.id
        WHERE v.id = ?
    ");
    
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $venda = $stmt->get_result()->fetch_assoc();

    // Consulta itens da venda
    $stmt = $conn->prepare("
        SELECT p.nome, iv.quantidade, iv.preco_unitario
        FROM itens_venda iv
        JOIN produtos p ON iv.produto_id = p.id
        WHERE iv.venda_id = ?
    ");
    
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $itens = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Comprovante de Venda #<?php echo $id; ?></title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            margin: 20px;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .divider {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
        }
        table {
            width: 100%;
            margin: 10px 0;
        }
        th, td {
            padding: 5px;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>MeuComerciodeBolso</h2>
        <p>Comprovante de Venda #<?php echo $id; ?></p>
    </div>

    <div class="divider"></div>

    <table>
        <tr>
            <th align="left">Data:</th>
            <td><?php echo date('d/m/Y H:i', strtotime($venda['data_venda'])); ?></td>
        </tr>
        <tr>
            <th align="left">Cliente:</th>
            <td><?php echo htmlspecialchars($venda['cliente_nome']); ?></td>
        </tr>
        <?php if (!empty($venda['cliente_cpf'])): ?>
        <tr>
            <th align="left">CPF:</th>
            <td><?php echo htmlspecialchars($venda['cliente_cpf']); ?></td>
        </tr>
        <?php endif; ?>
        <tr>
            <th align="left">Vendedor:</th>
            <td><?php echo htmlspecialchars($venda['vendedor_nome']); ?></td>
        </tr>
    </table>

    <div class="divider"></div>

    <table>
        <tr>
            <th align="left">Item</th>
            <th align="right">Qtd</th>
            <th align="right">Valor Un.</th>
            <th align="right">Total</th>
        </tr>
        <?php while ($item = $itens->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($item['nome']); ?></td>
            <td align="right"><?php echo $item['quantidade']; ?></td>
            <td align="right">R$ <?php echo number_format($item['preco_unitario'], 2, ',', '.'); ?></td>
            <td align="right">R$ <?php echo number_format($item['quantidade'] * $item['preco_unitario'], 2, ',', '.'); ?></td>
        </tr>
        <?php endwhile; ?>
    </table>

    <div class="divider"></div>

    <table>
        <?php if ($venda['desconto'] > 0): ?>
        <tr>
            <th align="left">Subtotal:</th>
            <td align="right">R$ <?php echo number_format($venda['valor_total'] + $venda['desconto'], 2, ',', '.'); ?></td>
        </tr>
        <tr>
            <th align="left">Desconto:</th>
            <td align="right">R$ <?php echo number_format($venda['desconto'], 2, ',', '.'); ?></td>
        </tr>
        <?php endif; ?>
        <tr>
            <th align="left">Total:</th>
            <td align="right">R$ <?php echo number_format($venda['valor_total'], 2, ',', '.'); ?></td>
        </tr>
        <tr>
            <th align="left">Forma de Pagamento:</th>
            <td align="right"><?php echo htmlspecialchars($venda['forma_pagamento']); ?></td>
        </tr>
    </table>

    <div class="divider"></div>

    <div class="footer">
        <p>Agradecemos a preferência!</p>
    </div>

    <button class="no-print" onclick="window.print()">Imprimir</button>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>

<?php
} catch (Exception $e) {
    echo "Erro ao gerar comprovante: " . $e->getMessage();
}
?>