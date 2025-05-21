<?php
$pageTitle = 'Minhas Mensagens';
require_once 'auth.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

// Busca todos os assuntos de mensagens do usuário
$selected_subject = null;
$subjects = [];

// Query para buscar assuntos agrupados
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(m.subject, 'Sem assunto') AS subject,
        MIN(m.id) AS first_message_id,
        COUNT(m.id) AS message_count,
        MAX(m.timestamp) AS last_message_time,
        SUM(CASE WHEN m.is_read = 0 AND m.receiver_id = ? THEN 1 ELSE 0 END) AS unread_count
    FROM messages m
    WHERE m.sender_id = ? OR m.receiver_id = ?
    GROUP BY COALESCE(m.subject, 'Sem assunto')
    ORDER BY last_message_time DESC
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
$subjects = $stmt->fetchAll();

// Busca mensagem específica se solicitado
$message_id = intval($_GET['view'] ?? 0);
$message = null;

if ($message_id) {
    $stmt = $pdo->prepare("
        SELECT m.*, u.username as sender_name, u.is_admin, o.id as order_id 
        FROM messages m 
        JOIN users u ON m.sender_id = u.id 
        LEFT JOIN orders o ON m.order_id = o.id 
        WHERE m.id = ? AND (m.sender_id = ? OR m.receiver_id = ?)
    ");
    $stmt->execute([$message_id, $_SESSION['user_id'], $_SESSION['user_id']]);
    $message = $stmt->fetch();
    
    if ($message) {
        // Marca como lida se for o destinatário
        if ($message['receiver_id'] === $_SESSION['user_id'] && !$message['is_read']) {
            $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
            $stmt->execute([$message_id]);
        }
        
        // Define o assunto selecionado
        $selected_subject = $message['subject'];
    } else {
        setFlashMessage('danger', 'Mensagem não encontrada ou você não tem permissão para acessá-la.');
        header("Location: " . BASE_URL . '/cliente/mensagens.php');
        exit();
    }
}

// Se tiver assunto selecionado via GET
if (isset($_GET['subject']) && !$message) {
    $selected_subject = $_GET['subject'];
    
    // Verifica se o assunto existe para este usuário
    $exists = false;
    foreach ($subjects as $subject) {
        if ($subject['subject'] === $selected_subject) {
            $exists = true;
            break;
        }
    }
    
    if (!$exists) {
        setFlashMessage('danger', 'Assunto não encontrado.');
        header("Location: " . BASE_URL . '/cliente/mensagens.php');
        exit();
    }
}

// Busca mensagens do assunto selecionado
$thread_messages = [];
if ($selected_subject) {
    $stmt = $pdo->prepare("
        SELECT m.*, u.username as sender_name, u.is_admin, o.id as order_id 
        FROM messages m 
        JOIN users u ON m.sender_id = u.id 
        LEFT JOIN orders o ON m.order_id = o.id 
        WHERE m.subject = ? AND (m.sender_id = ? OR m.receiver_id = ?)
        ORDER BY m.timestamp ASC
    ");
    $stmt->execute([$selected_subject, $_SESSION['user_id'], $_SESSION['user_id']]);
    $thread_messages = $stmt->fetchAll();
    
    // Marca todas as mensagens deste assunto como lidas
    $stmt = $pdo->prepare("
        UPDATE messages 
        SET is_read = 1 
        WHERE subject = ? AND receiver_id = ? AND is_read = 0
    ");
    $stmt->execute([$selected_subject, $_SESSION['user_id']]);
}

// Processa o envio de mensagens
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $content = trim($_POST['message'] ?? '');
        
        // Nova mensagem para o administrador
        if (isset($_POST['new_message'])) {
            $subject = trim($_POST['subject'] ?? '');
            
            if (empty($subject) || empty($content)) {
                setFlashMessage('danger', 'O assunto e a mensagem são obrigatórios.');
                header("Location: " . BASE_URL . '/cliente/mensagens.php');
                exit();
            }
            
            // Busca o ID do primeiro administrador
            $stmt = $pdo->prepare("SELECT id FROM users WHERE is_admin = 1 LIMIT 1");
            $stmt->execute();
            $admin = $stmt->fetch();
            
            if (!$admin) {
                setFlashMessage('danger', 'Não foi possível encontrar um administrador.');
                header("Location: " . BASE_URL . '/cliente/mensagens.php');
                exit();
            }
            
            // Cria a mensagem
            $stmt = $pdo->prepare("
                INSERT INTO messages (sender_id, receiver_id, subject, content, timestamp) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            if ($stmt->execute([$_SESSION['user_id'], $admin['id'], $subject, $content])) {
                setFlashMessage('success', 'Mensagem enviada com sucesso!');
                header("Location: " . BASE_URL . '/cliente/mensagens.php?subject=' . urlencode($subject));
                exit();
            } else {
                setFlashMessage('danger', 'Erro ao enviar mensagem.');
                header("Location: " . BASE_URL . '/cliente/mensagens.php');
                exit();
            }
        }
        // Resposta a uma mensagem existente
        elseif (isset($_POST['reply_subject'])) {
            $reply_subject = $_POST['reply_subject'] ?? '';
            
            if (empty($content)) {
                setFlashMessage('danger', 'A mensagem não pode estar vazia.');
                header("Location: " . BASE_URL . '/cliente/mensagens.php' . ($reply_subject ? "?subject=" . urlencode($reply_subject) : ''));
                exit();
            }
            
            // Busca o ID do primeiro administrador (destinatário da resposta)
            $stmt = $pdo->prepare("SELECT id FROM users WHERE is_admin = 1 LIMIT 1");
            $stmt->execute();
            $admin = $stmt->fetch();
            
            if (!$admin) {
                setFlashMessage('danger', 'Não foi possível encontrar um administrador.');
                header("Location: " . BASE_URL . '/cliente/mensagens.php');
                exit();
            }
            
            // Cria a resposta
            $stmt = $pdo->prepare("
                INSERT INTO messages (subject, sender_id, receiver_id, content, timestamp) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            if ($stmt->execute([$reply_subject, $_SESSION['user_id'], $admin['id'], $content])) {
                setFlashMessage('success', 'Resposta enviada com sucesso!');
            } else {
                setFlashMessage('danger', 'Erro ao enviar resposta.');
            }
            
            header("Location: " . BASE_URL . '/cliente/mensagens.php?subject=' . urlencode($reply_subject));
            exit();
        }
    } else {
        setFlashMessage('danger', 'Token de segurança inválido.');
        header("Location: " . BASE_URL . '/cliente/mensagens.php');
        exit();
    }
}
?>

<div class="row">
    <!-- Lista de Assuntos -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Assuntos</h5>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                    <i class="fas fa-plus me-1"></i>Nova Mensagem
                </button>
            </div>
            <div class="card-body p-0">
                <?php if ($subjects): ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($subjects as $subject): ?>
                    <a href="<?= BASE_URL ?>/cliente/mensagens.php?subject=<?= urlencode($subject['subject']) ?>" 
                       class="list-group-item list-group-item-action <?= $selected_subject === $subject['subject'] ? 'active' : '' ?>">
                        <div class="d-flex w-100 justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">
                                    <?= h($subject['subject']) ?>
                                </h6>
                                <small class="text-<?= $selected_subject === $subject['subject'] ? 'light' : 'muted' ?>">
                                    <?= $subject['message_count'] ?> mensagen<?= $subject['message_count'] > 1 ? 's' : '' ?> • 
                                    Última: <?= date('d/m/Y H:i', strtotime($subject['last_message_time'])) ?>
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
                <p class="text-muted p-3 mb-0">Nenhuma mensagem encontrada.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Mensagens do Assunto Selecionado -->
    <div class="col-md-8">
        <?php if ($selected_subject && $thread_messages): ?>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <?= h($selected_subject) ?>
                </h5>
                <a href="<?= BASE_URL ?>/cliente/mensagens.php" class="btn btn-outline-secondary btn-sm">
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
                                • <a href="<?= BASE_URL ?>/cliente/pedido.php?id=<?= $msg['order_id'] ?>" class="text-decoration-none">
                                    Pedido #<?= $msg['order_id'] ?>
                                  </a>
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Formulário de Resposta -->
                <form action="<?= BASE_URL ?>/cliente/mensagens.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="reply_subject" value="<?= h($selected_subject) ?>">
                    
                    <div class="mb-3">
                        <label for="message" class="form-label">Responder</label>
                        <textarea class="form-control" id="message" name="message" rows="3" required></textarea>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Enviar Resposta
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                <h4>Selecione um assunto para ver as mensagens</h4>
                <p class="text-muted">
                    Ou inicie uma nova conversa clicando em "Nova Mensagem"
                </p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                    <i class="fas fa-plus me-2"></i>Nova Mensagem
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Nova Mensagem -->
<div class="modal fade" id="newMessageModal" tabindex="-1" aria-labelledby="newMessageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newMessageModalLabel">Nova Mensagem para o Vendedor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?= BASE_URL ?>/cliente/mensagens.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="new_message" value="1">
                    
                    <div class="mb-3">
                        <label for="subject" class="form-label">Assunto</label>
                        <input type="text" class="form-control" id="subject" name="subject" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_message" class="form-label">Mensagem</label>
                        <textarea class="form-control" id="new_message" name="message" rows="4" required></textarea>
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