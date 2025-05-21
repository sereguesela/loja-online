<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

echo "<pre>";
echo "Status da Sessão:\n";
echo "----------------\n";
echo "session_id: " . session_id() . "\n";
echo "user_id: " . ($_SESSION['user_id'] ?? 'não definido') . "\n";
echo "is_admin: " . var_export(($_SESSION['is_admin'] ?? false), true) . "\n\n";

echo "Dados do Usuário no Banco:\n";
echo "-------------------------\n";
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT id, email, is_admin FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if ($user) {
        echo "ID: " . $user['id'] . "\n";
        echo "Email: " . $user['email'] . "\n";
        echo "is_admin no banco: " . var_export((bool)$user['is_admin'], true) . "\n";
    } else {
        echo "Usuário não encontrado no banco de dados.\n";
    }
} else {
    echo "Nenhum usuário logado.\n";
}
echo "</pre>"; 