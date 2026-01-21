<?php
session_start();
require_once '../includes/db_connection.php';
require_once '../includes/log_function.php';

if (isset($_SESSION['user_id'])) {
    log_action($pdo, $_SESSION['user_id'], "Logout realizado");
}

$_SESSION = [];
session_destroy();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

header('Location: login.php');
exit;
?>
