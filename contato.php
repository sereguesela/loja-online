<?php
$pageTitle = 'Contato';
require_once 'includes/functions.php';
require_once 'includes/header.php';

// Processa envio do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $errors = [];
        
        // Validações
        if (strlen($name) < 3) {
            $errors[] = 'O nome deve ter pelo menos 3 caracteres.';
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email inválido.';
        }
        
        if (strlen($subject) < 3) {
            $errors[] = 'O assunto deve ter pelo menos 3 caracteres.';
        }
        
        if (strlen($message) < 10) {
            $errors[] = 'A mensagem deve ter pelo menos 10 caracteres.';
        }
        
        if (empty($errors)) {
            // Em produção, enviar email usando PHPMailer ou similar
            // Por enquanto, apenas salva no banco de dados
            $stmt = $pdo->prepare("
                INSERT INTO contact_messages (name, email, subject, message, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            if ($stmt->execute([$name, $email, $subject, $message])) {
                setFlashMessage('success', 'Mensagem enviada com sucesso! Em breve entraremos em contato.');
                redirect(BASE_URL . '/contato.php');
            } else {
                $errors[] = 'Erro ao enviar mensagem. Tente novamente.';
            }
        }
        
        if (!empty($errors)) {
            setFlashMessage('danger', $errors[0]);
        }
    } else {
        setFlashMessage('danger', 'Token de segurança inválido.');
    }
}
?>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Envie sua Mensagem</h4>
                
                                <form action="<?= url('contato.php') ?>" method="post">                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Nome</label>
                        <input type="text" class="form-control" id="name" name="name" required minlength="3" 
                               value="<?= h($_POST['name'] ?? '') ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required 
                               value="<?= h($_POST['email'] ?? '') ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="subject" class="form-label">Assunto</label>
                        <input type="text" class="form-control" id="subject" name="subject" required minlength="3" 
                               value="<?= h($_POST['subject'] ?? '') ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="message" class="form-label">Mensagem</label>
                        <textarea class="form-control" id="message" name="message" rows="5" required minlength="10"><?= h($_POST['message'] ?? '') ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Enviar Mensagem
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-body">
                <h4 class="card-title mb-4">Informações de Contato</h4>
                
                <div class="d-flex mb-3">
                    <div class="flex-shrink-0">
                        <i class="fas fa-map-marker-alt fa-2x text-primary"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h5>Endereço</h5>
                        <p class="mb-0">
                            Rua Exemplo, 123<br>
                            Bairro Centro<br>
                            São Paulo - SP<br>
                            CEP: 01234-567
                        </p>
                    </div>
                </div>
                
                <div class="d-flex mb-3">
                    <div class="flex-shrink-0">
                        <i class="fas fa-phone fa-2x text-primary"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h5>Telefone</h5>
                        <p class="mb-0">
                            <a href="tel:+551199999999" class="text-decoration-none">
                                (11) 9999-9999
                            </a>
                        </p>
                    </div>
                </div>
                
                <div class="d-flex mb-3">
                    <div class="flex-shrink-0">
                        <i class="fas fa-envelope fa-2x text-primary"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h5>Email</h5>
                        <p class="mb-0">
                            <a href="mailto:contato@exemplo.com" class="text-decoration-none">
                                contato@exemplo.com
                            </a>
                        </p>
                    </div>
                </div>
                
                <div class="d-flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-clock fa-2x text-primary"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h5>Horário de Atendimento</h5>
                        <p class="mb-0">
                            Segunda a Sexta: 9h às 18h<br>
                            Sábado: 9h às 13h
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Redes Sociais</h4>
                
                <div class="d-flex justify-content-around">
                    <a href="#" class="text-decoration-none" target="_blank">
                        <i class="fab fa-facebook fa-2x text-primary"></i>
                    </a>
                    <a href="#" class="text-decoration-none" target="_blank">
                        <i class="fab fa-instagram fa-2x text-danger"></i>
                    </a>
                    <a href="#" class="text-decoration-none" target="_blank">
                        <i class="fab fa-twitter fa-2x text-info"></i>
                    </a>
                    <a href="#" class="text-decoration-none" target="_blank">
                        <i class="fab fa-whatsapp fa-2x text-success"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 