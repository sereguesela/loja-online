<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Verifica se está logado e é admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('danger', 'Acesso negado. Você precisa ser um administrador para acessar esta área.');
    redirect('/login.php');
}
?> 