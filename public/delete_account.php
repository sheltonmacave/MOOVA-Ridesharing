<?php
header('Content-Type: application/json');

require_once '../includes/auth_check.php';
require_once '../includes/db_connection.php';
require_once '../includes/log_function.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Sessão inválida.'
    ]);
    exit;
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['delete_account'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Pedido inválido.'
    ]);
    exit;
}

try {
    $pdo->beginTransaction();

    log_action($pdo, $userId, "Conta eliminada pelo utilizador");

    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);

    $pdo->commit();

    $_SESSION = [];
    session_destroy();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Conta eliminada com sucesso.',
        'redirect' => 'login.php'
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro ao eliminar conta.',
        'debug' => $e->getMessage()
    ]);
}
?>
