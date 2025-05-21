<?php
session_start();

// Função para verificar se o usuário está logado
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Função para verificar se o usuário é admin
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

// Função para redirecionar para a área correta
function redirectToUserArea() {
    if (!isLoggedIn()) {
        redirect(BASE_URL . '/login.php');
    }
    
    if (isAdmin()) {
        redirect(BASE_URL . '/admin/dashboard.php');
    } else {
        redirect(BASE_URL . '/cliente/dashboard.php');
    }
}

// Função para formatar preço
function formatPrice($price) {
    return 'R$ ' . number_format($price, 2, ',', '.');
}

// Função para upload de imagem
function uploadImage($file) {
    $target_dir = __DIR__ . "/../uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $imageFileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $newFileName = uniqid() . '.' . $imageFileType;
    $target_file = $target_dir . $newFileName;
    
    // Verifica se é uma imagem real
    if(!getimagesize($file["tmp_name"])) {
        return false;
    }
    
    // Verifica o tamanho do arquivo (max 5MB)
    if ($file["size"] > 5000000) {
        return false;
    }
    
    // Permite apenas certos formatos de arquivo
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
        return false;
    }
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return $newFileName;
    }
    
    return false;
}

// Função para gerar token CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Função para verificar token CSRF
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        die('Token CSRF inválido');
    }
    return true;
}

// Função para escapar output HTML
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Função para redirecionar
function redirect($path) {
    // Se o caminho já incluir o BASE_URL, usa ele diretamente
    if (strpos($path, BASE_URL) === 0) {
        header("Location: $path");
        exit();
    }
    
    // Se o caminho for uma URL absoluta (ex: http://), usa diretamente
    if (preg_match('/^https?:\/\//', $path)) {
        header("Location: $path");
        exit();
    }
    
    // Remove a barra inicial se existir, pois o BASE_URL já inclui uma
    $path = ltrim($path, '/');
    header("Location: " . BASE_URL . "/$path");
    exit();
}

// Função para mostrar mensagens flash
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Retorna as informações de formatação do status do pedido
 */
function getOrderStatus($status) {
    $status_info = [
        'pendente' => [
            'label' => 'Pendente',
            'class' => 'warning'
        ],
        'aguardando_confirmacao' => [
            'label' => 'Aguardando Confirmação',
            'class' => 'info'
        ],
        'pago' => [
            'label' => 'Pago',
            'class' => 'success'
        ],
        'enviado' => [
            'label' => 'Enviado',
            'class' => 'info'
        ],
        'entregue' => [
            'label' => 'Entregue',
            'class' => 'primary'
        ],
        'cancelado' => [
            'label' => 'Cancelado',
            'class' => 'danger'
        ]
    ];
    
    return $status_info[$status] ?? [
        'label' => ucfirst($status),
        'class' => 'secondary'
    ];
}

// Função para gerar URLs
function url($path) {
    // Se o caminho já incluir o BASE_URL, retorna ele diretamente
    if (strpos($path, BASE_URL) === 0) {
        return $path;
    }
    
    // Se for uma URL absoluta, retorna diretamente
    if (preg_match('/^https?:\/\//', $path)) {
        return $path;
    }
    
    // Remove a barra inicial se existir
    $path = ltrim($path, '/');
    return BASE_URL . '/' . $path;
} 