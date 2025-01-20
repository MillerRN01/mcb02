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
header('Content-Type: application/json; charset=utf-8');

// Verifica se o usuário está logado e se a sessão não expirou
if (!isset($_SESSION['usuario']) || !isset($_SESSION['last_activity'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit();
}

// Verifica se a sessão expirou (30 minutos)
if (time() - $_SESSION['last_activity'] > 1800) {
    session_unset();
    session_destroy();
    http_response_code(440);
    echo json_encode(['error' => 'Sessão expirada']);
    exit();
}

// Atualiza o timestamp da última atividade
$_SESSION['last_activity'] = time();

try {
    // Prepara a query base
    $query = "SELECT id, nome, email, telefone, status, tipo, data_cadastro, ultima_atualizacao 
              FROM clientes 
              WHERE 1=1";
    $params = array();

    // Processa parâmetros de busca
    if (isset($_GET['search'])) {
        $search = trim($_GET['search']);
        if (!empty($search)) {
            $query .= " AND (nome LIKE ? OR email LIKE ? OR telefone LIKE ?)";
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
    }

    // Filtro por status
    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $query .= " AND status = ?";
        $params[] = $_GET['status'];
    }

    // Filtro por tipo
    if (isset($_GET['tipo']) && !empty($_GET['tipo'])) {
        $query .= " AND tipo = ?";
        $params[] = $_GET['tipo'];
    }

    // Ordenação
    $orderBy = isset($_GET['order_by']) ? $_GET['order_by'] : 'nome';
    $orderDir = isset($_GET['order_dir']) && strtoupper($_GET['order_dir']) === 'DESC' ? 'DESC' : 'ASC';
    
    // Lista de campos permitidos para ordenação
    $allowedOrderFields = ['nome', 'email', 'status', 'tipo', 'data_cadastro'];
    
    if (in_array($orderBy, $allowedOrderFields)) {
        $query .= " ORDER BY " . $orderBy . " " . $orderDir;
    } else {
        $query .= " ORDER BY nome ASC";
    }

    // Paginação
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 10;
    $offset = ($page - 1) * $limit;

    // Adiciona LIMIT e OFFSET à query
    $query .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    // Prepara e executa a query
    $stmt = $conn->prepare($query);
    if ($params) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    // Conta total de registros (para paginação)
    $countQuery = "SELECT COUNT(*) as total FROM clientes WHERE 1=1";
    if (isset($search) && !empty($search)) {
        $countQuery .= " AND (nome LIKE ? OR email LIKE ? OR telefone LIKE ?)";
    }
    $countStmt = $conn->prepare($countQuery);
    if (isset($search) && !empty($search)) {
        $countStmt->bind_param('sss', $searchParam, $searchParam, $searchParam);
    }
    $countStmt->execute();
    $totalRows = $countStmt->get_result()->fetch_assoc()['total'];

    // Prepara a resposta
    $clientes = [];
    while ($row = $result->fetch_assoc()) {
        // Sanitiza os dados antes de enviar
        $cliente = array(
            'id' => (int)$row['id'],
            'nome' => htmlspecialchars($row['nome']),
            'email' => htmlspecialchars($row['email']),
            'telefone' => htmlspecialchars($row['telefone']),
            'status' => htmlspecialchars($row['status']),
            'tipo' => htmlspecialchars($row['tipo']),
            'data_cadastro' => $row['data_cadastro'],
            'ultima_atualizacao' => $row['ultima_atualizacao']
        );
        $clientes[] = $cliente;
    }

    // Monta resposta com metadados
    $response = array(
        'status' => 'success',
        'data' => array(
            'clientes' => $clientes,
            'pagination' => array(
                'total' => (int)$totalRows,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($totalRows / $limit)
            ),
            'filters' => array(
                'search' => $search ?? '',
                'status' => $_GET['status'] ?? '',
                'tipo' => $_GET['tipo'] ?? '',
                'order_by' => $orderBy,
                'order_dir' => $orderDir
            )
        )
    );

    echo json_encode($response);

} catch (Exception $e) {
    // Log do erro
    error_log("Erro na busca de clientes: " . $e->getMessage());
    
    // Retorna erro genérico para o cliente
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Ocorreu um erro ao processar sua solicitação'
    ]);
} finally {
    // Fecha as conexões
    if (isset($stmt)) $stmt->close();
    if (isset($countStmt)) $countStmt->close();
    if (isset($conn)) $conn->close();
}
?>