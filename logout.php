<?php
// Inicia a sessão se ainda não estiver iniciada
session_start();

// Registra o logout no log do sistema
if (isset($_SESSION['usuario'])) {
    error_log("Logout realizado: Usuário " . $_SESSION['usuario'] . " - IP: " . $_SERVER['REMOTE_ADDR'] . " - Data: " . date('Y-m-d H:i:s'));
}

// Remove o cookie da sessão
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Limpa todas as variáveis da sessão
$_SESSION = array();

// Destrói a sessão
session_destroy();

// Regenera o ID da sessão por segurança
session_regenerate_id(true);

// Define headers de segurança
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");

// Redireciona para a página de login com parâmetro de logout bem-sucedido
header('Location: index.php?logout=success');
exit();
?>