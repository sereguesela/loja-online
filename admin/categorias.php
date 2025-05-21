<?php
$pageTitle = 'Gerenciar Categorias';
require_once 'auth.php';
require_once '../includes/header.php';

// Processa exclusão de categoria
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('danger', 'Token de segurança inválido.');
        redirect(BASE_URL . '/admin/categorias.php');
    }
    
    $category_id = intval($_POST['category_id']);
    
    // Verifica se existem produtos na categoria
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
    $stmt->execute([$category_id]);
    $products_count = $stmt->fetchColumn();
    
    if ($products_count > 0) {
        setFlashMessage('danger', 'Não é possível excluir uma categoria que possui produtos.');
    } else {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        if ($stmt->execute([$category_id])) {
            setFlashMessage('success', 'Categoria excluída com sucesso!');
        } else {
            setFlashMessage('danger', 'Erro ao excluir categoria.');
        }
    }
    
    redirect(BASE_URL . '/admin/categorias.php');
}

// Processa adição/edição de categoria
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('danger', 'Token de segurança inválido.');
        redirect(BASE_URL . '/admin/categorias.php');
    }
    
    $category_id = intval($_POST['category_id'] ?? 0);
    $name = trim($_POST['name']);
    
    if (strlen($name) < 2) {
        setFlashMessage('danger', 'O nome da categoria deve ter pelo menos 2 caracteres.');
    } else {
        // Verifica se já existe uma categoria com este nome
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
        $stmt->execute([$name, $category_id]);
        if ($stmt->fetch()) {
            setFlashMessage('danger', 'Já existe uma categoria com este nome.');
        } else {
            if ($category_id > 0) {
                // Atualiza categoria existente
                $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?");
                if ($stmt->execute([$name, $category_id])) {
                    setFlashMessage('success', 'Categoria atualizada com sucesso!');
                } else {
                    setFlashMessage('danger', 'Erro ao atualizar categoria.');
                }
            } else {
                // Insere nova categoria
                $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
                if ($stmt->execute([$name])) {
                    setFlashMessage('success', 'Categoria adicionada com sucesso!');
                } else {
                    setFlashMessage('danger', 'Erro ao adicionar categoria.');
                }
            }
        }
    }
    
    redirect(BASE_URL . '/admin/categorias.php');
}

// Busca categoria para edição
$edit_category = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([intval($_GET['edit'])]);
    $edit_category = $stmt->fetch();
}

// Busca todas as categorias
$categories = $pdo->query("
    SELECT c.*, COUNT(p.id) as total_products 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id 
    GROUP BY c.id 
    ORDER BY c.name
")->fetchAll();
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Gerenciar Categorias</h1>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><?= $edit_category ? 'Editar Categoria' : 'Nova Categoria' ?></h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <?php if ($edit_category): ?>
                        <input type="hidden" name="category_id" value="<?= $edit_category['id'] ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Nome da Categoria</label>
                            <input type="text" class="form-control" id="name" name="name" required 
                                   value="<?= $edit_category ? h($edit_category['name']) : '' ?>">
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">
                                <?= $edit_category ? 'Atualizar' : 'Adicionar' ?>
                            </button>
                            <?php if ($edit_category): ?>
                            <a href="<?= BASE_URL ?>/admin/categorias.php" class="btn btn-outline-secondary">Cancelar</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <?php if ($categories): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Total de Produtos</th>
                                    <th style="width: 150px">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><?= h($category['name']) ?></td>
                                    <td><?= $category['total_products'] ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="?edit=<?= $category['id'] ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($category['total_products'] == 0): ?>
                                            <form method="post" class="d-inline" 
                                                  onsubmit="return confirm('Tem certeza que deseja excluir esta categoria?');">
                                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                                <input type="hidden" name="category_id" value="<?= $category['id'] ?>">
                                                <input type="hidden" name="delete_category" value="1">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-muted mb-0">Nenhuma categoria cadastrada.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 