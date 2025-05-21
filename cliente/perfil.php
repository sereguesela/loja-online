<?php
$pageTitle = 'Editar Perfil';
require_once 'auth.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

// Busca informações do usuário
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Processa atualização de dados
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $type = $_POST['type'] ?? '';
        $errors = [];
        
        if ($type === 'profile') {
            // Atualização de dados do perfil
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            
            if (strlen($username) < 3) {
                $errors[] = 'O nome de usuário deve ter pelo menos 3 caracteres.';
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Email inválido.';
            }
            
            // Verifica se o email já está em uso por outro usuário
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $_SESSION['user_id']]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'Este email já está em uso por outro usuário.';
            }
            
            if (empty($errors)) {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                if ($stmt->execute([$username, $email, $_SESSION['user_id']])) {
                    setFlashMessage('success', 'Dados atualizados com sucesso!');
                    redirect('/cliente/perfil.php');
                } else {
                    $errors[] = 'Erro ao atualizar dados.';
                }
            }
        } elseif ($type === 'password') {
            // Atualização de senha
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if (!password_verify($current_password, $user['password'])) {
                $errors[] = 'Senha atual incorreta.';
            }
            
            if (strlen($new_password) < 6) {
                $errors[] = 'A nova senha deve ter pelo menos 6 caracteres.';
            }
            
            if ($new_password !== $confirm_password) {
                $errors[] = 'As senhas não coincidem.';
            }
            
            if (empty($errors)) {
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                if ($stmt->execute([$hashed_password, $_SESSION['user_id']])) {
                    setFlashMessage('success', 'Senha atualizada com sucesso!');
                    redirect('/cliente/perfil.php');
                } else {
                    $errors[] = 'Erro ao atualizar senha.';
                }
            }
        }
        
        if (!empty($errors)) {
            setFlashMessage('danger', $errors[0]);
            redirect('/cliente/perfil.php');
        }
    } else {
        setFlashMessage('danger', 'Token de segurança inválido.');
        redirect('/cliente/perfil.php');
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <!-- Atualizar Dados -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Dados do Perfil</h5>
            </div>
            <div class="card-body">
                                <form action="<?= BASE_URL ?>/cliente/perfil.php" method="post">                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">                    <input type="hidden" name="type" value="profile">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Nome de Usuário</label>
                        <input type="text" class="form-control" id="username" name="username" required 
                               value="<?= h($user['username']) ?>" minlength="3">
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required 
                               value="<?= h($user['email']) ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Salvar Alterações
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Alterar Senha -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Alterar Senha</h5>
            </div>
            <div class="card-body">
                                <form action="<?= BASE_URL ?>/cliente/perfil.php" method="post">                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">                    <input type="hidden" name="type" value="password">
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Senha Atual</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Nova Senha</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirmar Nova Senha</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-key me-2"></i>Alterar Senha
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 