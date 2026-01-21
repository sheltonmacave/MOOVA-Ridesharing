<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connection.php';
require_once '../includes/log_function.php';

$ride_id = intval($_GET['ride_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($ride_id > 0) {
    $stmt = $pdo->prepare("
        UPDATE ride_requests 
        SET status = 'cancelled', available_seats = 0 
        WHERE id = ? AND driver_id = ?
    ");
    $stmt->execute([$ride_id, $user_id]);

    log_action($pdo, $user_id, "Cancelou a viagem ID $ride_id");
}

header("Location: ride.php?ride_id=$ride_id");
exit();
