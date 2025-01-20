<?php
require_once 'conexao_db.php';
session_start();
// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'];
    $senha = $_POST['senha'];

    try {
        // Preparar a consulta
        $stmt = $pdo->prepare("SELECT id, senha, email, foto, dante FROM login WHERE usuario = :usuario");
        $stmt->bindParam(':usuario', $usuario, PDO::PARAM_STR);
        $stmt->execute();

        // Verifica se o usuário existe
        if ($stmt->rowCount() === 1) {
            // Obtém os dados do usuário
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stored_password = $row['senha']; // A senha armazenada no banco

            // Verifica se a senha é válida
            if (password_verify($senha, $stored_password)) {
                // Login bem-sucedido, configurar as variáveis de sessão
                $_SESSION['dante'] = $row['dante'];  // Armazena o papel do usuário (por exemplo, 'admin', 'funcionario')
                $_SESSION['usuario'] = $usuario;     // Armazena o nome do usuário
                $_SESSION['email'] = $row['email'];  // Armazena o email do usuário
                $_SESSION['logado'] = true;          // Marca o usuário como logado
                $_SESSION['foto'] = $row['foto'];    // Armazena a foto do usuário
            
                // Redireciona para a página inicial
                header('Location: home.php');
                exit;
            } else {
                // Senha inválida
                $error_message = "Senha inválida!";
            }
        } else {
            // Usuário não encontrado
            $error_message = "Usuário não encontrado!";
        }
    } catch (PDOException $e) {
        $error_message = "Erro ao verificar login: " . $e->getMessage();
    }
}
?><!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
</html>
<?php
require_once 'conexao_db.php';
session_start();

// Configurações de segurança para a sessão
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);

// Headers de segurança
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");

// Constantes para mensagens
const ERROR_INVALID_PASSWORD = "Senha inválida!";
const ERROR_USER_NOT_FOUND = "Usuário não encontrado!";
const ERROR_TOO_MANY_ATTEMPTS = "Muitas tentativas de login. Por favor, aguarde alguns minutos.";
const ERROR_GENERIC = "Ocorreu um erro ao processar sua solicitação. Tente novamente mais tarde.";

$error_message = "";
$old_username = "";

// Função para verificar tentativas de login
function checkLoginAttempts($ip) {
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['first_attempt_time'] = time();
    }

    if ($_SESSION['login_attempts'] >= 5) {
        $time_diff = time() - $_SESSION['first_attempt_time'];
        if ($time_diff < 300) { // 5 minutos de bloqueio
            return false;
        }
        // Reset após 5 minutos
        $_SESSION['login_attempts'] = 0;
        $_SESSION['first_attempt_time'] = time();
    }
    return true;
}

// Função para sanitizar inputs
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = ERROR_GENERIC;
    } else {
        // Verificar tentativas de login
        if (!checkLoginAttempts($_SERVER['REMOTE_ADDR'])) {
            $error_message = ERROR_TOO_MANY_ATTEMPTS;
        } else {
            $usuario = sanitizeInput($_POST['usuario']);
            $senha = $_POST['senha'];
            $old_username = $usuario; // Manter o valor do usuário em caso de erro

            try {
                // Preparar a consulta
                $stmt = $pdo->prepare("SELECT id, senha, email, foto, dante FROM login WHERE usuario = :usuario");
                $stmt->bindParam(':usuario', $usuario, PDO::PARAM_STR);
                $stmt->execute();

                // Verifica se o usuário existe
                if ($stmt->rowCount() === 1) {
                    // Obtém os dados do usuário
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $stored_password = $row['senha'];

                    // Verifica se a senha é válida
                    if (password_verify($senha, $stored_password)) {
                        // Reset tentativas de login em caso de sucesso
                        $_SESSION['login_attempts'] = 0;

                        // Login bem-sucedido, configurar as variáveis de sessão
                        $_SESSION['dante'] = $row['dante'];
                        $_SESSION['usuario'] = $usuario;
                        $_SESSION['email'] = $row['email'];
                        $_SESSION['logado'] = true;
                        $_SESSION['foto'] = $row['foto'];
                        
                        // Regenerar ID da sessão por segurança
                        session_regenerate_id(true);

                        // Redireciona para a página inicial
                        header('Location: home.php');
                        exit;
                    } else {
                        $_SESSION['login_attempts']++;
                        $error_message = ERROR_INVALID_PASSWORD;
                    }
                } else {
                    $_SESSION['login_attempts']++;
                    $error_message = ERROR_USER_NOT_FOUND;
                }
            } catch (PDOException $e) {
                error_log("Erro de login: " . $e->getMessage());
                $error_message = ERROR_GENERIC;
            }
        }
    }
}

// Gerar CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tela de Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .login-container {
            max-width: 400px;
            margin: auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .btn-primary {
            width: 100%;
        }

        .register-link {
            text-align: center;
            margin-top: 15px;
        }

        .error-message {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .error-message i {
            margin-right: 10px;
        }

        .btn-primary:disabled {
            cursor: not-allowed;
        }

        .spinner-border {
            display: none;
            width: 1rem;
            height: 1rem;
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="login-container">
            <h2>Bem Vindo</h2>
            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            <form action="" method="post" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="mb-3">
                    <label for="usuario" class="form-label">Usuário</label>
                    <input type="text" class="form-control" id="usuario" name="usuario" 
                           value="<?php echo htmlspecialchars($old_username); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="senha" class="form-label">Senha</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="senha" name="senha" required>
                        <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                            <i class="bi bi-eye-slash" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <span class="spinner-border" role="status" id="loadingSpinner"></span>
                    <span id="buttonText">Entrar</span>
                </button>
            </form>

            <div class="register-link">
                <a href="tela_cadastro.php" class="text-decoration-none">Não tem uma conta? Cadastre-se aqui!</a>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('senha');
        const eyeIcon = document.getElementById('eyeIcon');

        togglePassword.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            eyeIcon.classList.toggle('bi-eye');
            eyeIcon.classList.toggle('bi-eye-slash');
        });

        // Form submission handling
        const loginForm = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');
        const loadingSpinner = document.getElementById('loadingSpinner');
        const buttonText = document.getElementById('buttonText');

        loginForm.addEventListener('submit', function(e) {
            // Disable button and show loading state
            submitBtn.disabled = true;
            loadingSpinner.style.display = 'inline-block';
            buttonText.textContent = 'Entrando...';
        });

        // Prevent multiple form submissions
        let formSubmitted = false;
        loginForm.addEventListener('submit', function(e) {
            if (formSubmitted) {
                e.preventDefault();
                return;
            }
            formSubmitted = true;
        });
    </script>
</body>
</html>
