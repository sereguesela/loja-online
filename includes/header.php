<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? h($pageTitle) . ' - ' : '' ?>Loja Online</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- CSS personalizado -->
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?= BASE_URL ?>/"><i class="fas fa-shopping-cart me-2"></i>Loja Online</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/produtos.php">Produtos</a>
                    </li>
                    <?php if (isAdmin()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            Administração
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/produtos.php">Produtos</a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/categorias.php">Categorias</a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/pedidos.php">Pedidos</a></li>
                        </ul>
                    </li>
                    <?php elseif (isLoggedIn()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            Minha Conta
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>/cliente/dashboard.php">Painel</a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>/cliente/pedidos.php">Meus Pedidos</a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>/cliente/mensagens.php">Mensagens</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <form class="d-flex me-2" action="<?= BASE_URL ?>/produtos.php" method="get">
                    <input class="form-control me-2" type="search" name="q" placeholder="Buscar produtos...">
                    <button class="btn btn-light" type="submit">Buscar</button>
                </form>
                
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i>Sair
                        </a>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/registro.php">
                            <i class="fas fa-user-plus me-1"></i>Registro
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Mensagens Flash -->
    <div class="container mt-3">
        <?php
        $flash = getFlashMessage();
        if ($flash): ?>
            <div class="alert alert-<?= h($flash['type']) ?> alert-dismissible fade show">
                <?= h($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Conteúdo Principal -->
    <main class="container mt-4 mb-5"> 