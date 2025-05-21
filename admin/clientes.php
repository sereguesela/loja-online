<?php
$pageTitle = 'Gerenciar Clientes';
require_once 'auth.php';
require_once '../includes/header.php';

// Configuração da paginação
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;

// Busca total de clientes
$total_customers = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0")->fetchColumn();
$total_pages = ceil($total_customers / $per_page);
$offset = ($page - 1) * $per_page;

// Busca clientes com paginação
$customers = $pdo->query("
    SELECT u.*, 
           COUNT(DISTINCT o.id) as total_orders,
           COALESCE(SUM(o.total_amount), 0) as total_spent,
           COUNT(DISTINCT CASE WHEN m.is_read = 0 AND m.receiver_id = {$_SESSION['user_id']} THEN m.id END) as unread_messages
    FROM users u 
    LEFT JOIN orders o ON u.id = o.user_id 
    LEFT JOIN messages m ON u.id = m.sender_id
    WHERE u.is_admin = 0 
    GROUP BY u.id 
    ORDER BY u.username 
    LIMIT $per_page OFFSET $offset
")->fetchAll();
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Gerenciar Clientes</h1>
    </div>

    <?php if ($customers): ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Email</th>
                            <th>Total de Pedidos</th>
                            <th>Total Gasto</th>
                            <th>Mensagens</th>
                            <th style="width: 150px">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td><?= h($customer['username']) ?></td>
                            <td><?= h($customer['email']) ?></td>
                            <td>
                                <?php if ($customer['total_orders'] > 0): ?>
                                <a href="<?= BASE_URL ?>/admin/pedidos.php?user=<?= $customer['id'] ?>" 
                                   class="text-decoration-none">
                                    <?= $customer['total_orders'] ?> pedido(s)
                                </a>
                                <?php else: ?>
                                Nenhum pedido
                                <?php endif; ?>
                            </td>
                            <td><?= formatPrice($customer['total_spent']) ?></td>
                            <td>
                                <?php if ($customer['unread_messages'] > 0): ?>
                                <a href="<?= BASE_URL ?>/admin/mensagens.php?user=<?= $customer['id'] ?>" 
                                   class="badge bg-warning text-decoration-none">
                                    <?= $customer['unread_messages'] ?> não lida(s)
                                </a>
                                <?php else: ?>
                                <a href="<?= BASE_URL ?>/admin/mensagens.php?user=<?= $customer['id'] ?>" 
                                   class="text-decoration-none">
                                    Ver mensagens
                                </a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="<?= BASE_URL ?>/admin/mensagens.php?user=<?= $customer['id'] ?>" 
                                       class="btn btn-sm btn-primary" title="Enviar mensagem">
                                        <i class="fas fa-envelope"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/admin/pedidos.php?user=<?= $customer['id'] ?>" 
                                       class="btn btn-sm btn-info" title="Ver pedidos">
                                        <i class="fas fa-shopping-cart"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
            <nav aria-label="Navegação de páginas" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page-1 ?>">Anterior</a>
                    </li>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page+1 ?>">Próxima</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-info">
        Nenhum cliente cadastrado.
    </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?> 