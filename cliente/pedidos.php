<?php
$pageTitle = 'Meus Pedidos';
require_once 'auth.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

// Filtros
$status_filter = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;

// Construir a query base
$query = "SELECT * FROM orders WHERE user_id = ?";
$params = [$_SESSION['user_id']];

// Adiciona filtro por status
if ($status_filter) {
    $query .= " AND status = ?";
    $params[] = $status_filter;
}

// Conta total de pedidos
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM ($query) as t");
$count_stmt->execute($params);
$total_orders = $count_stmt->fetchColumn();

// Calcula total de páginas
$total_pages = ceil($total_orders / $per_page);
$offset = ($page - 1) * $per_page;

// Busca pedidos com paginação
$query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Busca totais por status
$status_counts = [];
$stmt = $pdo->prepare("
    SELECT status, COUNT(*) as total 
    FROM orders 
    WHERE user_id = ? 
    GROUP BY status
");
$stmt->execute([$_SESSION['user_id']]);
while ($row = $stmt->fetch()) {
    $status_counts[$row['status']] = $row['total'];
}
?>

<div class="row">
    <!-- Filtros -->
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <?php
                    $status_options = [
                        'pendente' => ['label' => 'Pendentes', 'class' => 'warning'],
                        'pago' => ['label' => 'Pagos', 'class' => 'success'],
                        'enviado' => ['label' => 'Enviados', 'class' => 'info'],
                        'entregue' => ['label' => 'Entregues', 'class' => 'primary'],
                        'cancelado' => ['label' => 'Cancelados', 'class' => 'danger']
                    ];
                    
                    foreach ($status_options as $status => $info):
                        $count = $status_counts[$status] ?? 0;
                    ?>
                    <div class="col">
                        <a href="?status=<?= $status ?>" class="text-decoration-none">
                            <div class="card <?= $status_filter === $status ? 'border-'.$info['class'] : '' ?>">
                                <div class="card-body text-center">
                                    <h3 class="mb-0"><?= $count ?></h3>
                                    <small class="text-muted"><?= $info['label'] ?></small>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                    
                                        <?php if ($status_filter): ?>                    <div class="col d-flex align-items-center justify-content-center">                        <a href="<?= BASE_URL ?>/cliente/pedidos.php" class="btn btn-outline-secondary">                            <i class="fas fa-times me-2"></i>Limpar Filtro                        </a>                    </div>                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Lista de Pedidos -->
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <?php if ($status_filter): ?>
                    Pedidos <?= $status_options[$status_filter]['label'] ?>
                    <?php else: ?>
                    Todos os Pedidos
                    <?php endif; ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if ($orders): ?>
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
                            <?php foreach ($orders as $order): ?>
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
                                                                        <a href="<?= BASE_URL ?>/cliente/pedido.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-primary">                                        <i class="fas fa-eye me-1"></i>Detalhes                                    </a>                                    <?php if ($order['status'] === 'pendente'): ?>                                    <a href="<?= BASE_URL ?>/cliente/pagar.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-success">                                        <i class="fas fa-money-bill me-1"></i>Pagar                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginação -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Navegação de páginas" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page-1 ?><?= $status_filter ? '&status='.$status_filter : '' ?>">
                                Anterior
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?><?= $status_filter ? '&status='.$status_filter : '' ?>">
                                <?= $i ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page+1 ?><?= $status_filter ? '&status='.$status_filter : '' ?>">
                                Próxima
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
                
                <?php else: ?>
                <p class="text-muted mb-0">
                    <?php if ($status_filter): ?>
                    Nenhum pedido <?= strtolower($status_options[$status_filter]['label']) ?>.
                    <?php else: ?>
                    Você ainda não fez nenhum pedido.
                    <?php endif; ?>
                </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 