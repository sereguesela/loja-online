<?php
$pageTitle = 'Gerenciar Produtos';
require_once 'auth.php';
require_once '../includes/header.php';

// Configuração da paginação
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;

// Busca total de produtos
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_pages = ceil($total_products / $per_page);
$offset = ($page - 1) * $per_page;

// Busca produtos com paginação
$products = $pdo->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.id DESC 
    LIMIT $per_page OFFSET $offset
")->fetchAll();

// Processa exclusão de produto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('danger', 'Token de segurança inválido.');
        redirect(BASE_URL . '/admin/produtos.php');
    }
    
    $product_id = intval($_POST['product_id']);
    
    // Verifica se o produto existe
    $stmt = $pdo->prepare("SELECT image_filename FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if ($product) {
        // Remove a imagem se existir
        if ($product['image_filename']) {
            $image_path = __DIR__ . '/../uploads/' . $product['image_filename'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        // Remove o produto
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        if ($stmt->execute([$product_id])) {
            setFlashMessage('success', 'Produto excluído com sucesso!');
        } else {
            setFlashMessage('danger', 'Erro ao excluir produto.');
        }
    }
    
    redirect(BASE_URL . '/admin/produtos.php');
}
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Gerenciar Produtos</h1>
        <a href="<?= BASE_URL ?>/admin/criar_produto.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Novo Produto
        </a>
    </div>

    <?php if ($products): ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th style="width: 100px">Imagem</th>
                            <th>Produto</th>
                            <th>Categoria</th>
                            <th>Preço</th>
                            <th>Estoque</th>
                            <th style="width: 150px">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <?php if ($product['image_filename']): ?>
                                <img src="<?= BASE_URL ?>/uploads/<?= h($product['image_filename']) ?>" 
                                     alt="<?= h($product['title']) ?>" 
                                     class="img-thumbnail" style="max-height: 50px;">
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= h($product['title']) ?>
                                <?php if ($product['description']): ?>
                                <br><small class="text-muted"><?= h($product['description']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= h($product['category_name'] ?? 'Sem categoria') ?></td>
                            <td><?= formatPrice($product['price']) ?></td>
                            <td><?= $product['stock'] ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="<?= BASE_URL ?>/admin/editar_produto.php?id=<?= $product['id'] ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="post" class="d-inline" 
                                          onsubmit="return confirm('Tem certeza que deseja excluir este produto?');">
                                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <input type="hidden" name="delete_product" value="1">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
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
        Nenhum produto cadastrado.
    </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?> 