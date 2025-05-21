<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

// Encerra a sessão
// session_start();
session_destroy();

// Remove o cookie de "lembrar-me" se existir
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Redireciona para a página inicial com mensagem
setFlashMessage('success', 'Você saiu com sucesso!');
header("Location: " . BASE_URL . '/');
exit();
?> 