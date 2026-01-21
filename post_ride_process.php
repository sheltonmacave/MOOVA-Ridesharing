<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';
require_once 'includes/log_function.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: post_ride.php");
    exit();
}

$passenger_id = $_SESSION['user_id'] ?? null;
$driver_id = $_SESSION['user_id'];

if (!$passenger_id || !$driver_id) {
    header("Location: login.php");
    exit();
}

$pickup_location = trim($_POST['pickup_location'] ?? '');
$destination     = trim($_POST['destination'] ?? '');
$pickup_time     = $_POST['pickup_time'] ?? '';
$ride_type       = $_POST['ride_type'] ?? '';

if (!$pickup_location || !$destination || !$pickup_time || !$ride_type) {
    header("Location: post_ride.php?error=" . urlencode("Todos os campos obrigatórios devem ser preenchidos."));
    exit();
}

$stmt_driver = $pdo->prepare("SELECT car_capacity FROM users WHERE id = ? AND role = 'driver'");
$stmt_driver->execute([$driver_id]);
$driver = $stmt_driver->fetch();

if (!$driver) {
    header("Location: post_ride.php?error=" . urlencode("Driver inválido ou não encontrado."));
    exit();
}

$available_seats = max(0, intval($driver['car_capacity']) - 1);

try {
    $stmt_insert = $pdo->prepare("
        INSERT INTO ride_requests (
            user_id, driver_id, pickup_location, drop_location, ride_type, ride_datetime, status, available_seats
        ) VALUES (?, ?, ?, ?, ?, ?, 'scheduled', ?)
    ");
    $stmt_insert->execute([
        $passenger_id,
        $driver_id,
        $pickup_location,
        $destination,
        $ride_type,
        $pickup_time,
        $available_seats
    ]);

    $ride_id = $pdo->lastInsertId();

    log_action($pdo, $driver_id, "Criou uma nova viagem (ID: $ride_id) de '$pickup_location' para '$destination'");

} catch (Exception $e) {
    header("Location: post_ride.php?error=" . urlencode("Falha ao criar a viagem: " . $e->getMessage()));
    exit();
}

header("Location: ride.php?ride_id=" . intval($ride_id));
exit();
?>
