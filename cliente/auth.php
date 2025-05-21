<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Verifica se está logado e não é admin
if (!isLoggedIn()) {
    setFlashMessage('danger', 'Você precisa fazer login para acessar esta área.');
    redirect(BASE_URL . '/login.php');
} elseif (isAdmin()) {
    setFlashMessage('danger', 'Esta área é exclusiva para clientes.');
    redirect(BASE_URL . '/admin/dashboard.php');
}
?> 