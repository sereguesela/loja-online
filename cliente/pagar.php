<?php
$pageTitle = 'Pagar Pedido';
require_once 'auth.php';
require_once '../includes/header.php';

// Busca informações do pedido
$order_id = intval($_GET['id'] ?? 0);

if (!$order_id) {
    setFlashMessage('danger', 'Pedido não encontrado.');
    redirect(BASE_URL . '/cliente/pedidos.php');
}

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? AND status = 'pendente'");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    setFlashMessage('danger', 'Pedido não encontrado, já foi pago ou você não tem permissão para acessá-lo.');
    redirect(BASE_URL . '/cliente/pedidos.php');
}

// Processa confirmação de pagamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        // Atualiza o status do pedido para 'aguardando_confirmacao'
        $stmt = $pdo->prepare("UPDATE orders SET status = 'aguardando_confirmacao' WHERE id = ? AND user_id = ?");
        
        if ($stmt->execute([$order_id, $_SESSION['user_id']])) {
            // Envia mensagem automática para o administrador
            $mensagem = 'Cliente confirmou o pagamento via PIX. Aguardando confirmação do administrador.';
            
            // Se foi enviado um comprovante
            if (isset($_FILES['comprovante']) && $_FILES['comprovante']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['comprovante'];
                $comprovante = uploadImage($file);
                
                if ($comprovante) {
                    $mensagem .= "\n\n<img src='" . BASE_URL . "/uploads/" . $comprovante . "' class='img-fluid rounded' style='max-width: 300px;' alt='Comprovante de Pagamento'>";
                } else {
                    setFlashMessage('warning', 'Não foi possível anexar o comprovante. Envie-o pelo chat do pedido.');
                }
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO messages (order_id, sender_id, receiver_id, content, timestamp) 
                SELECT ?, ?, 
                    (SELECT id FROM users WHERE is_admin = 1 LIMIT 1), 
                    ?, NOW()
            ");
            $stmt->execute([$order_id, $_SESSION['user_id'], $mensagem]);
            
            setFlashMessage('success', 'Pagamento informado com sucesso! Aguarde a confirmação do administrador.');
            redirect(BASE_URL . "/cliente/pedido.php?id=$order_id");
        } else {
            setFlashMessage('danger', 'Erro ao processar a confirmação de pagamento.');
        }
    } else {
        setFlashMessage('danger', 'Token de segurança inválido.');
    }
    redirect(BASE_URL . "/cliente/pagar.php?id=$order_id");
}

// Gera chave PIX aleatória (simulação)
$pix_key = md5(uniqid(rand(), true));
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Pagar Pedido #<?= $order['id'] ?></h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-7">
                        <h6>Resumo do Pedido</h6>
                        <p>
                            <strong>Total a Pagar:</strong><br>
                            <span class="fs-4"><?= formatPrice($order['total_amount']) ?></span>
                        </p>
                        
                        <div class="alert alert-info">
                            <h6 class="alert-heading">Como Pagar</h6>
                            <ol class="mb-0">
                                <li>Copie a chave PIX ao lado</li>
                                <li>Abra o app do seu banco</li>
                                <li>Escolha pagar com PIX usando "Chave PIX"</li>
                                <li>Cole a chave e confirme o valor exato</li>
                                <li>Faça um print/screenshot do comprovante</li>
                                <li>Anexe o comprovante abaixo e clique em "Confirmar Pagamento"</li>
                            </ol>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i>
                            Após confirmar o pagamento, aguarde a validação do administrador.
                        </div>
                        
                        <div class="alert alert-primary">
                            <h6 class="alert-heading">
                                <i class="fas fa-file-image me-2"></i>
                                Comprovante de Pagamento
                            </h6>
                            <p class="mb-0">
                                Para agilizar a confirmação do seu pagamento, anexe o comprovante abaixo. 
                                Se preferir, você também pode enviar depois pelo chat do pedido.
                            </p>
                        </div>
                        
                        <form action="<?= BASE_URL ?>/cliente/pagar.php?id=<?= $order['id'] ?>" method="post" enctype="multipart/form-data" class="mb-4">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            
                            <div class="mb-3">
                                <label for="comprovante" class="form-label">Anexar Comprovante</label>
                                <input type="file" class="form-control" id="comprovante" name="comprovante" accept="image/*">
                                <div class="form-text">
                                    Formatos aceitos: JPG, JPEG, PNG. Tamanho máximo: 5MB
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <a href="<?= BASE_URL ?>/cliente/pedido.php?id=<?= $order['id'] ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Voltar para o Pedido
                                </a>
                                
                                <button type="submit" class="btn btn-success" onclick="return confirm('Você confirma que já realizou o pagamento via PIX?')">
                                    <i class="fas fa-check me-2"></i>Confirmar Pagamento
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="col-md-5">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title">Chave PIX</h6>
                                <div class="pix-key bg-light p-3 rounded mb-3">
                                    <code id="pix-key" class="user-select-all"><?= $pix_key ?></code>
                                </div>
                                
                                <button class="btn btn-outline-primary w-100" onclick="copyPixKey()">
                                    <i class="fas fa-copy me-2"></i>Copiar Chave PIX
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyPixKey() {
    const pixKey = document.getElementById('pix-key');
    navigator.clipboard.writeText(pixKey.textContent).then(() => {
        alert('Chave PIX copiada para a área de transferência!');
    });
}
</script>

<?php require_once '../includes/footer.php'; ?> 