<?php
require_once 'conexao.php';
session_start();

// Verifica autenticação
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

// Configurações iniciais
$dante = $_SESSION['dante'] ?? '';
$usuario = $_SESSION['usuario'] ?? '';
$foto = $_SESSION['foto'] ?? '';
$email = $_SESSION['email'] ?? '';
$message = '';
$messageType = '';

// Classe para gerenciar categorias
class CategoriaManager {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function getAllCategorias() {
        $sql = "SELECT c.*, 
                (SELECT COUNT(*) FROM produtos WHERE categoria_id = c.id) as produtos_count 
                FROM categorias c 
                ORDER BY nome ASC";
        return $this->conn->query($sql);
    }
    
    public function addCategoria($nome) {
        try {
            $nome = trim($nome);
            if (empty($nome)) {
                throw new Exception("Nome da categoria não pode estar vazio.");
            }
            
            // Verifica se já existe uma categoria com esse nome
            $stmt = $this->conn->prepare("SELECT id FROM categorias WHERE LOWER(nome) = LOWER(?)");
            $stmt->bind_param("s", $nome);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("Já existe uma categoria com este nome.");
            }
            
            $stmt = $this->conn->prepare("INSERT INTO categorias (nome, created_at) VALUES (?, NOW())");
            $stmt->bind_param("s", $nome);
            
            if (!$stmt->execute()) {
                throw new Exception("Erro ao adicionar categoria: " . $stmt->error);
            }
            
            return ["success" => true, "message" => "Categoria '$nome' adicionada com sucesso!"];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }
    
    public function updateCategoria($id, $nome) {
        try {
            $nome = trim($nome);
            if (empty($nome)) {
                throw new Exception("Nome da categoria não pode estar vazio.");
            }
            
            $stmt = $this->conn->prepare("UPDATE categorias SET nome = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $nome, $id);
            
            if (!$stmt->execute()) {
                throw new Exception("Erro ao atualizar categoria.");
            }
            
            return ["success" => true, "message" => "Categoria atualizada com sucesso!"];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }
    
    public function deleteCategoria($id) {
        try {
            // Verifica se existem produtos vinculados
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM produtos WHERE categoria_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if ($result['count'] > 0) {
                throw new Exception("Não é possível excluir esta categoria pois existem produtos vinculados a ela.");
            }
            
            $stmt = $this->conn->prepare("DELETE FROM categorias WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if (!$stmt->execute()) {
                throw new Exception("Erro ao excluir categoria.");
            }
            
            return ["success" => true, "message" => "Categoria excluída com sucesso!"];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }
}

// Instancia o gerenciador de categorias
$categoriaManager = new CategoriaManager($conn);

// Processamento de formulários
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $result = null;
    
    if (isset($_POST['add_category'])) {
        $result = $categoriaManager->addCategoria($_POST['categoria']);
    } elseif (isset($_POST['edit_id'])) {
        $result = $categoriaManager->updateCategoria($_POST['edit_id'], $_POST['categoria']);
    } elseif (isset($_POST['delete_id'])) {
        $result = $categoriaManager->deleteCategoria($_POST['delete_id']);
    }
    
    if ($result) {
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'danger';
        
        // Se for uma requisição AJAX, retorna JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode($result);
            exit;
        }
        
        // Se não for AJAX, redireciona com a mensagem
        header("Location: " . $_SERVER['PHP_SELF'] . "?message=" . urlencode($message) . "&type=" . $messageType);
        exit;
    }
}

// Recupera mensagens do GET
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
    $messageType = $_GET['type'] ?? 'info';
}

// Busca todas as categorias
$categorias = $categoriaManager->getAllCategorias();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="uploades/fotos/icone.png" type="image/x-icon">
    <link rel="stylesheet" href="global.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <title>Gerenciamento de Categorias</title>
    <style>
        .categoria-card {
            transition: transform 0.2s;
        }
        .categoria-card:hover {
            transform: translateY(-5px);
        }
        .categoria-stats {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        .custom-tooltip {
            position: relative;
            display: inline-block;
        }
        .custom-tooltip .tooltiptext {
            visibility: hidden;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s;
        }
        .custom-tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }
    </style>
</head>
<body>
    <!-- Navbar (mantido como está) -->
    
    <!-- Menu Lateral (mantido como está) -->

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Carregando...</span>
        </div>
    </div>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-tags"></i> Gerenciamento de Categorias</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="bi bi-plus-lg"></i> Nova Categoria
            </button>
        </div>

        <!-- Alertas -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Grid de Categorias -->
        <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
            <?php
            if ($categorias && $categorias->num_rows > 0) {
                while ($categoria = $categorias->fetch_assoc()) {
                    ?>
                    <div class="col">
                        <div class="card h-100 categoria-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h5 class="card-title">
                                        <i class="bi bi-tag"></i> 
                                        <?php echo htmlspecialchars($categoria["nome"]); ?>
                                    </h5>
                                    <div class="dropdown">
                                        <button class="btn btn-link" type="button" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="#" 
                                                   onclick="openEditModal(<?php echo $categoria['id']; ?>, '<?php echo htmlspecialchars($categoria['nome']); ?>')">
                                                    <i class="bi bi-pencil"></i> Editar
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="#" 
                                                   onclick="confirmDelete(<?php echo $categoria['id']; ?>, '<?php echo htmlspecialchars($categoria['nome']); ?>')">
                                                    <i class="bi bi-trash"></i> Excluir
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <p class="categoria-stats mt-2 mb-0">
                                    <i class="bi bi-box"></i> <?php echo $categoria['produtos_count']; ?> produtos
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Nenhuma categoria cadastrada.
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
    </div>

    <!-- Modal Adicionar Categoria -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle"></i> Nova Categoria
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addForm" method="POST" onsubmit="return handleSubmit(this, 'add')">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="categoria" class="form-label">Nome da Categoria</label>
                            <input type="text" class="form-control" id="categoria" name="categoria" required
                                   minlength="2" maxlength="50" pattern="[A-Za-zÀ-ÿ\s]+"
                                   title="Digite um nome válido (apenas letras e espaços)">
                            <div class="form-text">Digite um nome entre 2 e 50 caracteres.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="add_category" class="btn btn-primary">
                            <i class="bi bi-plus-lg"></i> Adicionar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Categoria -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil-square"></i> Editar Categoria
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editForm" method="POST" onsubmit="return handleSubmit(this, 'edit')">
                    <div class="modal-body">
                        <input type="hidden" name="edit_id" id="edit_id">
                        <div class="mb-3">
                            <label for="edit_categoria" class="form-label">Nome da Categoria</label>
                            <input type="text" class="form-control" id="edit_categoria" name="categoria" required
                                   minlength="2" maxlength="50" pattern="[A-Za-zÀ-ÿ\s]+"
                                   title="Digite um nome válido (apenas letras e espaços)">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Salvar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="global.js"></script>
    
    <script>
        // Função para mostrar loading
        function showLoading() {
            document.getElementById('loadingOverlay').style.display = 'flex';
        }

        // Função para esconder loading
        function hideLoading() {
            document.getElementById('loadingOverlay').style.display = 'none';
        }
            // Função para lidar com os formulários
    async function handleSubmit(form, type) {
        showLoading();
        
        try {
            const formData = new FormData(form);
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Sucesso!',
                    text: result.message,
                    timer: 2000
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: result.message
                });
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: 'Ocorreu um erro ao processar sua solicitação.'
            });
        } finally {
            hideLoading();
        }
        
        return false; // Previne o envio tradicional do formulário
    }

    // Função para confirmar exclusão
    function confirmDelete(id, nome) {
        Swal.fire({
            title: 'Confirmar exclusão',
            text: `Deseja realmente excluir a categoria "${nome}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sim, excluir!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="delete_id" value="${id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    // Validação de formulários
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const input = this.querySelector('input[name="categoria"]');
            if (input) {
                const value = input.value.trim();
                if (value.length < 2) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro de validação',
                        text: 'O nome da categoria deve ter pelo menos 2 caracteres.'
                    });
                }
            }
        });
    });

    // Pesquisa dinâmica de categorias
    document.getElementById('searchCategoria').addEventListener('input', function(e) {
        const searchText = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const categoria = row.querySelector('td:first-child').textContent.toLowerCase();
            row.style.display = categoria.includes(searchText) ? '' : 'none';
        });
    });

    // Ordenação de tabela
    document.querySelectorAll('th').forEach(header => {
        header.addEventListener('click', function() {
            const table = this.closest('table');
            const index = Array.from(this.parentElement.children).indexOf(this);
            const rows = Array.from(table.querySelectorAll('tbody tr'));
            const isAscending = this.classList.contains('asc');
            
            rows.sort((a, b) => {
                const aValue = a.children[index].textContent;
                const bValue = b.children[index].textContent;
                return isAscending ? 
                    bValue.localeCompare(aValue) : 
                    aValue.localeCompare(bValue);
            });
            
            table.querySelector('tbody').append(...rows);
            
            // Toggle sorting direction
            table.querySelectorAll('th').forEach(th => th.classList.remove('asc', 'desc'));
            this.classList.toggle('asc', !isAscending);
            this.classList.toggle('desc', isAscending);
        });
    });
</script>    