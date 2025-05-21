<?php
$pageTitle = 'Detalhes do Pedido';
require_once 'auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once '../includes/header.php';

// Busca informações do pedido
$order_id = intval($_GET['id'] ?? 0);

if (!$order_id) {
    setFlashMessage('danger', 'Pedido não encontrado.');
    redirect(BASE_URL . '/cliente/pedidos.php');
}

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    setFlashMessage('danger', 'Pedido não encontrado ou você não tem permissão para acessá-lo.');
    redirect(BASE_URL . '/cliente/pedidos.php');
}

// Busca itens do pedido
$stmt = $pdo->prepare("
    SELECT oi.*, p.title, p.image_filename 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();

// Processa nova mensagem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $content = trim($_POST['message'] ?? '');
        
        if (strlen($content) > 0) {
            $stmt = $pdo->prepare("
                INSERT INTO messages (order_id, sender_id, receiver_id, content, timestamp) 
                SELECT ?, ?, 
                    (SELECT id FROM users WHERE is_admin = 1 LIMIT 1), 
                    ?, NOW()
            ");
            
            if ($stmt->execute([$order_id, $_SESSION['user_id'], $content])) {
                setFlashMessage('success', 'Mensagem enviada com sucesso!');
            } else {
                setFlashMessage('danger', 'Erro ao enviar mensagem.');
            }
        } else {
            setFlashMessage('danger', 'A mensagem não pode estar vazia.');
        }
        
        // Certifique-se de que a função redirect esteja disponível
        if (!function_exists('redirect')) {
            require_once __DIR__ . '/../includes/functions.php';
        }
        
        // Redirecionar após processamento
        header("Location: " . BASE_URL . "/cliente/pedido.php?id=$order_id");
        exit();
    } else {
        setFlashMessage('danger', 'Token de segurança inválido.');
        
        // Certifique-se de que a função redirect esteja disponível
        if (!function_exists('redirect')) {
            require_once __DIR__ . '/../includes/functions.php';
        }
        
        // Redirecionar após processamento
        header("Location: " . BASE_URL . "/cliente/pedido.php?id=$order_id");
        exit();
    }
}

// Busca mensagens do pedido
$stmt = $pdo->prepare("
    SELECT m.*, u.username, u.is_admin 
    FROM messages m 
    JOIN users u ON m.sender_id = u.id 
    WHERE m.order_id = ? 
    ORDER BY m.timestamp ASC
");
$stmt->execute([$order_id]);
$messages = $stmt->fetchAll();

// Marca mensagens como lidas
$stmt = $pdo->prepare("
    UPDATE messages 
    SET is_read = 1 
    WHERE order_id = ? AND receiver_id = ? AND is_read = 0
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
?>

<div class="row">
    <div class="col-12 mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <h4>Pedido #<?= $order['id'] ?></h4>
                        <a href="<?= BASE_URL ?>/cliente/pedidos.php" class="btn btn-outline-secondary">                <i class="fas fa-arrow-left me-2"></i>Voltar            </a>
        </div>
    </div>
    
    <div class="col-md-8">
        <!-- Status do Pedido -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">Status do Pedido</h6>
                        <span class="badge bg-<?= getOrderStatus($order['status'])['class'] ?> fs-6">
                            <?= getOrderStatus($order['status'])['label'] ?>
                        </span>
                    </div>
                    <?php if ($order['status'] === 'pendente'): ?>
                                        <a href="<?= BASE_URL ?>/cliente/pagar.php?id=<?= $order['id'] ?>" class="btn btn-success">                        <i class="fas fa-money-bill me-2"></i>Pagar Agora                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Itens do Pedido -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Itens do Pedido</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Quantidade</th>
                                <th>Preço</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($item['image_filename']): ?>
                                        <img src="<?= BASE_URL ?>/uploads/<?= h($item['image_filename']) ?>" 
                                             alt="<?= h($item['title']) ?>" 
                                             class="img-thumbnail me-2" style="max-height: 50px;">
                                        <?php endif; ?>
                                        <?= h($item['title']) ?>
                                    </div>
                                </td>
                                <td><?= $item['quantity'] ?></td>
                                <td><?= formatPrice($item['price']) ?></td>
                                <td><?= formatPrice($item['price'] * $item['quantity']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                <td><strong><?= formatPrice($order['total_amount']) ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Informações do Pedido -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Informações do Pedido</h5>
            </div>
            <div class="card-body">
                <p class="mb-1">
                    <strong>Data do Pedido:</strong><br>
                    <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                </p>
                
                <?php if ($order['status'] === 'pago'): ?>
                <p class="mb-1">
                    <strong>Data do Pagamento:</strong><br>
                    <?= date('d/m/Y H:i', strtotime($order['paid_at'])) ?>
                </p>
                <?php endif; ?>
                
                <?php if ($order['status'] === 'enviado'): ?>
                <p class="mb-1">
                    <strong>Data de Envio:</strong><br>
                    <?= date('d/m/Y H:i', strtotime($order['shipped_at'])) ?>
                </p>
                
                <?php if ($order['tracking_code']): ?>
                <p class="mb-1">
                    <strong>Código de Rastreio:</strong><br>
                    <?= h($order['tracking_code']) ?>
                </p>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Chat -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Mensagens</h5>
            </div>
            <div class="card-body">
                <div class="chat-messages mb-3" style="max-height: 300px; overflow-y: auto;">
                    <?php if ($messages): ?>
                    <?php foreach ($messages as $message): ?>
                    <div class="chat-message mb-3 <?= $message['sender_id'] === $_SESSION['user_id'] ? 'text-end' : '' ?>">
                        <div class="d-inline-block text-start">
                            <div class="message-bubble p-2 rounded <?= $message['is_admin'] ? 'bg-primary text-white' : 'bg-light' ?>">
                                <?php
                                $content = $message['content'];
                                // Se a mensagem contiver uma tag de imagem, não escapa o HTML
                                if (strpos($content, '<img') !== false) {
                                    echo nl2br($content);
                                } else {
                                    echo nl2br(h($content));
                                }
                                ?>
                            </div>
                            <small class="text-muted">
                                <?= $message['is_admin'] ? 'Administrador' : h($message['username']) ?> • 
                                <?= date('d/m/Y H:i', strtotime($message['timestamp'])) ?>
                            </small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <p class="text-muted mb-0">Nenhuma mensagem ainda.</p>
                    <?php endif; ?>
                </div>
                
                                <form action="<?= BASE_URL ?>/cliente/pedido.php?id=<?= $order['id'] ?>" method="post">                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">                    <div class="mb-3">                        <label for="message" class="form-label">Nova Mensagem</label>                        <textarea class="form-control" id="message" name="message" rows="3" required></textarea>                    </div>                    <div class="d-grid">                        <button type="submit" class="btn btn-primary">                            <i class="fas fa-paper-plane me-2"></i>Enviar                        </button>                    </div>                </form>
            </div>
        </div>
    </div>
</div>

<style>
.message-bubble {
    max-width: 80%;
    word-wrap: break-word;
}
</style>

<?php require_once '../includes/footer.php'; ?> 