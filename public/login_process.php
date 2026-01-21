<?php
session_start();
require_once '../includes/db_connection.php';
require_once '../includes/log_function.php';

function sanitize($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = sanitize($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($phone) || empty($password)) {
        $_SESSION['login_error'] = "Ambos os campos são obrigatórios.";
        header('Location: login.php');
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id, name, password_hash, role FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $_SESSION['login_error'] = "Número de Telemóvel ou senha inválidos.";
            
            // Log de tentativa falhada
            log_action($pdo, $user['id'] ?? null, "Tentativa de login falhada para telefone $phone");
            
            header('Location: login.php');
            exit;
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['role'] = $user['role'];

        // Log de login bem-sucedido
        log_action($pdo, $user['id'], "Login realizado");

        header('Location: dashboard.php');
        exit;

    } catch (PDOException $e) {
        error_log("Erro de login: " . $e->getMessage());
        $_SESSION['login_error'] = "Ocorreu um erro inesperado. Tente novamente mais tarde.";
        header('Location: login.php');
        exit;
    }
} else {
    header('Location: login.php');
    exit;
}
?>
