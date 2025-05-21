<?php
$pageTitle = 'Mensagens';
require_once 'auth.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

// Busca cliente selecionado
$selected_user = null;
if (isset($_GET['user'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND is_admin = 0");
    $stmt->execute([intval($_GET['user'])]);
    $selected_user = $stmt->fetch();
}

// Busca assuntos disponíveis para o cliente selecionado
$subjects = [];
$selected_subject = null;

if ($selected_user && isset($_GET['subject'])) {
    $selected_subject = $_GET['subject'];
    
    // Verifica se o assunto existe para este cliente
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM messages 
        WHERE subject = ? AND (sender_id = ? OR receiver_id = ?)
    ");
    $stmt->execute([$selected_subject, $selected_user['id'], $selected_user['id']]);
    if ($stmt->fetchColumn() == 0) {
        setFlashMessage('danger', 'Assunto não encontrado para este cliente.');
        header("Location: " . BASE_URL . "/admin/mensagens.php?user={$selected_user['id']}");
        exit();
    }
}

// Se tiver cliente selecionado, busca assuntos dele
if ($selected_user) {
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(m.subject, 'Sem assunto') AS subject,
            COUNT(m.id) AS message_count,
            MAX(m.timestamp) AS last_message_time,
            SUM(CASE WHEN m.is_read = 0 AND m.receiver_id = ? THEN 1 ELSE 0 END) AS unread_count
        FROM messages m
        WHERE (m.sender_id = ? OR m.receiver_id = ?)
        GROUP BY COALESCE(m.subject, 'Sem assunto')
        ORDER BY last_message_time DESC
    ");
    $stmt->execute([$_SESSION['user_id'], $selected_user['id'], $selected_user['id']]);
    $subjects = $stmt->fetchAll();
}

// Busca mensagens do assunto selecionado
$thread_messages = [];
if ($selected_user && $selected_subject) {
    $stmt = $pdo->prepare("
        SELECT m.*, u.username as sender_name, u.is_admin, o.id as order_id 
        FROM messages m 
        JOIN users u ON m.sender_id = u.id 
        LEFT JOIN orders o ON m.order_id = o.id 
        WHERE m.subject = ? AND (m.sender_id = ? OR m.receiver_id = ?)
        ORDER BY m.timestamp ASC
    ");
    $stmt->execute([$selected_subject, $selected_user['id'], $selected_user['id']]);
    $thread_messages = $stmt->fetchAll();
    
    // Marca todas as mensagens deste assunto como lidas
    $stmt = $pdo->prepare("
        UPDATE messages 
        SET is_read = 1 
        WHERE subject = ? AND sender_id = ? AND receiver_id = ? AND is_read = 0
    ");
    $stmt->execute([$selected_subject, $selected_user['id'], $_SESSION['user_id']]);
}

// Processa envio de mensagem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('danger', 'Token de segurança inválido.');
        header("Location: " . BASE_URL . '/admin/mensagens.php');
        exit();
    }
    
    $content = trim($_POST['content']);
    $receiver_id = intval($_POST['receiver_id'] ?? 0);
    $subject = $_POST['subject'] ?? '';
    
    if (strlen($content) < 1) {
        setFlashMessage('danger', 'A mensagem não pode estar vazia.');
    } else if (strlen($subject) < 1) {
        setFlashMessage('danger', 'O assunto não pode estar vazio.');
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO messages (sender_id, receiver_id, subject, content, timestamp) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        if ($stmt->execute([$_SESSION['user_id'], $receiver_id, $subject, $content])) {
            setFlashMessage('success', 'Mensagem enviada com sucesso!');
            
            if ($selected_user) {
                header("Location: " . BASE_URL . "/admin/mensagens.php?user={$selected_user['id']}&subject=" . urlencode($subject));
                exit();
            } else {
                header("Location: " . BASE_URL . '/admin/mensagens.php');
                exit();
            }
        } else {
            setFlashMessage('danger', 'Erro ao enviar mensagem.');
        }
    }
}

// Busca clientes com mensagens não lidas
$customers_with_messages = $pdo->query("
    SELECT u.*, 
           COUNT(DISTINCT m.id) as total_messages,
           COUNT(DISTINCT CASE WHEN m.is_read = 0 AND m.receiver_id = {$_SESSION['user_id']} THEN m.id END) as unread_messages,
           MAX(m.timestamp) as last_message
    FROM users u 
    JOIN messages m ON (u.id = m.sender_id OR u.id = m.receiver_id)
    WHERE u.is_admin = 0 
    GROUP BY u.id 
    ORDER BY unread_messages DESC, last_message DESC
")->fetchAll();
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Mensagens</h1>
    </div>

    <div class="row">
        <!-- Lista de Clientes -->
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Clientes</h5>
                </div>
                <div class="card-body p-0">
                    <?php if ($customers_with_messages): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($customers_with_messages as $customer): ?>
                        <a href="<?= BASE_URL ?>/admin/mensagens.php?user=<?= $customer['id'] ?>" 
                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= $selected_user && $selected_user['id'] == $customer['id'] ? 'active' : '' ?>">
                            <div>
                                <h6 class="mb-1"><?= h($customer['username']) ?></h6>
                                <small class="text-<?= $selected_user && $selected_user['id'] == $customer['id'] ? 'light' : 'muted' ?>">
                                    <?= $customer['total_messages'] ?> mensagens
                                </small>
                            </div>
                            <?php if ($customer['unread_messages'] > 0): ?>
                            <span class="badge bg-<?= $selected_user && $selected_user['id'] == $customer['id'] ? 'light text-primary' : 'danger' ?> rounded-pill">
                                <?= $customer['unread_messages'] ?>
                            </span>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-muted p-3 mb-0">Nenhuma mensagem encontrada.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Lista de Assuntos -->
        <div class="col-md-3 mb-4">
            <?php if ($selected_user): ?>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Assuntos de <?= h($selected_user['username']) ?></h5>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                        <i class="fas fa-plus me-1"></i>Nova
                    </button>
                </div>
                <div class="card-body p-0">
                    <?php if ($subjects): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($subjects as $subject): ?>
                        <a href="<?= BASE_URL ?>/admin/mensagens.php?user=<?= $selected_user['id'] ?>&subject=<?= urlencode($subject['subject']) ?>" 
                           class="list-group-item list-group-item-action <?= $selected_subject === $subject['subject'] ? 'active' : '' ?>">
                            <div class="d-flex w-100 justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">
                                        <?= h($subject['subject']) ?>
                                    </h6>
                                    <small class="text-<?= $selected_subject === $subject['subject'] ? 'light' : 'muted' ?>">
                                        <?= $subject['message_count'] ?> mensagen<?= $subject['message_count'] > 1 ? 's' : '' ?> • 
                                        <?= date('d/m/Y H:i', strtotime($subject['last_message_time'])) ?>
                                    </small>
                                </div>
                                <?php if ($subject['unread_count'] > 0): ?>
                                <span class="badge bg-<?= $selected_subject === $subject['subject'] ? 'light text-primary' : 'primary' ?> rounded-pill">
                                    <?= $subject['unread_count'] ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-muted p-3 mb-0">Nenhum assunto encontrado.</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-user fa-3x text-muted mb-3"></i>
                    <h5>Selecione um cliente</h5>
                    <p class="text-muted">Escolha um cliente para ver os assuntos de conversa.</p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Mensagens do Assunto -->
        <div class="col-md-6">
            <?php if ($selected_user && $selected_subject && count($thread_messages) > 0): ?>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <?= h($selected_subject) ?>
                    </h5>
                    <a href="<?= BASE_URL ?>/admin/mensagens.php?user=<?= $selected_user['id'] ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-times me-1"></i>Fechar
                    </a>
                </div>
                <div class="card-body">
                    <div class="chat-messages mb-4" style="max-height: 400px; overflow-y: auto;">
                        <?php foreach ($thread_messages as $msg): ?>
                        <div class="chat-message mb-3 <?= $msg['sender_id'] === $_SESSION['user_id'] ? 'text-end' : '' ?>">
                            <div class="d-inline-block text-start">
                                <div class="message-bubble p-3 rounded <?= $msg['is_admin'] ? 'bg-primary text-white' : 'bg-light' ?>">
                                    <?= nl2br(h($msg['content'])) ?>
                                </div>
                                <small class="text-muted d-block mt-1">
                                    <?= $msg['is_admin'] ? 'Administrador' : h($msg['sender_name']) ?> • 
                                    <?= date('d/m/Y H:i', strtotime($msg['timestamp'])) ?>
                                    <?php if ($msg['order_id']): ?>
                                    • <a href="<?= BASE_URL ?>/admin/pedido.php?id=<?= $msg['order_id'] ?>" class="text-decoration-none">
                                        Pedido #<?= $msg['order_id'] ?>
                                      </a>
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Formulário de Resposta -->
                    <form action="<?= BASE_URL ?>/admin/mensagens.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="receiver_id" value="<?= $selected_user['id'] ?>">
                        <input type="hidden" name="subject" value="<?= h($selected_subject) ?>">
                        
                        <div class="mb-3">
                            <label for="content" class="form-label">Responder</label>
                            <textarea class="form-control" id="content" name="content" rows="3" required></textarea>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Enviar Resposta
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php elseif ($selected_user && $selected_subject): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                    <h5>Nenhuma mensagem neste assunto</h5>
                    <p class="text-muted">Inicie uma nova conversa sobre este assunto.</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                        <i class="fas fa-plus me-2"></i>Nova Mensagem
                    </button>
                </div>
            </div>
            <?php elseif ($selected_user): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-comment-dots fa-3x text-muted mb-3"></i>
                    <h5>Selecione um assunto</h5>
                    <p class="text-muted">Escolha um assunto para ver as mensagens ou inicie uma nova conversa.</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                        <i class="fas fa-plus me-2"></i>Nova Mensagem
                    </button>
                </div>
            </div>
            <?php else: ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5>Sistema de Mensagens</h5>
                    <p class="text-muted">Selecione um cliente na lista à esquerda para ver e responder mensagens.</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Nova Mensagem -->
<div class="modal fade" id="newMessageModal" tabindex="-1" aria-labelledby="newMessageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newMessageModalLabel">Nova Mensagem para <?= $selected_user ? h($selected_user['username']) : '' ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?= BASE_URL ?>/admin/mensagens.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="receiver_id" value="<?= $selected_user ? $selected_user['id'] : '' ?>">
                    
                    <div class="mb-3">
                        <label for="subject" class="form-label">Assunto</label>
                        <input type="text" class="form-control" id="subject" name="subject" required 
                               value="<?= $selected_subject ?? '' ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="content" class="form-label">Mensagem</label>
                        <textarea class="form-control" id="content" name="content" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Enviar Mensagem</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.message-bubble {
    max-width: 80%;
    word-wrap: break-word;
}
.chat-messages {
    display: flex;
    flex-direction: column;
}
</style>

<?php require_once '../includes/footer.php'; ?> 