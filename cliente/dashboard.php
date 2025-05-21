<?php
$pageTitle = 'Minha Conta';
require_once 'auth.php';
require_once '../includes/header.php';

// Busca informações do usuário
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Busca pedidos recentes
$stmt = $pdo->prepare("
    SELECT * FROM orders 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$recent_orders = $stmt->fetchAll();

// Busca mensagens não lidas
$stmt = $pdo->prepare("
    SELECT m.*, u.username as sender_name, o.id as order_id 
    FROM messages m 
    JOIN users u ON m.sender_id = u.id 
    LEFT JOIN orders o ON m.order_id = o.id 
    WHERE m.receiver_id = ? AND m.is_read = 0 
    ORDER BY m.timestamp DESC 
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$unread_messages = $stmt->fetchAll();

// Conta totais
$stats = [
    'total_orders' => $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?")->execute([$_SESSION['user_id']]) ? $stmt->fetchColumn() : 0,
    'unread_messages' => count($unread_messages)
];
?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Meus Dados</h5>
            </div>
            <div class="card-body">
                <p>
                    <strong>Nome:</strong> <?= h($user['username']) ?><br>
                    <strong>Email:</strong> <?= h($user['email']) ?><br>
                    <strong>Membro desde:</strong> <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                </p>
                <div class="d-grid">
                    <a href="<?= BASE_URL ?>/cliente/perfil.php" class="btn btn-primary">
                        <i class="fas fa-user-edit me-2"></i>Editar Perfil
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8 mb-4">
        <div class="row">
            <div class="col-sm-6 mb-4">
                <div class="card border-primary h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3">
                                <i class="fas fa-shopping-cart fa-2x text-primary"></i>
                            </div>
                            <div>
                                <h6 class="card-title mb-1">Total de Pedidos</h6>
                                <h2 class="mb-0"><?= $stats['total_orders'] ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-primary">
                        <a href="<?= BASE_URL ?>/cliente/pedidos.php" class="text-primary text-decoration-none">
                            Ver todos os pedidos <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-sm-6 mb-4">
                <div class="card border-info h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3">
                                <i class="fas fa-envelope fa-2x text-info"></i>
                            </div>
                            <div>
                                <h6 class="card-title mb-1">Mensagens Não Lidas</h6>
                                <h2 class="mb-0"><?= $stats['unread_messages'] ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-info">
                        <a href="<?= BASE_URL ?>/cliente/mensagens.php" class="text-info text-decoration-none">
                            Ver todas as mensagens <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Pedidos Recentes</h5>
                <a href="<?= BASE_URL ?>/cliente/pedidos.php" class="btn btn-primary btn-sm">Ver Todos</a>
            </div>
            <div class="card-body">
                <?php if ($recent_orders): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Pedido</th>
                                <th>Data</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                            <tr>
                                <td>#<?= $order['id'] ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                                <td><?= formatPrice($order['total_amount']) ?></td>
                                <td>
                                    <span class="badge bg-<?= getOrderStatus($order['status'])['class'] ?>">
                                        <?= getOrderStatus($order['status'])['label'] ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= BASE_URL ?>/cliente/pedido.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted mb-0">Você ainda não fez nenhum pedido.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php if ($unread_messages): ?>
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Mensagens Não Lidas</h5>
                <a href="<?= BASE_URL ?>/cliente/mensagens.php" class="btn btn-primary btn-sm">Ver Todas</a>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <?php foreach ($unread_messages as $message): ?>
                    <a href="<?= BASE_URL ?>/cliente/mensagens.php?view=<?= $message['id'] ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">
                                <?= h($message['sender_name']) ?>
                                <?php if ($message['order_id']): ?>
                                <small class="text-muted">(Pedido #<?= $message['order_id'] ?>)</small>
                                <?php endif; ?>
                            </h6>
                            <small><?= date('d/m/Y H:i', strtotime($message['timestamp'])) ?></small>
                        </div>
                        <p class="mb-1 text-truncate"><?= h($message['content']) ?></p>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?> 