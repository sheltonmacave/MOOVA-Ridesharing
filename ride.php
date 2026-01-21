<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';
include 'includes/header_private.php';

$user_id = $_SESSION['user_id'] ?? 0;
$ride_id = intval($_GET['ride_id'] ?? 0);
if ($ride_id <= 0) {
    header("Location: dashboard.php");
    exit();
}

$stmt_user = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt_user->execute([$user_id]);
$user = $stmt_user->fetch();
$user_role = $user['role'] ?? 'user';

$stmt_ride = $pdo->prepare("SELECT * FROM ride_requests WHERE id = ?");
$stmt_ride->execute([$ride_id]);
$ride = $stmt_ride->fetch();
if (!$ride) {
    echo "<div class='text-center mt-8 text-red-600'>Viagem não encontrada.</div>";
    exit();
}

$stmt_driver = $pdo->prepare("SELECT id, name, car_capacity FROM users WHERE id = ?");
$stmt_driver->execute([$ride['driver_id']]);
$driver = $stmt_driver->fetch();

$passenger_registered = ($ride['user_id'] == $user_id && $user_role === 'user');

$max_passengers = $driver['car_capacity'] - 1;
$available_seats = $ride['available_seats'];
?>

<section class="max-w-4xl mx-auto px-4 py-8 bg-white shadow-lg rounded-lg mt-8 mb-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Detalhes da Viagem</h2>

    <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <p><strong>Motorista:</strong> <?= htmlspecialchars($driver['name']) ?></p>
            <p><strong>Partida:</strong> <?= htmlspecialchars($ride['pickup_location']) ?></p>
            <p><strong>Destino:</strong> <?= htmlspecialchars($ride['drop_location']) ?></p>
            <p><strong>Data/Hora:</strong> <?= date('d/m/Y H:i', strtotime($ride['ride_datetime'])) ?></p>
            <p><strong>Tipo de Viagem:</strong> <?= htmlspecialchars($ride['ride_type']) ?></p>
            <p><strong>Status:</strong> <?= htmlspecialchars($ride['status']) ?></p>
            <p><strong>Assentos Disponíveis:</strong> <?= $available_seats ?></p>
        </div>

        <div id="rideMap" class="rounded-lg shadow" style="height: 300px; min-width:300px;"></div>
    </div>

    <?php if(in_array($ride['status'], ['finished','cancelled'])): ?>
        <div class="text-center mt-4">
            <?php if($ride['status'] === 'finished'): ?>
                <p class="text-green-600 font-semibold mb-4">Esta viagem já foi finalizada.</p>
            <?php else: ?>
                <p class="text-red-600 font-semibold mb-4">Esta viagem foi cancelada.</p>
            <?php endif; ?>
            <a href="dashboard.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Voltar ao Dashboard</a>
        </div>
    <?php else: ?>
        <?php if($user_role === 'driver' && $ride['driver_id'] === $user_id): ?>
            <!-- Botões do motorista -->
            <div class="flex flex-wrap gap-4 mt-4">
                <a href="edit_ride.php?ride_id=<?= $ride_id ?>" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Editar</a>
                <a href="cancel_ride.php?ride_id=<?= $ride_id ?>" class="border border-red-600 text-red-600 px-4 py-2 rounded hover:bg-red-600 hover:text-white transition">Cancelar</a>
                <a href="finish_ride.php?ride_id=<?= $ride_id ?>" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Finalizar</a>
            </div>
        <?php elseif($user_role === 'user'): ?>
            <?php if(!$passenger_registered): ?>
                <!-- Cadastro passageiro -->
                <form method="POST" action="register_passenger.php" class="mt-4">
                    <input type="hidden" name="ride_id" value="<?= $ride_id ?>">
                    <label class="block mb-2 font-semibold">Número de passageiros:</label>
                    <input type="number" name="passengers" min="1" max="<?= $available_seats ?>" value="1" class="border p-2 rounded w-24">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 ml-2">Cadastrar</button>
                </form>
            <?php else: ?>
                <!-- Editar / Cancelar participação -->
                <form method="POST" action="edit_passenger.php" class="mt-4 flex gap-2 items-center">
                    <input type="hidden" name="ride_id" value="<?= $ride_id ?>">
                    <label class="font-semibold">Número de passageiros:</label>
                    <input type="number" name="passengers" min="1" max="<?= $driver['car_capacity']-1 ?>" value="<?= $ride['available_seats'] ?>" class="border p-2 rounded w-24">
                    <button type="submit" class="border border-blue-600 text-blue-600 px-4 py-2 rounded hover:bg-blue-600 hover:text-white transition">Editar Cadastro</button>
                </form>
                <form method="POST" action="cancel_passenger.php" class="mt-2">
                    <input type="hidden" name="ride_id" value="<?= $ride_id ?>">
                    <button type="submit" class="border border-red-600 text-red-600 px-4 py-2 rounded hover:bg-red-600 hover:text-white transition">Cancelar Participação</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</section>

<!-- Leaflet e Routing Machine -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />
<script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>

<script>
let map = L.map('rideMap').setView([0,0],2);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

let markers = [];
let routeControl = null;

async function geocode(address, label) {
    if (!address) return;
    try {
        let response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}`);
        let data = await response.json();
        if (data && data.length > 0) {
            let { lat, lon } = data[0];
            let marker = L.marker([lat, lon]).addTo(map).bindPopup(label);
            markers.push(marker);

            if (markers.length === 2) {
                if (routeControl) map.removeControl(routeControl);
                routeControl = L.Routing.control({
                    waypoints: [markers[0].getLatLng(), markers[1].getLatLng()],
                    lineOptions: { addWaypoints: false, draggableWaypoints: false, extendToWaypoints: false },
                    routeWhileDragging: false,
                    showAlternatives: false,
                    createMarker: () => null
                }).addTo(map);

                let group = new L.featureGroup(markers);
                map.fitBounds(group.getBounds().pad(0.2));
            }
        }
    } catch (err) {
        console.error("Erro no geocoding:", err);
    }
}

geocode("<?= addslashes($ride['pickup_location']) ?>", "Partida");
geocode("<?= addslashes($ride['drop_location']) ?>", "Destino");
</script>

<?php include 'includes/footer_private.php'; ?>
