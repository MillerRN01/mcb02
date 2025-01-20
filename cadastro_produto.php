<?php
// Configurações iniciais
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'logs/error.log');
date_default_timezone_set('America/Sao_Paulo');

class ProductManager {
    private $conn;
    private $userData;
    
    public function __construct() {
        $this->initializeConnection();
        $this->checkAuth();
        $this->userData = $this->getUserData();
    }
    
    private function initializeConnection() {
        try {
            require_once 'conexao.php';
            $this->conn = $conn;
            
            if ($this->conn->connect_error) {
                throw new Exception("Erro na conexão: " . $this->conn->connect_error);
            }
        } catch (Exception $e) {
            $this->logError($e->getMessage());
            die("Erro crítico no sistema. Por favor, tente novamente mais tarde.");
        }
    }
    
    private function checkAuth() {
        session_start();
        if (!isset($_SESSION['usuario'])) {
            header("Location: index.php");
            exit();
        }
    }
    
    private function getUserData() {
        return [
            'usuario' => $_SESSION['usuario'] ?? null,
            'dante' => $_SESSION['dante'] ?? null,
            'foto' => $_SESSION['foto'] ?? null,
            'email' => $_SESSION['email'] ?? null
        ];
    }

    public function getProducts($filters = []) {
        try {
            $searchTerm = $filters['search'] ?? '';
            $categoryFilter = $filters['category'] ?? '';
            $statusFilter = $filters['status'] ?? '';

            $sql = "SELECT p.id, p.codigo, p.nome AS produto, c.nome AS categoria, 
                           p.preco, p.estoque, p.status
                    FROM produtos p
                    JOIN categorias c ON p.categoria_id = c.id
                    WHERE (p.nome LIKE ? OR p.codigo LIKE ? OR c.nome LIKE ?)";

            $params = ["%$searchTerm%", "%$searchTerm%", "%$searchTerm%"];
            $types = "sss";

            if ($categoryFilter) {
                $sql .= " AND p.categoria_id = ?";
                $params[] = $categoryFilter;
                $types .= "s";
            }

            if ($statusFilter) {
                $sql .= " AND p.status = ?";
                $params[] = $statusFilter;
                $types .= "s";
            }

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Erro na preparação da consulta: " . $this->conn->error);
            }

            $stmt->bind_param($types, ...$params);
            
            if (!$stmt->execute()) {
                throw new Exception("Erro na execução da consulta: " . $stmt->error);
            }

            return $stmt->get_result();
        } catch (Exception $e) {
            $this->logError($e->getMessage());
            return false;
        }
    }

    public function getCategories() {
        try {
            $sql = "SELECT id, nome FROM categorias ORDER BY nome";
            $result = $this->conn->query($sql);
            
            if (!$result) {
                throw new Exception("Erro ao buscar categorias: " . $this->conn->error);
            }
            
            return $result;
        } catch (Exception $e) {
            $this->logError($e->getMessage());
            return false;
        }
    }

    public function getSuppliers() {
        try {
            $sql = "SELECT id_fornecedor, nome_fornecedor FROM fornecedor ORDER BY nome_fornecedor";
            $result = $this->conn->query($sql);
            
            if (!$result) {
                throw new Exception("Erro ao buscar fornecedores: " . $this->conn->error);
            }
            
            return $result;
        } catch (Exception $e) {
            $this->logError($e->getMessage());
            return false;
        }
    }

    public function isAdmin() {
        return $this->userData['dante'] === 'admin';
    }

    private function logError($message) {
        error_log(date('Y-m-d H:i:s') . " - " . $message . "\n", 3, "logs/error.log");
    }

    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }

    public function renderProductsTable($result) {
        if (!$result || $result->num_rows === 0) {
            return "<tr><td colspan='7' class='text-center'>Nenhum produto encontrado</td></tr>";
        }

        $html = '';
        while ($row = $result->fetch_assoc()) {
            $html .= "<tr>";
            $html .= "<td>" . htmlspecialchars($row['codigo']) . "</td>";
            $html .= "<td>" . htmlspecialchars($row['produto']) . "</td>";
            $html .= "<td>" . htmlspecialchars($row['categoria']) . "</td>";
            $html .= "<td>R$ " . number_format($row['preco'], 2, ',', '.') . "</td>";
            $html .= "<td>" . htmlspecialchars($row['estoque']) . "</td>";
            $html .= "<td>" . $this->getStatusBadge($row['status']) . "</td>";
            $html .= "<td class='text-center'>";
            $html .= "<button class='btn btn-warning btn-sm me-1' onclick='editProduct(" . $row['id'] . ")'><i class='fas fa-edit'></i></button>";
            $html .= "<button class='btn btn-danger btn-sm' onclick='deleteProduct(" . $row['id'] . ")'><i class='fas fa-trash'></i></button>";
            $html .= "</td>";
            $html .= "</tr>";
        }
        return $html;
    }

    private function getStatusBadge($status) {
        $badgeClass = $status === 'ativo' ? 'success' : 'danger';
        return "<span class='badge bg-{$badgeClass}'>" . ucfirst(htmlspecialchars($status)) . "</span>";
    }
}

// Inicialização
$manager = new ProductManager();

// Obtém os parâmetros de busca com validação
$filters = [
    'search' => filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING) ?? '',
    'category' => filter_input(INPUT_GET, 'category', FILTER_SANITIZE_STRING) ?? '',
    'status' => filter_input(INPUT_GET, 'status', FILTER_SANITIZE_STRING) ?? ''
];

// Busca os dados
$products = $manager->getProducts($filters);
$categories = $manager->getCategories();
$suppliers = $manager->getSuppliers();

// Inclui o template HTML
require_once 'templates/header.php';
?>

<!-- Conteúdo Principal -->
<div class="container shadow box">
    <main class="main-content">
        <div class="container mt-4">
            <!-- Cabeçalho -->
            <div class="content-header d-flex justify-content-between align-items-center">
                <h1>Gerenciamento de Produtos</h1>
                <div class="header-actions">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal">
                        <i class="fas fa-plus"></i> Novo Produto
                    </button>
                    <button class="btn btn-secondary" onclick="exportProducts()">
                        <i class="fas fa-file-export"></i> Exportar
                    </button>
                </div>
            </div>

            <!-- Filtros -->
            <?php require_once 'templates/product-filters.php'; ?>

            <!-- Tabela de Produtos -->
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Produto</th>
                            <th>Categoria</th>
                            <th>Preço</th>
                            <th>Estoque</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="productsTableBody">
                        <?php echo $manager->renderProductsTable($products); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Modal de Produto -->
<?php require_once 'templates/product-modal.php'; ?>

<?php require_once 'templates/footer.php'; ?>