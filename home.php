<?php
require_once 'conexao.php';
session_start();

// Configurações de segurança para a sessão
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);

// Headers de segurança
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("Content-Security-Policy: default-src 'self' https://cdn.jsdelivr.net; img-src 'self' https: data:; style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; script-src 'self' https://cdn.jsdelivr.net;");

// Verifica se o usuário está logado e se a sessão não expirou
if (!isset($_SESSION['usuario']) || !isset($_SESSION['last_activity'])) {
    header("Location: index.php");
    exit();
}

// Verifica se a sessão expirou (30 minutos de inatividade)
if (time() - $_SESSION['last_activity'] > 1800) {
    session_unset();
    session_destroy();
    header("Location: index.php?expired=1");
    exit();
}

// Atualiza o timestamp da última atividade
$_SESSION['last_activity'] = time();

// Sanitiza os dados da sessão
$dante = htmlspecialchars($_SESSION['dante']);
$usuario = htmlspecialchars($_SESSION['usuario']);
$foto = htmlspecialchars($_SESSION['foto']);
$email = htmlspecialchars($_SESSION['email']);

// Função para verificar permissões
function checkPermission($required_role) {
    return $_SESSION['dante'] === $required_role;
}

// Define as seções do menu com base nas permissões
$menu_sections = [
    'cadastro' => [
        'title' => 'Cadastro',
        'icon' => 'person',
        'items' => [
            ['url' => 'categoria.php', 'icon' => 'list', 'text' => 'Categoria'],
            ['url' => 'cadastro_produto.php', 'icon' => 'box', 'text' => 'Produtos e Serviços'],
            ['url' => 'modificador.php', 'icon' => 'pencil-square', 'text' => 'Modificador'],
            ['url' => 'cadastro_cliente.php', 'icon' => 'person-check', 'text' => 'Clientes'],
            ['url' => 'cadastro_fornecedores.php', 'icon' => 'truck', 'text' => 'Fornecedor'],
            ['url' => 'vendedores.php', 'icon' => 'person-badge', 'text' => 'Vendedores']
        ]
    ]
];

// Adiciona seções administrativas se o usuário for admin
if ($dante === 'admin') {
    $menu_sections['gestao'] = [
        'title' => 'Gestão',
        'icon' => 'gear',
        'items' => [
            ['url' => 'consulta_vendas.php', 'icon' => 'search', 'text' => 'Consulta Vendas'],
            ['url' => 'estoque.php', 'icon' => 'box', 'text' => 'Consultar estoque'],
            ['url' => 'caixa.php', 'icon' => 'cash-stack', 'text' => 'Controle de caixa'],
            ['url' => 'fiado.php', 'icon' => 'credit-card', 'text' => 'Fiado'],
            ['url' => 'cadastro_funcionario.php', 'icon' => 'person-badge', 'text' => 'Cadastro de Funcionario'],
            ['url' => 'financeiro.php', 'icon' => 'wallet', 'text' => 'Financeiro']
        ]
    ];
    
    $menu_sections['relatorio'] = [
        'title' => 'Relatório',
        'icon' => 'bar-chart',
        'items' => [
            ['url' => 'relatorios.php', 'icon' => 'bar-chart', 'text' => 'Relatórios'],
            ['url' => 'relatorios_consolidados.php', 'icon' => 'pie-chart', 'text' => 'Relatórios consolidados']
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Sistema de gestão comercial">
    <title>MeuComerciodeBolso</title>
    <link rel="shortcut icon" href="uploades/fotos/icone.png" type="image/x-icon">
    <link rel="stylesheet" href="global.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    
    <style>
        .sidebar {
            transition: transform 0.3s ease-in-out;
            position: fixed;
            top: 56px;
            left: -250px;
            width: 250px;
            height: calc(100vh - 56px);
            background-color: #f8f9fa;
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar.active {
            transform: translateX(250px);
        }

        .sidebar-header {
            padding: 1rem 0;
            margin-top: 1rem;
            border-bottom: 1px solid #dee2e6;
        }

        .sidebar-list li a {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            color: #333;
            text-decoration: none;
            transition: background-color 0.2s;
        }

        .sidebar-list li a:hover {
            background-color: #e9ecef;
        }

        .sidebar-list li a i {
            margin-right: 0.5rem;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                left: -100%;
            }
            
            .sidebar.active {
                transform: translateX(100%);
            }
        }

        .user-profile {
            transition: all 0.3s ease;
        }

        .user-profile:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .navbar {
            background-color: #0d6efd;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <button class="navbar-toggler border-0" type="button" id="menuToggle" aria-label="Toggle navigation">
                <i class="bi bi-list text-white fs-2"></i>
            </button>
            <span class="navbar-brand ms-2">MeuComerciodeBolso</span>
            <div class="ms-auto d-flex align-items-center">
                <span class="text-white me-3 d-none d-md-block"><?php echo $usuario; ?></span>
            </div>
        </div>
    </nav>

    <div class="sidebar p-3" id="sidebar">
        <div class="card mb-3 user-profile">
            <div class="card-body text-center">
                <img src="<?php echo $foto ?: 'assets/images/default-avatar.png'; ?>"
                     class="card-img-top rounded-circle mb-2"
                     alt="Foto do Usuário"
                     style="width: 80px; height: 80px; object-fit: cover;">
                <h5 class="card-title"><?php echo $usuario; ?></h5>
                <p class="card-text text-muted small"><?php echo $email; ?></p>
            </div>
        </div>

        <?php foreach ($menu_sections as $section): ?>
            <div class="sidebar-header">
                <h5 class="mb-0"><i class="bi bi-<?php echo $section['icon']; ?>"></i> <?php echo $section['title']; ?></h5>
            </div>
            <ul class="sidebar-list list-unstyled">
                <?php foreach ($section['items'] as $item): ?>
                    <li>
                        <a href="<?php echo $item['url']; ?>">
                            <i class="bi bi-<?php echo $item['icon']; ?>"></i>
                            <?php echo $item['text']; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>

        <div class="sidebar-header">
            <h5 class="mb-0"><i class="bi bi-gear"></i> Preferencias</h5>
        </div>
        <ul class="sidebar-list list-unstyled">
            <li><a href="configuracoes.php"><i class="bi bi-gear"></i> Configurações</a></li>
            <li><a href="ajuda.php"><i class="bi bi-question-circle"></i> Ajuda</a></li>
            <li><a href="logout.php" id="logoutBtn"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');
            const logoutBtn = document.getElementById('logoutBtn');

            // Toggle menu
            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });

            // Fechar menu ao clicar fora
            document.addEventListener('click', function(event) {
                if (!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                    sidebar.classList.remove('active');
                }
            });

            // Confirmação de logout
            logoutBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (confirm('Tem certeza que deseja sair?')) {
                    window.location.href = this.href;
                }
            });

            // Detectar inatividade
            let inactivityTimer;
            function resetInactivityTimer() {
                clearTimeout(inactivityTimer);
                inactivityTimer = setTimeout(() => {
                    alert('Sua sessão irá expirar em 1 minuto por inatividade.');
                    setTimeout(() => {
                        window.location.href = 'logout.php';
                    }, 60000);
                }, 1740000); // 29 minutos
            }

            // Eventos para resetar o timer de inatividade
            ['mousemove', 'keypress', 'click', 'touchstart'].forEach(event => {
                document.addEventListener(event, resetInactivityTimer);
            });
            
            resetInactivityTimer();
        });
    </script>
</body>
</html>