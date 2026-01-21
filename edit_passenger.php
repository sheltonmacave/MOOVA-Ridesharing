<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';
require_once 'includes/log_function.php';

$user_id = $_SESSION['user_id'];
$ride_id = intval($_POST['ride_id'] ?? 0);
$new_passengers = intval($_POST['passengers'] ?? 1);

if ($ride_id <= 0 || $new_passengers < 1) {
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
$current_passengers = $max_passengers - $ride['available_seats'];

$diff = $new_passengers - $current_passengers;
$new_available_seats = $ride['available_seats'] - $diff;

if ($new_available_seats < 0 || $new_available_seats > $max_passengers) {
    header("Location: ride.php?ride_id=$ride_id");
    exit();
}

$stmt = $pdo->prepare("UPDATE ride_requests SET available_seats=? WHERE id=?");
$stmt->execute([$new_available_seats, $ride_id]);

log_action($pdo, $user_id, "Alterou o número de passageiros da viagem $ride_id de $current_passengers para $new_passengers");

header("Location: ride.php?ride_id=$ride_id");
exit();
?>
