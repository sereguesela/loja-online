<?php
$pageTitle = 'Página Inicial';
require_once 'includes/header.php';

// Busca produtos em destaque
$stmt = $pdo->query("SELECT p.*, c.name as category_name 
                     FROM products p 
                     LEFT JOIN categories c ON p.category_id = c.id 
                     ORDER BY p.created_at DESC 
                     LIMIT 6");
$featured_products = $stmt->fetchAll();

// Busca todas as categorias
$stmt = $pdo->query("SELECT * FROM categories");
$categories = $stmt->fetchAll();
?>

<div class="row">
    <!-- Banner Principal -->
    <div class="col-12 mb-4">
        <div class="p-5 mb-4 bg-light rounded-3">
            <div class="container-fluid py-5">
                <h1 class="display-5 fw-bold">Bem-vindo à Loja Online</h1>
                <p class="col-md-8 fs-4">Encontre os melhores produtos com os melhores preços.</p>
                <a class="btn btn-primary btn-lg" href="<?= BASE_URL ?>/produtos.php">Ver Produtos</a>
            </div>
        </div>
    </div>

    <!-- Produtos em Destaque -->
    <div class="col-12">
        <h2 class="mb-4">Produtos em Destaque</h2>
        <div class="row">
            <?php foreach ($featured_products as $product): ?>
            <div class="col-sm-6 col-md-4 col-lg-4 mb-4">
                <div class="card h-100">
                    <?php if ($product['image_filename']): ?>
                    <img src="<?= BASE_URL ?>/uploads/<?= h($product['image_filename']) ?>" class="card-img-top" alt="<?= h($product['title']) ?>">
                    <?php else: ?>
                    <img src="https://via.placeholder.com/300x200" class="card-img-top" alt="<?= h($product['title']) ?>">
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?= h($product['title']) ?></h5>
                        <p class="card-text text-truncate"><?= h($product['description']) ?></p>
                        <p class="card-price"><?= formatPrice($product['price']) ?></p>
                        <div class="d-flex justify-content-between">
                            <a href="<?= BASE_URL ?>/produto.php?id=<?= $product['id'] ?>" class="btn btn-outline-primary">
                                <i class="fas fa-eye me-1"></i>Detalhes
                            </a>
                            <?php if (isLoggedIn() && !isAdmin()): ?>
                            <a href="<?= BASE_URL ?>/cliente/comprar.php?id=<?= $product['id'] ?>" class="btn btn-primary">
                                <i class="fas fa-shopping-cart me-1"></i>Comprar
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="<?= BASE_URL ?>/produtos.php" class="btn btn-outline-primary btn-lg">
                Ver todos os produtos
            </a>
        </div>
    </div>

    <!-- Informações sobre o site -->
    <div class="col-12 mt-5">
        <div class="row g-4 py-5">
            <div class="col-md-4">
                <div class="feature text-center">
                    <div class="feature-icon bg-primary bg-gradient rounded-circle p-3 d-inline-flex mb-3">
                        <i class="fas fa-truck text-white fa-2x"></i>
                    </div>
                    <h3>Entrega Rápida</h3>
                    <p>Entregamos para todo o Brasil com eficiência e segurança. Você pode combinar o método de entrega pelo chat.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature text-center">
                    <div class="feature-icon bg-primary bg-gradient rounded-circle p-3 d-inline-flex mb-3">
                        <i class="fas fa-lock text-white fa-2x"></i>
                    </div>
                    <h3>Pagamento Seguro</h3>
                    <p>Pagamentos via PIX com total segurança e praticidade.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature text-center">
                    <div class="feature-icon bg-primary bg-gradient rounded-circle p-3 d-inline-flex mb-3">
                        <i class="fas fa-comments text-white fa-2x"></i>
                    </div>
                    <h3>Suporte ao Cliente</h3>
                    <p>Entre em contato diretamente com nossa equipe através do chat para tirar suas dúvidas.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 