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

// Array com as seções de ajuda
$help_sections = [
    'inicio' => [
        'title' => 'Primeiros Passos',
        'content' => [
            'Como começar' => 'Guia básico para iniciar o uso do sistema.',
            'Navegação' => 'Aprenda a navegar pelo menu e suas funcionalidades.',
            'Configuração inicial' => 'Configure seu perfil e preferências do sistema.'
        ]
    ],
    'cadastros' => [
        'title' => 'Cadastros',
        'content' => [
            'Produtos' => 'Como cadastrar e gerenciar produtos.',
            'Clientes' => 'Gerenciamento de cadastro de clientes.',
            'Fornecedores' => 'Como cadastrar e gerenciar fornecedores.',
            'Categorias' => 'Organização de produtos por categorias.'
        ]
    ],
    'gestao' => [
        'title' => 'Gestão',
        'content' => [
            'Vendas' => 'Como realizar e consultar vendas.',
            'Estoque' => 'Controle e gestão de estoque.',
            'Financeiro' => 'Gestão financeira e relatórios.',
            'Funcionários' => 'Gerenciamento de equipe.'
        ]
    ]
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Central de Ajuda - MeuComerciodeBolso">
    <title>Ajuda - MeuComerciodeBolso</title>
    <link rel="shortcut icon" href="uploades/fotos/icone.png" type="image/x-icon">
    <link rel="stylesheet" href="global.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    
    <style>
        .help-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .help-section {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            padding: 1.5rem;
            transition: transform 0.2s;
        }

        .help-section:hover {
            transform: translateY(-2px);
        }

        .help-title {
            color: #0d6efd;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }

        .help-item {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .help-item:last-child {
            border-bottom: none;
        }

        .help-item:hover {
            background-color: #f8f9fa;
        }

        .help-item i {
            margin-right: 0.5rem;
            color: #0d6efd;
        }

        .search-box {
            margin-bottom: 2rem;
        }

        .contact-support {
            text-align: center;
            margin-top: 3rem;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 8px;
        }

        /* Estilos do sidebar herdados do home.php */
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

        .navbar {
            background-color: #0d6efd;
        }

        @media (max-width: 768px) {
            .help-container {
                margin-top: 1rem;
            }
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <button class="navbar-toggler border-0" type="button" id="menuToggle" aria-label="Toggle navigation">
                <i class="bi bi-list text-white fs-2"></i>
            </button>
            <span class="navbar-brand ms-2">Central de Ajuda</span>
        </div>
    </nav>

    <!-- Sidebar (mesmo do home.php) -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Conteúdo da Ajuda -->
    <div class="help-container">
        <!-- Barra de Pesquisa -->
        <div class="search-box">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control" id="searchHelp" placeholder="Pesquisar na ajuda...">
            </div>
        </div>

        <!-- Seções de Ajuda -->
        <?php foreach ($help_sections as $section => $data): ?>
            <div class="help-section">
                <h2 class="help-title">
                    <i class="bi bi-book"></i> <?php echo htmlspecialchars($data['title']); ?>
                </h2>
                <?php foreach ($data['content'] as $title => $description): ?>
                    <div class="help-item" data-bs-toggle="collapse" data-bs-target="#<?php echo $section . '-' . str_replace(' ', '', $title); ?>">
                        <i class="bi bi-chevron-right"></i>
                        <strong><?php echo htmlspecialchars($title); ?></strong>
                        <div class="collapse" id="<?php echo $section . '-' . str_replace(' ', '', $title); ?>">
                            <div class="mt-2">
                                <?php echo htmlspecialchars($description); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <!-- Contato com Suporte -->
        <div class="contact-support">
            <h3><i class="bi bi-headset"></i> Precisa de mais ajuda?</h3>
            <p>Nossa equipe de suporte está disponível para ajudar</p>
            <a href="mailto:suporte@meucommerciodebolso.com" class="btn btn-primary">
                <i class="bi bi-envelope"></i> Contatar Suporte
            </a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Funcionalidade de pesquisa
            const searchInput = document.getElementById('searchHelp');
            const helpItems = document.querySelectorAll('.help-item');

            searchInput.addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase();

                helpItems.forEach(item => {
                    const text = item.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });

            // Toggle do menu lateral
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');

            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });

            // Fechar menu ao clicar fora
            document.addEventListener('click', function(event) {
                if (!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                    sidebar.classList.remove('active');
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

            ['mousemove', 'keypress', 'click', 'touchstart'].forEach(event => {
                document.addEventListener(event, resetInactivityTimer);
            });
            
            resetInactivityTimer();
        });
    </script>
</body>
</html>