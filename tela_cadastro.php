<?php
require_once 'conexao_db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtendo e limpando os dados do formulário
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $usuario = trim($_POST['usuario']);
    $dante = trim($_POST['dante']);
    $senha = $_POST['senha'];

    // Verificando se todos os campos obrigatórios estão preenchidos
    if (empty($nome) || empty($email) || empty($dante) || empty($usuario) || empty($senha)) {
        echo "<div style='color: red;'>Todos os campos são obrigatórios!</div>";
        exit;
    }

    // Validando o formato do e-mail
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<div style='color: red;'>E-mail inválido!</div>";
        exit;
    }

    // Validação de senha (mínimo de 8 caracteres, 1 número e 1 caractere especial)
    if (strlen($senha) < 8 || !preg_match('/[0-9]/', $senha) || !preg_match('/[\W_]/', $senha)) {
        echo "<div style='color: red;'>A senha deve ter pelo menos 8 caracteres, 1 número e 1 caractere especial.</div>";
        exit;
    }

    // Hash da senha para armazenamento seguro
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    // Processamento da foto (se houver upload)
    $fotoHash = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $foto = $_FILES['foto'];
        $uploadDir = 'uploades/cadastro/';
        
        // Garantindo que o diretório de uploads exista
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Gerando um nome único para o arquivo
        $fotoHash = $uploadDir . uniqid() . '_' . basename($foto['name']);

        // Tipos de arquivo permitidos
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($foto['type'], $allowedTypes)) {
            echo "<div style='color: red;'>Tipo de arquivo não permitido. Apenas JPG, PNG e GIF são aceitos.</div>";
            exit;
        }

        // Verificando o tamanho do arquivo (máximo de 2MB)
        if ($foto['size'] > 2 * 1024 * 1024) {
            echo "<div style='color: red;'>O arquivo é muito grande. Tamanho máximo permitido é 2MB.</div>";
            exit;
        }

        // Movendo o arquivo para o diretório de uploads
        if (!move_uploaded_file($foto['tmp_name'], $fotoHash)) {
            echo "<div style='color: red;'>Erro ao fazer upload da foto.</div>";
            exit;
        }
    }

    // Verificando se o e-mail ou o nome de usuário já existem no banco de dados
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM login WHERE email = :email OR usuario = :usuario");
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':usuario', $usuario);
        $stmt->execute();
        if ($stmt->fetchColumn() > 0) {
            echo "<div style='color: red;'>E-mail ou usuário já estão em uso!</div>";
            exit;
        }

        // Inserindo os dados do novo usuário no banco de dados
        $stmt = $pdo->prepare("INSERT INTO login (nome, email, usuario, dante, foto, senha) VALUES (:nome, :email, :usuario, :dante, :foto, :senha)");
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':usuario', $usuario);
        $stmt->bindParam(':dante', $dante);
        $stmt->bindParam(':senha', $senhaHash);
        $stmt->bindParam(':foto', $fotoHash);
        $stmt->execute();

        // Redirecionando para a página inicial após o cadastro
        header("Location: carregamento_login.php");
        exit;
    } catch (PDOException $e) {
        echo "<div style='color: red;'>Erro ao cadastrar: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tela de Cadastro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa; /* Cor de fundo da página */
        }
        .register-container {
            max-width: 400px;
            padding: 30px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #6a11cb;
        }
        .login-link {
            text-align: center;
            margin-top: 15px;
        }
        .password-requirements {
            color: red;
            font-size: 12px;
            display: none; /* Oculta por padrão */
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>Cadastro</h2>
        <form id="registrationForm" method="POST" action="" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="nome" class="form-label">Nome</label>
                <input type="text" class="form-control" id="nome" name="nome" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">E-mail</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="usuario" class="form-label">Usuário</label>
                <input type="text" class="form-control" id="usuario" name="usuario" required>
            </div>
            <div class="mb-3">
                <label for="senha" class="form-label">Senha</label>
                <input type="password" class="form-control" id="senha" name="senha" required>
                <div class="password-requirements" id="passwordRequirements">
                    A senha deve ter pelo menos 8 caracteres, 1 número e 1 caractere especial.
                </div>
            </div>
            <div class="mb-3">
                <label for="tipo" class="form-label">Tipo de Usuário</label>
                <select class="form-select" id="dante" name="dante" required>
                    <option value="" disabled selected>Selecione um tipo</option>
                    <option value="admin">Admin</option>
                    <option value="funcionario">Funcionário</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="foto" class="form-label">Foto de Perfil</label>
                <input type="file" class="form-control" id="foto" name="foto" accept="image/*">
            </div>
            <button type="submit" class="btn btn-primary">Cadastrar</button>
        </form>
        <div class="login-link">
            <a href="index.php" class="text-decoration-none">Já tem uma conta? Faça login aqui!</a>
        </div>
    </div>

    <script>
        // Função para validar a senha
        document.getElementById('senha').addEventListener('input', function() {
            const senha = document.getElementById('senha').value;
            const passwordRequirements = document.getElementById('passwordRequirements');

            // Regex para validar a senha
            const passwordPattern = /^(?=.*[0-9])(?=.*[!@#$%^&*(),.?":{}|<>])[A-Za-z\d!@#$%^&*(),.?":{}|<>]{8,}$/;

            if (!passwordPattern.test(senha)) {
                passwordRequirements.style.display = 'block'; // Mostra o aviso
            } else {
                passwordRequirements.style.display = 'none'; // Oculta o aviso se a senha for válida
            }
        });

        // Validação do formulário ao ser enviado
        document.getElementById('registrationForm').addEventListener('submit', function(event) {
            const senha = document.getElementById('senha').value;
            const passwordRequirements = document.getElementById('passwordRequirements');

            // Verifica se a senha atende aos requisitos
            const passwordPattern = /^(?=.*[0-9])(?=.*[!@#$%^&*(),.?":{}|<>])[A-Za-z\d!@#$%^&*(),.?":{}|<>]{8,}$/;
            if (!passwordPattern.test(senha)) {
                event.preventDefault(); // Impede o envio do formulário
                passwordRequirements.style.display = 'block'; // Exibe o aviso de erro
            }
        });
    </script>
</body>
</html>
