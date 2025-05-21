<?php
require_once 'includes/functions.php';
require_once 'includes/header.php';

// Busca informações do produto
$product_id = intval($_GET['id'] ?? 0);

if (!$product_id) {
    setFlashMessage('danger', 'Produto não encontrado.');
    redirect(BASE_URL . '/produtos.php');
}

$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ?
");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    setFlashMessage('danger', 'Produto não encontrado.');
    redirect(BASE_URL . '/produtos.php');
}

$pageTitle = $product['title'];

// Busca produtos relacionados
$stmt = $pdo->prepare("
    SELECT * FROM products 
    WHERE category_id = ? AND id != ? 
    ORDER BY RAND() 
    LIMIT 4
");
$stmt->execute([$product['category_id'], $product_id]);
$related_products = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-md-6">
        <?php if ($product['image_filename']): ?>
        <div class="card mb-4">
            <div class="card-body p-0">
                <img src="<?= BASE_URL ?>/uploads/<?= h($product['image_filename']) ?>" 
                     alt="<?= h($product['title']) ?>" 
                     class="img-fluid rounded">
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-md-6">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= url('produtos.php') ?>">Produtos</a></li>
                <?php if ($product['category_name']): ?>
                <li class="breadcrumb-item">
                    <a href="<?= url('produtos.php?categoria=' . $product['category_id']) ?>">
                        <?= h($product['category_name']) ?>
                    </a>
                </li>
                <?php endif; ?>
                <li class="breadcrumb-item active" aria-current="page"><?= h($product['title']) ?></li>
            </ol>
        </nav>
        
        <h1 class="h2 mb-3"><?= h($product['title']) ?></h1>
        
        <div class="mb-4">
            <span class="fs-3 fw-bold text-primary"><?= formatPrice($product['price']) ?></span>
        </div>
        
        <div class="mb-4">
            <?= nl2br(h($product['description'])) ?>
        </div>
        
        <form action="<?= url('carrinho.php') ?>" method="post">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
            
            <div class="row g-3 align-items-center mb-4">
                <div class="col-auto">
                    <label for="quantity" class="col-form-label">Quantidade:</label>
                </div>
                <div class="col-auto">
                    <input type="number" id="quantity" name="quantity" class="form-control" 
                           value="1" min="1" style="width: 80px;">
                </div>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-shopping-cart me-2"></i>Adicionar ao Carrinho
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($related_products): ?>
<div class="row mt-5">
    <div class="col-12">
        <h3>Produtos Relacionados</h3>
        <hr>
    </div>
    
    <?php foreach ($related_products as $related): ?>
    <div class="col-md-3 mb-4">
        <div class="card h-100">
            <?php if ($related['image_filename']): ?>
            <img src="<?= BASE_URL ?>/uploads/<?= h($related['image_filename']) ?>" 
                 alt="<?= h($related['title']) ?>" 
                 class="card-img-top">
            <?php endif; ?>
            
            <div class="card-body">
                <h5 class="card-title"><?= h($related['title']) ?></h5>
                <p class="card-text text-primary fw-bold"><?= formatPrice($related['price']) ?></p>
                                <a href="<?= url('produto.php?id=' . $related['id']) ?>" class="btn btn-outline-primary">                    Ver Detalhes                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?> 