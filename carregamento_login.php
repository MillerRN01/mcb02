<?php
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// Define o tempo máximo de carregamento
$tempo_carregamento = 2000; // 2 segundos
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Carregando sua sessão...</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --background-color: #f8f9fa;
            --text-color: #333;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: var(--background-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            text-align: center;
            transition: background-color 0.3s ease;
        }

        .container {
            background-color: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 90%;
        }

        .spinner {
            border: 8px solid #f3f3f3;
            border-top: 8px solid var(--primary-color);
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-text {
            margin-top: 1.5rem;
            font-size: 1.2rem;
            color: var(--text-color);
            font-weight: 500;
        }

        .progress-bar {
            width: 100%;
            height: 4px;
            background-color: #eee;
            border-radius: 4px;
            margin-top: 1.5rem;
            overflow: hidden;
        }

        .progress {
            width: 0%;
            height: 100%;
            background-color: var(--primary-color);
            border-radius: 4px;
            transition: width 2s ease-in-out;
        }

        .user-info {
            margin-top: 1rem;
            color: var(--text-color);
            font-size: 0.9rem;
        }

        .error-message {
            display: none;
            color: #e74c3c;
            margin-top: 1rem;
            font-size: 0.9rem;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --background-color: #1a1a1a;
                --text-color: #ffffff;
            }

            .container {
                background-color: #2d2d2d;
            }
        }

        /* Animação de fade out */
        .fade-out {
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="spinner"></div>
        <div class="loading-text">
            <span id="loading-message">Preparando seu ambiente...</span>
        </div>
        <div class="progress-bar">
            <div class="progress" id="progress"></div>
        </div>
        <div class="user-info">
            Bem-vindo(a), <?php echo htmlspecialchars($_SESSION['usuario_nome'] ?? 'Usuário'); ?>
        </div>
        <div class="error-message" id="error-message">
            <i class="fas fa-exclamation-circle"></i>
            Ocorreu um erro ao carregar. Tentando novamente...
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const messages = [
            'Preparando seu ambiente...',
            'Carregando suas preferências...',
            'Quase lá...'
        ];
        let currentMessage = 0;
        const loadingMessage = document.getElementById('loading-message');
        const progress = document.getElementById('progress');
        const container = document.querySelector('.container');
        const errorMessage = document.getElementById('error-message');

        // Atualiza as mensagens de carregamento
        const updateMessage = setInterval(() => {
            if (currentMessage < messages.length - 1) {
                currentMessage++;
                loadingMessage.textContent = messages[currentMessage];
            }
        }, <?php echo $tempo_carregamento / 3; ?>);

        // Atualiza a barra de progresso
        progress.style.width = '100%';

        // Função para redirecionar com fade out
        const redirectWithFadeOut = () => {
            clearInterval(updateMessage);
            container.classList.add('fade-out');
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 500);
        };

        // Tenta redirecionar após o tempo definido
        setTimeout(redirectWithFadeOut, <?php echo $tempo_carregamento; ?>);

        // Tratamento de erros
        window.onerror = function() {
            errorMessage.style.display = 'block';
            setTimeout(() => {
                errorMessage.style.display = 'none';
            }, 3000);
        };
    });

    // Previne que o usuário volte para a página anterior
    history.pushState(null, null, location.href);
    window.onpopstate = function () {
        history.go(1);
    };
    </script>
</body>
</html>