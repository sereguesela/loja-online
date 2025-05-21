<?php
$pageTitle = 'Editar Produto';
require_once 'auth.php';
require_once '../includes/header.php';

// Busca o produto
$product_id = intval($_GET['id'] ?? 0);

if (!$product_id) {
    setFlashMessage('danger', 'Produto não encontrado.');
    redirect(BASE_URL . '/admin/produtos.php');
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    setFlashMessage('danger', 'Produto não encontrado.');
    redirect(BASE_URL . '/admin/produtos.php');
}

// Busca categorias para o formulário
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Processa o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('danger', 'Token de segurança inválido.');
        redirect(BASE_URL . "/admin/editar_produto.php?id=$product_id");
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
        $image_filename = $product['image_filename'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $new_image = uploadImage($_FILES['image']);
            if (!$new_image) {
                $errors[] = 'Erro ao fazer upload da imagem.';
            } else {
                // Remove imagem antiga
                if ($image_filename) {
                    $old_image_path = __DIR__ . '/../uploads/' . $image_filename;
                    if (file_exists($old_image_path)) {
                        unlink($old_image_path);
                    }
                }
                $image_filename = $new_image;
            }
        }
        
        if (empty($errors)) {
            // Atualiza o produto
            $stmt = $pdo->prepare("
                UPDATE products 
                SET title = ?, description = ?, price = ?, stock = ?, category_id = ?, image_filename = ? 
                WHERE id = ?
            ");
            
            if ($stmt->execute([
                $title,
                $description,
                $price,
                $stock,
                $category_id ?: null,
                $image_filename,
                $product_id
            ])) {
                setFlashMessage('success', 'Produto atualizado com sucesso!');
                redirect(BASE_URL . '/admin/produtos.php');
            } else {
                $errors[] = 'Erro ao atualizar produto.';
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
        <h1 class="mb-0">Editar Produto</h1>
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
                                   value="<?= isset($_POST['title']) ? h($_POST['title']) : h($product['title']) ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="price" class="form-label">Preço</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="number" class="form-control" id="price" name="price" required 
                                       step="0.01" min="0" 
                                       value="<?= isset($_POST['price']) ? h($_POST['price']) : h($product['price']) ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="stock" class="form-label">Estoque</label>
                            <input type="number" class="form-control" id="stock" name="stock" required 
                                   min="0" value="<?= isset($_POST['stock']) ? h($_POST['stock']) : h($product['stock']) ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Categoria</label>
                            <select class="form-select" id="category_id" name="category_id">
                                <option value="">Selecione uma categoria</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" 
                                        <?= (isset($_POST['category_id']) ? $_POST['category_id'] : $product['category_id']) == $category['id'] ? 'selected' : '' ?>>
                                    <?= h($category['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="description" class="form-label">Descrição</label>
                            <textarea class="form-control" id="description" name="description" rows="5"><?= isset($_POST['description']) ? h($_POST['description']) : h($product['description']) ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="image" class="form-label">Imagem</label>
                            <?php if ($product['image_filename']): ?>
                            <div class="mb-2">
                                <img src="<?= BASE_URL ?>/uploads/<?= h($product['image_filename']) ?>" 
                                     alt="Imagem atual" class="img-thumbnail" style="max-height: 100px;">
                            </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            <div class="form-text">
                                Formatos aceitos: JPG, JPEG e PNG. Tamanho máximo: 5MB.
                                <?php if ($product['image_filename']): ?>
                                <br>Deixe em branco para manter a imagem atual.
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 