<?php
$pageTitle = 'Carrinho de Compras';
require_once 'includes/header.php';

// Inicializa o carrinho na sessão se não existir
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Processa ações do carrinho
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $action = $_POST['action'] ?? '';
        $product_id = intval($_POST['product_id'] ?? 0);
        
        switch ($action) {
            case 'add':
                $quantity = max(1, intval($_POST['quantity'] ?? 1));
                
                // Verifica se o produto existe
                $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch();
                
                if ($product) {
                    if (isset($_SESSION['cart'][$product_id])) {
                        $_SESSION['cart'][$product_id]['quantity'] += $quantity;
                    } else {
                        $_SESSION['cart'][$product_id] = [
                            'title' => $product['title'],
                            'price' => $product['price'],
                            'image_filename' => $product['image_filename'],
                            'quantity' => $quantity
                        ];
                    }
                    setFlashMessage('success', 'Produto adicionado ao carrinho!');
                }
                break;
                
            case 'update':
                $quantity = max(0, intval($_POST['quantity'] ?? 0));
                
                if ($quantity > 0) {
                    $_SESSION['cart'][$product_id]['quantity'] = $quantity;
                } else {
                    unset($_SESSION['cart'][$product_id]);
                }
                break;
                
            case 'remove':
                unset($_SESSION['cart'][$product_id]);
                setFlashMessage('success', 'Produto removido do carrinho!');
                break;
                
            case 'clear':
                $_SESSION['cart'] = [];
                setFlashMessage('success', 'Carrinho esvaziado com sucesso!');
                break;
                
            case 'checkout':
                if (!isLoggedIn()) {
                    $_SESSION['redirect_after_login'] = 'carrinho.php';
                    setFlashMessage('info', 'Faça login para continuar com a compra.');
                    redirect('login.php');
                }
                
                if (empty($_SESSION['cart'])) {
                    setFlashMessage('danger', 'Seu carrinho está vazio.');
                    redirect('carrinho.php');
                }
                
                // Calcula o total
                $total_amount = 0;
                foreach ($_SESSION['cart'] as $item) {
                    $total_amount += $item['price'] * $item['quantity'];
                }
                
                // Cria o pedido
                $stmt = $pdo->prepare("
                    INSERT INTO orders (user_id, total_amount, status, created_at) 
                    VALUES (?, ?, 'pendente', NOW())
                ");
                
                if ($stmt->execute([$_SESSION['user_id'], $total_amount])) {
                    $order_id = $pdo->lastInsertId();
                    
                    // Insere os itens do pedido
                    $stmt = $pdo->prepare("
                        INSERT INTO order_items (order_id, product_id, quantity, price) 
                        VALUES (?, ?, ?, ?)
                    ");
                    
                    foreach ($_SESSION['cart'] as $product_id => $item) {
                        $stmt->execute([
                            $order_id,
                            $product_id,
                            $item['quantity'],
                            $item['price']
                        ]);
                    }
                    
                    // Limpa o carrinho
                    $_SESSION['cart'] = [];
                    
                                        setFlashMessage('success', 'Pedido realizado com sucesso!');                    redirect(BASE_URL . "/cliente/pedido.php?id=$order_id");
                } else {
                    setFlashMessage('danger', 'Erro ao processar o pedido. Tente novamente.');
                }
                break;
        }
        
        if ($action !== 'checkout') {
            redirect('carrinho.php');
        }
    } else {
        setFlashMessage('danger', 'Token de segurança inválido.');
        redirect('carrinho.php');
    }
}

// Calcula totais
$cart_total = 0;
$cart_items = 0;

foreach ($_SESSION['cart'] as $item) {
    $cart_total += $item['price'] * $item['quantity'];
    $cart_items += $item['quantity'];
}
?>

<div class="row">
    <div class="col-12 mb-4">
        <h4>Meu Carrinho (<?= $cart_items ?> <?= $cart_items === 1 ? 'item' : 'itens' ?>)</h4>
    </div>
    
    <?php if (!empty($_SESSION['cart'])): ?>
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th class="text-center">Quantidade</th>
                                <th class="text-end">Preço</th>
                                <th class="text-end">Subtotal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($_SESSION['cart'] as $product_id => $item): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($item['image_filename']): ?>
                                        <img src="<?= BASE_URL ?>/uploads/<?= h($item['image_filename']) ?>" 
                                             alt="<?= h($item['title']) ?>" 
                                             class="img-thumbnail me-3" style="max-height: 64px;">
                                        <?php endif; ?>
                                        <div>
                                            <h6 class="mb-0"><?= h($item['title']) ?></h6>
                                            <small class="text-muted">
                                                <?= formatPrice($item['price']) ?> cada
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center" style="width: 200px;">
                                    <form action="<?= url('carrinho.php') ?>" method="post" class="d-flex align-items-center justify-content-center">
                                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="product_id" value="<?= $product_id ?>">
                                        
                                        <div class="input-group input-group-sm" style="width: 120px;">
                                            <button type="button" class="btn btn-outline-secondary" 
                                                    onclick="updateQuantity(this.form, -1)">-</button>
                                            <input type="number" class="form-control text-center" name="quantity" 
                                                   value="<?= $item['quantity'] ?>" min="1" 
                                                   onchange="this.form.submit()">
                                            <button type="button" class="btn btn-outline-secondary" 
                                                    onclick="updateQuantity(this.form, 1)">+</button>
                                        </div>
                                    </form>
                                </td>
                                <td class="text-end"><?= formatPrice($item['price']) ?></td>
                                <td class="text-end"><?= formatPrice($item['price'] * $item['quantity']) ?></td>
                                <td class="text-end">
                                    <form action="<?= url('carrinho.php') ?>" method="post" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="product_id" value="<?= $product_id ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                onclick="return confirm('Tem certeza que deseja remover este item?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Resumo do Pedido</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <span>Subtotal:</span>
                    <strong><?= formatPrice($cart_total) ?></strong>
                </div>
                
                <hr>
                
                <div class="d-flex justify-content-between mb-3">
                    <span>Total:</span>
                    <strong class="fs-4"><?= formatPrice($cart_total) ?></strong>
                </div>
                
                <form action="<?= url('carrinho.php') ?>" method="post">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="checkout">
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-shopping-cart me-2"></i>Finalizar Compra
                        </button>
                        
                        <button type="submit" class="btn btn-outline-danger" name="action" value="clear" 
                                onclick="return confirm('Tem certeza que deseja esvaziar o carrinho?')">
                            <i class="fas fa-trash me-2"></i>Esvaziar Carrinho
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="col-12">
        <div class="text-center py-5">
            <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
            <h5>Seu carrinho está vazio</h5>
            <p class="text-muted">Adicione produtos ao seu carrinho para continuar comprando.</p>
            <a href="<?= url('produtos.php') ?>" class="btn btn-primary">
                <i class="fas fa-shopping-bag me-2"></i>Continuar Comprando
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function updateQuantity(form, delta) {
    const input = form.querySelector('input[name="quantity"]');
    const newValue = Math.max(1, parseInt(input.value) + delta);
    input.value = newValue;
    form.submit();
}
</script>

<?php require_once 'includes/footer.php'; ?> 