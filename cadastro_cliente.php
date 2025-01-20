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
header("Content-Security-Policy: default-src 'self' https://cdn.jsdelivr.net; img-src 'self' https: data:; style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com;");

// Verifica autenticação e sessão
if (!isset($_SESSION['usuario']) || !isset($_SESSION['last_activity'])) {
    header("Location: index.php?error=session");
    exit();
}

// Verifica se a sessão expirou (30 minutos)
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

// Configurações de paginação
$items_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// Função para sanitizar inputs
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Função para validar CPF
function validaCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    if (strlen($cpf) != 11) {
        return false;
    }

    if (preg_match('/^(\d)\1+$/', $cpf)) {
        return false;
    }

    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }
    return true;
}

// Função para validar CNPJ
function validaCNPJ($cnpj) {
    $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
    
    if (strlen($cnpj) != 14) {
        return false;
    }

    if (preg_match('/^(\d)\1+$/', $cnpj)) {
        return false;
    }

    for ($i = 0, $j = 5, $soma = 0; $i < 12; $i++) {
        $soma += $cnpj[$i] * $j;
        $j = ($j == 2) ? 9 : $j - 1;
    }
    $resto = $soma % 11;
    if ($cnpj[12] != ($resto < 2 ? 0 : 11 - $resto)) {
        return false;
    }
    
    for ($i = 0, $j = 6, $soma = 0; $i < 13; $i++) {
        $soma += $cnpj[$i] * $j;
        $j = ($j == 2) ? 9 : $j - 1;
    }
    $resto = $soma % 11;
    return $cnpj[13] == ($resto < 2 ? 0 : 11 - $resto);
}

// Preparar consulta base para clientes
function prepareClientQuery($conn, $filters = []) {
    $where = ['1=1'];
    $params = [];
    $types = '';

    if (!empty($filters['search'])) {
        $where[] = "(nome_cliente LIKE ? OR email_cliente LIKE ? OR telefone_cliente LIKE ?)";
        $searchTerm = "%{$filters['search']}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= 'sss';
    }

    if (!empty($filters['status'])) {
        $where[] = "status = ?";
        $params[] = $filters['status'];
        $types .= 's';
    }

    if (!empty($filters['tipo'])) {
        $where[] = "tipo = ?";
        $params[] = $filters['tipo'];
        $types .= 's';
    }

    $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM clientes WHERE " . implode(' AND ', $where);

    // Ordenação
    $orderBy = isset($filters['order_by']) ? $filters['order_by'] : 'nome_cliente';
    $orderDir = isset($filters['order_dir']) ? $filters['order_dir'] : 'ASC';
    $allowedFields = ['nome_cliente', 'data_cadastro', 'ultima_compra'];
    
    if (in_array($orderBy, $allowedFields)) {
        $sql .= " ORDER BY {$orderBy} {$orderDir}";
    }

    // Paginação
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = $GLOBALS['items_per_page'];
    $params[] = $GLOBALS['offset'];
    $types .= 'ii';

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    return $stmt;
}

// Obter estatísticas dos clientes
function getClientStats($conn) {
    $stats = [
        'total' => 0,
        'novos' => 0,
        'ativos' => 0
    ];

    // Total de clientes
    $result = $conn->query("SELECT COUNT(*) as total FROM clientes");
    $stats['total'] = $result->fetch_assoc()['total'];

    // Novos clientes (este mês)
    $result = $conn->query("SELECT COUNT(*) as novos FROM clientes 
                           WHERE MONTH(data_cadastro) = MONTH(CURRENT_DATE()) 
                           AND YEAR(data_cadastro) = YEAR(CURRENT_DATE())");
    $stats['novos'] = $result->fetch_assoc()['novos'];

    // Clientes ativos (últimos 30 dias)
    $result = $conn->query("SELECT COUNT(DISTINCT cliente_id) as ativos 
                           FROM vendas 
                           WHERE data_venda >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)");
    $stats['ativos'] = $result->fetch_assoc()['ativos'];

    return $stats;
}

// Processar formulário se for POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verificar CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('Token de segurança inválido');
        }

        // Validar dados obrigatórios
        $required_fields = ['nome', 'email', 'telefone'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Campo {$field} é obrigatório");
            }
        }

        // Validar tipo de pessoa e documentos
        if ($_POST['tipo_pessoa'] === 'pf') {
            if (!validaCPF($_POST['cpf'])) {
                throw new Exception('CPF inválido');
            }
        } else {
            if (!validaCNPJ($_POST['cnpj'])) {
                throw new Exception('CNPJ inválido');
            }
        }

        // Preparar dados para inserção
        $data = array_map('sanitizeInput', $_POST);
        
        // Inserir ou atualizar cliente
        if (isset($_POST['id'])) {
            // Atualização
            // ... código de atualização ...
        } else {
            // Inserção
            // ... código de inserção ...
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;

    } catch (Exception $e) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

// Gerar novo token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Buscar clientes conforme filtros
$filters = [
    'search' => $_GET['search'] ?? '',
    'status' => $_GET['status'] ?? '',
    'tipo' => $_GET['tipo'] ?? '',
    'order_by' => $_GET['order_by'] ?? 'nome_cliente',
    'order_dir' => $_GET['order_dir'] ?? 'ASC'
];

$stmt = prepareClientQuery($conn, $filters);
$stmt->execute();
$clientes = $stmt->get_result();

// Obter total de registros para paginação
$total_results = $conn->query("SELECT FOUND_ROWS()")->fetch_array()[0];
$total_pages = ceil($total_results / $items_per_page);

// Obter estatísticas
$stats = getClientStats($conn);
?>