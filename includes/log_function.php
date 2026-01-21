<?php
function log_action(PDO $pdo, ?int $user_id, string $action) {
    try {
        if ($user_id) {
            $stmt = $pdo->prepare("
                INSERT INTO logs (user_id, action, created_at)
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$user_id, $action]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO logs (user_id, action, created_at)
                VALUES (NULL, ?, NOW())
            ");
            $stmt->execute([$action]);
        }
    } catch (PDOException $e) {
        // Apenas registra no log de erro do PHP, sem quebrar a página
        error_log("Falha ao registrar log: " . $e->getMessage());
    }
}
?>
