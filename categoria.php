<?php
include_once 'conexao.php';
session_start();

// Constantes para mensagens
define('MSG_ERRO_LOGIN', 'Você precisa estar logado para acessar esta página.');
define('MSG_SUCESSO_ADD', 'Nova categoria adicionada com sucesso!');
define('MSG_ERRO_ADD', 'Erro ao adicionar categoria.');
define('MSG_ERRO_NOME_INVALIDO', 'Por favor, insira um nome válido para a categoria.');
define('MSG_SUCESSO_EDIT', 'Categoria editada com sucesso!');
define('MSG_ERRO_EDIT', 'Erro ao editar categoria.');
define('MSG_ERRO_DADOS_INVALIDOS', 'Dados inválidos para edição.');
define('MSG_SUCESSO_DELETE', 'Categoria excluída com sucesso!');
define('MSG_ERRO_DELETE', 'Erro ao excluir categoria.');
define('MSG_ERRO_ID_INVALIDO', 'ID inválido.');

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

// Geração do token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$dante = $_SESSION['dante'];
$usuario = $_SESSION['usuario'];
$foto = $_SESSION['foto'];
$email = $_SESSION['email'];

$message = '';
$messageType = ''; // success, danger, warning, info

/**
 * Função para validar e sanitizar entrada
 */
function sanitizeInput($data) {
    return htmlspecialchars(trim($data));
}

/**
 * Função para verificar token CSRF
 */
function verificarCSRF() {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Erro de validação CSRF');
    }
}

try {
    // Consultar categorias
    $stmt = $conn->prepare("SELECT * FROM categorias ORDER BY nome ASC");
    $stmt->execute();
    $result = $stmt->get_result();
} catch (Exception $e) {
    $message = "Erro ao carregar categorias: " . $e->getMessage();
    $messageType = 'danger';
    $result = null;
}

// Adicionar nova categoria
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    verificarCSRF();
    $categoria = sanitizeInput($_POST['categoria']);

    if (!empty($categoria)) {
        try {
            $stmt = $conn->prepare("INSERT INTO categorias (nome) VALUES (?)");
            $stmt->bind_param("s", $categoria);

            if ($stmt->execute()) {
                $message = MSG_SUCESSO_ADD;
                $messageType = 'success';
            } else {
                throw new Exception(MSG_ERRO_ADD);
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = 'danger';
        }
    } else {
        $message = MSG_ERRO_NOME_INVALIDO;
        $messageType = 'warning';
    }

    header("Location: " . $_SERVER['PHP_SELF'] . "?message=" . urlencode($message) . "&type=" . urlencode($messageType));
    exit;
}

// Editar categoria
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_id'])) {
    verificarCSRF();
    $id = filter_var($_POST['edit_id'], FILTER_VALIDATE_INT);
    $categoria = sanitizeInput($_POST['categoria']);

    if ($id && !empty($categoria)) {
        try {
            $stmt = $conn->prepare("UPDATE categorias SET nome=? WHERE id=?");
            $stmt->bind_param("si", $categoria, $id);

            if ($stmt->execute()) {
                $message = MSG_SUCESSO_EDIT;
                $messageType = 'success';
            } else {
                throw new Exception(MSG_ERRO_EDIT);
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = 'danger';
        }
    } else {
        $message = MSG_ERRO_DADOS_INVALIDOS;
        $messageType = 'warning';
    }

    header("Location: " . $_SERVER['PHP_SELF'] . "?message=" . urlencode($message) . "&type=" . urlencode($messageType));
    exit;
}

// Excluir categoria
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    verificarCSRF();
    $id = filter_var($_POST['delete_id'], FILTER_VALIDATE_INT);

    if ($id) {
        try {
            // Primeiro verifica se existem produtos vinculados
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM produtos WHERE categoria_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            if ($row['count'] > 0) {
                throw new Exception("Não é possível excluir esta categoria pois existem produtos vinculados a ela.");
            }

            $stmt = $conn->prepare("DELETE FROM categorias WHERE id=?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $message = MSG_SUCESSO_DELETE;
                $messageType = 'success';
            } else {
                throw new Exception(MSG_ERRO_DELETE);
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = 'danger';
        }
    } else {
        $message = MSG_ERRO_ID_INVALIDO;
        $messageType = 'warning';
    }

    header("Location: " . $_SERVER['PHP_SELF'] . "?message=" . urlencode($message) . "&type=" . urlencode($messageType));
    exit;
}

// Exibir mensagem via GET
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
    $messageType = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : 'info';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="uploades/fotos/icone.png" type="image/x-icon">
    <link rel="stylesheet" href="global.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <title>Gerenciamento de Categorias</title>
</head>

<body>
    <!-- Navbar com o ícone de menu e o nome ao lado -->
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
        <div class="card mb-3" style="width: 100%;">
            <div class="card-body text-center">
                <img src="<?php echo $foto ? $foto : 'https://via.placeholder.com/100'; ?>"
                    class="card-img-top rounded-circle mb-2"
                    alt="Foto do Usuário"
                    style="width: 80px; height: 80px; object-fit: cover;">
                <h5 class="card-title"><?php echo htmlspecialchars($usuario); ?></h5>
                <p class="card-text"><?php echo htmlspecialchars($email); ?></p>
            </div>
        </div>

        <!-- Seção Cadastro -->
        <div class="sidebar-header">
            <h5 class="mb-0"><i class="bi bi-person"></i> Cadastro</h5>
        </div>
        <ul class="sidebar-list list-unstyled">
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
                <li><a href="cadastro_funcionario.php"><i class="bi bi-person-badge"></i>Cadastro de Funcionario</a></li>
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
            <h5 class="mb-0"><i class="bi bi-gear"></i> Preferencias</h5>
        </div>
        <ul class="sidebar-list list-unstyled">
            <li><a href="configuracoes.php"><i class="bi bi-gear"></i> Configurações</a></li>
            <li><a href="ajuda.php"><i class="bi bi-question-circle"></i> Ajuda</a></li>
            <li><a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </div>

    <div class="container mt-4">
        <h1>Gerenciamento de Categorias</h1>

        <!-- Exibição de mensagens -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Formulário para adicionar nova categoria -->
        <form method="POST" class="mb-4">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="input-group">
                <input type="text" name="categoria" class="form-control" placeholder="Nome da Categoria" 
                       required pattern="[A-Za-zÀ-ÿ0-9\s]{3,50}" 
                       title="O nome deve ter entre 3 e 50 caracteres e pode conter letras, números e espaços">
                <button class="btn btn-primary" type="submit" name="add_category">
                    <i class="bi bi-plus-circle"></i> Adicionar
                </button>
            </div>
        </form>

        <!-- Tabela de Categorias -->
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Categoria</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row["nome"]); ?></td>
                                <td>
                                    <button class="btn btn-warning btn-sm" 
                                            onclick="openEditModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['nome'], ENT_QUOTES); ?>')">
                                        <i class="bi bi-pencil"></i> Editar
                                    </button>
                                    <button class="btn btn-danger btn-sm" 
                                            onclick="openDeleteModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['nome'], ENT_QUOTES); ?>')">
                                        <i class="bi bi-trash"></i> Excluir
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2" class="text-center">Nenhuma categoria encontrada</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal para Editar Categoria -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="edit_id" id="edit_id">
                        <div class="mb-3">
                            <label for="edit_categoria" class="form-label">Nome da Categoria</label>
                            <input type="text" class="form-control" id="edit_categoria" name="categoria" 
                                   required pattern="[A-Za-zÀ-ÿ0-9\s]{3,50}" 
                                   title="O nome deve ter entre 3 e 50 caracteres e pode conter letras, números e espaços">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Salvar alterações
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Excluir Categoria -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel">
                        <i class="bi bi-exclamation-triangle"></i> Excluir Categoria
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="lead">Você tem certeza que deseja excluir esta categoria?</p>
                    <p class="text-muted">Esta ação não poderá ser desfeita.</p>
                    <p id="categoryNameToDelete" class="fw-bold"></p>
                </div>
                <div class="modal-footer">
                    <form id="deleteForm" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="delete_id" id="delete_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Confirmar Exclusão
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Função para validar o formulário antes de enviar
        function validateForm(formId) {
            const form = document.getElementById(formId);
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }

        // Função para abrir o modal de edição
        function openEditModal(id, nome) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_categoria').value = nome;
            const editModal = new bootstrap.Modal(document.getElementById('editModal'));
            editModal.show();
        }

        // Função para abrir o modal de exclusão
        function openDeleteModal(id, nome) {
            document.getElementById('delete_id').value = id;
            document.getElementById('categoryNameToDelete').textContent = `Categoria: ${nome}`;
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }

        // Adiciona validação aos formulários
        document.addEventListener('DOMContentLoaded', function() {
            // Validação para o formulário de adicionar
            const addForm = document.querySelector('form');
            addForm.addEventListener('submit', function(event) {
                validateForm(this);
            });

            // Validação para o formulário de edição
            const editForm = document.getElementById('editForm');
            editForm.addEventListener('submit', function(event) {
                validateForm('editForm');
            });

            // Auto-dismiss para alertas após 5 segundos
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });

        // Impede reenvio de formulário ao atualizar a página
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>
    <script src="global.js"></script>
</body>
</html>
                    <h5 class="modal-title" id="editModalLabel">Editar Categoria</h5>
                    <button type="button" class="btn-close" data