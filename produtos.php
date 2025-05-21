<?php
$pageTitle = 'Produtos';
require_once 'includes/header.php';

// Filtros
$category_id = intval($_GET['categoria'] ?? 0);
$search = trim($_GET['q'] ?? '');
$sort = $_GET['ordem'] ?? 'recentes';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 12;

// Busca categorias
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

// Construir a query base
$query = "
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE 1=1
";
$params = [];

// Adiciona filtro por categoria
if ($category_id) {
    $query .= " AND p.category_id = ?";
    $params[] = $category_id;
}

// Adiciona filtro por busca
if ($search) {
    $query .= " AND (p.title LIKE ? OR p.description LIKE ?)";
    $search_term = "%{$search}%";
    $params[] = $search_term;
    $params[] = $search_term;
}

// Conta total de produtos
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM ($query) as t");
$count_stmt->execute($params);
$total_products = $count_stmt->fetchColumn();

// Calcula total de páginas
$total_pages = ceil($total_products / $per_page);
$offset = ($page - 1) * $per_page;

// Adiciona ordenação
switch ($sort) {
    case 'preco_menor':
        $query .= " ORDER BY p.price ASC";
        break;
    case 'preco_maior':
        $query .= " ORDER BY p.price DESC";
        break;
    case 'nome':
        $query .= " ORDER BY p.title ASC";
        break;
    case 'recentes':
    default:
        $query .= " ORDER BY p.created_at DESC";
        break;
}

// Adiciona paginação
$query .= " LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;

// Busca produtos
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<div class="row">
    <!-- Filtros -->
    <div class="col-md-3 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Filtros</h5>
            </div>
            <div class="card-body">
                <form method="get" class="mb-4">
                    <div class="mb-3">
                        <label for="q" class="form-label">Buscar</label>
                        <input type="text" class="form-control" id="q" name="q" value="<?= h($search) ?>" 
                               placeholder="Digite o que procura...">
                    </div>
                    
                    <div class="mb-3">
                        <label for="categoria" class="form-label">Categoria</label>
                        <select class="form-select" id="categoria" name="categoria">
                            <option value="">Todas as Categorias</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" <?= $category_id === $category['id'] ? 'selected' : '' ?>>
                                <?= h($category['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="ordem" class="form-label">Ordenar por</label>
                        <select class="form-select" id="ordem" name="ordem">
                            <option value="recentes" <?= $sort === 'recentes' ? 'selected' : '' ?>>Mais Recentes</option>
                            <option value="preco_menor" <?= $sort === 'preco_menor' ? 'selected' : '' ?>>Menor Preço</option>
                            <option value="preco_maior" <?= $sort === 'preco_maior' ? 'selected' : '' ?>>Maior Preço</option>
                            <option value="nome" <?= $sort === 'nome' ? 'selected' : '' ?>>Nome</option>
                        </select>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-2"></i>Filtrar
                        </button>
                        
                        <?php if ($search || $category_id || $sort !== 'recentes'): ?>
                        <a href="<?= url('produtos.php') ?>" class="btn btn-outline-secondary mt-2">
                            <i class="fas fa-times me-2"></i>Limpar Filtros
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Lista de Produtos -->
    <div class="col-md-9">
        <?php if ($search || $category_id): ?>
        <div class="mb-4">
            <h4>
                <?php if ($search): ?>
                Resultados para "<?= h($search) ?>"
                <?php endif; ?>
                
                <?php if ($category_id): ?>
                <?php foreach ($categories as $category): ?>
                <?php if ($category['id'] === $category_id): ?>
                na categoria "<?= h($category['name']) ?>"
                <?php endif; ?>
                <?php endforeach; ?>
                <?php endif; ?>
                
                <small class="text-muted">(<?= $total_products ?> produtos encontrados)</small>
            </h4>
        </div>
        <?php endif; ?>
        
        <?php if ($products): ?>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach ($products as $product): ?>
            <div class="col">
                <div class="card h-100">
                    <?php if ($product['image_filename']): ?>
                    <img src="<?= BASE_URL ?>/uploads/<?= h($product['image_filename']) ?>" 
                         alt="<?= h($product['title']) ?>" 
                         class="card-img-top">
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <?php if ($product['category_name']): ?>
                        <div class="mb-2">
                            <a href="<?= url('produtos.php?categoria=' . $product['category_id']) ?>" class="text-decoration-none">
                                <span class="badge bg-secondary">
                                    <?= h($product['category_name']) ?>
                                </span>
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <h5 class="card-title">
                            <a href="<?= url('produto.php?id=' . $product['id']) ?>" class="text-decoration-none">
                                <?= h($product['title']) ?>
                            </a>
                        </h5>
                        
                        <p class="card-text text-primary fw-bold fs-5 mb-3">
                            <?= formatPrice($product['price']) ?>
                        </p>
                        
                        <div class="d-grid gap-2">
                            <a href="<?= url('produto.php?id=' . $product['id']) ?>" class="btn btn-outline-primary">
                                Ver Detalhes
                            </a>
                            
                            <form action="<?= url('carrinho.php') ?>" method="post">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                <input type="hidden" name="quantity" value="1">
                                
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-shopping-cart me-2"></i>Comprar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Paginação -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Navegação de páginas" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page-1 ?><?= $search ? '&q='.urlencode($search) : '' ?><?= $category_id ? '&categoria='.$category_id : '' ?><?= $sort !== 'recentes' ? '&ordem='.$sort : '' ?>">
                        Anterior
                    </a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?><?= $search ? '&q='.urlencode($search) : '' ?><?= $category_id ? '&categoria='.$category_id : '' ?><?= $sort !== 'recentes' ? '&ordem='.$sort : '' ?>">
                        <?= $i ?>
                    </a>
                </li>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page+1 ?><?= $search ? '&q='.urlencode($search) : '' ?><?= $category_id ? '&categoria='.$category_id : '' ?><?= $sort !== 'recentes' ? '&ordem='.$sort : '' ?>">
                        Próxima
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
        
        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
            <h5>Nenhum produto encontrado</h5>
            <p class="text-muted">
                <?php if ($search || $category_id): ?>
                Tente mudar os filtros ou fazer uma nova busca.
                <?php else: ?>
                Não há produtos cadastrados no momento.
                <?php endif; ?>
            </p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 