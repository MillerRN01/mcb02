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
$foto = $_SESSION['foto'] ?? 'https://via.placeholder.com/100'; // Foto padrão se não houver
$email = $_SESSION['email'];
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendedores</title>
    <link rel="shortcut icon" href="uploades/fotos/icone.png" type="image/x-icon">
    <link rel="stylesheet" href="global.css">
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
                <img src="<?php echo htmlspecialchars($foto); ?>" class="card-img-top rounded-circle mb-2" alt="Foto do Usuário" style="width: 80px; height: 80px; object-fit: cover;">
                <h5 class="card-title"><?php echo htmlspecialchars($usuario); ?></h5>
                <p class="card-text"><?php echo htmlspecialchars($email); ?></p>
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

        <!-- Seção Gestão (adm) -->
        <?php if ($dante === 'admin'): ?>
            <div class="sidebar-header">
                <h5 class="mb-0"><i class="bi bi-gear"></i> Gestão</h5>
            </div>
            <ul class="sidebar-list list-unstyled">
                <li><a href="consulta_vendas.php"><i class="bi bi-search"></i> Consulta Vendas</a></li>
                <li><a href="estoque.php"><i class="bi bi-box"></i> Consultar estoque</a></li>
                <li><a href="caixa.php"><i class="bi bi-cash-stack"></i> Controle de caixa</a></li>
                <li><a href="fiado.php"><i class="bi bi-credit-card"></i> Fiado</a></li>
                <li><a href="financeiro.php"><i class="bi bi-wallet"></i> Financeiro</a></li>
            </ul>

            <!-- Seção Relatório -->
            <div class="sidebar-header">
                <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Relatório</h5>
            </div>
            <ul class="sidebar-list list-unstyled">
                <li><a href="relatorios.php"><i class="bi bi-bar-chart"></i> Relatórios</a></li>
                <li><a href="relatorios_consolidados.php"><i class="bi bi-pie-chart"></i> Relatórios consolidados</a></li>
            </ul>
        <?php endif; ?>

        <!-- Seção Configuração -->
        <div class="sidebar-header">
            <h5 class="mb-0"><i class="bi bi-gear"></i> Preferências</h5>
        </div>
        <ul class="sidebar-list list-unstyled">
            <li><a href="configuracoes.php"><i class="bi bi-gear"></i> Configurações</a></li>
            <li><a href="ajuda.php"><i class="bi bi-question-circle"></i> Ajuda</a></li>
        </ul>
    </div>
    
    <!-- Resto do conteúdo da página -->
    <div class="container mt-4">
        <h1>Bem-vindo, <?php echo htmlspecialchars($usuario); ?>!</h1>
        <!-- Adicione aqui o conteúdo específico da página -->
    </div>

</body>
</html>
