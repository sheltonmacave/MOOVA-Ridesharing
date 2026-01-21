<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connection.php';
require_once '../includes/log_function.php';

$user_id = $_SESSION['user_id'];
$ride_id = intval($_POST['ride_id'] ?? 0);

if ($ride_id <= 0) {
    header("Location: dashboard.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM ride_requests WHERE id = ?");
$stmt->execute([$ride_id]);
$ride = $stmt->fetch();

if (!$ride || $ride['user_id'] != $user_id || in_array($ride['status'], ['finished','cancelled'])) {
    header("Location: dashboard.php");
    exit();
}

$stmt_driver = $pdo->prepare("SELECT car_capacity FROM users WHERE id=?");
$stmt_driver->execute([$ride['driver_id']]);
$driver = $stmt_driver->fetch();
$max_passengers = $driver['car_capacity'] - 1;

$stmt = $pdo->prepare("UPDATE ride_requests 
    SET user_id = driver_id, available_seats = ?, status = 'created'
    WHERE id = ?");
$stmt->execute([$max_passengers, $ride_id]);

log_action($pdo, $user_id, "Cancelou reserva da viagem ID $ride_id");

header("Location: ride.php?ride_id=$ride_id");
exit();
