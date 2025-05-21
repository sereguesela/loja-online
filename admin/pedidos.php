<?php
$pageTitle = 'Gerenciar Pedidos';
require_once 'auth.php';
require_once '../includes/header.php';

// Processa atualização de status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $order_id = intval($_POST['order_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        
        if ($order_id && in_array($status, ['pendente', 'pago', 'enviado', 'entregue', 'cancelado'])) {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            if ($stmt->execute([$status, $order_id])) {
                setFlashMessage('success', 'Status do pedido atualizado com sucesso!');
            } else {
                setFlashMessage('danger', 'Erro ao atualizar status do pedido.');
            }
        } else {
            setFlashMessage('danger', 'Dados inválidos.');
        }
    } else {
        setFlashMessage('danger', 'Token de segurança inválido.');
    }
    redirect('/admin/pedidos.php');
}

// Filtros
$status_filter = $_GET['status'] ?? '';
$search = $_GET['q'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;

// Construir a query base
$query = "SELECT o.*, u.username, u.email 
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          WHERE 1=1";
$params = [];

// Adiciona filtro por status
if ($status_filter) {
    $query .= " AND o.status = ?";
    $params[] = $status_filter;
}

// Adiciona filtro por busca
if ($search) {
    $query .= " AND (u.username LIKE ? OR u.email LIKE ? OR o.id LIKE ?)";
    $search_term = "%{$search}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

// Conta total de pedidos
$count_stmt = $pdo->prepare(str_replace("o.*, u.username, u.email", "COUNT(*)", $query));
$count_stmt->execute($params);
$total_orders = $count_stmt->fetchColumn();

// Calcula total de páginas
$total_pages = ceil($total_orders / $per_page);
$offset = ($page - 1) * $per_page;

// Busca pedidos com paginação
$query .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Busca detalhes do pedido se solicitado
$order_details = null;
if (isset($_GET['view'])) {
    $order_id = intval($_GET['view']);
    
    // Busca informações do pedido
    $stmt = $pdo->prepare("
        SELECT o.*, u.username, u.email 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?
    ");
    $stmt->execute([$order_id]);
    $order_details = $stmt->fetch();
    
    if ($order_details) {
        // Busca itens do pedido
        $stmt = $pdo->prepare("
            SELECT oi.*, p.title, p.image_filename 
            FROM order_items oi 
            JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order_id]);
        $order_details['items'] = $stmt->fetchAll();
        
        // Busca mensagens do pedido
        $stmt = $pdo->prepare("
            SELECT m.*, u.username 
            FROM messages m 
            JOIN users u ON m.sender_id = u.id 
            WHERE m.order_id = ? 
            ORDER BY m.timestamp DESC
        ");
        $stmt->execute([$order_id]);
        $order_details['messages'] = $stmt->fetchAll();
    }
}
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Gerenciar Pedidos</h1>
    </div>

    <?php if ($orders): ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
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
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?= $order['id'] ?></td>
                            <td><?= h($order['username']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                            <td>
                                <span class="badge bg-<?= getOrderStatus($order['status'])['class'] ?>">
                                    <?= getOrderStatus($order['status'])['label'] ?>
                                </span>
                            </td>
                            <td><?= formatPrice($order['total_amount']) ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>/admin/pedido.php?id=<?= $order['id'] ?>" 
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> Ver
                                </a>
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
        Nenhum pedido encontrado.
    </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?> 