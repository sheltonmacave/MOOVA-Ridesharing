<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connection.php';
require_once '../includes/log_function.php';

$ride_id = intval($_GET['ride_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($ride_id <= 0) {
    header("Location: dashboard.php");
    exit();
}

$stmt = $pdo->prepare("UPDATE ride_requests SET status='completed' WHERE id=? AND driver_id=?");
$stmt->execute([$ride_id, $user_id]);

log_action($pdo, $user_id, "Concluiu a viagem $ride_id");

header("Location: ride.php?ride_id=$ride_id");
exit();
?>
