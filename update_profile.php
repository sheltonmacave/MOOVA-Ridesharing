<?php
header('Content-Type: application/json');

require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';
require_once 'includes/log_function.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Sessão expirada. Faça login novamente.'
    ]);
    exit;
}

$userId = $_SESSION['user_id'];

$required = ['name', 'gender', 'phone', 'city'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        echo json_encode([
            'status' => 'error',
            'message' => "Campo em falta: $field"
        ]);
        exit;
    }
}

if (!in_array($_POST['gender'], ['Male','Female','Other'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Gênero inválido.'
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE users 
        SET name = ?, gender = ?, phone = ?, city = ?
        WHERE id = ?
    ");

    $success = $stmt->execute([
        $_POST['name'],
        $_POST['gender'],
        $_POST['phone'],
        $_POST['city'],
        $userId
    ]);

    if ($success) {
        log_action($pdo, $userId, "Perfil atualizado");

        echo json_encode([
            'status' => 'success',
            'message' => 'Perfil actualizado com sucesso!'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Falha ao actualizar perfil.'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro no servidor.',
        'debug' => $e->getMessage()
    ]);
}
?>
