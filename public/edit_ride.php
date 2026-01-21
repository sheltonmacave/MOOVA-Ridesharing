<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connection.php';
require_once '../includes/log_function.php';

$user_id = $_SESSION['user_id'] ?? 0;
$ride_id = intval($_GET['ride_id'] ?? 0);

if ($ride_id <= 0) {
    header("Location: dashboard.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM ride_requests WHERE id = ? AND driver_id = ?");
$stmt->execute([$ride_id, $user_id]);
$ride = $stmt->fetch();

if (!$ride) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pickup_location = trim($_POST['pickup_location']);
    $destination     = trim($_POST['destination']);
    $pickup_time     = $_POST['pickup_time'];
    $ride_type       = $_POST['ride_type'];

    if ($pickup_location && $destination && $pickup_time && $ride_type) {
        $stmt_update = $pdo->prepare("
            UPDATE ride_requests 
            SET pickup_location=?, drop_location=?, ride_type=?, ride_datetime=?
            WHERE id=?
        ");
        $stmt_update->execute([$pickup_location, $destination, $ride_type, $pickup_time, $ride_id]);
        
        log_action($pdo, $user_id, "Editou a viagem $ride_id: 
            Partida: '$old_pickup' → '$pickup_location', 
            Destino: '$old_drop' → '$destination', 
            Data/Hora: '$old_datetime' → '$pickup_time', 
            Tipo: '$old_type' → '$ride_type'");
        header("Location: ride.php?ride_id=$ride_id");
        exit();
    } else {
        $error = "Todos os campos devem ser preenchidos.";
    }
}

include '../includes/header_private.php';
?>

<section class="max-w-4xl mx-auto px-4 py-8 bg-white shadow-lg rounded-lg mt-8 mb-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Editar Viagem</h2>

    <?php if($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Local de Partida</label>
                <input type="text" id="pickup_location" name="pickup_location" value="<?= htmlspecialchars($ride['pickup_location']) ?>" class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-600" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Destino</label>
                <input type="text" id="destination" name="destination" value="<?= htmlspecialchars($ride['drop_location']) ?>" class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-600" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data/Hora de Partida</label>
                <input type="datetime-local" name="pickup_time" value="<?= date('Y-m-d\TH:i', strtotime($ride['ride_datetime'])) ?>" class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-600" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Viagem</label>
                <select name="ride_type" class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-600" required>
                    <option value="drop" <?= $ride['ride_type']=='drop'?'selected':'' ?>>Ida</option>
                    <option value="pick" <?= $ride['ride_type']=='pick'?'selected':'' ?>>Volta</option>
                    <option value="roundtrip" <?= $ride['ride_type']=='roundtrip'?'selected':'' ?>>Ida e Volta</option>
                </select>
            </div>
        </div>

        <div id="rideMap" style="height: 350px;" class="mt-4 rounded shadow"></div>

        <div class="flex flex-wrap gap-4 mt-4">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Atualizar Viagem</button>
            <a href="cancel_ride.php?ride_id=<?= $ride_id ?>" class="border border-blue-600 text-blue-600 px-4 py-2 rounded hover:bg-blue-50">Cancelar Viagem</a>
        </div>
    </form>
</section>

<!-- Leaflet -->
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
        let res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}`);
        let data = await res.json();
        if (data && data.length > 0) {
            let { lat, lon } = data[0];
            let marker = L.marker([lat, lon]).addTo(map).bindPopup(label);
            markers.push(marker);

            if (markers.length === 2) {
                if (routeControl) map.removeControl(routeControl);
                routeControl = L.Routing.control({
                    waypoints: [markers[0].getLatLng(), markers[1].getLatLng()],
                    lineOptions: { addWaypoints: false, draggableWaypoints: false },
                    routeWhileDragging: false,
                    showAlternatives: false,
                    createMarker: () => null
                }).addTo(map);

                let group = L.featureGroup(markers);
                map.fitBounds(group.getBounds().pad(0.2));
            }
        }
    } catch (err) {
        console.error("Erro no geocoding:", err);
    }
}

geocode("<?= addslashes($ride['pickup_location']) ?>", "Partida");
geocode("<?= addslashes($ride['drop_location']) ?>", "Destino");

document.getElementById('pickup_location').addEventListener('blur', () => {
    markers.forEach(m => map.removeLayer(m));
    markers = [];
    routeControl && map.removeControl(routeControl);
    geocode(document.getElementById('pickup_location').value, "Partida");
    geocode(document.getElementById('destination').value, "Destino");
});

document.getElementById('destination').addEventListener('blur', () => {
    markers.forEach(m => map.removeLayer(m));
    markers = [];
    routeControl && map.removeControl(routeControl);
    geocode(document.getElementById('pickup_location').value, "Partida");
    geocode(document.getElementById('destination').value, "Destino");
});
</script>
