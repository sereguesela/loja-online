<?php
$pageTitle = 'Painel Administrativo';
require_once 'auth.php';
require_once '../includes/header.php';

// Busca estatísticas
$stats = [
    'total_orders' => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'pending_orders' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pendente'")->fetchColumn(),
    'total_products' => $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'unread_messages' => $pdo->query("
        SELECT COUNT(*) 
        FROM messages 
        WHERE receiver_id = {$_SESSION['user_id']} 
        AND is_read = 0
    ")->fetchColumn()
];

// Busca últimos pedidos
$recent_orders = $pdo->query("
    SELECT o.*, u.username 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 5
")->fetchAll();

// Busca mensagens recentes
$recent_messages = $pdo->query("
    SELECT m.*, u.username as sender_name, o.id as order_id
    FROM messages m 
    JOIN users u ON m.sender_id = u.id 
    LEFT JOIN orders o ON m.order_id = o.id
    WHERE m.receiver_id = {$_SESSION['user_id']}
    ORDER BY m.timestamp DESC 
    LIMIT 5
")->fetchAll();
?>

<div class="container py-4">
    <h1 class="mb-4">Painel Administrativo</h1>

    <div class="row">
        <!-- Resumo -->
        <div class="col-md-12 mb-4">
            <div class="row">
                <div class="col-md-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <h5 class="card-title">Total de Pedidos</h5>
                            <h2 class="card-text"><?= $stats['total_orders'] ?></h2>
                            <p class="mb-0">Pedidos registrados</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h5 class="card-title">Pedidos Pendentes</h5>
                            <h2 class="card-text"><?= $stats['pending_orders'] ?></h2>
                            <p class="mb-0">Aguardando aprovação</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <h5 class="card-title">Total de Produtos</h5>
                            <h2 class="card-text"><?= $stats['total_products'] ?></h2>
                            <p class="mb-0">Produtos cadastrados</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <h5 class="card-title">Mensagens Novas</h5>
                            <h2 class="card-text"><?= $stats['unread_messages'] ?></h2>
                            <p class="mb-0">Não lidas</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Últimos Pedidos -->
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Últimos Pedidos</h5>
                    <a href="<?= BASE_URL ?>/admin/pedidos.php" class="btn btn-sm btn-primary">Ver Todos</a>
                </div>
                <div class="card-body">
                    <?php if ($recent_orders): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nº</th>
                                    <th>Cliente</th>
                                    <th>Data</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td>#<?= $order['id'] ?></td>
                                    <td><?= h($order['username']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($order['created_at'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= getOrderStatus($order['status'])['class'] ?>">
                                            <?= getOrderStatus($order['status'])['label'] ?>
                                        </span>
                                    </td>
                                    <td><?= formatPrice($order['total_amount']) ?></td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/admin/pedido.php?id=<?= $order['id'] ?>" 
                                           class="btn btn-sm btn-primary">
                                            Ver
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-muted mb-0">Nenhum pedido registrado.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Mensagens Recentes -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Mensagens Recentes</h5>
                    <a href="<?= BASE_URL ?>/admin/mensagens.php" class="btn btn-sm btn-primary">Ver Todas</a>
                </div>
                <div class="card-body">
                    <?php if ($recent_messages): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_messages as $message): ?>
                        <a href="<?= BASE_URL ?>/admin/mensagens.php?view=<?= $message['id'] ?>" 
                           class="list-group-item list-group-item-action <?= !$message['is_read'] ? 'bg-light' : '' ?>">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?= h($message['sender_name']) ?></h6>
                                <small class="text-muted"><?= date('H:i', strtotime($message['timestamp'])) ?></small>
                            </div>
                            <p class="mb-1 text-truncate"><?= h($message['content']) ?></p>
                            <?php if ($message['order_id']): ?>
                            <small class="text-muted">Ref: Pedido #<?= $message['order_id'] ?></small>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-muted mb-0">Nenhuma mensagem nova.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Ações Rápidas -->
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Ações Rápidas</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <a href="<?= BASE_URL ?>/admin/criar_produto.php" class="btn btn-primary btn-lg w-100 mb-2">
                                <i class="fas fa-plus-circle"></i> Novo Produto
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="<?= BASE_URL ?>/admin/categorias.php" class="btn btn-success btn-lg w-100 mb-2">
                                <i class="fas fa-tags"></i> Categorias
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="<?= BASE_URL ?>/admin/pedidos.php" class="btn btn-info btn-lg w-100 mb-2">
                                <i class="fas fa-shopping-cart"></i> Pedidos
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="<?= BASE_URL ?>/admin/clientes.php" class="btn btn-warning btn-lg w-100 mb-2">
                                <i class="fas fa-users"></i> Clientes
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 