<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connection.php';
include '../includes/header_private.php';

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    header('Location: login.php');
    exit();
}

$stmt_user = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt_user->execute([$user_id]);
$user = $stmt_user->fetch();
$user_role = $user['role'] ?? 'user';

function getRides($pdo, $role, $user_id) {
    if ($role === 'user') {
        $stmt = $pdo->prepare("
            SELECT rr.*, u.name AS driver_name 
            FROM ride_requests rr
            LEFT JOIN users u ON rr.driver_id = u.id
            WHERE rr.user_id = ? AND rr.status IN ('created','scheduled','in_progress')
            ORDER BY rr.ride_datetime DESC
        ");
        $stmt->execute([$user_id]);
        $reservas = $stmt->fetchAll();

        $pickup_filter = $_GET['pickup'] ?? '';
        $drop_filter = $_GET['drop'] ?? '';
        $sql_disponiveis = "
            SELECT rr.*, u.name AS driver_name 
            FROM ride_requests rr
            LEFT JOIN users u ON rr.driver_id = u.id
            WHERE rr.user_id != ? AND rr.status IN ('created','scheduled')
        ";
        $params = [$user_id];
        if ($pickup_filter) {
            $sql_disponiveis .= " AND rr.pickup_location LIKE ?";
            $params[] = "%$pickup_filter%";
        }
        if ($drop_filter) {
            $sql_disponiveis .= " AND rr.drop_location LIKE ?";
            $params[] = "%$drop_filter%";
        }
        $sql_disponiveis .= " ORDER BY rr.ride_datetime ASC";
        $stmt = $pdo->prepare($sql_disponiveis);
        $stmt->execute($params);
        $disponiveis = $stmt->fetchAll();

        $stmt = $pdo->prepare("
            SELECT rr.*, u.name AS driver_name 
            FROM ride_requests rr
            LEFT JOIN users u ON rr.driver_id = u.id
            WHERE rr.user_id = ? AND rr.status IN ('finished','cancelled')
            ORDER BY rr.ride_datetime DESC
        ");
        $stmt->execute([$user_id]);
        $historico = $stmt->fetchAll();

        return ['reservas'=>$reservas, 'disponiveis'=>$disponiveis, 'historico'=>$historico];
    } else {
        $stmt = $pdo->prepare("SELECT rr.*, u.name AS driver_name FROM ride_requests rr LEFT JOIN users u ON rr.driver_id = u.id WHERE rr.driver_id=? AND rr.status IN ('created','scheduled','in_progress') ORDER BY rr.ride_datetime DESC");
        $stmt->execute([$user_id]);
        $publicadas = $stmt->fetchAll();

        $stmt = $pdo->prepare("SELECT rr.*, u.name AS driver_name FROM ride_requests rr LEFT JOIN users u ON rr.driver_id = u.id WHERE rr.driver_id=? AND rr.status IN ('finished','cancelled') ORDER BY rr.ride_datetime DESC");
        $stmt->execute([$user_id]);
        $historico = $stmt->fetchAll();

        return ['publicadas'=>$publicadas, 'historico'=>$historico];
    }
}

$rides = getRides($pdo, $user_role, $user_id);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Dashboard</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
</head>
<body class="bg-gray-50 font-sans">

<div class="container mx-auto px-4 py-6 max-w-7xl">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Olá, <?= htmlspecialchars($_SESSION['user_name']) ?>!</h1>

    <?php if($user_role === 'user'): ?>
        <!-- RESERVAS -->
        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-4">Suas Reservas</h2>
            <?php if(empty($rides['reservas'])): ?>
                <div class="bg-white p-4 rounded shadow text-center text-gray-600">Nenhuma reserva ainda.</div>
            <?php else: ?>
                <div class="grid md:grid-cols-2 gap-4">
                    <?php foreach($rides['reservas'] as $r): ?>
                        <div class="bg-white rounded shadow p-4">
                            <h3 class="font-semibold"><?= htmlspecialchars($r['pickup_location']) ?> → <?= htmlspecialchars($r['drop_location']) ?></h3>
                            <p class="text-sm text-gray-600"><?= date('d/m/Y H:i', strtotime($r['ride_datetime'])) ?> | Motorista: <?= htmlspecialchars($r['driver_name'] ?: 'Desconhecido') ?></p>
                            <div id="map_res<?= $r['id'] ?>" class="h-40 rounded mt-2"></div>
                            <a href="ride.php?ride_id=<?= $r['id'] ?>" class="mt-2 inline-block bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">Ver Detalhes</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- VIAGENS DISPONÍVEIS -->
        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-4">Viagens Disponíveis</h2>
            <?php if(empty($rides['disponiveis'])): ?>
                <div class="bg-white p-4 rounded shadow text-center text-gray-600">Nenhuma viagem disponível.</div>
            <?php else: ?>
                <div class="grid md:grid-cols-2 gap-4">
                    <?php foreach($rides['disponiveis'] as $r): ?>
                        <div class="bg-white rounded shadow p-4">
                            <h3 class="font-semibold"><?= htmlspecialchars($r['pickup_location']) ?> → <?= htmlspecialchars($r['drop_location']) ?></h3>
                            <p class="text-sm text-gray-600"><?= date('d/m/Y H:i', strtotime($r['ride_datetime'])) ?> | Motorista: <?= htmlspecialchars($r['driver_name'] ?: 'Desconhecido') ?></p>
                            <div id="map_disp<?= $r['id'] ?>" class="h-40 rounded mt-2"></div>
                            <a href="ride.php?ride_id=<?= $r['id'] ?>" class="mt-2 inline-block bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">Ver Detalhes</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- HISTÓRICO -->
        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-4">Histórico de Viagens</h2>
            <?php if(empty($rides['historico'])): ?>
                <div class="bg-white p-4 rounded shadow text-center text-gray-600">Nenhuma viagem no histórico.</div>
            <?php else: ?>
                <div class="grid md:grid-cols-2 gap-4">
                    <?php foreach($rides['historico'] as $r): ?>
                        <div class="bg-white rounded shadow p-4">
                            <h3 class="font-semibold"><?= htmlspecialchars($r['pickup_location']) ?> → <?= htmlspecialchars($r['drop_location']) ?></h3>
                            <p class="text-sm text-gray-600"><?= date('d/m/Y H:i', strtotime($r['ride_datetime'])) ?> | Motorista: <?= htmlspecialchars($r['driver_name'] ?: 'Desconhecido') ?></p>
                            <div id="map_hist<?= $r['id'] ?>" class="h-40 rounded mt-2"></div>
                            <a href="ride.php?ride_id=<?= $r['id'] ?>" class="mt-2 inline-block bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">Ver Detalhes</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    <?php else: ?>
        <!-- MOTORISTA -->
        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Viagens Publicadas</h2>
            <?php if(empty($rides['publicadas'])): ?>
                <div class="bg-white p-4 rounded shadow text-center text-gray-600">Nenhuma viagem publicada.</div>
            <?php else: ?>
                <div class="grid md:grid-cols-2 gap-4">
                    <?php foreach($rides['publicadas'] as $r): ?>
                        <div class="bg-white rounded shadow p-4">
                            <h3 class="font-semibold"><?= htmlspecialchars($r['pickup_location']) ?> → <?= htmlspecialchars($r['drop_location']) ?></h3>
                            <p class="text-sm text-gray-600"><?= date('d/m/Y H:i', strtotime($r['ride_datetime'])) ?></p>
                            <div id="map_pub<?= $r['id'] ?>" class="h-40 rounded mt-2"></div>
                            <a href="ride.php?ride_id=<?= $r['id'] ?>" class="mt-2 inline-block bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">Ver Detalhes</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Histórico</h2>
            <?php if(empty($rides['historico'])): ?>
                <div class="bg-white p-4 rounded shadow text-center text-gray-600">Nenhuma viagem no histórico.</div>
            <?php else: ?>
                <div class="grid md:grid-cols-2 gap-4">
                    <?php foreach($rides['historico'] as $r): ?>
                        <div class="bg-white rounded shadow p-4">
                            <h3 class="font-semibold"><?= htmlspecialchars($r['pickup_location']) ?> → <?= htmlspecialchars($r['drop_location']) ?></h3>
                            <p class="text-sm text-gray-600"><?= date('d/m/Y H:i', strtotime($r['ride_datetime'])) ?></p>
                            <div id="map_hist<?= $r['id'] ?>" class="h-40 rounded mt-2"></div>
                            <a href="ride.php?ride_id=<?= $r['id'] ?>" class="mt-2 inline-block bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">Ver Detalhes</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
<?php
function simpleMaps($rides, $prefix) {
    foreach($rides as $r) {
        $pickupLat = floatval($r['pickup_lat'] ?? 0);
        $pickupLon = floatval($r['pickup_lon'] ?? 0);
        $destLat   = floatval($r['destination_lat'] ?? 0);
        $destLon   = floatval($r['destination_lon'] ?? 0);
        $divId = "map{$prefix}{$r['id']}";
        echo <<<JS
let map_$divId = L.map('$divId').setView([0,0],2);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
  attribution:'&copy; OpenStreetMap contributors'
}).addTo(map_$divId);

let markers = [];
markers.push(L.marker([$pickupLat,$pickupLon]).addTo(map_$divId).bindPopup('Partida'));
markers.push(L.marker([$destLat,$destLon]).addTo(map_$divId).bindPopup('Destino'));

let group = L.featureGroup(markers);
map_$divId.fitBounds(group.getBounds().pad(0.2));

L.polyline([[$pickupLat,$pickupLon],[$destLat,$destLon]], {color:'blue'}).addTo(map_$divId);
JS;
    }
}

if($user_role === 'user') {
    simpleMaps($rides['reservas'],'res');
    simpleMaps($rides['disponiveis'],'disp');
    simpleMaps($rides['historico'],'hist');
} else {
    simpleMaps($rides['publicadas'],'pub');
    simpleMaps($rides['historico'],'hist');
}
?>
});
</script>

<?php include '../includes/footer_private.php'; ?>
</body>
</html>
