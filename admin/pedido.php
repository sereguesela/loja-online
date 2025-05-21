<?php
$pageTitle = 'Detalhes do Pedido';
require_once 'auth.php';
require_once '../includes/header.php';

// Busca o pedido
$order_id = intval($_GET['id'] ?? 0);

if (!$order_id) {
    setFlashMessage('danger', 'Pedido não encontrado.');
    redirect(BASE_URL . '/admin/pedidos.php');
}

// Busca detalhes do pedido
$stmt = $pdo->prepare("
    SELECT o.*, u.username, u.email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    setFlashMessage('danger', 'Pedido não encontrado.');
    redirect(BASE_URL . '/admin/pedidos.php');
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

// Processa formulários POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('danger', 'Token de segurança inválido.');
        redirect(BASE_URL . "/admin/pedido.php?id=$order_id");
    }
    
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        switch ($action) {
            case 'send_message':
                $content = trim($_POST['message'] ?? '');
                
                if (strlen($content) > 0) {
                    $stmt = $pdo->prepare("
                        INSERT INTO messages (order_id, sender_id, receiver_id, content, timestamp) 
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    
                    if ($stmt->execute([
                        $order_id,
                        $_SESSION['user_id'],
                        $order['user_id'],
                        $content
                    ])) {
                        setFlashMessage('success', 'Mensagem enviada com sucesso!');
                    } else {
                        setFlashMessage('danger', 'Erro ao enviar mensagem.');
                    }
                } else {
                    setFlashMessage('danger', 'A mensagem não pode estar vazia.');
                }
                redirect(BASE_URL . "/admin/pedido.php?id=$order_id");
                break;
                
            case 'aprovar_pagamento':
                $new_status = 'pago';
                $message = 'Pagamento aprovado! Seu pedido será processado em breve.';
                break;
                
            case 'reprovar_pagamento':
                $new_status = 'pendente';
                $message = 'Pagamento não confirmado. Por favor, verifique os dados e tente novamente.';
                break;
        }
        
        // Atualiza status se necessário
        if (isset($new_status)) {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            
            if ($stmt->execute([$new_status, $order_id])) {
                if (isset($message)) {
                    $stmt = $pdo->prepare("
                        INSERT INTO messages (order_id, sender_id, receiver_id, content, timestamp) 
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([
                        $order_id,
                        $_SESSION['user_id'],
                        $order['user_id'],
                        $message
                    ]);
                }
                
                setFlashMessage('success', 'Status do pedido atualizado com sucesso!');
                redirect(BASE_URL . "/admin/pedido.php?id=$order_id");
            } else {
                setFlashMessage('danger', 'Erro ao atualizar status do pedido.');
                redirect(BASE_URL . "/admin/pedido.php?id=$order_id");
            }
        }
    }
    // Atualização geral de status
    elseif (isset($_POST['status'])) {
        $new_status = $_POST['status'];
        $valid_statuses = ['pendente', 'aguardando_confirmacao', 'pago', 'enviado', 'entregue', 'cancelado'];
        
        if (in_array($new_status, $valid_statuses)) {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            
            if ($stmt->execute([$new_status, $order_id])) {
                setFlashMessage('success', 'Status do pedido atualizado com sucesso!');
                redirect(BASE_URL . "/admin/pedido.php?id=$order_id");
            } else {
                setFlashMessage('danger', 'Erro ao atualizar status do pedido.');
                redirect(BASE_URL . "/admin/pedido.php?id=$order_id");
            }
        }
    }
}

// Recarrega os dados do pedido após qualquer atualização
$stmt = $pdo->prepare("
    SELECT o.*, u.username, u.email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Pedido #<?= $order_id ?></h1>
        <a href="<?= BASE_URL ?>/admin/pedidos.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Voltar
        </a>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Informações do Cliente</h5>
                </div>
                <div class="card-body">
                    <p>
                        <strong>Nome:</strong> <?= h($order['username']) ?><br>
                        <strong>Email:</strong> <?= h($order['email']) ?><br>
                        <strong>Data do Pedido:</strong> <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                    </p>

                    <h6 class="mt-4">Status do Pedido</h6>
                    <?php if ($order['status'] === 'aguardando_confirmacao'): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Cliente informou que realizou o pagamento. Por favor, verifique e confirme.
                    </div>
                    
                    <div class="d-flex gap-2 mb-3">
                        <form method="post" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="action" value="aprovar_pagamento">
                            <button type="submit" class="btn btn-success" onclick="return confirm('Confirma que o pagamento foi recebido?')">
                                <i class="fas fa-check me-2"></i>Aprovar Pagamento
                            </button>
                        </form>
                        
                        <form method="post" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="action" value="reprovar_pagamento">
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Confirma que o pagamento NÃO foi recebido?')">
                                <i class="fas fa-times me-2"></i>Reprovar Pagamento
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                    
                    <form method="post" class="mb-3">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <div class="input-group">
                            <select class="form-select" name="status">
                                <?php
                                $status_options = [
                                    'pendente' => 'Pendente',
                                    'aguardando_confirmacao' => 'Aguardando Confirmação',
                                    'pago' => 'Pago',
                                    'enviado' => 'Enviado',
                                    'entregue' => 'Entregue',
                                    'cancelado' => 'Cancelado'
                                ];
                                foreach ($status_options as $value => $label):
                                ?>
                                <option value="<?= $value ?>" <?= $order['status'] === $value ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Atualizar Status
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card">
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
                                        <?php if ($item['image_filename']): ?>
                                        <img src="<?= BASE_URL ?>/uploads/<?= h($item['image_filename']) ?>" 
                                             alt="<?= h($item['title']) ?>" 
                                             class="img-thumbnail" style="max-height: 50px;">
                                        <?php endif; ?>
                                        <?= h($item['title']) ?>
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

        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Mensagens</h5>
                </div>
                <div class="card-body">
                    <div class="chat-messages mb-3" style="max-height: 400px; overflow-y: auto;">
                        <?php if ($messages): ?>
                            <?php foreach ($messages as $message): ?>
                            <div class="chat-message mb-3 <?= $message['is_admin'] ? 'text-end' : '' ?>">
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
                            <p class="text-muted mb-3">Nenhuma mensagem ainda.</p>
                        <?php endif; ?>
                    </div>
                    
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="send_message">
                        <div class="mb-3">
                            <label for="message" class="form-label">Nova Mensagem</label>
                            <textarea class="form-control" id="message" name="message" rows="3" required></textarea>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Enviar Mensagem
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.chat-messages {
    padding: 1rem;
    background-color: #f8f9fa;
    border-radius: 0.5rem;
}

.message-bubble {
    max-width: 80%;
    word-wrap: break-word;
    display: inline-block;
}

.chat-message.text-end .message-bubble {
    border-radius: 1rem 1rem 0 1rem;
}

.chat-message:not(.text-end) .message-bubble {
    border-radius: 1rem 1rem 1rem 0;
}
</style>

<?php require_once '../includes/footer.php'; ?> 