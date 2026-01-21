<?php
session_start();
require_once 'includes/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método Não Permitido');
}

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user_id'] ?? 0;
$driver_id = intval($data['driver_id'] ?? 0);
$notify_date = $data['date'] ?? '';
$pickup = trim($data['pickup'] ?? '');
$destination = trim($data['destination'] ?? '');

if (!$user_id || !$driver_id || !$notify_date || !$pickup || !$destination) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados obrigatórios ausentes']);
    exit;
}

$stmt_driver = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'driver'");
$stmt_driver->execute([$driver_id]);
$driver = $stmt_driver->fetch();

if (!$driver) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Motorista não encontrado']);
    exit;
}

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            driver_id INT NOT NULL,
            date DATE NOT NULL,
            pickup VARCHAR(255) NOT NULL,
            destination VARCHAR(255) NOT NULL,
            status ENUM('pending', 'confirmed', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");

    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, driver_id, date, pickup, destination) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$user_id, $driver_id, $notify_date, $pickup, $destination]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro no servidor: ' . $e->getMessage()]);
}
