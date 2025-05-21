<?php
$pageTitle = 'Novo Produto';
require_once 'auth.php';
require_once '../includes/header.php';

// Busca categorias para o formulário
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Processa o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('danger', 'Token de segurança inválido.');
        redirect(BASE_URL . '/admin/criar_produto.php');
    }
    
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval(str_replace(',', '.', $_POST['price'] ?? 0));
    $stock = intval($_POST['stock'] ?? 0);
    $category_id = intval($_POST['category_id'] ?? 0);
    
    // Validações
    $errors = [];
    
    if (strlen($title) < 3) {
        $errors[] = 'O título deve ter pelo menos 3 caracteres.';
    }
    
    if ($price <= 0) {
        $errors[] = 'O preço deve ser maior que zero.';
    }
    
    if ($stock < 0) {
        $errors[] = 'O estoque não pode ser negativo.';
    }
    
    if (empty($errors)) {
        // Upload de imagem
        $image_filename = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image_filename = uploadImage($_FILES['image']);
            if (!$image_filename) {
                $errors[] = 'Erro ao fazer upload da imagem.';
            }
        }
        
        if (empty($errors)) {
            // Insere o produto
            $stmt = $pdo->prepare("
                INSERT INTO products (title, description, price, stock, category_id, image_filename) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            if ($stmt->execute([
                $title,
                $description,
                $price,
                $stock,
                $category_id ?: null,
                $image_filename
            ])) {
                setFlashMessage('success', 'Produto adicionado com sucesso!');
                redirect(BASE_URL . '/admin/produtos.php');
            } else {
                $errors[] = 'Erro ao adicionar produto.';
            }
        }
    }
    
    if (!empty($errors)) {
        setFlashMessage('danger', $errors[0]);
    }
}
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Novo Produto</h1>
        <a href="<?= BASE_URL ?>/admin/produtos.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Voltar
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="title" class="form-label">Título</label>
                            <input type="text" class="form-control" id="title" name="title" required 
                                   value="<?= isset($_POST['title']) ? h($_POST['title']) : '' ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="price" class="form-label">Preço</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="number" class="form-control" id="price" name="price" required 
                                       step="0.01" min="0" 
                                       value="<?= isset($_POST['price']) ? h($_POST['price']) : '' ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="stock" class="form-label">Estoque</label>
                            <input type="number" class="form-control" id="stock" name="stock" required 
                                   min="0" value="<?= isset($_POST['stock']) ? h($_POST['stock']) : '0' ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Categoria</label>
                            <select class="form-select" id="category_id" name="category_id">
                                <option value="">Selecione uma categoria</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" 
                                        <?= isset($_POST['category_id']) && $_POST['category_id'] == $category['id'] ? 'selected' : '' ?>>
                                    <?= h($category['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="description" class="form-label">Descrição</label>
                            <textarea class="form-control" id="description" name="description" rows="5"><?= isset($_POST['description']) ? h($_POST['description']) : '' ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="image" class="form-label">Imagem</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            <div class="form-text">
                                Formatos aceitos: JPG, JPEG e PNG. Tamanho máximo: 5MB.
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Salvar Produto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 