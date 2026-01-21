<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connection.php';
require_once '../includes/log_function.php';

$user_id = $_SESSION['user_id'];
$ride_id = intval($_POST['ride_id'] ?? 0);
$passengers = intval($_POST['passengers'] ?? 1);

if ($ride_id <= 0 || $passengers < 1) {
    header("Location: dashboard.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM ride_requests WHERE id = ?");
$stmt->execute([$ride_id]);
$ride = $stmt->fetch();

if (!$ride || in_array($ride['status'], ['finished','cancelled'])) {
    header("Location: dashboard.php");
    exit();
}

if ($ride['user_id'] == $user_id) {
    header("Location: ride.php?ride_id=$ride_id");
    exit();
}

if ($passengers > $ride['available_seats']) {
    header("Location: ride.php?ride_id=$ride_id");
    exit();
}

$new_available = $ride['available_seats'] - $passengers;
$stmt = $pdo->prepare("UPDATE ride_requests SET user_id=?, available_seats=? WHERE id=?");
$stmt->execute([$user_id, $new_available, $ride_id]);

log_action($pdo, $user_id, "Inscreveu-se na viagem (ID: $ride_id) como passageiro com $passengers assento(s)");

header("Location: ride.php?ride_id=$ride_id");
exit();
?>
