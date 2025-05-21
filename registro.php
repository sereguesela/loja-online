<?php
$pageTitle = 'Registro';
require_once 'includes/header.php';

// Se já estiver logado, redireciona
if (isLoggedIn()) {
    redirect(isAdmin() ? BASE_URL . '/admin/dashboard.php' : BASE_URL . '/cliente/dashboard.php');
}

// Processa o formulário de registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica o token CSRF
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('danger', 'Token de segurança inválido. Tente novamente.');
        redirect(BASE_URL . '/registro.php');
    }
    
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validações
    $errors = [];
    
    if (strlen($username) < 3) {
        $errors[] = 'O nome de usuário deve ter pelo menos 3 caracteres.';
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email inválido.';
    }
    
    if (strlen($password) < 6) {
        $errors[] = 'A senha deve ter pelo menos 6 caracteres.';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'As senhas não coincidem.';
    }
    
    // Verifica se o email já está em uso
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = 'Este email já está em uso.';
    }
    
    if (empty($errors)) {
        // Cria o usuário
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        if ($stmt->execute([$username, $email, $hashed_password])) {
            setFlashMessage('success', 'Registro realizado com sucesso! Faça login para continuar.');
            redirect(BASE_URL . '/login.php');
        } else {
            setFlashMessage('danger', 'Erro ao criar conta. Tente novamente.');
            redirect(BASE_URL . '/registro.php');
        }
    } else {
        // Se houver erros, mostra o primeiro erro
        setFlashMessage('danger', $errors[0]);
        redirect(BASE_URL . '/registro.php');
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white text-center">
                <h4 class="my-2"><i class="fas fa-user-plus me-2"></i>Registro</h4>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="<?= BASE_URL ?>/registro.php">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Nome de Usuário</label>
                        <input type="text" class="form-control" id="username" name="username" required minlength="3">
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Senha</label>
                        <input type="password" class="form-control" id="password" name="password" required minlength="6">
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirmar Senha</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">Registrar</button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                <p class="mb-0">Já tem uma conta? <a href="<?= BASE_URL ?>/login.php">Faça login</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 